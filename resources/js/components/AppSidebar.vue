<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import crewMembers from '@/routes/panel/crew-members/index';
import suppliers from '@/routes/panel/suppliers/index';
import transactions from '@/routes/panel/transactions/index';
import mareas from '@/routes/panel/mareas/index';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Users,
    UserCog,
    Building2,
    Wallet,
    Receipt,
    Home,
    Settings,
    Ship,
    Calculator
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { usePermissions } from '@/composables/usePermissions';
import { computed } from 'vue';

const { canView, hasPermission } = usePermissions();

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const mainNavItems = computed((): NavItem[] => {
    const vesselId = getCurrentVesselId();
    const items: NavItem[] = [];

    // Platform Section - Always visible
    items.push({
        title: 'Dashboard',
        href: `/panel/${vesselId}/dashboard`,
        icon: LayoutDashboard,
        group: 'Platform',
    });

    // Crew Management Section
    if (canView('crew')) {
        items.push({
            title: 'Crew Members',
            href: crewMembers.index.url({ vessel: vesselId }),
            icon: Users,
            group: 'Crew Management',
        });
    }

    // Crew Roles - only for users with crew-roles view permission
    if (canView('crew-roles')) {
        items.push({
            title: 'Crew Roles',
            href: `/panel/${vesselId}/crew-roles`,
            icon: UserCog,
            group: 'Crew Management',
        });
    }

    // Financial Section
    if (canView('suppliers')) {
        items.push({
            title: 'Suppliers',
            href: suppliers.index.url({ vessel: vesselId }),
            icon: Building2,
            group: 'Financial',
        });
    }

    if (canView('transactions')) {
        items.push({
            title: 'Transactions',
            href: transactions.index.url({ vessel: vesselId }),
            icon: Receipt,
            group: 'Financial',
        });
    }

    // Vessel Section - Mareas and Distribution Profiles
    if (canView('mareas')) {
        items.push({
            title: 'Mareas',
            href: mareas.index.url({ vessel: vesselId }),
            icon: Ship,
            group: 'Vessel',
        });
    }

    if (canView('distribution-profiles')) {
        items.push({
            title: 'Distribution Profiles',
            href: `/panel/${vesselId}/marea-distribution-profiles`,
            icon: Calculator,
            group: 'Vessel',
        });
    }

    // Settings Section - Only for users with settings.access permission
    if (hasPermission('settings.access')) {
        items.push({
            title: 'Vessel Settings',
            href: `/panel/${vesselId}/settings`,
            icon: Settings,
            group: 'Settings',
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Vessel Selector',
        href: '/panel',
        icon: Home,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="`/panel/${getCurrentVesselId()}/dashboard`">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
