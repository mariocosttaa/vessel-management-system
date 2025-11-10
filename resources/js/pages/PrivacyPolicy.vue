<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
</script>

<template>
    <Head title="Privacy Policy - Bindamy Mareas" />
    <AppLayout>
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <h1 class="text-3xl font-bold text-card-foreground dark:text-card-foreground mb-6">
                    {{ t('Privacy Policy') }}
                </h1>

                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-8">
                    {{ t('Last updated:') }} {{ new Date().toLocaleDateString() }}
                </p>

                <div class="space-y-6 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('1. Introduction') }}
                        </h2>
                        <p>
                            {{ t('Bindamy Mareas ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our vessel management system.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('2. Information We Collect') }}
                        </h2>
                        <p class="mb-2">{{ t('We collect information that you provide directly to us, including:') }}</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>{{ t('Account information (name, email address, password)') }}</li>
                            <li>{{ t('Vessel and operational data') }}</li>
                            <li>{{ t('Financial transaction information') }}</li>
                            <li>{{ t('Crew member information') }}</li>
                            <li>{{ t('Communication data when you contact us') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('3. How We Use Your Information') }}
                        </h2>
                        <p class="mb-2">{{ t('We use the information we collect to:') }}</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>{{ t('Provide, maintain, and improve our services') }}</li>
                            <li>{{ t('Process transactions and send related information') }}</li>
                            <li>{{ t('Send technical notices and support messages') }}</li>
                            <li>{{ t('Respond to your comments and questions') }}</li>
                            <li>{{ t('Monitor and analyze usage patterns') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('4. Data Security') }}
                        </h2>
                        <p>
                            {{ t('We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('5. Data Retention') }}
                        </h2>
                        <p>
                            {{ t('We retain your information for as long as necessary to provide our services and comply with legal obligations.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('6. Your Rights') }}
                        </h2>
                        <p class="mb-2">{{ t('You have the right to:') }}</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>{{ t('Access your personal information') }}</li>
                            <li>{{ t('Correct inaccurate data') }}</li>
                            <li>{{ t('Request deletion of your data') }}</li>
                            <li>{{ t('Object to processing of your data') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('7. Contact Us') }}
                        </h2>
                        <p>
                            {{ t('If you have questions about this Privacy Policy, please contact us at:') }}
                            <a href="mailto:geral@mareas.bindamy.site" class="text-primary hover:underline">geral@mareas.bindamy.site</a>
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

