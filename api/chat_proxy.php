<?php
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Extract required fields
$userId = $data['userId'] ?? 'guest';
$sessionId = $data['sessionId'] ?? 'default_session';
$appName = $data['appName'] ?? 'globetrotter_agent';

// 1. Ensure the session exists in ADK before running
$sessionUrl = "http://127.0.0.1:8000/apps/{$appName}/users/{$userId}/sessions/{$sessionId}";
$ch1 = curl_init($sessionUrl);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_POST, true);
curl_setopt($ch1, CURLOPT_POSTFIELDS, "{}");
curl_setopt($ch1, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_exec($ch1); // Ignore result; it might fail if already exists, which is fine
curl_close($ch1);

// 2. Run the agent
$ch2 = curl_init('http://127.0.0.1:8000/run');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

if (curl_errno($ch2)) {
    http_response_code(500);
    echo json_encode(['error' => curl_error($ch2)]);
} else {
    http_response_code($httpCode);
    echo $response;
}
curl_close($ch2);
