<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { login } from '@/routes'
import Icon from '@/components/Icon.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from '@/composables/useI18n'

const page = usePage()
const { t } = useI18n()
const user = computed(() => page.props.auth?.user)

// Animation state
const visibleSections = ref<Set<string>>(new Set())
const videoLoaded = ref(false)
const videoElement = ref<HTMLVideoElement | null>(null)
const videoElementMobile = ref<HTMLVideoElement | null>(null)

// Image lightbox modal state
const lightboxImage = ref<string | null>(null)
const lightboxAlt = ref<string>('')

// Observe all sections on mount
onMounted(() => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const sectionId = entry.target.getAttribute('data-section-id')
                    if (sectionId) {
                        visibleSections.value.add(sectionId)
                    }
                }
            })
        },
        { threshold: 0.1 }
    )

    // Observe all sections
    const sections = [
        'features-header',
        'features',
        'operations-header',
        'operations-1',
        'operations-2',
        'operations-3',
        'operations-4',
        'testimonials-header',
        'testimonials',
        'contact-header',
        'contact-card',
        'cta'
    ]

    sections.forEach((sectionId) => {
        const element = document.getElementById(sectionId)
        if (element) {
            element.setAttribute('data-section-id', sectionId)
            observer.observe(element)
        }
    })

    // Also observe pricing and contact sections
    const additionalSections = [
        'pricing-header',
        'pricing-card',
        'pricing-features',
        'contact-header',
        'contact-card',
        'contact-info',
        'showcase-header',
        'showcase-1',
        'showcase-2',
        'showcase-3'
    ]
    additionalSections.forEach((sectionId) => {
        const element = document.getElementById(sectionId)
        if (element) {
            element.setAttribute('data-section-id', sectionId)
            observer.observe(element)
        }
    })

    // Load video when page is ready (hero section is always visible)
    // Using setTimeout to ensure it doesn't block initial page render
    setTimeout(() => {
        videoLoaded.value = true
        if (videoElement.value) {
            videoElement.value.load()
        }
        if (videoElementMobile.value) {
            videoElementMobile.value.load()
        }
    }, 100)
})

// Close lightbox on ESC key
const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && lightboxImage.value) {
        closeLightbox()
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
    // Cleanup: restore body overflow if modal was open
    document.body.style.overflow = ''
})

// Lightbox functions
const openLightbox = (imageSrc: string, alt: string) => {
    lightboxImage.value = imageSrc
    lightboxAlt.value = alt
    document.body.style.overflow = 'hidden' // Prevent background scrolling
}

const closeLightbox = () => {
    lightboxImage.value = null
    lightboxAlt.value = ''
    document.body.style.overflow = '' // Restore scrolling
}

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
        <section class="relative bg-gradient-to-br from-primary/5 via-background to-primary/3 dark:from-primary/10 dark:via-[#121212] dark:to-primary/5 pt-20 pb-16 lg:pt-24 lg:pb-20 overflow-hidden">
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 bg-gradient-to-b from-background/95 via-background/90 to-background/80 dark:from-[#121212]/95 dark:via-[#121212]/90 dark:to-[#121212]/80"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-background via-transparent to-primary/5 dark:from-[#121212] dark:via-transparent dark:to-primary/10"></div>

            <!-- Decorative Circles -->
            <div class="absolute top-20 right-10 w-72 h-72 bg-primary/10 dark:bg-primary/20 rounded-full blur-3xl opacity-50"></div>
            <div class="absolute bottom-20 left-10 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-40"></div>

            <!-- Grid Pattern Overlay -->
            <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]" style="background-image: linear-gradient(rgba(59,130,246,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(59,130,246,0.1) 1px, transparent 1px); background-size: 50px 50px;"></div>

            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                    <!-- Left Column: Text Content -->
                    <div class="text-left animate-fade-in-left">
                        <!-- Badge -->
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-border/50 dark:border-sidebar-border/50 bg-card/60 dark:bg-card/30 backdrop-blur-md px-3 py-1 text-xs text-muted-foreground dark:text-muted-foreground shadow-lg">
                            <Icon name="sparkles" class="w-3 h-3 text-primary" />
                            <span>{{ t('Part of') }}</span>
                            <span class="font-semibold text-card-foreground dark:text-card-foreground">{{ t('Bindamy Group') }}</span>
                        </div>

                        <!-- Main Headline -->
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4 leading-tight">
                            {{ t('Vessel management system for') }}
                            <span class="block mt-2 bg-gradient-to-r from-primary via-primary/80 to-primary bg-clip-text text-transparent animate-gradient">
                                {{ t('modern operations') }}
                            </span>
                        </h1>

                        <!-- Subtext -->
                        <p class="text-base sm:text-lg text-muted-foreground dark:text-muted-foreground mb-8 leading-relaxed max-w-xl">
                            {{ t('Our vessel management platform works seamlessly across all devices, so you only have to set it up once, and get beautiful results forever. Complete financial control and operational efficiency.') }}
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row items-start gap-3">
                            <Link
                                v-if="!user"
                                :href="login()"
                                class="group inline-flex items-center px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-semibold text-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <span>{{ t('Get started') }}</span>
                                <Icon name="arrow-right" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
                            </Link>
                            <Link
                                v-else
                                href="/panel"
                                class="group inline-flex items-center px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-semibold text-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <Icon name="grid" class="w-4 h-4 mr-2" />
                                <span>{{ t('Go to Panel') }}</span>
                                <Icon name="arrow-right" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
                            </Link>
                            <a
                                href="#features"
                                class="inline-flex items-center px-6 py-2.5 border border-border dark:border-sidebar-border bg-background/80 dark:bg-card/80 backdrop-blur-sm hover:bg-muted/50 dark:hover:bg-muted/30 text-card-foreground dark:text-card-foreground rounded-lg font-semibold text-sm transition-all duration-300 hover:scale-105 shadow-md"
                            >
                                {{ t('Learn more') }}
                                <Icon name="chevron-down" class="w-4 h-4 ml-2" />
                            </a>
                        </div>
                    </div>

                    <!-- Right Column: Video Background -->
                    <div class="relative lg:block hidden animate-fade-in-right">
                        <div class="relative rounded-xl overflow-hidden border border-border/30 dark:border-sidebar-border/30 shadow-2xl bg-card/50 dark:bg-card/30 backdrop-blur-sm">
                            <div class="aspect-video relative">
                                <video
                                    ref="videoElement"
                                    class="w-full h-full object-cover"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                    :preload="videoLoaded ? 'auto' : 'none'"
                                >
                                    <source v-if="videoLoaded" src="/bindamy-marea-presentation.mp4" type="video/mp4" />
                                </video>
                                <!-- Loading placeholder -->
                                <div v-if="!videoLoaded" class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-primary/10 to-primary/5">
                                    <div class="text-center p-8">
                                        <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/20 dark:bg-primary/30 border-2 border-primary/50 animate-pulse">
                                            <Icon name="play" class="w-8 h-8 text-primary" />
                                        </div>
                                        <p class="text-xs font-medium text-muted-foreground dark:text-muted-foreground">{{ t('System Demo') }}</p>
                                    </div>
                                </div>
                                <!-- Overlay gradient for better text readability -->
                                <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-background/10 dark:to-[#121212]/10 pointer-events-none"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Video (centered, below text) -->
                    <div class="relative lg:hidden animate-fade-in-up">
                        <div class="relative rounded-xl overflow-hidden border border-border/30 dark:border-sidebar-border/30 shadow-xl bg-card/50 dark:bg-card/30">
                            <div class="aspect-video relative">
                                <video
                                    ref="videoElementMobile"
                                    class="w-full h-full object-cover"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                    :preload="videoLoaded ? 'auto' : 'none'"
                                >
                                    <source v-if="videoLoaded" src="/bindamy-marea-presentation.mp4" type="video/mp4" />
                                </video>
                                <!-- Loading placeholder -->
                                <div v-if="!videoLoaded" class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-primary/10 to-primary/5">
                                    <div class="text-center p-8">
                                        <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/20 dark:bg-primary/30 border-2 border-primary/50 animate-pulse">
                                            <Icon name="play" class="w-8 h-8 text-primary" />
                                        </div>
                                        <p class="text-xs font-medium text-muted-foreground dark:text-muted-foreground">{{ t('System Demo') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Build up the whole picture Section -->
        <section id="features" class="relative py-16 lg:py-20 bg-gradient-to-b from-muted/60 via-primary/5 to-muted/40 dark:from-[#0a0a0a] dark:via-primary/10 dark:to-[#0a0a0a] border-t border-border/50 dark:border-sidebar-border overflow-hidden">
            <!-- Decorative Background Elements -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>

            <div class="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div id="features-header" class="text-center mb-12" :class="{ 'animate-fade-in-up': visibleSections.has('features-header') }">
                    <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-primary/10 dark:bg-primary/20 border border-primary/20 dark:border-primary/30">
                        <Icon name="sparkles" class="w-4 h-4 text-primary" />
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider">{{ t('Features') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Everything You Need in One Platform') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('Comprehensive vessel management solution covering all aspects of your operations. From financial control to crew management, we\'ve got you covered.') }}
                    </p>
                </div>

                <!-- Features Grid -->
                <div id="features" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        :class="[
                            'group relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-gradient-to-br from-card/90 to-card/70 dark:from-card/60 dark:to-card/40 backdrop-blur-sm p-6 hover:border-primary/60 dark:hover:border-primary/60 transition-all duration-300 hover:shadow-xl hover:shadow-primary/10 hover:-translate-y-1',
                            visibleSections.has('features') ? 'animate-fade-in-up' : 'opacity-0',
                        ]"
                        :style="{ animationDelay: `${index * 0.1}s` }"
                    >
                        <!-- Decorative gradient overlay on hover -->
                        <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-primary/0 to-primary/0 group-hover:from-primary/5 group-hover:to-primary/0 transition-all duration-300 pointer-events-none"></div>

                        <div class="relative mb-4 inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-primary/20 to-primary/10 dark:from-primary/30 dark:to-primary/20 group-hover:from-primary/30 group-hover:to-primary/20 dark:group-hover:from-primary/40 dark:group-hover:to-primary/30 transition-all duration-300 shadow-lg group-hover:shadow-primary/20">
                            <Icon :name="feature.icon" class="w-6 h-6 text-primary" />
                        </div>
                        <h3 class="relative text-lg font-bold text-card-foreground dark:text-card-foreground mb-3">
                            {{ feature.title }}
                        </h3>
                        <p class="relative text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Complete Operations Management Section -->
        <section class="relative py-16 lg:py-20 bg-gradient-to-b from-background via-primary/3 to-background dark:from-[#121212] dark:via-primary/5 dark:to-[#121212] border-t border-border/30 dark:border-sidebar-border/30 overflow-hidden">
            <!-- Decorative Background -->
            <div class="absolute top-1/4 left-0 w-64 h-64 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-40"></div>
            <div class="absolute bottom-1/4 right-0 w-80 h-80 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-40"></div>

            <div class="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div id="operations-header" class="text-center mb-12" :class="{ 'animate-fade-in-up': visibleSections.has('operations-header') }">
                    <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-primary/10 dark:bg-primary/20 border border-primary/20 dark:border-primary/30">
                        <Icon name="zap" class="w-4 h-4 text-primary" />
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider">{{ t('Operations') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Streamline Your Entire Workflow') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('From vessel departure to return, track everything seamlessly. Register fishing trips, calculate distributions, manage finances, and monitor all operations in real-time.') }}
                    </p>
                </div>

                <!-- Workflow Block 1: Mareas Management -->
                <div id="operations-1" class="mb-16 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center" :class="{ 'animate-fade-in-left': visibleSections.has('operations-1') }">
                    <div class="order-2 lg:order-1">
                        <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            <Icon name="fish" class="w-4 h-4" />
                            <span>{{ t('Fishing Trip Control') }}</span>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('Complete Mareas Management') }}
                        </h3>
                        <p class="text-base text-muted-foreground dark:text-muted-foreground leading-relaxed mb-6">
                            {{ t('Track vessels from departure to return. Register catch details, crew participation, and automatically calculate distribution profiles. Everything is recorded and monitored.') }}
                        </p>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Real-time vessel tracking') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Automatic distribution calculations') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Complete catch documentation') }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="order-1 lg:order-2 relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                        <div class="relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-2 shadow-2xl hover:shadow-primary/20 transition-all duration-300 hover:scale-[1.02] cursor-pointer" @click="openLightbox('/assets/marea-manager.png', t('Mareas Management Interface'))">
                            <div class="aspect-video rounded-lg overflow-hidden bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 ring-2 ring-primary/10 relative">
                                <img
                                    src="/assets/marea-manager.png"
                                    :alt="t('Mareas Management Interface')"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/10 transition-colors">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2 px-4 py-2 rounded-lg bg-white/90 dark:bg-black/90 backdrop-blur-sm">
                                        <Icon name="maximize-2" class="w-4 h-4 text-primary" />
                                        <span class="text-xs font-semibold text-card-foreground">{{ t('Click to enlarge') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Block 2: Financial Control -->
                <div id="operations-2" class="mb-16 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center" :class="{ 'animate-fade-in-right': visibleSections.has('operations-2') }">
                    <div class="order-2 lg:order-1 relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                        <div class="relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-2 shadow-2xl hover:shadow-primary/20 transition-all duration-300 hover:scale-[1.02] cursor-pointer" @click="openLightbox('/assets/system-dashboard.png', t('Financial Dashboard'))">
                            <div class="aspect-video rounded-lg overflow-hidden bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 ring-2 ring-primary/10 relative">
                                <img
                                    src="/assets/system-dashboard.png"
                                    :alt="t('Financial Dashboard')"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/10 transition-colors">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2 px-4 py-2 rounded-lg bg-white/90 dark:bg-black/90 backdrop-blur-sm">
                                        <Icon name="maximize-2" class="w-4 h-4 text-primary" />
                                        <span class="text-xs font-semibold text-card-foreground">{{ t('Click to enlarge') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            <Icon name="dollar-sign" class="w-4 h-4" />
                            <span>{{ t('Complete Financial System') }}</span>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('Income, Expenses & Transfers') }}
                        </h3>
                        <p class="text-base text-muted-foreground dark:text-muted-foreground leading-relaxed mb-6">
                            {{ t('Register all financial movements with automatic VAT calculations. Track balances across multiple bank accounts. Export reports to PDF for documentation and compliance.') }}
                        </p>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Automatic VAT calculations') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Multi-account balance tracking') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('PDF export for compliance') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Workflow Block 3: Salary & Distribution -->
                <div id="operations-3" class="mb-16 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center" :class="{ 'animate-fade-in-left': visibleSections.has('operations-3') }">
                    <div class="order-2 lg:order-1">
                        <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            <Icon name="calculator" class="w-4 h-4" />
                            <span>{{ t('Automated Calculations') }}</span>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('Crew Salary & Distribution Profiles') }}
                        </h3>
                        <p class="text-base text-muted-foreground dark:text-muted-foreground leading-relaxed mb-6">
                            {{ t('Automatically calculate crew salaries based on fishing trips and distribution profiles. Track crew movements and positions. Everything is calculated and documented automatically.') }}
                        </p>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Automated salary calculations') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Custom distribution profiles') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Crew position tracking') }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="order-1 lg:order-2 relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                        <div class="relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-2 shadow-2xl hover:shadow-primary/20 transition-all duration-300 hover:scale-[1.02] cursor-pointer" @click="openLightbox('/assets/system-users-administratives-and-colaborators.png', t('Crew Management Interface'))">
                            <div class="aspect-video rounded-lg overflow-hidden bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 ring-2 ring-primary/10 relative">
                                <img
                                    src="/assets/system-users-administratives-and-colaborators.png"
                                    :alt="t('Crew Management Interface')"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/10 transition-colors">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2 px-4 py-2 rounded-lg bg-white/90 dark:bg-black/90 backdrop-blur-sm">
                                        <Icon name="maximize-2" class="w-4 h-4 text-primary" />
                                        <span class="text-xs font-semibold text-card-foreground">{{ t('Click to enlarge') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Block 4: Monitoring & Security -->
                <div id="operations-4" class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center" :class="{ 'animate-fade-in-right': visibleSections.has('operations-4') }">
                    <div class="order-2 lg:order-1 relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                        <div class="relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-card dark:bg-card/50 p-2 shadow-2xl hover:shadow-primary/20 transition-all duration-300 hover:scale-[1.02] cursor-pointer" @click="openLightbox('/assets/auditory.png', t('Audit Trail Interface'))">
                            <div class="aspect-video rounded-lg overflow-hidden bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20 ring-2 ring-primary/10 relative">
                                <img
                                    src="/assets/auditory.png"
                                    :alt="t('Audit Trail Interface')"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/10 transition-colors">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2 px-4 py-2 rounded-lg bg-white/90 dark:bg-black/90 backdrop-blur-sm">
                                        <Icon name="maximize-2" class="w-4 h-4 text-primary" />
                                        <span class="text-xs font-semibold text-card-foreground">{{ t('Click to enlarge') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider">
                            <Icon name="shield-check" class="w-4 h-4" />
                            <span>{{ t('Security & Transparency') }}</span>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('Complete Audit Trail & Permissions') }}
                        </h3>
                        <p class="text-base text-muted-foreground dark:text-muted-foreground leading-relaxed mb-6">
                            {{ t('Monitor everything with comprehensive audit logging. Control access with vessel-specific permissions and roles. Get email notifications for all important events. Export everything to PDF.') }}
                        </p>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Complete audit logging') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Vessel-specific permissions') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ t('Email notifications') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Additional Features Showcase -->
        <section class="relative py-16 lg:py-20 bg-gradient-to-b from-muted/60 via-primary/5 to-muted/40 dark:from-[#0a0a0a] dark:via-primary/10 dark:to-[#0a0a0a] border-t border-border/50 dark:border-sidebar-border overflow-hidden">
            <!-- Decorative Background -->
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>

            <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div id="showcase-header" class="text-center mb-12" :class="{ 'animate-fade-in-up': visibleSections.has('showcase-header') }">
                    <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-primary/10 dark:bg-primary/20 border border-primary/20 dark:border-primary/30">
                        <Icon name="layers" class="w-4 h-4 text-primary" />
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider">{{ t('Showcase') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Powerful Features at Your Fingertips') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('Discover the comprehensive tools that make vessel management effortless and efficient.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Feature 1: User Management -->
                    <div
                        id="showcase-1"
                        class="group relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-gradient-to-br from-card/90 to-card/70 dark:from-card/60 dark:to-card/40 overflow-hidden shadow-xl hover:shadow-2xl hover:shadow-primary/10 hover:border-primary/60 dark:hover:border-primary/60 transition-all duration-300 hover:scale-[1.02] hover:-translate-y-1"
                        :class="{ 'animate-fade-in-up': visibleSections.has('showcase-1') }"
                    >
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-xl blur-lg opacity-0 group-hover:opacity-50 transition-opacity"></div>
                        <div class="relative aspect-video overflow-hidden bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20">
                            <img
                                src="/assets/add-colaborator.png"
                                :alt="t('Add Collaborator')"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-card/90 via-transparent to-transparent"></div>
                        </div>
                        <div class="relative p-6 bg-gradient-to-br from-card/95 to-card/90 dark:from-card/70 dark:to-card/60">
                            <div class="mb-3 inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-primary/10 dark:bg-primary/20 text-xs font-semibold text-primary uppercase tracking-wider">
                                <Icon name="user-plus" class="w-4 h-4" />
                                <span>{{ t('Team Management') }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-card-foreground dark:text-card-foreground mb-2">
                                {{ t('Add Collaborators') }}
                            </h3>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                                {{ t('Easily add and manage team members with role-based permissions.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2: Recovery & Trash -->
                    <div
                        id="showcase-2"
                        class="group relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-gradient-to-br from-card/90 to-card/70 dark:from-card/60 dark:to-card/40 overflow-hidden shadow-xl hover:shadow-2xl hover:shadow-primary/10 hover:border-primary/60 dark:hover:border-primary/60 transition-all duration-300 hover:scale-[1.02] hover:-translate-y-1"
                        :class="{ 'animate-fade-in-up': visibleSections.has('showcase-2') }"
                        style="animation-delay: 0.1s"
                    >
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-xl blur-lg opacity-0 group-hover:opacity-50 transition-opacity"></div>
                        <div class="relative aspect-video overflow-hidden bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20">
                            <img
                                src="/assets/recovery-trash.png"
                                :alt="t('Recovery & Trash')"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-card/90 via-transparent to-transparent"></div>
                        </div>
                        <div class="relative p-6 bg-gradient-to-br from-card/95 to-card/90 dark:from-card/70 dark:to-card/60">
                            <div class="mb-3 inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-primary/10 dark:bg-primary/20 text-xs font-semibold text-primary uppercase tracking-wider">
                                <Icon name="trash-2" class="w-4 h-4" />
                                <span>{{ t('Data Recovery') }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-card-foreground dark:text-card-foreground mb-2">
                                {{ t('Recovery & Trash') }}
                            </h3>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                                {{ t('Safely recover deleted items with our comprehensive trash system.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3: Embassy Selection -->
                    <div
                        id="showcase-3"
                        class="group relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-gradient-to-br from-card/90 to-card/70 dark:from-card/60 dark:to-card/40 overflow-hidden shadow-xl hover:shadow-2xl hover:shadow-primary/10 hover:border-primary/60 dark:hover:border-primary/60 transition-all duration-300 hover:scale-[1.02] hover:-translate-y-1"
                        :class="{ 'animate-fade-in-up': visibleSections.has('showcase-3') }"
                        style="animation-delay: 0.2s"
                    >
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-xl blur-lg opacity-0 group-hover:opacity-50 transition-opacity"></div>
                        <div class="relative aspect-video overflow-hidden bg-gradient-to-br from-muted/50 to-muted/30 dark:from-muted/30 dark:to-muted/20">
                            <img
                                src="/assets/choose-embasy.png"
                                :alt="t('Choose Embassy')"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-card/90 via-transparent to-transparent"></div>
                        </div>
                        <div class="relative p-6 bg-gradient-to-br from-card/95 to-card/90 dark:from-card/70 dark:to-card/60">
                            <div class="mb-3 inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-primary/10 dark:bg-primary/20 text-xs font-semibold text-primary uppercase tracking-wider">
                                <Icon name="globe" class="w-4 h-4" />
                                <span>{{ t('International') }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-card-foreground dark:text-card-foreground mb-2">
                                {{ t('Embassy Selection') }}
                            </h3>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                                {{ t('Manage international operations with embassy selection tools.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Testimonials Section -->
        <section class="relative py-16 lg:py-20 bg-gradient-to-b from-muted/40 via-primary/3 to-background dark:from-[#0a0a0a] dark:via-primary/5 dark:to-[#0f0f0f] border-t border-border/50 dark:border-sidebar-border overflow-hidden">
            <!-- Decorative Background -->
            <div class="absolute top-1/2 left-0 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute top-1/2 right-0 w-80 h-80 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div id="testimonials-header" class="text-center mb-12" :class="{ 'animate-fade-in-up': visibleSections.has('testimonials-header') }">
                    <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-primary/10 dark:bg-primary/20 border border-primary/20 dark:border-primary/30">
                        <Icon name="star" class="w-4 h-4 text-primary" />
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider">{{ t('Testimonials') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Trusted by Industry Leaders') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('Discover how vessel management companies worldwide are transforming their operations with Bindamy Mareas.') }}
                    </p>
                </div>

                <!-- Testimonials Grid -->
                <div id="testimonials" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="(testimonial, index) in testimonials"
                        :key="index"
                        :class="[
                            'group relative rounded-xl border-2 border-border/50 dark:border-sidebar-border/50 bg-gradient-to-br from-card/90 to-card/70 dark:from-card/60 dark:to-card/40 backdrop-blur-sm p-6 hover:border-primary/60 dark:hover:border-primary/60 transition-all duration-300 hover:shadow-xl hover:shadow-primary/10 hover:-translate-y-1',
                            visibleSections.has('testimonials') ? 'animate-fade-in-up' : 'opacity-0',
                        ]"
                        :style="{ animationDelay: `${index * 0.15}s` }"
                    >
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/10 to-primary/5 rounded-xl blur-lg opacity-0 group-hover:opacity-50 transition-opacity"></div>
                        <div class="relative mb-4 inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-primary/20 to-primary/10 dark:from-primary/30 dark:to-primary/20">
                            <Icon name="message-square" class="w-6 h-6 text-primary" />
                        </div>
                        <p class="relative text-sm text-muted-foreground dark:text-muted-foreground mb-6 leading-relaxed">
                            "{{ testimonial.quote }}"
                        </p>
                        <div class="relative border-t border-border/50 dark:border-sidebar-border/50 pt-4">
                            <p class="text-sm font-bold text-card-foreground dark:text-card-foreground mb-1">
                                {{ testimonial.author }}
                            </p>
                            <p class="text-xs font-medium text-primary/80 dark:text-primary/70">
                                {{ testimonial.company }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="relative py-16 lg:py-20 bg-gradient-to-b from-background via-primary/3 to-background dark:from-[#121212] dark:via-primary/5 dark:to-[#121212] border-t border-border/30 dark:border-sidebar-border/30 overflow-hidden">
            <!-- Decorative Background -->
            <div class="absolute top-0 right-1/4 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute bottom-0 left-1/4 w-80 h-80 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div id="contact-header" class="text-center mb-12" :class="{ 'animate-fade-in-up': visibleSections.has('contact-header') }">
                    <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-primary/10 dark:bg-primary/20 border border-primary/20 dark:border-primary/30">
                        <Icon name="mail" class="w-4 h-4 text-primary" />
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider">{{ t('Contact') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Let\'s Talk About Your Needs') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('Have questions or need more information? Our team is here to help you get started with Bindamy Mareas. Whether you need a demo, have technical questions, or want to discuss your specific requirements, we\'re ready to assist you.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    <!-- Left: Description -->
                    <div id="contact-info" :class="{ 'animate-fade-in-left': visibleSections.has('contact-info') }">
                        <h3 class="text-xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('Contact Us') }}
                        </h3>
                        <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-6 leading-relaxed">
                            {{ t('Send us an email at the address below and we\'ll respond as soon as possible. We typically respond within 24 hours during business days. For urgent matters, please mention it in your message.') }}
                        </p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <Icon name="mail" class="w-5 h-5 text-primary flex-shrink-0" />
                                <a href="mailto:support@bindamy.site" class="text-sm text-card-foreground dark:text-card-foreground hover:text-primary transition-colors">
                                    support@bindamy.site
                                </a>
                            </div>
                            <div class="flex items-center gap-3">
                                <Icon name="clock" class="w-5 h-5 text-primary flex-shrink-0" />
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    {{ t('Response time: Within 24 hours') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Contact Card -->
                    <div id="contact-card" class="relative rounded-xl bg-gradient-to-br from-primary/10 via-primary/5 to-card/80 dark:from-primary/20 dark:via-primary/10 dark:to-card/50 backdrop-blur-sm border-2 border-primary/20 dark:border-primary/30 p-8 shadow-xl hover:shadow-2xl hover:shadow-primary/20 transition-all duration-300" :class="{ 'animate-fade-in-right': visibleSections.has('contact-card') }">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-primary/10 rounded-xl blur-lg opacity-50"></div>
                        <div class="relative text-center">
                            <h4 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-3">
                                {{ t('Ready to get started?') }}
                            </h4>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-6">
                                {{ t('Contact us for a personalized demo and pricing information tailored to your needs.') }}
                            </p>
                            <a
                                href="mailto:support@bindamy.site"
                                class="inline-flex items-center justify-center w-full px-6 py-3 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium text-sm transition-all duration-200 shadow-md hover:shadow-lg"
                            >
                                <Icon name="mail" class="w-4 h-4 mr-2" />
                                {{ t('Send Email') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="relative py-16 lg:py-20 bg-gradient-to-b from-muted/60 via-primary/5 to-muted/40 dark:from-[#0a0a0a] dark:via-primary/10 dark:to-[#0a0a0a] border-t border-border/50 dark:border-sidebar-border overflow-hidden">
            <!-- Decorative Background -->
            <div class="absolute top-1/4 left-0 w-96 h-96 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute bottom-1/4 right-0 w-80 h-80 bg-primary/5 dark:bg-primary/10 rounded-full blur-3xl opacity-30"></div>
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div id="pricing-header" class="text-center mb-12" :class="{ 'animate-fade-in-up': visibleSections.has('pricing-header') }">
                    <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-primary/10 dark:bg-primary/20 border border-primary/20 dark:border-primary/30">
                        <Icon name="dollar-sign" class="w-4 h-4 text-primary" />
                        <span class="text-xs font-semibold text-primary uppercase tracking-wider">{{ t('Pricing') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Simple, Transparent Pricing') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('Flexible pricing plans designed to scale with your business. Our pricing adapts to your fleet size, user count, and specific requirements. Contact us for personalized pricing that fits your needs.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left: Features List -->
                    <div id="pricing-features" :class="{ 'animate-fade-in-left': visibleSections.has('pricing-features') }">
                        <h3 class="text-xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                            {{ t('What\'s Included') }}
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3 group">
                                <div class="mt-0.5 flex-shrink-0">
                                    <Icon name="check" class="w-5 h-5 text-primary group-hover:scale-110 transition-transform" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-card-foreground dark:text-card-foreground mb-1">
                                        {{ t('Scalable pricing based on your fleet size') }}
                                    </p>
                                    <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        {{ t('Pay only for what you need, scale as you grow') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 group">
                                <div class="mt-0.5 flex-shrink-0">
                                    <Icon name="check" class="w-5 h-5 text-primary group-hover:scale-110 transition-transform" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-card-foreground dark:text-card-foreground mb-1">
                                        {{ t('No hidden fees or setup costs') }}
                                    </p>
                                    <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        {{ t('Transparent pricing with no surprises') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 group">
                                <div class="mt-0.5 flex-shrink-0">
                                    <Icon name="check" class="w-5 h-5 text-primary group-hover:scale-110 transition-transform" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-card-foreground dark:text-card-foreground mb-1">
                                        {{ t('Full feature access included') }}
                                    </p>
                                    <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        {{ t('All features available from day one') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 group">
                                <div class="mt-0.5 flex-shrink-0">
                                    <Icon name="check" class="w-5 h-5 text-primary group-hover:scale-110 transition-transform" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-card-foreground dark:text-card-foreground mb-1">
                                        {{ t('Dedicated support and training') }}
                                    </p>
                                    <p class="text-xs text-muted-foreground dark:text-muted-foreground">
                                        {{ t('Expert support team ready to help') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Pricing Card -->
                    <div id="pricing-card" class="relative rounded-xl bg-gradient-to-br from-primary/10 via-primary/15 to-primary/10 dark:from-primary/20 dark:via-primary/25 dark:to-primary/20 border-2 border-primary/30 dark:border-primary/40 p-8 shadow-2xl hover:shadow-primary/30 transition-all duration-300 hover:scale-[1.02]" :class="{ 'animate-fade-in-right': visibleSections.has('pricing-card') }">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary/30 to-primary/20 rounded-xl blur-xl opacity-50"></div>
                        <div class="relative text-center mb-6">
                            <h3 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-2">
                                {{ t('Custom Pricing') }}
                            </h3>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ t('Tailored to your needs') }}
                            </p>
                        </div>
                        <div class="mb-6">
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-6 leading-relaxed text-center">
                                {{ t('Our pricing is tailored to your specific needs. We offer flexible plans based on the number of vessels, users, and features you require.') }}
                            </p>
                        </div>
                        <a
                            href="mailto:support@bindamy.site"
                            class="inline-flex items-center justify-center w-full px-6 py-3 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium text-sm transition-all duration-200 shadow-md hover:shadow-lg"
                        >
                            <Icon name="mail" class="w-4 h-4 mr-2" />
                            {{ t('Get a Quote') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="py-12 lg:py-16 bg-gradient-to-b from-background via-muted/20 to-background dark:from-[#121212] dark:via-[#0a0a0a] dark:to-[#121212] border-t border-border/30 dark:border-sidebar-border/30">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div id="cta" class="rounded-xl bg-gradient-to-r from-primary/10 via-primary/5 to-primary/10 dark:from-primary/20 dark:via-primary/10 dark:to-primary/20 border border-primary/20 dark:border-primary/30 p-6 lg:p-8" :class="{ 'animate-fade-in-up': visibleSections.has('cta') }">
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
                                href="mailto:support@bindamy.site"
                                class="inline-flex items-center justify-center px-5 py-2.5 border border-border dark:border-sidebar-border bg-background dark:bg-card hover:bg-muted/50 dark:hover:bg-muted/30 text-card-foreground dark:text-card-foreground rounded-lg font-medium text-sm transition-all duration-200"
                            >
                                {{ t('Contact Us') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Image Lightbox Modal -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="lightboxImage"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 dark:bg-black/95 backdrop-blur-sm"
                @click.self="closeLightbox"
            >
                <!-- Close Button -->
                <button
                    @click="closeLightbox"
                    class="absolute top-4 right-4 z-10 flex items-center justify-center w-10 h-10 rounded-full bg-white/10 dark:bg-white/10 hover:bg-white/20 dark:hover:bg-white/20 backdrop-blur-sm border border-white/20 text-white transition-all duration-200 hover:scale-110"
                    :aria-label="t('Close')"
                >
                    <Icon name="x" class="w-5 h-5" />
                </button>

                <!-- Image Container -->
                <div class="relative max-w-7xl max-h-[90vh] w-full">
                    <div class="relative rounded-xl overflow-hidden shadow-2xl border-2 border-white/20 bg-card/10 backdrop-blur-sm">
                        <img
                            :src="lightboxImage"
                            :alt="lightboxAlt"
                            class="w-full h-auto max-h-[90vh] object-contain"
                        />
                        <!-- Image Info -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/60 to-transparent p-6">
                            <p class="text-white font-medium text-sm">{{ lightboxAlt }}</p>
                        </div>
                    </div>
                </div>

                <!-- ESC Hint -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 dark:bg-white/10 backdrop-blur-sm border border-white/20">
                    <Icon name="keyboard" class="w-4 h-4 text-white/70" />
                    <span class="text-xs text-white/70">{{ t('Press ESC to close') }}</span>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
