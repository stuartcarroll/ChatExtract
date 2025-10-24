#!/bin/bash
# Script to create GitHub security issues
# Usage: ./create-security-issues.sh

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}GitHub Security Issues Creation Script${NC}"
echo "========================================"
echo ""

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo -e "${RED}ERROR: GitHub CLI (gh) is not installed${NC}"
    echo ""
    echo "To install GitHub CLI:"
    echo "  macOS: brew install gh"
    echo "  Linux: See https://github.com/cli/cli/blob/trunk/docs/install_linux.md"
    echo "  Windows: winget install --id GitHub.cli"
    echo ""
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo -e "${YELLOW}Not authenticated with GitHub${NC}"
    echo "Running: gh auth login"
    gh auth login
fi

echo ""
echo -e "${GREEN}Creating 9 security issues...${NC}"
echo ""

# Issue 1: Path Traversal in Chunked Upload
echo "Creating Issue 1: Path Traversal in Chunked Upload..."
gh issue create \
  --title "[SECURITY] Path Traversal Vulnerability in Chunked Upload" \
  --label "security,critical,bug" \
  --body "## Security Issue
User-supplied \`upload_id\` parameter is used directly in file path construction without validation, allowing path traversal attacks.

## Location
\`app/Http/Controllers/ChunkedUploadController.php\` lines 68-70

## Vulnerable Code
\`\`\`php
\$uploadId = \$request->upload_id;  // From user input, not validated
\$uploadDir = storage_path('app/uploads/' . \$uploadId);  // Direct concatenation
\`\`\`

## Risk
- **Severity**: CRITICAL
- **Attack Vector**: Remote, authenticated
- **Impact**: Arbitrary file write, potential code execution
- **Exploitability**: Easy

An attacker could use path traversal sequences (e.g., \`../../\`) to write files outside the intended directory, potentially leading to:
- Code execution if PHP files are written to web-accessible locations
- Data breach through writing to sensitive directories
- System compromise

## Recommended Fix
\`\`\`php
// Validate upload_id is a valid UUID
\$request->validate([
    'upload_id' => 'required|uuid',
]);

// Defense in depth: verify resolved path
\$uploadId = basename(\$request->upload_id);
\$uploadDir = storage_path('app/uploads/' . \$uploadId);
\$realPath = realpath(\$uploadDir);

if (!\$realPath || !str_starts_with(\$realPath, storage_path('app/uploads/'))) {
    abort(403, 'Invalid upload directory');
}
\`\`\`

## References
- OWASP Path Traversal: https://owasp.org/www-community/attacks/Path_Traversal
- CWE-22: https://cwe.mitre.org/data/definitions/22.html

## Priority
🔴 **P0 - Fix Immediately**

See \`docs/SECURITY-ISSUES.md\` for full details."

echo -e "${GREEN}✓ Issue 1 created${NC}"
echo ""

# Issue 2: Insecure Tag Management
echo "Creating Issue 2: Insecure Tag Management..."
gh issue create \
  --title "[SECURITY] Any User Can Modify or Delete Any Tag" \
  --label "security,critical,bug,authorization" \
  --body "## Security Issue
Tag update and delete operations have no authorization checks. Any authenticated user can modify or delete tags created by other users.

## Location
\`app/Http/Controllers/TagController.php\` lines 62-82

## Vulnerable Code
\`\`\`php
public function update(Request \$request, Tag \$tag)
{
    // NO authorization check!
    \$tag->update(['name' => \$request->name]);
    return redirect()->route('tags.index')->with('success', 'Tag updated successfully.');
}

public function destroy(Tag \$tag)
{
    // NO authorization check!
    \$tag->delete();
    return redirect()->route('tags.index')->with('success', 'Tag deleted successfully.');
}
\`\`\`

## Risk
- **Severity**: HIGH
- **Attack Vector**: Remote, authenticated (any user)
- **Impact**: Data integrity, denial of service
- **Exploitability**: Trivial

## Recommended Fix
**Option 1** (Recommended): Add ownership
\`\`\`php
// Migration
Schema::table('tags', function (Blueprint \$table) {
    \$table->foreignId('created_by')->nullable()->constrained('users');
});

// Controller
public function update(Request \$request, Tag \$tag)
{
    if (\$tag->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
        abort(403, 'You can only modify tags you created.');
    }
    \$tag->update(['name' => \$request->name]);
    // ...
}
\`\`\`

## Priority
🔴 **P0 - Fix Immediately**

See \`docs/SECURITY-ISSUES.md\` for full details."

echo -e "${GREEN}✓ Issue 2 created${NC}"
echo ""

# Issue 3: ZIP Bomb
echo "Creating Issue 3: ZIP Bomb and Path Traversal..."
gh issue create \
  --title "[SECURITY] Unvalidated ZIP Extraction Allows ZIP Bombs and Path Traversal" \
  --label "security,critical,bug" \
  --body "## Security Issue
ZIP file extraction has no validation for file count, size, paths, or compression ratio. This enables ZIP bomb attacks and path traversal.

## Location
\`app/Jobs/ProcessChatImportJob.php\` lines 81-90

## Vulnerable Code
\`\`\`php
\$zip = new \\ZipArchive();
\$zipStatus = \$zip->open(\$this->filePath);
if (\$zipStatus === true) {
    \$zip->extractTo(\$extractPath);  // NO VALIDATION!
    \$zip->close();
}
\`\`\`

## Risk
- **Severity**: CRITICAL
- **Attack Vector**: Remote, authenticated
- **Impact**: Denial of service, data breach, system compromise
- **Exploitability**: Easy

Attackers can:
- Upload ZIP bombs (42.zip) to exhaust disk space
- Include files with path traversal (../../etc/passwd)
- Create symlinks to access sensitive files
- Cause denial of service

## Recommended Fix
See \`docs/SECURITY-ISSUES.md\` for complete fix with validation code.

## Priority
🔴 **P0 - Fix Immediately**"

echo -e "${GREEN}✓ Issue 3 created${NC}"
echo ""

# Issue 4: Mass Assignment
echo "Creating Issue 4: Mass Assignment Privilege Escalation..."
gh issue create \
  --title "[SECURITY] Role Field Mass Assignment Enables Privilege Escalation" \
  --label "security,critical,bug,privilege-escalation" \
  --body "## Security Issue
User model has \`role\` in \`\$fillable\` array, allowing users to assign themselves admin privileges through mass assignment.

## Location
\`app/Models/User.php\` lines 22-27

## Vulnerable Code
\`\`\`php
protected \$fillable = [
    'name',
    'email',
    'password',
    'role',  // DANGEROUS!
];
\`\`\`

## Risk
- **Severity**: CRITICAL
- **Attack Vector**: Remote, unauthenticated (during registration) or authenticated
- **Impact**: Complete privilege escalation to admin
- **Exploitability**: Trivial

## Attack Example
\`\`\`bash
POST /register
{
  \"name\": \"Attacker\",
  \"email\": \"attacker@evil.com\",
  \"password\": \"password\",
  \"role\": \"admin\"  ← User becomes admin!
}
\`\`\`

## Recommended Fix
\`\`\`php
// Remove role from fillable
protected \$fillable = [
    'name',
    'email',
    'password',
];

// Add to guarded instead
protected \$guarded = [
    'id',
    'role',  // Prevent privilege escalation
    'email_verified_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
];
\`\`\`

## Priority
🔴 **P0 - Fix Immediately**"

echo -e "${GREEN}✓ Issue 4 created${NC}"
echo ""

# Issue 5: SQL LIKE Wildcards
echo "Creating Issue 5: SQL LIKE Wildcards..."
gh issue create \
  --title "[SECURITY] LIKE Query Wildcards Not Escaped - DoS and Info Disclosure" \
  --label "security,high,bug" \
  --body "## Security Issue
LIKE queries don't escape SQL wildcard characters (\`%\` and \`_\`), allowing performance attacks and search result manipulation.

## Location
- SearchController.php (multiple LIKE queries)
- ImportController.php (filename searches)

## Risk
- **Severity**: MEDIUM-HIGH
- **Attack Vector**: Remote, authenticated
- **Impact**: Denial of service, information disclosure
- **Exploitability**: Easy

## Recommended Fix
\`\`\`php
// Create helper method
private function escapeLikeValue(string \$value): string
{
    return str_replace(['\\\\', '%', '_'], ['\\\\\\\\', '\\%', '\\_'], \$value);
}

// Use in all LIKE queries
\$searchTerm = \$this->escapeLikeValue(\$request->search);
\$query->where('name', 'like', '%' . \$searchTerm . '%');
\`\`\`

## Priority
🟠 **P1 - Fix This Week**"

echo -e "${GREEN}✓ Issue 5 created${NC}"
echo ""

# Issue 6: File Type Validation
echo "Creating Issue 6: File Type Validation..."
gh issue create \
  --title "[SECURITY] No MIME Type Validation in File Uploads" \
  --label "security,high,bug" \
  --body "## Security Issue
Chunked upload doesn't validate MIME type of uploaded files, only checks filename extension which can be spoofed.

## Location
\`app/Http/Controllers/ChunkedUploadController.php\` lines 60-66

## Risk
- **Severity**: HIGH
- **Impact**: Malware upload, potential code execution
- **Exploitability**: Moderate

## Recommended Fix
\`\`\`php
// In finalize() method
\$mimeType = mime_content_type(\$finalFullPath);
\$allowedMimes = ['text/plain', 'application/zip', 'application/x-zip-compressed'];

if (!in_array(\$mimeType, \$allowedMimes)) {
    unlink(\$finalFullPath);
    throw new \\Exception('Invalid file type: ' . \$mimeType);
}
\`\`\`

## Priority
🟠 **P1 - Fix This Week**"

echo -e "${GREEN}✓ Issue 6 created${NC}"
echo ""

# Issue 7: Weak Random
echo "Creating Issue 7: Weak Random Values..."
gh issue create \
  --title "[SECURITY] Predictable Temporary Directory Names in Export" \
  --label "security,medium,enhancement" \
  --body "## Security Issue
Export functions use \`uniqid()\` which is predictable. Should use cryptographically secure random values.

## Location
- ExportController.php:101
- TagController.php:186

## Recommended Fix
\`\`\`php
use Illuminate\\Support\\Str;
\$tempDir = storage_path('app/temp_exports/' . Str::uuid()->toString());
\`\`\`

## Priority
🟡 **P2 - Fix This Month**"

echo -e "${GREEN}✓ Issue 7 created${NC}"
echo ""

# Issue 8: Rate Limiting
echo "Creating Issue 8: Rate Limiting..."
gh issue create \
  --title "[SECURITY] No Rate Limiting on Bulk Export - DoS Risk" \
  --label "security,medium,enhancement" \
  --body "## Security Issue
Export endpoints have no rate limiting, allowing resource exhaustion attacks.

## Location
routes/web.php - export routes

## Recommended Fix
\`\`\`php
RateLimiter::for('exports', function (Request \$request) {
    return Limit::perMinute(5)->by(\$request->user()->id);
});

Route::middleware(['throttle:exports'])->group(function () {
    Route::post('/export', [ExportController::class, 'export']);
    Route::post('/tags/{tag}/export', [TagController::class, 'export']);
});
\`\`\`

## Priority
🟡 **P2 - Fix This Month**"

echo -e "${GREEN}✓ Issue 8 created${NC}"
echo ""

# Issue 9: Media Path Validation
echo "Creating Issue 9: Media Path Validation..."
gh issue create \
  --title "[SECURITY] Insufficient Validation of Media File Paths in Export" \
  --label "security,medium,bug" \
  --body "## Security Issue
Media file paths from database are used directly without validation. If database is compromised, could expose sensitive files.

## Location
- ExportController.php:136
- TagController.php:218

## Recommended Fix
\`\`\`php
// Validate file_path
if (str_contains(\$media->file_path, '..') || str_starts_with(\$media->file_path, '/')) {
    continue; // Skip
}

\$mediaPath = storage_path('app/public/' . \$media->file_path);
\$realPath = realpath(\$mediaPath);

if (!\$realPath || !str_starts_with(\$realPath, storage_path('app/public/'))) {
    continue; // Skip
}
\`\`\`

## Priority
🟡 **P2 - Fix This Month**"

echo -e "${GREEN}✓ Issue 9 created${NC}"
echo ""

echo "========================================"
echo -e "${GREEN}✅ All 9 security issues created!${NC}"
echo ""
echo "View issues at: https://github.com/stuartcarroll/ChatExtract/issues"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Review issues on GitHub"
echo "2. Assign issues to team members"
echo "3. Start with P0 (Critical) issues immediately"
echo "4. Follow remediation roadmap in docs/SECURITY-REVIEW-STATUS.md"
