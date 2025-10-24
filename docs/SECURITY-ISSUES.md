# Security Issues - ChatExtract

**Last Review**: October 24, 2025
**Original Review**: October 20, 2025
**Status**: Multiple HIGH severity issues require immediate attention

This document lists all confirmed security issues that need to be addressed. Create GitHub issues from this list at: https://github.com/stuartcarroll/ChatExtract/issues

---

## ⚠️ CRITICAL ISSUES (Immediate Fix Required)

### SECURITY-001: Path Traversal in Chunked Upload Controller
**Severity**: HIGH
**Status**: OPEN (Verified October 24, 2025)
**File**: `app/Http/Controllers/ChunkedUploadController.php:68-70`

**Issue**:
User-supplied `upload_id` is used directly in file path construction without validation. An attacker could use path traversal sequences (e.g., `../../`) to write files outside the intended directory.

```php
// Line 68-70 - Vulnerable code
$uploadId = $request->upload_id;  // From user input, not validated
$uploadDir = storage_path('app/uploads/' . $uploadId);  // Direct concatenation
```

**Risk**:
- Arbitrary file write outside upload directory
- Potential code execution if PHP files are written to web-accessible locations
- Data breach through writing to sensitive directories

**Impact**: Critical - Could lead to full system compromise

**Recommendation**:
```php
// Validate upload_id is a valid UUID
$request->validate([
    'upload_id' => 'required|uuid',
]);

// Or use basename to prevent path traversal
$uploadId = basename($request->upload_id);
$uploadDir = storage_path('app/uploads/' . $uploadId);

// Verify resolved path is within expected directory (defense in depth)
$realPath = realpath($uploadDir);
if (!$realPath || !str_starts_with($realPath, storage_path('app/uploads/'))) {
    abort(403, 'Invalid upload directory');
}
```

**Testing**:
1. Attempt upload with `upload_id=../../etc/passwd`
2. Verify request is rejected
3. Check logs for the attempt

---

### SECURITY-002: Insecure Global Tag Management
**Severity**: HIGH
**Status**: OPEN (Verified October 24, 2025)
**File**: `app/Http/Controllers/TagController.php:62-82`

**Issue**:
Any authenticated user can update or delete ANY tag, regardless of who created it. Tags are global resources with no ownership checks.

```php
// Lines 62-72 - No authorization check
public function update(Request $request, Tag $tag)
{
    // No check if user owns or has permission to modify this tag
    $tag->update(['name' => $request->name]);
    return redirect()->route('tags.index')->with('success', 'Tag updated successfully.');
}

// Lines 77-82 - No authorization check
public function destroy(Tag $tag)
{
    // No check if user owns or has permission to delete this tag
    $tag->delete();
    return redirect()->route('tags.index')->with('success', 'Tag deleted successfully.');
}
```

**Risk**:
- Malicious users can rename tags used by others
- Data integrity issues when tags are deleted
- Denial of service by deleting all tags
- Business disruption

**Impact**: High - Data integrity and availability

**Recommendation**:

**Option 1** (Recommended): Add ownership to tags
```php
// Migration
Schema::table('tags', function (Blueprint $table) {
    $table->foreignId('created_by')->nullable()->constrained('users');
});

// Controller
public function update(Request $request, Tag $tag)
{
    // Only creator or admin can modify
    if ($tag->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
        abort(403, 'You can only modify tags you created.');
    }

    $tag->update(['name' => $request->name]);
    return redirect()->route('tags.index')->with('success', 'Tag updated successfully.');
}

public function destroy(Tag $tag)
{
    if ($tag->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
        abort(403, 'You can only delete tags you created.');
    }

    $tag->delete();
    return redirect()->route('tags.index')->with('success', 'Tag deleted successfully.');
}
```

**Option 2**: Restrict to admin only
```php
public function update(Request $request, Tag $tag)
{
    if (!auth()->user()->isAdmin()) {
        abort(403, 'Only administrators can modify tags.');
    }
    // ... rest of method
}
```

**Option 3**: Add to routes middleware
```php
// In routes/web.php - Move update/destroy to admin middleware
Route::middleware('admin')->group(function() {
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
});
```

---

### SECURITY-003: ZIP Bomb and Path Traversal in ZIP Extraction
**Severity**: HIGH
**Status**: OPEN (Verified October 24, 2025)
**File**: `app/Jobs/ProcessChatImportJob.php:81-90`

**Issue**:
ZIP file extraction has no validation for:
- File count limits
- Uncompressed size limits
- Path traversal in filenames
- Compression ratio (ZIP bomb detection)
- Symlink attacks

```php
// Lines 81-90 - No validation before extraction
$zip = new \ZipArchive();
$zipStatus = $zip->open($this->filePath);

if ($zipStatus === true) {
    $fileCount = $zip->numFiles;
    $progress->addLog("ZIP opened successfully, contains {$fileCount} files");
    $progress->addLog("Extracting {$fileCount} files...");

    $zip->extractTo($extractPath);  // UNSAFE - No validation
    $zip->close();
}
```

**Risks**:
- **ZIP Bombs**: Highly compressed files (e.g., 42.zip) can exhaust disk space
- **Path Traversal**: Files like `../../etc/passwd` can be extracted outside intended directory
- **Symlink Attacks**: Malicious symlinks can access sensitive files
- **DoS**: Large file counts can exhaust system resources

**Impact**: Critical - System compromise, DoS, data breach

**Recommendation**:
```php
$zip = new \ZipArchive();
$zipStatus = $zip->open($this->filePath);

if ($zipStatus === true) {
    $fileCount = $zip->numFiles;

    // 1. Validate file count
    if ($fileCount > 10000) {
        throw new \Exception('ZIP contains too many files (max 10,000)');
    }

    // 2. Validate total uncompressed size and detect ZIP bombs
    $totalSize = 0;
    for ($i = 0; $i < $fileCount; $i++) {
        $stat = $zip->statIndex($i);
        $totalSize += $stat['size'];

        // Check for ZIP bombs (compression ratio > 100:1 is suspicious)
        if ($stat['comp_size'] > 0 && ($stat['size'] / $stat['comp_size']) > 100) {
            throw new \Exception('Suspicious compression ratio detected (potential ZIP bomb)');
        }

        // 3. Validate file paths (no traversal, no absolute paths)
        $filename = $stat['name'];
        if (str_contains($filename, '..') || str_starts_with($filename, '/')) {
            throw new \Exception('Invalid file path in ZIP: ' . $filename);
        }

        // 4. Check for symlinks (if possible with available ZIP library)
        // Note: ZipArchive doesn't expose this easily, may need additional checks
    }

    // 5. Validate total uncompressed size (max 1GB)
    if ($totalSize > 1073741824) {
        throw new \Exception('ZIP uncompressed size exceeds limit (1GB)');
    }

    $progress->addLog("ZIP validation passed: {$fileCount} files, " .
                      round($totalSize / 1024 / 1024, 2) . " MB uncompressed");

    $zip->extractTo($extractPath);
    $zip->close();
}
```

**Additional Security**:
```php
// After extraction, scan for suspicious files
$extractedFiles = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($extractPath)
);

foreach ($extractedFiles as $file) {
    if ($file->isFile()) {
        // Check for executable files
        if (is_executable($file->getPathname())) {
            throw new \Exception('Executable files not allowed in uploads');
        }

        // Validate MIME types for known extensions
        // Add your validation here
    }
}
```

---

### SECURITY-004: Mass Assignment Vulnerability - Role Privilege Escalation
**Severity**: HIGH
**Status**: OPEN (Verified October 24, 2025)
**File**: `app/Models/User.php:22-34`

**Issue**:
The User model has `role` in `$fillable`, which means it can be mass-assigned. This could allow privilege escalation attacks.

```php
// Lines 22-27 - role is fillable!
protected $fillable = [
    'name',
    'email',
    'password',
    'role',  // DANGEROUS - allows mass assignment
];

// Lines 33-34 - guarded doesn't protect critical fields
protected $guarded = ['email_verified_at'];
```

**Risk**:
- User registration could include `role=admin` parameter
- Profile update could include `role=admin` parameter
- Privilege escalation to admin access
- Full system compromise

**Attack Example**:
```bash
# During registration
POST /register
{
  "name": "Attacker",
  "email": "attacker@evil.com",
  "password": "password",
  "role": "admin"  // Becomes admin!
}

# During profile update
PATCH /profile
{
  "name": "Updated Name",
  "role": "admin"  // Escalates to admin!
}
```

**Impact**: Critical - Complete privilege escalation

**Recommendation**:
```php
// Remove $fillable, use $guarded instead for critical fields
protected $fillable = [
    'name',
    'email',
    'password',
];

// Explicitly guard all security-critical fields
protected $guarded = [
    'id',
    'role',  // CRITICAL - prevent privilege escalation
    'email_verified_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
];

// Better: Use $guarded exclusively
protected $guarded = [
    'id',
    'role',
    'email_verified_at',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
];

// Remove $fillable entirely - be explicit about what's NOT allowed
```

**Immediate Mitigation**:
1. Review all User creation/update code
2. Never use `User::create($request->all())`
3. Always explicitly set role:
```php
// In registration
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'role' => 'view_only',  // Explicit, safe default
]);
```

**Testing**:
1. Attempt to register with `role=admin` in request
2. Verify role is NOT set to admin
3. Attempt profile update with `role=admin`
4. Verify role doesn't change

---

### SECURITY-005: SQL Injection via LIKE Wildcards
**Severity**: MEDIUM-HIGH
**Status**: OPEN (Verified October 24, 2025)
**Files**: Multiple controllers using LIKE queries

**Issue**:
User input in LIKE queries doesn't escape SQL wildcard characters (`%` and `_`), allowing attackers to manipulate search results or cause performance issues.

```php
// Example vulnerable code pattern
$query->where('name', 'like', '%' . $request->search . '%');
// If $request->search = "%%%", this becomes LIKE '%%%%%%' which is expensive
```

**Risk**:
- Performance degradation (DoS) with wildcard spam
- Information disclosure through wildcard manipulation
- Bypassing search restrictions

**Impact**: Medium-High - DoS and information disclosure

**Recommendation**:
```php
// Create helper method
private function escapeLikeValue(string $value): string
{
    // Escape backslash first, then wildcards
    return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
}

// Use in queries
$searchTerm = $this->escapeLikeValue($request->search);
$query->where('name', 'like', '%' . $searchTerm . '%');
```

**Files to Fix**:
- SearchController.php (participant name search)
- ImportController.php (filename search)
- Any other LIKE queries using user input

---

### SECURITY-006: Insufficient File Type Validation in Chunked Upload
**Severity**: MEDIUM-HIGH
**Status**: OPEN (Verified October 24, 2025)
**File**: `app/Http/Controllers/ChunkedUploadController.php:60-66`

**Issue**:
Chunk uploads don't validate MIME type, only final filename extension. Attackers could upload malicious files by manipulating chunk content.

```php
// Lines 60-66 - No MIME validation
$request->validate([
    'upload_id' => 'required|string',
    'chunk_index' => 'required|integer|min:0|max:10000',
    'chunk' => 'required|file',  // No MIME type check
]);
```

**Risk**:
- Upload of executable files (PHP, shell scripts)
- Upload of malware
- Bypass of extension-based checks
- Code execution if files are accessible

**Impact**: High - Potential code execution

**Recommendation**:
```php
// In initiate() method - validate extension
$request->validate([
    'filename' => [
        'required',
        'string',
        function ($attribute, $value, $fail) {
            $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
            $allowedExtensions = ['txt', 'zip'];
            if (!in_array($extension, $allowedExtensions)) {
                $fail('Only .txt and .zip files are allowed.');
            }
        },
    ],
]);

// In finalize() method - validate MIME type
$mimeType = mime_content_type($finalFullPath);
$allowedMimes = [
    'text/plain',
    'application/zip',
    'application/x-zip-compressed',
    'application/octet-stream',  // Some systems report ZIP as this
];

if (!in_array($mimeType, $allowedMimes)) {
    // Delete the file
    unlink($finalFullPath);
    throw new \Exception('Invalid file type detected: ' . $mimeType);
}

// Additional: Check file header (magic bytes)
$fileHeader = file_get_contents($finalFullPath, false, null, 0, 4);
// ZIP files start with PK (0x504B)
if ($extension === 'zip' && substr($fileHeader, 0, 2) !== 'PK') {
    unlink($finalFullPath);
    throw new \Exception('File claims to be ZIP but header is invalid');
}
```

---

## 🔸 MEDIUM SEVERITY ISSUES

### SECURITY-007: Weak Random Value in Export
**Severity**: MEDIUM
**Status**: NEW (Found in delta review October 24, 2025)
**Files**:
- `app/Http/Controllers/ExportController.php:101`
- `app/Http/Controllers/TagController.php:186`

**Issue**:
Export functions use `uniqid()` for temporary directory names, which is predictable and not cryptographically secure.

```php
// Line 101 (ExportController) - Weak random
$tempDir = storage_path('app/temp_exports/' . uniqid());
```

**Risk**:
- Predictable directory names
- Potential unauthorized access to export files
- Race condition attacks

**Impact**: Medium - Information disclosure

**Recommendation**:
```php
use Illuminate\Support\Str;

// Use UUID instead
$tempDir = storage_path('app/temp_exports/' . Str::uuid()->toString());

// Or use random_bytes for stronger randomness
$tempDir = storage_path('app/temp_exports/' . bin2hex(random_bytes(16)));
```

---

### SECURITY-008: Missing Rate Limiting on Export Endpoints
**Severity**: MEDIUM
**Status**: NEW (Found in delta review October 24, 2025)
**Files**: `routes/web.php:98, 74`

**Issue**:
No rate limiting on bulk export and tag export endpoints. Users could abuse these to cause resource exhaustion.

```php
// No throttle middleware
Route::post('/export', [App\Http\Controllers\ExportController::class, 'export'])
    ->name('export.bulk');
Route::post('/tags/{tag}/export', [TagController::class, 'export'])
    ->name('tags.export');
```

**Risk**:
- Resource exhaustion (disk space, CPU, memory)
- Denial of Service
- Abuse of export feature

**Impact**: Medium - DoS

**Recommendation**:
```php
// Define rate limiter in App\Providers\RouteServiceProvider or bootstrap/app.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('exports', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()->id);
});

// Apply to routes
Route::middleware(['throttle:exports'])->group(function () {
    Route::post('/export', [App\Http\Controllers\ExportController::class, 'export'])
        ->name('export.bulk');
    Route::post('/tags/{tag}/export', [TagController::class, 'export'])
        ->name('tags.export');
});
```

---

### SECURITY-009: Path Traversal in Media Export
**Severity**: MEDIUM
**Status**: NEW (Found in delta review October 24, 2025)
**Files**:
- `app/Http/Controllers/ExportController.php:136`
- `app/Http/Controllers/TagController.php:218`

**Issue**:
Media file paths from database are used directly in file operations without validation.

```php
// Line 136 - Direct use of database value
$mediaPath = storage_path('app/public/' . $media->file_path);

if (file_exists($mediaPath)) {
    $mediaFilename = $this->getUniqueFilename($media->filename, $filenameCounts);
    $zip->addFile($mediaPath, $mediaFilename);
}
```

**Risk**:
- If `file_path` in database is compromised (e.g., `../../etc/passwd`)
- Could expose sensitive files in exports
- Information disclosure

**Impact**: Medium - Information disclosure (requires DB compromise)

**Recommendation**:
```php
// Validate file_path doesn't contain traversal
if (str_contains($media->file_path, '..') || str_starts_with($media->file_path, '/')) {
    Log::warning('Suspicious file_path in media export', [
        'media_id' => $media->id,
        'file_path' => $media->file_path,
    ]);
    continue; // Skip this file
}

$mediaPath = storage_path('app/public/' . $media->file_path);

// Verify resolved path is within expected directory
$realPath = realpath($mediaPath);
if (!$realPath || !str_starts_with($realPath, storage_path('app/public/'))) {
    Log::warning('Media file outside allowed directory', [
        'media_id' => $media->id,
        'attempted_path' => $mediaPath,
    ]);
    continue; // Skip this file
}

if (file_exists($realPath)) {
    $mediaFilename = $this->getUniqueFilename($media->filename, $filenameCounts);
    $zip->addFile($realPath, $mediaFilename);
}
```

---

## ✅ FIXED ISSUES

### SECURITY-FI X-001: Missing Authorization on Admin Routes
**Severity**: HIGH
**Status**: ✅ FIXED (Verified October 24, 2025)
**File**: `routes/web.php:86-95`

**Previous Issue**:
Transcription routes lacked route-level admin middleware protection.

**Fix Applied**:
```php
// Now properly protected with admin middleware
Route::middleware('admin')->group(function() {
    Route::get('/transcription/dashboard', [TranscriptionController::class, 'dashboard'])
        ->name('transcription.dashboard');
    // ... other admin routes
});
```

**Verification**: Admin middleware (`app/Http/Middleware/EnsureUserIsAdmin.php`) is properly enforced on all admin routes.

---

## Summary

**Total Issues**: 9 security issues
**Critical**: 4 (SECURITY-001, 002, 003, 004)
**High**: 2 (SECURITY-005, 006)
**Medium**: 3 (SECURITY-007, 008, 009)
**Fixed**: 1 (SECURITY-FIX-001)

**Recommended Fix Priority**:
1. **Immediate** (This Week): SECURITY-001, 002, 003, 004
2. **High Priority** (Next Week): SECURITY-005, 006
3. **Medium Priority** (This Month): SECURITY-007, 008, 009

**Estimated Remediation Time**: 24-32 hours total
- Critical issues: 16-20 hours
- High priority: 4-6 hours
- Medium priority: 4-6 hours

---

## Next Steps

1. Create individual GitHub issues for each security problem
2. Assign priority labels (critical, high, medium)
3. Assign to development team
4. Track progress in security project board
5. Schedule penetration testing after fixes
6. Update security documentation

---

## Additional Recommendations

1. **Security Headers**: Add security headers middleware
2. **Content Security Policy**: Implement CSP
3. **Dependency Scanning**: Use Snyk or Dependabot
4. **SAST**: Integrate static analysis tool (Laravel Enlightn)
5. **Penetration Testing**: Schedule professional security audit
6. **Security Training**: Team training on secure coding practices

---

**For questions or to report new security issues**: Use GitHub Security Advisories for sensitive issues or create a private security issue.
