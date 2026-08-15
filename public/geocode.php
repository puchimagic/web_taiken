<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

if ($lat === null || $lon === null) {
    http_response_code(400);
    echo json_encode(['error' => 'lat/lonは必須です']);
    exit;
}

// 緯度経度から住所を取得する（逆ジオコーディング）
// OpenStreetMapのNominatim APIを利用: https://nominatim.org/release-docs/develop/api/Reverse/
$url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2'
    . '&lat=' . urlencode((string)$lat)
    . '&lon=' . urlencode((string)$lon)
    . '&accept-language=ja';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: web-taiken-training-app']);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => '住所の取得に失敗しました: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);
$address = $data['display_name'] ?? '';

echo json_encode(['address' => $address]);
