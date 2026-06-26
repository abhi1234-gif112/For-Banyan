import { NavLink } from 'react-router-dom'
import { useStore } from '../store'
import {
  LayoutDashboard, MessageSquare, Users, Bell,
  Zap, Hash, FileText, Building2, Settings, Eye
} from 'lucide-react'
import clsx from 'clsx'

const navItems = [
  { to: '/dashboard',   icon: LayoutDashboard, label: 'Dashboard'   },
  { to: '/mentions',    icon: MessageSquare,   label: 'Mentions'     },
  { to: '/individuals', icon: Users,           label: 'Individuals'  },
  { to: '/alerts',      icon: Bell,            label: 'Alerts'       },
  { to: '/actions',     icon: Zap,             label: 'Actions'      },
  { to: '/keywords',    icon: Hash,            label: 'Keywords'     },
  { to: '/reports',     icon: FileText,        label: 'Reports'      },
  { to: '/clients',     icon: Building2,       label: 'Clients'      },
  { to: '/settings',    icon: Settings,        label: 'Settings'     },
]

export default function Sidebar() {
  const { sidebarOpen, user } = useStore()

  return (
    <aside
      className={clsx(
        'fixed left-0 top-0 h-full bg-navy text-white flex flex-col transition-all duration-300 z-40',
        sidebarOpen ? 'w-60' : 'w-16'
      )}
    >
      {/* Logo */}
      <div className="flex items-center gap-3 px-4 py-5 border-b border-white/10">
        <div className="w-8 h-8 bg-gold rounded-lg flex items-center justify-center flex-shrink-0">
          <Eye className="w-5 h-5 text-navy" />
        </div>
        {sidebarOpen && (
          <div>
            <div className="font-bold text-lg leading-none tracking-wide">NAZAR</div>
            <div className="text-xs text-white/50 mt-0.5">Saptanga Labs</div>
          </div>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        {navItems.map(({ to, icon: Icon, label }) => {
          if (to === '/clients' && user?.role !== 'super_admin') return null
          return (
            <NavLink
              key={to}
              to={to}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-gold text-navy'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                )
              }
            >
              <Icon className="w-5 h-5 flex-shrink-0" />
              {sidebarOpen && <span>{label}</span>}
            </NavLink>
          )
        })}
      </nav>

      {/* User info */}
      {sidebarOpen && user && (
        <div className="px-4 py-4 border-t border-white/10">
          <div className="text-xs text-white/50">{user.email}</div>
          <div className="text-xs text-gold mt-0.5 capitalize">{user.role.replace('_', ' ')}</div>
        </div>
      )}
    </aside>
  )
}
