<?php
require_once __DIR__ . '/config.php';

$siteName = 'AI Google Reviews';
$phoneDisplay = '97805-51900';
$phoneDial = '9780551900';
$whatsappNumber = '919780551900';
$email = 'info@officialdigitalmarketing.in';
$db = getDB();
$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$addons = $db->query("SELECT * FROM addons WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AI Google Reviews helps businesses generate AI-powered Google Review suggestions, copy them easily, and share QR codes for fast Google Review collection.">
  <title><?= htmlspecialchars($siteName) ?> - AI Review Generate System</title>
  <style>
    :root {
      --primary: #058a36;
      --primary-dark: #04662a;
      --primary-soft: #e9f8ef;
      --secondary: #f0b400;
      --secondary-dark: #b98700;
      --ink: #102016;
      --muted: #627064;
      --paper: #ffffff;
      --warm: #fff8df;
      --line: rgba(16, 32, 22, 0.12);
      --shadow: 0 22px 70px rgba(4, 82, 33, 0.16);
      --radius: 8px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      color: var(--ink);
      background: #fbfdf9;
      font-family: Arial, Helvetica, sans-serif;
      line-height: 1.6;
      overflow-x: hidden;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    img {
      display: block;
      max-width: 100%;
    }

    .wrap {
      width: min(1140px, calc(100% - 40px));
      margin: 0 auto;
    }

    .site-header {
      position: fixed;
      inset: 0 0 auto;
      z-index: 30;
      border-bottom: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(7, 64, 31, 0.78);
      color: #fff;
      backdrop-filter: blur(16px);
      transition: box-shadow 0.25s ease, background 0.25s ease;
    }

    .site-header.scrolled {
      background: rgba(5, 82, 33, 0.94);
      box-shadow: 0 12px 40px rgba(3, 45, 20, 0.22);
    }

    .nav {
      min-height: 76px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 800;
      letter-spacing: 0;
      white-space: nowrap;
    }

    .brand-mark {
      width: 42px;
      height: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid rgba(240, 180, 0, 0.95);
      border-radius: var(--radius);
      background: #fff;
      box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.08);
      position: relative;
      color: #fff;
    }

    .brand-google-icon {
      width: 28px;
      height: 28px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: conic-gradient(#4285f4 0 25%, #34a853 25% 46%, #fbbc05 46% 68%, #ea4335 68% 100%);
      color: #fff;
      font-size: 1rem;
      font-weight: 900;
      box-shadow: inset 0 0 0 5px #fff;
    }

    .brand-star {
      position: absolute;
      right: -5px;
      bottom: -5px;
      width: 20px;
      height: 20px;
      display: grid;
      place-items: center;
      border: 2px solid #fff;
      border-radius: 50%;
      background: var(--secondary);
      color: #171200;
      font-size: 0.76rem;
      line-height: 1;
      box-shadow: 0 6px 14px rgba(16, 32, 22, 0.22);
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 24px;
      font-size: 0.94rem;
      color: rgba(255, 255, 255, 0.9);
    }

    .nav-links a {
      transition: color 0.2s ease;
    }

    .nav-links a:hover {
      color: var(--secondary);
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn {
      min-height: 46px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      border: 1px solid transparent;
      border-radius: var(--radius);
      padding: 12px 18px;
      font-weight: 800;
      line-height: 1.1;
      cursor: pointer;
      transition: transform 0.22s ease, box-shadow 0.22s ease, background 0.22s ease, color 0.22s ease;
    }

    .btn svg {
      width: 18px;
      height: 18px;
      flex: 0 0 auto;
    }

    .btn:hover {
      transform: translateY(-2px);
    }

    .btn-primary {
      background: var(--secondary);
      color: #171200;
      box-shadow: 0 16px 36px rgba(240, 180, 0, 0.28);
    }

    .btn-primary:hover {
      background: #ffd24a;
    }

    .btn-green {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 16px 36px rgba(5, 138, 54, 0.26);
    }

    .btn-green:hover {
      background: var(--primary-dark);
    }

    .btn-light {
      border-color: rgba(255, 255, 255, 0.34);
      color: #fff;
      background: rgba(255, 255, 255, 0.12);
    }

    .btn-light:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .hero {
      min-height: 92vh;
      position: relative;
      display: grid;
      align-items: end;
      color: #fff;
      isolation: isolate;
      overflow: hidden;
      background:
        linear-gradient(110deg, rgba(3, 47, 22, 0.98) 0%, rgba(5, 138, 54, 0.88) 47%, rgba(240, 180, 0, 0.58) 100%),
        url("demo-qr.png") right 8% center / min(46vw, 520px) no-repeat,
        #064b24;
    }

    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      z-index: -1;
      opacity: 0.16;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.14) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.14) 1px, transparent 1px);
      background-size: 56px 56px;
      animation: gridDrift 16s linear infinite;
    }

    .hero::after {
      content: "";
      position: absolute;
      inset: auto 0 0;
      height: 35%;
      z-index: -1;
      background: linear-gradient(0deg, rgba(251, 253, 249, 1), rgba(251, 253, 249, 0));
    }

    @keyframes gridDrift {
      from { background-position: 0 0; }
      to { background-position: 56px 56px; }
    }

    .hero-inner {
      padding: 148px 0 108px;
    }

    .eyebrow {
      width: fit-content;
      display: inline-flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 22px;
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 999px;
      padding: 8px 13px;
      background: rgba(255, 255, 255, 0.13);
      color: rgba(255, 255, 255, 0.92);
      font-size: 0.84rem;
      font-weight: 700;
    }

    .eyebrow span {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--secondary);
      box-shadow: 0 0 0 7px rgba(240, 180, 0, 0.22);
    }

    .hero-google-badge {
      width: fit-content;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
      padding: 12px 16px;
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.93);
      color: var(--ink);
      box-shadow: 0 20px 44px rgba(3, 45, 20, 0.2);
      backdrop-filter: blur(12px);
      font-size: 1rem;
      font-weight: 900;
    }

    .hero-google-badge .google-g {
      width: 34px;
      height: 34px;
      flex: 0 0 auto;
    }

    .hero-google-badge .stars-inline {
      display: inline-block;
      margin-left: 8px;
      font-size: 0.9rem;
    }

    .hero h1 {
      max-width: 780px;
      font-size: clamp(2.75rem, 7vw, 6.75rem);
      line-height: 0.95;
      letter-spacing: 0;
      text-wrap: balance;
    }

    .hero-copy {
      max-width: 680px;
      margin-top: 26px;
      color: rgba(255, 255, 255, 0.86);
      font-size: clamp(1.05rem, 2vw, 1.24rem);
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-top: 34px;
    }

    .hero-stats {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 1px;
      max-width: 760px;
      margin-top: 52px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: var(--radius);
      background: rgba(255, 255, 255, 0.16);
      backdrop-filter: blur(12px);
    }

    .hero-stat {
      padding: 18px;
      background: rgba(255, 255, 255, 0.1);
    }

    .hero-stat strong {
      display: block;
      color: var(--secondary);
      font-size: clamp(1.4rem, 3vw, 2.1rem);
      line-height: 1.1;
    }

    .hero-stat span {
      display: block;
      margin-top: 5px;
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.91rem;
    }

    .quick-strip {
      position: relative;
      z-index: 4;
      margin-top: -48px;
      padding-bottom: 40px;
    }

    .quick-strip .hero-stats {
      max-width: none;
      margin-top: 0;
      border-color: var(--line);
      background: var(--paper);
      box-shadow: var(--shadow);
      backdrop-filter: none;
    }

    .quick-strip .hero-stat {
      background: var(--paper);
    }

    .quick-strip .hero-stat strong {
      color: var(--primary);
    }

    .quick-strip .hero-stat:nth-child(2) strong {
      color: var(--secondary-dark);
    }

    .quick-strip .hero-stat span {
      color: var(--muted);
    }

    .section {
      padding: 92px 0;
    }

    .section.alt {
      background: var(--paper);
    }

    .section-head {
      max-width: 760px;
      margin-bottom: 34px;
    }

    .section-kicker {
      color: var(--primary);
      font-size: 0.83rem;
      font-weight: 900;
      letter-spacing: 0.09em;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .section h2 {
      font-size: clamp(2rem, 4.3vw, 3.5rem);
      line-height: 1.05;
      letter-spacing: 0;
      text-wrap: balance;
    }

    .section-head p {
      margin-top: 16px;
      color: var(--muted);
      font-size: 1.08rem;
      max-width: 680px;
    }

    .google-word {
      display: inline-flex;
      align-items: baseline;
      gap: 0;
      font-weight: 900;
      letter-spacing: 0;
      white-space: nowrap;
    }

    .google-word .blue { color: #4285f4; }
    .google-word .red { color: #ea4335; }
    .google-word .yellow { color: #fbbc05; }
    .google-word .green { color: #34a853; }

    .google-badge {
      width: fit-content;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 18px;
      padding: 10px 14px;
      border: 1px solid rgba(16, 32, 22, 0.12);
      border-radius: 999px;
      background: #fff;
      box-shadow: 0 12px 32px rgba(16, 32, 22, 0.08);
      color: var(--ink);
      font-size: 0.95rem;
      font-weight: 800;
    }

    .google-g {
      width: 30px;
      height: 30px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: conic-gradient(#4285f4 0 25%, #34a853 25% 45%, #fbbc05 45% 68%, #ea4335 68% 100%);
      color: #fff;
      font-weight: 900;
      box-shadow: inset 0 0 0 5px #fff;
    }

    .google-review-section {
      position: relative;
      overflow: hidden;
      background: linear-gradient(180deg, #fff 0%, #f5fbf7 100%);
    }

    .google-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 38px;
      align-items: center;
    }

    .google-copy h2 {
      margin-bottom: 16px;
    }

    .google-copy p {
      color: var(--muted);
      font-size: 1.08rem;
      max-width: 620px;
    }

    .google-review-cards {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 26px;
    }

    .google-review-card {
      padding: 18px;
      border: 1px solid var(--line);
      border-radius: var(--radius);
      background: #fff;
      box-shadow: 0 14px 34px rgba(16, 32, 22, 0.06);
    }

    .google-review-card strong {
      display: block;
      color: var(--primary);
      font-size: 1rem;
      margin-bottom: 6px;
    }

    .google-review-card span {
      display: block;
      color: var(--muted);
      font-size: 0.9rem;
      line-height: 1.45;
    }

    .google-visual {
      position: relative;
      padding: 14px;
      border: 1px solid rgba(5, 138, 54, 0.18);
      border-radius: var(--radius);
      background: #fff;
      box-shadow: var(--shadow);
    }

    .google-visual img {
      width: 100%;
      aspect-ratio: 16 / 10;
      object-fit: cover;
      border-radius: var(--radius);
    }

    .google-floating {
      position: absolute;
      left: 28px;
      bottom: 28px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 14px;
      border-radius: var(--radius);
      background: rgba(255, 255, 255, 0.92);
      box-shadow: 0 16px 40px rgba(16, 32, 22, 0.16);
      backdrop-filter: blur(10px);
      font-weight: 900;
    }

    .google-floating small {
      display: block;
      color: var(--muted);
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .stars-inline {
      color: var(--secondary);
      letter-spacing: 1px;
      white-space: nowrap;
    }

    .feature-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
    }

    .feature-card,
    .step,
    .benefit,
    .contact-tile {
      border: 1px solid var(--line);
      border-radius: var(--radius);
      background: var(--paper);
      box-shadow: 0 14px 38px rgba(16, 32, 22, 0.06);
    }

    .feature-card {
      padding: 24px;
      min-height: 230px;
      transform: translateY(22px);
      opacity: 0;
      transition: transform 0.7s ease, opacity 0.7s ease, border-color 0.22s ease;
    }

    .feature-card.revealed {
      transform: translateY(0);
      opacity: 1;
    }

    .feature-card:hover {
      border-color: rgba(5, 138, 54, 0.34);
      transform: translateY(-4px);
    }

    .icon-box {
      width: 46px;
      height: 46px;
      display: grid;
      place-items: center;
      margin-bottom: 18px;
      border-radius: var(--radius);
      background: var(--primary-soft);
      color: var(--primary);
    }

    .icon-box.gold {
      background: #fff4c5;
      color: var(--secondary-dark);
    }

    .icon-box svg {
      width: 24px;
      height: 24px;
    }

    .feature-card h3,
    .step h3,
    .benefit h3 {
      font-size: 1.1rem;
      line-height: 1.25;
      margin-bottom: 10px;
    }

    .feature-card p,
    .step p,
    .benefit p {
      color: var(--muted);
      font-size: 0.96rem;
    }

    .demo-band {
      display: grid;
      grid-template-columns: 0.86fr 1.14fr;
      gap: 34px;
      align-items: center;
    }

    .qr-panel {
      position: relative;
      padding: 28px;
      border: 1px solid var(--line);
      border-radius: var(--radius);
      background: linear-gradient(145deg, #ffffff 0%, #effaf3 100%);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .qr-panel::before {
      content: "";
      position: absolute;
      inset: 18px;
      border: 1px dashed rgba(5, 138, 54, 0.25);
      border-radius: var(--radius);
      pointer-events: none;
    }

    .qr-shell {
      position: relative;
      z-index: 1;
      display: grid;
      place-items: center;
      padding: 22px;
      border-radius: var(--radius);
      background: #fff;
      box-shadow: inset 0 0 0 10px #fff7d8;
    }

    .qr-shell img {
      width: min(100%, 360px);
      aspect-ratio: 1;
      object-fit: contain;
      animation: qrFloat 4s ease-in-out infinite;
    }

    @keyframes qrFloat {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-9px); }
    }

    .scan-note {
      position: relative;
      z-index: 1;
      margin-top: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      color: var(--muted);
      font-size: 0.92rem;
    }

    .scan-note strong {
      color: var(--ink);
    }

    .steps {
      display: grid;
      gap: 14px;
      counter-reset: steps;
    }

    .step {
      position: relative;
      padding: 24px 24px 24px 82px;
      overflow: hidden;
    }

    .step::before {
      counter-increment: steps;
      content: counter(steps);
      position: absolute;
      left: 24px;
      top: 24px;
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      border-radius: var(--radius);
      background: var(--primary);
      color: #fff;
      font-weight: 900;
    }

    .step:nth-child(even)::before {
      background: var(--secondary);
      color: #171200;
    }

    .cta-section {
      padding: 74px 0;
      background:
        linear-gradient(135deg, rgba(5, 138, 54, 0.96), rgba(4, 76, 32, 0.98)),
        #058a36;
      color: #fff;
    }

    .cta-section.gold {
      background:
        linear-gradient(135deg, rgba(240, 180, 0, 0.98), rgba(255, 214, 76, 0.98)),
        #f0b400;
      color: #211900;
    }

    .cta-inner {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 24px;
      align-items: center;
    }

    .cta-inner h2 {
      max-width: 760px;
      font-size: clamp(2rem, 4vw, 3.3rem);
      line-height: 1.05;
      letter-spacing: 0;
    }

    .cta-inner p {
      max-width: 640px;
      margin-top: 14px;
      color: rgba(255, 255, 255, 0.82);
      font-size: 1.05rem;
    }

    .gold .cta-inner p {
      color: rgba(33, 25, 0, 0.74);
    }

    .benefit-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .benefit {
      padding: 26px;
      background: #fbfff9;
    }

    .benefit strong {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      margin-bottom: 12px;
      color: var(--primary);
      font-size: 0.86rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .pricing-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .pricing-card,
    .addon-tile {
      border: 1px solid var(--line);
      border-radius: var(--radius);
      background: var(--paper);
      box-shadow: 0 14px 38px rgba(16, 32, 22, 0.06);
    }

    .pricing-card {
      display: flex;
      flex-direction: column;
      min-height: 100%;
      padding: 28px;
    }

    .pricing-card h3,
    .addon-tile h3 {
      font-size: 1.16rem;
      line-height: 1.25;
    }

    .pricing-price {
      margin: 16px 0 8px;
      color: var(--primary);
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 900;
      line-height: 1;
    }

    .feature-list {
      display: grid;
      gap: 9px;
      margin: 14px 0 18px;
      padding: 0;
      list-style: none;
      color: var(--muted);
    }

    .feature-list li {
      position: relative;
      padding-left: 22px;
      line-height: 1.45;
    }

    .feature-list li::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0.62em;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--primary);
    }

    .addon-tile p {
      color: var(--muted);
    }

    .pricing-card .btn {
      width: 100%;
      margin-top: auto;
    }

    .pricing-meta {
      display: inline-flex;
      width: fit-content;
      margin: 14px 0 24px;
      border-radius: 999px;
      padding: 7px 11px;
      background: var(--primary-soft);
      color: var(--primary);
      font-size: 0.82rem;
      font-weight: 900;
    }

    .addons-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
      gap: 16px;
      margin-top: 18px;
    }

    .addon-tile {
      padding: 20px;
      background: #fbfff9;
    }

    .addon-price {
      margin: 10px 0 8px;
      color: var(--secondary-dark);
      font-size: 1.55rem;
      font-weight: 900;
    }

    .phone-strip {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      margin-top: 38px;
      padding: 22px;
      border: 1px solid rgba(5, 138, 54, 0.18);
      border-radius: var(--radius);
      background: linear-gradient(90deg, var(--primary-soft), #fff9df);
    }

    .phone-strip strong {
      display: block;
      font-size: 1.18rem;
      line-height: 1.25;
    }

    .phone-strip span {
      display: block;
      color: var(--muted);
      margin-top: 4px;
    }

    .contact-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .contact-tile {
      padding: 24px;
      transition: transform 0.22s ease, border-color 0.22s ease;
    }

    .contact-tile:hover {
      transform: translateY(-4px);
      border-color: rgba(5, 138, 54, 0.35);
    }

    .contact-tile span {
      display: block;
      color: var(--muted);
      font-size: 0.9rem;
      margin-bottom: 6px;
    }

    .contact-tile strong {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.02rem;
      overflow-wrap: anywhere;
    }

    .contact-tile svg {
      width: 20px;
      height: 20px;
      color: var(--primary);
      flex: 0 0 auto;
    }

    .site-footer {
      padding: 28px 0;
      border-top: 1px solid var(--line);
      background: #fff;
      color: var(--muted);
      font-size: 0.92rem;
    }

    .footer-inner {
      display: flex;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
    }

    .back-top {
      position: fixed;
      right: 18px;
      bottom: 18px;
      z-index: 20;
      width: 46px;
      height: 46px;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 50%;
      background: var(--primary);
      color: #fff;
      box-shadow: 0 14px 34px rgba(5, 138, 54, 0.3);
      cursor: pointer;
      opacity: 0;
      pointer-events: none;
      transform: translateY(10px);
      transition: opacity 0.22s ease, transform 0.22s ease;
    }

    .back-top.show {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }

    .back-top svg {
      width: 20px;
      height: 20px;
    }

    @media (max-width: 980px) {
      .nav-links {
        display: none;
      }

      .feature-grid,
      .benefit-grid,
      .google-review-cards,
      .addons-row,
      .contact-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .demo-band,
      .google-layout,
      .pricing-grid,
      .cta-inner {
        grid-template-columns: 1fr;
      }

      .hero {
        background:
          linear-gradient(110deg, rgba(3, 47, 22, 0.98) 0%, rgba(5, 138, 54, 0.9) 100%),
          url("demo-qr.png") center 16% / 300px no-repeat,
          #064b24;
      }

      .hero-inner {
        padding-top: 230px;
      }
    }

    @media (max-width: 680px) {
      .wrap {
        width: min(100% - 28px, 1140px);
      }

      .site-header {
        position: absolute;
      }

      .nav {
        min-height: 68px;
      }

      .nav-actions .btn-light {
        display: none;
      }

      .brand-mark {
        width: 38px;
        height: 38px;
      }

      .brand-google-icon {
        width: 26px;
        height: 26px;
      }

      .brand-star {
        width: 18px;
        height: 18px;
        font-size: 0.68rem;
      }

      .hero {
        min-height: auto;
      }

      .hero-inner {
        padding: 210px 0 78px;
      }

      .hero-stats,
      .feature-grid,
      .benefit-grid,
      .google-review-cards,
      .addons-row,
      .contact-grid {
        grid-template-columns: 1fr;
      }

      .quick-strip {
        margin-top: -28px;
        padding-bottom: 30px;
      }

      .hero-actions,
      .phone-strip {
        align-items: stretch;
        flex-direction: column;
      }

      .btn {
        width: 100%;
      }

      .section {
        padding: 68px 0;
      }

      .scan-note {
        align-items: flex-start;
        flex-direction: column;
      }

      .google-floating {
        position: static;
        margin-top: 12px;
        justify-content: center;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      *,
      *::before,
      *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body>
  <header class="site-header" id="siteHeader">
    <div class="wrap nav">
      <a class="brand" href="#top" aria-label="<?= htmlspecialchars($siteName) ?> home">
        <span class="brand-mark" aria-hidden="true">
          <span class="brand-google-icon">G</span>
          <span class="brand-star">★</span>
        </span>
        <span><?= htmlspecialchars($siteName) ?></span>
      </a>
      <nav class="nav-links" aria-label="Main navigation">
        <a href="#google-review">Google Review</a>
        <a href="#features">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="#demo">Demo QR</a>
        <a href="#process">How It Works</a>
        <a href="#contact">Contact</a>
      </nav>
      <div class="nav-actions">
        <a class="btn btn-light" href="<?= APP_URL ?>/customer/login.php">
          Login
        </a>
        <a class="btn btn-primary" href="<?= APP_URL ?>/customer/register.php">
          Register
        </a>
      </div>
    </div>
  </header>

  <main id="top">
    <section class="hero" aria-label="AI Google Reviews landing page">
      <div class="wrap hero-inner">
        <div class="eyebrow"><span></span> AI review generate system with QR code sharing</div>
        <div class="hero-google-badge" aria-label="Google Review">
          <span class="google-g" aria-hidden="true">G</span>
          <span>
            <span class="google-word" aria-hidden="true">
              <span class="blue">G</span><span class="red">o</span><span class="yellow">o</span><span class="blue">g</span><span class="green">l</span><span class="red">e</span>
            </span>
            Review
            <span class="stars-inline" aria-label="Five star rating">★★★★★</span>
          </span>
        </div>
        <h1><?= htmlspecialchars($siteName) ?></h1>
        <p class="hero-copy">
          A smart AI review generation system for businesses. Create a client page, let customers scan a QR code, generate natural AI-written review options, copy the best one, and open the Google Review page in seconds.
        </p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="<?= APP_URL ?>/customer/register.php">
            Register & Start
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"></path>
              <path d="m12 5 7 7-7 7"></path>
            </svg>
          </a>
          <a class="btn btn-light" href="<?= APP_URL ?>/customer/login.php">Customer Login</a>
          <a class="btn btn-light" href="#demo">Scan Demo QR</a>
        </div>
      </div>
    </section>

    <section class="quick-strip" aria-label="Product highlights">
      <div class="wrap">
        <div class="hero-stats">
          <div class="hero-stat">
            <strong>5</strong>
          <span>AI review options generated for each customer</span>
          </div>
          <div class="hero-stat">
            <strong>1 tap</strong>
            <span>copy review and open Google Review page</span>
          </div>
          <div class="hero-stat">
            <strong>QR</strong>
            <span>ready for shops, offices, bills, and flyers</span>
          </div>
        </div>
      </div>
    </section>

    <section class="section google-review-section" id="google-review">
      <div class="wrap google-layout">
        <div class="google-copy">
          <div class="google-badge" aria-label="Google Review">
            <span class="google-g" aria-hidden="true">G</span>
            <span>
              <span class="google-word" aria-hidden="true">
                <span class="blue">G</span><span class="red">o</span><span class="yellow">o</span><span class="blue">g</span><span class="green">l</span><span class="red">e</span>
              </span>
              Review
            </span>
          </div>
          <div class="section-kicker">Google Review Highlight</div>
          <h2>AI helps customers write better Google Reviews faster.</h2>
          <p>
            AI Google Reviews is designed around the Google Review journey. Customers scan your QR code, receive AI-written review suggestions, copy the review they like, and continue directly to your Google Review link.
          </p>
          <div class="google-review-cards">
            <div class="google-review-card">
              <strong>Scan QR</strong>
              <span>Send customers to your review page from counters, flyers, bills, and packaging.</span>
            </div>
            <div class="google-review-card">
              <strong>AI Review Text</strong>
              <span>Generate natural, service-based Google Review suggestions in seconds.</span>
            </div>
            <div class="google-review-card">
              <strong>Post On Google</strong>
              <span>Copy the selected review and open the Google Review page with one tap.</span>
            </div>
          </div>
        </div>
        <div class="google-visual">
          <img src="ai-google-review-hero.png" alt="AI Google Review generation visual with QR code and star ratings">
          <div class="google-floating">
            <span class="google-g" aria-hidden="true">G</span>
            <span>
              <small>Google Review Ready</small>
              <span class="stars-inline" aria-label="Five star rating">★★★★★</span>
            </span>
          </div>
        </div>
      </div>
    </section>

    <section class="section alt" id="features">
      <div class="wrap">
        <div class="section-head">
          <div class="section-kicker">AI-Powered Review System</div>
          <h2>Make customer reviews easier to write, copy, and post with AI.</h2>
          <p>
            AI Google Reviews removes friction from the Google Review process. Customers do not need to think for long or type from scratch. They choose their experience, receive AI-generated review suggestions, and continue to Google Review.
          </p>
        </div>
        <div class="feature-grid">
          <article class="feature-card reveal">
            <div class="icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <h3>Client Review Pages</h3>
            <p>Create separate pages for every business client with logo, tagline, Google Review link, service options, and active status.</p>
          </article>
          <article class="feature-card reveal">
            <div class="icon-box gold">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"></path>
              </svg>
            </div>
            <h3>AI Star Based Review Text</h3>
            <p>Customers select a star rating, and AI generates realistic review suggestions matching that experience.</p>
          </article>
          <article class="feature-card reveal">
            <div class="icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
              </svg>
            </div>
            <h3>Easy Copy And Paste</h3>
            <p>One tap copies the selected review and opens the client's Google Review link, making posting fast and simple.</p>
          </article>
          <article class="feature-card reveal">
            <div class="icon-box gold">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 3h7v7H3z"></path><path d="M14 3h7v7h-7z"></path><path d="M3 14h7v7H3z"></path><path d="M14 14h3v3h-3z"></path><path d="M18 18h3v3h-3z"></path><path d="M18 14h3"></path><path d="M14 21h3"></path>
              </svg>
            </div>
            <h3>QR Code Ready</h3>
            <p>Clients can use QR codes on counters, flyers, packaging, invoices, and social posts to collect reviews anywhere.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="cta-section" aria-label="First call to action">
      <div class="wrap cta-inner">
        <div>
          <h2>Turn happy customers into AI-assisted public reviews while the experience is fresh.</h2>
          <p>Use AI Google Reviews after service completion, at billing, after delivery, or during follow-up messages.</p>
        </div>
        <a class="btn btn-primary" href="tel:<?= htmlspecialchars($phoneDial) ?>">Call <?= htmlspecialchars($phoneDisplay) ?></a>
      </div>
    </section>

    <section class="section alt" id="pricing">
      <div class="wrap">
        <div class="section-head">
          <div class="section-kicker">Pricing Plans</div>
          <h2>Buy a plan to activate your AI Google Review dashboard.</h2>
        </div>
        <div class="pricing-grid">
          <?php foreach ($plans as $plan): ?>
            <article class="pricing-card">
              <h3><?= htmlspecialchars($plan['name']) ?></h3>
              <div class="pricing-price">₹<?= number_format((float) $plan['price'], 0) ?></div>
              <ul class="feature-list">
                <?php foreach (featureListFromText($plan['description'], $plan['duration_days'] . ' days access') as $feature): ?>
                  <li><?= htmlspecialchars($feature) ?></li>
                <?php endforeach; ?>
              </ul>
              <span class="pricing-meta"><?= (int) $plan['duration_days'] ?> days access</span>
              <a class="btn btn-green" href="<?= APP_URL ?>/customer/register.php?plan=<?= (int) $plan['id'] ?>">Buy Plan</a>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($addons): ?>
          <div class="section-head" style="margin-top:46px;margin-bottom:20px">
            <div class="section-kicker">Addons</div>
            <h2>Optional extras for your review campaign.</h2>
            <p>Addons can be selected during registration checkout or bought later from the billing dashboard.</p>
          </div>
          <div class="addons-row">
            <?php foreach ($addons as $addon): ?>
              <article class="addon-tile">
                <h3><?= htmlspecialchars($addon['name']) ?></h3>
                <div class="addon-price">₹<?= number_format((float) $addon['price'], 0) ?></div>
                <p><?= htmlspecialchars($addon['description'] ?: 'One-time addon') ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="section" id="demo">
      <div class="wrap demo-band">
        <div class="qr-panel">
          <div class="qr-shell">
            <img src="demo-qr.png" alt="Demo QR code for AI Google Reviews">
          </div>
          <div class="scan-note">
            <div>
              <strong>Demo QR Code</strong>
              <span>Scan this to understand how the customer journey works.</span>
            </div>
            <a class="btn btn-green" href="demo-qr.png" target="_blank" rel="noopener">Open QR</a>
          </div>
        </div>
        <div>
          <div class="section-head">
            <div class="section-kicker">AI Demo Experience</div>
            <h2>Customers scan, choose, generate with AI, copy, and review.</h2>
            <p>
              The QR code sends the customer to a clean review page. They can select the service, choose a star rating, receive multiple AI-written review suggestions, copy the one they like, and continue to Google Reviews.
            </p>
          </div>
          <div class="benefit-grid">
            <article class="benefit">
              <strong>Fast</strong>
              <h3>No long typing</h3>
              <p>Customers get useful review text quickly instead of starting from a blank box.</p>
            </article>
            <article class="benefit">
              <strong>Simple</strong>
              <h3>Mobile friendly</h3>
              <p>The review page is designed for phone screens, because most people scan QR codes on mobile.</p>
            </article>
            <article class="benefit">
              <strong>Useful</strong>
              <h3>Service specific</h3>
              <p>Review suggestions can include the selected service, making feedback more detailed and believable.</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="section alt" id="process">
      <div class="wrap">
        <div class="section-head">
          <div class="section-kicker">How It Works</div>
          <h2>A complete AI review flow for business owners, clients, and customers.</h2>
          <p>
            The admin manages clients. Clients share their QR code. Customers use the page to create and post a review with less effort.
          </p>
        </div>
        <div class="steps">
          <article class="step">
            <h3>Create a client profile</h3>
            <p>Add company name, logo, tagline, Google Review link, custom instructions, service options, and link expiry if needed.</p>
          </article>
          <article class="step">
            <h3>Generate and share the review link</h3>
            <p>Every client gets a unique review page link. That link can be copied, opened, or converted into a QR code for easy sharing.</p>
          </article>
          <article class="step">
            <h3>Customer opens the page</h3>
            <p>The customer scans the QR code, selects their service, and taps the star rating that matches their experience.</p>
          </article>
          <article class="step">
            <h3>AI review options are generated</h3>
            <p>The system creates multiple realistic AI review suggestions using the client context and selected rating.</p>
          </article>
          <article class="step">
            <h3>Customer copies and posts</h3>
            <p>The customer taps a review to copy it, then the Google Review page opens so they can paste and submit.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="cta-section gold" aria-label="Second call to action">
      <div class="wrap cta-inner">
        <div>
          <h2>Ready to collect more reviews with less customer effort?</h2>
          <p>Contact AI Google Reviews today and set up a clean AI review generation flow for your clients.</p>
        </div>
        <a class="btn btn-green" href="https://wa.me/<?= htmlspecialchars($whatsappNumber) ?>" target="_blank" rel="noopener">
          <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
            <path d="M16.02 3.2A12.74 12.74 0 0 0 5.23 22.7L3.6 28.8l6.24-1.6a12.74 12.74 0 1 0 6.18-24zm0 2.35a10.39 10.39 0 0 1 8.82 15.9 10.39 10.39 0 0 1-13.98 3.45l-.44-.26-3.72.96.98-3.63-.29-.47A10.39 10.39 0 0 1 16.02 5.55zm-4.34 5.47c-.2 0-.52.07-.8.38-.27.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.08 3.32 5.16 4.52 2.56 1.01 3.08.81 3.64.76.56-.05 1.8-.73 2.05-1.44.25-.7.25-1.31.18-1.44-.08-.13-.28-.2-.58-.35-.3-.15-1.8-.89-2.08-.99-.28-.1-.48-.15-.68.15-.2.3-.78.99-.95 1.19-.18.2-.35.22-.65.07-.3-.15-1.27-.47-2.42-1.49-.9-.8-1.5-1.78-1.67-2.08-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.08-.15-.68-1.64-.93-2.24-.24-.58-.49-.5-.68-.51h-.58z"></path>
          </svg>
          Message On WhatsApp
        </a>
      </div>
    </section>

    <section class="section alt" id="contact">
      <div class="wrap">
        <div class="section-head">
          <div class="section-kicker">Contact AI Google Reviews</div>
          <h2>Talk to us about your AI review system setup.</h2>
          <p>Use phone, WhatsApp, or email. We can help you explain the AI review flow to clients and start using QR codes for review collection.</p>
        </div>
        <div class="contact-grid">
          <a class="contact-tile" href="tel:<?= htmlspecialchars($phoneDial) ?>">
            <span>Phone</span>
            <strong>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.6 2.61a2 2 0 0 1-.45 2.11L8 9.7a16 16 0 0 0 6.3 6.3l1.26-1.26a2 2 0 0 1 2.11-.45c.84.28 1.71.48 2.61.6A2 2 0 0 1 22 16.92z"></path>
              </svg>
              <?= htmlspecialchars($phoneDisplay) ?>
            </strong>
          </a>
          <a class="contact-tile" href="https://wa.me/<?= htmlspecialchars($whatsappNumber) ?>" target="_blank" rel="noopener">
            <span>WhatsApp</span>
            <strong>
              <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                <path d="M16.02 3.2A12.74 12.74 0 0 0 5.23 22.7L3.6 28.8l6.24-1.6a12.74 12.74 0 1 0 6.18-24zm0 2.35a10.39 10.39 0 0 1 8.82 15.9 10.39 10.39 0 0 1-13.98 3.45l-.44-.26-3.72.96.98-3.63-.29-.47A10.39 10.39 0 0 1 16.02 5.55zm-4.34 5.47c-.2 0-.52.07-.8.38-.27.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.08 3.32 5.16 4.52 2.56 1.01 3.08.81 3.64.76.56-.05 1.8-.73 2.05-1.44.25-.7.25-1.31.18-1.44-.08-.13-.28-.2-.58-.35-.3-.15-1.8-.89-2.08-.99-.28-.1-.48-.15-.68.15-.2.3-.78.99-.95 1.19-.18.2-.35.22-.65.07-.3-.15-1.27-.47-2.42-1.49-.9-.8-1.5-1.78-1.67-2.08-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.08-.15-.68-1.64-.93-2.24-.24-.58-.49-.5-.68-.51h-.58z"></path>
              </svg>
              <?= htmlspecialchars($phoneDisplay) ?>
            </strong>
          </a>
          <a class="contact-tile" href="mailto:<?= htmlspecialchars($email) ?>">
            <span>Email Us</span>
            <strong>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><path d="m22 6-10 7L2 6"></path>
              </svg>
              <?= htmlspecialchars($email) ?>
            </strong>
          </a>
        </div>
        <div class="phone-strip">
          <div>
            <strong>AI Google Reviews by Official Digital Marketing</strong>
            <span>AI review generation, QR code sharing, and easy copy-paste review posting for client businesses.</span>
          </div>
          <a class="btn btn-green" href="mailto:<?= htmlspecialchars($email) ?>">Email Us</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="wrap footer-inner">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</span>
      <span>
        <a href="<?= APP_URL ?>/privacy-policy.php">Privacy Policy</a> |
        <a href="<?= APP_URL ?>/terms-and-conditions.php">Terms & Conditions</a> |
        Phone: <?= htmlspecialchars($phoneDisplay) ?> | Email: <?= htmlspecialchars($email) ?>
      </span>
    </div>
  </footer>

  <button class="back-top" id="backTop" type="button" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="m18 15-6-6-6 6"></path>
    </svg>
  </button>

  <script>
    const header = document.getElementById('siteHeader');
    const backTop = document.getElementById('backTop');
    const revealItems = document.querySelectorAll('.reveal');

    function updateChrome() {
      const y = window.scrollY || document.documentElement.scrollTop;
      header.classList.toggle('scrolled', y > 20);
      backTop.classList.toggle('show', y > 520);
    }

    updateChrome();
    window.addEventListener('scroll', updateChrome, { passive: true });

    backTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('revealed');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.18 });

      revealItems.forEach((item, index) => {
        item.style.transitionDelay = `${index * 90}ms`;
        observer.observe(item);
      });
    } else {
      revealItems.forEach((item) => item.classList.add('revealed'));
    }
  </script>
</body>
</html>
