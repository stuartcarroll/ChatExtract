#!/usr/bin/env php
<?php
/**
 * Get Test Credentials Helper
 *
 * This script helps you get the authentication cookies needed for system_test.php
 *
 * USAGE:
 *   1. Run this script: php tests/get_test_credentials.php
 *   2. It will show you which cookies to copy from your browser
 *   3. Open your browser, login to the application
 *   4. Open DevTools > Application > Cookies > https://chat.stuc.dev
 *   5. Copy the chatextract_session and XSRF-TOKEN values
 *   6. Paste them into the system_test.php file
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║              Get Test Credentials - Instructions              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "To run the system tests, you need authentication cookies from your browser.\n";
echo "\n";
echo "Follow these steps:\n";
echo "\n";
echo "1. Open your browser and navigate to: https://chat.stuc.dev\n";
echo "2. Login with your credentials\n";
echo "3. Press F12 to open Developer Tools\n";
echo "4. Go to the 'Application' tab (Chrome) or 'Storage' tab (Firefox)\n";
echo "5. Click on 'Cookies' in the left sidebar\n";
echo "6. Click on 'https://chat.stuc.dev'\n";
echo "7. Find and copy the values for these cookies:\n";
echo "   - chatextract_session\n";
echo "   - XSRF-TOKEN\n";
echo "\n";
echo "8. Open tests/system_test.php in your editor\n";
echo "9. Find the \$cookies array near the top of the file\n";
echo "10. Replace 'YOUR_SESSION_COOKIE_HERE' with your chatextract_session value\n";
echo "11. Replace 'YOUR_XSRF_TOKEN_HERE' with your XSRF-TOKEN value\n";
echo "\n";
echo "12. Save the file and run: php tests/system_test.php\n";
echo "\n";
echo "Example:\n";
echo "\n";
echo "  \$cookies = [\n";
echo "      'chatextract_session' => 'eyJpdiI6InNvbWV0aGluZ...',\n";
echo "      'XSRF-TOKEN' => 'eyJpdiI6ImFub3RoZXJ0aGluZy...',\n";
echo "  ];\n";
echo "\n";
echo "Note: These cookies expire after a while. If tests start failing with\n";
echo "401/403 errors, you may need to get fresh cookies.\n";
echo "\n";
