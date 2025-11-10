<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useNotifications } from '@/composables/useNotifications';

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

interface DistributionProfile {
    id: number;
    name: string;
    description: string | null;
    is_default: boolean;
    is_system: boolean;
    items_count: number;
    created_by: {
        id: number;
        name: string;
    } | null;
    created_at: string | null;
}

interface Props {
    profiles: DistributionProfile[];
}

const props = defineProps<Props>();
const { canCreate, canEdit, canDelete, canView } = usePermissions();
const { addNotification } = useNotifications();

// Check if user has permission to view distribution profiles
onMounted(() => {
    if (!canView('distribution-profiles')) {
        router.visit(`/panel/${getCurrentVesselId()}/dashboard`, {
            replace: true,
        });
    }
});

// Confirmation dialog state
const showDeleteDialog = ref(false);
const profileToDelete = ref<DistributionProfile | null>(null);
const isDeleting = ref(false);

// Dropdown state
const openDropdownId = ref<number | null>(null);

// Dropdown methods
const toggleActionsDropdown = (profileId: number) => {
    openDropdownId.value = openDropdownId.value === profileId ? null : profileId;
};

// Delete functions
const deleteProfile = (profile: DistributionProfile) => {
    profileToDelete.value = profile;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!profileToDelete.value) return;

    const profileName = profileToDelete.value.name;
    isDeleting.value = true;

    router.delete(`/panel/${getCurrentVesselId()}/marea-distribution-profiles/${profileToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            addNotification({
                type: 'success',
                title: 'Success',
                message: `Distribution profile '${profileName}' has been deleted successfully.`,
            });
            showDeleteDialog.value = false;
            profileToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            addNotification({
                type: 'error',
                title: 'Error',
                message: 'Failed to delete distribution profile. Please try again.',
            });
            isDeleting.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    profileToDelete.value = null;
};

// Navigation
const createProfile = () => {
    router.visit(`/panel/${getCurrentVesselId()}/marea-distribution-profiles/create`);
};

const editProfile = (profileId: number) => {
    router.visit(`/panel/${getCurrentVesselId()}/marea-distribution-profiles/${profileId}/edit`);
};

const viewProfile = (profileId: number) => {
    router.visit(`/panel/${getCurrentVesselId()}/marea-distribution-profiles/${profileId}`);
};
</script>

<template>
    <Head title="Distribution Profiles" />

    <VesselLayout :breadcrumbs="[
        { title: 'Distribution Profiles', href: `/panel/${getCurrentVesselId()}/marea-distribution-profiles` }
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
                            Distribution Profiles
                        </h1>
                        <p class="text-muted-foreground dark:text-muted-foreground mt-1">
                            Manage financial distribution profiles for mareas
                        </p>
                    </div>
                    <button
                        v-if="canCreate('distribution-profiles')"
                        @click="createProfile"
                        class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                    >
                        <Icon name="plus" class="w-4 h-4 mr-2" />
                        Create Profile
                    </button>
                </div>
            </div>

            <!-- Profiles List -->
            <div v-if="props.profiles.length === 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-12 text-center">
                <Icon name="file-text" class="w-12 h-12 mx-auto text-muted-foreground dark:text-muted-foreground mb-4" />
                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-2">
                    No Distribution Profiles
                </h3>
                <p class="text-muted-foreground dark:text-muted-foreground mb-6">
                    Get started by creating your first distribution profile
                </p>
                <button
                    v-if="canCreate('distribution-profiles')"
                    @click="createProfile"
                    class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
                >
                    <Icon name="plus" class="w-4 h-4 mr-2" />
                    Create Profile
                </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="profile in props.profiles"
                    :key="profile.id"
                    class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6 hover:shadow-lg transition-shadow cursor-pointer"
                    @click="viewProfile(profile.id)"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-1">
                                {{ profile.name }}
                            </h3>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="profile.is_default"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary"
                                >
                                    Default
                                </span>
                                <span
                                    v-if="profile.is_system"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground"
                                >
                                    System
                                </span>
                            </div>
                        </div>
                        <div class="relative">
                            <button
                                @click.stop="toggleActionsDropdown(profile.id)"
                                class="p-1 rounded-lg hover:bg-muted/50 transition-colors"
                            >
                                <Icon name="more-vertical" class="w-5 h-5 text-muted-foreground" />
                            </button>
                            <div
                                v-if="openDropdownId === profile.id"
                                class="absolute right-0 mt-2 w-48 rounded-lg border border-border dark:border-border bg-card dark:bg-card shadow-lg z-10"
                                @click.stop
                            >
                                <button
                                    @click="editProfile(profile.id)"
                                    :disabled="profile.is_system || !canEdit('distribution-profiles')"
                                    class="w-full text-left px-4 py-2 text-sm text-card-foreground hover:bg-muted/50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                >
                                    <Icon name="edit" class="w-4 h-4 mr-2" />
                                    Edit
                                </button>
                                <button
                                    @click="deleteProfile(profile)"
                                    :disabled="profile.is_system || !canDelete('distribution-profiles')"
                                    class="w-full text-left px-4 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                >
                                    <Icon name="trash" class="w-4 h-4 mr-2" />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <p v-if="profile.description" class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 line-clamp-2">
                        {{ profile.description }}
                    </p>

                    <div class="flex items-center justify-between text-sm text-muted-foreground dark:text-muted-foreground">
                        <span>{{ profile.items_count }} item{{ profile.items_count !== 1 ? 's' : '' }}</span>
                        <span v-if="profile.created_by">
                            by {{ profile.created_by.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Dialog -->
            <ConfirmationDialog
                :open="showDeleteDialog"
                title="Delete Distribution Profile"
                description="This action cannot be undone."
                :message="`Are you sure you want to delete the distribution profile '${profileToDelete?.name}'?`"
                confirm-text="Delete"
                cancel-text="Cancel"
                variant="destructive"
                type="danger"
                :loading="isDeleting"
                @confirm="confirmDelete"
                @cancel="cancelDelete"
            />
        </div>
    </VesselLayout>
</template>

