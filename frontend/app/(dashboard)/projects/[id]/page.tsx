export default function ProjectDetailPage({ params }: { params: { id: string } }) {
  return (
    <div>
      <h1 className="text-2xl font-semibold">Project #{params.id}</h1>
      <p className="text-gray-500 mt-2">AI generation features coming in Plan 2.</p>
    </div>
  )
}
