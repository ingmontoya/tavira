import { router } from '@inertiajs/vue3'

export function useFeatureFlags() {
  /**
   * Check if a feature is enabled and redirect to FeatureDisabled if not
   * @param feature Feature name to check
   * @param enabled Whether the feature is enabled
   * @param message Custom message to show if disabled
   * @param upgradeUrl Optional upgrade URL
   */
  function requireFeature(
    feature: string, 
    enabled: boolean, 
    message?: string,
    upgradeUrl?: string
  ) {
    if (!enabled) {
      router.visit('/feature-disabled', {
        method: 'get',
        data: {
          feature,
          message: message || `El módulo ${feature} no está disponible en su plan actual.`,
          upgrade_url: upgradeUrl || '/subscription/upgrade'
        }
      })
      return false
    }
    return true
  }

  /**
   * Create a route handler that checks for feature flags
   * @param feature Feature name to check
   * @param enabled Whether the feature is enabled
   */
  function withFeatureFlag(feature: string, enabled: boolean) {
    return {
      beforeEnter: () => {
        return requireFeature(feature, enabled)
      }
    }
  }

  return {
    requireFeature,
    withFeatureFlag
  }
}