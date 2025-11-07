<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';

interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    width?: string;
}

interface Action {
    label: string;
    icon: string;
    onClick: (item: any) => void;
    variant?: 'default' | 'destructive';
}

interface Props {
    columns: Column[];
    data: any[];
    clickable?: boolean;
    onRowClick?: (item: any) => void;
    actions?: Action[];
    sortField?: string;
    sortDirection?: 'asc' | 'desc';
    onSort?: (field: string) => void;
    loading?: boolean;
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    clickable: false,
    loading: false,
    emptyMessage: 'No data available',
});

// Dropdown state
const openDropdownId = ref<number | null>(null);

// Click outside handler
const handleClickOutside = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.dropdown-container')) {
        closeActionsDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Dropdown methods
const toggleActionsDropdown = (itemId: number) => {
    openDropdownId.value = openDropdownId.value === itemId ? null : itemId;
};

const closeActionsDropdown = () => {
    openDropdownId.value = null;
};

const handleRowClick = (item: any) => {
    if (props.clickable && props.onRowClick) {
        props.onRowClick(item);
    }
};

const handleActionClick = (action: Action, item: any) => {
    action.onClick(item);
    closeActionsDropdown();
};

const handleSort = (field: string) => {
    if (props.onSort) {
        props.onSort(field);
    }
};
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border dark:divide-border">
                <thead class="bg-muted/50 dark:bg-muted/50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            :class="[
                                'px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider',
                                column.sortable ? 'cursor-pointer hover:bg-muted dark:hover:bg-muted transition-colors' : '',
                                column.width ? `w-${column.width}` : ''
                            ]"
                            @click="column.sortable ? handleSort(column.key) : null"
                        >
                            <div class="flex items-center space-x-1">
                                <span>{{ column.label }}</span>
                                <Icon
                                    v-if="column.sortable && sortField === column.key"
                                    :name="sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'"
                                    class="w-4 h-4"
                                />
                            </div>
                        </th>
                        <th v-if="actions && actions.length > 0" class="px-6 py-3 text-right text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-card dark:bg-card divide-y divide-border dark:divide-border">
                    <tr v-if="loading">
                        <td :colspan="columns.length + (actions?.length ? 1 : 0)" class="px-6 py-12 text-center">
                            <div class="flex items-center justify-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                <span class="ml-2 text-muted-foreground dark:text-muted-foreground">Loading...</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-else-if="data.length === 0">
                        <td :colspan="columns.length + (actions?.length ? 1 : 0)" class="px-6 py-12 text-center text-muted-foreground dark:text-muted-foreground">
                            {{ emptyMessage }}
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="item in data"
                        :key="item.id"
                        @click="handleRowClick(item)"
                        :class="[
                            'hover:bg-muted/50 dark:hover:bg-muted/50 transition-colors',
                            clickable ? 'cursor-pointer' : ''
                        ]"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground dark:text-card-foreground"
                        >
                            <slot :name="`cell-${column.key}`" :item="item" :value="item[column.key]">
                                {{ item[column.key] }}
                            </slot>
                        </td>
                        <td v-if="actions && actions.length > 0" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                            <div class="relative dropdown-container">
                                <button
                                    @click="toggleActionsDropdown(item.id)"
                                    class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-muted dark:hover:bg-muted transition-colors"
                                >
                                    <Icon name="menu" class="w-4 h-4 text-muted-foreground dark:text-muted-foreground" />
                                </button>

                                <!-- Actions Dropdown -->
                                <div
                                    v-if="openDropdownId === item.id"
                                    class="absolute right-0 mt-2 w-48 bg-card dark:bg-card border border-border dark:border-border rounded-lg shadow-lg z-10"
                                >
                                    <div class="py-1">
                                        <button
                                            v-for="action in actions"
                                            :key="action.label"
                                            @click="handleActionClick(action, item)"
                                            :class="[
                                                'flex items-center w-full px-4 py-2 text-sm transition-colors',
                                                action.variant === 'destructive'
                                                    ? 'text-destructive dark:text-destructive hover:bg-muted dark:hover:bg-muted'
                                                    : 'text-card-foreground dark:text-card-foreground hover:bg-muted dark:hover:bg-muted'
                                            ]"
                                        >
                                            <Icon :name="action.icon" class="w-4 h-4 mr-3" />
                                            {{ action.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
