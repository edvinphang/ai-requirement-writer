'use client'
import { useEffect, useState } from 'react'
import Link from 'next/link'
import { apiClient } from '@/lib/api'
import { ProjectCard } from '@/components/ProjectCard'

interface Project {
  id: number; name: string; type: string; status: string; mode: string; created_at: string
}

export default function DashboardPage() {
  const [projects, setProjects] = useState<Project[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    apiClient.get<{ data: Project[] }>('/projects')
      .then(res => setProjects(res.data))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-semibold">Projects</h1>
        <Link href="/projects/new" className="bg-blue-900 text-white text-sm px-4 py-2 rounded">New project</Link>
      </div>
      {loading && <p className="text-gray-500">Loading…</p>}
      {!loading && projects.length === 0 && <p className="text-gray-500">No projects yet. Create your first one.</p>}
      <div className="grid gap-4">
        {projects.map(p => <ProjectCard key={p.id} project={p} />)}
      </div>
    </div>
  )
}
