# Repository Cleanup Checklist
## Before Making Repository Public

**Date:** October 19, 2025
**Status:** 🔴 **NOT READY FOR PUBLIC RELEASE**

---

## 1. Remove Temporary Files

### Test Scripts to Delete:
```bash
# Remove all test files from root directory
rm -f test_all_pages.php
rm -f test_participant_profile.php
rm -f final_test.php
rm -f TEST_RESULTS.txt

# Check for test files on server (if deployed)
ssh your-server "cd your-app-directory && rm -f *_test.php test_*.php comprehensive_test.php"
```

### Verification:
```bash
# Ensure no test files remain
find . -maxdepth 1 -type f \( -name "*test*.php" -o -name "*TEST*" \)
# Should return empty
```

---

## 2. Sanitize Sensitive Information

### Files Containing Server Details:

#### ❌ **SERVER_SETUP_COMMANDS.md** - CONTAINS SENSITIVE INFO
**Issues Found:**
- Real server hostnames: `usvps.stuc.dev`
- Real domain: `chat.stuc.dev`
- Real usernames: `ploi`
- File paths: `/home/ploi/chat.stuc.dev`

**Action:** DELETE this file or create sanitized version

**Sanitized Version:**
```markdown
# Server Setup Commands

Replace the following placeholders:
- `YOUR_SERVER` - Your server hostname/IP
- `YOUR_USERNAME` - Your SSH username
- `YOUR_APP_PATH` - Path to application directory
- `YOUR_DOMAIN` - Your application domain

## SSH to Server
ssh YOUR_USERNAME@YOUR_SERVER

## Navigate to Application
cd YOUR_APP_PATH

## Environment Setup
APP_URL=https://YOUR_DOMAIN
```

---

#### ❌ **deploy.sh** - CONTAINS SENSITIVE INFO
**Line 1:** `# Site directory: /home/ploi/chat.stuc.dev`

**Fix:**
```bash
# Change line 1 to:
# Site directory: /path/to/your/app
```

---

#### ❌ **TEST_PLAN.md** - CONTAINS DOMAIN
**Issues:**
- References `https://chat.stuc.dev`

**Fix:** Replace all instances with `https://your-domain.com`

```bash
sed -i '' 's/chat\.stuc\.dev/your-domain.com/g' TEST_PLAN.md
sed -i '' 's/https:\/\/chat\.stuc\.dev/https:\/\/your-domain.com/g' TEST_PLAN.md
```

---

### 3. Search for Hardcoded Credentials

```bash
# Check for passwords
grep -r "password.*=.*['\"]" --include="*.php" --include="*.env*" --exclude-dir=vendor

# Check for API keys
grep -r "OPENAI_API_KEY\|MEILISEARCH.*KEY" --include="*.php" --include="*.md" --exclude-dir=vendor

# Check for database credentials
grep -r "DB_PASSWORD\|DB_USERNAME" --include="*.php" --exclude-dir=vendor

# Check for secret keys
grep -r "APP_KEY\|SECRET" --include="*.env*"
```

**Expected Results:**
- `.env.example` only (with placeholder values)
- No real credentials in code

---

### 4. Verify .env.example

```bash
cat .env.example
```

**Checklist:**
- [ ] No real API keys
- [ ] No real database passwords
- [ ] No real domain names (use `example.com`)
- [ ] All values are clearly examples

**Safe .env.example:**
```env
APP_NAME="WhatsApp Archive"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_archive
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

OPENAI_API_KEY=sk-your-openai-api-key-here

MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-meilisearch-master-key
```

---

## 5. Update .gitignore

Add these patterns:

```gitignore
# Test files
test_*.php
*_test.php
comprehensive_test.php
final_test.php
TEST_RESULTS.txt
verify_*.php

# Deployment scripts with secrets
deploy_production.sh
SERVER_SETUP_COMMANDS.md
DEPLOYMENT_NOTES.md

# Temporary files
*.tmp
*.bak
*.swp
*~

# IDE
.idea/
.vscode/
*.sublime-*

# OS
.DS_Store
Thumbs.db

# Local config
.env.local
```

---

## 6. Create Public-Safe README

Create new `README.md` that:
- ✅ Describes the project
- ✅ Explains features
- ✅ Provides installation instructions
- ❌ No specific domains
- ❌ No server details
- ❌ No deployment commands with real paths

---

## 7. Final Security Scan

```bash
# Run comprehensive scan
./code-review/security-scan.sh

# Check for SSH keys
find . -name "id_rsa*" -o -name "*.pem" -o -name "*.key"

# Check for database dumps
find . -name "*.sql" -o -name "*.dump"

# Check for backup files
find . -name "*.backup" -o -name "backup*"
```

---

## 8. GitHub Secrets Setup

Before pushing, configure GitHub Secrets for CI/CD:

1. Go to: Repository → Settings → Secrets and variables → Actions
2. Add these secrets:
   - `DB_PASSWORD`
   - `OPENAI_API_KEY`
   - `MEILISEARCH_KEY`
   - `SSH_PRIVATE_KEY` (if needed for deployment)

---

## 9. Review Commit History

```bash
# Check if any commits contain secrets
git log --all --full-history --source -- .env

# If .env was ever committed:
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch .env" \
  --prune-empty --tag-name-filter cat -- --all
```

---

## 10. Pre-Release Checklist

- [ ] All test files removed
- [ ] SERVER_SETUP_COMMANDS.md deleted or sanitized
- [ ] deploy.sh sanitized
- [ ] TEST_PLAN.md sanitized
- [ ] No references to `usvps.stuc.dev`
- [ ] No references to `chat.stuc.dev`
- [ ] No references to username `ploi`
- [ ] .env.example verified safe
- [ ] .gitignore updated
- [ ] README.md reviewed
- [ ] No credentials in code
- [ ] No SSH keys in repo
- [ ] No database dumps
- [ ] GitHub secrets configured
- [ ] Commit history clean

---

## Quick Cleanup Script

Run this script to perform automatic cleanup:

```bash
#!/bin/bash

echo "🧹 Cleaning up repository for public release..."

# Remove test files
echo "Removing test files..."
rm -f test_*.php *_test.php comprehensive_test.php final_test.php TEST_RESULTS.txt verify_*.php

# Remove sensitive documentation
echo "Removing sensitive documentation..."
rm -f SERVER_SETUP_COMMANDS.md DEPLOYMENT_NOTES.md

# Sanitize remaining files
echo "Sanitizing files..."
sed -i '' 's/usvps\.stuc\.dev/your-server/g' *.md **/*.md
sed -i '' 's/chat\.stuc\.dev/your-domain.com/g' *.md **/*.md
sed -i '' 's/ploi@/your-username@/g' *.md **/*.md *.sh
sed -i '' 's/\/home\/ploi\//\/path\/to\//g' *.md **/*.md *.sh

# Verify .env.example
echo "Checking .env.example..."
if grep -q "sk-" .env.example; then
    echo "⚠️  WARNING: .env.example may contain real API key!"
fi

# Search for remaining issues
echo "Scanning for remaining sensitive data..."
grep -r "password.*=.*['\"]" --include="*.php" --exclude-dir=vendor | grep -v ".env.example"

echo "✅ Cleanup complete!"
echo "⚠️  Manual review still required for:"
echo "   - .env.example"
echo "   - README.md"
echo "   - deploy.sh"
echo "   - All .md files"
```

---

## Post-Cleanup Verification

1. Clone repository to fresh directory
2. Search for sensitive terms:
   ```bash
   cd /tmp
   git clone <your-repo-url> fresh-clone
   cd fresh-clone

   # Search for server names
   grep -r "usvps\|stuc\.dev" .

   # Search for usernames
   grep -r "ploi" .

   # Should only find references in:
   # - .git/ directory (history)
   # - code-review/ directory (documentation about what to clean)
   ```

3. If found outside those directories: **NOT READY**

---

**Status:** 🔴 **INCOMPLETE** - Run cleanup script before making public
