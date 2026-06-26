import { useNavigate } from 'react-router-dom'
import clsx from 'clsx'

interface IndividualCardProps {
  individual: any
}

export default function IndividualCard({ individual: ind }: IndividualCardProps) {
  const navigate = useNavigate()
  const initials = (ind.name || ind.handle || '?')
    .split(' ').map((w: string) => w[0]).join('').toUpperCase().slice(0, 2)

  const stanceClass =
    ind.stance === 'Ally'      ? 'badge-ally' :
    ind.stance === 'Threat'    ? 'badge-threat' :
    ind.stance === 'Watchlist' ? 'badge-watchlist' :
    'badge-neutral'

  return (
    <div
      className="card p-4 cursor-pointer hover:shadow-md transition-shadow"
      onClick={() => navigate(`/individuals/${ind.id}`)}
    >
      <div className="flex items-start gap-3">
        <div className="w-10 h-10 rounded-full bg-navy text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
          {initials}
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="font-semibold text-sm text-gray-900 truncate">{ind.name || ind.handle}</span>
            {ind.is_verified && <span className="text-blue-500 text-xs">✓</span>}
            <span className={stanceClass}>{ind.stance}</span>
          </div>
          <div className="text-xs text-gray-400 mt-0.5">{ind.handle}</div>
          <div className="mt-1">
            <span className="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded">{ind.category}</span>
          </div>
        </div>
      </div>

      <div className="mt-3 space-y-2">
        <div>
          <div className="flex justify-between text-xs text-gray-500 mb-1">
            <span>Influence</span><span>{ind.influence_score}/100</span>
          </div>
          <div className="h-1.5 bg-gray-100 rounded-full">
            <div
              className="h-1.5 bg-navy rounded-full"
              style={{ width: `${ind.influence_score}%` }}
            />
          </div>
        </div>
        <div>
          <div className="flex justify-between text-xs text-gray-500 mb-1">
            <span>Risk</span><span>{ind.risk_score}/100</span>
          </div>
          <div className="h-1.5 bg-gray-100 rounded-full">
            <div
              className={clsx('h-1.5 rounded-full', ind.risk_score > 70 ? 'bg-negative' : ind.risk_score > 40 ? 'bg-watchlist' : 'bg-positive')}
              style={{ width: `${ind.risk_score}%` }}
            />
          </div>
        </div>
      </div>

      {(ind.platforms || []).slice(0, 3).map((p: string) => (
        <span key={p} className="inline-block mt-2 mr-1 text-xs px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded">
          {p}
        </span>
      ))}
    </div>
  )
}
