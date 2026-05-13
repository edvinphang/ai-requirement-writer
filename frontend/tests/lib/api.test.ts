import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiClient } from '@/lib/api'

describe('apiClient', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn())
    localStorage.clear()
  })

  it('sends Authorization header when token exists in localStorage', async () => {
    localStorage.setItem('token', 'test-token-123')
    const mockResponse = { ok: true, json: async () => ({ data: [] }) }
    vi.mocked(fetch).mockResolvedValue(mockResponse as Response)

    await apiClient.get('/projects')

    expect(fetch).toHaveBeenCalledWith(
      'http://localhost:8000/api/projects',
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer test-token-123',
        }),
      })
    )
  })

  it('does not send Authorization header when no token', async () => {
    const mockResponse = { ok: true, json: async () => ({}) }
    vi.mocked(fetch).mockResolvedValue(mockResponse as Response)

    await apiClient.post('/auth/login', { email: 'a@b.com', password: 'pw' })

    const callArgs = vi.mocked(fetch).mock.calls[0][1] as RequestInit
    expect((callArgs.headers as Record<string, string>)['Authorization']).toBeUndefined()
  })

  it('throws an error when response is not ok', async () => {
    const mockResponse = { ok: false, status: 401, json: async () => ({ message: 'Unauthorized' }) }
    vi.mocked(fetch).mockResolvedValue(mockResponse as Response)

    await expect(apiClient.get('/projects')).rejects.toThrow('Unauthorized')
  })
})
