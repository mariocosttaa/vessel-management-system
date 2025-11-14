# Backend Translation Patterns

> **Important**: This document defines the backend translation system patterns for the Vessel Management System. Always follow these patterns when adding translations to controllers, emails, and other backend components.

## 📋 Table of Contents

- [Overview](#overview)
- [Key Principles](#key-principles)
- [Translation File Structure](#translation-file-structure)
- [Using Translations in Controllers](#using-translations-in-controllers)
- [Using Translations in Emails](#using-translations-in-emails)
- [Using Translations in PDFs](#using-translations-in-pdfs)
- [HasTranslations Trait](#hastranslations-trait)
- [Best Practices](#best-practices)
- [Adding New Translations](#adding-new-translations)
- [Common Patterns](#common-patterns)
- [Troubleshooting](#troubleshooting)

## Overview

The backend translation system uses **English text as keys** (same as frontend) and automatically respects the user's language preference. This ensures consistency across the entire application.

### Key Features

- ✅ **User-aware**: Automatically uses the authenticated user's language preference
- ✅ **English as key**: Same pattern as frontend for consistency
- ✅ **Fallback support**: Falls back to English if translation is missing
- ✅ **Placeholder support**: Supports Laravel's `:placeholder` syntax
- ✅ **File organization**: Organized by purpose (notifications, emails, etc.)

## Key Principles

### 1. English Text as Key

**Always use the English text as the translation key:**

```php
// ✅ CORRECT - English text as key
$this->transFrom('notifications', 'Profile updated successfully.');

// ❌ WRONG - Don't use abstract keys
$this->transFrom('notifications', 'profile.updated');
```

### 2. Use HasTranslations Trait

All controllers and mailables should use the `HasTranslations` trait:

```php
use App\Traits\HasTranslations;

class ProfileController extends Controller
{
    use HasTranslations;

    public function update(Request $request)
    {
        // Use translations
        return back()->with('success',
            $this->transFrom('notifications', 'Profile updated successfully.')
        );
    }
}
```

### 3. Respect User Language

The trait automatically detects and uses the authenticated user's language preference:

```php
// Automatically uses user's language if authenticated
$this->transFrom('notifications', 'Profile updated successfully.');

// Or force a specific locale
$this->transFrom('notifications', 'Profile updated successfully.', [], 'pt');
```

## Translation File Structure

### File Locations

All translation files are located in `lang/{locale}/`:

- `lang/en/notifications.php` - Notification messages (English)
- `lang/pt/notifications.php` - Notification messages (Portuguese)
- `lang/es/notifications.php` - Notification messages (Spanish)
- `lang/fr/notifications.php` - Notification messages (French)
- `lang/en/emails.php` - Email content (English)
- `lang/pt/emails.php` - Email content (Portuguese)
- `lang/en/pdfs.php` - PDF content (English)
- `lang/pt/pdfs.php` - PDF content (Portuguese)
- etc.

### File Organization

Organize translations by purpose:

- **`notifications.php`**: Flash messages, success/error notifications
- **`emails.php`**: Email subjects and content
- **`pdfs.php`**: PDF report titles, labels, and content
- **`validation.php`**: Validation error messages (if needed)
- **`common.php`**: Common phrases used across the system

### File Structure

```php
<?php

return [
    // Success messages
    'Profile updated successfully.' => 'Profile updated successfully.',
    'Operation completed successfully.' => 'Operation completed successfully.',

    // Error messages with placeholders
    'Failed to update profile: :message' => 'Failed to update profile: :message',
    "Marea ':number' has been created successfully." => "Marea ':number' has been created successfully.",
];
```

## Using Translations in Controllers

### Basic Usage

```php
use App\Traits\HasTranslations;

class ProfileController extends Controller
{
    use HasTranslations;

    public function update(Request $request)
    {
        try {
            // ... update logic ...

            return back()->with('success',
                $this->transFrom('notifications', 'Profile updated successfully.')
            );
        } catch (\Exception $e) {
            return back()->with('error',
                $this->transFrom('notifications', 'Failed to update profile: :message', [
                    'message' => $e->getMessage()
                ])
            );
        }
    }
}
```

### With Placeholders

```php
// Translation file
"Marea ':number' has been created successfully." => "Marea ':number' has been created successfully.",

// Controller usage
return back()->with('success',
    $this->transFrom('notifications', "Marea ':number' has been created successfully.", [
        'number' => $marea->marea_number
    ])
);
```

### Dynamic Content

```php
// For dynamic content, use placeholders
":type ':name' has been restored successfully." => ":type ':name' has been restored successfully.",

// Usage
return back()->with('success',
    $this->transFrom('notifications', ":type ':name' has been restored successfully.", [
        'type' => $itemType,
        'name' => $itemName
    ])
);
```

### Pluralization

```php
// Translation file
'Recycle bin has been emptied. :count item(s) have been permanently deleted.' =>
    'Recycle bin has been emptied. :count item(s) have been permanently deleted.',

// Controller usage
return back()->with('success',
    $this->transFrom('notifications',
        'Recycle bin has been emptied. :count item(s) have been permanently deleted.',
        ['count' => $totalCount]
    )
);
```

## Using Translations in Emails

### In Mailable Classes

```php
use App\Traits\HasTranslations;
use Illuminate\Support\Facades\App;

class GroupedVesselNotificationMail extends Mailable
{
    use Queueable, SerializesModels, HasTranslations;

    public function envelope(): Envelope
    {
        // Set locale based on user's preference
        $originalLocale = App::getLocale();
        if ($this->user->language) {
            App::setLocale($this->user->language);
        }

        $subject = $this->transFrom('emails', 'Transactions Created');

        // Restore original locale
        App::setLocale($originalLocale);

        return new Envelope(
            subject: $subject . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        // Set locale for email content
        $originalLocale = App::getLocale();
        if ($this->user->language) {
            App::setLocale($this->user->language);
        }

        $content = new Content(
            view: 'emails.notifications.grouped-transactions-created',
            with: [
                'user' => $this->user,
                'vessel' => $this->vessel,
                'locale' => $this->user->language ?? 'en',
                // ... other data
            ],
        );

        // Restore original locale
        App::setLocale($originalLocale);

        return $content;
    }
}
```

### In Email Blade Templates

```blade
{{-- Use Laravel's trans() helper in Blade --}}
<h1>{{ trans('emails.Transactions Created') }}</h1>

<p>{{ trans('emails.Hello :name', ['name' => $user->name]) }}</p>

<p>{{ trans('emails.New transactions have been created for vessel :vessel', [
    'vessel' => $vessel->name
]) }}</p>

<a href="{{ $url }}">{{ trans('emails.View Details') }}</p>
```

## Using Translations in PDFs

PDFs automatically respect the user's language preference. The translation system handles both header/footer text and content within the PDF template.

### PDF Translation File Structure

PDF translations are stored in `lang/{locale}/pdfs.php`:

```php
<?php

return [
    // Report titles
    'Transaction Report' => 'Transaction Report',
    'Movements and Transactions Overview' => 'Movements and Transactions Overview',
    'All Transactions' => 'All Transactions',

    // Section headers
    'Period:' => 'Period:',
    'Summary' => 'Summary',
    'Transactions' => 'Transactions',

    // Summary labels
    'Total Income' => 'Total Income',
    'Total Expenses' => 'Total Expenses',
    'Net Balance' => 'Net Balance',
    'Total Transactions' => 'Total Transactions',

    // Table headers
    'Date' => 'Date',
    'Description' => 'Description',
    'Category' => 'Category',
    'Amount' => 'Amount',
    'Type' => 'Type',

    // Empty state
    'No transactions found for the selected period.' => 'No transactions found for the selected period.',

    // Header/Footer labels
    'Vessel:' => 'Vessel:',
    'Registration:' => 'Registration:',
    'Type:' => 'Type:',
    'Generated:' => 'Generated:',
    'Generated by:' => 'Generated by:',
    'Email:' => 'Email:',
    'Page :pageNumber of :pageCount' => 'Page :pageNumber of :pageCount',
    'Generated on' => 'Generated on',
    'All rights reserved' => 'All rights reserved',
];
```

### In PDF Generator Classes

PDF generator classes (like `TransactionPdf` or `MovimentationPdf`) should accept an optional `User` parameter and translate titles/subtitles:

```php
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Support\Facades\App;

class TransactionPdf
{
    public static function generate(
        Vessel $vessel,
        Collection $transactions,
        array $summary,
        ?string $period = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $title = 'Transaction Report',
        ?string $subtitle = 'Movements and Transactions Overview',
        bool $enableColors = false,
        ?User $user = null  // Accept user parameter
    ) {
        // Translate title and subtitle if user is provided
        if ($user && $user->language) {
            $originalLocale = App::getLocale();
            App::setLocale($user->language);

            if ($title === 'Transaction Report') {
                $title = trans('pdfs.Transaction Report');
            }
            if ($subtitle === 'Movements and Transactions Overview') {
                $subtitle = trans('pdfs.Movements and Transactions Overview');
            }

            App::setLocale($originalLocale);
        }

        return PdfService::generate('pdf.reports.transaction-report', [
            'vessel' => $vessel,
            'transactions' => $transactions,
            'summary' => $summary,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'title' => $title,
            'subtitle' => $subtitle,
            'enableColors' => $enableColors,
            'user' => $user,  // Pass user to PdfService
        ]);
    }
}
```

### In Controllers

Always pass the authenticated user to PDF generation methods:

```php
use App\Pdf\TransactionPdf;

class TransactionController extends Controller
{
    public function downloadPdfAll(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // ... get transactions and summary ...

        $pdf = TransactionPdf::generate(
            $vessel,
            $transactions,
            $summary,
            $period,
            $startDate,
            $endDate,
            'Transaction Report',
            'Movements and Transactions Overview',
            $enableColors,
            $user  // Pass user for translation
        );

        return $pdf->download($filename);
    }
}
```

### In PDF Blade Templates

Use Laravel's `trans()` helper in PDF Blade templates:

```blade
{{-- Period Information --}}
@if(isset($period))
    <div class="period-section">
        <p class="period-text">
            <strong>{{ trans('pdfs.Period:') }}</strong> {{ $period }}
        </p>
    </div>
@endif

{{-- Summary Section --}}
@if(isset($summary))
    <div class="summary-section">
        <h3 class="section-title">{{ trans('pdfs.Summary') }}</h3>
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <div class="summary-label">{{ trans('pdfs.Total Income') }}</div>
                    <div class="summary-value">
                        {{ MoneyAction::format($summary['total_income'] ?? 0, 2, $currency, true) }}
                    </div>
                </td>
                <!-- More cells... -->
            </tr>
        </table>
    </div>
@endif

{{-- Transactions Table --}}
<table class="transactions-table">
    <thead>
        <tr>
            <th>{{ trans('pdfs.Date') }}</th>
            <th>{{ trans('pdfs.Description') }}</th>
            <th>{{ trans('pdfs.Category') }}</th>
            <th>{{ trans('pdfs.Amount') }}</th>
            <th>{{ trans('pdfs.Type') }}</th>
        </tr>
    </thead>
    <!-- Table body... -->
</table>
```

### PdfService Automatic Translation

The `PdfService` class automatically:

1. Detects user's language from the `user` parameter in data
2. Sets the locale before rendering the view
3. Translates header/footer text (Vessel, Registration, Generated, etc.)
4. Restores the original locale after PDF generation

```php
// PdfService automatically handles:
// - Header text: "Vessel:", "Registration:", "Type:"
// - Footer text: "Generated:", "Generated by:", "Page X of Y"
// - All translations are based on user's language preference
```

### With Placeholders

PDF translations support placeholders for dynamic content:

```php
// Translation file
'Page :pageNumber of :pageCount' => 'Page :pageNumber of :pageCount',

// Usage in PdfService (handled automatically)
$pageText = str_replace([':pageNumber', ':pageCount'], [$pageNumber, $pageCount],
    trans('pdfs.Page :pageNumber of :pageCount'));
```

## HasTranslations Trait

### Methods

#### `trans(string $key, array $replace = [], ?string $locale = null): string`

Get translation using Laravel's default translation system:

```php
$this->trans('Profile updated successfully.');
$this->trans('Hello :name', ['name' => $user->name]);
$this->trans('Hello :name', ['name' => $user->name], 'pt'); // Force Portuguese
```

#### `transFrom(string $file, string $key, array $replace = [], ?string $locale = null): string`

Get translation from a specific file:

```php
// From notifications.php
$this->transFrom('notifications', 'Profile updated successfully.');

// From emails.php
$this->transFrom('emails', 'Transactions Created');

// With placeholders
$this->transFrom('notifications', "Marea ':number' has been created successfully.", [
    'number' => $marea->marea_number
]);
```

### Automatic Language Detection

The trait automatically:

1. Checks if user is authenticated
2. Gets user's language preference from `user->language`
3. Uses that language for translations
4. Falls back to app locale if no user preference

## Best Practices

### 1. Always Use English as Key

```php
// ✅ CORRECT
$this->transFrom('notifications', 'Profile updated successfully.');

// ❌ WRONG
$this->transFrom('notifications', 'profile.updated');
```

### 2. Use Descriptive Keys

```php
// ✅ CORRECT - Descriptive
"Marea ':number' has been created successfully."

// ❌ WRONG - Too generic
"Item created successfully."
```

### 3. Use Placeholders for Dynamic Content

```php
// ✅ CORRECT - Use placeholders
":type ':name' has been restored successfully."

// ❌ WRONG - Don't concatenate
$type . " '" . $name . "' has been restored successfully."
```

### 4. Organize by Purpose

```php
// ✅ CORRECT - Organized by file
$this->transFrom('notifications', 'Profile updated successfully.');
$this->transFrom('emails', 'Transactions Created');

// ❌ WRONG - All in one file
$this->transFrom('messages', 'Profile updated successfully.');
$this->transFrom('messages', 'Transactions Created');
```

### 5. Always Restore Locale in Emails

```php
public function envelope(): Envelope
{
    $originalLocale = App::getLocale();
    if ($this->user->language) {
        App::setLocale($this->user->language);
    }

    // ... translation logic ...

    // Always restore
    App::setLocale($originalLocale);

    return new Envelope(...);
}
```

### 6. Handle Exceptions with Translations

```php
try {
    // ... operation ...
    return back()->with('success',
        $this->transFrom('notifications', 'Operation completed successfully.')
    );
} catch (\Exception $e) {
    return back()->with('error',
        $this->transFrom('notifications', 'Failed to complete operation: :message', [
            'message' => $e->getMessage()
        ])
    );
}
```

## Adding New Translations

### Step-by-Step Process

1. **Add to English file first** (`lang/en/notifications.php`):

    ```php
    'New operation completed successfully.' => 'New operation completed successfully.',
    ```

2. **Add to all other language files**:

    ```php
    // lang/pt/notifications.php
    'New operation completed successfully.' => 'Nova operação concluída com sucesso.',

    // lang/es/notifications.php
    'New operation completed successfully.' => 'Nueva operación completada con éxito.',

    // lang/fr/notifications.php
    'New operation completed successfully.' => 'Nouvelle opération terminée avec succès.',
    ```

3. **Use in controller**:
    ```php
    return back()->with('success',
        $this->transFrom('notifications', 'New operation completed successfully.')
    );
    ```

## Common Patterns

### Success Messages

```php
// Simple success
return back()->with('success',
    $this->transFrom('notifications', 'Profile updated successfully.')
);

// Success with dynamic content
return back()->with('success',
    $this->transFrom('notifications', "Marea ':number' has been created successfully.", [
        'number' => $marea->marea_number
    ])
);
```

### Error Messages

```php
// Error with exception message
try {
    // ... operation ...
} catch (\Exception $e) {
    return back()->with('error',
        $this->transFrom('notifications', 'Failed to update profile: :message', [
            'message' => $e->getMessage()
        ])
    );
}

// Simple error
return back()->with('error',
    $this->transFrom('notifications', 'Failed to update language.')
);
```

### Email Subjects

```php
public function envelope(): Envelope
{
    $originalLocale = App::getLocale();
    if ($this->user->language) {
        App::setLocale($this->user->language);
    }

    $subject = $this->transFrom('emails', 'Transactions Created');

    App::setLocale($originalLocale);

    return new Envelope(
        subject: $subject . ' - ' . config('app.name'),
    );
}
```

### Email Content in Blade

```blade
{{-- Use trans() helper --}}
<h1>{{ trans('emails.Transactions Created') }}</h1>

<p>{{ trans('emails.Hello :name', ['name' => $user->name]) }}</p>

<p>{{ trans('emails.New transactions have been created for vessel :vessel', [
    'vessel' => $vessel->name
]) }}</p>
```

## Troubleshooting

### Translation Not Showing

**Problem**: English text shows instead of translation.

**Solution**:

1. Check if key exists in target language file
2. Verify user has language preference set
3. Check for typos (case-sensitive)
4. Clear config cache: `php artisan config:clear`

### User Language Not Respected

**Problem**: Translations always show in English.

**Solution**:

1. Verify user has `language` column set in database
2. Check `HasTranslations` trait is being used
3. Verify user is authenticated when trait methods are called

### Placeholders Not Replaced

**Problem**: `:placeholder` shows in translation.

**Solution**:

1. Ensure placeholder name matches exactly
2. Check replacement array is passed correctly
3. Verify placeholder syntax: `:name` not `{name}`

### Email Locale Not Working

**Problem**: Emails always in English.

**Solution**:

1. Verify `App::setLocale()` is called before translations
2. Check `App::setLocale($originalLocale)` is called after
3. Ensure user has language preference set
4. Pass locale to Blade view if needed

### PDF Not Translated

**Problem**: PDFs always show in English.

**Solution**:

1. Verify user is passed to PDF generation method: `TransactionPdf::generate(..., $user)`
2. Check user has `language` column set in database
3. Verify PDF translation file exists: `lang/{locale}/pdfs.php`
4. Ensure all text in Blade template uses `trans('pdfs.xxx')`
5. Check `PdfService` is receiving user in data array

## Quick Reference

### Controller Pattern

```php
use App\Traits\HasTranslations;

class MyController extends Controller
{
    use HasTranslations;

    public function store(Request $request)
    {
        try {
            // ... create logic ...

            return back()->with('success',
                $this->transFrom('notifications', 'Item created successfully.')
            );
        } catch (\Exception $e) {
            return back()->with('error',
                $this->transFrom('notifications', 'Failed to create item: :message', [
                    'message' => $e->getMessage()
                ])
            );
        }
    }
}
```

### Email Pattern

```php
use App\Traits\HasTranslations;
use Illuminate\Support\Facades\App;

class MyMail extends Mailable
{
    use HasTranslations;

    public function envelope(): Envelope
    {
        $originalLocale = App::getLocale();
        if ($this->user->language) {
            App::setLocale($this->user->language);
        }

        $subject = $this->transFrom('emails', 'Email Subject');

        App::setLocale($originalLocale);

        return new Envelope(subject: $subject);
    }
}
```

### Blade Template Pattern

```blade
{{ trans('emails.Email Subject') }}
{{ trans('emails.Hello :name', ['name' => $user->name]) }}
```

### PDF Pattern

```php
use App\Pdf\TransactionPdf;

class MyController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $user = $request->user();

        // ... prepare data ...

        $pdf = TransactionPdf::generate(
            $vessel,
            $transactions,
            $summary,
            $period,
            $startDate,
            $endDate,
            'Transaction Report',
            'Movements and Transactions Overview',
            false,
            $user  // Always pass user for translations
        );

        return $pdf->download('report.pdf');
    }
}
```

### PDF Blade Template Pattern

```blade
{{-- Use trans() helper for all text --}}
<h3>{{ trans('pdfs.Summary') }}</h3>
<strong>{{ trans('pdfs.Period:') }}</strong> {{ $period }}
<th>{{ trans('pdfs.Date') }}</th>
<p>{{ trans('pdfs.No transactions found for the selected period.') }}</p>
```

## Summary

- ✅ **Always use English text as the translation key**
- ✅ **Use `HasTranslations` trait in controllers and mailables**
- ✅ **Respect user's language preference automatically**
- ✅ **Organize translations by purpose (notifications, emails, pdfs, etc.)**
- ✅ **Use placeholders for dynamic content**
- ✅ **Always restore locale in email and PDF methods**
- ✅ **Handle exceptions with translated error messages**
- ✅ **Always pass user to PDF generation methods for automatic translation**

Following these patterns ensures consistent, user-friendly multilingual backend responses throughout the application, including PDFs, emails, and notifications.
