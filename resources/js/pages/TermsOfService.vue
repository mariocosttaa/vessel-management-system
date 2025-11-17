<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useI18n } from '@/composables/useI18n'
import Icon from '@/components/Icon.vue'

const { t } = useI18n()
</script>

<template>
    <Head title="Terms of Service - Bindamy Mareas" />
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-b from-background via-muted/20 to-background dark:from-[#121212] dark:via-[#0a0a0a] dark:to-[#121212]">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <!-- Header Section -->
                <div class="mb-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 dark:bg-primary/20 mb-4">
                        <Icon name="file-text" class="w-8 h-8 text-primary" />
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Bindamy Mareas Terms of Service') }}
                    </h1>
                    <p class="text-base text-muted-foreground dark:text-muted-foreground">
                        {{ t('Effective Date:') }} <span class="font-semibold text-card-foreground dark:text-card-foreground">November 17, 2025</span>
                    </p>
                </div>

                <!-- Introduction Card -->
                <div class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 mb-8 shadow-lg">
                    <p class="text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('These Terms of Service ("Terms") govern your access to and use of the Bindamy Mareas platform, products, and services ("Platform"). By creating an account, accessing, or using our services, you agree to be bound by these Terms. If you do not agree, you may not use the Platform.') }}
                    </p>
                </div>

                <!-- Main Content -->
                <div class="space-y-8">
                    <!-- Section 1: Eligibility -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">1</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Eligibility') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('You must be at least 18 years old and have legal authority to enter into agreements on behalf of your organization.') }}</p>
                            <p>{{ t('By using the Platform, you represent and warrant that all information you provide is accurate and complete.') }}</p>
                            <p>{{ t('Use of the Platform is limited to lawful purposes related to vessel and crew management.') }}</p>
                        </div>
                    </section>

                    <!-- Section 2: Account Registration and Security -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">2</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Account Registration and Security') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('You must create an account to access the Platform.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('You are responsible for maintaining the confidentiality of your login credentials.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('All activity under your account is your responsibility.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Notify Bindamy Mareas immediately of any unauthorized access or suspected breaches.') }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section 3: Subscription and Payment -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">3</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Subscription and Payment') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="credit-card" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Access to the Platform is subscription-based. Fees are charged according to your selected plan.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="credit-card" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Payments must be made in accordance with the billing terms of your plan.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="credit-card" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Bindamy Mareas reserves the right to suspend or terminate accounts with overdue payments.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="credit-card" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Taxes and other applicable fees are your responsibility.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="credit-card" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('We may adjust pricing in accordance with plan changes, account upgrades, or service enhancements.') }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section 4: Platform Usage -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">4</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Platform Usage') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('You may use the Platform only for lawful business purposes.') }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-14">
                            <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-3">
                                {{ t('Prohibited actions include:') }}
                            </h3>
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="x-circle" class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Unauthorized access or hacking attempts.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="x-circle" class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Using the Platform to store or transmit malicious content.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="x-circle" class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Reverse engineering or copying the Platform.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="x-circle" class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Sharing login credentials with unauthorized users.') }}</span>
                                </li>
                            </ul>
                            <div class="mt-6 p-4 rounded-lg bg-primary/5 dark:bg-primary/10 border border-primary/20">
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                                    {{ t('The Platform provides tools for financial, operational, and crew management. Calculations and reports are provided for informational purposes and should be validated for legal or financial compliance.') }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Section 5: Intellectual Property -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">5</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Intellectual Property') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('All software, content, graphics, and trademarks on the Platform are the property of Bindamy Mareas or its licensors.') }}</p>
                            <p>{{ t('You may not copy, modify, distribute, or create derivative works without written permission.') }}</p>
                            <p>{{ t('By using the Platform, you grant Bindamy Mareas a limited license to process your data solely for the purpose of delivering services.') }}</p>
                        </div>
                    </section>

                    <!-- Section 6: Data and Privacy -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">6</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Data and Privacy') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('All data you provide remains your property.') }}</p>
                            <p>{{ t('Bindamy Mareas will handle your data according to the Privacy Policy.') }}</p>
                            <p>{{ t('You agree not to disclose confidential information obtained through the Platform.') }}</p>
                        </div>
                    </section>

                    <!-- Section 7: Confidentiality -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">7</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Confidentiality') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('Both parties agree to maintain confidentiality of proprietary or sensitive information.') }}</p>
                            <p>{{ t('Confidential information includes operational data, financial records, vessel specifications, and crew information.') }}</p>
                            <p>{{ t('Exceptions apply where disclosure is legally required.') }}</p>
                        </div>
                    </section>

                    <!-- Section 8: Termination -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">8</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Termination') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('You may terminate your account by contacting support.') }}</p>
                            <p class="font-semibold text-card-foreground dark:text-card-foreground mb-2">{{ t('Bindamy Mareas may suspend or terminate accounts for:') }}</p>
                            <ul class="space-y-2 ml-4">
                                <li class="flex items-start gap-2">
                                    <span class="text-primary mt-1">•</span>
                                    <span>{{ t('Violation of these Terms.') }}</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-primary mt-1">•</span>
                                    <span>{{ t('Illegal activities.') }}</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-primary mt-1">•</span>
                                    <span>{{ t('Misuse of the Platform.') }}</span>
                                </li>
                            </ul>
                            <p class="mt-4">{{ t('Upon termination, data may be deleted according to the Data Retention Policy in the Privacy Policy.') }}</p>
                        </div>
                    </section>

                    <!-- Section 9: Disclaimers -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">9</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Disclaimers') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('The Platform is provided "as is" and "as available."') }}</p>
                            <p>{{ t('Bindamy Mareas does not guarantee uninterrupted or error-free service.') }}</p>
                            <p>{{ t('Calculations, reports, or recommendations are informational; users are responsible for validating accuracy.') }}</p>
                            <p>{{ t('Bindamy Mareas is not responsible for third-party integrations or services linked within the Platform.') }}</p>
                        </div>
                    </section>

                    <!-- Section 10: Limitation of Liability -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">10</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Limitation of Liability') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('To the maximum extent permitted by law, Bindamy Mareas is not liable for indirect, incidental, special, or consequential damages.') }}</p>
                            <p>{{ t('Our total liability is limited to the amount paid for the service in the 12 months preceding a claim.') }}</p>
                            <p>{{ t('Liability does not cover acts of third parties, including payment processors, cloud providers, or external integrations.') }}</p>
                        </div>
                    </section>

                    <!-- Section 11: Indemnification -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">11</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Indemnification') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('You agree to indemnify, defend, and hold harmless Bindamy Mareas, its officers, employees, and affiliates from claims, liabilities, damages, and expenses arising from:') }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="alert-triangle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Your misuse of the Platform.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="alert-triangle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Breach of these Terms.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="alert-triangle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Violation of applicable laws or regulations.') }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section 12: Governing Law and Dispute Resolution -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">12</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Governing Law and Dispute Resolution') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('These Terms are governed by the laws of Angola.') }}</p>
                            <p>{{ t('Any disputes shall be resolved in the competent courts of Angola, unless the parties mutually agree to alternative dispute resolution.') }}</p>
                        </div>
                    </section>

                    <!-- Section 13: Changes to Terms -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">13</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Changes to Terms') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('Bindamy Mareas may update these Terms at any time.') }}</p>
                            <p>{{ t('Updates will be posted with the new "Effective Date."') }}</p>
                            <p>{{ t('Continued use of the Platform constitutes acceptance of the updated Terms.') }}</p>
                        </div>
                    </section>

                    <!-- Section 14: Contact Information -->
                    <section class="rounded-xl border-2 border-primary/20 dark:border-primary/30 bg-gradient-to-br from-primary/5 via-primary/10 to-primary/5 dark:from-primary/10 dark:via-primary/20 dark:to-primary/10 p-6 lg:p-8 shadow-lg">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/20 dark:bg-primary/30 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">14</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Contact Information') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-4">
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                                {{ t('For questions, support, or inquiries regarding these Terms:') }}
                            </p>
                            <div class="p-4 rounded-lg bg-card/50 dark:bg-card/30 border border-border/50 dark:border-sidebar-border/50">
                                <div class="flex items-center gap-3 mb-2">
                                    <Icon name="mail" class="w-5 h-5 text-primary" />
                                    <span class="text-base font-semibold text-card-foreground dark:text-card-foreground">
                                        {{ t('Email:') }}
                                    </span>
                                    <a href="mailto:support@bindamy.site" class="text-primary hover:underline font-medium">
                                        support@bindamy.site
                                    </a>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon name="clock" class="w-5 h-5 text-primary" />
                                    <span class="text-sm text-muted-foreground dark:text-muted-foreground">
                                        {{ t('Response Time:') }} <span class="font-semibold text-card-foreground dark:text-card-foreground">{{ t('Up to 24 hours during business days') }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
