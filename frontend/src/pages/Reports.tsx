import { useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { useStore } from '../store'
import api from '../api/client'
import { FileText, Download, Loader2 } from 'lucide-react'
import { format } from 'date-fns'

export default function Reports() {
  const { activeClientId } = useStore()
  const [reportType, setReportType] = useState('daily_hygiene')
  const [dateFrom, setDateFrom] = useState(format(new Date(Date.now() - 86400000), 'yyyy-MM-dd'))
  const [dateTo, setDateTo]     = useState(format(new Date(), 'yyyy-MM-dd'))

  const { data, refetch } = useQuery({
    queryKey: ['reports', activeClientId],
    enabled:  !!activeClientId,
    queryFn:  () => api.get(`/reports/list.php?client_id=${activeClientId}`).then((r) => r.data.data.reports),
  })

  const generate = useMutation({
    mutationFn: () => api.post('/reports/generate.php', {
      client_id:   activeClientId,
      report_type: reportType,
      date_from:   dateFrom,
      date_to:     dateTo,
    }),
    onSuccess: () => refetch(),
  })

  if (!activeClientId) return <div className="text-gray-400 text-center mt-20">Select a client first.</div>

  return (
    <div className="space-y-5 max-w-2xl">
      <h1 className="text-2xl font-bold text-gray-900">Reports</h1>

      {/* Generate */}
      <div className="card p-5">
        <h3 className="font-semibold text-gray-800 mb-4">Generate Report</h3>
        <div className="space-y-3">
          <div>
            <label className="text-xs text-gray-500 mb-1 block">Report Type</label>
            <select
              value={reportType}
              onChange={(e) => setReportType(e.target.value)}
              className="w-full border border-border rounded-lg px-3 py-2 text-sm"
            >
              <option value="daily_hygiene">Daily Hygiene Report</option>
              <option value="weekly_digest">Weekly Digest</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>

          {reportType === 'custom' && (
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs text-gray-500 mb-1 block">Date From</label>
                <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)}
                  className="w-full border border-border rounded-lg px-3 py-2 text-sm" />
              </div>
              <div>
                <label className="text-xs text-gray-500 mb-1 block">Date To</label>
                <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)}
                  className="w-full border border-border rounded-lg px-3 py-2 text-sm" />
              </div>
            </div>
          )}

          <button
            onClick={() => generate.mutate()}
            disabled={generate.isPending}
            className="w-full btn-primary flex items-center justify-center gap-2"
          >
            {generate.isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : <FileText className="w-4 h-4" />}
            {generate.isPending ? 'Generating PDF...' : 'Generate Report'}
          </button>

          {generate.data && (
            <a
              href={generate.data.data.data.download_url}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2 text-sm text-positive hover:underline"
            >
              <Download className="w-4 h-4" />
              Download generated report
            </a>
          )}
        </div>
      </div>

      {/* Reports list */}
      <div className="card p-5">
        <h3 className="font-semibold text-gray-800 mb-4">Report History</h3>
        {(data || []).length === 0 ? (
          <p className="text-gray-400 text-sm text-center py-8">No reports yet.</p>
        ) : (
          <div className="space-y-2">
            {(data || []).map((r: any) => (
              <div key={r.id} className="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50">
                <FileText className="w-5 h-5 text-navy flex-shrink-0" />
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-medium capitalize">{r.report_type.replace('_', ' ')}</div>
                  <div className="text-xs text-gray-400">{r.date_from} to {r.date_to}</div>
                </div>
                <div className="text-xs text-gray-400">{format(new Date(r.created_at), 'MMM d HH:mm')}</div>
                {r.download_url && (
                  <a href={r.download_url} target="_blank" rel="noopener noreferrer"
                    className="p-1.5 text-navy hover:bg-navy/10 rounded-lg">
                    <Download className="w-4 h-4" />
                  </a>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
