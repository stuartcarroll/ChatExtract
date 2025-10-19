#!/bin/bash

echo "🧹 Repository Cleanup Script"
echo "============================"
echo ""
echo "This script will prepare the repository for public release by:"
echo "  - Removing temporary test files"
echo "  - Checking for sensitive information"
echo "  - Verifying .env.example is safe"
echo "  - Scanning for credentials"
echo ""

read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Step 1: Removing temporary test files..."
echo "----------------------------------------"

# Find and list files to be removed
TEST_FILES=$(find . -maxdepth 1 -type f \( \
    -name "test_*.php" -o \
    -name "*_test.php" -o \
    -name "comprehensive_test.php" -o \
    -name "final_test.php" -o \
    -name "TEST_RESULTS.txt" -o \
    -name "verify_*.php" \
\))

if [ -z "$TEST_FILES" ]; then
    echo "✅ No test files found"
else
    echo "Found test files:"
    echo "$TEST_FILES"
    echo ""
    read -p "Remove these files? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "$TEST_FILES" | xargs rm -f
        echo "✅ Test files removed"
    else
        echo "⏭️  Skipped"
    fi
fi

echo ""
echo "Step 2: Checking for sensitive information..."
echo "---------------------------------------------"

# Check for server names
SENSITIVE=$(grep -r "usvps\.stuc\.dev\|chat\.stuc\.dev" \
    --include="*.md" \
    --include="*.sh" \
    --include="*.php" \
    --exclude-dir=vendor \
    --exclude-dir=node_modules \
    --exclude-dir=code-review \
    --exclude-dir=test-plan \
    . 2>/dev/null || true)

if [ -z "$SENSITIVE" ]; then
    echo "✅ No sensitive server information found"
else
    echo "⚠️  WARNING: Found sensitive information:"
    echo "$SENSITIVE"
    echo ""
    echo "Please manually review and sanitize these files."
fi

echo ""
echo "Step 3: Checking for credentials..."
echo "------------------------------------"

# Check for potential credentials
CREDENTIALS=$(grep -r "password.*=.*['\"][^'\"]\{10,\}" \
    --include="*.php" \
    --exclude-dir=vendor \
    --exclude-dir=node_modules \
    app/ config/ 2>/dev/null | \
    grep -v "validation\|'password'\|\"password\"" || true)

if [ -z "$CREDENTIALS" ]; then
    echo "✅ No hardcoded credentials found"
else
    echo "⚠️  WARNING: Potential credentials found:"
    echo "$CREDENTIALS"
fi

echo ""
echo "Step 4: Verifying .env.example..."
echo "----------------------------------"

if [ -f .env.example ]; then
    # Check for real API keys (starting with sk-, pk-, etc.)
    if grep -q "sk-\|pk-\|key-[a-zA-Z0-9]\{20,\}" .env.example 2>/dev/null; then
        echo "❌ ERROR: .env.example may contain real API keys!"
        echo "Please review .env.example and replace with placeholders."
    else
        echo "✅ .env.example appears safe"
    fi
else
    echo "⚠️  WARNING: .env.example not found"
fi

echo ""
echo "Step 5: Checking for SSH keys..."
echo "---------------------------------"

SSH_KEYS=$(find . -type f \( \
    -name "id_rsa*" -o \
    -name "*.pem" -o \
    -name "*.key" \
\) -not -path "./storage/*" -not -path "./vendor/*" 2>/dev/null || true)

if [ -z "$SSH_KEYS" ]; then
    echo "✅ No SSH keys found"
else
    echo "❌ ERROR: SSH keys found:"
    echo "$SSH_KEYS"
    echo "These should NOT be in the repository!"
fi

echo ""
echo "Step 6: Checking for database dumps..."
echo "---------------------------------------"

DB_DUMPS=$(find . -type f \( \
    -name "*.sql" -o \
    -name "*.dump" \
\) -not -path "./vendor/*" 2>/dev/null || true)

if [ -z "$DB_DUMPS" ]; then
    echo "✅ No database dumps found"
else
    echo "⚠️  WARNING: Database dumps found:"
    echo "$DB_DUMPS"
    echo ""
    read -p "Remove these files? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "$DB_DUMPS" | xargs rm -f
        echo "✅ Database dumps removed"
    fi
fi

echo ""
echo "Step 7: Final verification..."
echo "------------------------------"

# List all .md files in root
echo "Markdown files in root directory:"
ls -1 *.md 2>/dev/null || echo "  (none)"

echo ""
echo "Shell scripts in root directory:"
ls -1 *.sh 2>/dev/null || echo "  (none)"

echo ""
echo "=========================================="
echo "Cleanup Summary"
echo "=========================================="
echo ""

if [ -z "$SENSITIVE" ] && [ -z "$CREDENTIALS" ] && [ -z "$SSH_KEYS" ]; then
    echo "✅ Repository appears clean!"
    echo ""
    echo "Recommended next steps:"
    echo "  1. Review all .md files manually"
    echo "  2. Run: git status"
    echo "  3. Run: git add ."
    echo "  4. Run: git commit -m 'Clean up repository for public release'"
    echo ""
else
    echo "⚠️  Issues found - please review above warnings"
    echo ""
    echo "DO NOT make repository public until all issues are resolved."
    echo ""
fi

echo "For detailed cleanup instructions, see:"
echo "  code-review/CLEANUP_BEFORE_PUBLIC.md"
echo ""
