<!-- Menu Overlay Mobile with the X button -->
<div id="menu-overlay" class="animate__animated animate__fadeInDown animate__faster fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center opacity-0 invisible transition-opacity duration-300 z-50 hidden md:hidden">
    <button id="menu-close" class="absolute top-9 right-10 text-white text-5xl focus:outline-none">&times;</button>
    
        
    <?php
    wp_nav_menu([
        'theme_location' => 'primary-menu',
        'container'      => false,
        'menu_class'     => 'space-y-4 text-white text-2xl uppercase font-bold',
        'walker'         => new \App\Menu\CustomWalker(),
        'echo'           => true, // Since Blade handles the output
    ]);
    ?>
    
</div>
        
        
        <!-- Menu Toggle Button -->
        <button id="menu-toggle" class="lg:hidden mr-4 z-60 bg-transparent border-none focus:outline-none">
            <div class="w-8 h-1 bg-white mb-2 transform transition-transform"></div>
            <div class="w-8 h-1 bg-white mb-2 transform transition-transform"></div>
            <div class="w-8 h-1 bg-white transform transition-transform"></div>
        </button>

        {{-- Menu desktop --}}
        <div class="hidden justify-between items-center w-full lg:flex lg:order-1" id="mobile-menu-3">
            {{-- <div class="relative mt-3 lg:hidden">
                @include('partials/inputsearch')
            </div> --}}
            {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'flex flex-col pl-4 py-2
            tracking-widest w-full justify-evenly lg:flex-row lg:mt-0 nav text-white text-lg relative', 'echo' => false]) !!}
        </div>