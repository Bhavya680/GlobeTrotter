<?php
$data = json_encode([
    'title' => 'dhrde',
    'content' => 'dghdrhd s tdrsh ters hte htr rds jrsthjras',
    'trip_id' => '',
    'tags' => ['drhgdr', 'srgrsgg']
]);

$ch = curl_init('http://localhost:8000/api/community.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: PHPSESSID=' . (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : 'test')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: \n$response\n";
