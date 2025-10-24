<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "========================================\n";
echo "TESTING UI ACCESS FOR ALL USER ROLES\n";
echo "========================================\n\n";

// Test routes for each user
$tests = [
    'admin@test.com' => [
        'role' => 'Admin',
        'routes' => [
            '/chats' => 'Chats page',
            '/search' => 'Search page',
            '/tags' => 'Tags page',
            '/gallery' => 'Gallery page',
            '/import/dashboard' => 'Import dashboard (Admin/Chat User only)',
            '/admin/users' => 'Admin: Manage Users',
            '/admin/groups' => 'Admin: Manage Groups',
            '/admin/chat-access' => 'Admin: Chat Access',
            '/admin/tag-access' => 'Admin: Tag Access',
        ]
    ],
    'chatuser@test.com' => [
        'role' => 'Chat User',
        'routes' => [
            '/chats' => 'Chats page',
            '/search' => 'Search page',
            '/tags' => 'Tags page',
            '/gallery' => 'Gallery page',
            '/import/dashboard' => 'Import dashboard (Chat User allowed)',
        ],
        'forbidden' => [
            '/admin/users' => 'Admin: Manage Users (should be forbidden)',
            '/admin/groups' => 'Admin: Manage Groups (should be forbidden)',
        ]
    ],
    'viewonly@test.com' => [
        'role' => 'View Only',
        'routes' => [
            '/chats' => 'Chats page (only chats with accessible tags)',
            '/search' => 'Search page (only accessible content)',
            '/tags' => 'Tags page (only accessible tags)',
            '/gallery' => 'Gallery page (only accessible media)',
        ],
        'forbidden' => [
            '/import/dashboard' => 'Import dashboard (should be forbidden)',
            '/admin/users' => 'Admin: Manage Users (should be forbidden)',
        ]
    ]
];

function testRoute($url, $email, $expectedStatus = 200) {
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) {
        return "ERROR: User not found";
    }

    try {
        // Create a test request
        $request = \Illuminate\Http\Request::create($url, 'GET');
        $request->setUserResolver(function() use ($user) {
            return $user;
        });

        // Set authenticated user
        \Illuminate\Support\Facades\Auth::setUser($user);

        $response = app()->handle($request);
        $status = $response->getStatusCode();

        if ($status == $expectedStatus) {
            return "✓ $status";
        } else {
            return "✗ Expected $expectedStatus, got $status";
        }
    } catch (\Exception $e) {
        return "✗ Error: " . $e->getMessage();
    }
}

foreach ($tests as $email => $test) {
    echo "{$test['role']} User ($email)\n";
    echo str_repeat('-', 50) . "\n";

    if (isset($test['routes'])) {
        echo "Expected Accessible Routes:\n";
        foreach ($test['routes'] as $route => $description) {
            $result = testRoute($route, $email, 200);
            echo "  $result - $description\n";
        }
    }

    if (isset($test['forbidden'])) {
        echo "Expected Forbidden Routes:\n";
        foreach ($test['forbidden'] as $route => $description) {
            $result = testRoute($route, $email, 403);
            echo "  $result - $description\n";
        }
    }

    echo "\n";
}

echo "========================================\n";
echo "UI ACCESS TESTS COMPLETE\n";
echo "========================================\n";
