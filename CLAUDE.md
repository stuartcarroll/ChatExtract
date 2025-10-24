# CLAUDE.md - AI Development Context

**Last Updated**: October 24, 2025
**Project**: ChatExtract
**Version**: 2.1
**Purpose**: Context file for Claude Code development sessions

---

## Quick Start for New Sessions

**Read this file at the start of every session to understand the project context.**

```
Key commands to remember:
- Read CLAUDE.md first
- Check docs/README.md for documentation structure
- Review recent commits: git log --oneline -10
- Check current status: git status
```

---

## Project Overview

**ChatExtract** is a Laravel 11 web application for managing WhatsApp chat exports with features for:
- Importing WhatsApp chat ZIP files with media
- Full-text search across messages
- AI-powered audio transcription (OpenAI Whisper)
- Tagging and organization
- Bulk export functionality
- Role-based access control
- Media gallery with filtering

**Tech Stack**:
- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Blade, Tailwind CSS, Alpine.js
- **Database**: MySQL/MariaDB
- **Queue**: Database driver (supports Redis)
- **Search**: Laravel Scout (database driver)
- **Storage**: Local filesystem (S3-ready)
- **Transcription**: OpenAI Whisper API

---

## Project Structure

```
ChatExtract/
├── app/
│   ├── Console/Commands/        # CLI commands
│   ├── Http/
│   │   ├── Controllers/         # Request handlers
│   │   │   ├── Admin/           # Admin-only controllers
│   │   │   ├── ChatController.php
│   │   │   ├── ImportController.php
│   │   │   ├── SearchController.php
│   │   │   ├── TagController.php
│   │   │   ├── ExportController.php
│   │   │   ├── TranscriptionController.php
│   │   │   └── GalleryController.php
│   │   ├── Middleware/          # Custom middleware
│   │   └── Requests/            # Form validation
│   ├── Jobs/                    # Queue jobs
│   │   ├── ProcessChatImportJob.php
│   │   ├── TranscribeMediaJob.php
│   │   └── DetectStoryJob.php
│   ├── Models/                  # Eloquent models
│   │   ├── Chat.php
│   │   ├── Message.php
│   │   ├── Media.php
│   │   ├── Participant.php
│   │   ├── Tag.php
│   │   ├── User.php
│   │   ├── Group.php
│   │   ├── ChatAccess.php
│   │   └── TagAccess.php
│   ├── Policies/                # Authorization
│   ├── Services/                # Business logic
│   │   ├── WhatsAppParserService.php
│   │   ├── WhisperTranscriptionService.php
│   │   └── StoryDetectionService.php
│   └── View/Components/         # Blade components
├── config/                      # Configuration
├── database/
│   ├── migrations/              # Database schema
│   └── factories/               # Test data factories
├── docs/                        # Documentation
│   ├── architecture/            # System design
│   ├── deployment/              # Deployment guides
│   └── development/             # Dev workflow
├── public/                      # Web root
├── resources/
│   ├── views/                   # Blade templates
│   │   ├── chats/               # Chat views
│   │   ├── gallery/             # Gallery views
│   │   ├── search/              # Search views
│   │   ├── tags/                # Tag management
│   │   ├── transcription/       # Transcription dashboard
│   │   └── admin/               # Admin panels
│   ├── css/                     # Tailwind CSS
│   └── js/                      # JavaScript/Alpine
├── routes/
│   ├── web.php                  # Web routes
│   └── auth.php                 # Auth routes
├── scripts/                     # Dev/deploy scripts
├── storage/
│   ├── app/public/              # User uploads
│   └── logs/                    # Application logs
└── tests/                       # PHPUnit tests
```

---

## Database Schema

### Core Tables

**users**
- id, name, email, password
- role (admin, chat_user, view_only)
- two_factor_secret, two_factor_recovery_codes

**chats**
- id, name, description, participant_count
- created_by_user_id (owner)

**messages**
- id, chat_id, participant_id
- content, sent_at
- is_story, is_system_message, media_caption

**media**
- id, message_id
- type (image/video/audio), file_path, filename
- transcription, transcription_status
- transcribed_at

**participants**
- id, name, phone_number
- transcription_consent (bool)

**tags** (global)
- id, name

**message_tag** (pivot)
- message_id, tag_id

### Access Control Tables

**groups**
- id, name, description

**group_user** (pivot)
- group_id, user_id

**chat_access** (polymorphic)
- id, chat_id
- accessible_type (User/Group)
- accessible_id

**tag_access** (polymorphic - for view_only users)
- id, tag_id
- accessible_type (User/Group)
- accessible_id

### System Tables

**import_progress**
- id, user_id, filename
- status, progress_percentage
- total_messages, processed_messages
- error_message

**jobs** (queue)
- id, queue, payload, attempts

**failed_jobs**
- id, connection, queue, payload, exception

---

## Key Features & Implementation

### 1. Chat Import
**Files**: `ImportController.php`, `ProcessChatImportJob.php`, `WhatsAppParserService.php`

**Flow**:
1. User uploads WhatsApp ZIP export
2. File validated and stored
3. `ProcessChatImportJob` dispatched
4. Job parses chat, extracts media, creates DB records
5. Progress tracked in `import_progress` table
6. Audio files queued for transcription (if consent)

**Important**:
- Supports chunked uploads for large files
- Parses WhatsApp format: `[MM/DD/YY, HH:MM:SS] Name: Message`
- Detects stories vs regular messages
- Handles media references like `<attached: IMG-20240101-WA0001.jpg>`

### 2. Search
**Files**: `SearchController.php`, `resources/views/search/index.blade.php`

**Capabilities**:
- Full-text search using Laravel Scout
- Filters: chat, participant, date range, media type, tags
- Respects user access permissions
- Pagination and authorization built-in

**Recent Addition**: Message selection with bulk export from search results

### 3. Transcription
**Files**: `TranscriptionController.php`, `TranscribeMediaJob.php`, `WhisperTranscriptionService.php`

**How it works**:
- Admin-only feature
- Requires participant consent
- Uses OpenAI Whisper API
- Processes audio files asynchronously
- Stores transcription in media.transcription
- Dashboard shows progress and statistics

**Consent Management**:
- `/transcription/participants` - manage consent
- Only transcribes audio for participants with consent = true

### 4. Tagging System
**Files**: `TagController.php`, `resources/views/tags/index.blade.php`

**Features**:
- Global tags (not chat-specific)
- Bulk tagging from gallery
- Quick tag creation
- Tag export (all messages + media for a tag)
- Tag access control for view-only users

**Recent Additions**:
- Bulk tagging in gallery with "Quick Tag"
- Export All button for each tag
- Batch tag API endpoint

### 5. Gallery & Media
**Files**: `GalleryController.php`, `resources/views/gallery/index.blade.php`

**Features**:
- Filter by type (all/image/video/audio)
- Filter by participant
- Filter by tags (including multi-tag)
- Infinite scroll
- Bulk selection with floating toolbar
- Select All functionality
- Bulk export to ZIP

**Floating Toolbar Actions**:
- Select All / Clear selection
- Export selected items
- Quick Tag (create and apply)
- All Tags (apply existing)
- Transcribe Audio (admin only)

### 6. Bulk Export
**Files**: `ExportController.php`, `TagController::export()`

**Creates ZIP files containing**:
- Message text files with metadata
- Original media files
- MANIFEST.txt with export details

**Export Sources**:
- Gallery selection
- Search results selection
- Tag-based export (all messages with tag)

### 7. Role-Based Access Control
**Roles**:
- **admin**: Full access, manage users/groups/access, transcription
- **chat_user**: Create/import chats, tag, export (normal user)
- **view_only**: Read-only access to granted chats/tags

**Middleware**:
- `admin` - Admin-only routes
- `chat_user` - Chat User and Admin (blocks view-only)

**Access Model**:
- Polymorphic access grants (user OR group → chat/tag)
- User can access chats they created + granted chats
- `User::accessibleChatIds()` - returns collection of accessible chat IDs

### 8. Two-Factor Authentication
**Files**: `TwoFactorController.php`, `TwoFactorChallengeController.php`

**Features**:
- TOTP-based 2FA (compatible with Google Authenticator)
- QR code setup
- Recovery codes (10 codes, single-use)
- Optional per-user

---

## Common Development Patterns

### Adding a New Controller Method

```php
// 1. Add route in routes/web.php
Route::get('/chats/{chat}/export', [ChatController::class, 'export'])
    ->name('chats.export');

// 2. Add controller method
public function export(Chat $chat)
{
    // Authorize
    $this->authorize('view', $chat);

    // Get user's accessible chat IDs
    $userChatIds = auth()->user()->accessibleChatIds()->toArray();

    // Verify access
    if (!in_array($chat->id, $userChatIds)) {
        abort(403);
    }

    // Business logic...

    return response()->download($path);
}
```

### Creating a Job

```php
// 1. Create job
php artisan make:job ProcessSomethingJob

// 2. Implement handle method
public function handle()
{
    // Access job data
    $data = $this->data;

    // Process...

    // Update progress if needed
    $this->progress->update(['progress_percentage' => 50]);
}

// 3. Dispatch job
ProcessSomethingJob::dispatch($data)->onQueue('default');
```

### Adding a Migration

```php
// Create migration
php artisan make:migration add_field_to_table

// In migration file
public function up()
{
    Schema::table('messages', function (Blueprint $table) {
        $table->boolean('is_pinned')->default(false)->after('content');
    });
}

public function down()
{
    Schema::table('messages', function (Blueprint $table) {
        $table->dropColumn('is_pinned');
    });
}
```

---

## Important Code Conventions

### Authorization Pattern
**Always check authorization for resources**:

```php
// Option 1: Policy (preferred)
$this->authorize('view', $chat);

// Option 2: Manual check
$userChatIds = auth()->user()->accessibleChatIds()->toArray();
if (!in_array($chat->id, $userChatIds)) {
    abort(403);
}
```

### File Upload Sanitization
```php
// Always sanitize filenames
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
```

### Queue Usage
**Use queues for long-running tasks**:
- Import processing
- Transcription
- Bulk operations
- Email sending

**Queue names**:
- `default` - General jobs
- `transcriptions` - Audio transcription
- `indexing` - Search index updates

### Error Handling
```php
try {
    // Risky operation
} catch (\Exception $e) {
    Log::error('Operation failed: ' . $e->getMessage());
    return back()->with('error', 'Operation failed. Please try again.');
}
```

---

## Recent Major Changes

### October 24, 2025 - Bulk Selection & Export
**Added**:
- Select All button in gallery floating toolbar
- Message selection in search results
- Floating selection toolbar in search
- Export All button for tags
- Tag export endpoint with ZIP generation

**Files Modified**:
- `resources/views/gallery/index.blade.php`
- `resources/views/search/index.blade.php`
- `resources/views/tags/index.blade.php`
- `app/Http/Controllers/TagController.php`
- `routes/web.php`

### October 24, 2025 - Documentation Reorganization
**Changed**:
- Moved docs from root and `deploy/` to `docs/` structure
- Created `docs/architecture/`, `docs/deployment/`, `docs/development/`
- Added comprehensive documentation
- Created CLAUDE.md for AI context
- Updated .gitignore for security

### October 24, 2025 - User Roles & Permissions
**Added**:
- Three-tier role system (admin, chat_user, view_only)
- Groups for organizing users
- Polymorphic access control
- Admin UI for user/group/access management
- Tag access control for view-only users

---

## Known Issues & TODOs

### Current Limitations
1. **File Storage**: Local filesystem only (S3 integration pending)
2. **Search**: Database driver (Meilisearch integration pending)
3. **Queue**: Database driver (Redis recommended for production)
4. **Email**: Not configured (notifications pending)

### Planned Enhancements
- Meilisearch integration for better search
- S3 storage for media files
- Email notifications for imports/transcriptions
- Chat export (in addition to import)
- Message editing/deletion
- Participant merging (duplicate detection)
- Advanced analytics dashboard

**See GitHub Issues for full list of enhancements and bugs**

---

## Testing

### Running Tests
```bash
# All tests
php artisan test

# Specific test
php artisan test tests/Feature/Controllers/ChatControllerTest.php

# With coverage (requires Xdebug)
php artisan test --coverage
```

### Writing Tests
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Use factories for test data
- Test authorization, validation, happy path, edge cases

---

## Deployment

### Development
```bash
# Copy and configure
cp scripts/start-dev.sh.example start-dev.sh
# Edit with your local DB credentials
./start-dev.sh
```

### Production
See `docs/deployment/production-guide.md` for full guide.

**Quick steps**:
1. Backup database
2. `php artisan down`
3. `git pull origin master`
4. `composer install --no-dev --optimize-autoloader`
5. `php artisan migrate --force`
6. `php artisan config:cache && php artisan route:cache`
7. `php artisan up`

**Server**: ploi@usvps.stuc.dev
**Path**: `/home/ploi/usvps.stuc.dev` (verify actual path)

---

## Useful Commands

```bash
# Database
php artisan migrate              # Run migrations
php artisan migrate:rollback     # Rollback last batch
php artisan migrate:fresh        # Drop all tables and re-migrate
php artisan db:seed              # Run seeders

# Queue
php artisan queue:work           # Start queue worker
php artisan queue:failed         # List failed jobs
php artisan queue:retry all      # Retry all failed jobs

# Cache
php artisan config:cache         # Cache config
php artisan route:cache          # Cache routes
php artisan view:clear           # Clear compiled views
php artisan cache:clear          # Clear application cache

# Tinker (REPL)
php artisan tinker
> App\Models\User::count();
> App\Models\Message::search('test')->get();

# Scout search index
php artisan scout:import "App\Models\Message"
php artisan scout:flush "App\Models\Message"
```

---

## Security Considerations

### Secrets Management
- **NEVER** commit `.env` file
- **NEVER** commit credentials in scripts
- Use `.env.example` for templates
- Use `.gitignore` to exclude sensitive files

### Files to Never Commit
- `.env`
- `*.sqlite`, `*.db` database files
- `start-dev.sh`, `import-db.sh` (use .example versions)
- `*.sql`, `*.dump` backup files
- Local configuration files

### Authorization Checklist
- [ ] Check user has access to chat/tag
- [ ] Use policies or manual authorization
- [ ] Filter queries by accessible IDs
- [ ] Validate user input
- [ ] Sanitize file paths/names

---

## Troubleshooting

### Queue not processing
```bash
# Check if worker is running
ps aux | grep "queue:work"

# Start worker
php artisan queue:work --queue=default,transcriptions,indexing
```

### Search not working
```bash
# Rebuild search index
php artisan scout:flush "App\Models\Message"
php artisan scout:import "App\Models\Message"
```

### 500 Errors
```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Import failing
- Check file upload size limits (.user.ini, php.ini)
- Check storage permissions
- Check queue worker is running
- Check logs for specific errors

---

## Development Workflow with Claude

### Starting a Session
1. **Open Claude Code in VS Code**
2. **Provide context**: "Read CLAUDE.md to understand the project"
3. **Check status**: "Show me the current git status and recent commits"
4. **Start work**: Describe feature/fix clearly

### During Development
- **Be specific**: "Add a filter to gallery for date range"
- **One task at a time**: Don't mix unrelated changes
- **Test frequently**: Verify changes work
- **Review code**: Check Claude's changes make sense

### Before Committing
- [ ] Test the feature/fix locally
- [ ] Review all changed files
- [ ] Ensure no secrets in code
- [ ] Update relevant documentation
- [ ] Write clear commit message

### After Committing
- Push to GitHub
- Deploy to production (if ready)
- Update this CLAUDE.md if significant changes
- Create GitHub issues for follow-up work

---

## Contact & Resources

- **Repository**: https://github.com/stuartcarroll/ChatExtract
- **Documentation**: `docs/README.md`
- **Issues**: GitHub Issues
- **Deployment**: `docs/deployment/production-guide.md`

---

## Session Checklist

**At start of each Claude Code session:**
- [ ] Read CLAUDE.md (this file)
- [ ] Check `git status` and recent commits
- [ ] Understand the current task
- [ ] Review relevant documentation

**Before ending session:**
- [ ] Test all changes
- [ ] Commit with clear message
- [ ] Push to GitHub
- [ ] Update CLAUDE.md if needed
- [ ] Create GitHub issues for remaining work

---

**Remember**: This file is the source of truth for AI-assisted development. Keep it updated with significant changes!
