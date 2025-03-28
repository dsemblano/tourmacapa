<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

// Setup in functions.php
use function Roots\view;

add_filter('sage/blade/data', function ($data) {
  $data['primary_navigation'] = \Log1x\Navi\Facades\Navi::build('primary_navigation')->toArray();
  return $data;
});
