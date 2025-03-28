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
      <nav class="nav-primary container py-2 nav-primary">
        <div class="flex flex-wrap lg:flex-nowrap justify-between items-center mx-auto">   
          @include('partials.menu')
        </div>
      </nav>
      @endif
    </div>
  </div>
</header>
