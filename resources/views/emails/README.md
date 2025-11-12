# Email Templates

This directory contains the email template system for the Vessel Management System. All emails use a consistent, formal, and professional design with a clean and minimal aesthetic.

## 📁 Structure

```
emails/
├── layouts/
│   └── default.blade.php         # Main email layout template
├── partials/
│   ├── header.blade.php          # Email header with logo and branding
│   └── footer.blade.php          # Email footer with links and copyright
├── notifications/
│   ├── transaction-created.blade.php   # Transaction created notification
│   ├── transaction-deleted.blade.php   # Transaction deleted notification
│   ├── marea-started.blade.php         # Marea started notification
│   └── marea-completed.blade.php       # Marea completed notification
├── test.blade.php                # Test email view (for preview)
├── example.blade.php             # Example template (boilerplate)
├── notification.blade.php        # Base notification template
├── notification-example.blade.php # Notification template example (for preview)
└── README.md                     # This file
```

## 🎨 Design Features

- **Formal & Professional**: Clean, minimal design with subtle grays and borders
- **Simple Header**: Left-aligned logo and company name with subtle border
- **Responsive Design**: Works on all email clients and screen sizes
- **Table-based Layout**: Maximum email client compatibility
- **Inline CSS**: All styles are inline for proper email rendering
- **Logo Placeholder**: Ship icon (🚢) in header - can be replaced with actual logo
- **Clean Typography**: Modern, readable font stack with professional color scheme

## 📝 Usage

### Basic Email Template

Create a new email view by extending the default layout:

```blade
@extends('emails.layouts.default')

@section('content')
    <!-- Your email content here -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td>
                <h2 style="margin: 0; padding: 0; font-size: 24px; font-weight: 600; color: #1f2937;">
                    Your Title
                </h2>
            </td>
        </tr>
    </table>
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td>
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                    Your email body content here.
                </p>
            </td>
        </tr>
    </table>
@endsection
```

### Using the Example Template

The `example.blade.php` template provides a simple boilerplate:

```php
return view('emails.example', [
    'title' => 'Welcome to Vessel Management',
    'body' => 'Thank you for joining our platform...',
    'buttonText' => 'Get Started',
    'buttonUrl' => route('panel.index'),
]);
```

### Using the Notification Template

The `notification.blade.php` template is designed for system notifications (user actions, updates, etc.):

```php
return view('emails.notification', [
    'title' => 'Transaction Created',
    'subtitle' => 'A new transaction has been added to the system',
    'message' => 'Hello John Doe, A new transaction has been created in the system.',
    'icon' => '✅', // Optional: emoji or icon
    'details' => [
        'Transaction Number' => 'TRX-2025-001234',
        'Type' => 'Income',
        'Amount' => '€1,250.00',
        'Created By' => 'Jane Smith',
    ],
    'actionUrl' => route('panel.transactions.show', $transactionId), // Optional
    'actionText' => 'View Transaction', // Optional
    'timestamp' => now()->format('F j, Y \a\t g:i A'), // Optional
]);
```

**Notification Template Features:**
- Icon/emoji support for visual context
- Structured details section with key-value pairs
- Optional action button
- Automatic timestamp
- Professional, formal design

### Sending Emails

In the application, use the Mail facade or Mailable class:

```php
use Illuminate\Support\Facades\Mail;

Mail::send('emails.example', [
    'title' => 'Welcome!',
    'body' => 'Your account has been created.',
    'buttonText' => 'Login',
    'buttonUrl' => route('login'),
], function ($message) {
    $message->to('user@example.com')
            ->subject('Welcome to Vessel Management System');
});
```

Or create a Mailable class:

```php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class WelcomeEmail extends Mailable
{
    public function build()
    {
        return $this->subject('Welcome to Vessel Management System')
                    ->view('emails.example', [
                        'title' => 'Welcome!',
                        'body' => 'Your account has been created.',
                        'buttonText' => 'Login',
                        'buttonUrl' => route('login'),
                    ]);
    }
}
```

## 🎨 Color Palette

- **Primary Dark**: `#111827` (headings, buttons, important text)
- **Text Dark**: `#374151` (body text)
- **Text Gray**: `#6b7280` (secondary text, labels)
- **Text Light**: `#9ca3af` (tertiary text, timestamps)
- **Background**: `#f3f4f6` (email background)
- **Card Background**: `#ffffff` (email content area)
- **Border**: `#e5e7eb` (dividers, borders)
- **Light Background**: `#f9fafb` (highlighted sections)

## 🧪 Testing

To preview email templates in development:

1. **Default Template**: Visit `/panel/email-test` (only available in local/testing environment)
2. **Notification Template**: Visit `/panel/email-notification-test` (only available in local/testing environment)
3. Or use a tool like Mailtrap for testing
4. Or use the application's mail testing features

## 📋 Best Practices

1. **Always use table-based layouts** for email compatibility
2. **Use inline CSS** - email clients strip `<style>` tags
3. **Test in multiple email clients** (Gmail, Outlook, Apple Mail, etc.)
4. **Keep width under 600px** for best compatibility
5. **Use web-safe fonts** or provide fallbacks
6. **Include alt text** for images
7. **Use the example template** as a starting point
8. **Follow the color scheme** for brand consistency

## 🔧 Customization

### Changing Colors

Edit the color values in:
- `layouts/default.blade.php` - Main layout colors
- `partials/header.blade.php` - Header gradient colors
- `partials/footer.blade.php` - Footer text colors

### Adding a Logo

Replace the emoji icon in `partials/header.blade.php`:

```blade
<img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 64px; height: 64px;">
```

### Modifying Footer Links

Edit `partials/footer.blade.php` to update footer links and text.

## 📱 Responsive Design

The email template is designed to be responsive:
- Maximum width: 600px
- Padding adjusts for mobile devices
- Text scales appropriately
- Buttons are touch-friendly

## 🚀 Example Use Cases

### General Emails (use `example.blade.php` or `test.blade.php`)
- Welcome emails
- Password reset emails
- Invoice emails
- General announcements

### System Notifications (use `notification.blade.php` or specific templates)
- **Transaction Created**: `notifications/transaction-created.blade.php`
- **Transaction Deleted**: `notifications/transaction-deleted.blade.php`
- **Marea Started**: `notifications/marea-started.blade.php`
- **Marea Completed**: `notifications/marea-completed.blade.php`
- Crew member added/updated/removed (future)
- Vessel settings changed (future)
- Report generation notifications (future)
- System alerts and updates (future)
- User action notifications (future)
- Audit log notifications (future)

---

For questions or issues, refer to the main documentation or contact the development team.

