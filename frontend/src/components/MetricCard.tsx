import clsx from 'clsx'
import { LucideIcon } from 'lucide-react'

interface MetricCardProps {
  title: string
  value: string | number
  sub?: string
  icon: LucideIcon
  trend?: 'up' | 'down' | 'neutral'
  color?: 'navy' | 'positive' | 'negative' | 'gold' | 'watchlist'
}

export default function MetricCard({ title, value, sub, icon: Icon, trend, color = 'navy' }: MetricCardProps) {
  const colorMap = {
    navy:      'bg-navy text-white',
    positive:  'bg-positive text-white',
    negative:  'bg-negative text-white',
    gold:      'bg-gold text-navy',
    watchlist: 'bg-watchlist text-white',
  }

  return (
    <div className="card p-5 flex items-start gap-4">
      <div className={clsx('p-2.5 rounded-xl flex-shrink-0', colorMap[color])}>
        <Icon className="w-5 h-5" />
      </div>
      <div className="min-w-0">
        <div className="text-sm text-gray-500 font-medium">{title}</div>
        <div className="text-2xl font-bold text-gray-900 mt-0.5">{value}</div>
        {sub && <div className="text-xs text-gray-400 mt-0.5">{sub}</div>}
      </div>
    </div>
  )
}
