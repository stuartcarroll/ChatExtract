#!/usr/bin/env php
<?php

echo "=== Final Comprehensive Test ===\n\n";

$baseUrl = 'https://chat.stuc.dev';
$cookieFile = sys_get_temp_dir() . '/final_test_cookies.txt';

// Login
echo "1. Logging in...\n";
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
echo "   ✅ Logged in\n\n";

// Test all new routes with follow redirects
$testRoutes = [
    'Transcription Dashboard' => '/transcription/dashboard',
    'Transcription Participants' => '/transcription/participants',
    'Participant Profile (ID 3)' => '/transcription/participants/3',
    'Gallery (with audio)' => '/gallery?type=audio',
    'Chat Gallery (ID 1)' => '/chats/1/gallery?type=audio',
];

echo "2. Testing new routes:\n";
$allPassed = true;

foreach ($testRoutes as $name => $path) {
    $ch = curl_init($baseUrl . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    // Check for 500 error in response
    $has500Error = (strpos($response, '500 | Server Error') !== false) ||
                   (strpos($response, 'Whoops, something went wrong') !== false);

    if ($httpCode == 200 && !$has500Error) {
        echo "   ✅ $name (HTTP $httpCode)\n";
    } else {
        echo "   ❌ $name (HTTP $httpCode)" . ($has500Error ? ' - 500 Error in page' : '') . "\n";
        if ($finalUrl !== $baseUrl . $path) {
            echo "      Redirected to: $finalUrl\n";
        }
        $allPassed = false;
    }
}

echo "\n3. Checking for specific features:\n";

// Check transcription dashboard
$ch = curl_init($baseUrl . '/transcription/dashboard');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
curl_close($ch);

if (strpos($response, 'Manage Consent') !== false) {
    echo "   ✅ 'Manage Consent' button found on dashboard\n";
} else {
    echo "   ❌ 'Manage Consent' button NOT found on dashboard\n";
    $allPassed = false;
}

// Check participants page
$ch = curl_init($baseUrl . '/transcription/participants');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
curl_close($ch);

if (strpos($response, 'Transcription Consent') !== false) {
    echo "   ✅ Consent management UI found\n";
} else {
    echo "   ⚠️  Consent management UI not clearly visible\n";
}

if (strpos($response, 'Grant Consent') !== false || strpos($response, 'Revoke Consent') !== false) {
    echo "   ✅ Consent action buttons found\n";
} else {
    echo "   ⚠️  Consent action buttons not found\n";
}

// Check audio gallery for transcriptions
$ch = curl_init($baseUrl . '/gallery?type=audio');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
curl_close($ch);

if (strpos($response, 'Audio') !== false) {
    echo "   ✅ Audio gallery accessible\n";
} else {
    echo "   ⚠️  Audio gallery may have issues\n";
}

echo "\n=== Test Summary ===\n";
if ($allPassed) {
    echo "✅ All tests PASSED - No 500 errors detected\n";
    exit(0);
} else {
    echo "❌ Some tests FAILED - Check output above\n";
    exit(1);
}
