<template>
  <footer class="bg-background/80 backdrop-blur-md border-t border-border mt-auto">
    <div class="max-w-6xl mx-auto px-6 py-6">
      <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
        <!-- Center: Copyright -->
        <div class="flex items-center space-x-2 text-sm text-muted-foreground">
          <Icon name="copyright" class="h-4 w-4" />
          <span>2025 Bindamy Mareas. All rights reserved.</span>
        </div>

        <!-- Right: Privacy & Terms -->
        <div class="flex items-center space-x-6 text-sm text-muted-foreground">
          <Link href="/privacy-policy" class="hover:text-foreground transition-colors">
            Privacy Policy
          </Link>
          <Link href="/terms-of-service" class="hover:text-foreground transition-colors">
            Terms of Service
          </Link>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Icon from '@/components/Icon.vue'

const currentYear = computed(() => new Date().getFullYear())
</script>
