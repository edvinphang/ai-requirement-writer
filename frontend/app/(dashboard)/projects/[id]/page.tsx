'use client'
import { use, useEffect, useState } from 'react'
import Link from 'next/link'
import { apiClient } from '@/lib/api'

interface Project {
  id: number
  name: string
  type: string
  status: string
  mode: string
  template?: { name: string }
}

export default function ProjectDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const [project, setProject] = useState<Project | null>(null)

  useEffect(() => {
    apiClient.get<{ data: Project }>(`/projects/${id}`)
      .then(res => setProject(res.data))
      .catch(console.error)
  }, [id])

  if (!project) return <p className="text-sm text-gray-500">Loading…</p>

  return (
    <div className="max-w-2xl">
      <h1 className="text-2xl font-semibold mb-1">{project.name}</h1>
      <p className="text-sm text-gray-500 mb-6">{project.template?.name ?? project.type}</p>
      <Link
        href={`/projects/${id}/intake`}
        className="inline-block bg-blue-900 text-white px-6 py-2 rounded text-sm font-medium"
      >
        Fill intake form →
      </Link>
    </div>
  )
}
