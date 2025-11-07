# Confirmation Dialog Pattern

## Overview

The `ConfirmationDialog` component provides a consistent, accessible way to confirm destructive or important actions throughout the application. It replaces browser `confirm()` dialogs with a styled, customizable modal that matches the application's design system.

## When to Use

Use `ConfirmationDialog` for:
- ✅ **Delete operations** - Removing entities (bank accounts, suppliers, crew members, etc.)
- ✅ **Destructive actions** - Actions that cannot be undone
- ✅ **Important confirmations** - Critical operations that require user confirmation
- ✅ **Bulk operations** - Actions affecting multiple items

**Never use browser `confirm()` dialogs** - Always use `ConfirmationDialog` for consistency.

## Component Import

```vue
<script setup lang="ts">
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
</script>
```

## Basic Usage Pattern

### 1. State Management

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Confirmation dialog state
const showDeleteDialog = ref(false);
const itemToDelete = ref<Item | null>(null);
const isDeleting = ref(false);
</script>
```

### 2. Delete Function

```vue
<script setup lang="ts">
const deleteItem = (item: Item) => {
    itemToDelete.value = item;
    showDeleteDialog.value = true;
};
</script>
```

### 3. Confirm Handler

```vue
<script setup lang="ts">
const confirmDelete = () => {
    if (!itemToDelete.value) return;

    const itemName = itemToDelete.value.name;
    isDeleting.value = true;

    router.delete(routeToDelete.url(itemToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            itemToDelete.value = null;
            isDeleting.value = false;
            // Show success notification
        },
        onError: () => {
            isDeleting.value = false;
            // Show error notification
        },
    });
};
</script>
```

### 4. Cancel Handler

```vue
<script setup lang="ts">
const cancelDelete = () => {
    showDeleteDialog.value = false;
    itemToDelete.value = null;
    isDeleting.value = false;
};
</script>
```

### 5. Component Template

```vue
<template>
    <!-- Confirmation Dialog -->
    <ConfirmationDialog
        v-model:open="showDeleteDialog"
        title="Delete Item"
        description="This action cannot be undone."
        :message="`Are you sure you want to delete '${itemToDelete?.name}'? This will permanently remove the item and all its data.`"
        confirm-text="Delete Item"
        cancel-text="Cancel"
        variant="destructive"
        type="danger"
        :loading="isDeleting"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
    />
</template>
```

## Complete Example: Bank Account Deletion

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useNotifications } from '@/composables/useNotifications';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import bankAccounts from '@/routes/panel/bank-accounts';

interface BankAccount {
    id: number;
    name: string;
    bank_name: string;
}

const { addNotification } = useNotifications();

// Confirmation dialog state
const showDeleteDialog = ref(false);
const bankAccountToDelete = ref<BankAccount | null>(null);
const isDeleting = ref(false);

// Delete function - triggered from action button
const deleteBankAccount = (bankAccount: BankAccount) => {
    bankAccountToDelete.value = bankAccount;
    showDeleteDialog.value = true;
};

// Confirm deletion
const confirmDelete = () => {
    if (!bankAccountToDelete.value) return;

    const bankAccountName = bankAccountToDelete.value.name;
    isDeleting.value = true;

    router.delete(bankAccounts.destroy.url({ 
        vessel: getCurrentVesselId(), 
        bankAccount: bankAccountToDelete.value.id 
    }), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            bankAccountToDelete.value = null;
            isDeleting.value = false;
            addNotification({
                type: 'success',
                message: `Bank account '${bankAccountName}' has been deleted successfully.`,
            });
        },
        onError: () => {
            isDeleting.value = false;
            addNotification({
                type: 'error',
                message: 'Failed to delete bank account. Please try again.',
            });
        },
    });
};

// Cancel deletion
const cancelDelete = () => {
    showDeleteDialog.value = false;
    bankAccountToDelete.value = null;
    isDeleting.value = false;
};
</script>

<template>
    <!-- Your page content -->
    
    <!-- Confirmation Dialog -->
    <ConfirmationDialog
        v-model:open="showDeleteDialog"
        title="Delete Bank Account"
        description="This action cannot be undone."
        :message="`Are you sure you want to delete the bank account '${bankAccountToDelete?.name}'? This will permanently remove the bank account and all its data.`"
        confirm-text="Delete Bank Account"
        cancel-text="Cancel"
        variant="destructive"
        type="danger"
        :loading="isDeleting"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
    />
</template>
```

## Component Props

### Required Props

- `open: boolean` - Controls dialog visibility (use `v-model:open` for two-way binding)
- `title: string` - Dialog title (e.g., "Delete Bank Account")
- `description: string` - Short description (e.g., "This action cannot be undone.")

### Optional Props

- `message?: string` - Additional message with details (supports template strings)
- `confirmText?: string` - Text for confirm button (default: "Confirm")
- `cancelText?: string` - Text for cancel button (default: "Cancel")
- `variant?: string` - Button variant for confirm button (default: "default")
  - Options: `'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'`
- `type?: DialogType` - Visual type of dialog (default: "info")
  - Options: `'info' | 'warning' | 'danger'`
- `loading?: boolean` - Shows loading state on confirm button (default: false)
- `size?: string` - Dialog size (default: "md")
  - Options: `'sm' | 'md' | 'lg'`

## Dialog Types

### Danger (for destructive actions)

```vue
<ConfirmationDialog
    v-model:open="showDeleteDialog"
    title="Delete Item"
    description="This action cannot be undone."
    :message="`Are you sure you want to delete '${item?.name}'?`"
    confirm-text="Delete"
    variant="destructive"
    type="danger"
    :loading="isDeleting"
    @confirm="confirmDelete"
    @cancel="cancelDelete"
/>
```

### Warning (for important but non-destructive actions)

```vue
<ConfirmationDialog
    v-model:open="showWarningDialog"
    title="Archive Item"
    description="This item will be archived."
    :message="`Are you sure you want to archive '${item?.name}'?`"
    confirm-text="Archive"
    variant="default"
    type="warning"
    :loading="isArchiving"
    @confirm="confirmArchive"
    @cancel="cancelArchive"
/>
```

### Info (for informational confirmations)

```vue
<ConfirmationDialog
    v-model:open="showInfoDialog"
    title="Confirm Action"
    description="Please confirm this action."
    :message="`Do you want to proceed with this action?`"
    confirm-text="Confirm"
    variant="default"
    type="info"
    @confirm="confirmAction"
    @cancel="cancelAction"
/>
```

## Events

- `@confirm` - Emitted when user clicks confirm button
- `@cancel` - Emitted when user clicks cancel button or closes dialog
- `@update:open` - Emitted when dialog open state changes (for v-model binding)

## Best Practices

### 1. Always Store Item Reference

```vue
// ✅ Good - Store reference before showing dialog
const deleteItem = (item: Item) => {
    itemToDelete.value = item;
    showDeleteDialog.value = true;
};

// ❌ Bad - Don't pass item directly in template
// This can cause issues if item changes
```

### 2. Save Item Name Before Deletion

```vue
// ✅ Good - Save name before nullifying reference
const confirmDelete = () => {
    if (!itemToDelete.value) return;
    
    const itemName = itemToDelete.value.name; // Save before deletion
    isDeleting.value = true;
    
    router.delete(route.url(itemToDelete.value.id), {
        onSuccess: () => {
            itemToDelete.value = null; // Clear after success
            // Use saved itemName in notification
        },
    });
};
```

### 3. Always Reset State on Cancel

```vue
// ✅ Good - Reset all state
const cancelDelete = () => {
    showDeleteDialog.value = false;
    itemToDelete.value = null;
    isDeleting.value = false;
};
```

### 4. Handle Loading State

```vue
// ✅ Good - Show loading during operation
const confirmDelete = () => {
    isDeleting.value = true;
    
    router.delete(route.url(itemToDelete.value.id), {
        onSuccess: () => {
            isDeleting.value = false; // Reset on success
        },
        onError: () => {
            isDeleting.value = false; // Reset on error
        },
    });
};
```

### 5. Use Descriptive Messages

```vue
// ✅ Good - Clear, specific message
:message="`Are you sure you want to delete the bank account '${bankAccount?.name}'? This will permanently remove the bank account and all its data.`"

// ❌ Bad - Vague message
:message="`Are you sure?`"
```

## Integration with DataTable Actions

```vue
<script setup lang="ts">
const actions = computed(() => {
    const actionItems = [];

    if (canDelete('bank-accounts')) {
        actionItems.push({
            label: 'Delete Bank Account',
            icon: 'trash-2',
            variant: 'destructive' as const,
            onClick: (item: BankAccount) => deleteBankAccount(item),
        });
    }

    return actionItems;
});
</script>

<template>
    <DataTable
        :columns="columns"
        :data="data"
        :actions="actions"
    />
</template>
```

## Common Mistakes to Avoid

### ❌ Don't Use Browser confirm()

```vue
// ❌ BAD - Browser confirm dialog
const deleteItem = (item: Item) => {
    if (confirm('Are you sure?')) {
        router.delete(route.url(item.id));
    }
};
```

### ❌ Don't Forget to Reset State

```vue
// ❌ BAD - Missing state reset
const cancelDelete = () => {
    showDeleteDialog.value = false;
    // Missing: itemToDelete.value = null;
    // Missing: isDeleting.value = false;
};
```

### ❌ Don't Access Item After Nullifying

```vue
// ❌ BAD - Accessing after null
const confirmDelete = () => {
    router.delete(route.url(itemToDelete.value.id), {
        onSuccess: () => {
            itemToDelete.value = null;
            // Error: itemToDelete.value.name is now null
            addNotification({ message: `Deleted ${itemToDelete.value.name}` });
        },
    });
};
```

### ✅ Do Save Values Before Nullifying

```vue
// ✅ GOOD - Save before nullifying
const confirmDelete = () => {
    const itemName = itemToDelete.value.name;
    router.delete(route.url(itemToDelete.value.id), {
        onSuccess: () => {
            itemToDelete.value = null;
            addNotification({ message: `Deleted ${itemName}` });
        },
    });
};
```

## Testing Checklist

When implementing confirmation dialogs, verify:

- [ ] Dialog opens when delete action is triggered
- [ ] Dialog shows correct title and message
- [ ] Cancel button closes dialog without action
- [ ] Confirm button triggers deletion
- [ ] Loading state shows during deletion
- [ ] Success notification appears after deletion
- [ ] Error notification appears on failure
- [ ] Dialog state resets after cancel
- [ ] Dialog state resets after success
- [ ] Dialog state resets after error
- [ ] Item reference is cleared after operations

## Summary

The `ConfirmationDialog` component provides a consistent, accessible way to handle confirmations throughout the application. Always use it instead of browser `confirm()` dialogs for:

- Better UX with styled, consistent dialogs
- Accessibility support
- Loading states
- Customizable messaging
- Design system integration

Remember to:
1. Store item reference before showing dialog
2. Save item name before deletion
3. Reset all state on cancel/success/error
4. Handle loading states properly
5. Use descriptive, specific messages

