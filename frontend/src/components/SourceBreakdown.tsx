interface SourceBreakdownProps {
  data: Array<{ platform: string; count: string | number }>
  onFilter?: (platform: string) => void
}

const platformEmoji: Record<string, string> = {
  'Twitter/X': '🐦',
  'Facebook':  '👤',
  'Instagram': '📸',
  'YouTube':   '▶️',
  'News':      '📰',
  'Telegram':  '✈️',
  'Reddit':    '🔴',
  'LinkedIn':  '💼',
  'WhatsApp':  '💬',
  'Other':     '🌐',
}

export default function SourceBreakdown({ data, onFilter }: SourceBreakdownProps) {
  const total = data.reduce((s, d) => s + Number(d.count), 0) || 1

  return (
    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
      {data.map((item) => (
        <button
          key={item.platform}
          onClick={() => onFilter?.(item.platform)}
          className="card p-3 text-left hover:shadow-md transition-shadow"
        >
          <div className="text-xl">{platformEmoji[item.platform] || '🌐'}</div>
          <div className="text-xs text-gray-500 mt-1">{item.platform}</div>
          <div className="font-bold text-sm text-gray-900">{Number(item.count).toLocaleString()}</div>
          <div className="text-xs text-gray-400">{((Number(item.count) / total) * 100).toFixed(0)}%</div>
        </button>
      ))}
    </div>
  )
}
