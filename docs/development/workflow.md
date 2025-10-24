# Development Workflow

## Overview

ChatExtract development uses a modern workflow with Claude Code (AI-assisted development), Git, and GitHub for version control and collaboration.

## Development Tools

### Primary Tools
- **Claude Code**: AI-powered development assistant
- **Git**: Version control
- **GitHub**: Code hosting and collaboration
- **VS Code**: Recommended editor (with Claude Code extension)
- **PHP 8.2+**: Runtime environment
- **Composer**: PHP dependency management
- **NPM**: Frontend asset management

### Recommended VS Code Extensions
- Claude Code
- PHP Intelephense
- Laravel Extension Pack
- Tailwind CSS IntelliSense
- GitLens

## Getting Started

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/ChatExtract.git
cd ChatExtract
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# Node dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_DATABASE=chatextract
# DB_USERNAME=your_user
# DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# (Optional) Seed test data
php artisan db:seed
```

### 5. Build Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

### 6. Start Development Server
```bash
# Copy the dev server template
cp scripts/start-dev.sh.example start-dev.sh

# Edit with your database credentials
nano start-dev.sh

# Make it executable
chmod +x start-dev.sh

# Start server
./start-dev.sh
```

The application will be available at `http://localhost:8000`

## Development with Claude Code

### Starting a Session

1. Open VS Code in the ChatExtract directory
2. Open Claude Code panel
3. **Important**: At the start of each session, provide context:
   ```
   Read CLAUDE.md to understand the project context
   ```

### Effective Prompts

**Good Examples:**
```
Add a feature to filter gallery by date range

Fix the bug where search doesn't work with special characters

Refactor the ImportController to use a service class

Add tests for the TagController export functionality
```

**Tips:**
- Be specific about what you want
- Mention file paths when relevant
- Ask for tests when implementing features
- Request documentation updates for significant changes

### Workflow Pattern

1. **Plan**: Describe the feature/fix to Claude
2. **Review**: Claude creates a plan - review and approve
3. **Implement**: Claude writes the code
4. **Test**: Test the changes locally
5. **Commit**: Commit with clear message
6. **Push**: Push to GitHub

### Claude Code Best Practices

- **Read CLAUDE.md**: Always start sessions by having Claude read CLAUDE.md
- **One feature at a time**: Don't mix unrelated changes
- **Test thoroughly**: Verify changes work before committing
- **Clear commits**: Use descriptive commit messages
- **Document changes**: Update docs for significant features

## Git Workflow

### Branch Strategy

**Main Branch**: `master`
- Production-ready code
- Deploy from this branch
- Protect with branch rules

**Feature Branches**: Optional for complex features
```bash
git checkout -b feature/bulk-export
# Work on feature
git commit -am "Add bulk export feature"
git push origin feature/bulk-export
# Create PR on GitHub
```

### Commit Message Format

```
[Type] Brief description (50 chars max)

Detailed explanation of changes (if needed):
- What was changed
- Why it was changed
- Any breaking changes

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

**Types:**
- `Add`: New feature
- `Fix`: Bug fix
- `Update`: Enhancement to existing feature
- `Refactor`: Code improvement without functionality change
- `Docs`: Documentation only
- `Test`: Test additions/changes
- `Chore`: Maintenance tasks

**Examples:**
```bash
git commit -m "Add bulk export feature for tags

- Implement tag export to ZIP
- Include messages and media
- Add manifest file with export details"
```

### Before Committing

1. **Check status**: `git status`
2. **Review changes**: `git diff`
3. **Test locally**: Run the app and test changes
4. **Stage files**: `git add <files>`
5. **Commit**: `git commit -m "message"`
6. **Push**: `git push origin master`

### Common Git Commands

```bash
# Check status
git status

# View changes
git diff

# Stage specific files
git add app/Http/Controllers/TagController.php

# Stage all changes
git add .

# Commit with message
git commit -m "Add export feature"

# Push to GitHub
git push origin master

# Pull latest changes
git pull origin master

# View commit history
git log --oneline -10

# Undo unstaged changes
git restore <file>

# Undo staged changes
git restore --staged <file>
```

## Testing Workflow

### Manual Testing
1. Test the specific feature/fix
2. Test related functionality
3. Test edge cases
4. Test different user roles (if applicable)

### Automated Testing
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Controllers/ChatControllerTest.php

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Test Writing
When implementing features, ask Claude to:
```
Add tests for the tag export functionality
```

Tests should cover:
- Happy path
- Error cases
- Authorization checks
- Edge cases

## Debugging

### Laravel Telescope (Optional)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at: `http://localhost:8000/telescope`

### Debug Strategies

**1. Use dd() and dump()**
```php
dd($variable); // Dump and die
dump($variable); // Dump and continue
```

**2. Check logs**
```bash
tail -f storage/logs/laravel.log
```

**3. Tinker for database queries**
```bash
php artisan tinker
> App\Models\Message::count();
> App\Models\User::first();
```

**4. Enable query logging**
```php
// In a controller or route
DB::enableQueryLog();
// ... run queries ...
dd(DB::getQueryLog());
```

## Code Style

### PHP (Laravel Conventions)
- PSR-12 coding standard
- Laravel naming conventions
- DocBlocks for public methods
- Type hints for parameters and returns

### Blade Templates
- Indentation: 4 spaces
- Component names: kebab-case
- Props: camelCase

### JavaScript
- ES6+ syntax
- Alpine.js conventions
- Minimal inline JavaScript

### CSS/Tailwind
- Tailwind utility classes
- Custom classes in `resources/css/app.css`
- Responsive design (mobile-first)

## Deployment Workflow

### To Production

1. **Test locally thoroughly**
2. **Commit and push to GitHub**
   ```bash
   git add .
   git commit -m "Add feature X"
   git push origin master
   ```

3. **SSH to production server**
   ```bash
   ssh ploi@usvps.stuc.dev
   cd /home/ploi/usvps.stuc.dev
   ```

4. **Backup database** (important!)
   ```bash
   mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
   ```

5. **Pull changes**
   ```bash
   php artisan down
   git pull origin master
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:clear
   php artisan up
   ```

6. **Verify deployment**
   ```bash
   # Check for errors
   tail -f storage/logs/laravel.log

   # Verify features work
   # Visit the application in browser
   ```

## Issue Tracking

### Using GitHub Issues

**Creating Issues:**
- Use descriptive titles
- Provide reproduction steps for bugs
- Include expected vs actual behavior
- Add screenshots if helpful
- Label appropriately (bug, enhancement, documentation)

**Workflow:**
1. Create issue on GitHub
2. Assign to yourself (if working on it)
3. Create branch (for complex features)
4. Reference issue in commits: `Fix #123: description`
5. Close when fixed and deployed

### Labels
- `bug`: Something isn't working
- `enhancement`: New feature request
- `documentation`: Documentation improvements
- `question`: Questions or discussions
- `help wanted`: Extra attention needed

## Collaboration

### Pull Request Process (Optional)
For larger features or when collaborating:

1. Create feature branch
2. Make changes
3. Push branch to GitHub
4. Create pull request
5. Review (self or team)
6. Merge to master
7. Deploy

### Code Review Checklist
- [ ] Code follows style guidelines
- [ ] Tests pass
- [ ] Documentation updated
- [ ] No secrets or credentials
- [ ] Performance considerations
- [ ] Security considerations

## Common Tasks

### Adding a New Feature
```bash
# 1. Plan with Claude
"Add feature to filter messages by date range"

# 2. Claude implements
# - Creates controller methods
# - Updates routes
# - Creates views
# - Adds tests

# 3. Test locally
./start-dev.sh
# Visit http://localhost:8000 and test

# 4. Commit and push
git add .
git commit -m "Add date range filter to messages"
git push origin master
```

### Fixing a Bug
```bash
# 1. Reproduce bug locally
# 2. Debug with dd(), logs, tinker
# 3. Ask Claude for fix
"Fix bug where search fails with apostrophes"

# 4. Test fix
# 5. Commit
git commit -m "Fix search escaping for special characters"
```

### Updating Dependencies
```bash
# PHP dependencies
composer update

# Node dependencies
npm update

# Test thoroughly after updates
php artisan test
```

## Best Practices Summary

1. ✅ Always start Claude sessions by reading CLAUDE.md
2. ✅ Test changes before committing
3. ✅ Write clear commit messages
4. ✅ Keep commits focused (one feature/fix per commit)
5. ✅ Update documentation for new features
6. ✅ Backup database before production deployments
7. ✅ Use GitHub Issues for tracking
8. ✅ Code review your own changes before pushing
9. ✅ Keep secrets out of version control
10. ✅ Monitor logs after deployment
