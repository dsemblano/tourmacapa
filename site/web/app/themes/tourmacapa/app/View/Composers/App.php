<?php

namespace App\View\Composers;

use Log1x\Navi\Navi;
use Roots\Acorn\View\Composer;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        '*',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'siteName' => $this->siteName(),
            'descName' => $this->descName(),
            'primary_navigation' => Navi::make()->build('primary_navigation')->toArray(),
        ];
    }

    /**
     * Returns the site name.
     *
     * @return string
     */
    public function siteName()
    {
        return get_bloginfo('name', 'display');
    }

    public function descName()
    {
        return get_bloginfo('description', 'display');
    }
}
