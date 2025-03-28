import Alpine from 'alpinejs'
import focus from '@alpinejs/focus' // For better accessibility

// Initialize Alpine
window.Alpine = Alpine
Alpine.start()

import.meta.glob([
  '../images/**',
  '../fonts/**',
]);
