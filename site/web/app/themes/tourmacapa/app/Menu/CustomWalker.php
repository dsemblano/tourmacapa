<?php

namespace App\Menu;

class CustomWalker extends \Walker_Nav_Menu
{
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="submenu hidden pl-4 space-y-2 text-xl animate__animated animate__fadeInDown animate__faster">';
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $class_names = is_array($item->classes) ? $item->classes : explode(' ', $item->classes);

        $output .= '<li class="' . esc_attr(join(' ', $class_names)) . '">';

        // Add parent link (clickable for navigation)
        $output .= '<a href="' . esc_url($item->url) . '" class="flex items-center justify-between">';
        $output .= esc_html($item->title);
        $output .= '</a>';

        // Add submenu toggle button for items with children
        if (in_array('menu-item-has-children', $class_names)) {
            $output .= '<button class="submenu-toggle ml-2" aria-expanded="false">↓</button>';
        }
    }
}
