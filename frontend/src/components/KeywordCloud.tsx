interface KeywordCloudProps {
  keywords: Array<{ keyword: string; type: string; is_active: boolean }>
  onFilter?: (kw: string) => void
}

const typeColors: Record<string, string> = {
  primary:    'bg-navy text-white',
  secondary:  'bg-blue-100 text-blue-800',
  hashtag:    'bg-gold/20 text-yellow-800',
  negative:   'bg-red-100 text-negative',
  competitor: 'bg-orange-100 text-orange-800',
}

export default function KeywordCloud({ keywords, onFilter }: KeywordCloudProps) {
  const active = keywords.filter((k) => k.is_active)

  return (
    <div className="flex flex-wrap gap-2">
      {active.map((kw, i) => (
        <button
          key={i}
          onClick={() => onFilter?.(kw.keyword)}
          className={`px-3 py-1 rounded-full text-xs font-medium ${typeColors[kw.type] || 'bg-gray-100 text-gray-700'} hover:opacity-80 transition-opacity`}
        >
          {kw.keyword}
        </button>
      ))}
    </div>
  )
}
