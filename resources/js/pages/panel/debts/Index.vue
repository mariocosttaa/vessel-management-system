<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
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
import DataTable from '@/components/ui/DataTable.vue';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Plus, Search, Eye } from 'lucide-vue-next';
import Pagination from '@/components/ui/Pagination.vue';
import debtsRoutes from '@/routes/panel/debts';
import { debounce } from 'lodash-es';

const props = defineProps<{
    debts: {
        data: any[];
        links: any[];
        meta: any;
    };
    filters: {
        status?: string;
        search?: string;
    };
    auth: any;
}>();

const { t, formatCurrency } = useI18n();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const currentVesselId = props.auth.user.current_vessel.id;

const handleSearch = debounce(() => {
    router.get(
        debtsRoutes.index.url({ vessel: currentVesselId }),
        {
            search: search.value,
            status: status.value === 'all' ? null : status.value,
        },
        { preserveState: true, replace: true }
    );
}, 300);

watch([search, status], handleSearch);

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid':
            return 'default'; // Greenish usually or default badge
        case 'partial':
            return 'secondary'; // Yellowish
        case 'pending':
            return 'destructive'; // Red
        default:
            return 'outline';
    }
};

const statusOptions = computed(() => [
    { value: 'all', label: t('All Statuses') },
    { value: 'pending', label: t('Pending') },
    { value: 'partial', label: t('Partial') },
    { value: 'paid', label: t('Paid') },
]);

const getActions = (item: any) => [
    {
        label: t('View'),
        icon: 'eye',
        onClick: () => {
            router.visit(debtsRoutes.show.url({ vessel: currentVesselId, debtId: item.id }));
        },
    },
];
</script>

<template>
    <Head :title="t('Debts')" />

    <VesselLayout :breadcrumbs="[{ title: t('Debts'), href: debtsRoutes.index.url({ vessel: currentVesselId }) }]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">{{ t('Debts') }}</h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">{{ t('Manage your outstanding debts and payments.') }}</p>
                    </div>
                    <Link
                        :href="debtsRoutes.create.url({ vessel: currentVesselId })"
                        class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        {{ t('New Debt') }}
                    </Link>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-4">
                <div class="flex items-center gap-3">
                    <!-- Search -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                            <Input
                                v-model="search"
                                type="text"
                                :placeholder="t('Search debts...')"
                                class="pl-10"
                            />
                        </div>
                    </div>
                    <div class="w-full md:w-[200px]">
                        <Select
                            v-model="status"
                            :options="statusOptions"
                            :placeholder="t('Filter by status')"
                            searchable
                        />
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <DataTable
                :columns="[
                    { key: 'description', label: t('Description'), sortable: false },
                    { key: 'holder', label: t('Holder'), sortable: false },
                    { key: 'amount', label: t('Amount'), sortable: false },
                    { key: 'paid_amount', label: t('Paid'), sortable: false },
                    { key: 'remaining_amount', label: t('Remaining'), sortable: false },
                    { key: 'status', label: t('Status'), sortable: false },
                    { key: 'due_date', label: t('Due Date'), sortable: false },
                ]"
                :data="debts.data"
                :actions="getActions"
                :loading="false"
                :empty-message="t('No debts found.')"
            >
                <template #cell-holder="{ item }">
                    {{ item.holder || '-' }}
                </template>
                <template #cell-status="{ item }">
                    <Badge :variant="getStatusColor(item.status)">
                        {{ t(item.status.charAt(0).toUpperCase() + item.status.slice(1)) }}
                    </Badge>
                </template>
                <template #cell-due_date="{ item }">
                    {{ item.due_date || '-' }}
                </template>
            </DataTable>

            <!-- Pagination -->
            <Pagination
                v-if="debts.links && debts.links.length > 3"
                :links="debts.links"
                :meta="debts"
            />
        </div>
    </VesselLayout>
</template>
