import { ref, reactive } from 'vue'
import type { ToastMessage } from '@/components/ToastNotification.vue'

// Global toast state
const toasts = ref<ToastMessage[]>([])

// Generate unique IDs for toasts
let toastId = 0
const generateId = () => `toast-${++toastId}`

export function useToast() {
  const addToast = (message: Omit<ToastMessage, 'id'>) => {
    const toast: ToastMessage = {
      id: generateId(),
      ...message
    }
    
    toasts.value.push(toast)
    return toast.id
  }

  const removeToast = (id: string) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  const clearAllToasts = () => {
    toasts.value = []
  }

  // Convenience methods
  const success = (message: string, title?: string, options?: Partial<ToastMessage>) => {
    return addToast({
      type: 'success',
      title,
      message,
      ...options
    })
  }

  const error = (message: string, title?: string, options?: Partial<ToastMessage>) => {
    return addToast({
      type: 'error',
      title,
      message,
      duration: 7000, // Errors show longer by default
      ...options
    })
  }

  const warning = (message: string, title?: string, options?: Partial<ToastMessage>) => {
    return addToast({
      type: 'warning',
      title,
      message,
      ...options
    })
  }

  const info = (message: string, title?: string, options?: Partial<ToastMessage>) => {
    return addToast({
      type: 'info',
      title,
      message,
      ...options
    })
  }

  return {
    toasts,
    addToast,
    removeToast,
    clearAllToasts,
    success,
    error,
    warning,
    info
  }
}

// Export a global instance for use across the app
export const globalToast = useToast()