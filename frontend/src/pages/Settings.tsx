import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import { CheckCircle, XCircle, Loader2, Plus, Edit2, Key } from 'lucide-react'
import { formatDistanceToNow } from 'date-fns'

function HealthDot({ ok }: { ok: boolean | null }) {
  if (ok === null) return <Loader2 className="w-4 h-4 animate-spin text-gray-400" />
  return ok
    ? <CheckCircle className="w-4 h-4 text-positive" />
    : <XCircle className="w-4 h-4 text-negative" />
}

const emptyUser = { name: '', email: '', password: '', role: 'analyst', assigned_client_ids: [] as string[] }

export default function Settings() {
  const { user } = useStore()
  const qc = useQueryClient()
  const isSuperAdmin = user?.role === 'super_admin'

  const [pwForm, setPwForm] = useState({ current: '', next: '', confirm: '' })
  const [pwMsg, setPwMsg]   = useState('')
  const [uForm, setUForm]   = useState(emptyUser)
  const [editUid, setEditUid] = useState<string | null>(null)

  const { data: health } = useQuery({
    queryKey: ['health'],
    queryFn: () => api.get('/health.php').then((r) => r.data),
    refetchInterval: 30000,
  })

  const { data: cronLogs } = useQuery({
    queryKey: ['cron-logs'],
    enabled: isSuperAdmin,
    queryFn: () => api.get('/settings/cron_status.php').then((r) => r.data.data),
  })

  const { data: users } = useQuery({
    queryKey: ['users-list'],
    enabled: isSuperAdmin,
    queryFn: () => api.get('/settings/users.php').then((r) => r.data.data.users),
  })

  const { data: clients } = useQuery({
    queryKey: ['clients-admin'],
    enabled: isSuperAdmin,
    queryFn: () => api.get('/clients/list.php').then((r) => r.data.data.clients),
  })

  const saveUser = useMutation({
    mutationFn: () => editUid
      ? api.put('/settings/users.php', { id: editUid, ...uForm })
      : api.post('/settings/users.php', uForm),
    onSuccess: () => {
      setUForm(emptyUser); setEditUid(null)
      qc.invalidateQueries({ queryKey: ['users-list'] })
    },
  })

  const changePw = useMutation({
    mutationFn: () => api.post('/settings/change_password.php', { current_password: pwForm.current, new_password: pwForm.next }),
    onSuccess: () => { setPwForm({ current: '', next: '', confirm: '' }); setPwMsg('Password changed successfully.') },
    onError: (e: any) => setPwMsg(e.response?.data?.message || 'Error changing password.'),
  })

  const startEditUser = (u: any) => {
    setEditUid(u.id)
    setUForm({ name: u.name, email: u.email, password: '', role: u.role, assigned_client_ids: u.assigned_client_ids || [] })
  }

  const toggleClientAssign = (cid: string) => {
    setUForm((f) => ({
      ...f,
      assigned_client_ids: f.assigned_client_ids.includes(cid)
        ? f.assigned_client_ids.filter((x) => x !== cid)
        : [...f.assigned_client_ids, cid],
    }))
  }

  const cronJobs = [
    { name: 'collect_rss_news',    label: 'RSS News',         freq: '*/15 * * * *' },
    { name: 'collect_twitter',     label: 'Twitter',          freq: '*/15 * * * *' },
    { name: 'collect_youtube',     label: 'YouTube',          freq: '*/30 * * * *' },
    { name: 'collect_facebook',    label: 'Facebook',         freq: '*/30 * * * *' },
    { name: 'collect_instagram',   label: 'Instagram',        freq: '*/30 * * * *' },
    { name: 'collect_telegram',    label: 'Telegram',         freq: '*/5 * * * *'  },
    { name: 'collect_reddit',      label: 'Reddit',           freq: '*/20 * * * *' },
    { name: 'enrich_mentions',     label: 'AI Enrich',        freq: '*/10 * * * *' },
    { name: 'detect_alerts',       label: 'Alert Detection',  freq: '*/5 * * * *'  },
    { name: 'score_individuals',   label: 'Score Individuals',freq: '0 * * * *'    },
    { name: 'daily_report',        label: 'Daily Report',     freq: '0 7 * * *'    },
  ]

  const logMap: Record<string, any> = {}
  if (cronLogs?.logs) {
    for (const l of cronLogs.logs) logMap[l.script_name] = l
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <h1 className="text-2xl font-bold text-gray-900">Settings</h1>

      {/* System Health */}
      <div className="card p-5">
        <h3 className="font-semibold text-gray-800 mb-4">System Health</h3>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          {[
            { label: 'Database',      ok: health?.data?.db ?? null },
            { label: 'API',           ok: health ? true : null },
            { label: 'PHP 8.1',       ok: health?.data?.php ? health.data.php.startsWith('8') : null },
            { label: 'Reports Dir',   ok: health?.data?.reports_writable ?? null },
          ].map((item) => (
            <div key={item.label} className="flex items-center gap-2 p-3 rounded-xl bg-gray-50">
              <HealthDot ok={item.ok} />
              <span className="text-sm text-gray-700">{item.label}</span>
            </div>
          ))}
        </div>
        {health?.data?.php && (
          <p className="text-xs text-gray-400 mt-3">PHP {health.data.php} · {health.data.server || 'Apache'}</p>
        )}
      </div>

      {/* Cron Status */}
      {isSuperAdmin && (
        <div className="card overflow-hidden">
          <div className="px-5 py-4 border-b border-border">
            <h3 className="font-semibold text-gray-800">Cron Job Status</h3>
            <p className="text-xs text-gray-400 mt-0.5">Last run times from cron_logs table</p>
          </div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['Script', 'Frequency', 'Last Run', 'Status', 'Records'].map((h) => (
                  <th key={h} className="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {cronJobs.map((job) => {
                const log = logMap[job.name]
                return (
                  <tr key={job.name} className="border-t border-border hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium">{job.label}</td>
                    <td className="px-4 py-3 text-gray-400 font-mono text-xs">{job.freq}</td>
                    <td className="px-4 py-3 text-gray-500 text-xs">
                      {log ? formatDistanceToNow(new Date(log.ran_at), { addSuffix: true }) : '—'}
                    </td>
                    <td className="px-4 py-3">
                      {log ? (
                        <span className={`px-2 py-0.5 text-xs rounded-full ${log.status === 'success' ? 'bg-green-100 text-positive' : 'bg-red-100 text-negative'}`}>
                          {log.status}
                        </span>
                      ) : <span className="text-gray-300 text-xs">never</span>}
                    </td>
                    <td className="px-4 py-3 text-gray-500">{log?.records_processed ?? '—'}</td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* User Management — super_admin only */}
      {isSuperAdmin && (
        <div className="card p-5">
          <h3 className="font-semibold text-gray-800 mb-4">User Management</h3>

          {/* User form */}
          <div className="bg-gray-50 rounded-xl p-4 mb-4 space-y-3">
            <h4 className="text-sm font-medium text-gray-700">{editUid ? 'Edit User' : 'Add User'}</h4>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              {[['name', 'Full Name *'], ['email', 'Email *'], ['password', editUid ? 'New Password (leave blank to keep)' : 'Password *']].map(([k, lbl]) => (
                <div key={k}>
                  <label className="text-xs text-gray-500 mb-1 block">{lbl}</label>
                  <input
                    type={k === 'password' ? 'password' : 'text'}
                    value={(uForm as any)[k]}
                    onChange={(e) => setUForm({ ...uForm, [k]: e.target.value })}
                    className="w-full border border-border rounded-lg px-3 py-2 text-sm"
                  />
                </div>
              ))}
              <div>
                <label className="text-xs text-gray-500 mb-1 block">Role</label>
                <select
                  value={uForm.role}
                  onChange={(e) => setUForm({ ...uForm, role: e.target.value })}
                  className="w-full border border-border rounded-lg px-3 py-2 text-sm"
                >
                  <option value="viewer">Viewer</option>
                  <option value="analyst">Analyst</option>
                  <option value="super_admin">Super Admin</option>
                </select>
              </div>
            </div>

            {uForm.role !== 'super_admin' && (
              <div>
                <label className="text-xs text-gray-500 mb-2 block">Assign Clients</label>
                <div className="flex flex-wrap gap-2">
                  {(clients || []).map((c: any) => (
                    <button
                      key={c.id}
                      onClick={() => toggleClientAssign(c.id)}
                      className={`px-3 py-1 text-xs rounded-full border ${uForm.assigned_client_ids.includes(c.id) ? 'bg-navy text-white border-navy' : 'border-border text-gray-600'}`}
                    >
                      {c.name}
                    </button>
                  ))}
                </div>
              </div>
            )}

            <div className="flex gap-2">
              <button
                onClick={() => saveUser.mutate()}
                disabled={!uForm.name || !uForm.email || saveUser.isPending}
                className="btn-primary flex items-center gap-2"
              >
                <Plus className="w-4 h-4" />
                {editUid ? 'Update User' : 'Create User'}
              </button>
              {editUid && (
                <button onClick={() => { setEditUid(null); setUForm(emptyUser) }} className="btn-ghost">Cancel</button>
              )}
            </div>
          </div>

          {/* Users table */}
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border border-border rounded-lg">
              <tr>
                {['Name', 'Email', 'Role', 'Last Login', ''].map((h) => (
                  <th key={h} className="text-left px-4 py-2.5 text-xs font-semibold text-gray-500">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {(users || []).map((u: any) => (
                <tr key={u.id} className="border-t border-border hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium">{u.name}</td>
                  <td className="px-4 py-3 text-gray-500">{u.email}</td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-0.5 text-xs rounded-full ${u.role === 'super_admin' ? 'bg-navy/10 text-navy' : 'bg-gray-100 text-gray-600'}`}>
                      {u.role}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-gray-400 text-xs">
                    {u.last_login ? formatDistanceToNow(new Date(u.last_login), { addSuffix: true }) : 'Never'}
                  </td>
                  <td className="px-4 py-3">
                    <button onClick={() => startEditUser(u)} className="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-navy">
                      <Edit2 className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Change Password */}
      <div className="card p-5 max-w-md">
        <h3 className="font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <Key className="w-4 h-4" /> Change Password
        </h3>
        <div className="space-y-3">
          {[
            ['current', 'Current Password'],
            ['next',    'New Password'],
            ['confirm', 'Confirm New Password'],
          ].map(([k, lbl]) => (
            <div key={k}>
              <label className="text-xs text-gray-500 mb-1 block">{lbl}</label>
              <input
                type="password"
                value={(pwForm as any)[k]}
                onChange={(e) => setPwForm({ ...pwForm, [k]: e.target.value })}
                className="w-full border border-border rounded-lg px-3 py-2 text-sm"
              />
            </div>
          ))}
          {pwMsg && <p className={`text-xs ${pwMsg.includes('success') ? 'text-positive' : 'text-negative'}`}>{pwMsg}</p>}
          <button
            onClick={() => {
              if (pwForm.next !== pwForm.confirm) { setPwMsg('Passwords do not match.'); return }
              if (pwForm.next.length < 8) { setPwMsg('Password must be at least 8 characters.'); return }
              setPwMsg(''); changePw.mutate()
            }}
            disabled={!pwForm.current || !pwForm.next || changePw.isPending}
            className="w-full btn-primary"
          >
            {changePw.isPending ? 'Saving...' : 'Update Password'}
          </button>
        </div>
      </div>
    </div>
  )
}
