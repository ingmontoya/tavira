import { ref, computed } from 'vue'

export interface SecurityConfig {
  csrfToken: string
  sessionTimeout: number
  maxFileSize: number
  allowedFileTypes: string[]
  rateLimits: {
    [key: string]: {
      attempts: number
      decay: number
    }
  }
}

export interface SecurityState {
  isAuthenticated: boolean
  sessionExpiresAt: Date | null
  remainingAttempts: number
  lastActivity: Date
}

const securityState = ref<SecurityState>({
  isAuthenticated: false,
  sessionExpiresAt: null,
  remainingAttempts: 0,
  lastActivity: new Date(),
})

export function useSecurity() {
  const config = computed<SecurityConfig>(() => ({
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    sessionTimeout: 480, // 8 hours in minutes
    maxFileSize: 10485760, // 10MB
    allowedFileTypes: [
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/webp',
      'application/pdf',
      'text/plain',
      'text/csv',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    rateLimits: {
      default: { attempts: 100, decay: 60 },
      auth: { attempts: 5, decay: 60 },
      upload: { attempts: 10, decay: 60 },
      search: { attempts: 30, decay: 60 },
      strict: { attempts: 10, decay: 60 },
    },
  }))

  const updateActivity = () => {
    securityState.value.lastActivity = new Date()
  }

  const sanitizeInput = (input: string): string => {
    return input
      .replace(/[<>]/g, '')
      .replace(/javascript:/gi, '')
      .replace(/on\w+\s*=/gi, '')
      .trim()
  }

  const validateFile = (file: File): { valid: boolean; errors: string[] } => {
    const errors: string[] = []
    
    // Check file size
    if (file.size > config.value.maxFileSize) {
      errors.push(`File size exceeds maximum allowed size of ${formatBytes(config.value.maxFileSize)}`)
    }
    
    // Check MIME type
    if (!config.value.allowedFileTypes.includes(file.type)) {
      errors.push(`File type not allowed: ${file.type}`)
    }
    
    // Check file extension
    const extension = file.name.split('.').pop()?.toLowerCase()
    const dangerousExtensions = ['php', 'js', 'exe', 'bat', 'cmd', 'sh', 'ps1', 'vbs', 'jar', 'com', 'scr', 'msi', 'dll']
    
    if (extension && dangerousExtensions.includes(extension)) {
      errors.push(`File extension not allowed: ${extension}`)
    }
    
    // Check for double extensions
    const parts = file.name.split('.')
    if (parts.length > 2) {
      for (let i = 1; i < parts.length - 1; i++) {
        if (dangerousExtensions.includes(parts[i].toLowerCase())) {
          errors.push('Files with double extensions are not allowed')
          break
        }
      }
    }
    
    return {
      valid: errors.length === 0,
      errors,
    }
  }

  const formatBytes = (bytes: number): string => {
    const units = ['B', 'KB', 'MB', 'GB']
    let size = bytes
    let unitIndex = 0
    
    while (size > 1024 && unitIndex < units.length - 1) {
      size /= 1024
      unitIndex++
    }
    
    return `${Math.round(size * 100) / 100} ${units[unitIndex]}`
  }

  const isValidUrl = (url: string): boolean => {
    try {
      const urlObj = new URL(url)
      return ['http:', 'https:'].includes(urlObj.protocol)
    } catch {
      return false
    }
  }

  const escapeHtml = (text: string): string => {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  }

  const generateNonce = (): string => {
    const array = new Uint8Array(16)
    crypto.getRandomValues(array)
    return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('')
  }

  const checkSessionTimeout = (): boolean => {
    if (!securityState.value.sessionExpiresAt) return false
    
    const now = new Date()
    const timeRemaining = securityState.value.sessionExpiresAt.getTime() - now.getTime()
    
    return timeRemaining <= 0
  }

  const getSessionTimeRemaining = (): number => {
    if (!securityState.value.sessionExpiresAt) return 0
    
    const now = new Date()
    const timeRemaining = securityState.value.sessionExpiresAt.getTime() - now.getTime()
    
    return Math.max(0, Math.floor(timeRemaining / 1000 / 60)) // minutes
  }

  const initializeSession = (user: any) => {
    securityState.value.isAuthenticated = !!user
    
    if (user) {
      const expiresAt = new Date()
      expiresAt.setMinutes(expiresAt.getMinutes() + config.value.sessionTimeout)
      securityState.value.sessionExpiresAt = expiresAt
    }
  }

  const extendSession = () => {
    if (securityState.value.isAuthenticated) {
      const expiresAt = new Date()
      expiresAt.setMinutes(expiresAt.getMinutes() + config.value.sessionTimeout)
      securityState.value.sessionExpiresAt = expiresAt
    }
  }

  const clearSession = () => {
    securityState.value.isAuthenticated = false
    securityState.value.sessionExpiresAt = null
  }

  return {
    config,
    securityState,
    updateActivity,
    sanitizeInput,
    validateFile,
    formatBytes,
    isValidUrl,
    escapeHtml,
    generateNonce,
    checkSessionTimeout,
    getSessionTimeRemaining,
    initializeSession,
    extendSession,
    clearSession,
  }
}