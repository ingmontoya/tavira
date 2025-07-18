<template>
  <form @submit.prevent="handleSubmit" :class="formClasses">
    <slot />
    
    <!-- CSRF Token -->
    <input type="hidden" name="_token" :value="csrfToken" />
    
    <!-- Rate Limit Warning -->
    <SecurityAlert 
      v-if="rateLimitWarning" 
      type="warning" 
      :message="rateLimitWarning"
      :dismissible="false"
      class="mb-4"
    />
    
    <!-- Security Errors -->
    <SecurityAlert 
      v-for="error in securityErrors" 
      :key="error"
      type="error" 
      :message="error"
      :dismissible="true"
      class="mb-4"
    />
  </form>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useSecurity } from '../composables/useSecurity'
import SecurityAlert from './SecurityAlert.vue'

interface Props {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  action?: string
  rateLimit?: string
  validateInputs?: boolean
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  method: 'POST',
  validateInputs: true,
  rateLimit: 'default',
})

const emit = defineEmits<{
  submit: [data: FormData]
  securityError: [errors: string[]]
}>()

const { config, sanitizeInput, updateActivity } = useSecurity()

const securityErrors = ref<string[]>([])
const rateLimitWarning = ref<string>('')
const submitCount = ref(0)
const lastSubmitTime = ref<Date>(new Date())

const csrfToken = computed(() => config.value.csrfToken)

const formClasses = computed(() => props.class || '')

const handleSubmit = (event: Event) => {
  updateActivity()
  
  const form = event.target as HTMLFormElement
  const formData = new FormData(form)
  
  securityErrors.value = []
  
  // Check rate limiting
  if (checkRateLimit()) {
    return
  }
  
  // Validate inputs if enabled
  if (props.validateInputs) {
    const validationErrors = validateFormInputs(formData)
    if (validationErrors.length > 0) {
      securityErrors.value = validationErrors
      emit('securityError', validationErrors)
      return
    }
  }
  
  // Sanitize inputs
  if (props.validateInputs) {
    sanitizeFormData(formData)
  }
  
  // Update submit tracking
  submitCount.value++
  lastSubmitTime.value = new Date()
  
  emit('submit', formData)
}

const checkRateLimit = (): boolean => {
  const rateLimit = config.value.rateLimits[props.rateLimit]
  
  if (!rateLimit) return false
  
  const now = new Date()
  const timeSinceLastSubmit = now.getTime() - lastSubmitTime.value.getTime()
  
  // Reset count if decay time has passed
  if (timeSinceLastSubmit > rateLimit.decay * 1000) {
    submitCount.value = 0
  }
  
  if (submitCount.value >= rateLimit.attempts) {
    const remainingTime = Math.ceil((rateLimit.decay * 1000 - timeSinceLastSubmit) / 1000)
    rateLimitWarning.value = `Too many requests. Please wait ${remainingTime} seconds before trying again.`
    return true
  }
  
  rateLimitWarning.value = ''
  return false
}

const validateFormInputs = (formData: FormData): string[] => {
  const errors: string[] = []
  
  for (const [key, value] of formData.entries()) {
    if (typeof value === 'string') {
      // Check for potential XSS
      if (/<script|javascript:|on\w+\s*=/i.test(value)) {
        errors.push(`Potentially dangerous content detected in ${key}`)
      }
      
      // Check for SQL injection patterns
      if (/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i.test(value)) {
        errors.push(`Suspicious patterns detected in ${key}`)
      }
      
      // Check for path traversal
      if (/\.\.\/|\.\.\\/.test(value)) {
        errors.push(`Path traversal attempt detected in ${key}`)
      }
      
      // Check for extremely long inputs
      if (value.length > 10000) {
        errors.push(`Input too long in field ${key}`)
      }
    }
  }
  
  return errors
}

const sanitizeFormData = (formData: FormData) => {
  const sanitized = new FormData()
  
  for (const [key, value] of formData.entries()) {
    if (typeof value === 'string') {
      sanitized.set(key, sanitizeInput(value))
    } else {
      sanitized.set(key, value)
    }
  }
  
  // Replace original form data
  formData.forEach((value, key) => {
    formData.delete(key)
  })
  
  sanitized.forEach((value, key) => {
    formData.set(key, value)
  })
}

onMounted(() => {
  // Check if CSRF token is present
  if (!csrfToken.value) {
    securityErrors.value.push('CSRF token is missing. Please refresh the page.')
  }
})
</script>