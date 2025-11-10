import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-lg border px-2.5 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1.5 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-all duration-200 overflow-hidden shadow-sm",
  {
    variants: {
      variant: {
        default:
          "border-primary/20 bg-primary text-primary-foreground [a&]:hover:bg-primary/90 [a&]:hover:shadow-md",
        secondary:
          "border-secondary/30 bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90 [a&]:hover:shadow-md",
        destructive:
         "border-destructive/20 bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60 [a&]:hover:shadow-md",
        outline:
          "border-border/60 text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground [a&]:hover:shadow-md",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
