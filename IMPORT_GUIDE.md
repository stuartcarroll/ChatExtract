# Import Guide

## Large File Imports

ChatExtract now supports efficient importing of large WhatsApp chat exports (1GB+) with real-time progress tracking.

### Features

- ✅ **Asynchronous Processing**: Imports run in background queue jobs
- ✅ **Real-Time Progress**: Watch messages and media being imported live
- ✅ **Memory Efficient**: Processes messages in chunks of 500 to avoid memory exhaustion
- ✅ **Media Breakdown**: See counts of images, videos, and audio files
- ✅ **No Timeouts**: Large files won't timeout anymore
- ✅ **Error Handling**: Failed imports show clear error messages

### Web Upload

1. Go to **Import New Chat**
2. Upload your `.txt` or `.zip` file (up to 10GB)
3. Enter chat name and optional description
4. Click **Import Chat**
5. You'll be redirected to a progress page showing:
   - Overall status (Pending → Processing → Completed/Failed)
   - Messages imported (count and percentage)
   - Media files processed (count and percentage)
   - Breakdown by media type (photos, videos, audio)

The page auto-refreshes every 2 seconds until complete.

### Server-Side Import (CLI)

For very large files or bulk imports, you can use the command line directly on the server:

```bash
# Basic import
php artisan chat:import /path/to/chat.zip --user=user@example.com --name="My Chat"

# With description
php artisan chat:import /path/to/chat.zip \
  --user=user@example.com \
  --name="Family Group" \
  --description="Family chat from 2020-2024"

# Run synchronously (wait for completion, see live output)
php artisan chat:import /path/to/chat.zip \
  --user=user@example.com \
  --name="My Chat" \
  --sync

# Check import status
php artisan chat:import-status 123
```

#### Command Options

- `file`: Path to the `.txt` or `.zip` file (required)
- `--user=`: User ID or email address to import for (required)
- `--name=`: Name for the imported chat (optional, will prompt)
- `--description=`: Description for the chat (optional)
- `--sync`: Run synchronously instead of queuing (useful for debugging)

### Queue Worker

**Important**: The queue worker must be running for asynchronous imports to process.

Start the queue worker:
```bash
php artisan queue:work --daemon --tries=3 --timeout=3600
```

For production, use a process manager like Supervisor to keep the queue worker running.

### Troubleshooting

**Import stuck on "Pending"**
- Make sure the queue worker is running: `ps aux | grep queue:work`
- Start it with: `php artisan queue:work --daemon`

**Import failed with error**
- Check the error message on the progress page
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Common issues:
  - Invalid file format (must be WhatsApp export)
  - Corrupted ZIP file
  - Permission issues on storage directory

**Memory issues**
- The import processes messages in chunks of 500
- Adjust chunk size in `ProcessChatImportJob.php` if needed
- Increase PHP memory limit in `php.ini` for very large files

### Performance Tips

1. **Large files (1GB+)**: Use server-side CLI import
2. **Bulk imports**: Queue multiple imports, they'll process sequentially
3. **Very large media**: Media processing is separate from message import
4. **Database**: Consider using MySQL/PostgreSQL instead of SQLite for better performance with large datasets

## File Size Limits

- **Web Upload**: 10GB max (configurable in `ImportController.php`)
- **Server-Side**: No limit (depends on disk space)
- **PHP Settings**:
  - `upload_max_filesize`: 10240M
  - `post_max_size`: 10240M
  - `memory_limit`: -1 (unlimited)
  - `max_execution_time`: 0 (unlimited)

## Technical Details

### Database Schema

The `import_progress` table tracks:
- User and chat associations
- File information
- Message/media counts and progress
- Status and timestamps
- Error messages if failed

### Import Flow

1. File uploaded → stored in `storage/app/imports/`
2. ZIP extracted → temp directory
3. ImportProgress record created
4. ProcessChatImportJob dispatched to queue
5. Job processes:
   - Parses WhatsApp chat file
   - Creates Chat record
   - Imports messages in chunks (500 at a time)
   - Processes media files from ZIP
   - Updates progress in real-time
6. Cleanup temp files
7. Mark as completed

### Queue Configuration

Queue: `database` (default)
Connection: Database
Jobs table: `jobs`
Failed jobs table: `failed_jobs`

To use Redis for better performance:
1. Install Redis
2. Update `.env`: `QUEUE_CONNECTION=redis`
3. Run: `php artisan queue:work redis`
