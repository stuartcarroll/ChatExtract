# Quick Create Security Issues

Click "New Issue" 9 times and copy/paste these:

---

## Issue 1
**Title**: [SECURITY] Path Traversal Vulnerability in Chunked Upload
**Labels**: security, critical, bug
**Body**:
```
Path traversal in ChunkedUploadController.php:68-70

CRITICAL: User can write files anywhere on server via upload_id parameter

Fix: Validate upload_id is UUID and verify resolved path

Priority: P0 - Fix Immediately
See: docs/SECURITY-ISSUES.md #SECURITY-001
```

---

## Issue 2
**Title**: [SECURITY] Any User Can Modify or Delete Any Tag
**Labels**: security, critical, bug, authorization
**Body**:
```
No authorization in TagController.php:62-82

CRITICAL: Any authenticated user can modify/delete any tag

Fix: Add ownership or restrict to admin
Priority: P0 - Fix Immediately
See: docs/SECURITY-ISSUES.md #SECURITY-002
```

---

## Issue 3
**Title**: [SECURITY] ZIP Bomb and Path Traversal in Extraction
**Labels**: security, critical, bug
**Body**:
```
No validation in ProcessChatImportJob.php:81-90

CRITICAL: ZIP bombs, path traversal, symlink attacks possible

Fix: Validate file count, size, paths, compression ratio
Priority: P0 - Fix Immediately
See: docs/SECURITY-ISSUES.md #SECURITY-003
```

---

## Issue 4
**Title**: [SECURITY] Mass Assignment Privilege Escalation
**Labels**: security, critical, bug, privilege-escalation
**Body**:
```
Role field in $fillable - User.php:22-27

CRITICAL: Users can assign themselves admin role during registration

Fix: Remove role from $fillable, add to $guarded
Priority: P0 - Fix Immediately
See: docs/SECURITY-ISSUES.md #SECURITY-004
```

---

## Issue 5
**Title**: [SECURITY] SQL LIKE Wildcards Not Escaped
**Labels**: security, high, bug
**Body**:
```
LIKE queries in SearchController don't escape % and _

HIGH: DoS via wildcard spam, information disclosure

Fix: Add escapeLikeValue() helper method
Priority: P1 - Fix This Week
See: docs/SECURITY-ISSUES.md #SECURITY-005
```

---

## Issue 6
**Title**: [SECURITY] No MIME Type Validation in Uploads
**Labels**: security, high, bug
**Body**:
```
ChunkedUploadController.php:60-66 - only checks extension

HIGH: Malware upload, potential code execution

Fix: Validate MIME type in finalize() method
Priority: P1 - Fix This Week
See: docs/SECURITY-ISSUES.md #SECURITY-006
```

---

## Issue 7
**Title**: [SECURITY] Weak Random in Export Directory Names
**Labels**: security, medium, enhancement
**Body**:
```
uniqid() used in ExportController:101, TagController:186

MEDIUM: Predictable directory names

Fix: Use Str::uuid() instead
Priority: P2 - Fix This Month
See: docs/SECURITY-ISSUES.md #SECURITY-007
```

---

## Issue 8
**Title**: [SECURITY] No Rate Limiting on Export Endpoints
**Labels**: security, medium, enhancement
**Body**:
```
Export routes have no throttle middleware

MEDIUM: Resource exhaustion DoS

Fix: Add throttle:exports middleware
Priority: P2 - Fix This Month
See: docs/SECURITY-ISSUES.md #SECURITY-008
```

---

## Issue 9
**Title**: [SECURITY] Media Path Validation in Export
**Labels**: security, medium, bug
**Body**:
```
Media paths from DB used without validation - ExportController:136

MEDIUM: Info disclosure if DB compromised

Fix: Validate paths don't contain .. or start with /
Priority: P2 - Fix This Month
See: docs/SECURITY-ISSUES.md #SECURITY-009
```

---

**Total**: 9 issues
- P0 Critical: Issues 1-4 (Fix Immediately)
- P1 High: Issues 5-6 (Fix This Week)
- P2 Medium: Issues 7-9 (Fix This Month)

**Full details**: See docs/GITHUB-SECURITY-ISSUES.md for complete issue descriptions
