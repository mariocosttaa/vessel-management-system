<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <Label>Attach Documents <span class="text-muted-foreground text-xs">(Max 10 files)</span></Label>
      <p class="text-xs text-muted-foreground">
        Supported formats: PDF, JPG, JPEG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, CSV (Max: 10MB per file)
      </p>
    </div>

    <!-- Drop Zone - Use label for native click behavior -->
    <label
      ref="dropZoneRef"
      :for="fileInputId"
      class="relative border-2 border-dashed rounded-lg p-6 transition-colors block"
      :class="[
        isDragging
          ? 'border-primary bg-primary/5'
          : 'border-border bg-muted/30',
        files.length >= maxFiles ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
      ]"
      @drop.prevent="handleDrop"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
      @dragenter.prevent="handleDragEnter"
    >
      <input
        :id="fileInputId"
        ref="fileInputRef"
        type="file"
        :multiple="true"
        :accept="acceptedTypes"
        @change="handleFileSelect"
        class="hidden"
        :disabled="files.length >= maxFiles"
      />

      <div class="text-center">
        <Icon
          name="upload"
          class="w-12 h-12 mx-auto mb-3 text-muted-foreground"
        />
        <p class="text-sm font-medium text-foreground mb-1">
          {{ isDragging ? 'Drop files here' : 'Click to upload or drag and drop' }}
        </p>
        <p class="text-xs text-muted-foreground">
          {{ files.length }}/{{ maxFiles }} files selected
        </p>
      </div>
    </label>

    <!-- Files List -->
    <div v-if="files.length > 0" class="space-y-2">
      <div
        v-for="(file, index) in files"
        :key="index"
        class="flex flex-col gap-2 p-3 rounded-lg border border-border bg-card"
      >
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <!-- File Icon -->
          <div class="flex-shrink-0">
            <Icon
              :name="getFileIcon(file.type || getFileType(file.name))"
              class="w-8 h-8 text-muted-foreground"
            />
          </div>

          <!-- File Info -->
          <div class="flex-1 min-w-0">
            <!-- Editable File Name -->
            <div v-if="editingIndex === index" class="flex items-center gap-2">
              <input
                v-model="editingName"
                @blur="saveFileName(index)"
                @keydown.enter="saveFileName(index)"
                @keydown.esc="cancelEdit"
                type="text"
                class="flex-1 px-2 py-1 text-sm border border-border rounded-md bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                autofocus
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="saveFileName(index)"
                class="flex-shrink-0"
              >
                <Icon name="check" class="w-4 h-4 text-green-600" />
              </Button>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="cancelEdit"
                class="flex-shrink-0"
              >
                <Icon name="x" class="w-4 h-4 text-muted-foreground" />
              </Button>
            </div>
            <div v-else class="flex items-center gap-2">
              <p
                class="text-sm font-medium text-foreground truncate flex-1 cursor-pointer hover:text-primary"
                @click="startEditing(index)"
                :title="getDisplayName(file)"
              >
                {{ getDisplayName(file) }}
              </p>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="startEditing(index)"
                class="flex-shrink-0 text-muted-foreground hover:text-foreground"
                title="Edit file name"
              >
                <Icon name="edit" class="w-4 h-4" />
              </Button>
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ formatFileSize(file.size) }}
            </p>
          </div>

          <!-- Remove Button -->
          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="removeFile(index)"
            class="flex-shrink-0 text-destructive hover:text-destructive"
            title="Remove file"
          >
            <Icon name="x" class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <InputError v-if="error" :message="error" />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';

interface FileWithCustomName extends File {
  customName?: string;
  originalName?: string; // Store original file name to preserve extension
  preview?: string;
  isEditing?: boolean;
}

interface Props {
  modelValue?: File[];
  maxFiles?: number;
  maxSize?: number; // in bytes
  acceptedTypes?: string;
  error?: string;
}

const props = withDefaults(defineProps<Props>(), {
  maxFiles: 10,
  maxSize: 10 * 1024 * 1024, // 10MB
  acceptedTypes: '.pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.txt,.csv',
});

const emit = defineEmits<{
  'update:modelValue': [files: File[]];
  'error': [error: string];
}>();

const files = ref<FileWithCustomName[]>([]);
const editingIndex = ref<number | null>(null);
const editingName = ref<string>('');
const isDragging = ref(false);
const dropZoneRef = ref<HTMLLabelElement | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const error = ref<string | null>(null);
const fileInputId = `file-input-${Math.random().toString(36).substring(2, 9)}`;

// Flag to prevent circular updates between props.modelValue and files
const isInternalUpdate = ref(false);

// Initialize files from props.modelValue (only on mount or when explicitly cleared)
if (props.modelValue && props.modelValue.length > 0) {
  files.value = props.modelValue.map(file => {
    const fileWithOriginal = file as FileWithCustomName;
    if (!fileWithOriginal.originalName) {
      fileWithOriginal.originalName = file.name;
    }
    return fileWithOriginal;
  });
}

// Watch for external changes (only when not updating internally and when actually different)
watch(() => props.modelValue, (newValue, oldValue) => {
  // Skip if this is an internal update
  if (isInternalUpdate.value) {
    return;
  }

  // Skip if the value hasn't actually changed (same reference or same content)
  if (newValue === oldValue) {
    return;
  }

  // Only update if modelValue is explicitly set to empty array (clear operation)
  if (!newValue || newValue.length === 0) {
    // Only clear if we currently have files
    if (files.value.length > 0) {
      files.value = [];
    }
    return;
  }

  // For non-empty values, only update if length is different
  // This prevents unnecessary updates when the parent component re-renders
  if (newValue.length !== files.value.length) {
    // Create a map of existing files by their original name and size to preserve custom names
    const existingFilesMap = new Map(
      files.value.map(f => {
        const key = `${f.originalName || f.name}-${f.size}`;
        return [key, f];
      })
    );

    // Update files array, preserving custom names where possible
    files.value = newValue.map(newFile => {
      const key = `${newFile.name}-${newFile.size}`;
      const existing = existingFilesMap.get(key);

      if (existing && existing.customName && existing.customName !== newFile.name) {
        // File exists and has a custom name - preserve it
        const fileWithCustomName = new File([newFile], existing.customName, {
          type: newFile.type,
          lastModified: newFile.lastModified,
        });
        Object.assign(fileWithCustomName, {
          customName: existing.customName,
          originalName: existing.originalName || newFile.name,
          preview: existing.preview,
        });
        return fileWithCustomName as FileWithCustomName;
      }

      // New file or no custom name - store original name
      const fileWithOriginal = newFile as FileWithCustomName;
      if (!fileWithOriginal.originalName) {
        fileWithOriginal.originalName = newFile.name;
      }
      return fileWithOriginal;
    });
  }
}, { flush: 'post' });

// Emit changes manually (not through watcher to avoid loops)
const emitFilesUpdate = () => {
  isInternalUpdate.value = true;
  emit('update:modelValue', [...files.value]);
  error.value = null;
  // Reset flag after emit
  requestAnimationFrame(() => {
    isInternalUpdate.value = false;
  });
};


// Removed handleClick - using label with for attribute provides native click behavior
// This is the simplest and most compatible approach across all browsers

const triggerFileInput = () => {
  if (files.value.length >= props.maxFiles) {
    setError(`Maximum ${props.maxFiles} files allowed`);
    return;
  }

  if (fileInputRef.value && !fileInputRef.value.disabled) {
    fileInputRef.value.click();
  }
};

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    processFiles(Array.from(target.files));
  }
  // Reset input to allow selecting the same file again
  target.value = '';
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  isDragging.value = true;
};

const handleDragEnter = (event: DragEvent) => {
  event.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault();
  // Only set dragging to false if we're actually leaving the drop zone
  const relatedTarget = event.relatedTarget as HTMLElement;
  if (!dropZoneRef.value?.contains(relatedTarget)) {
    isDragging.value = false;
  }
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  isDragging.value = false;

  if (files.value.length >= props.maxFiles) {
    setError(`Maximum ${props.maxFiles} files allowed`);
    return;
  }

  const droppedFiles = event.dataTransfer?.files;
  if (droppedFiles && droppedFiles.length > 0) {
    processFiles(Array.from(droppedFiles));
  }
};

const processFiles = (newFiles: File[]) => {
  error.value = null;

  // Check total file count
  const totalFiles = files.value.length + newFiles.length;
  if (totalFiles > props.maxFiles) {
    setError(`Maximum ${props.maxFiles} files allowed. Please remove some files first.`);
    return;
  }

  // Track if any files were successfully added
  let filesAdded = false;

  // Validate each file
  for (const file of newFiles) {
    // Check file size
    if (file.size > props.maxSize) {
      setError(`File "${file.name}" exceeds maximum size of ${formatFileSize(props.maxSize)}`);
      continue;
    }

    // Check file type
    if (!isFileTypeAllowed(file)) {
      setError(`File "${file.name}" is not a supported file type`);
      continue;
    }

    // Check for duplicates
    if (files.value.some(f => f.name === file.name && f.size === file.size)) {
      setError(`File "${file.name}" is already selected`);
      continue;
    }

    // Add file with original name stored
    const fileWithOriginal = file as FileWithCustomName;
    fileWithOriginal.originalName = file.name;
    files.value.push(fileWithOriginal);
    filesAdded = true;
  }

  // Emit update after processing all files (only if files were actually added)
  if (filesAdded) {
    // Use nextTick to ensure Vue has processed the array update
    nextTick(() => {
      emitFilesUpdate();
    });
  }

  if (error.value) {
    emit('error', error.value);
  }
};

const isFileTypeAllowed = (file: File): boolean => {
  const allowedExtensions = props.acceptedTypes
    .split(',')
    .map(ext => ext.trim().toLowerCase().replace('.', ''));

  const fileExtension = file.name.split('.').pop()?.toLowerCase();

  if (!fileExtension) return false;

  // Also check MIME type
  const allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'text/csv',
  ];

  return allowedExtensions.includes(fileExtension) ||
         allowedMimeTypes.some(mime => file.type.includes(mime.split('/')[1]));
};

const removeFile = (index: number) => {
  files.value.splice(index, 1);
  error.value = null;
  if (editingIndex.value === index) {
    editingIndex.value = null;
    editingName.value = '';
  }
  // Emit update after removing file
  emitFilesUpdate();
};

const getDisplayName = (file: FileWithCustomName): string => {
  return file.customName || file.name;
};

const startEditing = (index: number) => {
  const file = files.value[index];
  editingIndex.value = index;
  // Get the name without extension for editing
  const nameWithoutExt = getDisplayName(file).replace(/\.[^/.]+$/, '');
  editingName.value = nameWithoutExt;
};

const saveFileName = (index: number) => {
  if (editingIndex.value === null) return;

  const file = files.value[index];
  const trimmedName = editingName.value.trim();

  if (trimmedName === '') {
    setError('File name cannot be empty');
    cancelEdit();
    return;
  }

  // Validate that the new name doesn't contain invalid characters
  if (!/^[^<>:"/\\|?*]+$/.test(trimmedName)) {
    setError('File name contains invalid characters');
    return;
  }

  // Get the original file extension from the original file name
  // This ensures we always use the correct extension even if name was already changed
  const originalFileName = file.originalName || file.name;
  const lastDotIndex = originalFileName.lastIndexOf('.');
  const extension = lastDotIndex !== -1 ? originalFileName.substring(lastDotIndex) : '';

  // Create new file name with extension
  const newFileName = trimmedName + extension;

  // Create a new File object with the custom name
  // This ensures the File object has the correct name when sent to the server
  const newFile = new File([file], newFileName, {
    type: file.type,
    lastModified: file.lastModified,
  });

  // Store custom name, original name, and other properties
  Object.assign(newFile, {
    customName: newFileName,
    originalName: file.originalName || file.name,
    preview: file.preview,
  });

  // Replace the file in the array
  files.value[index] = newFile as FileWithCustomName;

  // Reset editing state
  editingIndex.value = null;
  editingName.value = '';

  // Emit update after saving file name
  emitFilesUpdate();
};

const cancelEdit = () => {
  editingIndex.value = null;
  editingName.value = '';
};

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getFileType = (filename: string): string => {
  const extension = filename.split('.').pop()?.toLowerCase();

  if (['jpg', 'jpeg', 'png', 'gif'].includes(extension || '')) {
    return 'image';
  }
  if (extension === 'pdf') {
    return 'pdf';
  }
  if (['doc', 'docx'].includes(extension || '')) {
    return 'document';
  }
  if (['xls', 'xlsx'].includes(extension || '')) {
    return 'spreadsheet';
  }
  if (['txt', 'csv'].includes(extension || '')) {
    return 'text';
  }
  return 'file';
};

const getFileIcon = (type: string): string => {
  switch (type) {
    case 'image':
      return 'image';
    case 'pdf':
      return 'file-text';
    case 'document':
      return 'file-text';
    case 'spreadsheet':
      return 'file-text';
    case 'text':
      return 'file-text';
    default:
      return 'file';
  }
};

const setError = (message: string) => {
  error.value = message;
  emit('error', message);
  setTimeout(() => {
    error.value = null;
  }, 5000);
};

// Expose methods for parent component
const clearFiles = () => {
  isInternalUpdate.value = true;
  files.value = [];
  error.value = null;
  emit('update:modelValue', []);
  requestAnimationFrame(() => {
    isInternalUpdate.value = false;
  });
};

defineExpose({
  clearFiles,
  getFiles: () => files.value,
});
</script>

