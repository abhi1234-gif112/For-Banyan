import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import { Plus, Edit2 } from 'lucide-react'

const emptyForm = {
  name: '', handle: '', role: '', party: '', constituency: '', state: '',
  language_scope: [] as string[], platforms: [] as string[],
}

export default function Clients() {
  const { user }    = useStore()
  const qc          = useQueryClient()
  const [form, setForm] = useState(emptyForm)
  const [editing, setEditing] = useState<string | null>(null)

  if (user?.role !== 'super_admin') {
    return <div className="text-gray-400 text-center mt-20">Super admin access required.</div>
  }

  const { data, isLoading } = useQuery({
    queryKey: ['clients-admin'],
    queryFn:  () => api.get('/clients/list.php').then((r) => r.data.data.clients),
  })

  const createClient = useMutation({
    mutationFn: () => api.post('/clients/manage.php', form),
    onSuccess:  () => { setForm(emptyForm); qc.invalidateQueries({ queryKey: ['clients-admin'] }) },
  })

  const updateClient = useMutation({
    mutationFn: () => api.put('/clients/manage.php', { id: editing, ...form }),
    onSuccess:  () => { setEditing(null); setForm(emptyForm); qc.invalidateQueries({ queryKey: ['clients-admin'] }) },
  })

  const startEdit = (c: any) => {
    setEditing(c.id)
    setForm({
      name: c.name, handle: c.handle || '', role: c.role || '',
      party: c.party || '', constituency: c.constituency || '', state: c.state || '',
      language_scope: c.language_scope || [], platforms: c.platforms || [],
    })
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Clients</h1>

      {/* Add / Edit form */}
      <div className="card p-5">
        <h3 className="font-semibold text-gray-800 mb-4">{editing ? 'Edit Client' : 'Add New Client'}</h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {[
            ['name', 'Full Name *'],
            ['handle', '@Handle'],
            ['role', 'Role (e.g. Finance Minister)'],
            ['party', 'Party (e.g. BJP)'],
            ['constituency', 'Constituency'],
            ['state', 'State'],
          ].map(([key, label]) => (
            <div key={key}>
              <label className="text-xs text-gray-500 mb-1 block">{label}</label>
              <input
                value={(form as any)[key]}
                onChange={(e) => setForm({ ...form, [key]: e.target.value })}
                className="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-navy"
              />
            </div>
          ))}
        </div>
        <div className="flex gap-2 mt-4">
          <button
            onClick={() => (editing ? updateClient.mutate() : createClient.mutate())}
            disabled={!form.name || createClient.isPending || updateClient.isPending}
            className="btn-primary flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            {editing ? 'Update Client' : 'Add Client'}
          </button>
          {editing && (
            <button onClick={() => { setEditing(null); setForm(emptyForm) }} className="btn-ghost">
              Cancel
            </button>
          )}
        </div>
      </div>

      {/* Clients table */}
      <div className="card overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-border">
            <tr>
              {['Name', 'Role', 'Party', 'State', 'Constituency', 'Status', 'Actions'].map((h) => (
                <th key={h} className="text-left px-4 py-3 text-xs font-semibold text-gray-500">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr><td colSpan={7} className="text-center py-8 text-gray-400">Loading...</td></tr>
            ) : (data || []).map((c: any) => (
              <tr key={c.id} className="border-b border-border hover:bg-gray-50">
                <td className="px-4 py-3 font-medium">{c.name}</td>
                <td className="px-4 py-3 text-gray-600">{c.role}</td>
                <td className="px-4 py-3 text-gray-600">{c.party}</td>
                <td className="px-4 py-3 text-gray-600">{c.state}</td>
                <td className="px-4 py-3 text-gray-600">{c.constituency}</td>
                <td className="px-4 py-3">
                  <span className={`badge-${c.is_active ? 'positive' : 'neutral'}`}>
                    {c.is_active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <button onClick={() => startEdit(c)} className="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-navy">
                    <Edit2 className="w-4 h-4" />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
