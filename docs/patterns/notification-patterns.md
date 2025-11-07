# Notification System Patterns

This document outlines the complete notification system implementation for the Vessel Management System, covering both backend and frontend patterns.

## Overview

The notification system provides user feedback for CRUD operations (Create, Read, Update, Delete) with:
- **Flash Messages**: Server-side messages passed to frontend via Inertia.js
- **Frontend Notifications**: Real-time notification display with auto-dismissal
- **Confirmation Dialogs**: User confirmation for destructive operations
- **TypeScript Support**: Type-safe notification handling

## Backend Implementation

### 1. Middleware Configuration

#### HandleInertiaRequests Middleware

The core of the notification system is the `HandleInertiaRequests` middleware that shares flash messages with the frontend:

```php
<?php
// app/Http/Middleware/HandleInertiaRequests.php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->roles->pluck('name')->toArray(),
                    'permissions' => $this->getUserPermissions($request->user()),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'notification_delay' => $request->session()->get('notification_delay'),
            ],
        ];
    }
}
```

**Key Points:**
- Flash messages are explicitly shared under the `flash` key
- Messages are retrieved from Laravel's session storage
- Four notification types are supported: `success`, `error`, `warning`, `info`
- Messages are consumed after being shared (Laravel's default behavior)

### 2. Controller Implementation

#### Flash Message Patterns

Controllers should use flash messages for user feedback:

```php
<?php
// app/Http/Controllers/VesselController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVesselRequest;
use App\Http\Requests\UpdateVesselRequest;
use App\Models\Vessel;
use Illuminate\Http\Request;

class VesselController extends Controller
{
    public function store(StoreVesselRequest $request)
    {
        try {
            $vessel = Vessel::create($request->validated());

            return redirect()
                ->route('vessels.index')
                ->with('success', "Vessel '{$vessel->name}' has been created successfully.")
                ->with('notification_delay', 3); // 3 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create vessel. Please try again.')
                ->with('notification_delay', 0); // Persistent error (0 = no auto-dismiss)
        }
    }

    public function update(UpdateVesselRequest $request, Vessel $vessel)
    {
        try {
            $vessel->update($request->validated());

            return redirect()
                ->route('vessels.index')
                ->with('success', "Vessel '{$vessel->name}' has been updated successfully.")
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update vessel. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function destroy(Vessel $vessel)
    {
        try {
            // Check constraints before deletion
            if ($vessel->crewMembers()->count() > 0) {
                return back()->with('error', 
                    "Cannot delete vessel '{$vessel->name}' because it has crew members assigned. Please reassign or remove crew members first.")
                    ->with('notification_delay', 0); // Persistent error
            }

            if ($vessel->transactions()->count() > 0) {
                return back()->with('error', 
                    "Cannot delete vessel '{$vessel->name}' because it has transactions. Please remove all transactions first.")
                    ->with('notification_delay', 0); // Persistent error
            }

            $vesselName = $vessel->name;
            $vessel->delete();

            return redirect()
                ->route('vessels.index')
                ->with('success', "Vessel '{$vesselName}' has been deleted successfully.")
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete vessel. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }
}
```

**Controller Best Practices:**
- Use `try-catch` blocks for error handling
- Provide specific success messages with entity names
- Include constraint checks for destructive operations
- Use `back()` with `withInput()` for validation errors
- Use `redirect()` with `route()` for successful operations
- **Custom Delay Configuration:**
  - Success notifications: 3-5 seconds (auto-dismiss)
  - Error notifications: 0 seconds (persistent until manually dismissed)
  - Warning notifications: 3-4 seconds (auto-dismiss)
  - Info notifications: 2-3 seconds (auto-dismiss)
- Use `->with('notification_delay', seconds)` to control auto-dismiss timing

### 4. Middleware Registration

#### Bootstrap Configuration

The `CheckRole` middleware must be registered in the bootstrap configuration:

```php
<?php
// bootstrap/app.php

use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Register role middleware
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

#### Route Protection

Use the `role` middleware to protect routes based on user roles:

```php
<?php
// routes/web.php

use App\Http\Controllers\VesselController;
use Illuminate\Support\Facades\Route;

// Public routes (all authenticated users)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('vessels', [VesselController::class, 'index'])->name('vessels.index');
    Route::get('vessels/{vessel}', [VesselController::class, 'show'])->name('vessels.show');
});

// Protected routes (admin and manager only)
Route::middleware(['auth', 'verified', 'role:admin,manager'])->group(function () {
    Route::post('vessels', [VesselController::class, 'store'])->name('vessels.store');
    Route::put('vessels/{vessel}', [VesselController::class, 'update'])->name('vessels.update');
    Route::delete('vessels/{vessel}', [VesselController::class, 'destroy'])->name('vessels.destroy');
});
```

**Key Points:**
- Middleware is registered as an alias in `bootstrap/app.php`
- Routes are protected using the `role:admin,manager` syntax
- Multiple roles can be specified separated by commas
- The middleware automatically returns 403 for unauthorized access

### 5. Request Validation Integration

#### Authorization in Requests

Request classes should include authorization logic:

```php
<?php
// app/Http/Requests/StoreVesselRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVesselRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:50', 'unique:vessels'],
            'type' => ['required', 'string', 'in:cargo,passenger,fishing,yacht'],
            'status' => ['required', 'string', 'in:active,maintenance,inactive'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

**Request Authorization Best Practices:**
- Always implement `authorize()` method in Form Request classes
- Use role-based authorization with `hasAnyRole()` method
- Provide fallback `false` for unauthenticated users
- Combine with middleware protection for defense in depth

## Frontend Implementation

### 1. Notification Composable

#### TypeScript Notification Management

The `useNotifications` composable provides centralized notification handling:

```typescript
// resources/js/composables/useNotifications.ts

import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

export interface Notification {
    id: string
    type: 'success' | 'error' | 'warning' | 'info'
    title: string
    message: string
    duration?: number
    persistent?: boolean
}

export function useNotifications() {
    const page = usePage()
    const notifications = ref<Notification[]>([])

    // Get flash messages from Inertia
    const flashMessages = computed(() => {
        const flash = page.props.flash as any
        return {
            success: flash?.success,
            error: flash?.error,
            warning: flash?.warning,
            info: flash?.info,
            notification_delay: flash?.notification_delay,
        }
    })

    // Process flash messages immediately when they're available
    const processFlashMessages = () => {
        const flash = flashMessages.value
        const customDelay = flash.notification_delay

        if (flash.success) {
            addNotification({
                type: 'success',
                title: 'Success',
                message: flash.success,
                duration: customDelay ? customDelay * 1000 : undefined, // Convert seconds to milliseconds
                persistent: customDelay === 0, // If delay is 0, make it persistent
            })
        }

        if (flash.error) {
            addNotification({
                type: 'error',
                title: 'Error',
                message: flash.error,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0 || customDelay === undefined, // Default persistent for errors
            })
        }

        if (flash.warning) {
            addNotification({
                type: 'warning',
                title: 'Warning',
                message: flash.warning,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0,
            })
        }

        if (flash.info) {
            addNotification({
                type: 'info',
                title: 'Information',
                message: flash.info,
                duration: customDelay ? customDelay * 1000 : undefined,
                persistent: customDelay === 0,
            })
        }
    }

    // Process flash messages immediately when component mounts
    processFlashMessages()

    // Watch for flash message changes (for subsequent updates)
    watch(flashMessages, (newFlash, oldFlash) => {
        if (newFlash.success && newFlash.success !== oldFlash?.success) {
            addNotification({
                type: 'success',
                title: 'Success',
                message: newFlash.success,
            })
        }

        if (newFlash.error && newFlash.error !== oldFlash?.error) {
            addNotification({
                type: 'error',
                title: 'Error',
                message: newFlash.error,
            })
        }

        if (newFlash.warning && newFlash.warning !== oldFlash?.warning) {
            addNotification({
                type: 'warning',
                title: 'Warning',
                message: newFlash.warning,
            })
        }

        if (newFlash.info && newFlash.info !== oldFlash?.info) {
            addNotification({
                type: 'info',
                title: 'Information',
                message: newFlash.info,
            })
        }
    }, { deep: true })

    const addNotification = (notification: Omit<Notification, 'id'>) => {
        const id = Math.random().toString(36).substr(2, 9)
        const newNotification: Notification = {
            id,
            duration: 5000, // 5 seconds default
            persistent: false,
            ...notification,
        }

        notifications.value.push(newNotification)

        // Auto-remove notification after duration
        if (!newNotification.persistent && newNotification.duration) {
            setTimeout(() => {
                removeNotification(id)
            }, newNotification.duration)
        }

        return id
    }

    const removeNotification = (id: string) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    const clearAllNotifications = () => {
        notifications.value = []
    }

    // Convenience methods
    const success = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'success',
            title,
            message,
            ...options,
        })
    }

    const error = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'error',
            title,
            message,
            persistent: true, // Errors should persist until manually dismissed
            ...options,
        })
    }

    const warning = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'warning',
            title,
            message,
            ...options,
        })
    }

    const info = (title: string, message: string, options?: Partial<Notification>) => {
        return addNotification({
            type: 'info',
            title,
            message,
            ...options,
        })
    }

    return {
        notifications: computed(() => notifications.value),
        flashMessages,
        processFlashMessages,
        addNotification,
        removeNotification,
        clearAllNotifications,
        success,
        error,
        warning,
        info,
    }
}
```

**Key Features:**
- TypeScript interfaces for type safety
- Automatic flash message processing
- Auto-dismissal with configurable duration
- Persistent error notifications
- Convenience methods for different notification types

### 2. Notification Components

#### NotificationContainer Component

Global container for displaying notifications:

```vue
<!-- resources/js/components/NotificationContainer.vue -->

<template>
    <div class="fixed top-4 right-4 z-[9999] space-y-2 max-w-sm">
        <TransitionGroup
            name="notification"
            tag="div"
            class="space-y-2"
        >
            <NotificationItem
                v-for="notification in notifications"
                :key="notification.id"
                :notification="notification"
                @remove="removeNotification"
            />
        </TransitionGroup>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useNotifications } from '@/composables/useNotifications'
import NotificationItem from '@/components/NotificationItem.vue'

const { notifications, processFlashMessages, removeNotification } = useNotifications()

// Process flash messages when component mounts
onMounted(() => {
    processFlashMessages()
})
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.notification-move {
    transition: transform 0.3s ease;
}
</style>
```

#### NotificationItem Component

Individual notification display:

```vue
<!-- resources/js/components/NotificationItem.vue -->

<template>
    <div
        :class="notificationClasses"
        class="rounded-lg border p-4 shadow-lg backdrop-blur-sm"
        role="alert"
    >
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <Icon :name="iconName" :class="iconClasses" />
            </div>

            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-semibold text-foreground">
                    {{ notification.title }}
                </h4>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ notification.message }}
                </p>
            </div>

            <div class="flex-shrink-0">
                <button
                    @click="$emit('remove', notification.id)"
                    class="text-muted-foreground hover:text-foreground transition-colors"
                    aria-label="Close notification"
                >
                    <Icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Progress bar for auto-dismiss -->
        <div
            v-if="!notification.persistent && notification.duration"
            class="absolute bottom-0 left-0 h-1 bg-current opacity-20 rounded-b-lg"
            :style="{ animation: `shrink ${notification.duration}ms linear forwards` }"
        />

        <!-- Circular loader at border -->
        <div
            v-if="!notification.persistent && notification.duration"
            class="absolute top-0 right-0 w-4 h-4 border-2 border-current border-t-transparent rounded-full opacity-30"
            :style="{ animation: `spin ${notification.duration}ms linear forwards` }"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import type { Notification } from '@/composables/useNotifications';

const props = defineProps<{
    notification: Notification;
}>();

defineEmits(['remove']);

const notificationClasses = computed(() => {
    const baseClasses = 'relative overflow-hidden'

    switch (props.notification.type) {
        case 'success':
            return `${baseClasses} bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200`
        case 'error':
            return `${baseClasses} bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200`
        case 'warning':
            return `${baseClasses} bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200`
        case 'info':
            return `${baseClasses} bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200`
        default:
            return `${baseClasses} bg-card border-border text-card-foreground`
    }
});

const iconClasses = computed(() => {
    const baseClasses = 'h-5 w-5'

    switch (props.notification.type) {
        case 'success':
            return `${baseClasses} text-green-600 dark:text-green-400`
        case 'error':
            return `${baseClasses} text-red-600 dark:text-red-400`
        case 'warning':
            return `${baseClasses} text-yellow-600 dark:text-yellow-400`
        case 'info':
            return `${baseClasses} text-blue-600 dark:text-blue-400`
        default:
            return `${baseClasses} text-muted-foreground`
    }
});

const iconName = computed(() => {
    switch (props.notification.type) {
        case 'success':
            return 'check-circle'
        case 'error':
            return 'x-circle'
        case 'warning':
            return 'alert-triangle'
        case 'info':
            return 'info'
        default:
            return 'info'
    }
});
</script>

<style scoped>
@keyframes shrink {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
```

### 3. Confirmation Dialog Component

#### ConfirmationDialog Component

Reusable confirmation dialog for destructive operations:

```vue
<!-- resources/js/components/ConfirmationDialog.vue -->

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent :class="dialogSizeClass">
            <DialogHeader>
                <DialogTitle :class="titleClass">
                    <Icon v-if="iconName" :name="iconName" :class="iconClass" class="mr-2" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <div v-if="message" :class="messageClass">
                {{ message }}
            </div>
            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('cancel')"
                    :disabled="loading"
                >
                    {{ cancelText }}
                </Button>
                <Button
                    :variant="variant"
                    @click="$emit('confirm')"
                    :disabled="loading"
                >
                    <Icon v-if="loading" name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
                    {{ confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

type DialogType = 'info' | 'warning' | 'danger';

interface Props {
    open: boolean;
    title: string;
    description: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    type?: DialogType;
    loading?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'default',
    type: 'info',
    loading: false,
    size: 'md',
});

defineEmits(['update:open', 'confirm', 'cancel']);

const dialogSizeClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'sm:max-w-sm';
        case 'md': return 'sm:max-w-md';
        case 'lg': return 'sm:max-w-lg';
        default: return 'sm:max-w-md';
    }
});

const iconName = computed(() => {
    switch (props.type) {
        case 'warning': return 'alert-triangle';
        case 'danger': return 'x-circle';
        case 'info': return 'info';
        default: return 'info';
    }
});

const iconClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-500';
        case 'danger': return 'text-red-500';
        case 'info': return 'text-blue-500';
        default: return 'text-gray-500';
    }
});

const titleClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-600';
        case 'danger': return 'text-red-600';
        case 'info': return 'text-blue-600';
        default: return 'text-gray-900';
    }
});

const messageClass = computed(() => {
    switch (props.type) {
        case 'warning': return 'text-yellow-700 text-sm';
        case 'danger': return 'text-red-700 text-sm';
        case 'info': return 'text-blue-700 text-sm';
        default: return 'text-gray-700 text-sm';
    }
});
</script>
```

### 4. Layout Integration

#### AppSidebarLayout Integration

The notification system is globally available through the main layout:

```vue
<!-- resources/js/layouts/app/AppSidebarLayout.vue -->

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        
        <!-- Global Notifications -->
        <NotificationContainer />
    </AppShell>
</template>

<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import NotificationContainer from '@/components/NotificationContainer.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>
```

## Usage Examples

### 1. Page Integration

#### Vessels Index Page

Example of how to integrate notifications and confirmation dialogs in a page:

```vue
<!-- resources/js/Pages/Vessels/Index.vue -->

<template>
    <AppLayout>
        <!-- Page content -->
        
        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            :open="showDeleteDialog"
            title="Delete Vessel"
            description="This action cannot be undone."
            :message="`Are you sure you want to delete the vessel '${vesselToDelete?.name}'? This will permanently remove the vessel and all its data.`"
            confirm-text="Delete Vessel"
            cancel-text="Cancel"
            variant="destructive"
            type="danger"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import vessels from '@/routes/vessels';

// Confirmation dialog state
const showDeleteDialog = ref(false);
const vesselToDelete = ref<Vessel | null>(null);
const isDeleting = ref(false);

const deleteVessel = (vessel: Vessel) => {
    vesselToDelete.value = vessel;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!vesselToDelete.value) return;
    
    isDeleting.value = true;
    
    router.delete(vessels.destroy.url(vesselToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            vesselToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    vesselToDelete.value = null;
    isDeleting.value = false;
};
</script>
```

### 2. Manual Notification Usage

#### Programmatic Notifications

You can also trigger notifications programmatically:

```typescript
// In any Vue component
import { useNotifications } from '@/composables/useNotifications';

const { success, error, warning, info } = useNotifications();

// Trigger notifications
success('Operation Complete', 'The data has been saved successfully.');
error('Operation Failed', 'An error occurred while saving the data.');
warning('Warning', 'This action may have unintended consequences.');
info('Information', 'Please review the changes before proceeding.');
```

## Key Features

### 1. Custom Delay Configuration
- **Controller-controlled timing**: Controllers can specify notification delays in seconds
- **Flexible auto-dismiss**: Success notifications auto-dismiss after 3-5 seconds
- **Persistent errors**: Error notifications remain until manually dismissed (delay = 0)
- **Type-specific defaults**: Different default delays for different notification types

### 2. Visual Feedback Animations
- **Progress bar**: Bottom border animation showing auto-dismiss countdown
- **Circular loader**: Top-right spinning indicator for active notifications
- **Smooth transitions**: Slide-in/out animations for notification appearance/disappearance
- **Type-specific styling**: Color-coded notifications with appropriate icons

### 3. TypeScript Support
- **Type-safe notifications**: Full TypeScript interfaces for notification objects
- **Composable integration**: Type-safe useNotifications composable
- **IntelliSense support**: Auto-completion for notification properties and methods

### 4. Global Integration
- **Layout-level rendering**: Notifications rendered globally in AppSidebarLayout
- **Flash message processing**: Automatic processing of Laravel flash messages
- **Inertia.js integration**: Seamless integration with Inertia.js page props

## Best Practices

### Backend Best Practices

1. **Always use try-catch blocks** for error handling in controllers
2. **Provide specific messages** with entity names for better user experience
3. **Check constraints** before destructive operations
4. **Use appropriate HTTP status codes** and redirects
5. **Validate authorization** in request classes

### Frontend Best Practices

1. **Use TypeScript interfaces** for type safety
2. **Process flash messages immediately** on component mount
3. **Watch for flash message changes** to handle subsequent updates
4. **Provide confirmation dialogs** for destructive operations
5. **Use appropriate notification types** (success, error, warning, info)
6. **Set appropriate durations** for different notification types
7. **Make error notifications persistent** until manually dismissed

### Security Considerations

1. **Sanitize user input** in notification messages
2. **Validate authorization** before showing sensitive information
3. **Use CSRF protection** for all form submissions
4. **Implement rate limiting** for notification-heavy operations

## Troubleshooting

### Common Issues

1. **Notifications not appearing**: Check if `NotificationContainer` is included in the layout
2. **Flash messages not received**: Verify `HandleInertiaRequests` middleware is properly configured
3. **Notifications not auto-dismissing**: Check if `duration` is set and `persistent` is false
4. **TypeScript errors**: Ensure proper type imports and interfaces are used

### Debug Tips

1. **Check browser console** for JavaScript errors
2. **Verify middleware registration** in `bootstrap/app.php`
3. **Test flash messages** using `dd($request->session()->all())` in controllers
4. **Check Inertia props** using browser dev tools

## Migration Guide

### From Basic Alerts

If migrating from basic browser alerts:

1. Replace `alert()` calls with confirmation dialogs
2. Add flash message handling in controllers
3. Include `NotificationContainer` in layouts
4. Update error handling to use notifications

### From Toast Libraries

If migrating from toast libraries:

1. Replace toast calls with `useNotifications` composable
2. Update styling to match design system
3. Ensure proper TypeScript integration
4. Test auto-dismissal behavior

## Performance Considerations

1. **Limit notification count** to prevent UI clutter
2. **Use appropriate durations** to avoid notification spam
3. **Implement notification queuing** for high-frequency operations
4. **Optimize animations** for smooth user experience

## Accessibility

1. **Use proper ARIA roles** (`role="alert"`)
2. **Provide keyboard navigation** for notification dismissal
3. **Ensure sufficient color contrast** for all notification types
4. **Support screen readers** with proper semantic markup

This notification system provides a robust, type-safe, and user-friendly way to provide feedback across the entire Vessel Management System.
