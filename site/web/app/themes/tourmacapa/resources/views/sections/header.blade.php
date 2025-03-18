<header class="banner border-gray-100 border-b-2 ">
  <div class="container">
    <div class="flex flex-wrap lg:flex-nowrap py-4">
      <div class="logo">
        @if (! is_home () && ! is_front_page())
          @include('partials.logo')
        @endif
        {{-- <a class="brand" href="{{ home_url('/') }}">
          <span class="block">{!! $siteName !!}</span>
          <span>{!! $descName !!}</span>
        </a> --}}
      </div>
      
      @if (has_nav_menu('primary_navigation'))
      <nav class="nav-primary font-[ibmplexsans] text-gray-700 flex flex-col
                w-full lg:flex-row lg:mt-0 text-base relative" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
        {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav', 'echo' => false]) !!}
      
      </nav>
      @endif
    </div>
  </div>
</header>
