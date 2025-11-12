# Email Patterns

This document defines the email template patterns and conventions for the Vessel Management System. All emails follow a clean, minimal, and professional design inspired by modern email standards.

## 📁 Email Template Structure

```
resources/views/emails/
├── layouts/
│   └── default.blade.php         # Main email layout (clean, minimal)
├── partials/
│   ├── header.blade.php          # Simple header with logo
│   └── footer.blade.php          # Simple footer with disclaimer
├── notification.blade.php        # Base notification template
├── example.blade.php             # Example template (boilerplate)
├── test.blade.php                # Test template (for preview)
├── notifications/
│   ├── transaction-created.blade.php
│   ├── transaction-deleted.blade.php
│   ├── marea-started.blade.php
│   └── marea-completed.blade.php
└── README.md                     # Email templates documentation
```

## 🎨 Design Principles

### 1. Clean & Minimal
- **No box shadows**: Clean white background
- **No borders**: Except for detail sections
- **Ample white space**: 60px vertical padding, 40px horizontal
- **Centered content**: All content centered with max-width constraints

### 2. Typography
- **Large, bold titles**: 28px, font-weight 700, centered
- **Body text**: 16px, centered, max-width 500px
- **Details labels**: 12px, uppercase, gray color
- **Details values**: 15px, dark color

### 3. Color Palette
- **Background**: White (#ffffff)
- **Text Dark**: #111827 (headings, important text)
- **Text Gray**: #374151 (body text)
- **Text Light**: #6b7280 (secondary text, labels)
- **Button**: #111827 (dark button with white text)
- **Detail Box**: #f9fafb (light gray background)

### 4. Layout Structure
```
┌─────────────────────────────────┐
│  Header (Logo + Company Name)   │
├─────────────────────────────────┤
│                                 │
│      Large Centered Title       │
│                                 │
│    Centered Body Text (max      │
│         width 500px)            │
│                                 │
│    [Details Box - Optional]     │
│                                 │
│      [Action Button - Optional] │
│                                 │
│      [Note - Optional]          │
│                                 │
├─────────────────────────────────┤
│  Footer (Logo + Disclaimer)     │
└─────────────────────────────────┘
```

## 📝 Template Patterns

### Base Notification Template

All notification emails extend the `notification.blade.php` template which provides:

- **Title**: Large, centered, bold
- **Message**: Centered body text
- **Details Section**: Optional key-value pairs in a light gray box
- **Action Button**: Optional call-to-action button
- **Note**: Optional note/important information

### Basic Email Template

For general emails, use `example.blade.php` which provides:

- **Title**: Large, centered, bold
- **Body**: Centered text content
- **Button**: Optional call-to-action
- **Note**: Optional note

## 🔧 Usage Patterns

### 1. Transaction Created Notification

```php
return view('emails.notifications.transaction-created', [
    'transaction' => $transaction,
    'user' => $user,
    'vessel' => $vessel,
    'actionUrl' => route('panel.transactions.show', $transaction->id),
]);
```

**Template Variables:**
- `transaction`: Transaction model
- `user`: User who created the transaction
- `vessel`: Vessel associated with the transaction
- `actionUrl`: URL to view the transaction
- `actionText`: Button text (default: "Ver Transação")

### 2. Transaction Deleted Notification

```php
return view('emails.notifications.transaction-deleted', [
    'transactionNumber' => $transactionNumber,
    'transactionType' => $transactionType,
    'amount' => $amount,
    'user' => $user,
    'vessel' => $vessel,
    'deletedAt' => $deletedAt,
]);
```

**Template Variables:**
- `transactionNumber`: Transaction number/ID
- `transactionType`: Type of transaction (Income/Expense)
- `amount`: Transaction amount
- `user`: User who deleted the transaction
- `vessel`: Vessel associated with the transaction
- `deletedAt`: When the transaction was deleted

### 3. Marea Started Notification

```php
return view('emails.notifications.marea-started', [
    'marea' => $marea,
    'vessel' => $vessel,
    'user' => $user,
    'actionUrl' => route('panel.mareas.show', $marea->id),
]);
```

**Template Variables:**
- `marea`: Marea model
- `vessel`: Vessel going to marea
- `user`: User who started the marea
- `startedAt`: When the marea started
- `actionUrl`: URL to view the marea
- `actionText`: Button text (default: "Ver Marea")

### 4. Marea Completed Notification

```php
return view('emails.notifications.marea-completed', [
    'marea' => $marea,
    'vessel' => $vessel,
    'user' => $user,
    'returnedAt' => $returnedAt,
    'actionUrl' => route('panel.mareas.show', $marea->id),
]);
```

**Template Variables:**
- `marea`: Marea model
- `vessel`: Vessel that returned
- `user`: User who marked the marea as completed
- `returnedAt`: When the vessel returned
- `actionUrl`: URL to view the marea
- `actionText`: Button text (default: "Ver Marea")

## 📋 Template Structure

### Notification Email Structure

```blade
@extends('emails.layouts.default')

@section('content')
    <!-- Main Title -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 32px;">
                <h1 style="margin: 0; padding: 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; line-height: 1.2; text-align: center;">
                    {{ $title }}
                </h1>
            </td>
        </tr>
    </table>
    
    <!-- Main Content -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding-bottom: 24px;">
                <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6; text-align: center; max-width: 500px;">
                    {{ $message }}
                </p>
            </td>
        </tr>
    </table>
    
    <!-- Details Section -->
    @if(isset($details) && count($details) > 0)
    <!-- Details box -->
    @endif
    
    <!-- Action Button -->
    @if(isset($actionUrl))
    <!-- Button -->
    @endif
    
    <!-- Note -->
    @if(isset($note))
    <!-- Note -->
    @endif
@endsection
```

## 🎯 Best Practices

### 1. Content Guidelines
- **Keep titles concise**: Maximum 5-7 words
- **Use clear language**: Simple, direct Portuguese
- **Include key information**: Always show what, who, when
- **Provide context**: Explain why the notification was sent

### 2. Detail Sections
- **Use key-value pairs**: Label (uppercase) + Value
- **Limit to 4-5 items**: Don't overload with information
- **Most important first**: Put critical info at the top
- **Use consistent labels**: Same terminology across emails

### 3. Action Buttons
- **Clear call-to-action**: Specific action text
- **Direct URL**: Link directly to the relevant page
- **Only when needed**: Don't add buttons unnecessarily

### 4. Notes
- **Important information**: Use for warnings, time limits, etc.
- **Keep it short**: One or two sentences maximum
- **Use bold for emphasis**: Highlight critical parts

### 5. Portuguese Language
- **Use formal Portuguese**: Professional tone
- **Consistent terminology**: Use same terms as the system
- **Proper formatting**: Portuguese date/time formats
- **Currency formatting**: Use Portuguese format (€1.250,00)

## 📧 Email Types

### Transaction Emails
- **Transaction Created**: Sent when a transaction is created
- **Transaction Deleted**: Sent when a transaction is deleted
- **Transaction Updated**: (Future) Sent when a transaction is updated

### Marea Emails
- **Marea Started**: Sent when a vessel goes to marea
- **Marea Completed**: Sent when a vessel returns from marea
- **Marea Closed**: (Future) Sent when a marea is closed

### System Emails
- **Welcome Email**: (Future) Sent to new users
- **Password Reset**: (Future) Sent for password reset
- **Account Activity**: (Future) Sent for account changes

## 🔄 Implementation Workflow

### Step 1: Create Template
1. Create template file in `resources/views/emails/notifications/`
2. Extend `emails.layouts.default`
3. Follow the notification pattern structure
4. Use Portuguese for all text

### Step 2: Define Variables
1. Document all required variables
2. Document all optional variables
3. Provide default values where appropriate
4. Include type hints in comments

### Step 3: Create Mailable Class
1. Create Mailable class in `app/Mail/`
2. Set subject line
3. Pass required data to view
4. Set recipient(s)

### Step 4: Integrate in Controllers
1. Create email after action
2. Queue email for background sending
3. Handle errors gracefully
4. Log email sending

### Step 5: Test
1. Test in development environment
2. Check all email clients
3. Verify Portuguese text
4. Test with real data

## ✅ Checklist

When creating a new email template:

- [ ] Template follows the clean, minimal design
- [ ] Content is centered with max-width 500px
- [ ] Title is large, bold, and centered
- [ ] Details section uses light gray background
- [ ] Button is dark with white text
- [ ] All text is in Portuguese
- [ ] Currency and dates use Portuguese format
- [ ] Template extends `emails.layouts.default`
- [ ] All variables are documented
- [ ] Template is tested in email clients
- [ ] Template matches the design system

## 📚 Examples

See the following files for reference:
- `resources/views/emails/test.blade.php` - General email example
- `resources/views/emails/notification-example.blade.php` - Notification example
- `resources/views/emails/example.blade.php` - Basic template

---

This pattern ensures consistency, professionalism, and a great user experience across all email communications in the Vessel Management System.

