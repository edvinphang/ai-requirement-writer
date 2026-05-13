import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { ProjectCard } from '@/components/ProjectCard'

vi.mock('next/link', () => ({
  default: ({ href, children, className }: { href: string; children: React.ReactNode; className?: string }) => (
    <a href={href} className={className}>{children}</a>
  ),
}))

const project = {
  id: 1,
  name: 'My Web App',
  type: 'webapp',
  status: 'draft',
  mode: 'template',
  created_at: '2026-05-13T00:00:00Z',
}

describe('ProjectCard', () => {
  it('renders project name', () => {
    render(<ProjectCard project={project} />)
    expect(screen.getByText('My Web App')).toBeInTheDocument()
  })

  it('renders project type and status badge', () => {
    render(<ProjectCard project={project} />)
    expect(screen.getByText(/webapp/i)).toBeInTheDocument()
    expect(screen.getByText(/draft/i)).toBeInTheDocument()
  })
})
