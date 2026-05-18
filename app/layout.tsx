import type { Metadata } from 'next'

import './globals.css'

export const metadata: Metadata = {
  title: 'CuentasClaras',
  description: 'Dashboard shadcn para gestion de gastos del hogar'
}

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang='es'>
      <body>{children}</body>
    </html>
  )
}
