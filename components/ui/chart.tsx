'use client'

import * as React from 'react'
import { ResponsiveContainer, Tooltip } from 'recharts'

export type ChartConfig = Record<string, { label?: string; color?: string }>

function cssVars(config: ChartConfig): React.CSSProperties {
  return Object.fromEntries(
    Object.entries(config)
      .filter(([, value]) => value.color)
      .map(([key, value]) => [`--color-${key}`, value.color])
  ) as React.CSSProperties
}

export function ChartContainer({
  config,
  className,
  children
}: {
  config: ChartConfig
  className?: string
  children: React.ReactElement
}) {
  const [mounted, setMounted] = React.useState(false)

  React.useEffect(() => {
    setMounted(true)
  }, [])

  return (
    <div className={className} style={cssVars(config)}>
      {mounted ? (
        <ResponsiveContainer width='100%' height='100%' minWidth={1} minHeight={1}>
          {children}
        </ResponsiveContainer>
      ) : null}
    </div>
  )
}

export const ChartTooltip = Tooltip

export function ChartTooltipContent(_props: { hideLabel?: boolean }) {
  return (
    <div className='bg-popover text-popover-foreground rounded-md border px-2 py-1 text-xs shadow-sm'>
      Detalle
    </div>
  )
}
