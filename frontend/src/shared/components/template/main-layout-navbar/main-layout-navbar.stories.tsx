import type { Meta, StoryObj } from '@storybook/nextjs-vite'
import { vi } from 'vitest'
import { MainLayoutNavbar } from './main-layout-navbar'
import * as NavbarHook from './use-main-layout-navbar'
import type { UseMainLayoutNavbarReturn } from './use-main-layout-navbar'

type Override = Partial<UseMainLayoutNavbarReturn>

function mockNavbarReturn(overrides: Override = {}): UseMainLayoutNavbarReturn {
	const base: Omit<UseMainLayoutNavbarReturn, 'navItems'> = {
		isDesktop: true,
		isMobileMenuOpen: false,
		logoText: 'Narsis',
		avatarFallback: 'N',
		avatarSrc: undefined,
		toggleMobileMenu: vi.fn(),
		closeMobileMenu: vi.fn(),
	}

	const navItems: UseMainLayoutNavbarReturn['navItems'] = [
		{ id: 'home', label: 'Home', icon: 'Home', href: '/feed', isActive: false, onClick: vi.fn() },
		{
			id: 'create',
			label: 'Create',
			icon: 'PlusSquare',
			href: undefined,
			isActive: false,
			onClick: vi.fn(),
		},
		{
			id: 'profile',
			label: 'Profile',
			icon: 'User',
			href: '/profile',
			isActive: false,
			onClick: vi.fn(),
		},
	]

	return {
		...base,
		navItems,
		...overrides,
	}
}

function withMock(overrides: Override = {}) {
	const spy = vi.spyOn(NavbarHook, 'useMainLayoutNavbar')
	spy.mockReturnValue(mockNavbarReturn(overrides))
	return <MainLayoutNavbar />
}

const meta: Meta<typeof MainLayoutNavbar> = {
	title: 'Shared/Layouts/MainLayoutNavbar',
	component: MainLayoutNavbar,
	tags: ['autodocs'],
}

export default meta
type Story = StoryObj<typeof MainLayoutNavbar>

export const Parameters: Story = {
	render: () => withMock(),
	parameters: {
		docs: {
			description: {
				story:
					'This wrapper component does not receive props directly. It reads all navbar data from useMainLayoutNavbar().',
			},
		},
	},
}

export const Desktop: Story = {
	render: () => withMock({ isDesktop: true }),
}

export const Mobile: Story = {
	render: () => withMock({ isDesktop: false, isMobileMenuOpen: true }),
	parameters: {
		viewport: { defaultViewport: 'mobile1' },
	},
}

export const Default: Story = {
	render: () => withMock({ avatarSrc: undefined }),
}

export const ActiveHome: Story = {
	render: () =>
		withMock({
			navItems: mockNavbarReturn().navItems.map((item) => ({
				...item,
				isActive: item.id === 'home',
			})),
		}),
}

export const ActiveProfile: Story = {
	render: () =>
		withMock({
			avatarFallback: 'A',
			navItems: mockNavbarReturn().navItems.map((item) => ({
				...item,
				isActive: item.id === 'profile',
			})),
		}),
}

export const WithAvatar: Story = {
	render: () =>
		withMock({
			avatarFallback: 'A',
			avatarSrc: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&q=80',
			navItems: mockNavbarReturn().navItems.map((item) => ({
				...item,
				isActive: item.id === 'profile',
			})),
		}),
}

export const MobileViewport: Story = {
	render: () => withMock({ isDesktop: false }),
	parameters: {
		viewport: { defaultViewport: 'mobile1' },
	},
}
