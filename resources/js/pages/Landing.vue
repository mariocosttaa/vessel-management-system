<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { login } from '@/routes'
import Icon from '@/components/Icon.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from '@/composables/useI18n'

const page = usePage()
const { t } = useI18n()
const user = computed(() => page.props.auth?.user)

const features = computed(() => [
    {
        icon: 'ship',
        title: t('Vessel Management'),
        description: t('Complete vessel registry with specifications and maintenance tracking. Manage your entire fleet from one centralized platform.')
    },
    {
        icon: 'users',
        title: t('Crew Management & Salary Control'),
        description: t('Manage crew members, positions, and automated salary payments. Track crew movements and calculate salaries based on fishing trips and distribution profiles.')
    },
    {
        icon: 'dollar-sign',
        title: t('Financial Transactions'),
        description: t('Register all income, expenses, and transfers with automatic VAT calculations. Complete financial transparency with real-time balance tracking across multiple bank accounts.')
    },
    {
        icon: 'fish',
        title: t('Mareas Management'),
        description: t('Control fishing trips from departure to return. Register catch details, crew participation, and calculate distribution profiles automatically. Track everything from sea to port.')
    },
    {
        icon: 'file-text',
        title: t('Financial Reporting & PDF Export'),
        description: t('Generate comprehensive financial reports, cash flow statements, and VAT reports. Export everything to PDF for documentation and compliance.')
    },
    {
        icon: 'wrench',
        title: t('Maintenance Tracking'),
        description: t('Schedule and track vessel maintenance to ensure optimal performance and compliance with regulations.')
    },
    {
        icon: 'mail',
        title: t('Email Notifications'),
        description: t('Automatic email notifications for all important events. Get notified about transactions, mareas completion, maintenance schedules, and more.')
    },
    {
        icon: 'shield',
        title: t('Access Control & Permissions'),
        description: t('Granular permission system with vessel-specific roles. Control who can view, edit, or manage different aspects of your operations.')
    },
    {
        icon: 'activity',
        title: t('Complete Audit Trail'),
        description: t('Monitor everything with comprehensive audit logging. Track all changes, user actions, and system events for complete transparency and compliance.')
    },
    {
        icon: 'calculator',
        title: t('Distribution Profiles'),
        description: t('Create custom distribution profiles for mareas. Automatically calculate crew payments based on catch, positions, and predefined rules.')
    },
    {
        icon: 'trending-up',
        title: t('Analytics Dashboard'),
        description: t('Real-time insights into your operations with customizable dashboards and visual analytics.')
    },
    {
        icon: 'repeat',
        title: t('Recurring Transactions'),
        description: t('Automate recurring payments like salaries, insurance, and maintenance. Set it once and let the system handle the rest.')
    }
])

const testimonials = computed(() => [
    {
        quote: t('Bindamy Mareas has transformed how we manage our fishing fleet. The financial control and reporting capabilities are outstanding.'),
        author: 'Carlos Rodriguez',
        company: t('Atlantic Fisheries')
    },
    {
        quote: t('The crew management system saves us hours every week. Automated salary calculations and position tracking are game-changers.'),
        author: 'Maria Santos',
        company: t('Mediterranean Vessels')
    },
    {
        quote: t('Best investment we\'ve made for our operations. The system is intuitive, powerful, and has improved our efficiency significantly.'),
        author: 'John Anderson',
        company: t('Pacific Fleet Management')
    }
])
</script>

<template>
    <Head title="Bindamy Mareas - Vessel Management System" />
    <AppLayout>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-b from-background to-muted/30 dark:from-[#121212] dark:to-[#0a0a0a] pt-24 pb-12 lg:pt-32 lg:pb-20 overflow-hidden">
            <!-- Background gradient -->
            <div class="absolute inset-0 bg-gradient-to-b from-primary/5 via-transparent to-transparent dark:from-primary/10"></div>

            <div class="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <!-- Badge -->
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-border/50 dark:border-sidebar-border/50 bg-card/40 dark:bg-card/20 backdrop-blur-sm px-3 py-1 text-xs text-muted-foreground dark:text-muted-foreground">
                        <span>{{ t('Part of') }}</span>
                        <span class="font-semibold text-card-foreground dark:text-card-foreground">{{ t('Bindamy Group') }}</span>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4 leading-tight">
                        {{ t('Vessel management system for') }}
                        <span class="text-primary dark:text-primary">{{ t('modern operations') }}</span>
                    </h1>

                    <!-- Subtext -->
                    <p class="mx-auto max-w-2xl text-base text-muted-foreground dark:text-muted-foreground mb-8 leading-relaxed">
                        {{ t('Our vessel management platform works seamlessly across all devices, so you only have to set it up once, and get beautiful results forever. Complete financial control and operational efficiency.') }}
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-8">
                        <Link
                            v-if="!user"
                            :href="login()"
                            class="inline-flex items-center px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium text-sm transition-all duration-200 shadow-md hover:shadow-lg"
                        >
                            {{ t('Get started') }}
                        </Link>
                        <Link
                            v-else
                            href="/panel"
                            class="inline-flex items-center px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium text-sm transition-all duration-200 shadow-md hover:shadow-lg"
                        >
                            <Icon name="grid" class="w-4 h-4 mr-2" />
                            {{ t('Go to Panel') }}
                        </Link>
                        <a
                            href="#features"
                            class="inline-flex items-center px-6 py-2.5 border border-border dark:border-sidebar-border bg-background dark:bg-card hover:bg-muted/50 dark:hover:bg-muted/30 text-card-foreground dark:text-card-foreground rounded-lg font-medium text-sm transition-all duration-200"
                        >
                            {{ t('Learn more') }}
                        </a>
                    </div>

                    <!-- Video/Image Placeholder -->
                    <div class="relative mx-auto max-w-4xl rounded-xl overflow-hidden border border-border/50 dark:border-sidebar-border/50 shadow-xl bg-card/50 dark:bg-card/30 backdrop-blur-sm">
                        <div class="aspect-video bg-gradient-to-br from-muted/50 to-muted dark:from-muted/30 dark:to-muted/20 flex items-center justify-center relative group cursor-pointer">
                            <!-- Placeholder content -->
                            <div class="text-center p-8">
                                <div class="mb-4 inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary/20 dark:bg-primary/30 border-2 border-primary/50">
                                    <Icon name="play" class="w-10 h-10 text-primary" />
                                </div>
                                <p class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">{{ t('System Demo') }}</p>
                            </div>
                            <!-- Overlay on hover -->
                            <div class="absolute inset-0 bg-primary/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Build up the whole picture Section -->
        <section id="features" class="py-12 lg:py-16 bg-muted/40 dark:bg-[#0a0a0a] border-t border-border/50 dark:border-sidebar-border">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground dark:text-card-foreground mb-3">
                        {{ t('Build up the whole picture') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-sm text-muted-foreground dark:text-muted-foreground">
                        {{ t('Everything you need to manage your vessel operations efficiently. From financial control to crew management, we\'ve got you covered.') }}
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-lg border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-5 hover:border-primary/50 dark:hover:border-primary/50 transition-all duration-300 hover:shadow-md hover:bg-card dark:hover:bg-card/70"
                    >
                        <div class="mb-3 inline-flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 group-hover:bg-primary/20 dark:group-hover:bg-primary/30 transition-colors">
                            <Icon :name="feature.icon" class="w-5 h-5 text-primary" />
                        </div>
                        <h3 class="text-base font-semibold text-card-foreground dark:text-card-foreground mb-2">
                            {{ feature.title }}
                        </h3>
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Complete Operations Management Section -->
        <section class="py-12 lg:py-16 bg-background dark:bg-[#121212] border-t border-border/30 dark:border-sidebar-border/30">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground dark:text-card-foreground mb-3">
                        {{ t('Complete Operations Management') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-sm text-muted-foreground dark:text-muted-foreground">
                        {{ t('From vessel departure to return, track everything. Register fishing trips, calculate distributions, manage finances, and monitor all operations in real-time.') }}
                    </p>
                </div>

                <!-- Workflow Block 1: Mareas Management -->
                <div class="mb-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <div class="mb-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            {{ t('Fishing Trip Control') }}
                        </div>
                        <h3 class="text-xl font-bold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('Complete Mareas Management') }}
                        </h3>
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            {{ t('Track vessels from departure to return. Register catch details, crew participation, and automatically calculate distribution profiles. Everything is recorded and monitored.') }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-4 shadow-md">
                        <div class="aspect-video bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <Icon name="fish" class="w-12 h-12 text-primary/50 mx-auto mb-2" />
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground">{{ t('Mareas Tracking') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Block 2: Financial Control -->
                <div class="mb-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div class="order-2 lg:order-1 rounded-lg border border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-4 shadow-md">
                        <div class="aspect-video bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <Icon name="dollar-sign" class="w-12 h-12 text-primary/50 mx-auto mb-2" />
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground">{{ t('Financial Control') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="mb-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            {{ t('Complete Financial System') }}
                        </div>
                        <h3 class="text-xl font-bold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('Income, Expenses & Transfers') }}
                        </h3>
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            {{ t('Register all financial movements with automatic VAT calculations. Track balances across multiple bank accounts. Export reports to PDF for documentation and compliance.') }}
                        </p>
                    </div>
                </div>

                <!-- Workflow Block 3: Salary & Distribution -->
                <div class="mb-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <div class="mb-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            {{ t('Automated Calculations') }}
                        </div>
                        <h3 class="text-xl font-bold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('Crew Salary & Distribution Profiles') }}
                        </h3>
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            {{ t('Automatically calculate crew salaries based on fishing trips and distribution profiles. Track crew movements and positions. Everything is calculated and documented automatically.') }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-4 shadow-md">
                        <div class="aspect-video bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <Icon name="calculator" class="w-12 h-12 text-primary/50 mx-auto mb-2" />
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground">{{ t('Salary Calculation') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Block 4: Monitoring & Security -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div class="order-2 lg:order-1 rounded-lg border border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-4 shadow-md">
                        <div class="aspect-video bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <Icon name="activity" class="w-12 h-12 text-primary/50 mx-auto mb-2" />
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground">{{ t('Audit & Monitoring') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="mb-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            {{ t('Security & Transparency') }}
                        </div>
                        <h3 class="text-xl font-bold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('Complete Audit Trail & Permissions') }}
                        </h3>
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            {{ t('Monitor everything with comprehensive audit logging. Control access with vessel-specific permissions and roles. Get email notifications for all important events. Export everything to PDF.') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Testimonials Section -->
        <section class="py-12 lg:py-16 bg-gradient-to-b from-muted/20 to-card/50 dark:from-[#0a0a0a] dark:to-[#0f0f0f] border-t border-border/50 dark:border-sidebar-border">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground dark:text-card-foreground mb-3">
                        {{ t('Customer testimonials') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-sm text-muted-foreground dark:text-muted-foreground">
                        {{ t('See what our customers have to say about their experience with Bindamy Mareas.') }}
                    </p>
                </div>

                <!-- Testimonials Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="(testimonial, index) in testimonials"
                        :key="index"
                        class="relative rounded-lg border border-border/50 dark:border-sidebar-border/50 bg-background dark:bg-card/50 p-5 hover:border-primary/50 dark:hover:border-primary/50 transition-all duration-300"
                    >
                        <div class="mb-4">
                            <Icon name="message-square" class="w-8 h-8 text-primary/30 dark:text-primary/20" />
                        </div>
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                            {{ testimonial.quote }}
                        </p>
                        <div class="border-t border-border/50 dark:border-sidebar-border/50 pt-3">
                            <p class="text-sm font-semibold text-card-foreground dark:text-card-foreground">
                                {{ testimonial.author }}
                            </p>
                            <p class="text-xs text-primary/70 dark:text-primary/60">
                                {{ testimonial.company }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="py-12 lg:py-16 bg-gradient-to-b from-background via-muted/20 to-background dark:from-[#121212] dark:via-[#0a0a0a] dark:to-[#121212] border-t border-border/30 dark:border-sidebar-border/30">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl bg-gradient-to-r from-primary/10 via-primary/5 to-primary/10 dark:from-primary/20 dark:via-primary/10 dark:to-primary/20 border border-primary/20 dark:border-primary/30 p-6 lg:p-8">
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
                        <div class="text-center lg:text-left">
                            <h3 class="text-xl lg:text-2xl font-bold text-card-foreground dark:text-card-foreground mb-2">
                                {{ t('Ready to get started?') }}
                            </h3>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ t('Contact us for a personalized demo and pricing information.') }}
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                            <Link
                                v-if="!user"
                                :href="login()"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium text-sm transition-all duration-200 shadow-md hover:shadow-lg"
                            >
                                {{ t('Get started') }}
                            </Link>
                            <Link
                                v-else
                                href="/panel"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium text-sm transition-all duration-200 shadow-md hover:shadow-lg"
                            >
                                {{ t('Go to Panel') }}
                            </Link>
                            <a
                                href="mailto:info@bindamy.com"
                                class="inline-flex items-center justify-center px-5 py-2.5 border border-border dark:border-sidebar-border bg-background dark:bg-card hover:bg-muted/50 dark:hover:bg-muted/30 text-card-foreground dark:text-card-foreground rounded-lg font-medium text-sm transition-all duration-200"
                            >
                                {{ t('Contact Us') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
