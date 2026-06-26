import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface Client {
  id: string
  name: string
  role: string
  party: string
  state: string
  constituency: string
  platforms?: string[]
}

interface User {
  id: string
  email: string
  role: 'super_admin' | 'analyst' | 'viewer'
  assigned_client_ids: string[]
}

interface NazarStore {
  token: string | null
  user: User | null
  activeClient: Client | null
  activeClientId: string | null
  sidebarOpen: boolean
  setAuth: (token: string, user: User) => void
  setActiveClient: (client: Client) => void
  logout: () => void
  toggleSidebar: () => void
}

export const useStore = create<NazarStore>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      activeClient: null,
      activeClientId: null,
      sidebarOpen: true,
      setAuth: (token, user) => set({ token, user }),
      setActiveClient: (client) => set({ activeClient: client, activeClientId: client.id }),
      logout: () => set({ token: null, user: null, activeClient: null, activeClientId: null }),
      toggleSidebar: () => set((s) => ({ sidebarOpen: !s.sidebarOpen })),
    }),
    {
      name: 'nazar-store',
      partialize: (s) => ({
        token: s.token,
        user: s.user,
        activeClient: s.activeClient,
        activeClientId: s.activeClientId,
      }),
    }
  )
)
