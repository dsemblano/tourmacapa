<?php

add_action('admin_notices', function () {
  if (current_user_can('administrator') && isset($_GET['debug_voucher'])) {
      $id = intval($_GET['debug_voucher']);
      $meta = get_post_meta($id);
      echo '<pre>'; print_r($meta); echo '</pre>';
  }
});

add_action('admin_init', function () {
  if (isset($_GET['debug_voucher_all'])) {
      $vouchers = get_posts([
          'post_type' => 'voucher',
          'post_status' => 'publish',
          'posts_per_page' => 5,
          'orderby' => 'date',
          'order' => 'DESC'
      ]);

      echo '<pre>';
      foreach ($vouchers as $voucher) {
          echo "Voucher: {$voucher->post_title} (ID {$voucher->ID})\n";
          echo "voucher_code: " . get_post_meta($voucher->ID, 'voucher_code', true) . "\n";
          echo "redeemed: " . get_post_meta($voucher->ID, 'redeemed', true) . "\n";
          echo "user_id: " . get_post_meta($voucher->ID, 'user_id', true) . "\n";
          echo "qr_code_url: " . get_post_meta($voucher->ID, 'qr_code_url', true) . "\n";
          echo "email_customer (ACF): " . get_field('email_customer', $voucher->ID) . "\n";
          echo "voucher_id (ACF): " . get_field('voucher_id', $voucher->ID) . "\n";
          echo "price_voucher (ACF): " . get_field('price_voucher', $voucher->ID) . "\n";
          $qr_field = get_field('qr_code_voucher', $voucher->ID);
            if (is_array($qr_field)) {
                echo "qr_code_voucher (ACF): " . print_r($qr_field, true) . "\n";
            } else {
                echo "qr_code_voucher (ACF): " . $qr_field . "\n";
            }

          echo "---------\n";
      }
      echo '</pre>';
      exit;
  }
});

/**
 * Cria conta ao gerar voucher se e-mail não existir
 */
add_action('generate_vouchers_for_order', function($order_id) {
  $order = wc_get_order($order_id);
  $email = $order->get_billing_email();
  
  if (!email_exists($email)) {
      $username = sanitize_user(current(explode('@', $email)));
      $password = wp_generate_password();
      
      wp_create_user($username, $password, $email);
      
      // Envia e-mail com credenciais (opcional)
      wp_new_user_notification($user_id, null, 'both');
  }
}, 9, 1); // Prioridade 9 para executar antes da geração



// add_action('woocommerce_order_status_completed', 'generate_and_store_vouchers');

// function generate_and_store_vouchers($order_id) {
//     $order = wc_get_order($order_id);
//     $user_id = $order->get_user_id();

//     foreach ($order->get_items() as $item) {
//         $product = $item->get_product();

//         if ($product->is_virtual()) {
//             $qty = $item->get_quantity();

//             $client = new \Hidehalo\Nanoid\Client();

//             for ($i = 0; $i < $qty; $i++) {
//                 $voucher_code = $client->generateId(10);

//                 // Save as custom post
//                 wp_insert_post([
//                     'post_type' => 'voucher',
//                     'post_title' => 'Voucher ' . $voucher_code,
//                     'post_status' => 'publish',
//                     'meta_input' => [
//                         'voucher_code' => $voucher_code,
//                         'user_id' => $user_id,
//                         'order_id' => $order_id,
//                         'redeemed' => false,
//                     ]
//                 ]);
//             }
//         }
//     }
// }