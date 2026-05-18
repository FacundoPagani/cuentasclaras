'use client'

import * as React from 'react'
import { MenuIcon } from 'lucide-react'

import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const SidebarContext = React.createContext({ open: true, setOpen: (_value: boolean) => {} })

export function SidebarProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = React.useState(true)
  return <SidebarContext.Provider value={{ open, setOpen }}>{children}</SidebarContext.Provider>
}

export function Sidebar({ className, ...props }: React.HTMLAttributes<HTMLElement>) {
  const { open } = React.useContext(SidebarContext)
  return (
    <aside
      className={cn(
        'bg-card hidden min-h-dvh w-68 shrink-0 border-r md:block',
        !open && 'md:hidden',
        className
      )}
      {...props}
    />
  )
}

export function SidebarTrigger({ className }: { className?: string }) {
  const { open, setOpen } = React.useContext(SidebarContext)
  return (
    <Button variant='ghost' size='icon' className={className} onClick={() => setOpen(!open)} aria-label='Alternar menu'>
      <MenuIcon className='size-5' />
    </Button>
  )
}

export function SidebarContent({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('flex h-full flex-col gap-2 p-3', className)} {...props} />
}

export function SidebarGroup({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('space-y-1', className)} {...props} />
}

export function SidebarGroupLabel({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('px-2 py-1.5 text-xs font-medium uppercase text-muted-foreground', className)} {...props} />
}

export function SidebarGroupContent({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={className} {...props} />
}

export function SidebarMenu({ className, ...props }: React.HTMLAttributes<HTMLUListElement>) {
  return <ul className={cn('space-y-1', className)} {...props} />
}

export function SidebarMenuItem({ className, ...props }: React.HTMLAttributes<HTMLLIElement>) {
  return <li className={cn('relative', className)} {...props} />
}

export function SidebarMenuButton({ className, asChild, ...props }: React.ComponentProps<typeof Button>) {
  return (
    <Button
      asChild={asChild}
      variant='ghost'
      className={cn('h-9 w-full justify-start px-2 text-left [&_svg]:size-4', className)}
      {...props}
    />
  )
}

export function SidebarMenuBadge({ className, ...props }: React.HTMLAttributes<HTMLSpanElement>) {
  return <span className={cn('absolute right-2 top-1.5 px-2 py-0.5 text-xs', className)} {...props} />
}
