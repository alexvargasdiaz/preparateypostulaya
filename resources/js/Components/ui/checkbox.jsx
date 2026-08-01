import * as React from "react"
import * as CheckboxPrimitive from "radix-ui/checkbox"

import { cn } from "@/lib/utils"

function Checkbox({ className, ...props }) {
  return (
    <CheckboxPrimitive.Root
      data-slot="checkbox"
      className={cn(
        "peer size-4 shrink-0 rounded border border-cyber-dark-400 bg-surface",
        "data-[state=checked]:bg-neon-cyan data-[state=checked]:border-neon-cyan",
        "data-[state=checked]:text-cyber-dark",
        "focus-visible:ring-2 focus-visible:ring-neon-cyan/50 focus-visible:outline-none",
        "disabled:cursor-not-allowed disabled:opacity-50",
        "transition-all duration-200",
        className
      )}
      {...props}
    >
      <CheckboxPrimitive.Indicator
        className={cn("flex items-center justify-center text-current")}
      >
        <svg viewBox="0 0 12 12" className="size-3 fill-current">
          <path d="M3 6l2 2 4-4" stroke="currentColor" strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
      </CheckboxPrimitive.Indicator>
    </CheckboxPrimitive.Root>
  )
}

export { Checkbox }
