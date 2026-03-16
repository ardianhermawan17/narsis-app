import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AuthForm } from './auth-form';

const replaceMock = vi.fn();
const dispatchMock = vi.fn();

const loginUnwrapMock = vi.fn();
const signupUnwrapMock = vi.fn();

const loginMutationMock = vi.fn(() => ({
	unwrap: loginUnwrapMock,
}));

const signupMutationMock = vi.fn(() => ({
	unwrap: signupUnwrapMock,
}));

vi.mock('next/navigation', () => ({
	useRouter: () => ({
		replace: replaceMock,
	}),
}));

vi.mock('@shared/config/redux/hooks', () => ({
	useAppDispatch: () => dispatchMock,
}));

vi.mock('@feature/auth/api/auth-api', () => ({
	useLoginMutation: () => [loginMutationMock],
	useSignupMutation: () => [signupMutationMock],
}));

vi.mock('sonner', () => ({
	toast: {
		loading: vi.fn(() => 'toast-id'),
		success: vi.fn(),
		error: vi.fn(),
	},
}));

describe('AuthForm', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		loginUnwrapMock.mockResolvedValue({});
		signupUnwrapMock.mockResolvedValue({});
	});

	it('shows login mode by default and toggles to signup/login', async () => {
		render(<AuthForm />);

		expect(screen.getByText('login')).toBeTruthy();
		expect(screen.getByPlaceholderText('Email')).toBeTruthy();
		expect(screen.getByPlaceholderText('Password')).toBeTruthy();
		expect(screen.queryByPlaceholderText('Display Name')).toBeNull();
		expect(screen.getByRole('button', { name: 'Login' })).toBeTruthy();

		fireEvent.click(screen.getByRole('button', { name: 'Sign up' }));

		await waitFor(() => {
			expect(screen.getByText('signup')).toBeTruthy();
			expect(screen.getByPlaceholderText('Display Name')).toBeTruthy();
			expect(screen.getByRole('button', { name: 'Sign Up' })).toBeTruthy();
		});

		fireEvent.click(screen.getByRole('button', { name: 'Login' }));

		await waitFor(() => {
			expect(screen.getByText('login')).toBeTruthy();
			expect(screen.queryByPlaceholderText('Display Name')).toBeNull();
		});
	});

	it('shows required validation for email, password, and display_name in signup mode', async () => {
		render(<AuthForm />);

		fireEvent.click(screen.getByRole('button', { name: 'Sign up' }));
		fireEvent.click(screen.getByRole('button', { name: 'Sign Up' }));

		await waitFor(() => {
			expect(screen.getByText('Email is required')).toBeTruthy();
			expect(screen.getByText('Display name is required')).toBeTruthy();
			expect(screen.getByText('Password is required')).toBeTruthy();
		});

		expect(loginMutationMock).not.toHaveBeenCalled();
		expect(signupMutationMock).not.toHaveBeenCalled();
	});

	it('submits login values correctly', async () => {
		render(<AuthForm />);

		fireEvent.change(screen.getByPlaceholderText('Email'), {
			target: { value: 'login@example.com' },
		});
		fireEvent.change(screen.getByPlaceholderText('Password'), {
			target: { value: 'secret123' },
		});

		fireEvent.click(screen.getByRole('button', { name: 'Login' }));

		await waitFor(() => {
			expect(loginMutationMock).toHaveBeenCalledWith({
				email: 'login@example.com',
				password: 'secret123',
			});
			expect(replaceMock).toHaveBeenCalledWith('/draft-history');
		});

		expect(signupMutationMock).not.toHaveBeenCalled();
	});

	it('submits signup values correctly', async () => {
		render(<AuthForm />);

		fireEvent.click(screen.getByRole('button', { name: 'Sign up' }));

		fireEvent.change(screen.getByPlaceholderText('Email'), {
			target: { value: 'signup@example.com' },
		});
		fireEvent.change(screen.getByPlaceholderText('Display Name'), {
			target: { value: 'New User' },
		});
		fireEvent.change(screen.getByPlaceholderText('Password'), {
			target: { value: 'secret123' },
		});

		fireEvent.click(screen.getByRole('button', { name: 'Sign Up' }));

		await waitFor(() => {
			expect(signupMutationMock).toHaveBeenCalledWith({
				email: 'signup@example.com',
				password: 'secret123',
				display_name: 'New User',
			});
			expect(replaceMock).toHaveBeenCalledWith('/draft-history');
		});

		expect(loginMutationMock).not.toHaveBeenCalled();
	});
});
