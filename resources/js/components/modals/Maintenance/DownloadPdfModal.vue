<script setup lang="ts">
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    'close': [];
    'download': (enableColors: boolean) => void;
}>();

const { t } = useI18n();

// Color toggle (enable/disable colors in PDF) - default to false (unchecked)
const enableColors = ref<boolean>(false);

const handleDownload = () => {
    emit('download', enableColors.value);
};

const handleClose = () => {
    emit('close');
};

// Reset form when modal opens
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        // Reset colors to unchecked
        enableColors.value = false;
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ t('Generate PDF') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Generate maintenance report PDF') }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <!-- Color Toggle -->
                <div class="flex items-center justify-between space-x-4 p-3 border rounded-md bg-card">
                    <div class="flex-1">
                        <Label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Enable Colors') }}
                        </Label>
                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ t('Use green for income and red for expenses') }}
                        </p>
                    </div>
                    <Switch
                        v-model:checked="enableColors"
                    />
                </div>

                <!-- Action Buttons -->
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="handleClose"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        @click="handleDownload"
                    >
                        {{ t('Generate PDF') }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>

