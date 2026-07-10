<?php
// ============================================
// api/generate-reviews.php
// ============================================
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$clientId = isset($input['client_id']) ? intval($input['client_id']) : 0;
$rating = isset($input['rating']) ? intval($input['rating']) : 5;
$service = trim($input['service'] ?? '');

if (!$clientId || $rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Fetch client
$db = getDB();
$stmt = $db->prepare("SELECT * FROM clients WHERE id = ? AND is_active = 1");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    echo json_encode(['error' => 'Client not found']);
    exit;
}

if (!empty($client['link_expire_at']) && strtotime($client['link_expire_at']) < time()) {
    echo json_encode(['error' => 'Plan expired']);
    exit;
}

if (!clientHasActiveSubscription($client)) {
    echo json_encode(['error' => 'Plan is not active']);
    exit;
}

// Get AI provider settings
$aiProvider = getSetting('ai_provider') ?: 'openai';
$wordLimit = intval(getSetting('review_word_limit') ?: 60);
$reviewsPerClick = intval(getSetting('reviews_per_click') ?: 5);
if ($reviewsPerClick < 1 || $reviewsPerClick > 10) {
    $reviewsPerClick = 5;
}

if ($aiProvider === 'gemini') {
    $apiKey = getSetting('gemini_api_key');
    $model = getSetting('gemini_model') ?: 'gemini-2.0-flash';
} else {
    $apiKey = getSetting('openai_api_key');
    $model = getSetting('openai_model') ?: 'gpt-4o-mini';
}

if (empty($apiKey)) {
    echo json_encode(['error' => ucfirst($aiProvider) . ' API key not configured']);
    exit;
}

// Star labels and tone control
$starLabels = [1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars'];
$ratingStyle = [
    1 => 'Keep it short and mild. Mention one minor issue but keep the overall tone supportive and polite.',
    2 => 'Slightly mixed but still positive overall. Mention room for improvement with respectful wording.',
    3 => 'Balanced and positive, highlighting decent service and a couple of good points.',
    4 => 'Clearly positive with strong appreciation and useful specifics.',
    5 => 'Very enthusiastic and highly satisfied, warm and detailed.'
];
$starLabel = $starLabels[$rating];
$serviceLine = $service !== '' ? "Selected service by customer: {$service}" : 'No service selected.';

// Build prompt
$instructions = $client['chatgpt_instructions'] ?: 'Generate genuine, helpful customer reviews.';
$prompt = "Generate exactly {$reviewsPerClick} unique, realistic Google reviews for a business called \"{$client['company_name']}\".

Business instructions/context: {$instructions}
{$serviceLine}

Requirements:
- Every review must remain positive in tone, even for low star ratings
- The review should align with a {$starLabel} experience
- {$ratingStyle[$rating]}
- If a service is selected, naturally mention that specific service in each review
- Reviews must sound like real customers wrote them (varied tone, style, length)
- Each review must be under {$wordLimit} words. Keep them concise.
- Make them specific and believable, mentioning realistic details
- Vary the writing style (some casual, some formal, some emotional)
- Do NOT number them or add labels
- Separate each review with the delimiter: |||

Return ONLY the {$reviewsPerClick} reviews separated by ||| with no other text.";

$systemMsg = 'You are a helpful assistant that generates realistic customer reviews. Return only the reviews as instructed.';

if ($aiProvider === 'gemini') {
    // Call Gemini API
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $payload = [
        'contents' => [
            ['parts' => [['text' => $systemMsg . "\n\n" . $prompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0.9,
            'maxOutputTokens' => 1500
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $err = json_decode($response, true);
        $errMsg = $err['error']['message'] ?? 'Unknown error';
        echo json_encode(['error' => 'Gemini API error: ' . $errMsg]);
        exit;
    }

    $data = json_decode($response, true);
    $content = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

} else {
    // Call OpenAI
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemMsg],
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 1500,
        'temperature' => 0.9
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $err = json_decode($response, true);
        echo json_encode(['error' => 'OpenAI API error: ' . ($err['error']['message'] ?? 'Unknown error')]);
        exit;
    }

    $data = json_decode($response, true);
    $content = trim($data['choices'][0]['message']['content'] ?? '');
}

// Parse reviews
$reviews = array_map('trim', explode('|||', $content));
$reviews = array_filter($reviews, fn($r) => strlen($r) > 10);
$reviews = array_values($reviews);

if (count($reviews) < 1) {
    echo json_encode(['error' => 'Failed to generate reviews']);
    exit;
}

echo json_encode([
    'success' => true,
    'reviews' => $reviews,
    'rating' => $rating,
    'google_review_link' => $client['google_review_link']
]);
?>
