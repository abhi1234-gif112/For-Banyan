import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useStore } from './store'
import Layout from './components/Layout'
import ProtectedRoute from './components/ProtectedRoute'
import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import Mentions from './pages/Mentions'
import Individuals from './pages/Individuals'
import IndividualProfile from './pages/IndividualProfile'
import Alerts from './pages/Alerts'
import Actions from './pages/Actions'
import Keywords from './pages/Keywords'
import Reports from './pages/Reports'
import Clients from './pages/Clients'
import Settings from './pages/Settings'

export default function App() {
  const token = useStore((s) => s.token)

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={token ? <Navigate to="/dashboard" replace /> : <Login />} />
        <Route element={<ProtectedRoute />}>
          <Route element={<Layout />}>
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/mentions" element={<Mentions />} />
            <Route path="/individuals" element={<Individuals />} />
            <Route path="/individuals/:id" element={<IndividualProfile />} />
            <Route path="/alerts" element={<Alerts />} />
            <Route path="/actions" element={<Actions />} />
            <Route path="/keywords" element={<Keywords />} />
            <Route path="/reports" element={<Reports />} />
            <Route path="/clients" element={<Clients />} />
            <Route path="/settings" element={<Settings />} />
          </Route>
        </Route>
        <Route path="/" element={<Navigate to={token ? '/dashboard' : '/login'} replace />} />
        <Route path="*" element={<Navigate to={token ? '/dashboard' : '/login'} replace />} />
      </Routes>
    </BrowserRouter>
  )
}
