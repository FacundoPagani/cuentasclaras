'use client'

import {
  BadgePercentIcon,
  ChartNoAxesCombinedIcon,
  CirclePercentIcon,
  DollarSignIcon,
  ShoppingBagIcon,
  TrendingUpIcon
} from 'lucide-react'

import { Bar, BarChart, Label, Pie, PieChart } from 'recharts'

import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { type ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart'

const planPercentage = 68
const totalBars = 24
const filledBars = Math.round((planPercentage * totalBars) / 100)

const planChartData = Array.from({ length: totalBars }, (_, index) => ({
  date: '15/05/2026',
  sales: index < filledBars ? 315 : 0
}))

const planChartConfig = {
  sales: {
    label: 'Carga'
  }
} satisfies ChartConfig

const MetricsData = [
  {
    icons: <TrendingUpIcon className='size-5' />,
    title: 'Transferencia Ana',
    value: '$142.500'
  },
  {
    icons: <BadgePercentIcon className='size-5' />,
    title: 'Transferencia Luis',
    value: '$118.300'
  },
  {
    icons: <DollarSignIcon className='size-5' />,
    title: 'Pozo requerido',
    value: '$260.800'
  },
  {
    icons: <ShoppingBagIcon className='size-5' />,
    title: 'Movimientos',
    value: '42'
  }
]

const revenueChartData = [
  { month: 'comunes', sales: 340, fill: 'var(--color-comunes)' },
  { month: 'fijos', sales: 260, fill: 'var(--color-fijos)' },
  { month: 'tarjetas', sales: 190, fill: 'var(--color-tarjetas)' }
]

const revenueChartConfig = {
  sales: {
    label: 'Monto'
  },
  comunes: {
    label: 'Comunes',
    color: 'var(--primary)'
  },
  fijos: {
    label: 'Fijos',
    color: 'color-mix(in oklab, var(--primary) 60%, transparent)'
  },
  tarjetas: {
    label: 'Tarjetas',
    color: 'color-mix(in oklab, var(--primary) 20%, transparent)'
  }
} satisfies ChartConfig

const SalesMetricsCard = ({ className }: { className?: string }) => {
  return (
    <Card className={className}>
      <CardContent className='space-y-4'>
        <div className='grid gap-6 lg:grid-cols-5'>
          <div className='flex flex-col gap-7 lg:col-span-3'>
            <span className='text-lg font-semibold'>Motor de liquidacion</span>
            <div className='flex items-center gap-3'>
              <div className='bg-primary/10 text-primary flex size-10.5 items-center justify-center rounded-lg font-semibold'>50</div>
              <div className='flex flex-col gap-0.5'>
                <span className='text-xl font-medium'>Division 50/50</span>
                <span className='text-muted-foreground text-sm'>Compensa quien adelanto mas gastos comunes</span>
              </div>
            </div>

            <div className='grid gap-4 sm:grid-cols-2'>
              {MetricsData.map((metric, index) => (
                <div key={index} className='flex items-center gap-3 rounded-md border px-4 py-2'>
                  <Avatar className='size-8.5 rounded-sm'>
                    <AvatarFallback className='bg-primary/10 text-primary shrink-0 rounded-sm'>
                      {metric.icons}
                    </AvatarFallback>
                  </Avatar>
                  <div className='flex flex-col gap-0.5'>
                    <span className='text-muted-foreground text-sm font-medium'>{metric.title}</span>
                    <span className='text-lg font-medium'>{metric.value}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
          <Card className='gap-4 py-4 shadow-none lg:col-span-2'>
            <CardHeader className='gap-1'>
              <CardTitle className='text-lg font-semibold'>Composicion</CardTitle>
            </CardHeader>
            <CardContent className='px-0'>
              <ChartContainer config={revenueChartConfig} className='h-38.5 w-full'>
                <PieChart margin={{ top: 0, bottom: 0, left: 0, right: 0 }}>
                  <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                  <Pie
                    data={revenueChartData}
                    dataKey='sales'
                    nameKey='month'
                    startAngle={300}
                    endAngle={660}
                    innerRadius={58}
                    outerRadius={75}
                    paddingAngle={2}
                  >
                    <Label
                      content={({ viewBox }) => {
                        if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                          return (
                            <text x={viewBox.cx} y={viewBox.cy} textAnchor='middle' dominantBaseline='middle'>
                              <tspan x={viewBox.cx} y={(viewBox.cy || 0) - 12} className='fill-card-foreground text-lg font-medium'>
                                790k
                              </tspan>
                              <tspan x={viewBox.cx} y={(viewBox.cy || 0) + 19} className='fill-muted-foreground text-sm'>
                                Total
                              </tspan>
                            </text>
                          )
                        }
                      }}
                    />
                  </Pie>
                </PieChart>
              </ChartContainer>
            </CardContent>
            <CardFooter className='justify-between'>
              <span className='text-xl'>Carga completa</span>
              <span className='text-2xl font-medium'>68%</span>
            </CardFooter>
          </Card>
        </div>
        <Card className='shadow-none'>
          <CardContent className='grid gap-4 px-4 lg:grid-cols-5'>
            <div className='flex flex-col justify-center gap-6'>
              <span className='text-lg font-semibold'>Cierre</span>
              <span className='text-6xl'>{planPercentage}%</span>
              <span className='text-muted-foreground text-sm'>Datos cargados para cerrar el mes</span>
            </div>
            <div className='flex flex-col gap-6 text-lg md:col-span-4'>
              <span className='font-medium'>Tres bloques separados</span>
              <span className='text-muted-foreground text-wrap'>
                Lo ya pagado, lo que falta pagar y el efectivo exacto que cada integrante debe transferir al pozo.
              </span>
              <div className='grid gap-6 md:grid-cols-2'>
                <div className='flex items-center gap-2'>
                  <ChartNoAxesCombinedIcon className='size-6' />
                  <span className='text-lg font-medium'>Gastos comunes</span>
                </div>
                <div className='flex items-center gap-2'>
                  <CirclePercentIcon className='size-6' />
                  <span className='text-lg font-medium'>Compensacion</span>
                </div>
              </div>
              <ChartContainer config={planChartConfig} className='h-7.75 w-full'>
                <BarChart accessibilityLayer data={planChartData} margin={{ left: 0, right: 0 }} maxBarSize={16}>
                  <Bar
                    dataKey='sales'
                    fill='var(--primary)'
                    background={{ fill: 'color-mix(in oklab, var(--primary) 10%, transparent)', radius: 12 }}
                    radius={12}
                  />
                </BarChart>
              </ChartContainer>
            </div>
          </CardContent>
        </Card>
      </CardContent>
    </Card>
  )
}

export default SalesMetricsCard
