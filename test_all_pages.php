#!/usr/bin/env php
<?php

/**
 * Comprehensive Page Test Script
 * Tests all application routes for 500 errors
 */

$baseUrl = 'https://chat.stuc.dev';
$cookieFile = sys_get_temp_dir() . '/test_cookies.txt';

// Initialize cURL session with authentication
function initCurl($url, $cookieFile) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Testing Script)',
    ]);
    return $ch;
}

// Login first
echo "=== Logging in ===\n";
$loginUrl = $baseUrl . '/login';
$ch = initCurl($loginUrl, $cookieFile);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Extract CSRF token
preg_match('/<input type="hidden" name="_token" value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;

if (!$csrfToken) {
    echo "❌ Failed to get CSRF token\n";
    exit(1);
}

// Perform login
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'stuart@stuc.dev',
    'password' => 'Pass2word!'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 302 || $httpCode == 200) {
    echo "✅ Login successful\n\n";
} else {
    echo "❌ Login failed (HTTP $httpCode)\n";
    exit(1);
}

// Define all routes to test
$routes = [
    // Core routes
    'GET /' => '/',
    'GET /dashboard' => '/dashboard',
    'GET /chats' => '/chats',

    // Import routes
    'GET /import' => '/import',
    'GET /import/dashboard' => '/import/dashboard',

    // Search routes
    'GET /search' => '/search',

    // Tag routes
    'GET /tags' => '/tags',

    // Gallery routes
    'GET /gallery' => '/gallery',
    'GET /gallery?type=image' => '/gallery?type=image',
    'GET /gallery?type=video' => '/gallery?type=video',
    'GET /gallery?type=audio' => '/gallery?type=audio',

    // Transcription routes (NEW - must test these)
    'GET /transcription/dashboard' => '/transcription/dashboard',
    'GET /transcription/participants' => '/transcription/participants',
];

// Test each route
echo "=== Testing Routes ===\n\n";
$failures = [];
$successes = 0;

foreach ($routes as $name => $path) {
    $ch = initCurl($baseUrl . $path, $cookieFile);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 500) {
        echo "❌ $name - HTTP $httpCode (ERROR)\n";
        $failures[] = $name;

        // Try to extract error from response
        if (preg_match('/<title>([^<]+)<\/title>/', $response, $matches)) {
            echo "   Error: " . trim($matches[1]) . "\n";
        }
    } elseif ($httpCode == 200) {
        echo "✅ $name - HTTP $httpCode\n";
        $successes++;
    } elseif ($httpCode == 302 || $httpCode == 301) {
        echo "➡️  $name - HTTP $httpCode (Redirect)\n";
        $successes++;
    } elseif ($httpCode == 403) {
        echo "🔒 $name - HTTP $httpCode (Forbidden - may need admin)\n";
        $successes++;
    } else {
        echo "⚠️  $name - HTTP $httpCode\n";
    }
}

// Summary
echo "\n=== Summary ===\n";
echo "Total routes tested: " . count($routes) . "\n";
echo "Successful: $successes\n";
echo "Failed (500 errors): " . count($failures) . "\n";

if (!empty($failures)) {
    echo "\n❌ Failed routes:\n";
    foreach ($failures as $route) {
        echo "  - $route\n";
    }
    exit(1);
} else {
    echo "\n✅ All routes working correctly!\n";
    exit(0);
}
