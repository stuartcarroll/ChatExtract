# Production Deployment Guide - ChatExtract

**IMPORTANT: This deployment includes database migrations and new system dependencies. Follow steps carefully.**

---

## Overview of Changes

This deployment includes:
- ✅ User roles and permissions system (Admin, Chat User, View Only)
- ✅ Admin UI for user/group/access management
- ✅ Enhanced transcription system with consent management
- ✅ Multiple bug fixes (search, gallery, timestamps)
- ✅ 8 new database migrations
- ✅ FFmpeg dependency requirement
- ✅ Scout search index changes

---

## Pre-Deployment Checklist

- [ ] **Backup production database** (CRITICAL - migrations will modify schema)
- [ ] **Backup production code** directory
- [ ] **Verify SSH access** to production server
- [ ] **Check production PHP version** (requires PHP 8.2+)
- [ ] **Check production MySQL version** (requires MySQL 5.7+ or MariaDB 10.2+)
- [ ] **Notify users** of brief maintenance window

---

## Step 1: Backup Production Database

```bash
# SSH into production server
ssh your-production-server

# Backup database
cd /path/to/your/application
mysqldump -u your_db_user -p your_database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup was created
ls -lh backup_*.sql
```

**CRITICAL:** Do not proceed without a verified database backup.

---

## Step 2: Enable Maintenance Mode

```bash
# Put application in maintenance mode
php artisan down --message="Deploying new features. Back shortly!"
```

---

## Step 3: Pull Latest Code

```bash
# Ensure you're in the application directory
cd /path/to/your/application

# Stash any local changes (if needed)
git stash

# Pull latest code from master branch
git pull origin master

# Check current branch and commit
git log -1 --oneline
# Should show: "Add user roles, permissions, and admin UI system"
```

---

## Step 4: Install System Dependencies

### Install FFmpeg (Required for Audio Transcription)

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install -y ffmpeg

# Verify installation
ffmpeg -version
# Should show version 4.x or higher
```

**CentOS/RHEL:**
```bash
sudo yum install -y epel-release
sudo yum install -y ffmpeg

# Verify installation
ffmpeg -version
```

---

## Step 5: Install PHP Dependencies

```bash
# Update Composer dependencies
composer install --no-dev --optimize-autoloader

# If you get memory errors:
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

---

## Step 6: Update Environment Configuration

```bash
# Edit .env file
nano .env  # or vim .env

# Ensure these settings are correct:
```

**Required .env settings:**
```ini
# Application
APP_ENV=production
APP_DEBUG=false  # IMPORTANT: Must be false in production

# Database (verify these match your production DB)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Scout Search - Use database driver (no external service required)
SCOUT_DRIVER=database
SCOUT_PREFIX=
SCOUT_QUEUE=false

# Queue Configuration
QUEUE_CONNECTION=database  # or redis if you have it

# OpenAI (Required for transcription feature)
OPENAI_API_KEY=your-openai-api-key-here
# Get from: https://platform.openai.com/api-keys

# Session
SESSION_DRIVER=database  # or redis for better performance
```

---

## Step 7: Run Database Migrations

**CRITICAL: These migrations will modify your database schema.**

```bash
# First, preview what migrations will run
php artisan migrate:status

# Expected new migrations:
# - 2025_10_24_104437_add_role_to_users_table
# - 2025_10_24_104525_create_groups_table
# - 2025_10_24_104531_create_group_user_table
# - 2025_10_24_090729_create_chat_access_table
# - 2025_10_24_104536_create_tag_access_table
# - 2025_10_24_101245_add_transcription_fields_to_media_table
# - 2025_10_24_140505_add_transcribed_at_to_media_table
# - Plus core table migrations if fresh install

# Run migrations
php artisan migrate --force

# Verify migrations completed successfully
php artisan migrate:status
# All migrations should show "Ran"
```

**If migrations fail:**
```bash
# Restore from backup
mysql -u your_db_user -p your_database_name < backup_YYYYMMDD_HHMMSS.sql

# Check error logs
tail -100 storage/logs/laravel.log

# Contact support before retrying
```

---

## Step 8: Set Default Admin User

**IMPORTANT:** All existing users will default to 'chat_user' role. You need to promote at least one user to admin.

```bash
# Option 1: Using Tinker (Interactive)
php artisan tinker

# In tinker prompt:
$user = App\Models\User::where('email', 'your-admin-email@example.com')->first();
$user->role = 'admin';
$user->save();
echo "User {$user->name} is now an admin";
exit
```

**Option 2: Using SQL directly**
```bash
mysql -u your_db_user -p your_database_name -e "UPDATE users SET role = 'admin' WHERE email = 'your-admin-email@example.com';"
```

**Verify admin user:**
```bash
mysql -u your_db_user -p your_database_name -e "SELECT id, name, email, role FROM users WHERE role = 'admin';"
```

---

## Step 9: Configure Storage and Permissions

```bash
# Create storage link if not exists
php artisan storage:link

# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Or if using a different web server user (e.g., nginx):
# sudo chown -R nginx:nginx storage bootstrap/cache
```

---

## Step 10: Rebuild Search Index

The search index structure has changed. Rebuild it:

```bash
# Clear old search index
php artisan scout:flush "App\Models\Message"

# Rebuild search index with new structure
php artisan scout:import "App\Models\Message"

# This may take several minutes depending on message count
# You'll see: "Imported [App\Models\Message] models up to ID: XXXX"
```

---

## Step 11: Clear All Caches

```bash
# Clear configuration cache
php artisan config:clear
php artisan config:cache

# Clear route cache
php artisan route:clear
php artisan route:cache

# Clear view cache
php artisan view:clear

# Clear application cache
php artisan cache:clear

# Clear compiled classes
php artisan clear-compiled
php artisan optimize
```

---

## Step 12: Configure Queue Worker

The queue worker must process multiple queues for transcriptions to work.

### Option A: Using Supervisor (Recommended for Production)

Create supervisor config file:
```bash
sudo nano /etc/supervisor/conf.d/chatextract-worker.conf
```

Add this configuration:
```ini
[program:chatextract-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/application/artisan queue:work --queue=default,transcriptions,indexing --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/application/storage/logs/worker.log
stopwaitsecs=3600
```

**Update the paths** in the config above, then:
```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chatextract-worker:*

# Check status
sudo supervisorctl status
# Should show: chatextract-worker:chatextract-worker_00 RUNNING
```

### Option B: Using systemd

Create systemd service file:
```bash
sudo nano /etc/systemd/system/chatextract-worker.service
```

Add this configuration:
```ini
[Unit]
Description=ChatExtract Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/application
ExecStart=/usr/bin/php /path/to/your/application/artisan queue:work --queue=default,transcriptions,indexing --sleep=3 --tries=3
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Update the paths**, then:
```bash
# Reload systemd
sudo systemctl daemon-reload

# Start the service
sudo systemctl start chatextract-worker

# Enable on boot
sudo systemctl enable chatextract-worker

# Check status
sudo systemctl status chatextract-worker
```

### Option C: Using Cron (Not Recommended - for testing only)

```bash
# Edit crontab
crontab -e

# Add this line to run queue:work every minute
* * * * * cd /path/to/your/application && php artisan queue:work --queue=default,transcriptions,indexing --stop-when-empty >> /dev/null 2>&1
```

---

## Step 13: Test Critical Functionality

### Test 1: Can you log in?
```bash
# Try accessing your application in browser
# URL: https://your-domain.com/login
```

### Test 2: Database connection
```bash
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count() . '\n';"
# Should show user count without errors
```

### Test 3: Admin access
```bash
# Log in as admin user
# Navigate to: https://your-domain.com/admin/users
# You should see the admin users page
```

### Test 4: Search functionality
```bash
# Test search from command line
php artisan tinker --execute="
\$results = App\Models\Message::search('test')->get();
echo 'Search returned: ' . \$results->count() . ' results\n';
"
```

### Test 5: Queue worker
```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# Check queue jobs
php artisan queue:failed
# Should show 0 failed jobs (or investigate any failures)
```

---

## Step 14: Disable Maintenance Mode

```bash
# Bring application back online
php artisan up
```

---

## Step 15: Monitor Application

### Check logs for errors:
```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log

# Check for any errors after deployment
grep -i error storage/logs/laravel.log | tail -20
```

### Check queue processing:
```bash
# Monitor queue jobs
watch -n 5 'php artisan queue:failed'
```

### Monitor disk space:
```bash
df -h
# Ensure adequate space for media files and logs
```

---

## Post-Deployment Tasks

### 1. Create Additional Admin Users (if needed)
- Log in as admin
- Navigate to: Admin → Manage Users
- Create new users or promote existing users to admin role

### 2. Create User Groups (if needed)
- Navigate to: Admin → Manage Groups
- Create groups for organizing user access
- Add users to groups

### 3. Grant Chat Access (if needed for existing data)
- Navigate to: Admin → Chat Access
- Grant chat access to specific users or groups for chats they should see

### 4. Grant Tag Access (for View-Only users)
- Navigate to: Admin → Tag Access
- Grant tag access to view-only users for specific tags

### 5. Set Up Transcription Consent
- Navigate to: Transcription → Manage Consent
- Review participants and grant consent for those who have given permission
- **Note:** Only participants with consent will have their voice notes transcribed

---

## Rollback Procedure (If Needed)

If you encounter critical issues:

### 1. Enable Maintenance Mode
```bash
php artisan down
```

### 2. Restore Database
```bash
mysql -u your_db_user -p your_database_name < backup_YYYYMMDD_HHMMSS.sql
```

### 3. Restore Code
```bash
git reset --hard HEAD~1  # Revert to previous commit
# Or restore from your code backup
```

### 4. Restart Services
```bash
sudo supervisorctl restart chatextract-worker:*
# or
sudo systemctl restart chatextract-worker
```

### 5. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 6. Disable Maintenance Mode
```bash
php artisan up
```

---

## Common Issues and Solutions

### Issue 1: "Class 'FFmpeg' not found" or transcription jobs failing
**Solution:** Install FFmpeg (see Step 4)

### Issue 2: Search returns "participant_name column not found"
**Solution:** Rebuild search index (see Step 10)

### Issue 3: Queue jobs not processing
**Solution:**
- Check queue worker is running: `ps aux | grep queue:work`
- Ensure worker is listening to all queues: `--queue=default,transcriptions,indexing`
- Restart queue worker: `sudo supervisorctl restart chatextract-worker:*`

### Issue 4: "Storage link not found" - media files not loading
**Solution:**
```bash
php artisan storage:link
sudo chown -R www-data:www-data storage
```

### Issue 5: Users can't access admin panel
**Solution:**
- Verify user role: `SELECT email, role FROM users WHERE email = 'user@example.com';`
- Update to admin: `UPDATE users SET role = 'admin' WHERE email = 'user@example.com';`

### Issue 6: Migrations fail with foreign key errors
**Solution:**
- Check database constraints
- Ensure migrations run in correct order
- Review migration:status output
- Restore backup and investigate specific error

---

## Security Considerations

### 1. Verify Production Settings
```bash
# Check .env file
grep -E "APP_DEBUG|APP_ENV" .env
# Should show:
# APP_ENV=production
# APP_DEBUG=false
```

### 2. Protect Admin Routes
Admin routes are protected by middleware. Verify:
- Only users with `role = 'admin'` can access `/admin/*` routes
- Test by logging in as non-admin user

### 3. File Permissions
```bash
# Verify sensitive files are not world-readable
ls -la .env
# Should NOT show rwx for "others"

# Fix if needed
chmod 640 .env
```

### 4. HTTPS Enforcement
Ensure your web server forces HTTPS for all traffic.

---

## Performance Optimization (Optional)

### 1. Use Redis for Cache and Queue (Recommended)
```ini
# In .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2. Enable OPcache
```bash
# Edit php.ini
sudo nano /etc/php/8.2/fpm/php.ini

# Enable OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### 3. Database Optimization
```sql
-- Analyze tables after large data changes
ANALYZE TABLE messages, media, chats, users;

-- Check index usage
SHOW INDEX FROM messages;
```

---

## Monitoring and Logging

### Set Up Log Rotation
```bash
# Create logrotate config
sudo nano /etc/logrotate.d/chatextract
```

Add:
```
/path/to/your/application/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    create 0640 www-data www-data
}
```

### Monitor Disk Usage
```bash
# Check storage directory size
du -sh storage/app/
du -sh storage/logs/

# Set up alerts for disk usage if needed
```

---

## Support and Troubleshooting

### Useful Commands

**Check application status:**
```bash
php artisan about
```

**View recent logs:**
```bash
tail -100 storage/logs/laravel.log
```

**Check queue status:**
```bash
php artisan queue:failed
php artisan queue:work --once  # Process one job to test
```

**Database connectivity:**
```bash
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB Connected\n';"
```

**Scout status:**
```bash
php artisan scout:status
```

---

## Deployment Verification Checklist

After deployment, verify:

- [ ] Application loads without errors
- [ ] Users can log in
- [ ] Admin user can access `/admin/users`
- [ ] Search functionality works
- [ ] Gallery page loads
- [ ] Import page accessible
- [ ] Transcription dashboard shows
- [ ] Queue worker is processing jobs
- [ ] Media files (images/audio) display correctly
- [ ] No errors in `storage/logs/laravel.log`
- [ ] FFmpeg is installed and working
- [ ] Scout search index is built
- [ ] Permissions are correctly applied
- [ ] All new migrations are "Ran"

---

## Quick Reference - Database Migrations

These migrations will be applied:

1. **2025_10_24_104437_add_role_to_users_table** - Adds role column to users (admin/chat_user/view_only)
2. **2025_10_24_104525_create_groups_table** - Creates groups table
3. **2025_10_24_104531_create_group_user_table** - Group membership pivot table
4. **2025_10_24_090729_create_chat_access_table** - Polymorphic chat access control
5. **2025_10_24_104536_create_tag_access_table** - Polymorphic tag access control
6. **2025_10_24_101245_add_transcription_fields_to_media_table** - Adds transcription columns
7. **2025_10_24_140505_add_transcribed_at_to_media_table** - Adds transcribed_at timestamp

---

## Contact

If you encounter any issues during deployment:
1. Check the troubleshooting section above
2. Review `storage/logs/laravel.log` for errors
3. Ensure all pre-deployment checklist items are complete
4. Have your database backup ready for rollback if needed

---

**Last Updated:** October 24, 2025
**Version:** v2.0 - User Roles & Admin UI Release
