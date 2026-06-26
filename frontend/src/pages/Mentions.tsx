import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import MentionCard from '../components/MentionCard'
import ActionModal from '../components/ActionModal'
import { Search, ChevronLeft, ChevronRight } from 'lucide-react'
import clsx from 'clsx'

const PLATFORMS = ['Twitter/X', 'Facebook', 'Instagram', 'YouTube', 'News', 'Telegram', 'Reddit', 'Other']
const SENTIMENTS = ['positive', 'negative', 'neutral']
const TONES = ['informational', 'critical', 'promotional', 'satirical', 'threatening', 'emotional']

export default function Mentions() {
  const { activeClientId } = useStore()
  const [page, setPage] = useState(1)
  const [platform, setPlatform] = useState('')
  const [sentiment, setSentiment] = useState('')
  const [tone, setTone] = useState('')
  const [search, setSearch] = useState('')
  const [sort, setSort] = useState('collected_at')
  const [modal, setModal] = useState<{ open: boolean; mention: any }>({ open: false, mention: null })

  const params = new URLSearchParams({
    client_id: activeClientId || '',
    page: String(page),
    per_page: '20',
    sort,
    order: 'DESC',
    ...(platform  && { platform }),
    ...(sentiment && { sentiment }),
    ...(tone      && { tone }),
    ...(search    && { search }),
  })

  const { data, isLoading } = useQuery({
    queryKey: ['mentions', activeClientId, page, platform, sentiment, tone, search, sort],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/mentions/list.php?${params}`).then((r) => r.data.data),
  })

  const resetFilters = () => { setPlatform(''); setSentiment(''); setTone(''); setSearch(''); setPage(1) }

  if (!activeClientId) return <div className="text-gray-400 text-center mt-20">Select a client first.</div>

  return (
    <div className="space-y-5">
      <h1 className="text-2xl font-bold text-gray-900">Mentions</h1>

      {/* Filters */}
      <div className="card p-4 space-y-4">
        {/* Search */}
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            placeholder="Search content or author..."
            className="w-full pl-9 pr-4 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-navy"
          />
        </div>

        {/* Platform chips */}
        <div>
          <p className="text-xs font-medium text-gray-500 mb-2">Platform</p>
          <div className="flex flex-wrap gap-1.5">
            {PLATFORMS.map((p) => (
              <button
                key={p}
                onClick={() => { setPlatform(platform === p ? '' : p); setPage(1) }}
                className={clsx(
                  'px-3 py-1 rounded-full text-xs font-medium border transition-colors',
                  platform === p ? 'bg-navy text-white border-navy' : 'border-border text-gray-600 hover:border-navy'
                )}
              >
                {p}
              </button>
            ))}
          </div>
        </div>

        {/* Sentiment pills */}
        <div className="flex items-center gap-3">
          <p className="text-xs font-medium text-gray-500">Sentiment:</p>
          <div className="flex gap-1.5">
            {SENTIMENTS.map((s) => (
              <button
                key={s}
                onClick={() => { setSentiment(sentiment === s ? '' : s); setPage(1) }}
                className={clsx(
                  'px-3 py-1 rounded-full text-xs font-medium border transition-colors capitalize',
                  sentiment === s
                    ? s === 'positive' ? 'bg-positive text-white border-positive' : s === 'negative' ? 'bg-negative text-white border-negative' : 'bg-gray-500 text-white border-gray-500'
                    : 'border-border text-gray-600 hover:border-gray-400'
                )}
              >
                {s}
              </button>
            ))}
          </div>

          <p className="text-xs font-medium text-gray-500 ml-3">Sort:</p>
          <select
            value={sort}
            onChange={(e) => setSort(e.target.value)}
            className="text-xs border border-border rounded px-2 py-1"
          >
            <option value="collected_at">Recent</option>
            <option value="reach_estimate">Reach</option>
          </select>

          <button onClick={resetFilters} className="text-xs text-gray-400 hover:text-gray-600 ml-auto">
            Clear filters
          </button>
        </div>
      </div>

      {/* Results */}
      {isLoading ? (
        <div className="text-center py-20 text-gray-400">Loading mentions...</div>
      ) : (
        <>
          <div className="text-sm text-gray-500">
            {data?.pagination?.total?.toLocaleString()} mentions found
          </div>
          <div className="space-y-3">
            {(data?.mentions || []).map((m: any) => (
              <MentionCard
                key={m.id}
                mention={m}
                onRespond={(mention) => setModal({ open: true, mention })}
              />
            ))}
          </div>

          {/* Pagination */}
          {data?.pagination && data.pagination.total_pages > 1 && (
            <div className="flex items-center justify-center gap-2">
              <button
                disabled={page === 1}
                onClick={() => setPage(page - 1)}
                className="p-2 border border-border rounded-lg hover:bg-gray-50 disabled:opacity-40"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <span className="text-sm text-gray-600">
                Page {page} of {data.pagination.total_pages}
              </span>
              <button
                disabled={page === data.pagination.total_pages}
                onClick={() => setPage(page + 1)}
                className="p-2 border border-border rounded-lg hover:bg-gray-50 disabled:opacity-40"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          )}
        </>
      )}

      {modal.open && modal.mention && (
        <ActionModal
          isOpen={modal.open}
          onClose={() => setModal({ ...modal, open: false })}
          actionType="rapid_response"
          clientId={activeClientId}
          context={{ topic: modal.mention.content.slice(0, 200), platform: modal.mention.platform }}
        />
      )}
    </div>
  )
}
