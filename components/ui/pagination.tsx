import * as React from 'react'

import { cn } from '@/lib/utils'

export function Pagination({ className, ...props }: React.HTMLAttributes<HTMLElement>) {
  return <nav className={cn('mx-auto flex w-full justify-center', className)} {...props} />
}

export function PaginationContent({ className, ...props }: React.HTMLAttributes<HTMLUListElement>) {
  return <ul className={cn('flex flex-row items-center gap-1', className)} {...props} />
}

export function PaginationItem({ className, ...props }: React.HTMLAttributes<HTMLLIElement>) {
  return <li className={className} {...props} />
}

export function PaginationEllipsis({ className, ...props }: React.HTMLAttributes<HTMLSpanElement>) {
  return <span className={cn('flex size-9 items-center justify-center', className)} {...props}>...</span>
}
