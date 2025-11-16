<script setup lang="ts">
import { ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';
import { useNotifications } from '@/composables/useNotifications';
import { router } from '@inertiajs/vue3';

interface Props {
    open: boolean;
    vesselId: string;
    mareaId?: number | string;
    maintenanceId?: number | string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { t } = useI18n();
const { notify } = useNotifications();

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const isUploading = ref(false);
const importResults = ref<{
    success_count: number;
    error_count: number;
    skipped_count: number;
    errors: Array<{ row: number; error: string }>;
    skipped: Array<{ row: number; reason: string }>;
} | null>(null);
const showResults = ref(false);
const skipDuplicates = ref(true); // Default: skip duplicate transaction numbers
const ignoreTransactionNumbers = ref(true); // Default: ignore transaction numbers (create new only)

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];

        // Validate file type
        const validTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel', // .xls
            'application/vnd.ms-excel.sheet.macroEnabled.12', // .xlsm
        ];

        const validExtensions = ['.xlsx', '.xls', '.xlsm'];
        const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();

        if (!validTypes.includes(file.type) && !validExtensions.includes(fileExtension)) {
            notify.error(t('Please select a valid Excel file (.xlsx, .xls, .xlsm)'));
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            selectedFile.value = null;
            return;
        }

        selectedFile.value = file;
    }
};

const handleImport = async () => {
    if (!selectedFile.value) {
        notify.error(t('Please select a file to import'));
        return;
    }

    isUploading.value = true;
    importResults.value = null;
    showResults.value = false;

    // Track start time for minimum loading duration
    const startTime = Date.now();
    const MIN_LOADING_TIME = 2000; // 2 seconds minimum

    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);
        // Get the current value at the moment of import - read directly from ref
        const skipValue = skipDuplicates.value ? '1' : '0';
        formData.append('skip_duplicates', skipValue);
        const ignoreValue = ignoreTransactionNumbers.value ? '1' : '0';
        formData.append('ignore_transaction_numbers', ignoreValue);

        // Add marea_id or maintenance_id if provided (for linking transactions)
        if (props.mareaId) {
            formData.append('marea_id', props.mareaId.toString());
        }
        if (props.maintenanceId) {
            formData.append('maintenance_id', props.maintenanceId.toString());
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            notify.error(t('CSRF token not found. Please refresh the page.'));
            isUploading.value = false;
            return;
        }

        const response = await fetch(`/panel/${props.vesselId}/movimentations/import-excel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        let data: any = null;

        if (contentType && contentType.includes('application/json')) {
            try {
                data = await response.json();
            } catch (jsonError) {
                console.error('Failed to parse JSON response:', jsonError);
                notify.error(t('Import failed. Server returned an invalid response.'));
                const elapsed = Date.now() - startTime;
                if (elapsed < MIN_LOADING_TIME) {
                    await new Promise(resolve => setTimeout(resolve, MIN_LOADING_TIME - elapsed));
                }
                isUploading.value = false;
                return;
            }
        } else {
            // If not JSON, get text response
            const text = await response.text();
            console.error('Non-JSON response:', text);
            notify.error(t('Import failed. Server returned an invalid response.'));

            // Ensure minimum loading time
            const elapsed = Date.now() - startTime;
            if (elapsed < MIN_LOADING_TIME) {
                await new Promise(resolve => setTimeout(resolve, MIN_LOADING_TIME - elapsed));
            }
            isUploading.value = false;
            return;
        }

        // Ensure minimum loading time before processing results
        const elapsed = Date.now() - startTime;
        if (elapsed < MIN_LOADING_TIME) {
            await new Promise(resolve => setTimeout(resolve, MIN_LOADING_TIME - elapsed));
        }

        if (response.ok && data) {
            const successCount = data.success_count || 0;
            const errorCount = data.error_count || 0;
            const skippedCount = data.skipped_count || 0;

            // Store results to show in modal
            importResults.value = {
                success_count: successCount,
                error_count: errorCount,
                skipped_count: skippedCount,
                errors: data.errors || [],
                skipped: data.skipped || [],
            };

            // Show results in modal
            showResults.value = true;

            // Build comprehensive notification message
            let notificationMessage = '';
            if (successCount > 0) {
                notificationMessage = t('{count} transaction(s) have been imported successfully', { count: successCount });
                if (skippedCount > 0) {
                    notificationMessage += ` ${t('and {count} row(s) were skipped', { count: skippedCount })}.`;
                } else if (errorCount > 0) {
                    notificationMessage += ` ${t('but {count} row(s) had errors', { count: errorCount })}.`;
                } else {
                    notificationMessage += '.';
                }
                notify.success(notificationMessage, { duration: 6000 });
            } else if (skippedCount > 0 && errorCount === 0) {
                notificationMessage = t('All {count} row(s) were skipped (transactions already exist). No new transactions were imported.', { count: skippedCount });
                notify.info(notificationMessage, { duration: 6000 });
            } else if (errorCount > 0) {
                notificationMessage = t('Import completed with {count} error(s). Please check the details below.', { count: errorCount });
                notify.error(notificationMessage, { duration: 8000 });
            } else {
                notificationMessage = t('Import completed, but no transactions were imported.');
                notify.info(notificationMessage, { duration: 5000 });
            }

            // Refresh transactions list only if import was successful (no errors)
            // Only reload if transactions were imported successfully and there are no errors
            if (successCount > 0 && errorCount === 0) {
                // Small delay to ensure backend has fully processed the import
                setTimeout(() => {
                    // If importing from marea or maintenance page, reload entire page to update marea/maintenance data
                    // Otherwise, just reload transactions data
                    if (props.mareaId || props.maintenanceId) {
                        router.reload({
                            preserveState: true,
                            preserveScroll: true,
                            onFinish: () => {
                                // Data has been refreshed - marea/maintenance and transactions are now updated
                                emit('success');
                            },
                        });
                    } else {
                        // Reload transactions data to update the list
                        // This preserves current filters, pagination, and scroll position
                        router.reload({
                            only: ['transactions'],
                            preserveState: true,
                            preserveScroll: true,
                            onFinish: () => {
                                // Data has been refreshed - transactions list is now updated
                                emit('success');
                            },
                        });
                    }
                }, 500); // Small delay to ensure backend commit is complete
            }

            // Auto-close after 4 seconds if successful and no errors (or only skipped)
            if (data && typeof data === 'object' && 'success' in data && data.success && errorCount === 0) {
                setTimeout(() => {
                    handleClose();
                }, 4000);
            }
        } else {
            // Safely access data properties - data might be undefined or null
            const errorMessage = (data && typeof data === 'object' && (data.message || data.error)) || t('Import failed. Please check the file format and try again.');

            // Show detailed errors if available
            if (data && data.errors && data.errors.length > 0) {
                const errorMessages = data.errors
                    .slice(0, 10)
                    .map((e: any) => `Row ${e.row}: ${e.error}`)
                    .join('\n');
                notify.error(`${errorMessage}\n\n${errorMessages}`, { duration: 15000 });
            } else {
                notify.error(errorMessage, { duration: 8000 });
            }

            console.error('Import failed:', data);
        }
    } catch (error) {
        console.error('Import error:', error);
        const errorMessage = error instanceof Error ? error.message : t('An error occurred during import. Please try again.');

        // Ensure minimum loading time even on error
        const elapsed = Date.now() - startTime;
        if (elapsed < MIN_LOADING_TIME) {
            await new Promise(resolve => setTimeout(resolve, MIN_LOADING_TIME - elapsed));
        }

        notify.error(errorMessage, { duration: 8000 });
    } finally {
        isUploading.value = false;
    }
};

const handleClose = () => {
    // Don't reset skipDuplicates here - let user's choice persist
    selectedFile.value = null;
    importResults.value = null;
    showResults.value = false;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    emit('close');
};

const handleImportAgain = () => {
    importResults.value = null;
    showResults.value = false;
    selectedFile.value = null;
    // Don't reset skipDuplicates - keep user's choice
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const openFileDialog = () => {
    fileInput.value?.click();
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('Import Transactions from Excel') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Upload an Excel file to import transactions. The file should match the export format.') }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4 relative">
                <!-- Loading Overlay -->
                <div v-if="isUploading" class="absolute inset-0 bg-background/90 backdrop-blur-sm z-10 flex items-center justify-center rounded-lg">
                    <div class="text-center space-y-4">
                        <div class="relative">
                            <div class="animate-spin rounded-full h-16 w-16 border-4 border-primary/20 border-t-primary mx-auto"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-8 h-8 text-primary animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <p class="text-base font-semibold text-card-foreground dark:text-card-foreground">
                                {{ t('Importing transactions...') }}
                            </p>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ t('Processing your Excel file, please wait') }}
                            </p>
                            <div class="flex items-center justify-center gap-2 mt-3">
                                <div class="h-2 w-2 bg-primary rounded-full animate-bounce" style="animation-delay: 0s"></div>
                                <div class="h-2 w-2 bg-primary rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                <div class="h-2 w-2 bg-primary rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results View -->
                <div v-if="showResults && importResults" class="space-y-4">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-2">
                            {{ t('Import Results') }}
                        </h3>
                    </div>

                    <!-- Summary Stats -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ importResults.success_count }}
                            </div>
                            <div class="text-xs text-green-700 dark:text-green-300 mt-1">
                                {{ t('Imported') }}
                            </div>
                        </div>
                        <div class="p-3 rounded-md bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-center">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ importResults.skipped_count }}
                            </div>
                            <div class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                                {{ t('Skipped') }}
                            </div>
                        </div>
                        <div class="p-3 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-center">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ importResults.error_count }}
                            </div>
                            <div class="text-xs text-red-700 dark:text-red-300 mt-1">
                                {{ t('Errors') }}
                            </div>
                        </div>
                    </div>

                    <!-- Errors List -->
                    <div v-if="importResults.errors.length > 0" class="space-y-2">
                        <h4 class="text-sm font-semibold text-red-600 dark:text-red-400">
                            {{ t('Errors') }} ({{ importResults.errors.length }})
                        </h4>
                        <div class="max-h-40 overflow-y-auto space-y-1 p-2 bg-red-50 dark:bg-red-900/10 rounded-md border border-red-200 dark:border-red-800">
                            <div
                                v-for="error in importResults.errors.slice(0, 10)"
                                :key="error.row"
                                class="text-xs text-red-700 dark:text-red-300"
                            >
                                <span class="font-medium">{{ t('Row') }} {{ error.row }}:</span> {{ error.error }}
                            </div>
                            <div v-if="importResults.errors.length > 10" class="text-xs text-red-600 dark:text-red-400 font-medium">
                                {{ t('... and {count} more errors', { count: importResults.errors.length - 10 }) }}
                            </div>
                        </div>
                    </div>

                    <!-- Skipped List -->
                    <div v-if="importResults.skipped.length > 0" class="space-y-2">
                        <h4 class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                            {{ t('Skipped Rows') }} ({{ importResults.skipped.length }})
                        </h4>
                        <div class="max-h-40 overflow-y-auto space-y-1 p-2 bg-yellow-50 dark:bg-yellow-900/10 rounded-md border border-yellow-200 dark:border-yellow-800">
                            <div
                                v-for="skip in importResults.skipped.slice(0, 10)"
                                :key="skip.row"
                                class="text-xs text-yellow-700 dark:text-yellow-300"
                            >
                                <span class="font-medium">{{ t('Row') }} {{ skip.row }}:</span> {{ skip.reason }}
                            </div>
                            <div v-if="importResults.skipped.length > 10" class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">
                                {{ t('... and {count} more skipped rows', { count: importResults.skipped.length - 10 }) }}
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            v-if="importResults.error_count > 0"
                            variant="outline"
                            @click="handleImportAgain"
                        >
                            {{ t('Try Again') }}
                        </Button>
                        <Button
                            @click="handleClose"
                        >
                            {{ t('Close') }}
                        </Button>
                    </div>
                </div>

                <!-- Import Form -->
                <div v-else class="space-y-6">
                    <!-- File Input -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Excel File') }}
                        </label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.xlsm,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                            @change="handleFileSelect"
                            class="hidden"
                            :disabled="isUploading"
                        />
                        <div class="flex items-center gap-3">
                            <Button
                                variant="outline"
                                @click="openFileDialog"
                                type="button"
                                :disabled="isUploading"
                            >
                                {{ t('Select File') }}
                            </Button>
                            <span v-if="selectedFile" class="text-sm text-card-foreground dark:text-card-foreground">
                                {{ selectedFile.name }}
                            </span>
                            <span v-else class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ t('No file selected') }}
                            </span>
                        </div>
                    </div>

                    <!-- Skip Duplicates Switch -->
                    <div class="flex items-center justify-between p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
                        <div class="flex-1">
                            <Label for="skip_duplicates" class="font-medium cursor-pointer" @click="skipDuplicates = !skipDuplicates">
                                {{ t('Skip Duplicate Transaction Numbers') }}
                            </Label>
                            <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                <span v-if="skipDuplicates">
                                    {{ t('If enabled, transactions with existing transaction numbers will be skipped') }}
                                </span>
                                <span v-else>
                                    {{ t('If disabled, existing transactions will be updated instead of skipped') }}
                                </span>
                            </p>
                            <p class="text-xs font-medium mt-1" :class="skipDuplicates ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'">
                                {{ skipDuplicates ? t('Currently: Skipping duplicates') : t('Currently: Updating duplicates') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Switch
                                id="skip_duplicates"
                                v-model:checked="skipDuplicates"
                                :disabled="isUploading || ignoreTransactionNumbers"
                            />
                            <span class="text-xs font-mono text-muted-foreground">
                                ({{ skipDuplicates ? 'ON' : 'OFF' }})
                            </span>
                        </div>
                    </div>

                    <!-- Ignore Transaction Numbers Switch -->
                    <div class="flex items-center justify-between p-4 border rounded-lg bg-muted/50 dark:bg-muted/30">
                        <div class="flex-1">
                            <Label for="ignore_transaction_numbers" class="font-medium cursor-pointer" @click="ignoreTransactionNumbers = !ignoreTransactionNumbers">
                                {{ t('Ignore Transaction Numbers (Create New Only)') }}
                            </Label>
                            <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                <span v-if="ignoreTransactionNumbers">
                                    {{ t('If enabled, transaction numbers will be ignored and new transactions will always be created') }}
                                </span>
                                <span v-else>
                                    {{ t('If disabled, transaction numbers from the file will be used') }}
                                </span>
                            </p>
                            <p class="text-xs font-medium mt-1" :class="ignoreTransactionNumbers ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'">
                                {{ ignoreTransactionNumbers ? t('Currently: Ignoring transaction numbers') : t('Currently: Using transaction numbers') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Switch
                                id="ignore_transaction_numbers"
                                v-model:checked="ignoreTransactionNumbers"
                                :disabled="isUploading"
                            />
                            <span class="text-xs font-mono text-muted-foreground">
                                ({{ ignoreTransactionNumbers ? 'ON' : 'OFF' }})
                            </span>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="p-3 border rounded-md bg-muted/50 dark:bg-muted/20">
                        <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                            {{ t('The Excel file should include all required columns. Use the export function to get a template with the correct format.') }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            variant="outline"
                            @click="handleClose"
                            :disabled="isUploading"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button
                            @click="handleImport"
                            :disabled="!selectedFile || isUploading"
                        >
                            <span v-if="isUploading">{{ t('Importing...') }}</span>
                            <span v-else>{{ t('Import') }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

