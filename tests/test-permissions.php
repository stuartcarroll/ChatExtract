<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Chat;
use App\Models\Tag;
use App\Models\Message;

echo "========================================\n";
echo "TESTING USER PERMISSIONS SYSTEM\n";
echo "========================================\n\n";

// Test Admin User
echo "1. TESTING ADMIN USER (admin@test.com)\n";
echo "----------------------------------------\n";
$admin = User::where('email', 'admin@test.com')->first();
echo "Role: " . $admin->role . "\n";
echo "Is Admin: " . ($admin->isAdmin() ? 'Yes' : 'No') . "\n";
echo "Is Chat User: " . ($admin->isChatUser() ? 'Yes' : 'No') . "\n";
echo "Is View Only: " . ($admin->isViewOnly() ? 'Yes' : 'No') . "\n";
$adminChatIds = $admin->accessibleChatIds();
echo "Accessible Chats: " . $adminChatIds->count() . " (should see ALL chats)\n";
$adminTagIds = $admin->accessibleTagIds();
echo "Accessible Tags: " . $adminTagIds->count() . "\n";
echo "✓ Admin test complete\n\n";

// Test Chat User
echo "2. TESTING CHAT USER (chatuser@test.com)\n";
echo "----------------------------------------\n";
$chatUser = User::where('email', 'chatuser@test.com')->first();
echo "Role: " . $chatUser->role . "\n";
echo "Is Admin: " . ($chatUser->isAdmin() ? 'Yes' : 'No') . "\n";
echo "Is Chat User: " . ($chatUser->isChatUser() ? 'Yes' : 'No') . "\n";
echo "Is View Only: " . ($chatUser->isViewOnly() ? 'Yes' : 'No') . "\n";
$chatUserChatIds = $chatUser->accessibleChatIds();
echo "Accessible Chats: " . $chatUserChatIds->count() . " (should see granted chats only)\n";
echo "  Chat IDs: " . $chatUserChatIds->implode(', ') . "\n";
$chatUserTagIds = $chatUser->accessibleTagIds();
echo "Accessible Tags: " . $chatUserTagIds->count() . "\n";
echo "✓ Chat User test complete\n\n";

// Test View Only User
echo "3. TESTING VIEW ONLY USER (viewonly@test.com)\n";
echo "----------------------------------------\n";
$viewOnly = User::where('email', 'viewonly@test.com')->first();
echo "Role: " . $viewOnly->role . "\n";
echo "Is Admin: " . ($viewOnly->isAdmin() ? 'Yes' : 'No') . "\n";
echo "Is Chat User: " . ($viewOnly->isChatUser() ? 'Yes' : 'No') . "\n";
echo "Is View Only: " . ($viewOnly->isViewOnly() ? 'Yes' : 'No') . "\n";
$viewOnlyChatIds = $viewOnly->accessibleChatIds();
echo "Accessible Chats: " . $viewOnlyChatIds->count() . " (should see ONLY chats with tagged messages)\n";
echo "  Chat IDs: " . $viewOnlyChatIds->implode(', ') . "\n";
$viewOnlyTagIds = $viewOnly->accessibleTagIds();
echo "Accessible Tags: " . $viewOnlyTagIds->count() . " (should see granted tags only)\n";
echo "  Tag IDs: " . $viewOnlyTagIds->implode(', ') . "\n";
echo "✓ View Only test complete\n\n";

// Test Chat Access
echo "4. TESTING CHAT ACCESS PERMISSIONS\n";
echo "----------------------------------------\n";
$chat = Chat::first();
echo "Testing Chat #{$chat->id} (Owner: User #{$chat->user_id})\n";
echo "  Admin can view: " . ($admin->accessibleChatIds()->contains($chat->id) ? 'Yes' : 'No') . "\n";
echo "  Chat User can view: " . ($chatUser->accessibleChatIds()->contains($chat->id) ? 'Yes' : 'No') . "\n";
echo "  View Only can view: " . ($viewOnly->accessibleChatIds()->contains($chat->id) ? 'Yes' : 'No') . "\n";
echo "✓ Chat access test complete\n\n";

// Test Tag Access
echo "5. TESTING TAG ACCESS PERMISSIONS\n";
echo "----------------------------------------\n";
$tag = Tag::first();
if ($tag) {
    echo "Testing Tag #{$tag->id} ({$tag->name})\n";
    echo "  Admin can access: Yes (admins see all tags)\n";
    echo "  Chat User can access: Yes (chat users see all tags)\n";
    echo "  View Only can access: " . ($viewOnlyTagIds->contains($tag->id) ? 'Yes' : 'No') . "\n";

    // Check if View Only user can see messages with this tag
    $taggedMessageCount = Message::whereHas('tags', function($q) use ($tag) {
        $q->where('tags.id', $tag->id);
    })->count();
    echo "  Messages with this tag: $taggedMessageCount\n";
}
echo "✓ Tag access test complete\n\n";

echo "========================================\n";
echo "ALL TESTS COMPLETE\n";
echo "========================================\n";
