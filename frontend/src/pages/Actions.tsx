import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import ActionModal from '../components/ActionModal'
import { formatDistanceToNow } from 'date-fns'
import { Zap, ChevronDown, ChevronUp, Copy, CheckCheck } from 'lucide-react'
import clsx from 'clsx'

const ACTION_TYPES = [
  { value: 'counter_brief',        label: 'Counter Brief'       },
  { value: 'rapid_response',       label: 'Rapid Response'      },
  { value: 'press_kit',            label: 'Press Kit'           },
  { value: 'outreach_message',     label: 'Outreach'            },
  { value: 'whatsapp_forward',     label: 'WhatsApp Forward'    },
  { value: 'keyword_blocking_list',label: 'Keyword List'        },
  { value: 'narrative_brief',      label: 'Narrative Brief'     },
  { value: 'individual_strategy',  label: 'Individual Strategy' },
  { value: 'daily_report',         label: 'Daily Report'        },
  { value: 'weekly_digest',        label: 'Weekly Digest'       },
]

function ActionRow({ action }: { action: any }) {
  const [expanded, setExpanded] = useState(false)
  const [copied, setCopied] = useState(false)

  const label = ACTION_TYPES.find((t) => t.value === action.action_type)?.label || action.action_type

  const handleCopy = () => {
    // We need to fetch full content
    api.get(`/actions/list.php?client_id=${action.client_id}`).then(() => {
      navigator.clipboard.writeText(action.preview || '')
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    })
  }

  return (
    <div className="card p-4">
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-center gap-3 flex-wrap">
          <span className="px-2.5 py-0.5 bg-navy/10 text-navy text-xs font-medium rounded-full">{label}</span>
          <span className={clsx('text-xs px-2 py-0.5 rounded-full',
            action.status === 'completed' ? 'bg-green-100 text-positive' : 'bg-red-100 text-negative'
          )}>{action.status}</span>
          <span className="text-xs text-gray-400">
            {formatDistanceToNow(new Date(action.created_at), { addSuffix: true })}
          </span>
        </div>
        <div className="flex items-center gap-1.5">
          <button onClick={handleCopy} className="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400" title="Copy preview">
            {copied ? <CheckCheck className="w-3.5 h-3.5 text-positive" /> : <Copy className="w-3.5 h-3.5" />}
          </button>
          <button onClick={() => setExpanded(!expanded)} className="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400">
            {expanded ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
          </button>
        </div>
      </div>

      <p className="mt-2 text-sm text-gray-600 line-clamp-2">{action.preview}</p>

      {expanded && action.preview && (
        <div className="mt-3 border-t border-border pt-3">
          <pre className="whitespace-pre-wrap text-xs text-gray-700 font-sans leading-relaxed">{action.preview}
{action.full_length > 300 && <span className="text-gray-400"> …({action.full_length - 300} more chars — regenerate to view full content)</span>}
          </pre>
        </div>
      )}
    </div>
  )
}

export default function Actions() {
  const { activeClientId } = useStore()
  const [filterType, setFilterType] = useState('')
  const [newModal, setNewModal] = useState<{ open: boolean; type: string }>({ open: false, type: '' })

  const params = new URLSearchParams({
    client_id: activeClientId || '',
    per_page:  '30',
    ...(filterType && { action_type: filterType }),
  })

  const { data, isLoading } = useQuery({
    queryKey: ['actions', activeClientId, filterType],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/actions/list.php?${params}`).then((r) => r.data.data),
  })

  if (!activeClientId) return <div className="text-gray-400 text-center mt-20">Select a client first.</div>

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Actions</h1>
        <button
          onClick={() => setNewModal({ open: true, type: 'daily_report' })}
          className="btn-primary flex items-center gap-2"
        >
          <Zap className="w-4 h-4" /> Generate New
        </button>
      </div>

      {/* Filter */}
      <div className="flex flex-wrap gap-2">
        <button
          onClick={() => setFilterType('')}
          className={`px-3 py-1.5 text-xs rounded-lg border ${!filterType ? 'bg-navy text-white border-navy' : 'border-border text-gray-600'}`}
        >
          All
        </button>
        {ACTION_TYPES.map((t) => (
          <button
            key={t.value}
            onClick={() => setFilterType(filterType === t.value ? '' : t.value)}
            className={`px-3 py-1.5 text-xs rounded-lg border ${filterType === t.value ? 'bg-navy text-white border-navy' : 'border-border text-gray-600'}`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="text-center py-20 text-gray-400">Loading...</div>
      ) : (
        <div className="space-y-3">
          {(data?.actions || []).map((a: any) => <ActionRow key={a.id} action={a} />)}
          {data?.actions?.length === 0 && (
            <div className="card p-12 text-center text-gray-400">
              <Zap className="w-8 h-8 mx-auto mb-3 opacity-30" />
              <p>No actions yet. Generate your first one!</p>
            </div>
          )}
        </div>
      )}

      {/* Quick-generate modal */}
      <div className="card p-5">
        <h3 className="font-semibold text-gray-800 mb-3">Quick Generate</h3>
        <div className="grid grid-cols-2 sm:grid-cols-5 gap-2">
          {ACTION_TYPES.map((t) => (
            <button
              key={t.value}
              onClick={() => setNewModal({ open: true, type: t.value })}
              className="px-3 py-2 text-xs border border-border rounded-xl hover:bg-navy hover:text-white hover:border-navy transition-colors text-center"
            >
              {t.label}
            </button>
          ))}
        </div>
      </div>

      {newModal.open && activeClientId && (
        <ActionModal
          isOpen={newModal.open}
          onClose={() => setNewModal({ ...newModal, open: false })}
          actionType={newModal.type}
          clientId={activeClientId}
          context={{}}
        />
      )}
    </div>
  )
}
