<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import VesselLayout from '@/layouts/VesselLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Trash2, ArrowLeft, CreditCard } from 'lucide-vue-next';
import debtsRoutes from '@/routes/panel/debts';

const props = defineProps<{
    debt: any;
    auth: any;
}>();

const { t, formatCurrency } = useI18n();
const currentVesselId = props.auth.user.current_vessel.id;

const isPaymentModalOpen = ref(false);

const paymentForm = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const submitPayment = () => {
    paymentForm.post(debtsRoutes.payment.url({ vessel: currentVesselId, debtId: props.debt.id }), {
        onSuccess: () => {
            isPaymentModalOpen.value = false;
            paymentForm.reset();
        },
    });
};

const deleteDebt = () => {
    if (confirm(t('Are you sure you want to delete this debt? This action cannot be undone.'))) {
        router.delete(debtsRoutes.destroy.url({ vessel: currentVesselId, debtId: props.debt.id }));
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid':
            return 'default';
        case 'partial':
            return 'secondary';
        case 'pending':
            return 'destructive';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Head :title="t('Debt Details')" />

    <VesselLayout
        :breadcrumbs="[
            { label: t('Debts'), href: debtsRoutes.index.url({ vessel: currentVesselId }) },
            { label: props.debt.description },
        ]"
    >
        <div class="container py-6 space-y-6">
            <div class="flex justify-between items-center">
                <Button variant="ghost" as-child>
                    <Link :href="debtsRoutes.index.url({ vessel: currentVesselId })">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        {{ t('Back to Debts') }}
                    </Link>
                </Button>
                <div class="flex gap-2">
                    <Button variant="destructive" size="icon" @click="deleteDebt">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main Info -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <div class="flex justify-between items-start">
                            <div>
                                <CardTitle class="text-2xl">{{ debt.description }}</CardTitle>
                                <CardDescription class="mt-2">
                                    {{ t('Supplier') }}: <span class="font-medium text-foreground">{{ debt.supplier?.name || t('N/A') }}</span>
                                </CardDescription>
                            </div>
                            <Badge :variant="getStatusColor(debt.status)" class="text-lg px-3 py-1">
                                {{ t(debt.status.charAt(0).toUpperCase() + debt.status.slice(1)) }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="space-y-1">
                                <p class="text-sm text-muted-foreground">{{ t('Total Amount') }}</p>
                                <p class="text-xl font-bold">{{ formatCurrency(debt.amount) }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm text-muted-foreground">{{ t('Paid Amount') }}</p>
                                <p class="text-xl font-bold text-green-600">{{ formatCurrency(debt.paid_amount) }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm text-muted-foreground">{{ t('Remaining') }}</p>
                                <p class="text-xl font-bold text-red-600">{{ formatCurrency(debt.remaining_amount) }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm text-muted-foreground">{{ t('Due Date') }}</p>
                                <p class="font-medium">{{ debt.due_date || t('N/A') }}</p>
                            </div>
                        </div>

                        <div v-if="debt.notes" class="bg-muted/50 p-4 rounded-md">
                            <h4 class="font-semibold mb-2">{{ t('Notes') }}</h4>
                            <p class="text-sm whitespace-pre-wrap">{{ debt.notes }}</p>
                        </div>

                        <!-- Payment Action -->
                        <div v-if="debt.status !== 'paid'" class="flex justify-end">
                            <Dialog v-model:open="isPaymentModalOpen">
                                <DialogTrigger as-child>
                                    <Button>
                                        <CreditCard class="mr-2 h-4 w-4" />
                                        {{ t('Record Payment') }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>{{ t('Record Payment') }}</DialogTitle>
                                        <DialogDescription>
                                            {{ t('Record a payment for this debt. Remaining amount:') }} {{ formatCurrency(debt.remaining_amount) }}
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form @submit.prevent="submitPayment" class="space-y-4">
                                        <div class="space-y-2">
                                            <Label for="payment_amount">{{ t('Amount') }}</Label>
                                            <Input
                                                id="payment_amount"
                                                type="number"
                                                step="0.01"
                                                :max="debt.remaining_amount"
                                                v-model="paymentForm.amount"
                                                required
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="payment_date">{{ t('Date') }}</Label>
                                            <Input
                                                id="payment_date"
                                                type="date"
                                                v-model="paymentForm.payment_date"
                                                required
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="payment_notes">{{ t('Notes') }}</Label>
                                            <Textarea
                                                id="payment_notes"
                                                v-model="paymentForm.notes"
                                                :placeholder="t('Payment details...')"
                                            />
                                        </div>
                                        <DialogFooter>
                                            <Button type="submit" :disabled="paymentForm.processing">
                                                {{ t('Save Payment') }}
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment History -->
                <Card class="md:col-span-1">
                    <CardHeader>
                        <CardTitle>{{ t('Payment History') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="debt.payments.length === 0" class="text-center text-muted-foreground py-8">
                            {{ t('No payments recorded yet.') }}
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="payment in debt.payments" :key="payment.id" class="border-b pb-4 last:border-0 last:pb-0">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold text-green-600">{{ formatCurrency(payment.amount) }}</span>
                                    <span class="text-sm text-muted-foreground">{{ payment.payment_date }}</span>
                                </div>
                                <p v-if="payment.notes" class="text-xs text-muted-foreground italic">
                                    {{ payment.notes }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </VesselLayout>
</template>
