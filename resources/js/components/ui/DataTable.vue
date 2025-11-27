<script setup lang="ts">
import { ref, onMounted, onUnmounted, Teleport } from 'vue';
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

type ActionsType = Action[] | ((item: any) => Action[]);

interface Props {
    columns: Column[];
    data: any[];
    clickable?: boolean;
    onRowClick?: (item: any) => void;
    actions?: ActionsType;
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
const dropdownPosition = ref<{ top: number; bottom: number; right: number; openUp: boolean } | null>(null);
const currentItem = ref<any>(null);

// Click outside handler
const handleClickOutside = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.dropdown-container') && !target.closest('.dropdown-menu-portal')) {
        closeActionsDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('scroll', closeActionsDropdown, true);
    window.addEventListener('resize', closeActionsDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', closeActionsDropdown, true);
    window.removeEventListener('resize', closeActionsDropdown);
});

// Dropdown methods
const toggleActionsDropdown = (itemId: number, event?: MouseEvent) => {
    if (openDropdownId.value === itemId) {
        closeActionsDropdown();
        return;
    }

    const button = event?.currentTarget as HTMLElement;
    if (button) {
        const rect = button.getBoundingClientRect();
        const dropdownHeight = 200; // Approximate height of dropdown menu (adjust if needed)
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;
        const minSpace = 8; // Minimum space from viewport edge
        const openUp = spaceBelow < dropdownHeight + minSpace && spaceAbove > spaceBelow;

        // Calculate position ensuring dropdown stays within viewport
        let top = 0;
        let bottom = 0;

        if (openUp) {
            // Position above button, but ensure it doesn't go off top of screen
            const calculatedBottom = window.innerHeight - rect.top + minSpace;
            const maxBottom = window.innerHeight - minSpace; // Leave some space at top
            bottom = Math.min(calculatedBottom, maxBottom);
        } else {
            // Position below button, but ensure it doesn't go off bottom of screen
            const calculatedTop = rect.bottom + minSpace;
            const maxTop = window.innerHeight - dropdownHeight - minSpace;
            top = Math.min(calculatedTop, maxTop);
        }

        dropdownPosition.value = {
            top: openUp ? 0 : top,
            bottom: openUp ? bottom : 0,
            right: window.innerWidth - rect.right,
            openUp,
        };
    }

    const item = props.data.find(i => i.id === itemId);
    currentItem.value = item;
    openDropdownId.value = itemId;
};

const closeActionsDropdown = () => {
    openDropdownId.value = null;
    dropdownPosition.value = null;
    currentItem.value = null;
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

// Get actions for a specific item (supports both array and function)
const getActionsForItem = (item: any): Action[] => {
    if (!props.actions) return [];
    if (typeof props.actions === 'function') {
        return props.actions(item);
    }
    return props.actions;
};
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-x-auto overflow-y-visible">
        <div>
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
                                    v-if="column.sortable && props.sortField === column.key"
                                    :name="props.sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'"
                                    class="w-4 h-4"
                                />
                            </div>
                        </th>
                        <th v-if="actions && (Array.isArray(actions) ? actions.length > 0 : true)" class="px-6 py-3 text-right text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-card dark:bg-card divide-y divide-border dark:divide-border">
                    <tr v-if="loading">
                        <td :colspan="columns.length + (actions ? 1 : 0)" class="px-6 py-12 text-center">
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
                        <td v-if="getActionsForItem(item) && getActionsForItem(item).length > 0" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                            <div class="relative dropdown-container">
                                <button
                                    @click="toggleActionsDropdown(item.id, $event)"
                                    class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-muted dark:hover:bg-muted transition-colors"
                                >
                                    <Icon name="menu" class="w-4 h-4 text-muted-foreground dark:text-muted-foreground" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Actions Dropdown Menu (Teleported) -->
        <Teleport to="body">
            <div
                v-if="openDropdownId !== null && dropdownPosition && currentItem"
                class="dropdown-menu-portal fixed z-[9999]"
                :style="{
                    top: dropdownPosition.openUp ? 'auto' : `${dropdownPosition.top}px`,
                    bottom: dropdownPosition.openUp ? `${dropdownPosition.bottom}px` : 'auto',
                    right: `${dropdownPosition.right}px`,
                }"
                @click.stop
            >
                <div class="w-48 bg-card dark:bg-card border border-border dark:border-border rounded-lg shadow-lg max-h-[200px] overflow-y-auto">
                    <div class="py-1">
                        <button
                            v-for="action in getActionsForItem(currentItem)"
                            :key="action.label"
                            @click="handleActionClick(action, currentItem)"
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
        </Teleport>
    </div>
</template>
