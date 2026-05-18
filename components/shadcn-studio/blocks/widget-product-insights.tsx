'use client'

import { Bar, BarChart } from 'recharts'

import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { type ChartConfig, ChartContainer } from '@/components/ui/chart'
import { Separator } from '@/components/ui/separator'

import { cn } from '@/lib/utils'

const commonChartData = [
  { month: 'Ene', reached: 168000 },
  { month: 'Feb', reached: 305000 },
  { month: 'Mar', reached: 213000 },
  { month: 'Abr', reached: 330000 },
  { month: 'May', reached: 305000 }
]

const commonChartConfig = {
  reached: {
    label: 'Gastos',
    color: 'var(--primary)'
  }
} satisfies ChartConfig

const obligationChartData = [
  { month: 'Ene', orders: 268000 },
  { month: 'Feb', orders: 245000 },
  { month: 'Mar', orders: 293000 },
  { month: 'Abr', orders: 310000 },
  { month: 'May', orders: 285000 }
]

const obligationChartConfig = {
  orders: {
    label: 'Gastos fijos',
    color: 'color-mix(in oklab, var(--primary) 20%, transparent)'
  }
} satisfies ChartConfig

const ProductInsightsCard = ({ className }: { className?: string }) => {
  return (
    <Card className={cn('gap-4', className)}>
      <CardHeader className='flex justify-between gap-3'>
        <div className='flex flex-col gap-1'>
          <span className='text-lg font-semibold'>Pulso del ciclo</span>
          <span className='text-muted-foreground text-sm'>Corte mensual listo para revisar</span>
        </div>
        <div className='bg-primary/10 text-primary flex size-20 items-center justify-center rounded-md text-2xl font-semibold'>
          CC
        </div>
      </CardHeader>
      <CardContent className='space-y-4'>
        <Separator />
        <div className='flex items-center justify-between gap-1'>
          <div className='flex flex-col gap-1'>
            <span className='text-xs'>Comunes registrados</span>
            <span className='text-2xl font-semibold'>$305.000</span>
          </div>
          <ChartContainer config={commonChartConfig} className='min-h-13 max-w-18'>
            <BarChart accessibilityLayer data={commonChartData} barSize={8}>
              <Bar dataKey='reached' fill='var(--color-reached)' radius={2} />
            </BarChart>
          </ChartContainer>
        </div>

        <div className='flex items-center justify-between gap-1'>
          <div className='flex flex-col gap-1'>
            <span className='text-xs'>Gastos fijos cargados</span>
            <span className='text-2xl font-semibold'>$285.000</span>
          </div>
          <ChartContainer config={obligationChartConfig} className='min-h-13 max-w-18'>
            <BarChart accessibilityLayer data={obligationChartData} barSize={8}>
              <Bar dataKey='orders' fill='var(--color-orders)' radius={2} />
            </BarChart>
          </ChartContainer>
        </div>
      </CardContent>
    </Card>
  )
}

export default ProductInsightsCard
