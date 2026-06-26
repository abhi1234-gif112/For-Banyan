import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import { Plus, Trash2, ToggleLeft, ToggleRight } from 'lucide-react'
import clsx from 'clsx'

const TYPES     = ['primary', 'secondary', 'hashtag', 'negative', 'competitor']
const LANGUAGES = ['en', 'hi', 'hinglish', 'regional']

const typeColors: Record<string, string> = {
  primary:    'bg-navy/10 text-navy',
  secondary:  'bg-blue-100 text-blue-800',
  hashtag:    'bg-yellow-100 text-yellow-800',
  negative:   'bg-red-100 text-negative',
  competitor: 'bg-orange-100 text-orange-800',
}

export default function Keywords() {
  const { activeClientId } = useStore()
  const qc                 = useQueryClient()
  const [keyword, setKeyword]   = useState('')
  const [type, setType]         = useState('primary')
  const [language, setLanguage] = useState('en')
  const [bulk, setBulk]         = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['keywords', activeClientId],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/keywords/list.php?client_id=${activeClientId}`).then((r) => r.data.data.keywords),
  })

  const add = useMutation({
    mutationFn: () => api.post('/keywords/manage.php', { client_id: activeClientId, keyword, type, language }),
    onSuccess:  () => { setKeyword(''); qc.invalidateQueries({ queryKey: ['keywords'] }) },
  })
  const bulkAdd = useMutation({
    mutationFn: () => api.post('/keywords/manage.php', { client_id: activeClientId, bulk, type, language }),
    onSuccess:  () => { setBulk(''); qc.invalidateQueries({ queryKey: ['keywords'] }) },
  })
  const toggle = useMutation({
    mutationFn: ({ id, val }: { id: string; val: number }) => api.put('/keywords/manage.php', { id, is_active: val }),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['keywords'] }),
  })
  const del = useMutation({
    mutationFn: (id: string) => api.delete(`/keywords/manage.php?id=${id}`),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['keywords'] }),
  })

  if (!activeClientId) return <div className="text-gray-400 text-center mt-20">Select a client first.</div>

  const keywords: any[] = data || []

  return (
    <div className="space-y-5">
      <h1 className="text-2xl font-bold text-gray-900">Keywords</h1>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Keywords list */}
        <div className="card p-5">
          <h3 className="font-semibold text-gray-800 mb-4">{keywords.length} Keywords</h3>
          {isLoading ? (
            <p className="text-gray-400 text-sm">Loading...</p>
          ) : (
            <div className="space-y-2">
              {keywords.map((kw: any) => (
                <div key={kw.id} className="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50">
                  <span className={clsx('px-2 py-0.5 text-xs rounded-full font-medium', typeColors[kw.type] || 'bg-gray-100 text-gray-700')}>
                    {kw.type}
                  </span>
                  <span className={clsx('flex-1 text-sm', !kw.is_active && 'opacity-40 line-through')}>{kw.keyword}</span>
                  <span className="text-xs text-gray-400">{kw.language}</span>
                  <button
                    onClick={() => toggle.mutate({ id: kw.id, val: kw.is_active ? 0 : 1 })}
                    className="text-gray-400 hover:text-navy"
                  >
                    {kw.is_active ? <ToggleRight className="w-5 h-5 text-positive" /> : <ToggleLeft className="w-5 h-5" />}
                  </button>
                  <button
                    onClick={() => { if (confirm('Delete keyword?')) del.mutate(kw.id) }}
                    className="text-gray-300 hover:text-negative"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Add form */}
        <div className="space-y-4">
          <div className="card p-5">
            <h3 className="font-semibold text-gray-800 mb-4">Add Keyword</h3>
            <div className="space-y-3">
              <input
                value={keyword}
                onChange={(e) => setKeyword(e.target.value)}
                placeholder="e.g. OP Choudhary, Raigarh"
                className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-navy"
              />
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs text-gray-500 mb-1 block">Type</label>
                  <select value={type} onChange={(e) => setType(e.target.value)} className="w-full border border-border rounded-lg px-2 py-1.5 text-sm">
                    {TYPES.map((t) => <option key={t} value={t}>{t}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-xs text-gray-500 mb-1 block">Language</label>
                  <select value={language} onChange={(e) => setLanguage(e.target.value)} className="w-full border border-border rounded-lg px-2 py-1.5 text-sm">
                    {LANGUAGES.map((l) => <option key={l} value={l}>{l}</option>)}
                  </select>
                </div>
              </div>
              <button
                onClick={() => keyword.trim() && add.mutate()}
                disabled={!keyword.trim() || add.isPending}
                className="w-full btn-primary flex items-center justify-center gap-2"
              >
                <Plus className="w-4 h-4" />
                Add Keyword
              </button>
            </div>
          </div>

          <div className="card p-5">
            <h3 className="font-semibold text-gray-800 mb-4">Bulk Import</h3>
            <p className="text-xs text-gray-400 mb-2">Paste keywords separated by commas</p>
            <textarea
              value={bulk}
              onChange={(e) => setBulk(e.target.value)}
              placeholder="Raigarh, coal mining, tribal welfare, ..."
              className="w-full px-3 py-2 border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-navy h-24 resize-none"
            />
            <button
              onClick={() => bulk.trim() && bulkAdd.mutate()}
              disabled={!bulk.trim() || bulkAdd.isPending}
              className="w-full mt-2 btn-gold flex items-center justify-center gap-2"
            >
              <Plus className="w-4 h-4" />
              Import All
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
