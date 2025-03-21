<section class="">
    {{-- {!! do_shortcode('[voucher_validation]') !!} --}}

    <div class="container">
        <div class="flex flex-col lg:flex-row p-8">
            <div class="hero w-full lg:w-1/2">
                <img id="logoname" class="hover:scale-110 transition duration-300 ease-in-out" width="200" height="280"
                src="{{ Vite::asset('resources/images/TucuFoodlogo.png')}}" alt="Tour Macapá página inicial" />
                <span class="text-4xl inline-block text-white bg-primaryColor font-bold p-2">Compre 1, Leve 2</span>
                <h1 class="text-8xl hero-heading font-bold text-grayH">
                    Tour<br /> Macapá
                </h1>
                <p class="mt-7 text-3xl text-grayH">
                    Vouchers e promoções para os melhores estabelecimentos do Amapá!
                </p>
            </div>
    
            <picture class="hero-image w-full lg:w-1/2">
                <img src="{{ Vite::asset('resources/images/bogo-hero.webp')}}" width="600" height="600" alt="Hero image">
            </picture>
    
        </div>
    </div>

</section>
