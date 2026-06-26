import { Outlet } from 'react-router-dom'
import Sidebar from './Sidebar'
import Topbar from './Topbar'
import { useStore } from '../store'
import clsx from 'clsx'

export default function Layout() {
  const sidebarOpen = useStore((s) => s.sidebarOpen)

  return (
    <div className="min-h-screen bg-bg">
      <Sidebar />
      <div
        className={clsx(
          'transition-all duration-300',
          sidebarOpen ? 'ml-60' : 'ml-16'
        )}
        style={{ '--sidebar-w': sidebarOpen ? '15rem' : '4rem' } as React.CSSProperties}
      >
        <Topbar />
        <main className="pt-14 min-h-screen">
          <div className="p-6">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  )
}
