import { formatDistanceToNow } from 'date-fns'
import { AlertTriangle, Zap, CheckCircle } from 'lucide-react'
import clsx from 'clsx'

interface AlertCardProps {
  alert: any
  onMarkRead?: (id: string) => void
  onMarkActioned?: (id: string) => void
  onRespond?: (alert: any) => void
}

const severityIcon: Record<string, any> = {
  critical: AlertTriangle,
  high:     AlertTriangle,
  medium:   AlertTriangle,
  low:      CheckCircle,
}

const alertTypeLabel: Record<string, string> = {
  spike:               'Mention Spike',
  viral_positive:      'Viral Positive',
  viral_negative:      'Viral Negative',
  opposition_attack:   'Opposition Attack',
  coordinated_campaign: 'Coordinated Campaign',
  new_threat_individual: 'New Threat',
  keyword_surge:       'Keyword Surge',
  media_pickup:        'Media Pickup',
  positive_milestone:  'Positive Milestone',
  silence_anomaly:     'Silence Anomaly',
}

export default function AlertCard({ alert, onMarkRead, onMarkActioned, onRespond }: AlertCardProps) {
  const SevIcon = severityIcon[alert.severity] || AlertTriangle
  const sevClass = `badge-${alert.severity}`
  const canRespond = ['viral_negative', 'coordinated_campaign', 'opposition_attack', 'spike'].includes(alert.alert_type)

  return (
    <div className={clsx(
      'card p-4 border-l-4',
      alert.severity === 'critical' ? 'border-l-negative' :
      alert.severity === 'high'     ? 'border-l-orange-500' :
      alert.severity === 'medium'   ? 'border-l-blue-500' :
      'border-l-gray-300'
    )}>
      <div className="flex items-start gap-3">
        <SevIcon className={clsx(
          'w-5 h-5 flex-shrink-0 mt-0.5',
          alert.severity === 'critical' ? 'text-negative' :
          alert.severity === 'high'     ? 'text-orange-500' :
          alert.severity === 'medium'   ? 'text-blue-500' :
          'text-gray-400'
        )} />
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <span className={sevClass}>{alert.severity}</span>
            <span className="text-xs text-gray-400">{alertTypeLabel[alert.alert_type] || alert.alert_type}</span>
            <span className="text-xs text-gray-300">·</span>
            <span className="text-xs text-gray-400">
              {formatDistanceToNow(new Date(alert.triggered_at), { addSuffix: true })}
            </span>
          </div>
          <h4 className="font-semibold text-sm text-gray-900 mt-1">{alert.title}</h4>
          <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{alert.description}</p>

          <div className="flex items-center gap-2 mt-3 flex-wrap">
            {!alert.is_read && onMarkRead && (
              <button
                onClick={() => onMarkRead(alert.id)}
                className="text-xs px-2.5 py-1 border border-gray-200 rounded-lg hover:bg-gray-50"
              >
                Mark Read
              </button>
            )}
            {!alert.is_actioned && onMarkActioned && (
              <button
                onClick={() => onMarkActioned(alert.id)}
                className="text-xs px-2.5 py-1 border border-gray-200 rounded-lg hover:bg-gray-50"
              >
                Mark Actioned
              </button>
            )}
            {canRespond && onRespond && (
              <button
                onClick={() => onRespond(alert)}
                className="text-xs px-2.5 py-1 bg-navy text-white rounded-lg hover:bg-opacity-90 flex items-center gap-1"
              >
                <Zap className="w-3 h-3" />
                Generate Counter
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
