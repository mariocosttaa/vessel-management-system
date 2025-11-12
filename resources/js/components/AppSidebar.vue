<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
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
    Receipt,
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
import { usePage } from '@inertiajs/vue3';

const { canView, hasPermission, hasAnyRole, isAdmin } = usePermissions();
const { t } = useI18n();
const page = usePage();

// Get current vessel from page props
// According to middleware, it's in auth.user.current_vessel, but components use auth.current_vessel
// Try both locations for compatibility
const currentVessel = computed(() => {
    const auth = page.props.auth as any;
    return auth?.user?.current_vessel || auth?.current_vessel;
});

// Get current vessel ID from URL (supports both hashed and numeric for transition)
const getCurrentVesselId = () => {
    const path = window.location.pathname;
    // Match hashed ID (alphanumeric string) or numeric ID
    const vesselMatch = path.match(/\/panel\/([^/]+)/);
    return vesselMatch ? vesselMatch[1] : null;
};

const mainNavItems = computed((): NavItem[] => {
    const vesselId = getCurrentVesselId();
    const items: NavItem[] = [];

    // Core Section - Most Used Items
    items.push({
        title: t('Dashboard'),
        href: `/panel/${vesselId}/dashboard`,
        icon: LayoutDashboard,
        group: t('Core'),
    });

    // Mareas with nested Distribution Profiles
    if (canView('mareas')) {
        const mareaChildren: NavItem[] = [];

        // Distribution Profiles nested under Mareas
        if (canView('distribution-profiles')) {
            mareaChildren.push({
                title: t('Distribution Profiles'),
                href: `/panel/${vesselId}/marea-distribution-profiles`,
                icon: FileSpreadsheet,
            });
        }

        items.push({
            title: t('Mareas'),
            href: mareas.index.url({ vessel: vesselId }),
            icon: Ship,
            group: t('Core'),
            children: mareaChildren.length > 0 ? mareaChildren : undefined,
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

    // Transactions with nested Transaction History
    if (canView('transactions')) {
        const transactionChildren: NavItem[] = [];

        // Transaction History nested under Transactions
        if (hasPermission('reports.access')) {
            transactionChildren.push({
                title: t('Transaction History'),
                href: `/panel/${vesselId}/transactions/history`,
                icon: Calculator,
            });
        }

        items.push({
            title: t('Transactions'),
            href: transactions.index.url({ vessel: vesselId }),
            icon: Receipt,
            group: t('Core'),
            children: transactionChildren.length > 0 ? transactionChildren : undefined,
        });
    }

    // Crew Management Section with nested Crew Roles
    if (canView('crew')) {
        const crewChildren: NavItem[] = [];

        // Crew Roles nested under Crew Members
        if (canView('crew-roles')) {
            crewChildren.push({
                title: t('Crew Roles'),
                href: `/panel/${vesselId}/crew-roles`,
                icon: UserCog,
            });
        }

        items.push({
            title: t('Crew Members'),
            href: crewMembers.index.url({ vessel: vesselId }),
            icon: Users,
            group: t('Crew Management'),
            children: crewChildren.length > 0 ? crewChildren : undefined,
        });
    }

    // Reports Section - Financial Reports and VAT Reports
    if (canView('transactions') && hasPermission('reports.access')) {
        items.push({
            title: t('Financial Reports'),
            href: financialReports.index.url({ vessel: vesselId }),
            icon: BarChart3,
            group: t('Reports'),
        });
        items.push({
            title: t('VAT Reports'),
            href: vatReports.index.url({ vessel: vesselId }),
            icon: FileSpreadsheet,
            group: t('Reports'),
        });
    }

    // Settings Section


     // Financial Section
    if (canView('suppliers')) {
        items.push({
            title: t('Suppliers'),
            href: suppliers.index.url({ vessel: vesselId }),
            icon: Building2,
            group: t('Others'),
        });
    }

    if (hasPermission('settings.access')) {
        items.push({
            title: t('Settings'),
            href: `/panel/${vesselId}/settings`,
            icon: Settings,
            group: t('Others'),
        });
    }


    // Audit Logs
    if (isAdmin.value || hasAnyRole(['administrator', 'admin'])) {
        items.push({
            title: t('Auditory'),
            href: `/panel/${vesselId}/audit-logs`,
            icon: FileText,
            group: t('Others'),
        });
    }

        // Recycle Bin
        if (hasPermission('recycle_bin.view')) {
        items.push({
            title: t('Recycle Bin'),
            href: `/panel/${vesselId}/recycle-bin`,
            icon: Trash2,
            group: t('Others'),
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
        <!-- Logo and Vessel Name -->
        <div class="flex flex-col justify-center items-center w-full py-4">
            <Link
                :href="`/panel/${getCurrentVesselId()}/dashboard`"
                class="focus:outline-none focus-visible:outline-none focus-visible:ring-0"
            >
                <AppLogo />
            </Link>
            <!-- Vessel Name -->
            <div
                v-if="currentVessel?.name"
                class="mt-3 px-4 text-center"
            >
                <p class="text-sm font-semibold text-sidebar-foreground truncate w-full max-w-[200px]">
                    {{ currentVessel.name }}
                </p>
            </div>
        </div>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
