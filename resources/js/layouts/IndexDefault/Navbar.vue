<template>
  <div>
    <!-- Main Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 border-b transition-all duration-300" :class="navbarClasses">
      <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14 rounded-full transition-all duration-300 mt-3 px-6 shadow-sm" :class="navbarInnerClasses">
          <!-- Left: Logo -->
          <div class="flex items-center">
            <Link
              :href="isLandingPage ? '/' : '/panel'"
              class="flex items-center space-x-2"
            >
              <div class="bg-primary/80 rounded-full p-1.5">
                <Icon name="ship" class="h-4 w-4 text-primary-foreground" />
              </div>
              <div class="text-left">
                <h1 class="text-sm font-semibold text-card-foreground dark:text-card-foreground leading-tight">
                  Bindamy Mareas
                </h1>
              </div>
            </Link>
          </div>

          <!-- Center: Desktop Navigation -->
          <div class="hidden md:flex items-center justify-center space-x-1">
            <!-- Landing Page Navigation -->
            <template v-if="isLandingPage">
                     <a
                       href="/"
                       :class="[
                         'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                         'text-card-foreground dark:text-card-foreground bg-muted/50'
                       ]"
                     >
                       Home
                     </a>
                     <a
                       href="#pricing"
                       class="px-4 py-2 rounded-md text-sm font-medium text-muted-foreground dark:text-muted-foreground hover:text-card-foreground dark:hover:text-card-foreground hover:bg-muted/30 transition-colors"
                     >
                       Pricing
                     </a>
                     <a
                       href="#contact"
                       class="px-4 py-2 rounded-md text-sm font-medium text-muted-foreground dark:text-muted-foreground hover:text-card-foreground dark:hover:text-card-foreground hover:bg-muted/30 transition-colors"
                     >
                       Contact
                     </a>
            </template>

            <!-- Panel Navigation (logged in users) -->
            <template v-else>
              <Link
                href="/panel"
                :class="[
                  'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                  isHomePage
                    ? 'text-card-foreground dark:text-card-foreground bg-muted/50'
                    : 'text-muted-foreground dark:text-muted-foreground hover:text-card-foreground dark:hover:text-card-foreground hover:bg-muted/30'
                ]"
              >
                Panel
              </Link>
              <Link
                href="/panel/profile"
                :class="[
                  'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                  isProfilePage
                    ? 'text-card-foreground dark:text-card-foreground bg-muted/50'
                    : 'text-muted-foreground dark:text-muted-foreground hover:text-card-foreground dark:hover:text-card-foreground hover:bg-muted/30'
                ]"
              >
                Profile
              </Link>
              <Link
                href="/"
                class="px-4 py-2 rounded-md text-sm font-medium text-muted-foreground dark:text-muted-foreground hover:text-card-foreground dark:hover:text-card-foreground hover:bg-muted/30 transition-colors"
              >
                Website
              </Link>
            </template>
          </div>

          <!-- Right: Actions -->
          <div class="flex items-center gap-3">
            <!-- Desktop Language Switcher -->
            <div class="hidden md:flex items-center">
              <LanguageSwitcher />
            </div>

            <!-- Desktop Theme Toggle -->
            <div class="hidden md:flex items-center">
              <div class="flex items-center space-x-1 px-2 py-1.5 rounded-md hover:bg-muted/40 transition-colors duration-200">
                <ThemeToggle />
              </div>
            </div>

            <!-- Panel Page: User Avatar Dropdown (only shown when user is logged in on panel) -->
            <div v-if="props.user && !isLandingPage" class="relative">
              <button
                @click="toggleDropdown"
                class="flex items-center space-x-2 p-1.5 rounded-md hover:bg-muted/40 transition-all duration-200"
                :title="t('User Menu')"
              >
                <div class="h-8 w-8 rounded-full bg-primary/80 flex items-center justify-center">
                  <span class="text-sm font-semibold text-primary-foreground">
                    {{ userInitials }}
                  </span>
                </div>
                <Icon
                  name="chevron-down"
                  class="h-4 w-4 text-muted-foreground transition-transform duration-200"
                  :class="{ 'rotate-180': showDropdown }"
                />
              </button>

              <!-- Dropdown Menu -->
              <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div
                  v-if="showDropdown"
                  class="absolute right-0 mt-2 w-64 rounded-xl shadow-xl bg-card/90 backdrop-blur-xl border border-border/50 z-50 overflow-hidden"
                >
                  <div class="py-2">
                    <!-- User Info -->
                    <div class="px-4 py-3 border-b border-border/50 bg-muted/60">
                      <p class="text-sm font-semibold text-card-foreground">
                        {{ props.user.name }}
                      </p>
                      <p class="text-xs text-muted-foreground">
                        {{ props.user.email }}
                      </p>
                    </div>

                    <!-- Menu Items -->
                    <div class="py-1">
                      <button
                        @click="goToProfile"
                        class="flex items-center w-full px-4 py-3 text-sm text-card-foreground hover:bg-muted/40 transition-all duration-200"
                      >
                        <Icon name="user" class="w-4 h-4 mr-3 text-muted-foreground" />
                        <span>{{ t('Profile Settings') }}</span>
                      </button>
                      <button
                        @click="logout"
                        class="flex items-center w-full px-4 py-3 text-sm text-destructive hover:bg-destructive/10 transition-all duration-200"
                      >
                        <Icon name="log-out" class="w-4 h-4 mr-3 text-destructive" />
                        <span>{{ t('Logout') }}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </Transition>
            </div>

            <!-- Landing Page: Panel Button (when user is logged in) -->
            <Link
              v-else-if="isLandingPage && props.user"
              href="/panel"
              class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors text-sm"
            >
              <Icon name="grid" class="w-4 h-4 mr-2" />
              Panel
            </Link>

            <!-- Landing Page: Login Button (when user is not logged in) -->
            <Link
              v-else-if="isLandingPage && !props.user"
              :href="login()"
              class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors text-sm"
            >
              <Icon name="log-in" class="w-4 h-4 mr-2" />
              Login
            </Link>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
              <button
                @click="toggleMobileMenu"
                class="p-1.5 hover:bg-muted/40 rounded-md transition-colors duration-200"
              >
                <Icon :name="isMobileMenuOpen ? 'x' : 'menu'" class="h-5 w-5 text-muted-foreground" />
              </button>
            </div>
          </div>
        </div>

        <!-- Mobile Menu -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="transform scale-95 opacity-0"
          enter-to-class="transform scale-100 opacity-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="transform scale-100 opacity-100"
          leave-to-class="transform scale-95 opacity-0"
        >
          <div v-if="isMobileMenuOpen" class="border-t border-border/50 bg-card/95 backdrop-blur-md py-3 md:hidden">
            <nav class="space-y-1 px-4">
              <!-- Landing Page Mobile Menu -->
              <template v-if="isLandingPage">
                <a
                  href="/"
                  class="block px-4 py-2.5 rounded-lg font-medium text-card-foreground bg-muted/60 transition-all duration-200"
                >
                  Home
                </a>
                       <a
                         href="#pricing"
                         class="block px-4 py-2.5 rounded-lg text-muted-foreground hover:text-card-foreground hover:bg-muted/40 transition-all duration-200"
                       >
                         Pricing
                       </a>
                       <a
                         href="#contact"
                         class="block px-4 py-2.5 rounded-lg text-muted-foreground hover:text-card-foreground hover:bg-muted/40 transition-all duration-200"
                       >
                         Contact
                       </a>
              </template>

              <!-- Panel Mobile Menu -->
              <template v-else>
                <Link
                  href="/panel"
                  :class="[
                    'block px-4 py-2.5 rounded-lg transition-all duration-200',
                    isHomePage
                      ? 'font-medium text-card-foreground bg-muted/60'
                      : 'text-muted-foreground hover:text-card-foreground hover:bg-muted/40'
                  ]"
                >
                  Panel
                </Link>
                <Link
                  href="/panel/profile"
                  :class="[
                    'block px-4 py-2.5 rounded-lg transition-all duration-200',
                    isProfilePage
                      ? 'font-medium text-card-foreground bg-muted/60'
                      : 'text-muted-foreground hover:text-card-foreground hover:bg-muted/40'
                  ]"
                >
                  Profile
                </Link>
                <Link
                  href="/"
                  class="block px-4 py-2.5 rounded-lg text-muted-foreground hover:text-card-foreground hover:bg-muted/40 transition-all duration-200"
                >
                  Website
                </Link>
              </template>
            </nav>

            <!-- Mobile Language Switcher & Theme Toggle -->
            <div class="flex items-center justify-center px-4 py-3 border-t border-border/50 gap-3">
              <LanguageSwitcher />
              <ThemeToggle />
            </div>
          </div>
        </Transition>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, Transition } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import Icon from '@/components/Icon.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import { useI18n } from '@/composables/useI18n'
import { login } from '@/routes'

interface User {
  id: number
  name: string
  email: string
}

interface Props {
  user?: User | null
}

const props = withDefaults(defineProps<Props>(), {
  user: null
})
const page = usePage()
const { t } = useI18n()

const showDropdown = ref(false)
const isMobileMenuOpen = ref(false)
const scrollY = ref(0)
const isScrolled = computed(() => scrollY.value > 20)

const userInitials = computed(() => {
  if (!props.user) return ''
  return props.user.name
    .split(' ')
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const isLandingPage = computed(() => {
  const url = page.props.url || window.location.pathname
  return url === '/' || url === ''
})

const isHomePage = computed(() => {
  const url = page.props.url || window.location.pathname
  return url === '/panel' || url === '/panel/'
})

const isProfilePage = computed(() => {
  const url = page.props.url || window.location.pathname
  return url === '/panel/profile'
})

// Dynamic navbar classes based on scroll
const navbarClasses = computed(() => {
  if (isScrolled.value) {
    return 'border-sidebar-border/50 dark:border-sidebar-border/50'
  }
  return 'border-transparent'
})

const navbarInnerClasses = computed(() => {
  if (isScrolled.value) {
    // More opaque when scrolled
    return 'bg-card/80 dark:bg-card/40 backdrop-blur-xl'
  }
  // More transparent when at top
  return 'bg-card/30 dark:bg-card/10 backdrop-blur-md'
})

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const goToProfile = () => {
  showDropdown.value = false
  isMobileMenuOpen.value = false
  router.visit('/panel/profile')
}

const logout = () => {
  showDropdown.value = false
  isMobileMenuOpen.value = false
  router.post('/logout')
}

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement
  if (!target.closest('.relative')) {
    showDropdown.value = false
  }
}

// Handle scroll for dynamic transparency
const handleScroll = () => {
  scrollY.value = window.scrollY || window.pageYOffset
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('scroll', handleScroll, { passive: true })
  handleScroll() // Initial check
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', handleScroll)
})
</script>
