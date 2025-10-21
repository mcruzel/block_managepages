# Security Documentation

## Overview
This document outlines the security measures implemented in the block_managepages plugin for Moodle.

## Security Measures Implemented

### 1. Authentication and Authorization
- **Session validation**: All requests require `require_login()` and `require_sesskey()`
- **Capability checks**: Users must have `block/managepages:export` capability
- **Additional editing capability**: Page editing requires `moodle/course:manageactivities` capability
- **Course context verification**: All operations verify that resources belong to the correct course

### 2. Input Validation
- **Parameter validation**: All user inputs are validated using Moodle's PARAM_* types
  - `PARAM_INT` for IDs and numeric values
  - `PARAM_RAW` for HTML content (with appropriate context)
  - `PARAM_PATH` for file paths
- **Array sanitization**: Page IDs are sanitized to remove duplicates, zeros, and negative values
- **Maximum limits**: Export limited to 100 pages per request to prevent resource exhaustion
- **Filename length limits**: Filenames limited to 200 characters

### 3. Output Escaping and Sanitization
- **Template engine**: Mustache automatically escapes output to prevent XSS
- **Filename cleaning**: Filenames are sanitized to remove special characters and HTML tags
- **JSON responses**: All JSON responses use proper Content-Type headers
- **Database queries**: All database operations use parameterized queries via Moodle's DML

### 4. File Operations
- **Temporary files**: ZIP files created in system temp directory with unique names
- **File cleanup**: Temporary files are deleted immediately after serving
- **Path sanitization**: All file paths are validated using `clean_param()`
- **ZIP validation**: ZIP files are verified before serving

### 5. HTTP Headers
- **Content-Type**: Proper Content-Type headers for all responses (JSON, markdown, ZIP)
- **Cache-Control**: No-cache headers for dynamic content
- **Content-Length**: Accurate Content-Length headers for downloads
- **Content-Disposition**: Proper filename handling in download headers

### 6. Error Handling
- **Exception handling**: All operations wrapped in try-catch blocks
- **Debug logging**: Errors logged using `debugging()` for administrators
- **Generic error messages**: User-facing errors don't reveal system details
- **HTTP status codes**: Appropriate status codes (400, 403, 404, 405, 500)

### 7. SQL Injection Prevention
- **DML API**: All database operations use Moodle's Database Manipulation API
- **Prepared statements**: All queries use bound parameters
- **Record validation**: All database records verified before use

### 8. Cross-Site Scripting (XSS) Prevention
- **Mustache templates**: Automatic output escaping
- **format_string()**: Course content sanitized using Moodle's format_string()
- **HTML in content**: Page HTML content is stored and served as-is (as intended by course creators)

### 9. Access Control
- **Visibility checks**: Only pages visible to the current user are accessible
- **Course membership**: Users must have access to the course
- **Permission inheritance**: Respects Moodle's role and permission system

### 10. Code Quality
- **Type validation**: Strict type checking for all parameters
- **PHPDoc comments**: Full documentation with parameter types
- **Unit tests**: Comprehensive test coverage for security-critical functions
- **AMD JavaScript**: Modern JavaScript modules instead of inline code

## Security Considerations for Site Administrators

### Recommended Settings
1. Only grant `block/managepages:export` to trusted users (teachers and managers by default)
2. Regularly review user capabilities in courses
3. Monitor server logs for unusual export activity
4. Ensure PHP `max_execution_time` is appropriate for large exports

### Known Limitations
1. Page content is exported as-is (HTML), which may contain embedded scripts if added by course creators
2. Export operations may consume server resources for large courses
3. ZIP file creation requires sufficient disk space in temp directory

## Reporting Security Issues
If you discover a security vulnerability, please report it via the project's GitHub issues page or contact the maintainer directly.

## Recent Security Improvements (v1.2.3)
- Added input validation for all methods in exporter class
- Implemented maximum page limit to prevent resource exhaustion
- Enhanced filename sanitization with length limits
- Added comprehensive error handling with proper HTTP status codes
- Improved temporary file cleanup
- Added unit tests for security-critical functions
- Migrated JavaScript from inline to AMD modules
- Enhanced permission checks for page editing

## Audit Log
- 2025-06-08: Security review and enhancements completed
- 2025-06-08: Added comprehensive input validation
- 2025-06-08: Implemented PHPUnit test suite
