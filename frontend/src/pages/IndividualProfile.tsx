import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import api from '../api/client'
import { useStore } from '../store'
import MetricCard from '../components/MetricCard'
import MentionCard from '../components/MentionCard'
import ActionModal from '../components/ActionModal'
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'
import { ArrowLeft, Users, Shield, Eye, Zap } from 'lucide-react'
import { format, parseISO } from 'date-fns'
import clsx from 'clsx'

export default function IndividualProfile() {
  const { id }              = useParams()
  const navigate            = useNavigate()
  const { activeClientId }  = useStore()
  const [modal, setModal]   = useState<{ open: boolean; type: string; context: any }>({ open: false, type: '', context: {} })

  const { data, isLoading } = useQuery({
    queryKey: ['individual', id],
    queryFn:  () => api.get(`/individuals/profile.php?id=${id}`).then((r) => r.data.data),
    enabled:  !!id,
  })

  if (isLoading) return <div className="text-center py-20 text-gray-400">Loading profile...</div>
  if (!data) return <div className="text-center py-20 text-negative">Individual not found.</div>

  const ind       = data.individual
  const initials  = (ind.name || ind.handle || '?').split(' ').map((w: string) => w[0]).join('').toUpperCase().slice(0, 2)

  const stanceClass =
    ind.stance === 'Ally'      ? 'badge-ally' :
    ind.stance === 'Threat'    ? 'badge-threat' :
    ind.stance === 'Watchlist' ? 'badge-watchlist' :
    'badge-neutral'

  const sparklineData = (data.sparkline || []).map((d: any) => ({
    ...d,
    label: format(parseISO(d.date), 'MMM d'),
  }))

  const suggestions = ind.suggestions || []

  return (
    <div className="space-y-6 max-w-4xl">
      {/* Back */}
      <button onClick={() => navigate('/individuals')} className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <ArrowLeft className="w-4 h-4" /> Back to Individuals
      </button>

      {/* Header */}
      <div className="card p-6">
        <div className="flex items-start gap-4">
          <div className="w-16 h-16 rounded-2xl bg-navy text-white flex items-center justify-center text-xl font-bold flex-shrink-0">
            {initials}
          </div>
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-3 flex-wrap">
              <h2 className="text-xl font-bold text-gray-900">{ind.name || ind.handle}</h2>
              {ind.is_verified && <span className="text-blue-500">✓ Verified</span>}
              <span className={stanceClass}>{ind.stance}</span>
            </div>
            <div className="text-gray-500 mt-0.5">{ind.handle}</div>
            <div className="flex items-center gap-2 mt-2 flex-wrap">
              <span className="text-xs px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full">{ind.category}</span>
              {ind.is_political && <span className="text-xs px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full">Political</span>}
              {ind.party_affiliation && <span className="text-xs px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full">{ind.party_affiliation}</span>}
              {(ind.platforms || []).map((p: string) => (
                <span key={p} className="text-xs px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full">{p}</span>
              ))}
            </div>
            {ind.stance_reasoning && (
              <p className="text-xs text-gray-400 mt-2">{ind.stance_reasoning}</p>
            )}
          </div>
        </div>
      </div>

      {/* Metrics */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <MetricCard title="Influence Score" value={`${ind.influence_score}/100`} icon={Users} color="navy" />
        <MetricCard title="Risk Score" value={`${ind.risk_score}/100`} icon={Shield} color={ind.risk_score > 70 ? 'negative' : ind.risk_score > 40 ? 'watchlist' : 'positive'} />
        <MetricCard title="Est. Reach" value={ind.reach_estimate > 999999 ? `${(ind.reach_estimate / 1000000).toFixed(1)}M` : `${Math.round(ind.reach_estimate / 1000)}K`} icon={Eye} color="gold" />
        <MetricCard title="30d Mentions" value={ind.mention_count_30d} sub="about your client" icon={Zap} color="navy" />
      </div>

      {/* Sparkline */}
      {sparklineData.length > 0 && (
        <div className="card p-5">
          <h3 className="font-semibold text-gray-800 mb-4">30-Day Mention Activity</h3>
          <ResponsiveContainer width="100%" height={150}>
            <LineChart data={sparklineData}>
              <XAxis dataKey="label" tick={{ fontSize: 10, fill: '#9CA3AF' }} tickLine={false} axisLine={false} />
              <YAxis tick={{ fontSize: 10, fill: '#9CA3AF' }} tickLine={false} axisLine={false} />
              <Tooltip contentStyle={{ fontSize: 11 }} />
              <Line type="monotone" dataKey="positive" stroke="#1D7A4A" strokeWidth={2} dot={false} />
              <Line type="monotone" dataKey="negative" stroke="#C0392B" strokeWidth={2} dot={false} />
              <Line type="monotone" dataKey="total"    stroke="#1B2A4A" strokeWidth={1.5} dot={false} strokeDasharray="3 3" />
            </LineChart>
          </ResponsiveContainer>
        </div>
      )}

      {/* Topics */}
      {(ind.topics || []).length > 0 && (
        <div className="card p-5">
          <h3 className="font-semibold text-gray-800 mb-3">Topic Coverage</h3>
          <div className="flex flex-wrap gap-2">
            {ind.topics.map((t: string) => (
              <span key={t} className="px-3 py-1 bg-navy/10 text-navy text-xs rounded-full font-medium">{t}</span>
            ))}
          </div>
        </div>
      )}

      {/* AI Suggestions */}
      {suggestions.length > 0 && (
        <div className="card p-5">
          <h3 className="font-semibold text-gray-800 mb-4">NAZAR Recommendations</h3>
          <div className="space-y-3">
            {suggestions.map((s: any, i: number) => (
              <div key={i} className="border border-border rounded-xl p-4">
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-2">
                    <span className={clsx('text-xs px-2 py-0.5 rounded-full font-medium',
                      s.priority === 'high' ? 'bg-red-100 text-negative' :
                      s.priority === 'medium' ? 'bg-orange-100 text-orange-700' :
                      'bg-gray-100 text-gray-600'
                    )}>{s.priority}</span>
                    <span className="text-xs text-gray-400 capitalize">{s.action_type}</span>
                  </div>
                  {activeClientId && s.next_step && (
                    <button
                      onClick={() => setModal({ open: true, type: s.action_type === 'counter' ? 'counter_brief' : s.action_type === 'outreach' ? 'outreach_message' : 'individual_strategy', context: { individual_name: ind.name, individual_handle: ind.handle, individual_category: ind.category, risk_score: ind.risk_score } })}
                      className="text-xs px-2.5 py-1 bg-navy text-white rounded-lg hover:bg-opacity-90"
                    >
                      Generate
                    </button>
                  )}
                </div>
                <h4 className="font-semibold text-sm text-gray-900 mt-2">{s.title}</h4>
                <p className="text-xs text-gray-500 mt-0.5">{s.detail}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Recent mentions */}
      <div className="card p-5">
        <h3 className="font-semibold text-gray-800 mb-4">Recent Mentions</h3>
        <div className="space-y-3">
          {(data.recent_mentions || []).map((m: any) => (
            <MentionCard key={m.id} mention={m} />
          ))}
        </div>
      </div>

      {modal.open && activeClientId && (
        <ActionModal
          isOpen={modal.open}
          onClose={() => setModal({ ...modal, open: false })}
          actionType={modal.type}
          clientId={activeClientId}
          context={modal.context}
        />
      )}
    </div>
  )
}
