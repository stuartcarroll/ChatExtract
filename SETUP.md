# WhatsApp Archive System - Setup Guide

This is a complete Laravel application for archiving and analyzing WhatsApp chat exports with AI-powered story detection.

## Features

- WhatsApp chat import (.txt files)
- Message parsing with multiple date format support
- AI-powered story detection (Azure OpenAI & Claude API)
- Full-text search with Laravel Scout/MeiliSearch
- Media file management
- Tag system for organizing messages
- Access control with Laravel policies
- Background job processing for story detection

## System Requirements

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL/PostgreSQL/SQLite database
- MeiliSearch (for search functionality)
- Redis (optional, for queue processing)

## Installation Steps

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Configuration

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 3. Database Setup

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_archive
DB_USERNAME=root
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

### 4. MeiliSearch Setup

Install MeiliSearch: https://www.meilisearch.com/docs/learn/getting_started/installation

Start MeiliSearch:

```bash
meilisearch --master-key="your-master-key"
```

Configure in `.env`:

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-master-key
```

Index your messages:

```bash
php artisan scout:import "App\Models\Message"
```

### 5. Queue Configuration

For background story detection, configure queue in `.env`:

```env
QUEUE_CONNECTION=database
```

Create queue tables:

```bash
php artisan queue:table
php artisan migrate
```

Start queue worker:

```bash
php artisan queue:work --queue=story-detection
```

### 6. Storage Setup

Create storage symlink:

```bash
php artisan storage:link
```

Create media directory:

```bash
mkdir -p storage/app/media
```

### 7. PHP Configuration for Large Files

To allow uploads up to 10GB, update your `php.ini`:

```ini
upload_max_filesize = 10240M
post_max_size = 10240M
max_execution_time = 600
max_input_time = 600
memory_limit = 2048M
```

Restart your web server after changes.

### 8. AI Services Configuration (Optional)

#### Azure OpenAI

```env
AZURE_OPENAI_ENABLED=true
AZURE_OPENAI_ENDPOINT=https://your-resource.openai.azure.com
AZURE_OPENAI_API_KEY=your-api-key
AZURE_OPENAI_DEPLOYMENT_NAME=gpt-4
```

#### Claude API

```env
CLAUDE_ENABLED=true
CLAUDE_API_KEY=your-api-key
CLAUDE_MODEL=claude-3-haiku-20240307
```

Note: If neither AI service is configured, the system will use pattern-based story detection.

### 9. Build Frontend Assets

```bash
npm run build
```

For development:

```bash
npm run dev
```

### 10. Start the Application

```bash
php artisan serve
```

Visit: http://localhost:8000

## Usage

### 1. Register/Login

Create an account or login at `/register` or `/login`.

### 2. Import WhatsApp Chat

1. Export chat from WhatsApp:
   - Open the chat in WhatsApp
   - Tap menu (three dots) > More > Export chat
   - Choose "Without Media" or "Include Media"
   - Save the .txt file

2. Import in the application:
   - Click "Import New Chat"
   - Enter chat name and description
   - Upload the .txt file
   - Click "Import Chat"

3. Wait for processing:
   - Messages will be imported immediately
   - Story detection runs in the background
   - Refresh the chat page to see updated story detection results

### 3. Search Messages

- Click "Search" in the navigation
- Enter keywords to search across all your chats
- Use filters for date range, chat, tags, and story detection
- Use "Advanced Search" for more options

### 4. View and Filter Messages

- Click on a chat to view all messages
- Use filters to find specific messages:
  - By participant
  - By date range
  - Stories only
  - Has media

### 5. Upload Media Files (Optional)

If you exported your chat with media:

1. Go to the chat page
2. Click "Upload Media"
3. Select all media files from your WhatsApp export
4. The system will match them to messages by filename

## Database Schema

### Tables

- **chats**: Chat information (name, description, user_id)
- **participants**: Participants in chats (name, phone_number)
- **messages**: Individual messages (content, sent_at, is_story, story_confidence)
- **media**: Media attachments (type, filename, file_path)
- **tags**: User-defined tags
- **message_tag**: Pivot table for message-tag relationships
- **chat_user**: Pivot table for chat access control

## Architecture

### Services

- **WhatsAppParserService**: Parses WhatsApp .txt export files
  - Supports multiple date formats
  - Detects multi-line messages
  - Identifies system messages
  - Detects media references

- **StoryDetectionService**: Detects stories in messages
  - Pattern-based detection (temporal markers, past tense, narrative structure)
  - AI-powered detection (Azure OpenAI or Claude)
  - Combines both methods for best results

### Controllers

- **ChatController**: CRUD operations for chats
- **ImportController**: Handle chat imports and media uploads
- **SearchController**: Full-text search functionality

### Jobs

- **DetectStoryJob**: Background job for story detection
  - Queued automatically after import
  - Retries on failure (3 attempts)
  - Updates message with is_story and confidence

### Policies

- **ChatPolicy**: Authorization for chat operations
  - Owner can update/delete
  - Shared users can view

## Troubleshooting

### Import Issues

- **"No messages found"**: Check file format, ensure it's a valid WhatsApp export
- **Date parsing errors**: The parser supports multiple formats, but some locales may need adjustment

### Search Not Working

- Ensure MeiliSearch is running: `curl http://127.0.0.1:7700/health`
- Re-index messages: `php artisan scout:import "App\Models\Message"`

### Story Detection Not Running

- Check queue worker is running: `php artisan queue:work --queue=story-detection`
- Check failed jobs: `php artisan queue:failed`
- Retry failed jobs: `php artisan queue:retry all`

### Large File Upload Fails

- Check PHP settings in `php.ini`
- Check web server timeout settings (nginx/apache)
- Check Laravel upload limits in `config/filesystems.php`

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

Follow PSR-12 coding standards:

```bash
./vendor/bin/pint
```

### Database Seeding

Create test data:

```bash
php artisan db:seed
```

## Security Considerations

- All chat access is controlled via ChatPolicy
- Users can only view/edit their own chats
- Message hashes prevent duplicates
- Media files are stored privately by default
- CSRF protection enabled on all forms

## Performance Optimization

### For Large Chats

- Enable queue processing for imports
- Use database indexing on sent_at, chat_id, participant_id
- Consider pagination limits (currently 100 messages per page)

### For Many Users

- Enable Redis for caching and queues
- Consider horizontal scaling with load balancer
- Use CDN for static assets

## License

This project is open-source. Modify and use as needed.

## Support

For issues or questions, refer to the Laravel documentation:
- Laravel: https://laravel.com/docs
- Scout: https://laravel.com/docs/scout
- Breeze: https://laravel.com/docs/starter-kits#breeze
