# Email Notification Patterns

## Overview

The email notification system allows users with high-level vessel access to receive email notifications when important changes occur in the system (transactions created/deleted, mareas started/completed). Notifications are grouped to prevent email spam.

## Features

- **User-controlled**: Users can enable/disable notifications in their profile settings
- **Permission-based**: Only users with high-level vessel access can enable notifications
- **Grouped notifications**: Multiple notifications are grouped together (last 3 of each type) to prevent spam
- **Action exclusion**: Users don't receive notifications for actions they perform themselves
- **Delayed sending**: Notifications are sent after a 2-minute delay to allow grouping

## Database Schema

### Users Table
- `vessel_admin_notification` (boolean): Whether the user wants to receive email notifications

### Email Notifications Table
- `user_id`: User who should receive the notification
- `vessel_id`: Vessel associated with the notification
- `type`: Notification type (transaction_created, transaction_deleted, marea_started, marea_completed)
- `subject_type`: Model class name (Transaction, Marea)
- `subject_id`: Model ID
- `subject_data`: JSON snapshot of subject data at time of notification
- `action_by_user_id`: User who performed the action (excluded from notifications)
- `sent_at`: When the notification was sent
- `grouped_at`: When the notification was grouped with others
- `is_grouped`: Whether this notification is part of a group
- `group_id`: Group ID for grouped notifications

## Permission Requirements

Users must have high-level vessel access to enable notifications. High-level access is determined by checking if the user has any of the following permissions for a vessel:

- `transactions.view`
- `transactions.create`
- `transactions.edit`
- `mareas.view`
- `mareas.create`
- `mareas.edit`
- `reports.access`

Based on `config/permissions.php`, the following roles have high-level access:
- **Administrator**: All permissions
- **Supervisor**: Most permissions (transactions, mareas, reports)
- **Moderator**: Some permissions (transactions.view, mareas.view)
- **Normal User**: No high-level access (cannot enable notifications)

## Notification Types

### Transaction Notifications

#### transaction_created
- Triggered when: A transaction is created
- Excludes: The user who created the transaction
- Data captured: Transaction number, type, amount, currency, description, category

#### transaction_deleted
- Triggered when: A transaction is deleted
- Excludes: The user who deleted the transaction
- Data captured: Transaction number, type, amount, currency, description

### Marea Notifications

#### marea_started
- Triggered when: A marea is marked as "at sea"
- Excludes: The user who marked the marea as at sea
- Data captured: Marea number, name, started date, expected return date

#### marea_completed
- Triggered when: A marea is marked as "returned"
- Excludes: The user who marked the marea as returned
- Data captured: Marea number, name, started date, returned date

## Implementation

### Service: EmailNotificationService

The service handles creating notification records and dispatching jobs:

```php
EmailNotificationService::createNotification(
    type: 'transaction_created',
    subjectType: Transaction::class,
    subjectId: $transaction->id,
    vesselId: $vesselId,
    actionByUserId: $user->id,
    subjectData: [
        'transaction_number' => $transaction->transaction_number,
        'type' => $transaction->type,
        'amount' => $transaction->total_amount,
        // ... other data
    ]
);
```

### Job: SendGroupedEmailNotifications

The job processes pending notifications and sends grouped emails:

1. Gets all users who should receive notifications for the vessel
2. Groups pending notifications by type (last 5 minutes)
3. Limits to last 3 notifications of each type
4. Marks notifications as grouped
5. Sends grouped email
6. Marks notifications as sent

### Mailable: GroupedVesselNotificationMail

The mailable class handles email rendering:

- Determines the correct email template based on notification type
- Passes user, vessel (with name), notifications, and count to the template
- Sets appropriate subject line with app name (Bindamy Mareas)
- Ensures vessel name is loaded and available in templates

**Subject Format**: `{Notification Type} - Bindamy Mareas`
- Example: "Transações Criadas - Bindamy Mareas"
- Example: "Mareas Iniciadas (2 itens) - Bindamy Mareas"

### Email Templates

Templates are located in `resources/views/emails/notifications/`:

- `grouped-transactions-created.blade.php` - Shows created transactions with vessel name
- `grouped-transactions-deleted.blade.php` - Shows deleted transactions with vessel name
- `grouped-mareas-started.blade.php` - Shows started mareas with vessel name
- `grouped-mareas-completed.blade.php` - Shows completed mareas with vessel name
- `grouped-default.blade.php` - Fallback template with vessel name

**Important**: All email templates display the vessel name prominently. The vessel name is loaded from the database based on the `vessel_id` and displayed in a dedicated section of the email.

## Usage in Controllers

### TransactionController

```php
// After creating a transaction
EmailNotificationService::createNotification(
    type: 'transaction_created',
    subjectType: Transaction::class,
    subjectId: $transaction->id,
    vesselId: $vesselId,
    actionByUserId: $user->id,
    subjectData: [...]
);

// Before deleting a transaction (to capture data)
EmailNotificationService::createNotification(
    type: 'transaction_deleted',
    subjectType: Transaction::class,
    subjectId: $transaction->id,
    vesselId: $vesselId,
    actionByUserId: $user->id,
    subjectData: [...]
);
```

### MareaController

```php
// After marking marea as at sea
EmailNotificationService::createNotification(
    type: 'marea_started',
    subjectType: Marea::class,
    subjectId: $marea->id,
    vesselId: $vesselId,
    actionByUserId: $user->id,
    subjectData: [...]
);

// After marking marea as returned
EmailNotificationService::createNotification(
    type: 'marea_completed',
    subjectType: Marea::class,
    subjectId: $marea->id,
    vesselId: $vesselId,
    actionByUserId: $user->id,
    subjectData: [...]
);
```

## User Interface

### Profile Settings

Users with high-level vessel access can enable/disable notifications in their profile settings:

1. Navigate to Profile Settings (`/panel/profile`)
2. Find "Notificações de Administração" section
3. Toggle the switch to enable/disable notifications
4. Save changes

The toggle is only visible if the user has high-level access to at least one vessel.

## Queue Configuration

Notifications are processed via a queue job. Make sure to:

1. Configure a queue driver (Redis, database, etc.)
2. Run queue workers: `php artisan queue:work --queue=emails`
3. For production, use a process manager like Supervisor

## Testing

To test email notifications:

1. Enable notifications for a test user
2. Create/delete transactions or change marea status
3. Wait 2 minutes for the job to process
4. Check the email inbox for grouped notifications

## Best Practices

1. **Always capture data before deletion**: For deleted transactions, capture data before the transaction is deleted
2. **Error handling**: Wrap notification creation in try-catch blocks to prevent failures from affecting main operations
3. **Logging**: Log errors for debugging but don't fail the main operation
4. **Grouping**: Use the 2-minute delay to group notifications and prevent spam
5. **Limit notifications**: Only show last 3 notifications of each type in grouped emails
6. **Vessel name display**: Always show vessel name clearly in email templates for context
7. **App name consistency**: Use `config('app.name', 'Bindamy Mareas')` in all templates, never hardcode "Laravel"
8. **Template structure**: Follow the established template structure with header, content, and footer

## Future Enhancements

- Real-time notifications (web sockets)
- Notification preferences per vessel
- Notification history in the UI
- Email templates customization
- Notification frequency settings (immediate, daily digest, weekly digest)

