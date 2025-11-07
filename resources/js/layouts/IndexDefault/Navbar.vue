<template>
  <div>
    <!-- Floating scroll indicator when navbar is hidden -->
    <div
      v-if="!isNavbarVisible"
      class="fixed top-4 left-1/2 transform -translate-x-1/2 z-40 bg-card/90 backdrop-blur-sm rounded-full px-4 py-2 shadow-lg border border-border transition-all duration-500 ease-in-out"
    >
      <div class="flex items-center space-x-2 text-xs text-muted-foreground">
        <Icon name="chevron-up" class="w-4 h-4 animate-bounce" />
        <span>Scroll up for navigation</span>
      </div>
    </div>

    <!-- Main Navbar -->
    <nav
      :class="[
        'sticky z-50 bg-transparent transition-all duration-300',
        isScrolled ? 'top-0' : 'top-4'
      ]"
    >
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div
          :class="[
            'grid grid-cols-3 items-center rounded-2xl border px-4 md:px-6 will-change-transform transition-all duration-300 ring-1',
            isScrolled ? 'h-12 lg:h-14' : 'h-14 lg:h-16',
            isScrolled
              ? 'bg-card/90 backdrop-blur-xl border-border shadow-xl ring-ring/10'
              : 'bg-card/60 backdrop-blur-md border-border/60 shadow-lg ring-ring/5'
          ]"
        >
          <!-- Left: Logo -->
          <div class="flex items-center">
            <button
              @click="goToVesselSelector"
              class="flex items-center space-x-2.5"
            >
              <div class="bg-primary rounded-full p-2">
                <Icon name="ship" class="h-5 w-5 text-primary-foreground" />
              </div>
              <div class="text-left">
                <h1 class="text-base font-semibold text-foreground lg:text-lg">
                  Bindamy Mareas
                </h1>
                <p class="text-xs text-muted-foreground -mt-1">
                  Vessel Management
                </p>
              </div>
            </button>
          </div>

          <!-- Center: Desktop Navigation -->
          <div class="hidden md:flex items-center justify-center space-x-2 lg:space-x-3">
            <button
              @click="goToProfile"
              :class="[
                'px-3 py-2 rounded-lg text-[15px] font-medium transition-all duration-200 hover:underline underline-offset-8 decoration-muted-foreground hover:scale-105',
                isProfilePage
                  ? 'text-foreground bg-muted shadow-sm'
                  : 'text-muted-foreground hover:text-foreground hover:bg-muted/80 hover:shadow-sm'
              ]"
            >
              Profile
            </button>
          </div>

          <!-- Right: Theme Toggle & User Menu -->
          <div class="flex items-center justify-end gap-2">
            <!-- Desktop Theme Toggle -->
            <div class="hidden md:flex items-center">
              <div class="flex items-center space-x-1 px-2 py-1.5 rounded-lg hover:bg-muted/60 transition-colors duration-200">
                <ThemeToggle />
              </div>
            </div>

            <!-- User Avatar Dropdown -->
            <div class="relative">
              <button
                @click="toggleDropdown"
                class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-muted transition-all duration-200"
                title="User Menu"
              >
                <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center">
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
                  class="absolute right-0 mt-2 w-64 rounded-xl shadow-xl bg-card border border-border z-50 overflow-hidden"
                >
                  <div class="py-2">
                    <!-- User Info -->
                    <div class="px-4 py-3 border-b border-border bg-muted">
                      <p class="text-sm font-semibold text-card-foreground">
                        {{ user.name }}
                      </p>
                      <p class="text-xs text-muted-foreground">
                        {{ user.email }}
                      </p>
                    </div>

                    <!-- Menu Items -->
                    <div class="py-1">
                      <button
                        @click="goToProfile"
                        class="flex items-center w-full px-4 py-3 text-sm text-card-foreground hover:bg-muted transition-all duration-200"
                      >
                        <Icon name="user" class="w-4 h-4 mr-3 text-muted-foreground" />
                        <span>Profile Settings</span>
                      </button>
                      <button
                        @click="logout"
                        class="flex items-center w-full px-4 py-3 text-sm text-destructive hover:bg-destructive/10 transition-all duration-200"
                      >
                        <Icon name="log-out" class="w-4 h-4 mr-3 text-destructive" />
                        <span>Logout</span>
                      </button>
                    </div>
                  </div>
                </div>
              </Transition>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
              <button
                @click="toggleMobileMenu"
                class="p-1.5 hover:bg-muted rounded-lg transition-colors duration-200"
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
          <div v-if="isMobileMenuOpen" class="space-y-1 border-t border-border bg-card/70 backdrop-blur-md py-3 md:hidden rounded-2xl mx-4 mt-2 shadow-lg ring-1 ring-border">
            <nav class="space-y-1">
              <button
                @click="goToProfile"
                :class="[
                  'block px-4 py-2.5 rounded-lg mx-2 transition-all duration-200 hover:scale-105',
                  isProfilePage
                    ? 'font-medium text-card-foreground bg-muted shadow-sm'
                    : 'text-muted-foreground hover:text-card-foreground hover:bg-muted/80 hover:shadow-sm'
                ]"
              >
                Profile
              </button>
            </nav>

            <!-- Mobile Theme Toggle -->
            <div class="flex items-center justify-center px-4 py-3 border-t border-border">
              <div class="flex items-center space-x-2">
                <ThemeToggle />
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, Transition } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from '@/components/Icon.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'

interface User {
  id: number
  name: string
  email: string
}

interface Props {
  user: User
}

const props = defineProps<Props>()
const page = usePage()

const showDropdown = ref(false)
const isMobileMenuOpen = ref(false)
const isNavbarVisible = ref(true)
const isScrolled = ref(false)
const lastScrollY = ref(0)

const userInitials = computed(() => {
  return props.user.name
    .split(' ')
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const isProfilePage = computed(() => {
  const url = page.props.url || window.location.pathname
  return url === '/panel/profile'
})

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const goToVesselSelector = () => {
  showDropdown.value = false
  isMobileMenuOpen.value = false
  router.visit('/panel')
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

// Scroll behavior logic
const handleScroll = () => {
  const currentScrollY = window.scrollY

  // Show navbar when scrolling up, hide when scrolling down
  if (currentScrollY > lastScrollY.value && currentScrollY > 100) {
    isNavbarVisible.value = false
  } else {
    isNavbarVisible.value = true
  }

  // Update scrolled state for styling
  isScrolled.value = currentScrollY > 50

  lastScrollY.value = currentScrollY
}

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement
  if (!target.closest('.relative')) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', handleScroll)
})
</script>
