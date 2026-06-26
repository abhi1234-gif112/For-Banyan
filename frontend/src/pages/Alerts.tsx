import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import AlertCard from '../components/AlertCard'
import ActionModal from '../components/ActionModal'

const SEVERITIES = ['critical', 'high', 'medium', 'low'] as const

export default function Alerts() {
  const { activeClientId } = useStore()
  const qc                 = useQueryClient()
  const [severity, setSeverity] = useState('')
  const [modal, setModal]  = useState<{ open: boolean; type: string; context: any }>({ open: false, type: '', context: {} })

  const params = new URLSearchParams({
    client_id: activeClientId || '',
    per_page:  '50',
    ...(severity && { severity }),
  })

  const { data, isLoading } = useQuery({
    queryKey: ['alerts', activeClientId, severity],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/alerts/list.php?${params}`).then((r) => r.data.data),
    refetchInterval: 60 * 1000,
  })

  const markRead = useMutation({
    mutationFn: (id: string) => api.post('/alerts/update.php', { id, is_read: 1 }),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['alerts'] }),
  })
  const markActioned = useMutation({
    mutationFn: (id: string) => api.post('/alerts/update.php', { id, is_actioned: 1, is_read: 1 }),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['alerts'] }),
  })

  const counts = data?.severity_counts || { critical: 0, high: 0, medium: 0, low: 0 }
  const alerts = data?.alerts || []

  if (!activeClientId) return <div className="text-gray-400 text-center mt-20">Select a client first.</div>

  return (
    <div className="space-y-5">
      <h1 className="text-2xl font-bold text-gray-900">Alert Centre</h1>

      {/* Severity tabs */}
      <div className="flex gap-2 flex-wrap">
        <button
          onClick={() => setSeverity('')}
          className={`px-4 py-2 rounded-xl text-sm font-medium border ${!severity ? 'bg-navy text-white border-navy' : 'border-border text-gray-600 hover:border-navy'}`}
        >
          All ({(Object.values(counts) as number[]).reduce((a, b) => a + b, 0)})
        </button>
        {SEVERITIES.map((s) => (
          <button
            key={s}
            onClick={() => setSeverity(severity === s ? '' : s)}
            className={`px-4 py-2 rounded-xl text-sm font-medium border capitalize ${severity === s ? 'bg-navy text-white border-navy' : 'border-border text-gray-600 hover:border-navy'}`}
          >
            {s} ({counts[s] || 0})
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="text-center py-20 text-gray-400">Loading alerts...</div>
      ) : alerts.length === 0 ? (
        <div className="card p-12 text-center text-gray-400">
          <div className="text-4xl mb-3">✅</div>
          <p className="font-medium">No alerts{severity ? ` with ${severity} severity` : ''}</p>
        </div>
      ) : (
        <div className="space-y-3">
          {alerts.map((a: any) => (
            <AlertCard
              key={a.id}
              alert={a}
              onMarkRead={(id) => markRead.mutate(id)}
              onMarkActioned={(id) => markActioned.mutate(id)}
              onRespond={(al) => setModal({
                open: true,
                type: 'counter_brief',
                context: { alert_id: al.id, attacker: al.trigger_data?.author || 'unknown', claim: al.title },
              })}
            />
          ))}
        </div>
      )}

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
