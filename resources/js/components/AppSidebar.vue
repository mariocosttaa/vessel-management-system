<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
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
import maintenances from '@/routes/panel/maintenances/index';
import financialReports from '@/routes/panel/financial-reports';
import vatReports from '@/routes/panel/vat-reports';
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
    Calculator,
    Trash2,
    ArrowLeft,
    FileText,
    BarChart3,
    FileSpreadsheet,
    Wrench
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useI18n } from '@/composables/useI18n';
import { computed } from 'vue';

const { canView, hasPermission, hasAnyRole, isAdmin } = usePermissions();
const { t } = useI18n();

// Get current vessel ID from URL
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    const vesselMatch = path.match(/\/panel\/(\d+)/);
    return vesselMatch ? vesselMatch[1] : '1';
};

const mainNavItems = computed((): NavItem[] => {
    const vesselId = getCurrentVesselId();
    const items: NavItem[] = [];

    // Core Section - Most Used Items (Dashboard, Marea, Transaction)
    items.push({
        title: t('Dashboard'),
        href: `/panel/${vesselId}/dashboard`,
        icon: LayoutDashboard,
        group: t('Core'),
    });

    if (canView('mareas')) {
        items.push({
            title: t('Mareas'),
            href: mareas.index.url({ vessel: vesselId }),
            icon: Ship,
            group: t('Core'),
        });
    }

    if (canView('maintenances')) {
        items.push({
            title: t('Maintenances'),
            href: maintenances.index.url({ vessel: vesselId }),
            icon: Wrench,
            group: t('Core'),
        });
    }

    if (canView('transactions')) {
        items.push({
            title: t('Transactions'),
            href: transactions.index.url({ vessel: vesselId }),
            icon: Receipt,
            group: t('Core'),
        });
        // Transaction History - requires reports.access permission
        if (hasPermission('reports.access')) {
            items.push({
                title: t('Transaction History'),
                href: `/panel/${vesselId}/transactions/history`,
                icon: Calculator,
                group: t('Core'),
            });
        }
    }

    // Crew Management Section
    if (canView('crew')) {
        items.push({
            title: t('Crew Members'),
            href: crewMembers.index.url({ vessel: vesselId }),
            icon: Users,
            group: t('Crew Management'),
        });
    }

    // Crew Roles - only for users with crew-roles view permission
    if (canView('crew-roles')) {
        items.push({
            title: t('Crew Roles'),
            href: `/panel/${vesselId}/crew-roles`,
            icon: UserCog,
            group: t('Crew Management'),
        });
    }

    // Financial Section
    if (canView('suppliers')) {
        items.push({
            title: t('Suppliers'),
            href: suppliers.index.url({ vessel: vesselId }),
            icon: Building2,
            group: t('Financial'),
        });
    }

    if (canView('transactions')) {
        // Financial Reports - requires reports.access permission
        if (hasPermission('reports.access')) {
            items.push({
                title: t('Financial Reports'),
                href: financialReports.index.url({ vessel: vesselId }),
                icon: BarChart3,
                group: t('Financial'),
            });
            items.push({
                title: t('VAT Reports'),
                href: vatReports.index.url({ vessel: vesselId }),
                icon: FileSpreadsheet,
                group: t('Financial'),
            });
        }
    }

    // Vessel Section - Distribution Profiles
    if (canView('distribution-profiles')) {
        items.push({
            title: t('Distribution Profiles'),
            href: `/panel/${vesselId}/marea-distribution-profiles`,
            icon: Calculator,
            group: t('Vessel'),
        });
    }

    // Settings Section - Only for users with settings.access permission
    if (hasPermission('settings.access')) {
        items.push({
            title: t('Settings'),
            href: `/panel/${vesselId}/settings`,
            icon: Settings,
            group: t('Settings'),
        });
    }

    // Recycle Bin - Only for users with recycle_bin.view permission
    if (hasPermission('recycle_bin.view')) {
        items.push({
            title: t('Bin'),
            href: `/panel/${vesselId}/recycle-bin`,
            icon: Trash2,
            group: t('Settings'),
        });
    }

    // Auditory - Only for administrators
    // Check if user has Administrator role (vessel role) or admin/administrator global roles
    // The backend also checks for these roles, so we match that logic
    if (isAdmin.value || hasAnyRole(['administrator', 'admin'])) {
        items.push({
            title: t('Auditory'),
            href: `/panel/${vesselId}/audit-logs`,
            icon: FileText,
            group: t('Settings'),
        });
    }

    return items;
});

const footerNavItems = computed((): NavItem[] => [
    {
        title: t('Back'),
        href: '/panel',
        icon: ArrowLeft,
    },
]);
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
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
