import * as React from 'react'

import { cn } from '@/lib/utils'

export function Progress({ value = 0, className }: { value?: number; className?: string }) {
  return (
    <div className={cn('bg-primary/10 h-2 overflow-hidden rounded-full', className)}>
      <div className='bg-primary h-full rounded-full' style={{ width: `${Math.max(0, Math.min(100, value))}%` }} />
    </div>
  )
}
