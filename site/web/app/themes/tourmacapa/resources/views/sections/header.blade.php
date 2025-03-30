<header class="banner border-gray-100 border-b-2 ">
  <div class="container">
    <div class="flex flex-row justify-between lg:justify-start lg:flex-wrap">
      <div class="logo">
          @include('partials.logo')
        {{-- <a class="brand" href="{{ home_url('/') }}">
          <span class="block">{!! $siteName !!}</span>
          <span>{!! $descName !!}</span>
        </a> --}}
      </div>
      @if (has_nav_menu('primary_navigation'))
      <nav class="nav-primary">
        <div class="flex flex-wrap lg:flex-nowrap justify-between items-center mx-auto">   
          @include('partials.menu')
        </div>
      </nav>
      @endif
    </div>
  </div>
</header>
