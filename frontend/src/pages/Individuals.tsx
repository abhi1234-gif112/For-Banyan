import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import api from '../api/client'
import IndividualCard from '../components/IndividualCard'
import { ChevronLeft, ChevronRight, Search } from 'lucide-react'
import clsx from 'clsx'

const STANCES    = ['Ally', 'Threat', 'Watchlist', 'Neutral']
const CATEGORIES = ['Journalist', 'Influencer', 'Opposition', 'Academic', 'Media House', 'Political Handle', 'Troll/Bot', 'Fan Page']

export default function Individuals() {
  const [page, setPage]         = useState(1)
  const [stance, setStance]     = useState('')
  const [category, setCategory] = useState('')
  const [riskMin, setRiskMin]   = useState(0)
  const [sort, setSort]         = useState('influence')
  const [search, setSearch]     = useState('')

  const params = new URLSearchParams({
    page: String(page),
    per_page: '24',
    sort,
    ...(stance   && { stance }),
    ...(category && { category }),
    ...(riskMin  && { risk_min: String(riskMin) }),
    ...(search   && { search }),
  })

  const { data, isLoading } = useQuery({
    queryKey: ['individuals', page, stance, category, riskMin, sort, search],
    queryFn:  () => api.get(`/individuals/list.php?${params}`).then((r) => r.data.data),
  })

  return (
    <div className="space-y-5">
      <h1 className="text-2xl font-bold text-gray-900">Individuals</h1>

      <div className="card p-4 space-y-4">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            placeholder="Search name or handle..."
            className="w-full pl-9 pr-4 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-navy"
          />
        </div>

        <div className="flex flex-wrap gap-1.5">
          <span className="text-xs font-medium text-gray-500 mr-1 self-center">Stance:</span>
          {STANCES.map((s) => (
            <button
              key={s}
              onClick={() => { setStance(stance === s ? '' : s); setPage(1) }}
              className={clsx(
                'px-3 py-1 rounded-full text-xs font-medium border transition-colors',
                stance === s ? 'bg-navy text-white border-navy' : 'border-border text-gray-600 hover:border-navy'
              )}
            >
              {s}
            </button>
          ))}
        </div>

        <div className="flex flex-wrap gap-1.5">
          <span className="text-xs font-medium text-gray-500 mr-1 self-center">Category:</span>
          {CATEGORIES.map((c) => (
            <button
              key={c}
              onClick={() => { setCategory(category === c ? '' : c); setPage(1) }}
              className={clsx(
                'px-3 py-1 rounded-full text-xs font-medium border transition-colors',
                category === c ? 'bg-navy text-white border-navy' : 'border-border text-gray-600'
              )}
            >
              {c}
            </button>
          ))}
        </div>

        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2">
            <span className="text-xs font-medium text-gray-500">Sort:</span>
            <select
              value={sort}
              onChange={(e) => setSort(e.target.value)}
              className="text-xs border border-border rounded px-2 py-1"
            >
              <option value="influence">Influence</option>
              <option value="risk">Risk Score</option>
              <option value="active">Last Active</option>
              <option value="mentions">Most Mentioned</option>
            </select>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-xs font-medium text-gray-500">Min risk:</span>
            <input
              type="range" min={0} max={100} value={riskMin}
              onChange={(e) => { setRiskMin(Number(e.target.value)); setPage(1) }}
              className="w-24"
            />
            <span className="text-xs text-gray-500">{riskMin}+</span>
          </div>
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-20 text-gray-400">Loading...</div>
      ) : (
        <>
          <div className="text-sm text-gray-500">{data?.pagination?.total} individuals</div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {(data?.individuals || []).map((ind: any) => (
              <IndividualCard key={ind.id} individual={ind} />
            ))}
          </div>

          {data?.pagination?.total_pages > 1 && (
            <div className="flex items-center justify-center gap-2">
              <button disabled={page === 1} onClick={() => setPage(page - 1)} className="p-2 border border-border rounded-lg hover:bg-gray-50 disabled:opacity-40">
                <ChevronLeft className="w-4 h-4" />
              </button>
              <span className="text-sm text-gray-600">Page {page} of {data.pagination.total_pages}</span>
              <button disabled={page === data.pagination.total_pages} onClick={() => setPage(page + 1)} className="p-2 border border-border rounded-lg hover:bg-gray-50 disabled:opacity-40">
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
