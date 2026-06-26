import { useQuery } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import { Menu, LogOut, ChevronDown } from 'lucide-react'
import { useState } from 'react'

export default function Topbar() {
  const { toggleSidebar, activeClient, setActiveClient, logout, user } = useStore()
  const [clientOpen, setClientOpen] = useState(false)

  const { data: clientsData } = useQuery({
    queryKey: ['clients'],
    queryFn: () => api.get('/clients/list.php').then((r) => r.data.data.clients),
  })

  const clients: any[] = clientsData || []

  const handleClientSelect = (client: any) => {
    setActiveClient(client)
    setClientOpen(false)
  }

  return (
    <header className="fixed top-0 right-0 left-0 bg-white border-b border-border z-30 ml-[var(--sidebar-w)]">
      <div className="flex items-center justify-between px-6 h-14">
        <div className="flex items-center gap-4">
          <button onClick={toggleSidebar} className="p-1.5 hover:bg-gray-100 rounded-lg">
            <Menu className="w-5 h-5 text-gray-600" />
          </button>

          {/* Client selector */}
          <div className="relative">
            <button
              onClick={() => setClientOpen(!clientOpen)}
              className="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-border hover:bg-gray-50 text-sm font-medium"
            >
              <div className="w-2 h-2 rounded-full bg-gold" />
              <span>{activeClient?.name || 'Select Client'}</span>
              <ChevronDown className="w-4 h-4 text-gray-400" />
            </button>
            {clientOpen && (
              <div className="absolute top-full left-0 mt-1 bg-white border border-border rounded-xl shadow-lg min-w-[220px] py-1 z-50">
                {clients.map((c: any) => (
                  <button
                    key={c.id}
                    onClick={() => handleClientSelect(c)}
                    className="w-full text-left px-4 py-2.5 hover:bg-gray-50 text-sm"
                  >
                    <div className="font-medium">{c.name}</div>
                    <div className="text-xs text-gray-400">{c.role} · {c.party}</div>
                  </button>
                ))}
              </div>
            )}
          </div>

          {activeClient && (
            <div className="hidden sm:flex items-center gap-2 text-sm text-gray-500">
              <span>{activeClient.party}</span>
              <span>·</span>
              <span>{activeClient.state}</span>
            </div>
          )}
        </div>

        <div className="flex items-center gap-2">
          <span className="text-sm text-gray-500 hidden sm:block">{user?.email}</span>
          <button
            onClick={logout}
            className="p-1.5 hover:bg-gray-100 rounded-lg text-gray-500"
            title="Logout"
          >
            <LogOut className="w-5 h-5" />
          </button>
        </div>
      </div>
    </header>
  )
}
