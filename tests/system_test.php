#!/usr/bin/env php
<?php
/**
 * ChatExtract System Test Script
 *
 * This script simulates a logged-in user and tests all pages in the application.
 * It checks for meaningful content in responses, not just 200 status codes.
 *
 * USAGE:
 *   php tests/system_test.php
 *
 * SETUP:
 *   1. Login to the application in your browser
 *   2. Open browser DevTools > Application > Cookies
 *   3. Copy the values for: chatextract_session, XSRF-TOKEN
 *   4. Update the $cookies array below with these values
 *   5. Run this script
 *
 * UPDATING THIS SCRIPT:
 *   When you add new pages/routes to the application:
 *   1. Add a new test to the $tests array below
 *   2. Specify the URL, method, and expected content
 *   3. For POST requests, include necessary form data
 *   4. Run the test to verify the new page works
 *
 * OUTPUT:
 *   ✓ Green = Test passed
 *   ✗ Red = Test failed (shows error details)
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

$baseUrl = 'https://chat.stuc.dev';

// Update these cookies from your browser after logging in
$cookies = [
    'chatextract_session' => 'YOUR_SESSION_COOKIE_HERE',
    'XSRF-TOKEN' => 'YOUR_XSRF_TOKEN_HERE',
];

// ============================================================================
// TEST DEFINITIONS
// ============================================================================

$tests = [
    // Basic Navigation
    [
        'name' => 'Home Page (redirects to chats)',
        'url' => '/',
        'method' => 'GET',
        'expect_redirect' => true,
        'expected_location' => '/chats',
    ],

    [
        'name' => 'Dashboard (redirects to chats)',
        'url' => '/dashboard',
        'method' => 'GET',
        'expect_redirect' => true,
        'expected_location' => '/chats',
    ],

    // Chat Routes
    [
        'name' => 'Chat List',
        'url' => '/chats',
        'method' => 'GET',
        'expected_content' => ['Your Chats', 'Import Chat'],
    ],

    [
        'name' => 'Chat Detail (first chat)',
        'url' => '/chats/1',
        'method' => 'GET',
        'expected_content' => ['Messages', 'Participants'],
        'allow_404' => true, // May not exist if no chats imported yet
    ],

    [
        'name' => 'Chat Gallery (first chat)',
        'url' => '/chats/1/gallery',
        'method' => 'GET',
        'expected_content_any' => ['Gallery', 'No media', 'Photos', 'Videos'],
        'allow_404' => true,
    ],

    // Global Features
    [
        'name' => 'Global Gallery',
        'url' => '/gallery',
        'method' => 'GET',
        'expected_content_any' => ['Media Gallery', 'All Chats', 'No media found'],
    ],

    [
        'name' => 'Search Page',
        'url' => '/search',
        'method' => 'GET',
        'expected_content' => ['Search Messages', 'Search for messages'],
    ],

    // Import Routes
    [
        'name' => 'Import Dashboard',
        'url' => '/import/dashboard',
        'method' => 'GET',
        'expected_content' => ['Import Dashboard', 'Status'],
    ],

    [
        'name' => 'Import Form',
        'url' => '/import',
        'method' => 'GET',
        'expected_content' => ['Import WhatsApp Chat', 'Select a file'],
    ],

    // Profile
    [
        'name' => 'Profile Edit',
        'url' => '/profile',
        'method' => 'GET',
        'expected_content' => ['Profile Information', 'Email'],
    ],
];

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function color($text, $color) {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'reset' => "\033[0m",
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function makeRequest($url, $method, $cookies, $data = []) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects automatically
    curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in output
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // Set cookies
    $cookieString = '';
    foreach ($cookies as $name => $value) {
        $cookieString .= "$name=" . urlencode($value) . "; ";
    }
    curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieString, '; '));

    // Set method and data
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    // Extract redirect location if present
    $location = null;
    if (preg_match('/^Location:\s*(.+)$/im', $headers, $matches)) {
        $location = trim($matches[1]);
    }

    return [
        'status' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'location' => $location,
    ];
}

function runTest($test, $baseUrl, $cookies) {
    $url = $baseUrl . $test['url'];
    $method = $test['method'] ?? 'GET';
    $data = $test['data'] ?? [];

    $response = makeRequest($url, $method, $cookies, $data);

    $result = [
        'name' => $test['name'],
        'passed' => false,
        'message' => '',
        'details' => '',
    ];

    // Check for 404 if allowed
    if ($response['status'] == 404 && ($test['allow_404'] ?? false)) {
        $result['passed'] = true;
        $result['message'] = '404 Not Found (allowed)';
        return $result;
    }

    // Check for expected redirect
    if ($test['expect_redirect'] ?? false) {
        if ($response['status'] >= 300 && $response['status'] < 400) {
            if (isset($test['expected_location'])) {
                $location = $response['location'];
                if (strpos($location, $test['expected_location']) !== false) {
                    $result['passed'] = true;
                    $result['message'] = "Redirects to {$test['expected_location']}";
                } else {
                    $result['message'] = "Expected redirect to {$test['expected_location']}, got: $location";
                }
            } else {
                $result['passed'] = true;
                $result['message'] = "Redirects successfully";
            }
        } else {
            $result['message'] = "Expected redirect (3xx), got status {$response['status']}";
        }
        return $result;
    }

    // Check status code
    if ($response['status'] != 200) {
        $result['message'] = "HTTP {$response['status']}";
        $result['details'] = substr($response['body'], 0, 500);
        return $result;
    }

    // Check for expected content (all must be present)
    if (isset($test['expected_content'])) {
        $missing = [];
        foreach ($test['expected_content'] as $expected) {
            if (stripos($response['body'], $expected) === false) {
                $missing[] = $expected;
            }
        }

        if (empty($missing)) {
            $result['passed'] = true;
            $result['message'] = 'All expected content found';
        } else {
            $result['message'] = 'Missing content: ' . implode(', ', $missing);
            $result['details'] = 'First 500 chars: ' . substr($response['body'], 0, 500);
        }
        return $result;
    }

    // Check for expected content (at least one must be present)
    if (isset($test['expected_content_any'])) {
        $found = [];
        foreach ($test['expected_content_any'] as $expected) {
            if (stripos($response['body'], $expected) !== false) {
                $found[] = $expected;
            }
        }

        if (!empty($found)) {
            $result['passed'] = true;
            $result['message'] = 'Found: ' . implode(', ', $found);
        } else {
            $result['message'] = 'None of the expected content found: ' . implode(', ', $test['expected_content_any']);
            $result['details'] = 'First 500 chars: ' . substr($response['body'], 0, 500);
        }
        return $result;
    }

    // If no specific expectations, just check 200 status
    $result['passed'] = true;
    $result['message'] = 'HTTP 200 OK';
    return $result;
}

// ============================================================================
// RUN TESTS
// ============================================================================

echo "\n";
echo color("╔════════════════════════════════════════════════════════════════╗\n", 'blue');
echo color("║        ChatExtract System Test - All Application Pages        ║\n", 'blue');
echo color("╚════════════════════════════════════════════════════════════════╝\n", 'blue');
echo "\n";

// Check if cookies are configured
if ($cookies['chatextract_session'] === 'YOUR_SESSION_COOKIE_HERE') {
    echo color("⚠ WARNING: Cookies not configured!\n", 'yellow');
    echo "Please update the \$cookies array in this script with your browser cookies.\n";
    echo "See instructions at the top of this file.\n\n";
    exit(1);
}

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    $result = runTest($test, $baseUrl, $cookies);

    if ($result['passed']) {
        echo color("✓ ", 'green');
        echo $result['name'];
        echo color(" - " . $result['message'], 'green');
        echo "\n";
        $passed++;
    } else {
        echo color("✗ ", 'red');
        echo $result['name'];
        echo color(" - " . $result['message'], 'red');
        echo "\n";
        if (!empty($result['details'])) {
            echo color("  Details: " . $result['details'], 'red');
            echo "\n";
        }
        $failed++;
    }
}

// ============================================================================
// SUMMARY
// ============================================================================

echo "\n";
echo color("════════════════════════════════════════════════════════════════\n", 'blue');
echo "Total Tests: " . ($passed + $failed) . "\n";
echo color("Passed: $passed\n", 'green');
if ($failed > 0) {
    echo color("Failed: $failed\n", 'red');
} else {
    echo "Failed: 0\n";
}
echo color("════════════════════════════════════════════════════════════════\n", 'blue');
echo "\n";

exit($failed > 0 ? 1 : 0);
