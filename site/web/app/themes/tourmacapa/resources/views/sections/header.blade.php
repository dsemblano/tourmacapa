<header class="banner border-gray-100 border-b-2 w-full z-50 bg-white fixed top-0 left-0 shadow">
  <div class="container">
    <div class="flex flex-row justify-between items-center gap-20 lg:justify-start lg:flex-wrap lg:text-2xl text-p">
      <div class="logo">
          @include('partials.logo')
        {{-- <a class="brand" href="{{ home_url('/') }}">
          <span class="block">{!! $siteName !!}</span>
          <span>{!! $descName !!}</span>
        </a> --}}
      </div>
      @if (has_nav_menu('primary_navigation'))
      <nav class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
        <div class="flex flex-wrap lg:flex-nowrap justify-between items-center mx-auto">   
          @include('partials.menu')
        </div>
      </nav>
      @endif
    </div>
  </div>
</header>
