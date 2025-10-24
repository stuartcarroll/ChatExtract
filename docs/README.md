# ChatExtract Documentation

**ChatExtract** is a Laravel-based web application for importing, analyzing, and managing WhatsApp chat exports with advanced features like transcription, tagging, search, and role-based access control.

## Documentation Structure

### Architecture
- **[Overview](architecture/overview.md)** - System architecture and technology stack
- **[Database Schema](architecture/database-schema.md)** - Database design and relationships
- **[Features](architecture/features.md)** - Complete feature list and capabilities
- **[Security](architecture/security.md)** - Security model, authentication, and authorization

### Deployment
- **[Production Guide](deployment/production-guide.md)** - Step-by-step production deployment
- **[Checklist](deployment/checklist.md)** - Quick deployment checklist
- **[500 Errors Troubleshooting](deployment/troubleshooting-500-errors.md)** - Common 500 error fixes

### Development
- **[Setup](development/setup.md)** - Local development environment setup
- **[Workflow](development/workflow.md)** - Development workflow with Claude and GitHub
- **[Testing](development/testing.md)** - Testing guide and test coverage
- **[Scripts](development/scripts.md)** - Development and deployment scripts

### AI Development
- **[CLAUDE.md](../CLAUDE.md)** - Context file for Claude Code sessions

## Quick Start

### For Developers
1. See [Development Setup](development/setup.md)
2. Review [Development Workflow](development/workflow.md)
3. Read [CLAUDE.md](../CLAUDE.md) for AI-assisted development

### For Deployment
1. Follow [Production Guide](deployment/production-guide.md)
2. Use [Deployment Checklist](deployment/checklist.md)
3. Reference [Troubleshooting](deployment/troubleshooting-500-errors.md) if issues arise

## Key Technologies

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Search**: Laravel Scout (database driver)
- **Queue**: Database-backed jobs for async processing
- **File Storage**: Local filesystem (S3-compatible)
- **Transcription**: OpenAI Whisper API
- **Authentication**: Laravel Breeze with 2FA support

## Core Features

- **WhatsApp Chat Import**: Bulk import with media extraction
- **Full-Text Search**: Messages, participants, dates, tags
- **Media Gallery**: Browse photos, videos, audio with filters
- **Audio Transcription**: AI-powered transcription with consent management
- **Tagging System**: Global tags with bulk operations
- **Export**: Bulk export to ZIP with metadata
- **Role-Based Access**: Admin, Chat User, View Only roles
- **Group Management**: Organize users and control access
- **Two-Factor Authentication**: TOTP-based 2FA

## Support

- **Issues**: Use GitHub Issues for bug reports and feature requests
- **Development**: See [Workflow](development/workflow.md) for contribution guidelines
- **Deployment Help**: Check [Troubleshooting](deployment/troubleshooting-500-errors.md)
