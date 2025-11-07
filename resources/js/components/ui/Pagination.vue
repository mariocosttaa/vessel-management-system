<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    links: PaginationLink[];
    meta: PaginationMeta;
    showInfo?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showInfo: true,
});

const getPageNumbers = () => {
    const current = props.meta.current_page;
    const last = props.meta.last_page;
    const delta = 2;
    const range = [];
    const rangeWithDots = [];

    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }

    if (current - delta > 2) {
        rangeWithDots.push(1, '...');
    } else {
        rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (current + delta < last - 1) {
        rangeWithDots.push('...', last);
    } else {
        rangeWithDots.push(last);
    }

    return rangeWithDots;
};
</script>

<template>
    <div v-if="links.length > 3" class="bg-card dark:bg-card px-4 py-3 border-t border-border dark:border-border sm:px-6">
        <div class="flex items-center justify-between">
            <!-- Mobile pagination -->
            <div class="flex-1 flex justify-between sm:hidden">
                <Link
                    v-if="links[0]?.url"
                    :href="links[0].url"
                    class="relative inline-flex items-center px-4 py-2 border border-border dark:border-border text-sm font-medium rounded-lg text-card-foreground dark:text-card-foreground bg-background dark:bg-background hover:bg-muted dark:hover:bg-muted transition-colors"
                >
                    Previous
                </Link>
                <Link
                    v-if="links[links.length - 1]?.url"
                    :href="links[links.length - 1].url"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-border dark:border-border text-sm font-medium rounded-lg text-card-foreground dark:text-card-foreground bg-background dark:bg-background hover:bg-muted dark:hover:bg-muted transition-colors"
                >
                    Next
                </Link>
            </div>

            <!-- Desktop pagination -->
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <!-- Info -->
                <div v-if="showInfo">
                    <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                        Showing
                        <span class="font-medium">{{ meta.from }}</span>
                        to
                        <span class="font-medium">{{ meta.to }}</span>
                        of
                        <span class="font-medium">{{ meta.total }}</span>
                        results
                    </p>
                </div>

                <!-- Pagination controls -->
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <!-- Previous button -->
                        <Link
                            v-if="links[0]?.url"
                            :href="links[0].url"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-border dark:border-border bg-background dark:bg-background text-sm font-medium text-card-foreground dark:text-card-foreground hover:bg-muted dark:hover:bg-muted transition-colors"
                        >
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </Link>

                        <!-- Page numbers -->
                        <template v-for="page in getPageNumbers()" :key="page">
                            <span
                                v-if="page === '...'"
                                class="relative inline-flex items-center px-4 py-2 border border-border dark:border-border bg-background dark:bg-background text-sm font-medium text-muted-foreground dark:text-muted-foreground"
                            >
                                ...
                            </span>
                            <Link
                                v-else
                                :href="links.find(link => link.label === page.toString())?.url || '#'"
                                :class="[
                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors',
                                    links.find(link => link.label === page.toString())?.active
                                        ? 'z-10 bg-primary border-primary text-primary-foreground'
                                        : 'bg-background dark:bg-background border-border dark:border-border text-card-foreground dark:text-card-foreground hover:bg-muted dark:hover:bg-muted'
                                ]"
                            >
                                {{ page }}
                            </Link>
                        </template>

                        <!-- Next button -->
                        <Link
                            v-if="links[links.length - 1]?.url"
                            :href="links[links.length - 1].url"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-border dark:border-border bg-background dark:bg-background text-sm font-medium text-card-foreground dark:text-card-foreground hover:bg-muted dark:hover:bg-muted transition-colors"
                        >
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
