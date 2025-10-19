#!/bin/bash

# Deployment Verification Script
# Tests critical routes after deployment to catch errors early

set -e

echo "🔍 Starting deployment verification..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

FAILED=0

# Function to test a route
test_route() {
    local url=$1
    local expected_status=$2
    local description=$3

    echo -n "Testing: $description... "

    status=$(curl -s -o /dev/null -w "%{http_code}" "$url" -L --max-redirs 0 2>&1)

    if [ "$status" = "$expected_status" ]; then
        echo -e "${GREEN}✓${NC} (HTTP $status)"
    else
        echo -e "${RED}✗${NC} (Expected HTTP $expected_status, got HTTP $status)"
        FAILED=$((FAILED + 1))
    fi
}

# Function to test authenticated route with credentials
test_authenticated_route() {
    local url=$1
    local description=$2

    echo -n "Testing (auth): $description... "

    # Get CSRF token and session cookie
    response=$(curl -s -c /tmp/cookies.txt "$url")

    # Check if we got a 200 response (page loaded without 500 error)
    status=$(curl -s -o /dev/null -w "%{http_code}" -b /tmp/cookies.txt "$url")

    if [ "$status" = "200" ] || [ "$status" = "302" ]; then
        echo -e "${GREEN}✓${NC} (HTTP $status - No 500 error)"
    else
        echo -e "${RED}✗${NC} (HTTP $status - Possible error)"
        FAILED=$((FAILED + 1))
    fi

    rm -f /tmp/cookies.txt
}

echo "📋 Testing Public Routes"
echo "========================"
test_route "https://chat.stuc.dev/" "302" "Home page"
test_route "https://chat.stuc.dev/login" "200" "Login page"
test_route "https://chat.stuc.dev/register" "200" "Register page"
echo ""

echo "📋 Testing Protected Routes (should redirect to login)"
echo "======================================================"
test_route "https://chat.stuc.dev/chats" "302" "Chats index"
test_route "https://chat.stuc.dev/gallery" "302" "Gallery"
test_route "https://chat.stuc.dev/search" "302" "Search"
test_route "https://chat.stuc.dev/tags" "302" "Tags"
test_route "https://chat.stuc.dev/import" "302" "Import"
echo ""

echo "📋 Testing API Endpoints"
echo "========================"
test_route "https://chat.stuc.dev/api/health" "404" "Health endpoint (expected 404 if not implemented)"
echo ""

# Summary
echo "================================"
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    echo "Deployment verification successful."
    exit 0
else
    echo -e "${RED}✗ $FAILED test(s) failed!${NC}"
    echo "Please check the server logs for details."
    exit 1
fi
