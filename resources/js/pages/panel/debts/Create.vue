<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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

import { Select } from '@/components/ui/select';
import debtsRoutes from '@/routes/panel/debts';

const props = defineProps<{
    auth: any;
}>();

const { t } = useI18n();
const currentVesselId = props.auth.user.current_vessel.id;

const form = useForm({
    description: '',
    holder: '',
    amount: '',
    due_date: '',
    notes: '',
});

const submit = () => {
    form.post(debtsRoutes.store.url({ vessel: currentVesselId }));
};
</script>

<template>
    <Head :title="t('New Debt')" />

    <VesselLayout :breadcrumbs="[{ title: t('Debts'), href: debtsRoutes.index.url({ vessel: currentVesselId }) }, { title: t('New Debt') }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card">
                <CardHeader>
                    <CardTitle>{{ t('Create New Debt') }}</CardTitle>
                    <CardDescription>
                        {{ t('Register a new outstanding debt.') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="description">{{ t('Description') }}</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                :placeholder="t('e.g., Fuel supply invoice')"
                                required
                            />
                            <p v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="amount">{{ t('Amount') }}</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    v-model="form.amount"
                                    placeholder="0.00"
                                    required
                                />
                                <p v-if="form.errors.amount" class="text-sm text-destructive">
                                    {{ form.errors.amount }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="due_date">{{ t('Due Date') }}</Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    v-model="form.due_date"
                                />
                                <p v-if="form.errors.due_date" class="text-sm text-destructive">
                                    {{ form.errors.due_date }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="holder">{{ t('Holder') }}</Label>
                            <Input
                                id="holder"
                                v-model="form.holder"
                                :placeholder="t('e.g., Company name or person')"
                            />
                            <p v-if="form.errors.holder" class="text-sm text-destructive">
                                {{ form.errors.holder }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="notes">{{ t('Notes') }}</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                :placeholder="t('Additional details...')"
                                rows="3"
                                class="w-full rounded-lg border border-input dark:border-input bg-background dark:bg-background px-3 py-2 text-sm text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent transition-colors resize-none"
                            ></textarea>
                            <p v-if="form.errors.notes" class="text-sm text-destructive">
                                {{ form.errors.notes }}
                            </p>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <Button variant="outline" type="button" @click="$inertia.visit(debtsRoutes.index.url({ vessel: currentVesselId }))">
                                {{ t('Cancel') }}
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ t('Create Debt') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </VesselLayout>
</template>
