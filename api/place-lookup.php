<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isCustomerLoggedIn() && !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required.']);
    exit;
}

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 3) {
    echo json_encode(['success' => false, 'message' => 'Enter a business name or location.']);
    exit;
}

$key = trim(getSetting('google_maps_api_key') ?: '');
if ($key === '') {
    echo json_encode(['success' => false, 'message' => 'Google Maps API key is not configured. Paste the Google Review link manually or ask admin to add the API key.']);
    exit;
}

$url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?' . http_build_query([
    'input' => $query,
    'inputtype' => 'textquery',
    'fields' => 'place_id,name,formatted_address',
    'key' => $key
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15
]);
$body = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => $error ?: 'Could not contact Google Places API.']);
    exit;
}

$data = json_decode($body, true);
if (($data['status'] ?? '') !== 'OK' || empty($data['candidates'])) {
    echo json_encode(['success' => false, 'message' => $data['error_message'] ?? 'No matching place found. Try business name with city.']);
    exit;
}

$places = array_map(function ($place) {
    return [
        'place_id' => $place['place_id'] ?? '',
        'name' => $place['name'] ?? '',
        'address' => $place['formatted_address'] ?? '',
        'review_link' => !empty($place['place_id']) ? googleReviewLinkFromPlaceId($place['place_id']) : ''
    ];
}, $data['candidates']);

echo json_encode(['success' => true, 'places' => $places]);
