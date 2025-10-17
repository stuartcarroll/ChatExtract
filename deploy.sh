#!/bin/bash

# WhatsApp Archive Deployment Script
# For Ubuntu 24.04 with Ploi-managed server
# Site directory: /home/ploi/chat.stuc.dev

set -e  # Exit on error

echo "=========================================="
echo "WhatsApp Archive - Deployment Script"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

# Check if running as ploi user
if [ "$USER" != "ploi" ]; then
    print_error "This script must be run as the ploi user"
    exit 1
fi

# Site directory
SITE_DIR="/home/ploi/chat.stuc.dev"
cd "$SITE_DIR"

print_info "Current directory: $(pwd)"
echo ""

# Step 1: Check PHP version
print_info "Checking PHP version..."
PHP_VERSION=$(php -v | head -n 1)
echo "$PHP_VERSION"
print_success "PHP is installed"
echo ""

# Step 2: Check Composer
print_info "Checking Composer..."
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed"
    exit 1
fi
COMPOSER_VERSION=$(composer --version)
echo "$COMPOSER_VERSION"
print_success "Composer is installed"
echo ""

# Step 3: Check Node.js and npm
print_info "Checking Node.js and npm..."
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed"
    exit 1
fi
NODE_VERSION=$(node -v)
NPM_VERSION=$(npm -v)
echo "Node.js: $NODE_VERSION"
echo "npm: $NPM_VERSION"
print_success "Node.js and npm are installed"
echo ""

# Step 4: Install dependencies
print_info "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
print_success "Composer dependencies installed"
echo ""

print_info "Installing npm dependencies..."
npm install
print_success "npm dependencies installed"
echo ""

# Step 5: Build frontend assets
print_info "Building frontend assets..."
npm run build
print_success "Frontend assets built"
echo ""

# Step 6: Set up environment file
print_info "Setting up environment file..."
if [ ! -f .env ]; then
    cp .env.example .env
    print_success ".env file created from .env.example"

    # Generate application key
    php artisan key:generate --force
    print_success "Application key generated"

    # Update environment variables
    print_info "Please update the following in your .env file:"
    echo "  - APP_URL=https://chat.stuc.dev"
    echo "  - DB_CONNECTION=sqlite"
    echo "  - MEILISEARCH_HOST, MEILISEARCH_KEY"
    echo "  - AZURE_OPENAI_* (optional)"
    echo "  - CLAUDE_API_* (optional)"
    echo ""
else
    print_success ".env file already exists"
fi
echo ""

# Step 7: Create SQLite database
print_info "Setting up SQLite database..."
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    print_success "SQLite database created"
else
    print_success "SQLite database already exists"
fi
echo ""

# Step 8: Set permissions
print_info "Setting permissions..."
chmod -R 775 storage bootstrap/cache
print_success "Permissions set"
echo ""

# Step 9: Create storage directories
print_info "Creating storage directories..."
mkdir -p storage/app/media
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
print_success "Storage directories created"
echo ""

# Step 10: Create symbolic link for storage
print_info "Creating storage symbolic link..."
php artisan storage:link
print_success "Storage link created"
echo ""

# Step 11: Run migrations
print_info "Running database migrations..."
php artisan migrate --force
print_success "Migrations completed"
echo ""

# Step 12: Check if MeiliSearch is installed
print_info "Checking MeiliSearch installation..."
if ! command -v meilisearch &> /dev/null; then
    print_error "MeiliSearch is not installed"
    echo ""
    echo "To install MeiliSearch, run:"
    echo "  curl -L https://install.meilisearch.com | sh"
    echo "  sudo mv ./meilisearch /usr/local/bin/"
    echo ""
    echo "Then create a systemd service at /etc/systemd/system/meilisearch.service:"
    echo ""
    echo "[Unit]"
    echo "Description=MeiliSearch"
    echo "After=network.target"
    echo ""
    echo "[Service]"
    echo "Type=simple"
    echo "User=ploi"
    echo "ExecStart=/usr/local/bin/meilisearch --http-addr 127.0.0.1:7700 --env production --master-key YOUR_MASTER_KEY_HERE"
    echo "Restart=on-failure"
    echo ""
    echo "[Install]"
    echo "WantedBy=multi-user.target"
    echo ""
else
    MEILISEARCH_VERSION=$(meilisearch --version)
    echo "$MEILISEARCH_VERSION"
    print_success "MeiliSearch is installed"
fi
echo ""

# Step 13: Clear and optimize caches
print_info "Clearing and optimizing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Caches optimized"
echo ""

# Step 14: Index models in MeiliSearch
print_info "Indexing models in MeiliSearch..."
if command -v meilisearch &> /dev/null && systemctl is-active --quiet meilisearch; then
    php artisan scout:import "App\Models\Message"
    print_success "Models indexed in MeiliSearch"
else
    print_error "MeiliSearch service is not running. Skipping indexing."
    echo "Start MeiliSearch and run: php artisan scout:import \"App\Models\Message\""
fi
echo ""

# Step 15: Check queue configuration
print_info "Checking queue configuration..."
echo "Make sure you have a queue worker running:"
echo "  php artisan queue:work --daemon"
echo ""
echo "Or set up a supervisor configuration at /etc/supervisor/conf.d/chat-worker.conf:"
echo ""
echo "[program:chat-worker]"
echo "process_name=%(program_name)s_%(process_num)02d"
echo "command=php $SITE_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600"
echo "autostart=true"
echo "autorestart=true"
echo "stopasgroup=true"
echo "killasgroup=true"
echo "user=ploi"
echo "numprocs=2"
echo "redirect_stderr=true"
echo "stdout_logfile=$SITE_DIR/storage/logs/worker.log"
echo "stopwaitsecs=3600"
echo ""

# Step 16: Create admin user
print_info "Creating admin user..."
php artisan tinker --execute="
\$user = \App\Models\User::firstOrCreate(
    ['email' => 'stuart@stuartc.net'],
    [
        'name' => 'stu',
        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16))
    ]
);
\$password = \Illuminate\Support\Str::random(16);
\$user->password = \Illuminate\Support\Facades\Hash::make(\$password);
\$user->save();
echo \"Admin user created!\n\";
echo \"Email: stuart@stuartc.net\n\";
echo \"Temporary Password: {\$password}\n\";
echo \"Please change this password after first login.\n\";
" 2>/dev/null || print_error "Failed to create admin user. You can create it manually later."
echo ""

# Step 17: Final checks
print_info "Running final checks..."
php artisan about
echo ""

print_success "=========================================="
print_success "Deployment completed successfully!"
print_success "=========================================="
echo ""
echo "Next steps:"
echo "1. Update your .env file with proper configuration"
echo "2. Make sure MeiliSearch is running"
echo "3. Set up queue worker (supervisor recommended)"
echo "4. Visit https://chat.stuc.dev to access the application"
echo "5. Login with the admin credentials shown above"
echo ""
echo "For large file uploads (up to 10GB), ensure PHP is configured with:"
echo "  upload_max_filesize = 10240M"
echo "  post_max_size = 10240M"
echo "  max_execution_time = 3600"
echo "  memory_limit = 512M"
echo ""
