<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
</script>

<template>
    <Head title="Terms of Service - Bindamy Mareas" />
    <AppLayout>
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <h1 class="text-3xl font-bold text-card-foreground dark:text-card-foreground mb-6">
                    {{ t('Terms of Service') }}
                </h1>

                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-8">
                    {{ t('Last updated:') }} {{ new Date().toLocaleDateString() }}
                </p>

                <div class="space-y-6 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('1. Acceptance of Terms') }}
                        </h2>
                        <p>
                            {{ t('By accessing and using Bindamy Mareas, you accept and agree to be bound by the terms and provision of this agreement.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('2. Use License') }}
                        </h2>
                        <p class="mb-2">{{ t('Permission is granted to use the Bindamy Mareas system for your vessel management operations. This license does not include:') }}</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>{{ t('Modifying or copying the software') }}</li>
                            <li>{{ t('Using the system for any commercial purpose without authorization') }}</li>
                            <li>{{ t('Attempting to reverse engineer the system') }}</li>
                            <li>{{ t('Removing any copyright or proprietary notations') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('3. User Account') }}
                        </h2>
                        <p class="mb-2">{{ t('You are responsible for:') }}</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>{{ t('Maintaining the confidentiality of your account credentials') }}</li>
                            <li>{{ t('All activities that occur under your account') }}</li>
                            <li>{{ t('Notifying us immediately of any unauthorized use') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('4. Data Accuracy') }}
                        </h2>
                        <p>
                            {{ t('You are responsible for ensuring the accuracy of all data entered into the system. We are not liable for any errors or omissions in your data.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('5. Prohibited Uses') }}
                        </h2>
                        <p class="mb-2">{{ t('You may not use the system:') }}</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>{{ t('In any way that violates applicable laws or regulations') }}</li>
                            <li>{{ t('To transmit any malicious code or viruses') }}</li>
                            <li>{{ t('To interfere with or disrupt the system or servers') }}</li>
                            <li>{{ t('To attempt to gain unauthorized access to any part of the system') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('6. Limitation of Liability') }}
                        </h2>
                        <p>
                            {{ t('Bindamy Mareas shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use or inability to use the service.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('7. Termination') }}
                        </h2>
                        <p>
                            {{ t('We reserve the right to terminate or suspend your account and access to the service immediately, without prior notice, for any breach of these Terms of Service.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('8. Changes to Terms') }}
                        </h2>
                        <p>
                            {{ t('We reserve the right to modify these terms at any time. Your continued use of the service after changes constitutes acceptance of the new terms.') }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-card-foreground dark:text-card-foreground mb-3">
                            {{ t('9. Contact Information') }}
                        </h2>
                        <p>
                            {{ t('For questions about these Terms of Service, please contact us at:') }}
                            <a href="mailto:geral@mareas.bindamy.site" class="text-primary hover:underline">geral@mareas.bindamy.site</a>
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

