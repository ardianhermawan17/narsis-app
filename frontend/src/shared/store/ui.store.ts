import { create } from 'zustand'
import { immer } from 'zustand/middleware/immer'

interface UIState {
  isCreateModalOpen: boolean
  openCreateModal: () => void
  closeCreateModal: () => void
  setCreateModalOpen: (open: boolean) => void
}

export const useUIStore = create<UIState>()(
  immer((set) => ({
    isCreateModalOpen: false,

    openCreateModal: () =>
      set((state) => {
        state.isCreateModalOpen = true
      }),

    closeCreateModal: () =>
      set((state) => {
        state.isCreateModalOpen = false
      }),

    setCreateModalOpen: (open) =>
      set((state) => {
        state.isCreateModalOpen = open
      }),
  }))
)