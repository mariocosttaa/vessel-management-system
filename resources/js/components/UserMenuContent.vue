<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Settings, Home } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const page = usePage();

// Check if we're on a vessel page
const isVesselPage = computed(() => {
    const url = page.url?.value || window.location.pathname;
    return /^\/panel\/(\d+)\//.test(url);
});

const handleLogout = () => {
    router.flushAll();
};

const goToVesselSelector = () => {
    router.visit('/panel');
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="edit()" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem
            v-if="isVesselPage"
            @click="goToVesselSelector"
            as="button"
            class="w-full"
        >
            <Home class="mr-2 h-4 w-4" />
            Back to Vessels
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
