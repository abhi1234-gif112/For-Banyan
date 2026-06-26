import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts'
import { format, parseISO } from 'date-fns'

interface SentimentChartProps {
  data: Array<{ date: string; positive: number; negative: number; neutral: number; total: number }>
}

export default function SentimentChart({ data }: SentimentChartProps) {
  const formatted = data.map((d) => ({
    ...d,
    label: format(parseISO(d.date), 'MMM d'),
  }))

  return (
    <ResponsiveContainer width="100%" height={220}>
      <LineChart data={formatted} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" />
        <XAxis dataKey="label" tick={{ fontSize: 11, fill: '#9CA3AF' }} tickLine={false} axisLine={false} />
        <YAxis tick={{ fontSize: 11, fill: '#9CA3AF' }} tickLine={false} axisLine={false} />
        <Tooltip
          contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #E2E8F0' }}
          labelStyle={{ fontWeight: 600 }}
        />
        <Legend wrapperStyle={{ fontSize: 12 }} />
        <Line type="monotone" dataKey="positive" stroke="#1D7A4A" strokeWidth={2} dot={false} name="Positive" />
        <Line type="monotone" dataKey="negative" stroke="#C0392B" strokeWidth={2} dot={false} name="Negative" />
        <Line type="monotone" dataKey="neutral"  stroke="#9CA3AF" strokeWidth={2} dot={false} name="Neutral" />
      </LineChart>
    </ResponsiveContainer>
  )
}
