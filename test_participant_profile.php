#!/usr/bin/env php
<?php

$baseUrl = 'https://chat.stuc.dev';
$cookieFile = sys_get_temp_dir() . '/test_cookies_profile.txt';

// Login
$ch = curl_init($baseUrl . '/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
preg_match('/<input type="hidden" name="_token" value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'stuart@stuc.dev',
    'password' => 'Pass2word!'
]));
curl_exec($ch);
curl_close($ch);

echo "Testing participant profile page...\n";

// Test participant profile
$ch = curl_init($baseUrl . '/transcription/participants/3');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode == 200) {
    echo "✅ Participant profile page working!\n";

    // Check for key elements
    if (strpos($response, 'Participant Profile:') !== false) {
        echo "✅ Profile header found\n";
    }
    if (strpos($response, 'Total Messages') !== false) {
        echo "✅ Statistics found\n";
    }
    if (strpos($response, 'Chats Participated In') !== false) {
        echo "✅ Chats section found\n";
    }
} elseif ($httpCode == 500) {
    echo "❌ 500 Error on participant profile page\n";
    if (preg_match('/<title>([^<]+)<\/title>/', $response, $matches)) {
        echo "Error: " . trim($matches[1]) . "\n";
    }
    exit(1);
} else {
    echo "⚠️  Unexpected HTTP code: $httpCode\n";
}
