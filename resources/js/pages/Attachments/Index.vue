<template>
  <div class="min-h-screen bg-background">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground">Attachments</h1>
        <p class="text-muted-foreground mt-2">
          Manage files and documents
        </p>
      </div>

      <!-- Upload Section -->
      <div class="mb-8">
        <FileUpload
          :attachable-type="attachableType"
          :attachable-id="attachableId"
        />
      </div>

      <!-- Attachments List -->
      <div class="space-y-4">
        <h2 class="text-xl font-semibold">Uploaded Files</h2>

        <div v-if="attachments.length === 0" class="text-center py-8 text-muted-foreground">
          <Icon name="file" class="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p>No files uploaded yet.</p>
        </div>

        <div v-else class="grid gap-4">
          <div
            v-for="attachment in attachments"
            :key="attachment.id"
            class="rounded-lg border border-border bg-card p-4 hover:shadow-sm transition-shadow"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <Icon name="file" class="w-8 h-8 text-muted-foreground" />
                <div>
                  <h3 class="font-medium">{{ attachment.file_name }}</h3>
                  <p class="text-sm text-muted-foreground">
                    {{ attachment.file_size_human }} • {{ attachment.file_type.toUpperCase() }}
                  </p>
                  <p v-if="attachment.description" class="text-sm text-muted-foreground mt-1">
                    {{ attachment.description }}
                  </p>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <Button
                  variant="outline"
                  size="sm"
                  @click="downloadFile(attachment)"
                >
                  <Icon name="download" class="w-4 h-4 mr-2" />
                  Download
                </Button>

                <Button
                  variant="outline"
                  size="sm"
                  @click="deleteFile(attachment)"
                  class="text-destructive hover:text-destructive"
                >
                  <Icon name="trash-2" class="w-4 h-4 mr-2" />
                  Delete
                </Button>
              </div>
            </div>

            <div class="mt-3 text-xs text-muted-foreground">
              Uploaded by {{ attachment.uploaded_by?.name }} on {{ formatDate(attachment.created_at) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { File, Download, Trash2 } from 'lucide-vue-next'
import Icon from '@/Components/Icon.vue'
import FileUpload from '@/Components/FileUpload.vue'
import { Button } from '@/Components/ui/button'

interface Attachment {
  id: number
  file_name: string
  file_path: string
  file_type: string
  file_size: number
  file_size_human: string
  description?: string
  uploaded_by?: {
    id: number
    name: string
    email: string
  }
  created_at: string
  updated_at: string
}

interface Props {
  attachments: Attachment[]
  attachableType?: string
  attachableId?: number
}

const props = defineProps<Props>()

const downloadFile = (attachment: Attachment) => {
  window.open(`/panel/attachments/${attachment.id}/download`, '_blank')
}

const deleteFile = (attachment: Attachment) => {
  if (confirm(`Are you sure you want to delete "${attachment.file_name}"?`)) {
    router.delete(`/panel/attachments/${attachment.id}`)
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
