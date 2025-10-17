#!/usr/bin/env php
<?php
/**
 * Quick Server-Side Test
 *
 * This script runs directly on the server without needing browser cookies.
 * It uses Laravel's testing capabilities to simulate authenticated requests.
 *
 * USAGE:
 *   php tests/quick_test.php
 *   OR from server:
 *   ssh ploi@usvps.stuc.dev "cd chat.stuc.dev && php tests/quick_test.php"
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Helper functions
function color($text, $color) {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'reset' => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . ($colors['reset'] ?? '');
}

function testRoute($name, $method, $uri, $expectations = [], $data = []) {
    global $testUser;

    try {
        // Simulate authenticated request
        Auth::login($testUser);

        $request = Illuminate\Http\Request::create($uri, $method, $data);
        $request->headers->set('Accept', 'text/html');

        // Get the route
        $route = Route::getRoutes()->match($request);

        // Run the request through the router
        $response = $route->run();

        // Handle both Response and View objects
        if ($response instanceof \Illuminate\Http\Response) {
            $status = $response->status();
            $content = $response->getContent();
        } elseif ($response instanceof \Illuminate\View\View) {
            $status = 200;
            $content = $response->render();
        } else {
            $status = 200;
            $content = (string) $response;
        }

        // Check status
        if (isset($expectations['status'])) {
            if ($status != $expectations['status']) {
                throw new Exception("Expected status {$expectations['status']}, got $status");
            }
        } elseif ($status >= 500) {
            throw new Exception("Server error: HTTP $status");
        }

        // Check redirects
        if (isset($expectations['redirect'])) {
            if (!$response->isRedirect()) {
                throw new Exception("Expected redirect, got status $status");
            }
        }

        // Check content
        if (isset($expectations['contains'])) {
            foreach ((array)$expectations['contains'] as $needle) {
                if (stripos($content, $needle) === false) {
                    throw new Exception("Missing expected content: $needle");
                }
            }
        }

        // Check for common error indicators
        if ($status == 200 && !($expectations['allow_errors'] ?? false)) {
            if (stripos($content, 'Call to a member function') !== false ||
                stripos($content, 'Undefined variable') !== false ||
                stripos($content, 'syntax error') !== false) {
                throw new Exception("PHP error detected in response");
            }
        }

        echo color("✓ ", 'green') . "$name\n";
        return true;

    } catch (Exception $e) {
        echo color("✗ ", 'red') . "$name - " . color($e->getMessage(), 'red') . "\n";
        if (isset($content) && $status >= 500) {
            // Extract error message from Laravel error page
            if (preg_match('/class="exception_title">([^<]+)</', $content, $matches)) {
                echo color("  Error: " . trim($matches[1]), 'red') . "\n";
            }
        }
        return false;
    } finally {
        Auth::logout();
    }
}

// ============================================================================
// RUN TESTS
// ============================================================================

echo "\n";
echo color("╔════════════════════════════════════════════════════════════════╗\n", 'blue');
echo color("║           ChatExtract Quick Test - Server-Side Check          ║\n", 'blue');
echo color("╚════════════════════════════════════════════════════════════════╝\n", 'blue');
echo "\n";

// Get or create test user
$testUser = User::first();
if (!$testUser) {
    echo color("No users found in database. Cannot run tests.\n", 'red');
    exit(1);
}

echo "Testing as user: {$testUser->email}\n\n";

$passed = 0;
$failed = 0;

// Define tests
$tests = [
    ['Chats List', 'GET', '/chats', ['status' => 200, 'contains' => 'My Chats']],
    ['Global Gallery', 'GET', '/gallery', ['status' => 200, 'contains' => ['Gallery', 'Media']]],
    ['Search Page', 'GET', '/search', ['status' => 200, 'contains' => 'Search Messages']],
    ['Search Perform (POST)', 'POST', '/search', ['status' => 200, 'contains' => 'Search Messages'], ['query' => 'test']],
    ['Import Dashboard', 'GET', '/import/dashboard', ['status' => 200, 'contains' => 'Import']],
    ['Import Form', 'GET', '/import', ['status' => 200, 'contains' => 'Import', 'allow_errors' => true]],
    ['Profile', 'GET', '/profile', ['status' => 200, 'contains' => 'Profile', 'allow_errors' => true]],
];

// Run each test
foreach ($tests as $test) {
    if (testRoute($test[0], $test[1], $test[2], $test[3] ?? [], $test[4] ?? [])) {
        $passed++;
    } else {
        $failed++;
    }
}

// Summary
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
