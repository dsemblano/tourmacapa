<section id="hero" class="pb-6">
    {{-- {!! do_shortcode('[voucher_validation]') !!} --}}
    <div class="container">
        <div class="flex flex-col lg:flex-row gap-12">
            <div class="hero-left">
                {{-- <img id="logoname" class="hover:scale-110 transition duration-300 ease-in-out animate__animated animate__heartBeat animate__fast animate__delay-2s" width="200" height="280"
                src="{{ asset('resources/images/TucuFoodlogo.png') }}" alt="Tour Macapá página inicial" /> --}}

                <span
                    class="bg-primary text-white font-sans text-xl inline-block px-6 py-2 animate__animated animate__fadeInLeftBig">
                    Compre 1, Leve 2
                </span>
                <div class="flex flex-row">
                    <div class="w-full">
                        <h1
                        class="mt-4 text-7xl lg:text-8xl hero-heading font-bold text-grayH animate__animated animate__zoomInLeft  animate__fast text-gray-800">
                        Tour<br /> 
                        <span class="flex flex-row"> Macapá @include('partials/icons.logoTour', ['class' => 'relative lg:bottom-0 ', 'width' => '6rem', 'height' => '6rem'])</span>
                        </h1>
                    </div>
                    
                </div>

                <div class="hero-text text-center lg:text-left">
                    <p class="mt-7 text-3xl text-grayH">
                        Os melhores estabelecimentos da cidade e região!
                    </p>
                    <p class="mt-7 text-3xl text-grayH">
                        Vouchers e promoções para os melhores estabelecimentos do Amapá!
                    </p>
                </div>

                <div class="flex flex-col lg:flex-row gap-12 text-center lg:text-left mt-8">

                    <button
                        class="cursor-pointer bg-primary animate__animated animate__heartBeat animate__fast animate__delay-2s hover:bg-gray-400 text-white text-4xl py-4 px-6 rounded-lg inline-flex items-center">
                        @include('partials/icons.voucher')
                        <span class="pl-6">Assine já!</span>
                    </button>


                    <button
                        class="cursor-pointer bg-secondary hover:bg-accent text-white text-4xl font-bold py-4 px-6 rounded-lg inline-flex items-center">
                        <a href="/cadastro-loja">
                            <span class="pl-6">Cadastro loja</span>
                        </a>
                    </button>



                </div>
            </div>

            <picture class="hero-image">
                <img src="{{ asset('resources/images/bogo-hero.webp') }}" fetchpriority="high" width="700"
                    height="700" alt="Hero image">
            </picture>

        </div>
    </div>

</section>
