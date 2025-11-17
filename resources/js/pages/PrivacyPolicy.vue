<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useI18n } from '@/composables/useI18n'
import Icon from '@/components/Icon.vue'

const { t } = useI18n()
</script>

<template>
    <Head title="Privacy Policy - Bindamy Mareas" />
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-b from-background via-muted/20 to-background dark:from-[#121212] dark:via-[#0a0a0a] dark:to-[#121212]">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <!-- Header Section -->
                <div class="mb-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 dark:bg-primary/20 mb-4">
                        <Icon name="shield" class="w-8 h-8 text-primary" />
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                        {{ t('Bindamy Mareas Privacy Policy') }}
                    </h1>
                    <p class="text-base text-muted-foreground dark:text-muted-foreground">
                        {{ t('Effective Date:') }} <span class="font-semibold text-card-foreground dark:text-card-foreground">November 17, 2025</span>
                    </p>
                </div>

                <!-- Introduction Card -->
                <div class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 mb-8 shadow-lg">
                    <p class="text-base text-muted-foreground dark:text-muted-foreground leading-relaxed">
                        {{ t('Bindamy Mareas ("we," "our," "us") respects your privacy and is committed to protecting your personal and business data. This Privacy Policy explains what information we collect, how we use it, how it is stored, and your rights regarding your data when using our platform and services.') }}
                    </p>
                </div>

                <!-- Main Content -->
                <div class="space-y-8">
                    <!-- Section 1: Information We Collect -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">1</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Information We Collect') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('We collect different types of information depending on how you use our platform:') }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-6 ml-14">
                            <!-- Personal Information -->
                            <div>
                                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-3 flex items-center gap-2">
                                    <Icon name="user" class="w-5 h-5 text-primary" />
                                    {{ t('a) Personal Information') }}
                                </h3>
                                <ul class="space-y-2 text-sm text-muted-foreground dark:text-muted-foreground ml-7">
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('User account information: name, email address, phone number, username, and password.') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('Employment-related data of crew members: names, positions, schedules, and salary information.') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('Payment information: billing addresses, credit/debit card details, and bank account information for financial operations.') }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Business and Operational Data -->
                            <div>
                                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-3 flex items-center gap-2">
                                    <Icon name="briefcase" class="w-5 h-5 text-primary" />
                                    {{ t('b) Business and Operational Data') }}
                                </h3>
                                <ul class="space-y-2 text-sm text-muted-foreground dark:text-muted-foreground ml-7">
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('Vessel information: registration numbers, specifications, maintenance history, and location tracking.') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('Operational data: fishing trips, catch details, crew assignments, salary distribution profiles.') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('Financial records: revenue, expenses, transfers, and VAT calculations.') }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Usage Data -->
                            <div>
                                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-3 flex items-center gap-2">
                                    <Icon name="activity" class="w-5 h-5 text-primary" />
                                    {{ t('c) Usage Data') }}
                                </h3>
                                <ul class="space-y-2 text-sm text-muted-foreground dark:text-muted-foreground ml-7">
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('Technical data: IP address, device type, browser type, operating system, and log-in times.') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-primary mt-1">•</span>
                                        <span>{{ t('Platform activity: actions performed, reports generated, and notifications received.') }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Cookies and Tracking -->
                            <div>
                                <h3 class="text-lg font-semibold text-card-foreground dark:text-card-foreground mb-3 flex items-center gap-2">
                                    <Icon name="cookie" class="w-5 h-5 text-primary" />
                                    {{ t('d) Cookies and Tracking') }}
                                </h3>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground ml-7 leading-relaxed">
                                    {{ t('We may use cookies or similar technologies to manage sessions, optimize performance, and collect analytics.') }}
                                </p>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground ml-7 mt-2 leading-relaxed">
                                    {{ t('Users can manage cookies through browser settings.') }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Section 2: How We Use Your Information -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">2</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('How We Use Your Information') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('Your data is used to deliver, maintain, and improve our platform:') }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Provide vessel management, crew tracking, and financial management services.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Automate salary calculations and distribution profiles.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Generate reports, PDFs, and analytics dashboards.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Send notifications and alerts regarding maintenance, operations, and financial transactions.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Improve platform performance and user experience.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Provide technical support and respond to inquiries.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="check-circle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Comply with legal obligations, including labor, tax, and maritime regulations.') }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section 3: Sharing and Disclosure -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">3</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Sharing and Disclosure') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('We do not sell your data. Information may be shared only in the following circumstances:') }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="users" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Within your organization to authorized personnel needing operational or financial access.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="shield" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('With third-party service providers assisting in hosting, email delivery, or platform operations, under strict confidentiality agreements.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="gavel" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('When legally required by law enforcement or government authorities.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="lock" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('To protect the rights, safety, or property of Bindamy Mareas, our users, or the public.') }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section 4: Data Security -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">4</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Data Security') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('We employ technical and organizational measures to protect your data:') }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="lock" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Encryption of sensitive data in transit and at rest.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="shield-check" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Secure storage systems and restricted access based on user roles.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="eye" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Regular security audits and system monitoring.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="alert-triangle" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Protection against unauthorized access, disclosure, or destruction of data.') }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section 5: Data Retention -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">5</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Data Retention') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('Personal and business data is retained while your account is active or as required to provide services.') }}</p>
                            <p>{{ t('Financial records are retained as required by law.') }}</p>
                            <p>{{ t('You may request deletion of your personal data; some information may remain for legal or compliance reasons.') }}</p>
                        </div>
                    </section>

                    <!-- Section 6: Your Rights -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">6</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Your Rights') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('Depending on your jurisdiction, you may have rights to:') }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="eye" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Access your personal data.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="edit" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Correct inaccuracies in your data.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="trash-2" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Request deletion of personal data.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="ban" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Object to processing for marketing purposes.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="download" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Receive your data in a machine-readable format.') }}</span>
                                </li>
                            </ul>
                            <div class="mt-6 p-4 rounded-lg bg-primary/5 dark:bg-primary/10 border border-primary/20">
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground">
                                    {{ t('To exercise these rights, contact:') }}
                                    <a href="mailto:support@bindamy.site" class="text-primary hover:underline font-semibold ml-1">support@bindamy.site</a>
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Section 7: International Transfers -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">7</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('International Transfers') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('Your data may be processed or stored in countries other than your own. We ensure that any international transfers comply with data protection laws and provide adequate safeguards.') }}</p>
                        </div>
                    </section>

                    <!-- Section 8: Third-Party Services -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">8</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Third-Party Services') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('The platform may contain links or integrations with third-party services.') }}</p>
                            <p>{{ t('We are not responsible for the privacy practices of third parties.') }}</p>
                            <p>{{ t('We recommend reviewing third-party privacy policies before sharing data.') }}</p>
                        </div>
                    </section>

                    <!-- Section 9: Notifications -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">9</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Notifications') }}
                                </h2>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4 leading-relaxed">
                                    {{ t('We may send emails to provide:') }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-14">
                            <ul class="space-y-3 text-sm text-muted-foreground dark:text-muted-foreground">
                                <li class="flex items-start gap-3">
                                    <Icon name="mail" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Account updates and notifications.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="bell" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Transaction or operational alerts.') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <Icon name="wrench" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                    <span>{{ t('Maintenance or service reminders.') }}</span>
                                </li>
                            </ul>
                            <p class="mt-4 text-sm text-muted-foreground dark:text-muted-foreground">
                                {{ t('You can manage notification preferences through your account settings.') }}
                            </p>
                        </div>
                    </section>

                    <!-- Section 10: Children's Privacy -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">10</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Children\'s Privacy') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('The platform is not intended for individuals under 18. We do not knowingly collect personal data from children. If we become aware of such data, it will be deleted.') }}</p>
                        </div>
                    </section>

                    <!-- Section 11: Changes to This Privacy Policy -->
                    <section class="rounded-xl border border-border/50 dark:border-sidebar-border/50 bg-card/80 dark:bg-card/50 backdrop-blur-sm p-6 lg:p-8 shadow-md">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">11</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Changes to This Privacy Policy') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-3 text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                            <p>{{ t('We may update this policy to reflect changes in our practices or legal requirements.') }}</p>
                            <p>{{ t('Updates will be posted with a new "Effective Date."') }}</p>
                            <p>{{ t('Continued use of the platform after updates constitutes acceptance of the revised policy.') }}</p>
                        </div>
                    </section>

                    <!-- Section 12: Contact Information -->
                    <section class="rounded-xl border-2 border-primary/20 dark:border-primary/30 bg-gradient-to-br from-primary/5 via-primary/10 to-primary/5 dark:from-primary/10 dark:via-primary/20 dark:to-primary/10 p-6 lg:p-8 shadow-lg">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary/20 dark:bg-primary/30 flex items-center justify-center">
                                <span class="text-lg font-bold text-primary">12</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-card-foreground dark:text-card-foreground mb-4">
                                    {{ t('Contact Information') }}
                                </h2>
                            </div>
                        </div>

                        <div class="ml-14 space-y-4">
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground leading-relaxed">
                                {{ t('If you have questions, complaints, or requests regarding your data:') }}
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
