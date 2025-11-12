<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';

interface Props {
    open: boolean;
    countdown?: number; // Countdown in seconds
}

const props = withDefaults(defineProps<Props>(), {
    countdown: 5,
});

const emit = defineEmits<{
    close: [];
    cancel: [];
}>();

const { t } = useI18n();
const remainingSeconds = ref(props.countdown);

let countdownInterval: ReturnType<typeof setInterval> | null = null;

const startCountdown = () => {
    remainingSeconds.value = props.countdown;

    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    countdownInterval = setInterval(() => {
        remainingSeconds.value--;

        if (remainingSeconds.value <= 0) {
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
        }
    }, 1000);
};

watch(() => props.open, (newValue) => {
    if (newValue) {
        startCountdown();
    } else {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    }
});

onMounted(() => {
    if (props.open) {
        startCountdown();
    }
});

const handleClose = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    emit('close');
};

const handleCancel = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    emit('cancel');
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => !val && handleClose()">
        <DialogContent :closeable="true" class="sm:max-w-md">
            <div class="p-6">
                <div class="text-center">
                    <!-- Loading Spinner -->
                    <div class="flex justify-center mb-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-2">
                        {{ t('Generating PDF') }}
                    </h3>

                    <!-- Message -->
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4">
                        {{ t('Please wait while we generate your transaction report...') }}
                    </p>

                    <!-- Countdown -->
                    <div v-if="remainingSeconds > 0" class="text-2xl font-bold text-primary mb-4">
                        {{ remainingSeconds }}
                    </div>

                    <!-- Ready Message -->
                    <div v-else class="text-sm text-green-600 dark:text-green-400 mb-4">
                        {{ t('PDF is ready! Download will start automatically.') }}
                    </div>

                    <!-- Cancel Button -->
                    <div v-if="remainingSeconds > 0" class="mt-4">
                        <Button
                            variant="outline"
                            @click="handleCancel"
                            class="w-full"
                        >
                            {{ t('Cancel') }}
                        </Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

