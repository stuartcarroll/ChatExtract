# WhatsApp Archive System - Test Plan

This document outlines the testing procedures to verify the WhatsApp Archive system is working correctly.

## Prerequisites

- Server setup completed (see SERVER_SETUP_COMMANDS.md)
- Admin user created
- Application accessible at https://chat.stuc.dev
- WhatsApp export file ready for testing

---

## Test Suite

### 1. Authentication Tests

#### 1.1 Login Test
- [ ] Navigate to https://chat.stuc.dev
- [ ] Click "Login"
- [ ] Enter admin credentials (stuart@stuartc.net + your password)
- [ ] Verify successful login and redirect to dashboard
- [ ] **Expected**: User is logged in and sees dashboard/chat list

#### 1.2 Logout Test
- [ ] Click on user menu (top right)
- [ ] Click "Logout"
- [ ] Verify redirect to welcome/login page
- [ ] **Expected**: User is logged out successfully

#### 1.3 Registration Test
- [ ] Click "Register"
- [ ] Fill in registration form with new user details
- [ ] Submit form
- [ ] Verify new user can login
- [ ] **Expected**: New user account created successfully

---

### 2. WhatsApp Import Tests

#### 2.1 Basic Import Test
- [ ] Login to the application
- [ ] Navigate to "Import Chat" or click "New Chat"
- [ ] Upload a WhatsApp .txt export file
- [ ] Enter chat name and description
- [ ] Click "Import"
- [ ] **Expected**:
  - File uploads successfully
  - Progress indicator shows
  - Redirect to chat view after import
  - All messages are displayed
  - Message count is correct

#### 2.2 Import Statistics Verification
After import, verify:
- [ ] Total message count is correct
- [ ] Participant list is accurate
- [ ] Date range (first message - last message) is correct
- [ ] **Expected**: All statistics match the source WhatsApp export

#### 2.3 Deduplication Test
- [ ] Import the same WhatsApp chat file again
- [ ] **Expected**:
  - System detects duplicates
  - No duplicate messages are created
  - Message count remains the same

#### 2.4 Multi-line Message Test
Verify that multi-line messages are parsed correctly:
- [ ] Find a multi-line message in the chat
- [ ] Verify all lines are displayed together as one message
- [ ] **Expected**: Multi-line messages are complete and properly formatted

#### 2.5 System Message Test
Verify system messages are detected:
- [ ] Find system messages (e.g., "You created this group", "X left")
- [ ] Verify they are marked as system messages
- [ ] **Expected**: System messages are identifiable and properly displayed

---

### 3. Media Handling Tests

#### 3.1 Media Detection Test
- [ ] Import a chat with media attachments
- [ ] Verify messages with media show media indicators
- [ ] **Expected**: Media types are detected (image, video, audio, document)

#### 3.2 Media Upload Test (if uploading media files)
- [ ] Try uploading actual media files if the import supports it
- [ ] Verify media files are stored correctly
- [ ] **Expected**: Media files are accessible and properly linked to messages

#### 3.3 Media Display Test
- [ ] Click on a message with image attachment
- [ ] Verify image displays correctly
- [ ] Try video and audio files
- [ ] **Expected**: All media types display/play correctly

---

### 4. Participant Tests

#### 4.1 Participant List Test
- [ ] View chat details/participants
- [ ] Verify all participants from WhatsApp export are listed
- [ ] **Expected**: Complete and accurate participant list

#### 4.2 Filter by Participant Test
- [ ] Use participant filter dropdown
- [ ] Select a specific participant
- [ ] Verify only that participant's messages are shown
- [ ] **Expected**: Filtered messages belong only to selected participant

#### 4.3 Participant Message Count Test
- [ ] Check message count per participant
- [ ] Verify counts are accurate
- [ ] **Expected**: Each participant's message count is correct

---

### 5. Search Tests

#### 5.1 Basic Search Test
- [ ] Navigate to Search page
- [ ] Enter a keyword that exists in your chat
- [ ] Submit search
- [ ] **Expected**:
  - Relevant messages are returned
  - Search highlights the keyword
  - Results link back to original chat

#### 5.2 Advanced Search - Date Range Test
- [ ] Use advanced search with date range filter
- [ ] Select start and end dates
- [ ] Submit search
- [ ] **Expected**: Only messages within date range are returned

#### 5.3 Advanced Search - Chat Filter Test
- [ ] Create/import multiple chats
- [ ] Use advanced search with specific chat selected
- [ ] **Expected**: Results only from selected chat

#### 5.4 Advanced Search - Tag Filter Test
- [ ] Tag some messages first
- [ ] Use advanced search with tag filter
- [ ] **Expected**: Only tagged messages are returned

#### 5.5 Search - No Results Test
- [ ] Search for a term that doesn't exist
- [ ] **Expected**: "No results found" message displayed

---

### 6. Story Detection Tests

#### 6.1 Pattern-Based Detection Test
- [ ] Import a chat with story-like messages
- [ ] Wait for background processing to complete (check queue worker)
- [ ] View messages marked as stories
- [ ] **Expected**:
  - Messages with past tense narratives are marked as stories
  - Confidence score is displayed
  - Story badge is visible

#### 6.2 Filter by Stories Test
- [ ] Use "Stories Only" filter in chat view
- [ ] **Expected**: Only messages marked as stories are shown

#### 6.3 Story Confidence Test
- [ ] Check confidence scores on detected stories
- [ ] Verify scores are between 0 and 1
- [ ] **Expected**: Higher confidence for clear story narratives

---

### 7. Tagging Tests

#### 7.1 Create Tag Test
- [ ] Create a new tag (if UI allows)
- [ ] **Expected**: Tag is created and available for use

#### 7.2 Tag Message Test
- [ ] Select a message
- [ ] Apply a tag to it
- [ ] **Expected**:
  - Tag is applied successfully
  - Tag is visible on the message

#### 7.3 Tag Filter Test
- [ ] Tag multiple messages
- [ ] Use tag filter to show only tagged messages
- [ ] **Expected**: Only messages with selected tag are shown

#### 7.4 Multiple Tags Test
- [ ] Apply multiple tags to a single message
- [ ] **Expected**: All tags are displayed on the message

---

### 8. Conversation Threading Tests

#### 8.1 Message Order Test
- [ ] View a chat
- [ ] Verify messages are in chronological order
- [ ] **Expected**: Messages ordered by timestamp (oldest to newest or newest to oldest)

#### 8.2 Date Grouping Test
- [ ] Check if messages are grouped by date
- [ ] **Expected**: Clear date separators between different days

#### 8.3 Pagination Test
- [ ] For chats with many messages, test pagination
- [ ] Navigate to different pages
- [ ] **Expected**: Pagination works correctly, messages load properly

---

### 9. Access Control & Permissions Tests

#### 9.1 Own Chat Access Test
- [ ] Create/import a chat as admin user
- [ ] Verify you can view and edit it
- [ ] **Expected**: Full access to own chats

#### 9.2 Other User's Chat Test (if multi-user)
- [ ] Login as different user
- [ ] Try to access another user's chat directly (via URL manipulation)
- [ ] **Expected**: Access denied or 403/404 error

#### 9.3 Chat Deletion Test
- [ ] Delete a chat you own
- [ ] Confirm deletion
- [ ] **Expected**:
  - Chat is deleted
  - Associated messages are deleted
  - Media files are cleaned up

#### 9.4 Unauthorized Action Test
- [ ] Try to edit/delete a chat you don't own
- [ ] **Expected**: Permission denied

---

### 10. Performance Tests

#### 10.1 Large Import Test
- [ ] Import a large WhatsApp chat (1000+ messages)
- [ ] Monitor import time
- [ ] **Expected**:
  - Import completes without timeout
  - No memory errors
  - Reasonable performance (< 2 minutes for 1000 messages)

#### 10.2 Search Performance Test
- [ ] Search across multiple large chats
- [ ] Monitor response time
- [ ] **Expected**: Results returned within 2 seconds

#### 10.3 Large Media Upload Test
- [ ] Upload a large file (up to 10GB if possible)
- [ ] **Expected**:
  - Upload progresses without timeout
  - File is stored correctly

---

### 11. Queue Worker Tests

#### 11.1 Queue Processing Test
- [ ] Check supervisor status: `sudo supervisorctl status`
- [ ] Verify queue workers are running
- [ ] Import a chat and check worker logs
- [ ] **Expected**: Jobs are processed automatically

#### 11.2 Failed Job Test
- [ ] Check for failed jobs: `php artisan queue:failed`
- [ ] **Expected**: No failed jobs (or minimal failures with retry)

---

### 12. Integration Tests

#### 12.1 MeiliSearch Integration Test
- [ ] Check MeiliSearch status: `curl http://127.0.0.1:7700/health`
- [ ] Perform a search
- [ ] **Expected**: Search uses MeiliSearch and returns fast results

#### 12.2 AI Integration Test (if configured)
- [ ] Configure Azure OpenAI or Claude API in .env
- [ ] Import a chat
- [ ] Check if AI-powered story detection works
- [ ] **Expected**: Enhanced story detection with AI confidence scores

---

### 13. Error Handling Tests

#### 13.1 Invalid File Upload Test
- [ ] Try to upload a non-WhatsApp file (e.g., random .txt)
- [ ] **Expected**: Error message displayed, no crash

#### 13.2 Oversized File Test
- [ ] Try to upload a file larger than configured max
- [ ] **Expected**: Clear error message about file size

#### 13.3 Missing Environment Variables Test
- [ ] Temporarily remove a required env var
- [ ] Try to use that feature
- [ ] **Expected**: Graceful error handling

#### 13.4 MeiliSearch Down Test
- [ ] Stop MeiliSearch: `sudo systemctl stop meilisearch`
- [ ] Try to search
- [ ] **Expected**: Appropriate error message
- [ ] Restart: `sudo systemctl start meilisearch`

---

### 14. UI/UX Tests

#### 14.1 Responsive Design Test
- [ ] View application on mobile device or resize browser
- [ ] **Expected**: UI adapts to different screen sizes

#### 14.2 Navigation Test
- [ ] Test all navigation links
- [ ] Verify breadcrumbs work
- [ ] **Expected**: Smooth navigation, no broken links

#### 14.3 Form Validation Test
- [ ] Submit forms with invalid data
- [ ] **Expected**: Clear validation error messages

---

### 15. Security Tests

#### 15.1 SQL Injection Test
- [ ] Try SQL injection in search box: `'; DROP TABLE messages; --`
- [ ] **Expected**: Query is safely escaped, no database damage

#### 15.2 XSS Test
- [ ] Import chat with potential XSS payload: `<script>alert('XSS')</script>`
- [ ] **Expected**: Script tags are escaped/sanitized

#### 15.3 CSRF Protection Test
- [ ] Check forms have CSRF tokens
- [ ] **Expected**: All POST forms are CSRF protected

#### 15.4 HTTPS Test
- [ ] Visit http://chat.stuc.dev
- [ ] **Expected**: Redirect to https://

---

## Test Results Template

Use this template to record your test results:

```
Test: [Test Name]
Date: [Date]
Tester: [Your Name]
Result: ✅ PASS / ❌ FAIL
Notes: [Any observations or issues]
```

---

## Critical Issues to Report

If any of these tests fail, they should be addressed immediately:

1. Authentication failures (can't login)
2. Import failures (chat won't import)
3. Database errors
4. Permission bypasses (unauthorized access)
5. Data loss (messages disappear)
6. Security vulnerabilities

---

## Post-Testing Checklist

After completing all tests:

- [ ] All critical features work
- [ ] No security vulnerabilities found
- [ ] Performance is acceptable
- [ ] Error handling is graceful
- [ ] UI is user-friendly
- [ ] Documentation is accurate
- [ ] Admin password has been changed
- [ ] Backup strategy is in place

---

## Automated Testing (Optional)

For automated testing, you can run:

```bash
# Run all PHPUnit tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
```

---

## Support

If you encounter any issues during testing, check:

1. Laravel logs: `storage/logs/laravel.log`
2. Nginx logs: `/var/log/nginx/error.log`
3. MeiliSearch logs: `sudo journalctl -u meilisearch`
4. Queue worker logs: `storage/logs/worker.log`

For issues or questions, refer to SETUP.md or the GitHub repository.
