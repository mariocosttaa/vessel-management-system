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
    <div v-if="links.length > 3" class="bg-card dark:bg-card border-t border-border dark:border-border">
        <div class="px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between">
                <!-- Mobile pagination -->
                <div class="flex-1 flex justify-between sm:hidden gap-3">
                    <Link
                        v-if="links[0]?.url"
                        :href="links[0].url"
                        preserve-scroll
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-border dark:border-border bg-background dark:bg-background text-card-foreground dark:text-card-foreground hover:bg-muted/50 dark:hover:bg-muted/50 hover:border-primary/20 dark:hover:border-primary/20 active:scale-95 transition-all duration-200 shadow-sm hover:shadow"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Previous
                    </Link>
                    <Link
                        v-if="links[links.length - 1]?.url"
                        :href="links[links.length - 1].url"
                        preserve-scroll
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-border dark:border-border bg-background dark:bg-background text-card-foreground dark:text-card-foreground hover:bg-muted/50 dark:hover:bg-muted/50 hover:border-primary/20 dark:hover:border-primary/20 active:scale-95 transition-all duration-200 shadow-sm hover:shadow"
                    >
                        Next
                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>

                <!-- Desktop pagination -->
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between gap-4">
                    <!-- Info -->
                    <div v-if="showInfo">
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                            Showing
                            <span class="font-semibold text-card-foreground dark:text-card-foreground">{{ meta.from }}</span>
                            to
                            <span class="font-semibold text-card-foreground dark:text-card-foreground">{{ meta.to }}</span>
                            of
                            <span class="font-semibold text-card-foreground dark:text-card-foreground">{{ meta.total }}</span>
                            results
                        </p>
                    </div>

                    <!-- Pagination controls -->
                    <div class="flex items-center gap-1">
                        <nav class="relative z-0 inline-flex items-center gap-1" aria-label="Pagination">
                            <!-- Previous button -->
                            <Link
                                v-if="links[0]?.url"
                                :href="links[0].url"
                                preserve-scroll
                                class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border border-border dark:border-border bg-background dark:bg-background text-sm font-medium text-card-foreground dark:text-card-foreground hover:bg-muted/50 dark:hover:bg-muted/50 hover:border-primary/20 dark:hover:border-primary/20 hover:text-primary dark:hover:text-primary active:scale-95 transition-all duration-200 shadow-sm hover:shadow"
                                title="Previous page"
                            >
                                <span class="sr-only">Previous</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>

                            <!-- Page numbers -->
                            <template v-for="page in getPageNumbers()" :key="page">
                                <span
                                    v-if="page === '...'"
                                    class="relative inline-flex items-center justify-center w-9 h-9 text-sm font-medium text-muted-foreground dark:text-muted-foreground"
                                >
                                    ...
                                </span>
                                <Link
                                    v-else
                                    :href="links.find(link => link.label === page.toString())?.url || '#'"
                                    preserve-scroll
                                    :class="[
                                        'relative inline-flex items-center justify-center min-w-[2.25rem] h-9 px-3 rounded-lg text-sm font-medium transition-all duration-200',
                                        links.find(link => link.label === page.toString())?.active
                                            ? 'z-10 bg-primary text-primary-foreground shadow-md shadow-primary/20 border border-primary/20 scale-105'
                                            : 'border border-border dark:border-border bg-background dark:bg-background text-card-foreground dark:text-card-foreground hover:bg-muted/50 dark:hover:bg-muted/50 hover:border-primary/20 dark:hover:border-primary/20 hover:text-primary dark:hover:text-primary active:scale-95 shadow-sm hover:shadow'
                                    ]"
                                >
                                    {{ page }}
                                </Link>
                            </template>

                            <!-- Next button -->
                            <Link
                                v-if="links[links.length - 1]?.url"
                                :href="links[links.length - 1].url"
                                preserve-scroll
                                class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border border-border dark:border-border bg-background dark:bg-background text-sm font-medium text-card-foreground dark:text-card-foreground hover:bg-muted/50 dark:hover:bg-muted/50 hover:border-primary/20 dark:hover:border-primary/20 hover:text-primary dark:hover:text-primary active:scale-95 transition-all duration-200 shadow-sm hover:shadow"
                                title="Next page"
                            >
                                <span class="sr-only">Next</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
