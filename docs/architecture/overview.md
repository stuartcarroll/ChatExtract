# Architecture Overview

## System Architecture

ChatExtract is built on Laravel 11 following MVC (Model-View-Controller) architecture with a service-oriented approach for complex business logic.

```
┌─────────────────────────────────────────────────────────────┐
│                         Frontend                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  Blade Views │  │ Tailwind CSS │  │  Alpine.js   │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└────────────────────────────┬────────────────────────────────┘
                             │ HTTP/HTTPS
┌────────────────────────────┴────────────────────────────────┐
│                     Laravel Application                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │                    Controllers                        │   │
│  │  - ChatController                                     │   │
│  │  - ImportController                                   │   │
│  │  - SearchController                                   │   │
│  │  - TranscriptionController                            │   │
│  │  - TagController, ExportController, etc.              │   │
│  └──────────────┬───────────────────────────────────────┘   │
│                 │                                             │
│  ┌──────────────┴───────────────────────────────────────┐   │
│  │                       Services                        │   │
│  │  - WhatsAppParserService (chat parsing)               │   │
│  │  - WhisperTranscriptionService (OpenAI integration)   │   │
│  │  - StoryDetectionService (AI-powered detection)       │   │
│  └──────────────┬───────────────────────────────────────┘   │
│                 │                                             │
│  ┌──────────────┴───────────────────────────────────────┐   │
│  │                        Models                         │   │
│  │  Chat → Messages → Media, Tags, Participants          │   │
│  │  User → Groups → ChatAccess, TagAccess                │   │
│  └──────────────┬───────────────────────────────────────┘   │
│                 │                                             │
│  ┌──────────────┴───────────────────────────────────────┐   │
│  │                    Job Queue                          │   │
│  │  - ProcessChatImportJob (async import)                │   │
│  │  - TranscribeMediaJob (async transcription)           │   │
│  │  - DetectStoryJob (async story detection)             │   │
│  └──────────────┬───────────────────────────────────────┘   │
│                 │                                             │
└─────────────────┴─────────────────────────────────────────┬─┘
                                                              │
┌─────────────────────────────────────────────────────────────┴┐
│                         Data Layer                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │    MySQL     │  │  File Storage│  │  Scout Index │       │
│  │   Database   │  │   (Laravel)  │  │  (Database)  │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└────────────────────────────────────────────────────────────────┘

                              │
                              ▼
                    ┌──────────────────┐
                    │   External APIs   │
                    │ - OpenAI Whisper  │
                    │   (Transcription) │
                    └──────────────────┘
```

## Technology Stack

### Backend
- **Framework**: Laravel 11.x
- **Language**: PHP 8.2+
- **Authentication**: Laravel Breeze with custom 2FA
- **Authorization**: Policies and middleware
- **Queue System**: Database driver (supports Redis/SQS)
- **File Storage**: Local filesystem (configurable to S3)
- **Search**: Laravel Scout with database driver

### Frontend
- **Templating**: Blade (Laravel)
- **CSS Framework**: Tailwind CSS 3.x
- **JavaScript**: Alpine.js for interactive components
- **Build Tool**: Vite
- **Icons**: SVG icons (inline)

### Database
- **Primary**: MySQL 5.7+ or MariaDB 10.2+
- **Character Set**: utf8mb4 (full Unicode support)
- **Indexes**: Optimized for search and filtering

### External Services
- **OpenAI Whisper API**: Audio transcription
- **Future**: Meilisearch for advanced search (optional)

## Key Architectural Patterns

### 1. Service Layer Pattern
Complex business logic is extracted into dedicated service classes:
- `WhatsAppParserService`: Parses WhatsApp export files
- `WhisperTranscriptionService`: Handles OpenAI API integration
- `StoryDetectionService`: AI-powered story detection

### 2. Job Queue Pattern
Long-running tasks are processed asynchronously:
- **Import**: Chat imports run in background
- **Transcription**: Audio transcription queued per file
- **Indexing**: Search index updates are queued

### 3. Policy-Based Authorization
- Resource authorization using Laravel Policies
- Role-based access control (Admin, Chat User, View Only)
- Polymorphic access grants (users/groups → chats/tags)

### 4. Repository-Like Models
Models use Eloquent ORM with:
- Relationships defined clearly
- Scopes for common queries
- Accessors/Mutators for data transformation

## Request Lifecycle

### 1. Chat Import Flow
```
User uploads ZIP → ImportController::store()
                ↓
         Validate & store file
                ↓
         Dispatch ProcessChatImportJob
                ↓
         Job processes in background:
         - Parse WhatsApp export
         - Extract media files
         - Create database records
         - Queue TranscribeMediaJob (if consent)
         - Update progress
```

### 2. Search Flow
```
User submits search → SearchController::search()
                   ↓
            Build query with filters
                   ↓
            Execute Scout search
                   ↓
            Filter by user's accessible chats
                   ↓
            Return paginated results
```

### 3. Export Flow
```
User selects items → ExportController::export()
                  ↓
           Validate selection & authorization
                  ↓
           Create temporary directory
                  ↓
           Build ZIP file:
           - Add message text files
           - Add media files
           - Add manifest
                  ↓
           Stream download & cleanup
```

## Security Layers

### 1. Authentication Layer
- Session-based authentication
- Optional 2FA with TOTP
- Password hashing (bcrypt)
- Remember me tokens

### 2. Authorization Layer
- Role-based access (Admin, Chat User, View Only)
- Resource-level policies (ChatPolicy)
- Per-chat access grants (polymorphic)
- Per-tag access grants (for view-only users)

### 3. Input Validation Layer
- Form requests for complex validations
- Controller-level validation
- File upload restrictions
- CSRF protection

### 4. Data Protection Layer
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade escaping)
- Mass assignment protection
- File path sanitization

## Scalability Considerations

### Current Architecture
- **Designed for**: Single server, moderate traffic
- **Database**: Supports ~1M messages comfortably
- **File Storage**: Local filesystem
- **Queue**: Database-backed (simple setup)

### Scaling Path
When scaling is needed:

1. **Queue System**: Migrate to Redis/SQS
2. **Search**: Migrate to Meilisearch or Elasticsearch
3. **File Storage**: Migrate to S3/CloudFront
4. **Database**: Read replicas, connection pooling
5. **Cache**: Add Redis for session/cache
6. **Load Balancer**: Horizontal scaling with multiple app servers

## Directory Structure

```
ChatExtract/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Http/
│   │   ├── Controllers/      # Request handlers
│   │   ├── Middleware/       # Custom middleware
│   │   └── Requests/         # Form validation
│   ├── Jobs/                 # Queue jobs
│   ├── Models/               # Eloquent models
│   ├── Policies/             # Authorization policies
│   ├── Services/             # Business logic services
│   └── View/Components/      # Blade components
├── config/                   # Configuration files
├── database/
│   ├── factories/            # Model factories
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── docs/                     # Documentation
├── public/                   # Web root
├── resources/
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript
│   └── views/                # Blade templates
├── routes/
│   ├── web.php               # Web routes
│   ├── auth.php              # Auth routes
│   └── console.php           # Console routes
├── scripts/                  # Deployment scripts
├── storage/
│   ├── app/public/           # User uploads
│   ├── framework/            # Framework files
│   └── logs/                 # Application logs
└── tests/                    # Tests
```

## Performance Optimizations

### Database
- Strategic indexes on foreign keys and search columns
- Eager loading to prevent N+1 queries
- Pagination for large result sets

### Frontend
- Lazy loading for images/videos
- Infinite scroll for galleries
- Debounced search inputs
- Minimal JavaScript (Alpine.js is lightweight)

### Caching
- Route caching (`php artisan route:cache`)
- Config caching (`php artisan config:cache`)
- View compilation
- Query result caching (where appropriate)

### Queue Processing
- Multiple queue workers for concurrency
- Prioritized queues (default > transcriptions > indexing)
- Job retry logic with exponential backoff

## Monitoring & Logging

### Logs
- **Location**: `storage/logs/laravel.log`
- **Rotation**: Configured in `config/logging.php`
- **Channels**: Single file (daily rotation available)

### Error Tracking
- Laravel exception handler
- 500 errors logged with full stack trace
- Failed jobs stored in `failed_jobs` table

### Performance Monitoring
- Queue worker status monitoring
- Database query logging (in development)
- Import progress tracking
- Transcription status tracking

## API Integration

### OpenAI Whisper API
- **Purpose**: Audio transcription
- **Rate Limiting**: Handled by queue system
- **Error Handling**: Retry logic with exponential backoff
- **Cost Control**: Consent-based transcription only

### Future APIs
- Potential integration with Meilisearch
- Potential webhook support for imports
- Potential export to cloud storage
