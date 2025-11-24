# Troubleshooting Guide

This guide documents common production issues and their solutions for the Vessel Management System.

## 📋 Table of Contents

- [Vite Manifest Errors](#vite-manifest-errors)
- [Translation Key Issues](#translation-key-issues)
- [Error Pages](#error-pages)
- [Production Deployment](#production-deployment)
- [Common Console Errors](#common-console-errors)

## Vite Manifest Errors

### Problem: "Unable to locate file in Vite manifest: resources/css/app.css"

**Symptoms:**
- Error occurs when rendering error pages (404, 500, etc.)
- Error message: `Unable to locate file in Vite manifest: resources/css/app.css`
- Happens in production when Vite manifest is not available

**Root Cause:**
The `@vite` directive throws an exception when the manifest file doesn't exist. This commonly happens on error pages when assets haven't been built or the manifest path is incorrect.

**Solution:**
Error pages should check if the Vite manifest exists before using the `@vite` directive:

```php
@php
    // Check if Vite manifest exists before using @vite directive
    // The @vite directive throws an exception if manifest is not found
    // This check prevents the error in production when assets might not be built
    $manifestPath = public_path('build/.vite/manifest.json');
    $hasViteManifest = file_exists($manifestPath);
@endphp

@if($hasViteManifest)
    @vite(['resources/css/app.css'])
@endif
{{-- If Vite manifest doesn't exist, the page uses inline styles defined below --}}
```

**Files Affected:**
- `resources/views/errors/404.blade.php`
- `resources/views/errors/500.blade.php` (if exists)
- Any other error page templates

**Prevention:**
- Always include conditional Vite loading in error pages
- Ensure error pages have inline styles as fallback
- Test error pages in production-like environment

## Translation Key Issues

### Problem: "[intlify] Not found 'Key Name' key in 'locale' locale messages"

**Symptoms:**
- Console errors about missing translation keys
- SSR errors when rendering pages
- Missing text in UI (shows key instead of translation)

**Root Cause:**
Translation keys are missing from the locale JSON files. The system uses English text as keys, so both the key and value must exist.

**Solution:**
Add the missing translation key to all locale files:

1. **English (en.json)** - Add the key with English value:
```json
{
    "Delete Vessel": "Delete Vessel",
    "This will permanently delete the vessel and remove all user access. This action cannot be undone.": "This will permanently delete the vessel and remove all user access. This action cannot be undone."
}
```

2. **Other Languages** - Add the same key with translated value:
```json
{
    "Delete Vessel": "Excluir Embarcação",
    "This will permanently delete the vessel and remove all user access. This action cannot be undone.": "Isso excluirá permanentemente a embarcação e removerá todo o acesso do usuário. Esta ação não pode ser desfeita."
}
```

**Common Missing Keys:**
- Vessel deletion: `"Delete Vessel"` and `"This will permanently delete the vessel and remove all user access. This action cannot be undone."`
- Privacy Policy sections: `"Information We Collect"`, `"How We Use Your Information"`, `"Data Security"`, `"Data Retention"`, `"Your Rights"`

**Prevention:**
- Always add translation keys to all locale files (en, pt, es, fr)
- Use English text as the key (not abstract keys)
- Verify keys exist before using in components
- Test pages in all supported languages

**Translation File Locations:**
- `resources/js/i18n/locales/en.json`
- `resources/js/i18n/locales/pt.json`
- `resources/js/i18n/locales/es.json`
- `resources/js/i18n/locales/fr.json`

## Error Pages

### 404 Page Best Practices

The 404 error page should:
1. ✅ Check for Vite manifest before using `@vite` directive
2. ✅ Include inline styles as fallback
3. ✅ Use Laravel translation helpers for text
4. ✅ Support dark mode
5. ✅ Be lightweight and fast-loading

**Example Structure:**
```php
@php
    $manifestPath = public_path('build/.vite/manifest.json');
    $hasViteManifest = file_exists($manifestPath);
@endphp

@if($hasViteManifest)
    @vite(['resources/css/app.css'])
@endif

<style>
    /* Inline styles as fallback */
    body {
        font-family: 'Inter', sans-serif;
        /* ... */
    }
</style>
```

## Production Deployment

### Pre-Deployment Checklist

Before deploying to production, verify:

- [ ] All translation keys exist in all locale files
- [ ] Error pages handle missing Vite manifest gracefully
- [ ] Assets are built: `npm run build`
- [ ] Vite manifest exists: `public/build/.vite/manifest.json`
- [ ] Error pages tested with missing manifest
- [ ] All pages tested in all supported languages

### Building Assets for Production

```bash
# Build production assets
npm run build

# Verify manifest exists
ls -la public/build/.vite/manifest.json

# Test error pages
# Navigate to non-existent route to test 404 page
```

### Verifying Translation Keys

```bash
# Check for missing keys in English
grep -r "t('Delete Vessel')" resources/js/

# Verify key exists in all locales
grep "Delete Vessel" resources/js/i18n/locales/*.json
```

## Common Console Errors

### Development-Only Warnings (Safe to Ignore)

These errors appear in development but don't affect production:

1. **Vite HMR Warnings:**
   - `[vite] Failed to reload /resources/js/components/AppSidebar.vue`
   - These are Hot Module Reload warnings during development
   - **Action:** None required - these don't affect production builds

2. **Wayfinder Type Generation:**
   - `Pre-transform error: Failed to load url /resources/js/routes/index.ts`
   - Wayfinder plugin trying to generate TypeScript types
   - **Action:** None required - types are generated automatically

3. **Vue HMR Updates:**
   - Multiple `[vite] (client) hmr update` messages
   - Normal development behavior
   - **Action:** None required

### Production Errors (Must Fix)

1. **Vite Manifest Errors:**
   - `Unable to locate file in Vite manifest`
   - **Action:** Implement conditional Vite loading in error pages

2. **Translation Key Errors:**
   - `[intlify] Not found 'Key Name' key`
   - **Action:** Add missing keys to all locale files

3. **SSR Errors:**
   - `SsrException: [intlify] Not found key`
   - **Action:** Add missing translation keys

## Quick Fixes

### Fix Missing Translation Key

1. Identify the missing key from error logs
2. Add to `resources/js/i18n/locales/en.json`:
```json
{
    "Missing Key": "Missing Key"
}
```
3. Add to other locale files with translations
4. Test the page

### Fix 404 Vite Error

1. Open `resources/views/errors/404.blade.php`
2. Replace `@vite(['resources/css/app.css'])` with:
```php
@php
    $manifestPath = public_path('build/.vite/manifest.json');
    $hasViteManifest = file_exists($manifestPath);
@endphp

@if($hasViteManifest)
    @vite(['resources/css/app.css'])
@endif
```
3. Ensure inline styles exist as fallback
4. Test 404 page

## Testing Production Issues

### Testing 404 Page

```bash
# Navigate to non-existent route
curl http://localhost:8000/non-existent-page-12345

# Check for Vite errors in response
# Should return 404 page without errors
```

### Testing Translations

```bash
# Change language in browser/app
# Navigate to pages using translations
# Check console for missing key errors
```

### Testing Error Handling

```bash
# Trigger 404 error
# Trigger 500 error (if possible)
# Verify error pages load without Vite errors
```

## Related Documentation

- **Translation Patterns**: `docs/patterns/translation-patterns.md`
- **Frontend Patterns**: `docs/patterns/frontend-patterns.md`
- **SSR Deployment**: `docs/ssr-deployment-guide.md`
- **Quick Reference**: `docs/quick-reference.md`

## Support

If you encounter issues not covered in this guide:

1. Check production logs: `storage/logs/laravel.log`
2. Check browser console for errors
3. Verify all translation keys exist
4. Test error pages with missing manifest
5. Review recent changes to error pages or translations

