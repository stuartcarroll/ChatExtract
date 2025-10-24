# Quick Deployment Checklist

**Use this checklist when deploying. See DEPLOYMENT-GUIDE.md for detailed instructions.**

---

## Pre-Deployment
- [ ] Backup production database
- [ ] Backup production code
- [ ] Notify users of maintenance

## System Prep
- [ ] `php artisan down` (enable maintenance mode)
- [ ] `git pull origin master`
- [ ] `sudo apt-get install -y ffmpeg` (if not installed)
- [ ] `composer install --no-dev --optimize-autoloader`

## Configuration
- [ ] Update `.env` with:
  - `SCOUT_DRIVER=database`
  - `OPENAI_API_KEY=your-key`
  - `APP_DEBUG=false`
  - `APP_ENV=production`

## Database
- [ ] `php artisan migrate:status` (preview)
- [ ] `php artisan migrate --force` (run migrations)
- [ ] Promote admin user:
  ```bash
  mysql -u user -p db -e "UPDATE users SET role='admin' WHERE email='admin@example.com';"
  ```

## Search & Cache
- [ ] `php artisan storage:link`
- [ ] `php artisan scout:flush "App\Models\Message"`
- [ ] `php artisan scout:import "App\Models\Message"`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:clear`
- [ ] `php artisan cache:clear`

## Queue Worker Setup
Choose ONE method:

**Supervisor (Recommended):**
- [ ] Create `/etc/supervisor/conf.d/chatextract-worker.conf`
- [ ] Update paths in config
- [ ] `sudo supervisorctl reread && sudo supervisorctl update`
- [ ] `sudo supervisorctl start chatextract-worker:*`

**OR Systemd:**
- [ ] Create `/etc/systemd/system/chatextract-worker.service`
- [ ] `sudo systemctl daemon-reload`
- [ ] `sudo systemctl start chatextract-worker`
- [ ] `sudo systemctl enable chatextract-worker`

## Testing
- [ ] Can log in?
- [ ] Admin can access `/admin/users`?
- [ ] Search works?
- [ ] Gallery loads?
- [ ] No errors in logs?
- [ ] Queue worker running: `ps aux | grep queue:work`

## Go Live
- [ ] `php artisan up` (disable maintenance mode)
- [ ] Monitor logs: `tail -f storage/logs/laravel.log`

---

## Critical Settings in .env

```ini
APP_ENV=production
APP_DEBUG=false
SCOUT_DRIVER=database
QUEUE_CONNECTION=database
OPENAI_API_KEY=sk-...
```

## Queue Worker Command

```bash
php artisan queue:work --queue=default,transcriptions,indexing --sleep=3 --tries=3
```

## Rollback (Emergency)

```bash
php artisan down
mysql -u user -p db < backup.sql
git reset --hard HEAD~1
php artisan config:clear && php artisan cache:clear
sudo supervisorctl restart chatextract-worker:*
php artisan up
```

---

**See DEPLOYMENT-GUIDE.md for complete instructions and troubleshooting.**
