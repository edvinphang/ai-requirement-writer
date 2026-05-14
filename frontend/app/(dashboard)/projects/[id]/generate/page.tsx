'use client'
import { use, useCallback, useEffect, useState } from 'react'
import { apiClient } from '@/lib/api'
import StreamingOutput from '@/components/StreamingOutput'
import DraftEditor from '@/components/DraftEditor'

interface Draft {
  id: number
  type: 'brd' | 'stories' | 'spec'
  version: number
  content: string | null
  status: 'draft' | 'approved'
}

interface DraftsResponse {
  data: {
    brd?: Draft[]
    stories?: Draft[]
    spec?: Draft[]
  }
}

type Step = 'brd' | 'stories' | 'spec'

const STEPS: { key: Step; label: string; title: string }[] = [
  { key: 'brd', label: 'Business Requirements', title: 'Step 1 — BRD' },
  { key: 'stories', label: 'User Stories', title: 'Step 2 — User Stories' },
  { key: 'spec', label: 'Technical Spec', title: 'Step 3 — Technical Spec' },
]

export default function GeneratePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const [drafts, setDrafts] = useState<Record<Step, Draft[]>>({ brd: [], stories: [], spec: [] })
  const [generating, setGenerating] = useState<Step | null>(null)
  const [error, setError] = useState('')

  const loadDrafts = useCallback(async () => {
    try {
      const res = await apiClient.get<DraftsResponse>(`/projects/${id}/drafts`)
      setDrafts({
        brd: res.data.brd ?? [],
        stories: res.data.stories ?? [],
        spec: res.data.spec ?? [],
      })
    } catch {
      // silently ignore — drafts just stay empty
    }
  }, [id])

  useEffect(() => { loadDrafts() }, [loadDrafts])

  function latestDraft(step: Step): Draft | undefined {
    return drafts[step].at(-1)
  }

  function isApproved(step: Step): boolean {
    return latestDraft(step)?.status === 'approved'
  }

  function generateBody(step: Step): Record<string, unknown> {
    if (step === 'stories') return { brd_draft_id: latestDraft('brd')!.id }
    if (step === 'spec') return { brd_draft_id: latestDraft('brd')!.id, stories_draft_id: latestDraft('stories')!.id }
    return {}
  }

  function canGenerate(step: Step): boolean {
    if (step === 'brd') return true
    if (step === 'stories') return isApproved('brd')
    if (step === 'spec') return isApproved('stories')
    return false
  }

  return (
    <div className="max-w-3xl space-y-10">
      <h1 className="text-2xl font-semibold">Generate requirements</h1>
      {error && <p className="text-red-600 text-sm">{error}</p>}

      {STEPS.map((step) => {
        const draft = latestDraft(step.key)
        const locked = !canGenerate(step.key)
        const isGenerating = generating === step.key

        return (
          <section key={step.key} className={locked ? 'opacity-40 pointer-events-none' : ''}>
            <div className="flex items-center justify-between mb-3">
              <h2 className="text-lg font-medium">{step.title}</h2>
              {isApproved(step.key) && (
                <span className="text-xs text-green-600 font-medium">Approved</span>
              )}
            </div>

            {!isGenerating && !draft && (
              <button
                onClick={() => setGenerating(step.key)}
                disabled={locked}
                className="bg-blue-900 text-white px-5 py-2 rounded text-sm font-medium disabled:opacity-50"
              >
                Generate {step.label}
              </button>
            )}

            {isGenerating && (
              <StreamingOutput
                projectId={id}
                endpoint={`/projects/${id}/generate/${step.key}`}
                body={generateBody(step.key)}
                onComplete={async () => {
                  setGenerating(null)
                  await loadDrafts()
                }}
                onError={msg => {
                  setError(msg)
                  setGenerating(null)
                }}
              />
            )}

            {!isGenerating && draft && (
              <DraftEditor
                projectId={id}
                draft={draft}
                onApproved={loadDrafts}
              />
            )}

            {!isGenerating && draft && draft.status !== 'approved' && (
              <button
                onClick={() => setGenerating(step.key)}
                className="mt-3 text-sm text-gray-500 underline"
              >
                Regenerate
              </button>
            )}
          </section>
        )
      })}
    </div>
  )
}
