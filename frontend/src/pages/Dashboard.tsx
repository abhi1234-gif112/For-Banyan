import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import MetricCard from '../components/MetricCard'
import SentimentChart from '../components/SentimentChart'
import SourceBreakdown from '../components/SourceBreakdown'
import MentionCard from '../components/MentionCard'
import AlertCard from '../components/AlertCard'
import ActionModal from '../components/ActionModal'
import { MessageSquare, Eye, Bell, TrendingUp } from 'lucide-react'
import { useNavigate } from 'react-router-dom'

export default function Dashboard() {
  const { activeClientId } = useStore()
  const navigate = useNavigate()
  const [modal, setModal] = useState<{ open: boolean; type: string; context: any }>({
    open: false, type: '', context: {}
  })
  const [dateRange, setDateRange] = useState('7')

  const dateFrom = new Date(Date.now() - parseInt(dateRange) * 86400000).toISOString().split('T')[0]
  const dateTo   = new Date().toISOString().split('T')[0]

  const { data: stats } = useQuery({
    queryKey: ['stats', activeClientId, dateRange],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/mentions/stats.php?client_id=${activeClientId}&date_from=${dateFrom}&date_to=${dateTo}`)
                      .then((r) => r.data.data),
    refetchInterval: 2 * 60 * 1000,
  })

  const { data: mentionsData } = useQuery({
    queryKey: ['mentions-latest', activeClientId],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/mentions/list.php?client_id=${activeClientId}&per_page=10&sort=collected_at&order=DESC`)
                      .then((r) => r.data.data),
  })

  const { data: alertsData } = useQuery({
    queryKey: ['alerts-latest', activeClientId],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/alerts/list.php?client_id=${activeClientId}&per_page=4&is_read=0`)
                      .then((r) => r.data.data),
  })

  const sentimentTotals = stats?.sentiment_totals || {}
  const total = (sentimentTotals.positive || 0) + (sentimentTotals.negative || 0) + (sentimentTotals.neutral || 0)
  const positivePct = total ? Math.round((sentimentTotals.positive || 0) / total * 100) : 0

  if (!activeClientId) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="text-4xl mb-3">👆</div>
          <h3 className="text-lg font-semibold text-gray-700">Select a client to begin</h3>
          <p className="text-sm text-gray-400 mt-1">Use the client selector in the top bar</p>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <div className="flex items-center gap-2">
          {['7', '30'].map((d) => (
            <button
              key={d}
              onClick={() => setDateRange(d)}
              className={`px-3 py-1.5 text-sm rounded-lg ${dateRange === d ? 'bg-navy text-white' : 'btn-ghost'}`}
            >
              {d}d
            </button>
          ))}
        </div>
      </div>

      {/* Metric Cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <MetricCard
          title="Total Mentions"
          value={(stats?.total_mentions || 0).toLocaleString()}
          sub={`${dateRange}-day period`}
          icon={MessageSquare}
          color="navy"
        />
        <MetricCard
          title="Total Reach"
          value={stats?.total_reach > 999999 ? `${(stats.total_reach / 1000000).toFixed(1)}M` : `${Math.round((stats?.total_reach || 0) / 1000)}K`}
          sub="estimated impressions"
          icon={Eye}
          color="gold"
        />
        <MetricCard
          title="Positive Sentiment"
          value={`${positivePct}%`}
          sub={`${sentimentTotals.positive || 0} positive mentions`}
          icon={TrendingUp}
          color="positive"
        />
        <MetricCard
          title="Active Alerts"
          value={stats?.active_alerts || 0}
          sub="unread alerts"
          icon={Bell}
          color={stats?.active_alerts > 0 ? 'negative' : 'navy'}
        />
      </div>

      {/* Charts + Alerts row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Sentiment Chart */}
        <div className="card p-5 lg:col-span-2">
          <h3 className="font-semibold text-gray-800 mb-4">Sentiment Trend</h3>
          <SentimentChart data={stats?.by_day || []} />
        </div>

        {/* Alerts panel */}
        <div className="card p-5">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-gray-800">Live Alerts</h3>
            <button onClick={() => navigate('/alerts')} className="text-xs text-navy hover:underline">View all</button>
          </div>
          <div className="space-y-3">
            {(alertsData?.alerts || []).length === 0 && (
              <p className="text-sm text-gray-400 py-4 text-center">No unread alerts</p>
            )}
            {(alertsData?.alerts || []).map((a: any) => (
              <AlertCard
                key={a.id}
                alert={a}
                onRespond={(al) => setModal({ open: true, type: 'counter_brief', context: { alert_id: al.id, attacker: al.trigger_data?.author, claim: al.title } })}
              />
            ))}
          </div>
        </div>
      </div>

      {/* Source Breakdown */}
      {stats?.by_platform?.length > 0 && (
        <div className="card p-5">
          <h3 className="font-semibold text-gray-800 mb-4">Source Breakdown</h3>
          <SourceBreakdown data={stats.by_platform} />
        </div>
      )}

      {/* Latest Mentions */}
      <div className="card p-5">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-gray-800">Latest Mentions</h3>
          <button onClick={() => navigate('/mentions')} className="text-xs text-navy hover:underline">View all</button>
        </div>
        <div className="space-y-3">
          {(mentionsData?.mentions || []).map((m: any) => (
            <MentionCard
              key={m.id}
              mention={m}
              onRespond={(mention) => setModal({
                open: true,
                type: 'rapid_response',
                context: { topic: mention.content.slice(0, 200), platform: mention.platform },
              })}
            />
          ))}
        </div>
      </div>

      <ActionModal
        isOpen={modal.open}
        onClose={() => setModal({ ...modal, open: false })}
        actionType={modal.type}
        clientId={activeClientId}
        context={modal.context}
      />
    </div>
  )
}
