import Link from 'next/link'

interface Project {
  id: number
  name: string
  type: string
  status: string
  mode: string
  created_at: string
}

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-700',
  in_progress: 'bg-blue-100 text-blue-700',
  complete: 'bg-green-100 text-green-700',
}

export function ProjectCard({ project }: { project: Project }) {
  return (
    <Link
      href={`/projects/${project.id}`}
      className="block border rounded-lg p-4 hover:shadow-md transition-shadow bg-white"
    >
      <div className="flex items-start justify-between">
        <h3 className="font-medium text-gray-900">{project.name}</h3>
        <span className={`text-xs px-2 py-1 rounded-full font-medium ${statusColors[project.status] ?? 'bg-gray-100'}`}>
          {project.status.replace('_', ' ')}
        </span>
      </div>
      <p className="text-sm text-gray-500 mt-1 capitalize">{project.type}</p>
    </Link>
  )
}
