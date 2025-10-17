# Server Setup Commands

Run these commands on your VPS to complete the deployment.

## Step 1: Connect to Server

```bash
ssh ploi@usvps.stuc.dev
cd /home/ploi/chat.stuc.dev
```

## Step 2: Set Up Environment File

```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit the .env file with your settings
nano .env
```

### Required .env Settings:

```env
APP_NAME="WhatsApp Archive"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://chat.stuc.dev

DB_CONNECTION=sqlite

MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-master-key-here

QUEUE_CONNECTION=database

# Optional AI Services (add these later if needed)
AZURE_OPENAI_ENABLED=false
# AZURE_OPENAI_ENDPOINT=
# AZURE_OPENAI_API_KEY=
# AZURE_OPENAI_DEPLOYMENT_NAME=

CLAUDE_API_ENABLED=false
# CLAUDE_API_KEY=
# CLAUDE_API_MODEL=claude-3-sonnet-20240229
```

Save and exit (Ctrl+X, Y, Enter)

## Step 3: Create SQLite Database

```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
```

## Step 4: Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
mkdir -p storage/app/media
```

## Step 5: Run Migrations

```bash
php artisan migrate --force
```

## Step 6: Create Storage Link

```bash
php artisan storage:link
```

## Step 7: Install Node Dependencies and Build Assets

```bash
npm install
npm run build
```

## Step 8: Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 9: Install MeiliSearch

Check if MeiliSearch is already installed:

```bash
which meilisearch
```

If not installed, install it:

```bash
# Download and install MeiliSearch
curl -L https://install.meilisearch.com | sh
sudo mv ./meilisearch /usr/local/bin/

# Generate a master key
MEILISEARCH_KEY=$(openssl rand -base64 32)
echo "Your MeiliSearch Master Key: $MEILISEARCH_KEY"
echo "Save this key and add it to your .env file as MEILISEARCH_KEY"
```

Create MeiliSearch systemd service:

```bash
sudo nano /etc/systemd/system/meilisearch.service
```

Paste this content (replace YOUR_MASTER_KEY with the key from above):

```ini
[Unit]
Description=MeiliSearch
After=network.target

[Service]
Type=simple
User=ploi
ExecStart=/usr/local/bin/meilisearch --http-addr 127.0.0.1:7700 --env production --master-key YOUR_MASTER_KEY
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Save and start MeiliSearch:

```bash
sudo systemctl daemon-reload
sudo systemctl enable meilisearch
sudo systemctl start meilisearch
sudo systemctl status meilisearch
```

## Step 10: Update .env with MeiliSearch Key

```bash
nano .env
```

Update the MEILISEARCH_KEY with your generated key, then:

```bash
php artisan config:clear
```

## Step 11: Index Models in MeiliSearch

```bash
php artisan scout:import "App\Models\Message"
```

## Step 12: Set Up Queue Worker

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/chat-worker.conf
```

Paste this content:

```ini
[program:chat-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ploi/chat.stuc.dev/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ploi
numprocs=2
redirect_stderr=true
stdout_logfile=/home/ploi/chat.stuc.dev/storage/logs/worker.log
stopwaitsecs=3600
```

Save and start the worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chat-worker:*
sudo supervisorctl status
```

## Step 13: Create Admin User

```bash
php artisan tinker
```

Then paste this code in tinker:

```php
$password = Str::random(16);
$user = \App\Models\User::create([
    'name' => 'stu',
    'email' => 'stuart@stuartc.net',
    'password' => Hash::make($password)
]);
echo "Admin user created!\n";
echo "Email: stuart@stuartc.net\n";
echo "Password: {$password}\n";
echo "Please save this password and change it after first login.\n";
exit
```

**IMPORTANT:** Copy and save the password displayed!

## Step 14: Configure PHP for Large File Uploads

Edit PHP ini file:

```bash
# Find your PHP ini file location
php --ini

# Edit it (path may vary, typically /etc/php/8.x/fpm/php.ini)
sudo nano /etc/php/8.3/fpm/php.ini
```

Update these values:

```ini
upload_max_filesize = 10240M
post_max_size = 10240M
max_execution_time = 3600
memory_limit = 512M
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

## Step 15: Verify Nginx Configuration

Check the Nginx config for chat.stuc.dev:

```bash
sudo nano /etc/nginx/sites-available/chat.stuc.dev
```

Ensure it has these directives for large uploads:

```nginx
client_max_body_size 10240M;
client_body_timeout 3600s;
```

If you made changes, restart Nginx:

```bash
sudo nginx -t
sudo systemctl restart nginx
```

## Step 16: Final Checks

```bash
# Check application status
php artisan about

# Check if site is accessible
curl -I https://chat.stuc.dev

# Check logs for any errors
tail -f storage/logs/laravel.log
```

## Step 17: Test the Application

1. Visit https://chat.stuc.dev
2. You should see the application welcome page
3. Click "Login" or "Register"
4. Login with your admin credentials from Step 13
5. You should be redirected to the dashboard

## Troubleshooting

### If you get a 500 error:

```bash
# Check Laravel logs
tail -100 storage/logs/laravel.log

# Check Nginx logs
sudo tail -100 /var/log/nginx/error.log

# Ensure permissions are correct
chmod -R 775 storage bootstrap/cache
chown -R ploi:ploi /home/ploi/chat.stuc.dev
```

### If MeiliSearch isn't working:

```bash
# Check MeiliSearch status
sudo systemctl status meilisearch

# Check MeiliSearch logs
sudo journalctl -u meilisearch -n 100

# Test MeiliSearch connection
curl http://127.0.0.1:7700/health
```

### If queue jobs aren't running:

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart chat-worker:*

# Check worker logs
tail -f storage/logs/worker.log
```

## Next Steps After Deployment

1. **Change Admin Password**: Login and go to Profile to change your password
2. **Import Test Chat**: Try importing a WhatsApp chat export
3. **Configure AI Services** (Optional):
   - Get Azure OpenAI credentials and add to .env
   - Get Claude API key and add to .env
   - Update .env: `AZURE_OPENAI_ENABLED=true` or `CLAUDE_API_ENABLED=true`
   - Run: `php artisan config:clear`

## Maintenance Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check queue status
php artisan queue:monitor

# Restart queue workers after code changes
sudo supervisorctl restart chat-worker:*
```
