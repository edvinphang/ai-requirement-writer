'use client'
import { useEffect, useRef, useState } from 'react'

interface Props {
  projectId: string
  endpoint: string
  body?: Record<string, unknown>
  onComplete: (content: string) => void
  onError: (msg: string) => void
}

export default function StreamingOutput({ projectId, endpoint, body, onComplete, onError }: Props) {
  const [text, setText] = useState('')
  const [streaming, setStreaming] = useState(false)
  const [done, setDone] = useState(false)
  const accumulatedRef = useRef('')

  useEffect(() => {
    const controller = new AbortController()
    const token = localStorage.getItem('token')
    setStreaming(true)
    setText('')
    accumulatedRef.current = ''

    const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api'

    fetch(`${BASE_URL}${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'text/event-stream',
        Authorization: `Bearer ${token ?? ''}`,
      },
      body: JSON.stringify(body ?? {}),
      signal: controller.signal,
    }).then(async res => {
      if (!res.ok) {
        const err = await res.json()
        onError(err.message ?? 'Generation failed')
        setStreaming(false)
        return
      }

      const reader = res.body!.getReader()
      const decoder = new TextDecoder()
      let buffer = ''

      while (true) {
        const { done: streamDone, value } = await reader.read()
        if (streamDone) break

        buffer += decoder.decode(value, { stream: true })
        const lines = buffer.split('\n')
        buffer = lines.pop() ?? ''

        for (const line of lines) {
          if (!line.startsWith('data: ')) continue
          const payload = line.slice(6)
          if (payload === '[DONE]') {
            setDone(true)
            setStreaming(false)
            onComplete(accumulatedRef.current)
            return
          }
          try {
            const parsed = JSON.parse(payload)
            if (parsed.error) {
              onError(parsed.error)
              setStreaming(false)
              return
            }
            if (parsed.text) {
              accumulatedRef.current += parsed.text
              setText(prev => prev + parsed.text)
            }
          } catch {
            // malformed chunk, skip
          }
        }
      }
    }).catch(err => {
      if (err.name === 'AbortError') return
      onError(err.message ?? 'Network error')
      setStreaming(false)
    })

    return () => controller.abort()
  }, [endpoint, JSON.stringify(body), onComplete, onError]) // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div className="relative">
      <div className="border rounded p-4 min-h-[200px] text-sm font-mono whitespace-pre-wrap bg-gray-50 overflow-auto max-h-[400px]">
        {text || <span className="text-gray-400">{streaming ? 'Generating…' : 'Waiting…'}</span>}
      </div>
      {streaming && (
        <span className="absolute bottom-3 right-3 text-xs text-gray-400 animate-pulse">streaming…</span>
      )}
      {done && (
        <span className="absolute bottom-3 right-3 text-xs text-green-600">Done</span>
      )}
    </div>
  )
}
