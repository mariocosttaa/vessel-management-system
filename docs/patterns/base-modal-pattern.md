# BaseModal Pattern

## Overview

The `BaseModal.vue` component provides a unified, reusable modal system that handles common modal functionality including loading states, API requests, error handling, and lazy loading. This pattern eliminates code duplication across different modal types and provides a consistent user experience.

## Key Features

- **Unified Loading States**: Automatic loading indicators with blur effects
- **API Request Integration**: Built-in support for making API requests when modal opens
- **Error Handling**: Graceful error states with retry functionality
- **Lazy Loading**: Optional automatic data fetching when modal opens
- **Flexible Content**: Slot-based content system with data passing
- **Consistent UI**: Standardized modal structure and behavior

## BaseModal Props

### Core Props
```typescript
interface Props {
    open: boolean;                    // Modal visibility state
    title: string;                    // Modal title
    description?: string;             // Optional description
    size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';  // Modal size
    showCloseButton?: boolean;        // Show close button (default: true)
    showCancelButton?: boolean;       // Show cancel button (default: true)
    showConfirmButton?: boolean;      // Show confirm button (default: true)
    confirmText?: string;             // Confirm button text (default: 'Save')
    cancelText?: string;              // Cancel button text (default: 'Cancel')
    loading?: boolean;                // External loading state
    disabled?: boolean;              // Disable confirm button
}
```

### API Request Props
```typescript
interface Props {
    apiUrl?: string;                  // API endpoint URL
    apiMethod?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';  // HTTP method
    apiData?: any;                   // Request body data
    enableLazyLoading?: boolean;     // Auto-fetch data when modal opens
    retryOnError?: boolean;          // Show retry button on error (default: true)
}
```

## BaseModal Events

```typescript
interface Events {
    'update:open': [value: boolean];  // Modal visibility change
    'confirm': [];                    // Confirm button clicked
    'cancel': [];                    // Cancel button clicked
    'close': [];                     // Modal closed
    'data-loaded': [data: any];      // API data loaded successfully
    'error': [error: string];        // API request failed
}
```

## Usage Patterns

### 1. Simple Modal (No API Request)

```vue
<template>
    <BaseModal
        :open="showModal"
        title="Confirm Action"
        description="Are you sure you want to proceed?"
        @close="showModal = false"
        @confirm="handleConfirm"
    >
        <template #default>
            <p>This is a simple confirmation modal.</p>
        </template>
    </BaseModal>
</template>
```

### 2. Show Modal with API Request

```vue
<template>
    <BaseModal
        :open="showModal"
        title="Entity Details"
        size="2xl"
        :api-url="`/api/entities/${entityId}/details`"
        :enable-lazy-loading="true"
        :show-cancel-button="false"
        :show-confirm-button="false"
        @close="showModal = false"
        @data-loaded="handleDataLoaded"
        @error="handleError"
    >
        <template #default="{ data }">
            <div v-if="data?.entity" class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-500">Name</label>
                    <p class="text-lg">{{ data.entity.name }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Status</label>
                    <Badge :variant="getStatusVariant(data.entity.status)">
                        {{ data.entity.status_label }}
                    </Badge>
                </div>
            </div>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
const handleDataLoaded = (data: any) => {
    console.log('Data loaded:', data);
};

const handleError = (error: string) => {
    console.error('Error:', error);
};
</script>
```

### 3. Edit Modal with API Request

```vue
<template>
    <BaseModal
        :open="showModal"
        title="Edit Entity"
        size="xl"
        :api-url="`/api/entities/${entityId}/details`"
        :enable-lazy-loading="true"
        confirm-text="Update"
        @close="showModal = false"
        @confirm="handleSubmit"
        @data-loaded="populateForm"
    >
        <template #default="{ data, loading }">
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        :disabled="loading"
                    />
                </div>
                <div>
                    <Label for="status">Status</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        :disabled="loading"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </form>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    status: 'active',
});

const populateForm = (data: any) => {
    if (data?.entity) {
        form.name = data.entity.name;
        form.status = data.entity.status;
    }
};

const handleSubmit = () => {
    form.put(`/entities/${entityId}`, {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};
</script>
```

### 4. Create Modal (No API Request)

```vue
<template>
    <BaseModal
        :open="showModal"
        title="Create Entity"
        size="lg"
        confirm-text="Create"
        @close="showModal = false"
        @confirm="handleSubmit"
    >
        <template #default>
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                    />
                </div>
            </form>
        </template>
    </BaseModal>
</template>
```

## Slot Props

The default slot receives the following props:

```typescript
interface SlotProps {
    data: any;        // API response data (null if no API request)
    loading: boolean; // Combined loading state (prop + API loading)
}
```

## Loading States

### 1. API Loading State
When `enableLazyLoading` is true and an API request is in progress:
- Shows spinner with "Loading data..." message
- Disables all form interactions
- Blurs content area

### 2. Error State
When API request fails:
- Shows error message
- Displays "Try Again" button (if `retryOnError` is true)
- Allows retry of failed request

### 3. Combined Loading State
The `loading` prop combines:
- External loading state (from parent component)
- Internal API loading state
- Used to disable buttons and blur content

## API Request Configuration

### Authentication
BaseModal automatically includes:
- CSRF token from meta tag
- Proper headers for Laravel requests
- Credentials for same-origin requests

### Request Options
```typescript
const requestOptions: RequestInit = {
    method: props.apiMethod,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    credentials: 'same-origin',
};
```

## Best Practices

### 1. Use Appropriate Sizes
- `sm`: Simple confirmations
- `md`: Standard forms
- `lg`: Complex forms
- `xl`: Forms with multiple sections
- `2xl`: Show modals with detailed information

### 2. Handle Loading States
```vue
<template #default="{ data, loading }">
    <form :class="{ 'pointer-events-none': loading }">
        <!-- Form content -->
    </form>
</template>
```

### 3. Error Handling
```vue
<script setup lang="ts">
const handleError = (error: string) => {
    // Log error for debugging
    console.error('Modal API error:', error);
    
    // Show user-friendly notification
    addNotification({
        type: 'error',
        message: 'Failed to load data. Please try again.',
    });
};
</script>
```

### 4. Data Population
```vue
<script setup lang="ts">
const populateForm = (data: any) => {
    if (data?.entity) {
        // Always check if data exists
        form.name = data.entity.name;
        form.status = data.entity.status;
        form.clearErrors(); // Clear any previous errors
    }
};
</script>
```

## Migration from Custom Modals

### Before (Custom Modal)
```vue
<script setup lang="ts">
import { ref, watch } from 'vue';

const loading = ref(false);
const error = ref<string | null>(null);
const data = ref(null);

const fetchData = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await fetch(`/api/entities/${id}/details`);
        const result = await response.json();
        data.value = result;
    } catch (err) {
        error.value = 'Failed to load data';
    } finally {
        loading.value = false;
    }
};

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        fetchData();
    }
});
</script>

<template>
    <Dialog :open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Entity Details</DialogTitle>
            </DialogHeader>
            
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
            </div>
            
            <div v-else-if="error" class="text-center py-8">
                <p class="text-red-600">{{ error }}</p>
                <Button @click="fetchData">Try Again</Button>
            </div>
            
            <div v-else-if="data" class="py-4">
                <!-- Content -->
            </div>
        </DialogContent>
    </Dialog>
</template>
```

### After (BaseModal)
```vue
<template>
    <BaseModal
        :open="open"
        title="Entity Details"
        :api-url="`/api/entities/${id}/details`"
        :enable-lazy-loading="true"
        @data-loaded="(data) => console.log('Loaded:', data)"
        @error="(error) => console.error('Error:', error)"
    >
        <template #default="{ data }">
            <div v-if="data?.entity">
                <!-- Content -->
            </div>
        </template>
    </BaseModal>
</template>
```

## Benefits

1. **Code Reduction**: Eliminates repetitive loading/error handling code
2. **Consistency**: Standardized modal behavior across the application
3. **Maintainability**: Centralized modal logic in one component
4. **User Experience**: Consistent loading states and error handling
5. **Developer Experience**: Simple API for common modal patterns
6. **Flexibility**: Supports both simple and complex modal scenarios

## Examples in Codebase

- `resources/js/components/modals/BankAccount/show-simple.vue` - Show modal with API request
- `resources/js/components/modals/BankAccount/update-simple.vue` - Edit modal with API request
- `resources/js/components/modals/BaseModal.vue` - Base implementation

This pattern provides a solid foundation for all modal implementations in the Vessel Management System, ensuring consistency and reducing code duplication.
