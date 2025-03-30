<div x-data="{ mobileOpen: false }" class="relative" x-cloak>
  <!-- Desktop Menu (Hidden on mobile) -->
  <nav class="hidden lg:flex items-center space-x-6">
    @foreach ($primary_navigation as $item)
      <a 
        href="{{ $item->url }}" 
        class="hover:text-blue-500 transition-colors"
      >
        {{ $item->label }}
      </a>
    @endforeach
  </nav>

  <!-- Mobile Toggle Button (Hidden on desktop) -->
  <button 
    @click.stop="mobileOpen = !mobileOpen" 
    class="lg:hidden p-2 relative w-10 h-10 z-50" 
    :aria-expanded="mobileOpen"
  >
    <svg x-show="!mobileOpen" class="absolute inset-0 w-full h-full" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" color="#FF4242" />
    </svg>
    <svg x-show="mobileOpen" class="absolute inset-0 w-full h-full" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
  </button>

  <!-- Mobile Menu (Hidden on desktop) -->
  <div
    x-show="mobileOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-white lg:hidden pt-16"
    style="display: none"
  >
    <div class="container p-4">
      @foreach ($primary_navigation as $item)
        <a 
          href="{{ $item->url }}" 
          @click="mobileOpen = false"
          class="block py-3 text-xl border-b border-gray-100"
        >
          {{ $item->label }}
        </a>
      @endforeach
    </div>
  </div>
</div>