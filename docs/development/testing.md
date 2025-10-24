# Testing Guide

## Running Tests

### Automated Tests

Run PHPUnit tests before deploying:

```bash
php artisan test
```

Run specific test file:

```bash
php artisan test tests/Feature/Controllers/ChatControllerTest.php
```

### Post-Deployment Verification

After deploying to production, run the verification script:

```bash
./scripts/verify-deployment.sh
```

This tests all critical routes and ensures no 500 errors occur.

## Pre-Deployment Checklist

Before deploying any changes:

- [ ] Run `php artisan test` locally
- [ ] Check for missing imports (`use` statements)
- [ ] Verify migrations run successfully
- [ ] Test as an authenticated user locally

After deploying:

- [ ] Run `./scripts/verify-deployment.sh`
- [ ] Check server logs: `ssh server "tail -100 storage/logs/laravel.log"`
- [ ] Test critical user workflows manually

## Writing Tests

### Controller Tests

Test that pages load without 500 errors:

```php
public function test_page_loads_for_authenticated_user(): void
{
    $user = User::factory()->create();
    $chat = Chat::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get("/chats/{$chat->id}");

    $response->assertStatus(200);
}
```

### Common Pitfalls to Test

1. **Missing imports** - Test that controllers can be instantiated
2. **Missing relationships** - Test that eager loading works
3. **Authorization** - Test that users can only access their own data
4. **Missing columns** - Test after migrations

## Testing Authenticated Routes

Use `actingAs()` to simulate authenticated requests:

```php
$user = User::factory()->create();
$response = $this->actingAs($user)->get('/chats');
```

## Database Factories

Factories exist for:
- User
- Chat

Create test data easily:

```php
$user = User::factory()->create();
$chat = Chat::factory()->create(['user_id' => $user->id]);
```

## Troubleshooting

### Tests Fail Locally

- Ensure test database is configured in `.env.testing`
- Run migrations: `php artisan migrate --env=testing`

### False Positives

- Test returns 302 but should test authenticated view
- Use `actingAs()` to authenticate the request
- Don't just check status codes, check actual content

## CI/CD Integration

Add to GitHub Actions (`.github/workflows/tests.yml`):

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```
