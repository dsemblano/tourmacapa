<footer class="content-info py-4">
  <div class="container">
    {!! wp_nav_menu(['theme_location' => 'footer_navigation', 'menu_class' => 'flex footer-nav gap-4 w-full
        justify-center items-center lg:justify-start
        flex-row my-8 nav text-sm md:text-xl relative', 'echo' => false]) !!}
  </div>
  <div class="text-sm mt-4 flex flex-col items-center copyright border-gray-500 border-t border-solid pt-8 gap-2">
    <span class="z-10 font-bold">© {{date("Y")}} Tour Macapá<span
        class="sup align-text-bottom">&reg;</span>
  </div>
</footer>
