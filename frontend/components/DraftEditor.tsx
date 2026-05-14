'use client'
import { useState } from 'react'
import { apiClient } from '@/lib/api'

interface Props {
  projectId: string
  draft: { id: number; content: string | null; status: string }
  onApproved: () => void
}

export default function DraftEditor({ projectId, draft, onApproved }: Props) {
  const [content, setContent] = useState(draft.content ?? '')
  const [saving, setSaving] = useState(false)
  const [approving, setApproving] = useState(false)
  const [error, setError] = useState('')

  async function handleSave() {
    setSaving(true)
    try {
      await apiClient.patch(`/projects/${projectId}/drafts/${draft.id}`, { content })
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  async function handleApprove() {
    setApproving(true)
    try {
      await apiClient.post(`/projects/${projectId}/drafts/${draft.id}/approve`, {})
      onApproved()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Approve failed')
    } finally {
      setApproving(false)
    }
  }

  if (draft.status === 'approved') {
    return (
      <div className="border rounded p-4 bg-green-50 text-sm">
        <p className="text-green-700 font-medium mb-2">Approved</p>
        <pre className="whitespace-pre-wrap text-gray-700 text-xs">{content}</pre>
      </div>
    )
  }

  return (
    <div className="space-y-3">
      {error && <p className="text-red-600 text-sm">{error}</p>}
      <textarea
        value={content}
        onChange={e => setContent(e.target.value)}
        rows={12}
        className="w-full border rounded px-3 py-2 text-sm font-mono resize-y"
      />
      <div className="flex gap-3">
        <button onClick={handleSave} disabled={saving}
          className="border px-4 py-2 rounded text-sm disabled:opacity-50">
          {saving ? 'Saving…' : 'Save edits'}
        </button>
        <button onClick={handleApprove} disabled={approving}
          className="bg-blue-900 text-white px-4 py-2 rounded text-sm disabled:opacity-50">
          {approving ? 'Approving…' : 'Approve'}
        </button>
      </div>
    </div>
  )
}
