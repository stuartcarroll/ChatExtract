# GitHub Security Issues to Create

Create these security issues at: https://github.com/stuartcarroll/ChatExtract/issues

For sensitive security issues, consider using GitHub Security Advisories instead.

---

## Issue 1: [CRITICAL] Path Traversal in Chunked Upload Controller

**Labels**: `security`, `critical`, `bug`
**Priority**: P0 - Immediate

**Title**: [SECURITY] Path Traversal Vulnerability in Chunked Upload

**Description**:
## Security Issue
User-supplied `upload_id` parameter is used directly in file path construction without validation, allowing path traversal attacks.

## Location
`app/Http/Controllers/ChunkedUploadController.php` lines 68-70

## Vulnerable Code
```php
$uploadId = $request->upload_id;  // From user input, not validated
$uploadDir = storage_path('app/uploads/' . $uploadId);  // Direct concatenation
```

## Risk
- **Severity**: CRITICAL
- **Attack Vector**: Remote, authenticated
- **Impact**: Arbitrary file write, potential code execution
- **Exploitability**: Easy

An attacker could use path traversal sequences (e.g., `../../`) to write files outside the intended directory, potentially leading to:
- Code execution if PHP files are written to web-accessible locations
- Data breach through writing to sensitive directories
- System compromise

## Reproduction Steps
1. Initiate a chunked upload normally
2. Capture the `upload_id` value
3. Modify subsequent chunk uploads to use `upload_id=../../malicious/path`
4. File is written outside intended directory

## Recommended Fix
```php
// Validate upload_id is a valid UUID
$request->validate([
    'upload_id' => 'required|uuid',
]);

// Defense in depth: verify resolved path
$uploadId = basename($request->upload_id);
$uploadDir = storage_path('app/uploads/' . $uploadId);
$realPath = realpath($uploadDir);

if (!$realPath || !str_starts_with($realPath, storage_path('app/uploads/'))) {
    abort(403, 'Invalid upload directory');
}
```

## References
- OWASP Path Traversal: https://owasp.org/www-community/attacks/Path_Traversal
- CWE-22: https://cwe.mitre.org/data/definitions/22.html

---

## Issue 2: [CRITICAL] Insecure Global Tag Management - No Authorization

**Labels**: `security`, `critical`, `bug`, `authorization`
**Priority**: P0 - Immediate

**Title**: [SECURITY] Any User Can Modify or Delete Any Tag

**Description**:
## Security Issue
Tag update and delete operations have no authorization checks. Any authenticated user can modify or delete tags created by other users.

## Location
`app/Http/Controllers/TagController.php` lines 62-82

## Vulnerable Code
```php
public function update(Request $request, Tag $tag)
{
    // NO authorization check!
    $tag->update(['name' => $request->name]);
    return redirect()->route('tags.index')->with('success', 'Tag updated successfully.');
}

public function destroy(Tag $tag)
{
    // NO authorization check!
    $tag->delete();
    return redirect()->route('tags.index')->with('success', 'Tag deleted successfully.');
}
```

## Risk
- **Severity**: HIGH
- **Attack Vector**: Remote, authenticated (any user)
- **Impact**: Data integrity, denial of service
- **Exploitability**: Trivial

Attackers can:
- Rename tags used by other users
- Delete all tags in the system
- Cause business disruption
- Corrupt data integrity

## Reproduction Steps
1. User A creates tag "important"
2. User B (malicious) accesses tag edit endpoint
3. User B renames tag to "garbage" or deletes it
4. User A's workflow is disrupted

## Recommended Fix

**Option 1** (Recommended): Add ownership
```php
// Migration
Schema::table('tags', function (Blueprint $table) {
    $table->foreignId('created_by')->nullable()->constrained('users');
});

// Controller
public function update(Request $request, Tag $tag)
{
    if ($tag->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
        abort(403, 'You can only modify tags you created.');
    }
    $tag->update(['name' => $request->name]);
    // ...
}
```

**Option 2**: Restrict to admin only in routes
```php
Route::middleware('admin')->group(function() {
    Route::put('/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
});
```

## References
- OWASP Broken Access Control: https://owasp.org/Top10/A01_2021-Broken_Access_Control/

---

## Issue 3: [CRITICAL] ZIP Bomb and Path Traversal in File Extraction

**Labels**: `security`, `critical`, `bug`
**Priority**: P0 - Immediate

**Title**: [SECURITY] Unvalidated ZIP Extraction Allows ZIP Bombs and Path Traversal

**Description**:
## Security Issue
ZIP file extraction has no validation for file count, size, paths, or compression ratio. This enables ZIP bomb attacks and path traversal.

## Location
`app/Jobs/ProcessChatImportJob.php` lines 81-90

## Vulnerable Code
```php
$zip = new \ZipArchive();
$zipStatus = $zip->open($this->filePath);
if ($zipStatus === true) {
    $zip->extractTo($extractPath);  // NO VALIDATION!
    $zip->close();
}
```

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

## Reproduction Steps
1. Create a ZIP bomb (highly compressed file)
2. Upload through import feature
3. Server disk space is exhausted

## Recommended Fix
```php
$zip = new \ZipArchive();
$zipStatus = $zip->open($this->filePath);

if ($zipStatus === true) {
    $fileCount = $zip->numFiles;

    // Validate file count
    if ($fileCount > 10000) {
        throw new \Exception('ZIP contains too many files (max 10,000)');
    }

    // Validate size and detect ZIP bombs
    $totalSize = 0;
    for ($i = 0; $i < $fileCount; $i++) {
        $stat = $zip->statIndex($i);
        $totalSize += $stat['size'];

        // Detect ZIP bombs (compression ratio > 100:1)
        if ($stat['comp_size'] > 0 && ($stat['size'] / $stat['comp_size']) > 100) {
            throw new \Exception('Suspicious compression ratio detected');
        }

        // Validate paths
        $filename = $stat['name'];
        if (str_contains($filename, '..') || str_starts_with($filename, '/')) {
            throw new \Exception('Invalid file path in ZIP: ' . $filename);
        }
    }

    // Validate total size (max 1GB)
    if ($totalSize > 1073741824) {
        throw new \Exception('ZIP uncompressed size exceeds limit (1GB)');
    }

    $zip->extractTo($extractPath);
    $zip->close();
}
```

## References
- ZIP Bomb: https://en.wikipedia.org/wiki/Zip_bomb
- CWE-409: https://cwe.mitre.org/data/definitions/409.html

---

## Issue 4: [CRITICAL] Mass Assignment Allows Privilege Escalation

**Labels**: `security`, `critical`, `bug`, `privilege-escalation`
**Priority**: P0 - Immediate

**Title**: [SECURITY] Role Field Mass Assignment Enables Privilege Escalation

**Description**:
## Security Issue
User model has `role` in `$fillable` array, allowing users to assign themselves admin privileges through mass assignment.

## Location
`app/Models/User.php` lines 22-27

## Vulnerable Code
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',  // DANGEROUS!
];
```

## Risk
- **Severity**: CRITICAL
- **Attack Vector**: Remote, unauthenticated (during registration) or authenticated
- **Impact**: Complete privilege escalation to admin
- **Exploitability**: Trivial

## Reproduction Steps
1. During user registration:
```bash
POST /register
{
  "name": "Attacker",
  "email": "attacker@evil.com",
  "password": "password",
  "role": "admin"  ← User becomes admin!
}
```

2. Or during profile update:
```bash
PATCH /profile
{
  "name": "Updated",
  "role": "admin"  ← Escalates to admin!
}
```

## Recommended Fix
```php
// Remove role from fillable
protected $fillable = [
    'name',
    'email',
    'password',
];

// Add to guarded instead
protected $guarded = [
    'id',
    'role',  // Prevent privilege escalation
    'email_verified_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
];
```

**In controllers, explicitly set role**:
```php
// Registration
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'role' => 'view_only',  // Explicit safe default
]);
```

## References
- Laravel Mass Assignment: https://laravel.com/docs/11.x/eloquent#mass-assignment
- OWASP Privilege Escalation: https://owasp.org/www-community/attacks/Privilege_escalation

---

## Issue 5: [HIGH] SQL Injection via Unescaped LIKE Wildcards

**Labels**: `security`, `high`, `bug`
**Priority**: P1 - High

**Title**: [SECURITY] LIKE Query Wildcards Not Escaped - DoS and Info Disclosure

**Description**:
## Security Issue
LIKE queries don't escape SQL wildcard characters (`%` and `_`), allowing performance attacks and search result manipulation.

## Location
- SearchController.php (multiple LIKE queries)
- ImportController.php (filename searches)

## Vulnerable Pattern
```php
$query->where('name', 'like', '%' . $request->search . '%');
// If search = "%%%", becomes LIKE '%%%%%%' - very expensive
```

## Risk
- **Severity**: MEDIUM-HIGH
- **Attack Vector**: Remote, authenticated
- **Impact**: Denial of service, information disclosure
- **Exploitability**: Easy

## Recommended Fix
```php
// Create helper method
private function escapeLikeValue(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
}

// Use in all LIKE queries
$searchTerm = $this->escapeLikeValue($request->search);
$query->where('name', 'like', '%' . $searchTerm . '%');
```

## References
- CWE-89: SQL Injection

---

## Issue 6: [HIGH] Insufficient File Type Validation in Chunked Upload

**Labels**: `security`, `high`, `bug`
**Priority**: P1 - High

**Title**: [SECURITY] No MIME Type Validation in File Uploads

**Description**:
## Security Issue
Chunked upload doesn't validate MIME type of uploaded files, only checks filename extension which can be spoofed.

## Location
`app/Http/Controllers/ChunkedUploadController.php` lines 60-66

## Risk
- **Severity**: HIGH
- **Impact**: Malware upload, potential code execution
- **Exploitability**: Moderate

## Recommended Fix
```php
// In finalize() method
$mimeType = mime_content_type($finalFullPath);
$allowedMimes = ['text/plain', 'application/zip', 'application/x-zip-compressed'];

if (!in_array($mimeType, $allowedMimes)) {
    unlink($finalFullPath);
    throw new \Exception('Invalid file type: ' . $mimeType);
}
```

---

## Issue 7: [MEDIUM] Weak Random Value in Export Directory Names

**Labels**: `security`, `medium`, `enhancement`
**Priority**: P2 - Medium

**Title**: [SECURITY] Predictable Temporary Directory Names in Export

**Description**:
## Security Issue
Export functions use `uniqid()` which is predictable. Should use cryptographically secure random values.

## Location
- ExportController.php:101
- TagController.php:186

## Recommended Fix
```php
use Illuminate\Support\Str;
$tempDir = storage_path('app/temp_exports/' . Str::uuid()->toString());
```

---

## Issue 8: [MEDIUM] Missing Rate Limiting on Export Endpoints

**Labels**: `security`, `medium`, `enhancement`
**Priority**: P2 - Medium

**Title**: [SECURITY] No Rate Limiting on Bulk Export - DoS Risk

**Description**:
## Security Issue
Export endpoints have no rate limiting, allowing resource exhaustion attacks.

## Location
routes/web.php - export routes

## Recommended Fix
```php
RateLimiter::for('exports', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()->id);
});

Route::middleware(['throttle:exports'])->group(function () {
    Route::post('/export', [ExportController::class, 'export']);
    Route::post('/tags/{tag}/export', [TagController::class, 'export']);
});
```

---

## Issue 9: [MEDIUM] Path Traversal Risk in Media Export

**Labels**: `security`, `medium`, `bug`
**Priority**: P2 - Medium

**Title**: [SECURITY] Insufficient Validation of Media File Paths in Export

**Description**:
## Security Issue
Media file paths from database are used directly without validation. If database is compromised, could expose sensitive files.

## Location
- ExportController.php:136
- TagController.php:218

## Recommended Fix
```php
// Validate file_path
if (str_contains($media->file_path, '..') || str_starts_with($media->file_path, '/')) {
    continue; // Skip
}

$mediaPath = storage_path('app/public/' . $media->file_path);
$realPath = realpath($mediaPath);

if (!$realPath || !str_starts_with($realPath, storage_path('app/public/'))) {
    continue; // Skip
}
```

---

## Priority Summary

**P0 - Critical (Fix Immediately)**:
- Issue 1: Path Traversal in Upload
- Issue 2: Insecure Tag Management
- Issue 3: ZIP Bomb/Path Traversal
- Issue 4: Mass Assignment Privilege Escalation

**P1 - High (Fix This Week)**:
- Issue 5: SQL LIKE Wildcards
- Issue 6: File Type Validation

**P2 - Medium (Fix This Month)**:
- Issue 7: Weak Random Values
- Issue 8: Missing Rate Limiting
- Issue 9: Media Path Validation

---

## Notes

- For P0 issues, consider using GitHub Security Advisories for responsible disclosure
- Test all fixes in development environment before deploying
- Update security documentation after fixes
- Schedule penetration testing after all fixes are deployed
