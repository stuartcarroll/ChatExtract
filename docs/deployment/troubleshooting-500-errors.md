# Preventing 500 Errors After Code Changes

This guide explains common causes of 500 errors after making code changes to ChatExtract and how to prevent them.

## Common Causes & Solutions

### 1. Cache Issues (Most Common)

**Problem:** Laravel caches configuration, routes, and views. After changes, stale caches cause errors.

**Solution - Run these commands after ANY code change:**

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Or use the combined optimization clear command
php artisan optimize:clear
```

**When to run:** After changing:
- Routes (`routes/web.php`)
- Configuration files (`config/`)
- Environment variables (`.env`)
- Controllers or middleware
- Blade templates

---

### 2. Database Schema Mismatches

**Problem:** Code expects columns/tables that don't exist in database.

**Solution:**

```bash
# Always run migrations after pulling changes
php artisan migrate

# If migrations fail, check migration status
php artisan migrate:status

# For development, you can reset and reseed
php artisan migrate:fresh --seed
```

**Prevention:**
- Always commit migrations with code changes
- Test migrations before committing
- Document schema changes in commit messages

---

### 3. Missing Dependencies

**Problem:** New PHP packages or NPM modules not installed.

**Solution:**

```bash
# After pulling changes, always run:
composer install --no-interaction
npm install

# If using specific versions:
composer update --no-interaction
npm update
```

**Prevention:**
- Always commit `composer.lock` and `package-lock.json`
- Document new dependencies in pull requests

---

### 4. Permission Issues

**Problem:** Laravel can't write to storage directories.

**Solution:**

```bash
# Fix permissions on storage and cache directories
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Or if running as your user
chown -R $USER:www-data storage bootstrap/cache
```

---

### 5. Environment Configuration

**Problem:** Missing or incorrect `.env` variables.

**Solution:**

```bash
# Copy example and update
cp .env.example .env

# Generate application key
php artisan key:generate

# Check required variables are set
cat .env | grep -E "APP_KEY|DB_|CACHE_|SESSION_|QUEUE_"
```

**Required Variables:**
- `APP_KEY` - Must be set (run `php artisan key:generate`)
- `DB_CONNECTION` - Database type (sqlite, mysql, etc.)
- `CACHE_STORE` - Cache driver
- `SESSION_DRIVER` - Session storage
- `QUEUE_CONNECTION` - Queue driver

---

### 6. Autoload Issues

**Problem:** New classes not recognized.

**Solution:**

```bash
# Regenerate autoload files
composer dump-autoload

# With optimization (production)
composer dump-autoload --optimize
```

**When to run:**
- After adding new classes
- After moving/renaming classes
- After updating namespaces

---

### 7. Queue Jobs Issues

**Problem:** Jobs failing with old code/structure.

**Solution:**

```bash
# Restart queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush

# For development, process jobs synchronously
# In .env: QUEUE_CONNECTION=sync
```

---

### 8. Scout/Search Index Issues

**Problem:** Search functionality failing after model changes.

**Solution:**

```bash
# Clear and rebuild search indexes
php artisan scout:flush "App\Models\Message"
php artisan scout:import "App\Models\Message"

# Check MeiliSearch is running
curl http://127.0.0.1:7700/health
```

---

## Comprehensive "After Changes" Checklist

**Every time you make code changes, run this:**

```bash
#!/bin/bash
# Save as: scripts/after-changes.sh

echo "🧹 Clearing caches..."
php artisan optimize:clear

echo "📦 Installing dependencies..."
composer install --no-interaction
npm install

echo "🗄️  Running migrations..."
php artisan migrate --force

echo "🔄 Restarting queue..."
php artisan queue:restart

echo "📝 Regenerating autoload..."
composer dump-autoload

echo "✅ Done! Application ready."
```

**Make it executable:**
```bash
chmod +x scripts/after-changes.sh
```

---

## Debugging 500 Errors

### 1. Enable Debug Mode (Development Only!)

```env
# In .env
APP_DEBUG=true
APP_ENV=local
```

⚠️ **NEVER** enable debug mode in production!

### 2. Check Laravel Logs

```bash
# View latest errors
tail -f storage/logs/laravel.log

# View last 100 lines
tail -100 storage/logs/laravel.log

# Search for specific errors
grep "ERROR" storage/logs/laravel.log | tail -20
```

### 3. Check Web Server Logs

```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP-FPM
tail -f /var/log/php8.2-fpm.log
```

### 4. Use Laravel Debugbar (Development)

```bash
composer require barryvdh/laravel-debugbar --dev
php artisan optimize:clear
```

---

## Prevention Best Practices

### 1. Version Control Workflow

```bash
# Before starting work
git pull origin main
composer install
npm install
php artisan migrate
php artisan optimize:clear

# After making changes
./scripts/after-changes.sh

# Before committing
php artisan test
php artisan pint  # Code formatting
```

### 2. Use Git Hooks

Create `.git/hooks/post-merge`:

```bash
#!/bin/bash
echo "🔄 Post-merge: Running setup..."
composer install --no-interaction
npm install
php artisan migrate
php artisan optimize:clear
echo "✅ Setup complete!"
```

Make it executable:
```bash
chmod +x .git/hooks/post-merge
```

### 3. Testing Before Committing

```bash
# Run tests
php artisan test

# Check for common issues
php artisan route:list  # Verify routes compile
php artisan config:show  # Verify config loads
php artisan view:clear  # Clear view cache

# Check code quality
composer run-script pint  # Or: ./vendor/bin/pint
```

---

## Production Deployment Checklist

When deploying to production:

```bash
# 1. Put in maintenance mode
php artisan down

# 2. Pull latest code
git pull origin main

# 3. Install dependencies (production mode)
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
php artisan queue:restart
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# 7. Bring back online
php artisan up
```

---

## Quick Reference Commands

```bash
# Nuclear option - clear everything
php artisan optimize:clear && composer dump-autoload && php artisan migrate

# Check application health
php artisan about

# View config values (helps debug .env issues)
php artisan config:show app
php artisan config:show database

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue status
php artisan queue:work --once --verbose
```

---

## Common Error Messages & Solutions

### "Class not found"
```bash
composer dump-autoload
php artisan optimize:clear
```

### "Route [name] not defined"
```bash
php artisan route:clear
php artisan route:cache
```

### "View [name] not found"
```bash
php artisan view:clear
```

### "SQLSTATE[HY000]: General error: 1 no such table"
```bash
php artisan migrate
# Or check database exists
```

### "Session store not set on request"
```bash
php artisan config:clear
php artisan cache:clear
```

### "The stream or file could not be opened"
```bash
chmod -R 775 storage bootstrap/cache
```

---

## Development Environment Setup

For a clean start:

```bash
# 1. Clone repository
git clone <repo-url>
cd ChatExtract

# 2. Install dependencies
composer install
npm install

# 3. Environment configuration
cp .env.example .env
php artisan key:generate

# 4. Database setup
touch database/database.sqlite
php artisan migrate

# 5. Storage setup
php artisan storage:link
chmod -R 775 storage bootstrap/cache

# 6. Build assets
npm run build

# 7. Start services
# Terminal 1: php artisan serve
# Terminal 2: php artisan queue:work
# Terminal 3: ./vendor/bin/sail meilisearch (if using Docker)

# 8. Optional: Seed test data
php artisan db:seed
```

---

## Still Getting 500 Errors?

1. **Check Laravel logs first:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Enable query logging (temporarily):**
   ```php
   // Add to a controller or route
   DB::enableQueryLog();
   // ... your code ...
   dd(DB::getQueryLog());
   ```

3. **Check for syntax errors:**
   ```bash
   php -l app/Http/Controllers/YourController.php
   ```

4. **Verify file permissions:**
   ```bash
   ls -la storage/logs/
   ls -la bootstrap/cache/
   ```

5. **Check PHP error logs:**
   ```bash
   php -i | grep error_log
   tail -50 /var/log/php_errors.log
   ```

---

## Preventive Maintenance

**Weekly:**
- Clear logs: `truncate -s 0 storage/logs/laravel.log`
- Update dependencies: `composer update && npm update`
- Run tests: `php artisan test`

**Monthly:**
- Check for security updates: `composer outdated --direct`
- Review failed jobs: `php artisan queue:failed`
- Optimize database: `php artisan db:analyze` (if available)

---

## Getting Help

If errors persist:

1. **Share the exact error message** from `storage/logs/laravel.log`
2. **Note what changed** before the error started
3. **List commands run** since last working state
4. **Check environment**: `php artisan about`

**Need immediate fix?**
```bash
# Rollback to last working commit
git log --oneline -5  # Find last working commit
git checkout <commit-hash>
php artisan optimize:clear
```

---

## Summary: The "Always Run" Commands

After making **ANY** changes:

```bash
php artisan optimize:clear
composer dump-autoload
php artisan migrate
```

This solves **90%** of 500 errors after code changes.
