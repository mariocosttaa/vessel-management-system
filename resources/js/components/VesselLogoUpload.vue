<template>
  <div class="space-y-4">
    <Label>{{ t('Vessel Logo') }}</Label>

    <!-- Current Logo Preview (only show if no new file selected and logoUrl exists) -->
    <div v-if="logoUrl && !selectedFile" class="flex items-start gap-4" :key="`logo-${logoUrl}`">
      <div class="relative">
        <img
          :src="logoUrl"
          :alt="t('Vessel logo')"
          class="w-24 h-24 rounded-lg object-cover border-2 border-border shadow-sm"
        />
        <button
          v-if="!readonly"
          @click="showRemoveConfirmation"
          type="button"
          class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-destructive text-destructive-foreground flex items-center justify-center hover:bg-destructive/90 transition-colors shadow-sm"
          :title="t('Remove logo')"
        >
          <Icon name="x" class="w-4 h-4" />
        </button>
      </div>
      <div class="flex-1">
        <p class="text-sm text-muted-foreground">
          {{ t('Current vessel logo') }}
        </p>
        <p class="text-xs text-muted-foreground mt-1">
          {{ t('Click the remove button to delete the current logo') }}
        </p>
      </div>
    </div>

    <!-- Logo Removal Pending Message -->
    <div v-if="isRemovalPending && !selectedFile && !readonly" class="rounded-lg border border-yellow-500/50 bg-yellow-500/10 dark:bg-yellow-500/5 p-4">
      <div class="flex items-start gap-3">
        <Icon name="alert-circle" class="w-5 h-5 text-yellow-600 dark:text-yellow-500 flex-shrink-0 mt-0.5" />
        <div class="flex-1">
          <p class="text-sm font-medium text-yellow-900 dark:text-yellow-100">
            {{ t('Logo removal pending') }}
          </p>
          <p class="text-xs text-yellow-800/80 dark:text-yellow-200/80 mt-1">
            {{ t('The logo has been marked for removal. Click "Save Changes" below to confirm and permanently delete the logo.') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Remove Logo Confirmation Dialog -->
    <ConfirmationDialog
      :open="showConfirmDialog"
      @update:open="showConfirmDialog = $event"
      :title="t('Remove Vessel Logo')"
      :description="t('Are you sure you want to remove the vessel logo?')"
      :message="t('This action cannot be undone. The logo will be permanently deleted.')"
      :confirm-text="t('Remove Logo')"
      :cancel-text="t('Cancel')"
      variant="destructive"
      type="danger"
      @confirm="confirmRemoveLogo"
      @cancel="cancelRemoveLogo"
    />

    <!-- Upload Area -->
    <div v-if="!readonly" class="space-y-2">
      <div
        @click="triggerFileInput"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="[
          'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors',
          isDragging
            ? 'border-primary bg-primary/5'
            : 'border-border hover:border-primary/50 hover:bg-muted/50'
        ]"
      >
        <input
          ref="fileInputRef"
          type="file"
          accept="image/*"
          class="hidden"
          @change="handleFileSelect"
        />
        <div class="flex flex-col items-center gap-2">
          <Icon name="upload" class="w-8 h-8 text-muted-foreground" />
          <div>
            <p class="text-sm font-medium text-card-foreground">
              {{ t('Click to upload or drag and drop') }}
            </p>
            <p class="text-xs text-muted-foreground mt-1">
              {{ t('PNG, JPG, GIF or WEBP (Max 2MB)') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Selected File Preview -->
      <div v-if="selectedFile" class="rounded-lg border border-border p-4 bg-muted/50">
        <div class="flex items-center gap-3">
          <img
            v-if="previewUrl"
            :src="previewUrl"
            alt="Preview"
            class="w-16 h-16 rounded object-cover"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-card-foreground truncate">
              {{ selectedFile.name }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ formatFileSize(selectedFile.size) }}
            </p>
          </div>
          <button
            @click="clearSelection"
            type="button"
            class="p-1 text-muted-foreground hover:text-destructive transition-colors"
            :title="t('Remove')"
          >
            <Icon name="x" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Error Message -->
      <InputError v-if="error" :message="error" />
    </div>

    <!-- Readonly Message -->
    <p v-if="readonly" class="text-sm text-muted-foreground">
      {{ t('You do not have permission to change the vessel logo') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import Icon from '@/components/Icon.vue'
import Label from '@/components/ui/label/Label.vue'
import InputError from '@/components/InputError.vue'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
import { useI18n } from '@/composables/useI18n'

interface Props {
  logoUrl?: string | null
  modelValue?: File | null
  error?: string | null
  readonly?: boolean
  isRemovalPending?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  logoUrl: null,
  modelValue: null,
  error: null,
  readonly: false,
  isRemovalPending: false
})

const emit = defineEmits<{
  'update:modelValue': [file: File | null]
  'remove': []
}>()

const { t } = useI18n()
const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(props.modelValue || null)
const previewUrl = ref<string | null>(null)
const isDragging = ref(false)
const removeLogoFlag = ref(false)
const showConfirmDialog = ref(false)

// Watch for external modelValue changes
watch(() => props.modelValue, (newValue) => {
  selectedFile.value = newValue
  if (newValue) {
    createPreview(newValue)
  } else {
    previewUrl.value = null
  }
})

// Watch for logoUrl prop changes to handle removal
watch(() => props.logoUrl, (newUrl) => {
  // If logoUrl becomes null and we have removeLogoFlag, the logo was removed
  if (!newUrl && removeLogoFlag.value) {
    removeLogoFlag.value = false
  }
})

const triggerFileInput = () => {
  if (props.readonly) return
  fileInputRef.value?.click()
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (props.readonly) return

  const file = event.dataTransfer?.files[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const validateAndSetFile = (file: File) => {
  // Validate file type
  if (!file.type.startsWith('image/')) {
    emit('update:modelValue', null)
    return
  }

  // Validate file size (2MB)
  if (file.size > 2 * 1024 * 1024) {
    emit('update:modelValue', null)
    return
  }

  selectedFile.value = file
  removeLogoFlag.value = false
  createPreview(file)
  emit('update:modelValue', file)
}

const createPreview = (file: File) => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
  previewUrl.value = URL.createObjectURL(file)
}

const clearSelection = () => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
  selectedFile.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
  emit('update:modelValue', null)
}

const showRemoveConfirmation = () => {
  if (props.readonly) return
  showConfirmDialog.value = true
}

const confirmRemoveLogo = () => {
  // Clear any selected file first
  clearSelection()
  // Emit remove event to parent component
  emit('remove')
  // Close the dialog
  showConfirmDialog.value = false
}

const cancelRemoveLogo = () => {
  showConfirmDialog.value = false
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

// Cleanup preview URL on unmount
onBeforeUnmount(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
})
</script>

