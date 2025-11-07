# Modal Patterns

## Overview

This document defines the standard patterns for creating modals in the Vessel Management System. All CRUD operations (Create, Read, Update, Delete) should use modals instead of separate pages for better UX and consistency.

## Modal Types

### 1. Form Modals (Create/Edit)
Used for creating and editing entities with form inputs.

### 2. Show Modals (Read)
Used for displaying detailed entity information with separate API requests for optimal performance.

### 3. Confirmation Modals (Delete)
Used for confirming destructive actions like deletion.

For detailed information about show modals, see [Show Modal Pattern](./show-modal-pattern.md).

## BaseModal Component

The `BaseModal.vue` component provides a standardized modal structure with:

- Consistent sizing options
- Standardized buttons (Cancel/Confirm)
- Loading states
- Proper event handling
- Accessibility features

### Usage

```vue
<BaseModal
    :open="isModalOpen"
    :title="modalTitle"
    :description="modalDescription"
    size="lg"
    :loading="isSubmitting"
    @update:open="handleModalClose"
    @confirm="handleSubmit"
    @cancel="handleCancel"
>
    <!-- Modal content goes here -->
</BaseModal>
```

## Modal Structure Pattern

### File Organization

```
resources/js/components/modals/
├── BaseModal.vue                    # Base modal component
├── VesselFormModal.vue             # Vessel-specific modal
├── CrewMemberFormModal.vue         # Crew member modal
├── SupplierFormModal.vue           # Supplier modal
└── TransactionFormModal.vue        # Transaction modal
```

### Modal Component Structure

```vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseModal from './BaseModal.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'

interface Props {
    open: boolean
    entity?: EntityType | null
    relatedData?: RelatedDataType[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    'saved': []
}>()

// Form state
const form = ref({
    field1: '',
    field2: '',
    // ... other fields
})

const errors = ref<Record<string, string>>({})
const isSubmitting = ref(false)

// Computed properties
const modalTitle = computed(() => 
    props.entity ? 'Edit Entity' : 'Create Entity'
)

const modalDescription = computed(() => 
    props.entity ? 'Update entity information' : 'Add a new entity'
)

// Watch for entity changes to populate form
watch(() => props.entity, (newEntity) => {
    if (newEntity) {
        form.value = {
            field1: newEntity.field1,
            field2: newEntity.field2,
            // ... populate other fields
        }
    } else {
        resetForm()
    }
}, { immediate: true })

// Methods
const resetForm = () => {
    form.value = {
        field1: '',
        field2: '',
        // ... reset other fields
    }
    errors.value = {}
}

const handleSubmit = async () => {
    isSubmitting.value = true
    errors.value = {}

    try {
        const route = props.entity 
            ? route('entities.update', props.entity.id)
            : route('entities.store')
        
        const method = props.entity ? 'put' : 'post'

        await router[method](route, form.value, {
            onSuccess: () => {
                emit('saved')
                handleClose()
            },
            onError: (newErrors) => {
                errors.value = newErrors
            }
        })
    } finally {
        isSubmitting.value = false
    }
}

const handleClose = () => {
    emit('update:open', false)
    resetForm()
}

const handleCancel = () => {
    handleClose()
}
</script>

<template>
    <BaseModal
        :open="open"
        :title="modalTitle"
        :description="modalDescription"
        size="lg"
        :loading="isSubmitting"
        @update:open="handleClose"
        @confirm="handleSubmit"
        @cancel="handleCancel"
    >
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Field 1 -->
            <div>
                <Label for="field1">Field 1</Label>
                <Input
                    id="field1"
                    v-model="form.field1"
                    type="text"
                    placeholder="Enter field 1"
                    :class="{ 'border-red-500': errors.field1 }"
                />
                <InputError :message="errors.field1" />
            </div>

            <!-- Field 2 -->
            <div>
                <Label for="field2">Field 2</Label>
                <Select
                    v-model="form.field2"
                    :options="relatedData"
                    placeholder="Select field 2"
                    :class="{ 'border-red-500': errors.field2 }"
                />
                <InputError :message="errors.field2" />
            </div>

            <!-- Additional fields... -->
        </form>
    </BaseModal>
</template>
```

## Page Integration Pattern

### Index Page with Modal

```vue
<script setup lang="ts">
import { ref } from 'vue'
import EntityFormModal from '@/components/modals/EntityFormModal.vue'

// Modal state
const isModalOpen = ref(false)
const editingEntity = ref(null)

// Methods
const openCreateModal = () => {
    editingEntity.value = null
    isModalOpen.value = true
}

const openEditModal = (entity) => {
    editingEntity.value = entity
    isModalOpen.value = true
}

const handleModalSaved = () => {
    // Refresh the page to show updated data
    router.reload()
}
</script>

<template>
    <div>
        <!-- Page content -->
        <button @click="openCreateModal">
            Add Entity
        </button>

        <!-- Table with edit buttons -->
        <button @click="openEditModal(entity)">
            Edit
        </button>

        <!-- Modal -->
        <EntityFormModal
            :open="isModalOpen"
            :entity="editingEntity"
            :related-data="relatedData"
            @update:open="isModalOpen = $event"
            @saved="handleModalSaved"
        />
    </div>
</template>
```

## Modal Sizing Guidelines

- **sm**: Simple confirmations, single field forms
- **md**: Standard forms with 2-3 fields
- **lg**: Complex forms with 4-6 fields (default for CRUD)
- **xl**: Forms with many fields or complex layouts
- **2xl**: Very complex forms or data tables

## Error Handling Pattern

```vue
<script setup lang="ts">
const errors = ref<Record<string, string>>({})

const handleSubmit = async () => {
    errors.value = {}
    
    try {
        await router.post(route('entities.store'), form.value, {
            onSuccess: () => {
                emit('saved')
                handleClose()
            },
            onError: (newErrors) => {
                errors.value = newErrors
            }
        })
    } catch (error) {
        console.error('Submission error:', error)
    }
}
</script>
```

## Loading States

```vue
<BaseModal
    :loading="isSubmitting"
    :disabled="isSubmitting"
>
    <!-- Modal content -->
</BaseModal>
```

## Accessibility Features

The BaseModal component includes:

- Focus management
- Keyboard navigation (ESC to close)
- ARIA attributes
- Screen reader support
- Focus trapping

## Best Practices

### 1. Always Use BaseModal
Never create custom modal implementations. Always extend BaseModal.

### 2. Consistent Form Structure
- Use the same form field patterns
- Include proper validation
- Show loading states during submission

### 3. Proper Event Handling
- Emit `saved` event after successful operations
- Handle errors gracefully
- Reset form state on close

### 4. Responsive Design
- Use appropriate modal sizes
- Ensure forms work on mobile devices
- Test with different screen sizes

### 5. Data Management
- Watch for prop changes to populate forms
- Reset forms when switching between create/edit
- Handle related data properly

## Examples

### Simple Confirmation Modal

```vue
<BaseModal
    :open="showDeleteModal"
    title="Delete Entity"
    description="Are you sure you want to delete this entity? This action cannot be undone."
    size="sm"
    confirm-text="Delete"
    cancel-text="Cancel"
    @confirm="handleDelete"
    @update:open="showDeleteModal = $event"
/>
```

### Complex Form Modal

```vue
<BaseModal
    :open="isFormModalOpen"
    :title="isEditing ? 'Edit Entity' : 'Create Entity'"
    size="xl"
    :loading="isSubmitting"
    @confirm="handleSubmit"
    @update:open="handleClose"
>
    <form class="space-y-6">
        <!-- Multiple form sections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Form fields -->
        </div>
    </form>
</BaseModal>
```

## Migration from Page-based CRUD

When migrating from separate create/edit pages to modals:

1. **Remove route definitions** for create/edit pages
2. **Update navigation** to use modal triggers instead of links
3. **Move form logic** from pages to modal components
4. **Update controllers** to handle modal-based requests
5. **Test thoroughly** to ensure all functionality works

## Common Patterns

### Entity Form Modal Template

```vue
<!-- EntityFormModal.vue -->
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseModal from './BaseModal.vue'
// ... other imports

interface Props {
    open: boolean
    entity?: EntityType | null
    relatedData?: RelatedDataType[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
    'update:open': [value: boolean]
    'saved': []
}>()

// Form state and methods as shown above
</script>

<template>
    <BaseModal
        :open="open"
        :title="modalTitle"
        :description="modalDescription"
        size="lg"
        :loading="isSubmitting"
        @update:open="handleClose"
        @confirm="handleSubmit"
        @cancel="handleCancel"
    >
        <!-- Form content -->
    </BaseModal>
</template>
```

This pattern ensures consistency across all CRUD operations and provides a better user experience with modals instead of page redirects.
