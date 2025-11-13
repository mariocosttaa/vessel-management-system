<script setup lang="ts">
import { ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    confirm: [enableColors: boolean];
}>();

const { t } = useI18n();

// Color toggle (default to false - unchecked)
const enableColors = ref<boolean>(false);

const handleConfirm = () => {
    emit('confirm', enableColors.value);
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('PDF Download Options') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Choose your PDF download preferences') }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <!-- Color Toggle -->
                <div class="flex items-center justify-between space-x-4 p-4 border rounded-lg bg-card">
                    <div class="flex-1">
                        <label class="text-sm font-medium text-card-foreground dark:text-card-foreground">
                            {{ t('Enable Colors') }}
                        </label>
                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                            {{ t('Use green for income and red for expenses') }}
                        </p>
                    </div>
                    <Switch
                        v-model:checked="enableColors"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    @click="handleClose"
                >
                    {{ t('Cancel') }}
                </Button>
                <Button
                    @click="handleConfirm"
                >
                    {{ t('Download PDF') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

