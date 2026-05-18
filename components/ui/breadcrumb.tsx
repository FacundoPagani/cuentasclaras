import * as React from 'react'

import { cn } from '@/lib/utils'

export function Breadcrumb({ className, ...props }: React.HTMLAttributes<HTMLElement>) {
  return <nav className={cn('text-sm', className)} {...props} />
}

export function BreadcrumbList({ className, ...props }: React.HTMLAttributes<HTMLOListElement>) {
  return <ol className={cn('flex items-center gap-1.5', className)} {...props} />
}

export function BreadcrumbItem({ className, ...props }: React.HTMLAttributes<HTMLLIElement>) {
  return <li className={cn('inline-flex items-center gap-1.5', className)} {...props} />
}

export function BreadcrumbLink({ className, ...props }: React.AnchorHTMLAttributes<HTMLAnchorElement>) {
  return <a className={cn('text-muted-foreground hover:text-foreground', className)} {...props} />
}

export function BreadcrumbPage({ className, ...props }: React.HTMLAttributes<HTMLSpanElement>) {
  return <span className={cn('font-medium text-foreground', className)} {...props} />
}

export function BreadcrumbSeparator() {
  return <li className='text-muted-foreground'>/</li>
}
