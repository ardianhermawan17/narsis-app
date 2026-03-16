import { fireEvent, render, screen, waitFor } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { AuthForm } from './index'

const loginMock = vi.fn(async (): Promise<void> => undefined)
const registerMock = vi.fn(async (): Promise<void> => undefined)

vi.mock('../../hooks/use-auth', () => ({
  useAuth: () => ({
    login: loginMock,
    register: registerMock,
    logout: vi.fn(),
    user: null,
    isAuthenticated: false,
    accessToken: null,
  }),
}))

describe('AuthForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    loginMock.mockResolvedValue(undefined)
    registerMock.mockResolvedValue(undefined)
  })

  it('shows login tab by default and can switch to register', async () => {
    render(<AuthForm />)

    expect(screen.getByRole('tab', { name: 'Login' }).getAttribute('data-state')).toBe('active')
    expect(screen.getByLabelText('Username or email')).toBeTruthy()

    fireEvent.click(screen.getByRole('tab', { name: 'Register' }))

    await waitFor(() => {
      expect(screen.getByLabelText('Username')).toBeTruthy()
      expect(screen.getByLabelText('Confirm password')).toBeTruthy()
    })
  })

  it('submits login values', async () => {
    render(<AuthForm />)

    fireEvent.change(screen.getByLabelText('Username or email'), {
      target: { value: 'user@example.com' },
    })
    fireEvent.change(screen.getByLabelText('Password'), {
      target: { value: 'secret123' },
    })

    fireEvent.click(screen.getByRole('button', { name: 'Login' }))

    await waitFor(() => {
      expect(loginMock).toHaveBeenCalledWith({
        usernameOrEmail: 'user@example.com',
        password: 'secret123',
      })
    })
  })

  it('submits register values', async () => {
    render(<AuthForm />)

    fireEvent.click(screen.getByRole('tab', { name: 'Register' }))

    fireEvent.change(screen.getByLabelText('Username'), {
      target: { value: 'newuser' },
    })
    fireEvent.change(screen.getByLabelText('Email'), {
      target: { value: 'new@example.com' },
    })
    fireEvent.change(screen.getByLabelText('Password'), {
      target: { value: 'secret123' },
    })
    fireEvent.change(screen.getByLabelText('Confirm password'), {
      target: { value: 'secret123' },
    })

    fireEvent.click(screen.getByRole('button', { name: 'Register' }))

    await waitFor(() => {
      expect(registerMock).toHaveBeenCalledWith({
        username: 'newuser',
        email: 'new@example.com',
        password: 'secret123',
        confirmPassword: 'secret123',
      })
    })
  })
})
