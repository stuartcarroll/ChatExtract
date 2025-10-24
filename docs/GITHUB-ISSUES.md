# GitHub Issues to Create

This document lists issues that should be created in the GitHub repository. Create these manually at https://github.com/stuartcarroll/ChatExtract/issues

---

## Enhancements

### 1. S3 Storage Integration
**Title**: Add S3 storage support for media files
**Labels**: enhancement, infrastructure
**Description**:
Currently media files are stored on local filesystem. Add support for S3-compatible storage to enable:
- Scalable media storage
- CDN integration
- Better backup/disaster recovery

**Tasks**:
- [ ] Configure S3 driver in filesystems config
- [ ] Add environment variables for S3 credentials
- [ ] Test upload/download with S3
- [ ] Add migration path for existing media
- [ ] Update documentation

**Priority**: Medium

---

### 2. Meilisearch Integration
**Title**: Replace database search with Meilisearch
**Labels**: enhancement, performance
**Description**:
Current search uses Laravel Scout with database driver. Integrate Meilisearch for:
- Faster search performance
- Better relevance ranking
- Typo tolerance
- Faceted search

**Tasks**:
- [ ] Setup Meilisearch server
- [ ] Configure Scout to use Meilisearch driver
- [ ] Update search index configuration
- [ ] Test search performance
- [ ] Document deployment changes

**Priority**: Medium

---

### 3. Email Notifications
**Title**: Add email notifications for async operations
**Labels**: enhancement, feature
**Description**:
Users should receive email notifications for:
- Import completion/failure
- Transcription completion
- Failed queue jobs
- Admin alerts

**Tasks**:
- [ ] Configure mail driver
- [ ] Create notification classes
- [ ] Add user email preferences
- [ ] Create email templates
- [ ] Test email delivery
- [ ] Add queue for email sending

**Priority**: Low

---

### 4. Message Editing and Deletion
**Title**: Allow users to edit and delete messages
**Labels**: enhancement, feature
**Description**:
Currently messages are read-only. Add ability to:
- Edit message content
- Delete individual messages
- Track edit history
- Soft delete with option to permanently delete

**Tasks**:
- [ ] Add edit/delete buttons to message UI
- [ ] Create edit modal with textarea
- [ ] Add validation for edits
- [ ] Create migration for edit tracking
- [ ] Implement authorization checks
- [ ] Add audit log

**Priority**: Low

---

### 5. Participant Merging
**Title**: Detect and merge duplicate participants
**Labels**: enhancement, feature
**Description**:
Users can have multiple participant entries due to:
- Phone number changes
- Name variations
- Import from multiple sources

Add UI to:
- Detect potential duplicates
- Preview merge
- Merge participants (update all messages)
- Undo merge if needed

**Tasks**:
- [ ] Create participant similarity detection
- [ ] Add admin UI for reviewing duplicates
- [ ] Implement merge functionality
- [ ] Update message relationships
- [ ] Add undo mechanism
- [ ] Test with large datasets

**Priority**: Medium

---

### 6. Advanced Analytics Dashboard
**Title**: Create analytics dashboard for chat statistics
**Labels**: enhancement, feature
**Description**:
Add dashboard showing:
- Messages over time (charts)
- Most active participants
- Media type breakdown
- Tag usage statistics
- Peak activity times
- Response time analytics

**Tasks**:
- [ ] Design dashboard UI
- [ ] Create analytics queries
- [ ] Add chart library (Chart.js)
- [ ] Implement caching for expensive queries
- [ ] Add date range filters
- [ ] Add export to PDF/CSV

**Priority**: Low

---

### 7. Chat Export Feature
**Title**: Export chats back to WhatsApp format
**Labels**: enhancement, feature
**Description**:
Currently can only import. Add ability to export chats in:
- WhatsApp text format
- JSON format
- CSV format
- Custom format with options

**Tasks**:
- [ ] Create export service
- [ ] Add export format options
- [ ] Include/exclude media option
- [ ] Generate downloadable file
- [ ] Add to chat actions menu
- [ ] Test with various formats

**Priority**: Low

---

### 8. Redis Queue Backend
**Title**: Support Redis for queue backend in production
**Labels**: enhancement, infrastructure, documentation
**Description**:
Database queue works but Redis is more performant for production. Add:
- Redis configuration documentation
- Environment setup guide
- Migration from database to Redis
- Monitoring and management tools

**Tasks**:
- [ ] Document Redis setup
- [ ] Create Redis configuration
- [ ] Test queue performance
- [ ] Add Redis to deployment guide
- [ ] Document monitoring with Horizon

**Priority**: Medium

---

## Bugs

### 9. Search Special Characters
**Title**: Improve search handling of special characters
**Labels**: bug, search
**Description**:
Search may not properly handle:
- Apostrophes
- Quotes
- Non-Latin characters
- Emojis

**Tasks**:
- [ ] Test search with various character sets
- [ ] Fix escaping issues
- [ ] Add test cases
- [ ] Verify with real data

**Priority**: High

---

### 10. Large File Upload Timeout
**Title**: Handle large file uploads better (>100MB)
**Labels**: bug, import
**Description**:
Very large chat exports may timeout during upload. Improvements needed:
- Better chunked upload handling
- Progress feedback
- Resume capability
- Timeout configuration

**Tasks**:
- [ ] Increase timeout limits
- [ ] Improve chunked upload UI
- [ ] Add resume functionality
- [ ] Test with 500MB+ files
- [ ] Document size limits

**Priority**: Medium

---

## Documentation

### 11. API Documentation
**Title**: Create API documentation (if public API is planned)
**Labels**: documentation
**Description**:
If API endpoints are to be made public, create:
- OpenAPI/Swagger spec
- Authentication docs
- Rate limiting info
- Example requests/responses

**Priority**: Low (only if API is made public)

---

### 12. User Guide
**Title**: Create end-user documentation
**Labels**: documentation
**Description**:
Create user-facing documentation for:
- How to export chats from WhatsApp
- How to import into ChatExtract
- How to search and filter
- How to tag and organize
- How to export data

**Tasks**:
- [ ] Write user guide
- [ ] Add screenshots
- [ ] Create video tutorial
- [ ] Add FAQ section

**Priority**: Medium

---

## Security

### 13. Rate Limiting
**Title**: Add rate limiting to API endpoints
**Labels**: security, enhancement
**Description**:
Protect against abuse by adding rate limiting to:
- Login attempts
- API endpoints
- Search queries
- File uploads

**Tasks**:
- [ ] Configure rate limiting middleware
- [ ] Set appropriate limits
- [ ] Add user feedback for limits
- [ ] Monitor rate limit hits

**Priority**: High

---

### 14. Security Audit
**Title**: Conduct comprehensive security audit
**Labels**: security
**Description**:
Perform security review of:
- Authentication system
- Authorization checks
- Input validation
- File upload security
- SQL injection vectors
- XSS vulnerabilities
- CSRF protection

**Tasks**:
- [ ] Review all controllers
- [ ] Test authorization on all routes
- [ ] Verify input sanitization
- [ ] Check file upload restrictions
- [ ] Review Blade escaping
- [ ] Test session security

**Priority**: High

---

## Testing

### 15. Increase Test Coverage
**Title**: Write tests for uncovered features
**Labels**: testing
**Description**:
Current test coverage is limited. Add tests for:
- Import functionality
- Search features
- Export features
- Transcription
- Tag management
- Access control

**Target**: 80% code coverage

**Priority**: Medium

---

## Infrastructure

### 16. Docker Support
**Title**: Create Docker containers for development and production
**Labels**: infrastructure, documentation
**Description**:
Add Docker support for easier:
- Development environment setup
- Consistent deployments
- CI/CD integration

**Tasks**:
- [ ] Create Dockerfile
- [ ] Create docker-compose.yml
- [ ] Document Docker workflow
- [ ] Test with MySQL container
- [ ] Add Redis container
- [ ] Create production Docker config

**Priority**: Low

---

## How to Create These Issues

1. Go to https://github.com/stuartcarroll/ChatExtract/issues
2. Click "New Issue"
3. Copy the title and description from above
4. Add appropriate labels
5. Set milestone if applicable
6. Assign to yourself if working on it

## Priority Legend
- **High**: Should be addressed soon
- **Medium**: Important but not urgent
- **Low**: Nice to have, work on when time permits
