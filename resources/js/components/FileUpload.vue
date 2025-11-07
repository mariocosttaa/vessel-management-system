<template>
  <div class="space-y-4">
    <!-- Upload Form -->
    <div class="rounded-lg border border-border bg-card p-4">
      <h3 class="text-lg font-semibold mb-4">Upload File</h3>

      <Form
        :action="`/panel/attachments`"
        method="post"
        enctype="multipart/form-data"
        v-slot="{ errors, processing }"
        class="space-y-4"
      >
        <!-- Hidden fields for attachable info -->
        <input type="hidden" name="attachable_type" :value="attachableType" />
        <input type="hidden" name="attachable_id" :value="attachableId" />

        <!-- File Input -->
        <div class="space-y-2">
          <Label for="file">Select File *</Label>
          <Input
            id="file"
            name="file"
            type="file"
            required
            @change="handleFileChange"
            :class="{ 'border-destructive': errors.file }"
          />
          <InputError :message="errors.file" />
          <p class="text-sm text-muted-foreground">
            Supported formats: PDF, JPG, JPEG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, CSV (Max: 10MB)
          </p>
        </div>

        <!-- Description -->
        <div class="space-y-2">
          <Label for="description">Description (Optional)</Label>
          <textarea
            id="description"
            name="description"
            rows="3"
            v-model="description"
            placeholder="Enter a description for this file..."
            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="{ 'border-destructive': errors.description }"
          ></textarea>
          <InputError :message="errors.description" />
        </div>

        <!-- Upload Button -->
        <Button
          type="submit"
          :disabled="processing || !selectedFile"
          class="w-full"
        >
          <Icon
            v-if="processing"
            name="loader-circle"
            class="w-4 h-4 mr-2 animate-spin"
          />
          <Icon
            v-else
            name="upload"
            class="w-4 h-4 mr-2"
          />
          {{ processing ? 'Uploading...' : 'Upload File' }}
        </Button>
      </Form>
    </div>

    <!-- File Preview -->
    <div v-if="selectedFile" class="rounded-lg border border-border bg-card p-4">
      <h4 class="font-medium mb-2">Selected File:</h4>
      <div class="flex items-center space-x-2 text-sm text-muted-foreground">
        <Icon name="file" class="w-4 h-4" />
        <span>{{ selectedFile.name }}</span>
        <span>({{ formatFileSize(selectedFile.size) }})</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { Upload, LoaderCircle, File } from 'lucide-vue-next'
import Icon from '@/Components/Icon.vue'
import InputError from '@/Components/InputError.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { ref } from 'vue'

interface Props {
  attachableType: string
  attachableId: number
}

const props = defineProps<Props>()

const selectedFile = ref<File | null>(null)
const description = ref('')

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
  }
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'

  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>
