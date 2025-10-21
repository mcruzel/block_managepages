# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2025-10-21

### Added
- AMD JavaScript module to replace inline JavaScript for better maintainability
- Comprehensive PHPUnit test suite for the exporter class
- Input validation with maximum limits (100 pages per export)
- Filename length limits (200 characters) to prevent issues
- SECURITY.md documentation file detailing security measures
- CHANGELOG.md to track version history
- Enhanced error handling with specific exception types
- Proper HTTP status codes for all error responses (400, 403, 404, 405, 500)
- Debug logging for administrator troubleshooting

### Changed
- Refactored export.php into separate handler functions for better code organization
- Improved exporter class with robust parameter validation
- Enhanced filename sanitization to handle edge cases (empty names, HTML tags, special characters)
- Migrated from inline JavaScript to AMD modules (Moodle standard)
- Updated template to use AMD module instead of inline scripts
- Improved error messages to be more user-friendly
- Better ZIP file validation and cleanup

### Fixed
- Potential security issues with unvalidated user input
- Missing validation for course membership and page ownership
- Inconsistent error handling across different code paths
- Memory issues when exporting large numbers of pages
- Filename sanitization edge cases

### Security
- Added comprehensive input validation for all user-supplied data
- Implemented capability checks for editing operations
- Enhanced file path sanitization using Moodle's clean_param()
- Added protection against resource exhaustion attacks
- Improved error messages to not leak system information
- Added proper Content-Type and Cache-Control headers
- Validated that pages belong to the requested course
- Sanitized page IDs to remove duplicates and invalid values

### Developer
- Added PHPDoc comments for all methods
- Improved code organization with separate handler functions
- Added constants for configuration values (MAX_PAGES, MAX_FILENAME_LENGTH)
- Enhanced test coverage with edge cases and error conditions
- Better exception handling throughout the codebase

## [1.2.3] - 2025-06-08

### Previous Releases
See git history for details on releases prior to 1.3.0.
