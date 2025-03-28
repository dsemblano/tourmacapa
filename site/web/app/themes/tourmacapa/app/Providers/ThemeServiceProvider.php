<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        $this->app['view']->composer(
            'partials.header', // Match the view(s) you specified
            \App\View\Composers\NavigationComposer::class
        );
        
    }
}
