import { formatDistanceToNow } from 'date-fns'
import { Eye, Share2, Heart, MessageCircle, Zap, Flag } from 'lucide-react'
import clsx from 'clsx'

const platformColors: Record<string, string> = {
  'Twitter/X':  'bg-blue-100 text-blue-800',
  'Facebook':   'bg-blue-50 text-blue-700',
  'Instagram':  'bg-pink-100 text-pink-800',
  'YouTube':    'bg-red-100 text-red-700',
  'News':       'bg-gray-100 text-gray-700',
  'Telegram':   'bg-cyan-100 text-cyan-800',
  'Reddit':     'bg-orange-100 text-orange-800',
  'LinkedIn':   'bg-blue-200 text-blue-900',
  'WhatsApp':   'bg-green-100 text-green-800',
  'Other':      'bg-gray-100 text-gray-600',
}

interface MentionCardProps {
  mention: any
  onRespond?: (mention: any) => void
}

export default function MentionCard({ mention, onRespond }: MentionCardProps) {
  const sentimentClass =
    mention.sentiment === 'positive' ? 'badge-positive' :
    mention.sentiment === 'negative' ? 'badge-negative' :
    'badge-neutral'

  const platformClass = platformColors[mention.platform] ?? 'bg-gray-100 text-gray-700'

  return (
    <div className={clsx(
      'card p-4 border-l-4',
      mention.sentiment === 'positive' ? 'border-l-positive' :
      mention.sentiment === 'negative' ? 'border-l-negative' :
      'border-l-gray-200'
    )}>
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-center gap-2 flex-wrap">
          <span className={clsx('px-2 py-0.5 rounded text-xs font-medium', platformClass)}>
            {mention.platform}
          </span>
          <span className="text-sm font-medium text-gray-800">{mention.author_handle}</span>
          {mention.is_verified_account ? <span className="text-blue-500 text-xs">✓</span> : null}
          {mention.is_viral ? <span className="text-orange-500 text-xs font-semibold">🔥 Viral</span> : null}
        </div>
        <div className="flex items-center gap-2 flex-shrink-0">
          <span className={sentimentClass}>{mention.sentiment}</span>
          {mention.tone && (
            <span className="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">{mention.tone}</span>
          )}
        </div>
      </div>

      <p className="mt-2 text-sm text-gray-700 line-clamp-3">{mention.content}</p>

      {mention.summary_en && (
        <p className="mt-1 text-xs text-gray-400 italic">{mention.summary_en}</p>
      )}

      <div className="mt-3 flex items-center justify-between">
        <div className="flex items-center gap-3 text-xs text-gray-400">
          {mention.engagement?.likes != null && (
            <span className="flex items-center gap-1"><Heart className="w-3 h-3" />{mention.engagement.likes?.toLocaleString()}</span>
          )}
          {mention.engagement?.shares != null && (
            <span className="flex items-center gap-1"><Share2 className="w-3 h-3" />{mention.engagement.shares?.toLocaleString()}</span>
          )}
          {mention.reach_estimate > 0 && (
            <span className="flex items-center gap-1"><Eye className="w-3 h-3" />{(mention.reach_estimate / 1000).toFixed(0)}K reach</span>
          )}
          <span>{formatDistanceToNow(new Date(mention.collected_at), { addSuffix: true })}</span>
        </div>

        {onRespond && (
          <button
            onClick={() => onRespond(mention)}
            className="flex items-center gap-1 px-3 py-1 text-xs bg-navy text-white rounded-lg hover:bg-opacity-90"
          >
            <Zap className="w-3 h-3" />
            Respond
          </button>
        )}
      </div>
    </div>
  )
}
