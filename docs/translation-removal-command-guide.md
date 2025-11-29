# Translation Removal Command Guide

Complete documentation for the `translation:remove` command - a powerful tool for removing translation keys from both frontend JSON files and backend PHP files in the Vessel Management System.

## 📚 Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Command Syntax](#command-syntax)
- [Options](#options)
- [Usage Examples](#usage-examples)
- [File Locations](#file-locations)
- [Wildcard Patterns](#wildcard-patterns)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [Related Documentation](#related-documentation)

---

## Overview

The `translation:remove` command allows you to remove translation keys from both frontend and backend translation files simultaneously. This is essential for cleaning up unused translations, removing deprecated keys, or maintaining translation consistency across the application.

### Key Features

- ✅ **Dual Location Support**: Removes from both frontend JSON and backend PHP files
- ✅ **Wildcard Support**: Use `*` patterns to remove multiple keys at once
- ✅ **Dry Run Mode**: Preview changes before applying them
- ✅ **Selective Removal**: Target specific locales or files
- ✅ **Safe Operations**: Validates files and provides clear feedback
- ✅ **Format Preservation**: Maintains proper JSON and PHP formatting

### Supported Locations

1. **Frontend JSON Files**: `resources/js/i18n/locales/{locale}.json`
   - `en.json` - English
   - `pt.json` - Portuguese
   - `es.json` - Spanish
   - `fr.json` - French

2. **Backend PHP Files**: `lang/{locale}/{file}.php`
   - `notifications.php` - Notification messages
   - `emails.php` - Email content
   - `pdfs.php` - PDF content
   - `errors.php` - Error messages
   - `categories.php` - Category translations
   - `crew-positions.php` - Crew position translations
   - And other translation files

---

## Quick Start

### Basic Usage

Remove a single translation key from all files:

```bash
php artisan translation:remove "Dashboard"
```

### Preview Changes First

Always use `--dry-run` to preview changes:

```bash
php artisan translation:remove "Dashboard" --dry-run
```

### Remove with Wildcard

Remove all keys matching a pattern:

```bash
php artisan translation:remove "Dashboard*"
```

---

## Command Syntax

```bash
php artisan translation:remove {key} [options]
```

### Required Arguments

- `key` - The translation key to remove (supports wildcards with `*`)

### Options

| Option | Description | Example |
|--------|-------------|---------|
| `--locale=*` | Specific locales to process (en, pt, es, fr). Default: all | `--locale=en --locale=pt` |
| `--file=*` | Specific backend files to process. Default: all | `--file=notifications --file=emails` |
| `--dry-run` | Preview changes without applying them | `--dry-run` |
| `--frontend-only` | Only remove from frontend JSON files | `--frontend-only` |
| `--backend-only` | Only remove from backend PHP files | `--backend-only` |

---

## Options

### `--locale`

Specify which locales to process. If not provided, all locales are processed.

**Valid locales**: `en`, `pt`, `es`, `fr`

```bash
# Remove from English and Portuguese only
php artisan translation:remove "Dashboard" --locale=en --locale=pt

# Remove from Spanish only
php artisan translation:remove "Dashboard" --locale=es
```

### `--file`

Specify which backend PHP files to process. If not provided, all files are processed.

**Common files**: `notifications`, `emails`, `pdfs`, `errors`, `categories`, `crew-positions`

```bash
# Remove from notifications and emails only
php artisan translation:remove "Dashboard" --file=notifications --file=emails

# Remove from a single file
php artisan translation:remove "Dashboard" --file=notifications
```

### `--dry-run`

Preview what would be removed without making any changes. Always recommended before actual removal.

```bash
php artisan translation:remove "Dashboard" --dry-run
```

**Output Example**:
```
🔍 Removing Translation Key: Dashboard
🔍 DRY RUN MODE - No changes will be made

📄 Processing Frontend JSON Files:
  🗑️  [en] Removing: Dashboard
  🗑️  [pt] Removing: Dashboard
  🗑️  [es] Removing: Dashboard
  🗑️  [fr] Removing: Dashboard

📄 Processing Backend PHP Files:
  🗑️  [en/notifications] Removing: Dashboard
  🗑️  [pt/notifications] Removing: Dashboard

📊 Preview: Would remove 6 translation(s)
Run without --dry-run to apply changes
```

### `--frontend-only`

Only process frontend JSON files, skip backend PHP files.

```bash
php artisan translation:remove "Dashboard" --frontend-only
```

### `--backend-only`

Only process backend PHP files, skip frontend JSON files.

```bash
php artisan translation:remove "Dashboard" --backend-only
```

---

## Usage Examples

### Example 1: Remove Single Key

Remove "Dashboard" from all files and all locales:

```bash
php artisan translation:remove "Dashboard"
```

### Example 2: Preview Before Removing

Always preview first:

```bash
php artisan translation:remove "Dashboard" --dry-run
```

### Example 3: Remove with Wildcard

Remove all keys starting with "Dashboard":

```bash
php artisan translation:remove "Dashboard*"
```

This will remove:
- "Dashboard"
- "Dashboard Settings"
- "Dashboard Overview"
- etc.

### Example 4: Remove from Specific Locale

Remove only from English and Portuguese:

```bash
php artisan translation:remove "Dashboard" --locale=en --locale=pt
```

### Example 5: Remove from Specific Backend File

Remove only from notifications file:

```bash
php artisan translation:remove "Dashboard" --file=notifications
```

### Example 6: Remove from Multiple Backend Files

Remove from notifications and emails:

```bash
php artisan translation:remove "Dashboard" --file=notifications --file=emails
```

### Example 7: Frontend Only

Remove only from JSON files:

```bash
php artisan translation:remove "Dashboard" --frontend-only
```

### Example 8: Backend Only

Remove only from PHP files:

```bash
php artisan translation:remove "Dashboard" --backend-only
```

### Example 9: Complex Pattern

Remove all keys containing "Member" from English locale only:

```bash
php artisan translation:remove "*Member*" --locale=en
```

### Example 10: Remove Deprecated Keys

Remove a deprecated key pattern from all files:

```bash
php artisan translation:remove "Old*Key*" --dry-run
```

---

## File Locations

### Frontend JSON Files

Located in: `resources/js/i18n/locales/`

```
resources/js/i18n/locales/
├── en.json  # English translations
├── pt.json  # Portuguese translations
├── es.json  # Spanish translations
└── fr.json  # French translations
```

**Format**:
```json
{
    "_comment": "English translations - keys are the English text themselves",
    "Dashboard": "Dashboard",
    "Mareas": "Mareas",
    "Settings": "Settings"
}
```

### Backend PHP Files

Located in: `lang/{locale}/`

```
lang/
├── en/
│   ├── notifications.php
│   ├── emails.php
│   ├── pdfs.php
│   ├── errors.php
│   └── ...
├── pt/
│   ├── notifications.php
│   ├── emails.php
│   └── ...
├── es/
└── fr/
```

**Format**:
```php
<?php

return [
    'Dashboard' => 'Dashboard',
    'Mareas' => 'Mareas',
    'Settings' => 'Settings',
];
```

---

## Wildcard Patterns

The command supports wildcard patterns using `*` for flexible key matching.

### Wildcard Examples

| Pattern | Matches | Example Matches |
|---------|---------|-----------------|
| `Dashboard*` | Keys starting with "Dashboard" | "Dashboard", "Dashboard Settings", "Dashboard Overview" |
| `*Member*` | Keys containing "Member" | "Member", "Crew Member", "Add Member", "Member List" |
| `*Settings` | Keys ending with "Settings" | "Settings", "User Settings", "Vessel Settings" |
| `*Member*List*` | Keys with "Member" and "List" | "Member List", "Crew Member List" |

### Wildcard Usage

```bash
# Remove all keys starting with "Dashboard"
php artisan translation:remove "Dashboard*"

# Remove all keys containing "Member"
php artisan translation:remove "*Member*"

# Remove all keys ending with "Settings"
php artisan translation:remove "*Settings"
```

**Note**: Wildcards use regex matching. Special regex characters in keys are automatically escaped.

---

## Best Practices

### 1. Always Use Dry Run First

Before removing translations, always preview changes:

```bash
php artisan translation:remove "Key" --dry-run
```

### 2. Remove Unused Keys Regularly

Keep translation files clean by removing unused keys:

```bash
# Find and remove deprecated keys
php artisan translation:remove "Deprecated*" --dry-run
```

### 3. Use Specific Options

Be specific about what to remove to avoid unintended deletions:

```bash
# Instead of removing from all files
php artisan translation:remove "Key"

# Be specific
php artisan translation:remove "Key" --file=notifications --locale=en
```

### 4. Verify Before Removing

Check if keys are actually unused:

1. Search codebase for key usage
2. Use `--dry-run` to preview
3. Remove only confirmed unused keys

### 5. Remove in Batches

For multiple related keys, use wildcards:

```bash
# Remove all related keys at once
php artisan translation:remove "OldFeature*"
```

### 6. Backup Before Bulk Removal

Before removing many keys, consider backing up translation files:

```bash
# Backup before removal
cp -r lang lang.backup
cp -r resources/js/i18n/locales resources/js/i18n/locales.backup

# Then remove
php artisan translation:remove "OldKey*"
```

### 7. Test After Removal

After removing translations:

1. Test the application
2. Check for missing translations
3. Verify no errors in console

---

## Troubleshooting

### Command Not Found

If the command is not found:

```bash
# Clear Laravel cache
php artisan cache:clear

# Rebuild autoload
composer dump-autoload
```

### Invalid JSON Error

If you see "Invalid JSON" errors:

1. Check the JSON file syntax
2. Validate JSON using a JSON validator
3. Fix syntax errors manually
4. Re-run the command

### Invalid PHP Array Error

If you see "Invalid PHP array" errors:

1. Check the PHP file syntax
2. Ensure it returns an array
3. Fix syntax errors manually
4. Re-run the command

### Key Not Found

If a key is not found:

1. Verify the key exists in the files
2. Check for typos in the key name
3. Use wildcards if unsure: `*Key*`
4. Check both frontend and backend files

### File Not Found

If files are not found:

1. Verify file paths exist
2. Check locale names (en, pt, es, fr)
3. Verify backend file names
4. Ensure files have correct extensions (.json, .php)

### Formatting Issues

If files lose formatting:

1. The command preserves formatting automatically
2. JSON files maintain pretty-print format
3. PHP files maintain alignment
4. If issues occur, restore from backup

### Permission Errors

If you get permission errors:

```bash
# Check file permissions
ls -la resources/js/i18n/locales/
ls -la lang/

# Fix permissions if needed
chmod -R 755 resources/js/i18n/locales/
chmod -R 755 lang/
```

---

## Related Documentation

- [Translation Patterns](./patterns/translation-patterns.md) - Frontend translation patterns
- [Backend Translation Patterns](./patterns/backend-translation-patterns.md) - Backend translation patterns
- [Quick Reference](./quick-reference.md) - Quick lookup for common patterns
- [Implementation Guide](./implementation-guide.md) - Complete system architecture

---

## Command Output Examples

### Successful Removal

```bash
$ php artisan translation:remove "Dashboard"

🔍 Removing Translation Key: Dashboard

📄 Processing Frontend JSON Files:
  🗑️  [en] Removing: Dashboard
  🗑️  [pt] Removing: Dashboard
  🗑️  [es] Removing: Dashboard
  🗑️  [fr] Removing: Dashboard

📄 Processing Backend PHP Files:
  🗑️  [en/notifications] Removing: Dashboard
  🗑️  [pt/notifications] Removing: Dashboard
  🗑️  [es/notifications] Removing: Dashboard
  🗑️  [fr/notifications] Removing: Dashboard

✅ Successfully removed 8 translation(s)
```

### Dry Run Preview

```bash
$ php artisan translation:remove "Dashboard*" --dry-run

🔍 Removing Translation Key: Dashboard*
🔍 DRY RUN MODE - No changes will be made

📄 Processing Frontend JSON Files:
  🗑️  [en] Removing: Dashboard
  🗑️  [en] Removing: Dashboard Settings
  🗑️  [en] Removing: Dashboard Overview
  🗑️  [pt] Removing: Dashboard
  🗑️  [pt] Removing: Dashboard Settings
  🗑️  [pt] Removing: Dashboard Overview

📄 Processing Backend PHP Files:
  🗑️  [en/notifications] Removing: Dashboard
  🗑️  [pt/notifications] Removing: Dashboard

📊 Preview: Would remove 8 translation(s)
Run without --dry-run to apply changes
```

### No Keys Found

```bash
$ php artisan translation:remove "NonExistentKey"

🔍 Removing Translation Key: NonExistentKey

📄 Processing Frontend JSON Files:
  ℹ️  [en] No matching keys found
  ℹ️  [pt] No matching keys found
  ℹ️  [es] No matching keys found
  ℹ️  [fr] No matching keys found

📄 Processing Backend PHP Files:
  ℹ️  [en/notifications] No matching keys found

✅ Successfully removed 0 translation(s)
```

---

## Summary

The `translation:remove` command is a powerful tool for maintaining translation files. Key points:

- ✅ Removes from both frontend JSON and backend PHP files
- ✅ Supports wildcard patterns for bulk removal
- ✅ Always use `--dry-run` first to preview changes
- ✅ Can target specific locales or files
- ✅ Preserves file formatting
- ✅ Provides clear feedback on operations

**Remember**: Always preview changes with `--dry-run` before removing translations!

---

**Last Updated**: 2025-01-XX  
**Version**: 1.0.0  
**Command**: `php artisan translation:remove`

