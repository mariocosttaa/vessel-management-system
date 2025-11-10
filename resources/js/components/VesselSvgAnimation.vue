<template>
  <div class="flex items-center justify-center">
    <svg
      width="128"
      height="128"
      viewBox="0 0 128 128"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      class="vessel-svg-animation"
    >
      <!-- Water/Waves -->
      <g class="waves">
        <path
          d="M0 96 Q32 88, 64 96 T128 96 L128 128 L0 128 Z"
          fill="currentColor"
          class="text-primary/20"
          opacity="0.6"
        >
          <animate
            attributeName="d"
            values="M0 96 Q32 88, 64 96 T128 96 L128 128 L0 128 Z;M0 96 Q32 104, 64 96 T128 96 L128 128 L0 128 Z;M0 96 Q32 88, 64 96 T128 96 L128 128 L0 128 Z"
            dur="2s"
            repeatCount="indefinite"
          />
        </path>
        <path
          d="M0 104 Q32 96, 64 104 T128 104 L128 128 L0 128 Z"
          fill="currentColor"
          class="text-primary/30"
          opacity="0.4"
        >
          <animate
            attributeName="d"
            values="M0 104 Q32 96, 64 104 T128 104 L128 128 L0 128 Z;M0 104 Q32 112, 64 104 T128 104 L128 128 L0 128 Z;M0 104 Q32 96, 64 104 T128 104 L128 128 L0 128 Z"
            dur="2.5s"
            repeatCount="indefinite"
          />
        </path>
      </g>

      <!-- Vessel Hull -->
      <g class="vessel-hull">
        <!-- Main hull -->
        <path
          d="M24 72 L104 72 L96 88 L32 88 Z"
          fill="currentColor"
          class="text-primary"
          opacity="0.9"
        />

        <!-- Hull detail line -->
        <path
          d="M28 80 L100 80"
          stroke="currentColor"
          class="text-primary-foreground"
          stroke-width="1"
          opacity="0.3"
        />
      </g>

      <!-- Vessel Superstructure -->
      <g class="vessel-superstructure">
        <!-- Cabin 1 -->
        <rect
          x="40"
          y="56"
          width="16"
          height="16"
          fill="currentColor"
          class="text-primary"
          opacity="0.8"
        />
        <rect
          x="42"
          y="58"
          width="12"
          height="8"
          fill="currentColor"
          class="text-primary-foreground"
          opacity="0.2"
        />

        <!-- Cabin 2 -->
        <rect
          x="72"
          y="52"
          width="20"
          height="20"
          fill="currentColor"
          class="text-primary"
          opacity="0.8"
        />
        <rect
          x="74"
          y="54"
          width="16"
          height="12"
          fill="currentColor"
          class="text-primary-foreground"
          opacity="0.2"
        />
      </g>

      <!-- Mast -->
      <line
        x1="64"
        y1="52"
        x2="64"
        y2="32"
        stroke="currentColor"
        class="text-primary"
        stroke-width="3"
        stroke-linecap="round"
      />

      <!-- Flag/Banner -->
      <g class="flag">
        <rect
          x="64"
          y="32"
          width="20"
          height="12"
          fill="currentColor"
          class="text-primary"
          opacity="0.9"
        >
          <animate
            attributeName="x"
            values="64;66;64"
            dur="1s"
            repeatCount="indefinite"
          />
        </rect>
        <path
          d="M84 32 L84 44"
          stroke="currentColor"
          class="text-primary"
          stroke-width="2"
        />
      </g>

      <!-- Wake/Waves behind vessel -->
      <g class="wake" opacity="0.3">
        <circle
          cx="32"
          cy="88"
          r="4"
          fill="currentColor"
          class="text-primary"
        >
          <animate
            attributeName="r"
            values="2;6;2"
            dur="1.5s"
            repeatCount="indefinite"
          />
          <animate
            attributeName="opacity"
            values="0.5;0.2;0.5"
            dur="1.5s"
            repeatCount="indefinite"
          />
        </circle>
        <circle
          cx="48"
          cy="90"
          r="3"
          fill="currentColor"
          class="text-primary"
        >
          <animate
            attributeName="r"
            values="2;5;2"
            dur="1.8s"
            repeatCount="indefinite"
          />
          <animate
            attributeName="opacity"
            values="0.4;0.15;0.4"
            dur="1.8s"
            repeatCount="indefinite"
          />
        </circle>
      </g>

      <!-- Motion lines (optional) -->
      <g class="motion-lines" opacity="0.2">
        <line
          x1="20"
          y1="76"
          x2="24"
          y2="76"
          stroke="currentColor"
          class="text-primary"
          stroke-width="2"
          stroke-linecap="round"
        >
          <animate
            attributeName="opacity"
            values="0.2;0.5;0.2"
            dur="1s"
            repeatCount="indefinite"
          />
        </line>
        <line
          x1="20"
          y1="80"
          x2="24"
          y2="80"
          stroke="currentColor"
          class="text-primary"
          stroke-width="2"
          stroke-linecap="round"
        >
          <animate
            attributeName="opacity"
            values="0.2;0.5;0.2"
            dur="1.2s"
            repeatCount="indefinite"
          />
        </line>
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
// This component renders an animated SVG vessel
</script>

<style scoped>
.vessel-svg-animation {
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
  }
}

.waves {
  animation: wave-motion 2s ease-in-out infinite;
}

.vessel-hull {
  animation: vessel-float 3s ease-in-out infinite;
}

.flag {
  animation: flag-wave 1s ease-in-out infinite;
}

@keyframes wave-motion {
  0%, 100% {
    transform: translateX(0);
  }
  50% {
    transform: translateX(2px);
  }
}

@keyframes vessel-float {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-2px) rotate(0.5deg);
  }
}

@keyframes flag-wave {
  0%, 100% {
    transform: translateX(0);
  }
  50% {
    transform: translateX(2px);
  }
}
</style>

