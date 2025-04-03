<?php

add_action('wp_ajax_submit_loja_form', 'handle_loja_form_submission');
add_action('wp_ajax_nopriv_submit_loja_form', 'handle_loja_form_submission');

function handle_loja_form_submission()
{
    // Verify nonce
    if (!isset($_POST['acf_nonce']) || !wp_verify_nonce($_POST['acf_nonce'], 'acf_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    // Get nome_estabelecimento value first
    $nome_estabelecimento = isset($_POST['acf']['field_67eb5b4d107f4'])
        ? sanitize_text_field($_POST['acf']['field_67eb5b4d107f4'])
        : 'Sem Nome'; // Fallback if empty

    // Create post WITH the correct title immediately
    $post_id = wp_insert_post([
        'post_type'    => 'cadastro-loja',
        'post_status'  => 'publish',
        'post_title'   => $nome_estabelecimento, // Set title here instead of later
    ]);

    if (is_wp_error($post_id)) {
        wp_send_json_error('Falha ao criar cadastro');
    }

    // Save ACF fields
    if (isset($_POST['acf'])) {
        foreach ($_POST['acf'] as $key => $value) {
            update_field($key, $value, $post_id);
        }
    }

    wp_send_json_success();
}

// trigger voucher creation 
add_action('ywsbs_subscription_status_active', function($subscription_id) {
    $subscription = ywsbs_get_subscription($subscription_id);
    $product_id = $subscription->product_id;

    // Check if product is properly configured as subscription
    $product = wc_get_product($product_id);
    if ($product && $product->is_type('subscription')) {
    error_log("Product {$product_id} is subscription type");
    } else {
    error_log("Product {$product_id} is NOT subscription type");
    }
    
    // Get ACF fields
    $voucher_count = get_field('voucher_count', $product_id) ?: 5;
    $prefix = get_field('voucher_prefix', $product_id) ?: 'BOGO_';
    
    for ($i = 0; $i < $voucher_count; $i++) {
      $voucher_code = $prefix . generate_nanoid(); // Your Nanoid function
      
      // Create voucher post
      $voucher_id = wp_insert_post([
        'post_type' => 'voucher',
        'post_title' => 'Voucher - ' . $voucher_code,
        'post_status' => 'publish'
      ]);
      
      // Set ACF fields
      update_field('voucher_code', $voucher_code, $voucher_id);
      update_field('voucher_status', 'active', $voucher_id);
      update_field('qr_code', generate_qr_code($voucher_code), $voucher_id);
      
      // Link to subscription
      update_post_meta($voucher_id, 'subscription_id', $subscription_id);
      update_post_meta($voucher_id, 'customer_id', $subscription->user_id);
    }
  });

// Voucher Dashboard
add_filter('ywsbs_my_account_subscription_actions', function($actions, $subscription) {
    $actions['view_vouchers'] = [
      'url' => wc_get_account_endpoint_url('vouchers'),
      'name' => __('View Vouchers', 'your-textdomain')
    ];
    return $actions;
  }, 10, 2);

  // Admin interface for restaurants
add_action('admin_menu', function() {
    add_menu_page(
      'Voucher Redemption',
      'Voucher Redemption',
      'edit_shop_orders', // WooCommerce order management cap
      'voucher-redemption',
      'render_voucher_redemption_page',
      'dashicons-tickets-alt',
      30
    );
  });
  
  function render_voucher_redemption_page() {
    ?>
    <div class="wrap">
      <h1 class="text-2xl font-bold mb-6">Redeem Vouchers</h1>
      
      <div class="bg-white p-6 rounded-lg shadow-md max-w-md">
        <form id="voucher-redemption-form">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Scan QR Code</label>
            <video id="qr-scanner" class="w-full border"></video>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Or Enter Code</label>
            <input type="text" id="voucher-code" class="w-full border p-2 rounded">
          </div>
          
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Redeem Voucher
          </button>
        </form>
        
        <div id="redemption-result" class="mt-4 hidden p-4 rounded"></div>
      </div>
    </div>
    
    <script>
    // QR scanner and AJAX redemption logic
    </script>
    <?php
  }

// Restaurant Redemption System
    
    // Custom Admin Interface:
    add_action('ywsbs_subscription_admin_after_details', function($subscription) {
        // Display voucher status/redemption UI for restaurant staff
      });

    // Voucher Validation Endpoint
    add_action('rest_api_init', function() {
        register_rest_route('bogo/v1', '/redeem', [
          'methods' => 'POST',
          'callback' => function(WP_REST_Request $request) {
            $code = sanitize_text_field($request->get_param('code'));
            $user_id = get_current_user_id();
            
            // Find voucher
            $vouchers = get_posts([
              'post_type' => 'voucher',
              'meta_query' => [
                ['key' => 'voucher_code', 'value' => $code],
                ['key' => 'voucher_status', 'value' => 'active']
              ]
            ]);
            
            if (empty($vouchers)) {
              return new WP_Error('invalid', 'Invalid or already redeemed voucher');
            }
            
            $voucher_id = $vouchers[0]->ID;
            
            // Verify subscription is active
            $subscription_id = get_post_meta($voucher_id, 'subscription_id', true);
            if (!ywsbs_is_subscription_active($subscription_id)) {
              return new WP_Error('expired', 'Subscription no longer active');
            }
            
            // Update voucher
            update_field('voucher_status', 'redeemed', $voucher_id);
            update_post_meta($voucher_id, 'redeemed_by', $user_id);
            update_post_meta($voucher_id, 'redeemed_at', current_time('mysql'));
            
            return [
              'success' => true,
              'data' => [
                'voucher_code' => $code,
                'customer' => get_the_author_meta('display_name', get_post_meta($voucher_id, 'customer_id', true))
              ]
            ];
          },
          'permission_callback' => function() {
            return current_user_can('edit_shop_orders'); // WooCommerce capability
          }
        ]);
      });
    
    // QR Validation Endpoint
    add_action('rest_api_init', function() {
        register_rest_route('vouchers/v1', '/validate', [
          'methods' => 'POST',
          'callback' => function($request) {
            $code = $request['code'];
            // Check against yith_subscription_vouchers table
            // Verify subscription is active via YITH's API
          },
          'permission_callback' => function() {
            return current_user_can('restaurant_staff_role');
          }
        ]);
      });

    // Automated Expiry & Syncing
    add_action('ywsbs_subscription_status_expired', function($subscription_id) {
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}yith_subscription_vouchers", 
          ['status' => 'expired'],
          ['subscription_id' => $subscription_id]
        );
      });

// ACF Fields and Post Type
if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
      'key' => 'group_voucher_settings',
      'title' => 'Subscription Voucher Settings',
      'fields' => [
        [
          'key' => 'field_voucher_count',
          'label' => 'Number of Vouchers',
          'name' => 'voucher_count',
          'type' => 'number',
          'default_value' => 5,
          'min' => 1
        ],
        [
          'key' => 'field_voucher_prefix',
          'label' => 'Voucher Code Prefix',
          'name' => 'voucher_prefix',
          'type' => 'text',
          'placeholder' => 'BOGO_'
        ]
      ],
      'location' => [
        [
          [
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'product',
          ],
          [
            'param' => 'product_type',
            'operator' => '==',
            'value' => 'subscription',
          ]
        ]
      ]
    ]);
  }

  // Voucher Details
  acf_add_local_field_group([
    'key' => 'group_voucher_details',
    'title' => 'Voucher Details',
    'fields' => [
      [
        'key' => 'field_voucher_code',
        'label' => 'Voucher Code',
        'name' => 'voucher_code',
        'type' => 'text',
        'readonly' => true
      ],
      [
        'key' => 'field_voucher_status',
        'label' => 'Status',
        'name' => 'voucher_status',
        'type' => 'select',
        'choices' => [
          'active' => 'Active',
          'redeemed' => 'Redeemed',
          'expired' => 'Expired'
        ],
        'default_value' => 'active'
      ],
      [
        'key' => 'field_qr_code',
        'label' => 'QR Code',
        'name' => 'qr_code',
        'type' => 'image',
        'return_format' => 'url'
      ]
    ],
    'location' => [[[
      'param' => 'post_type',
      'operator' => '==',
      'value' => 'voucher',
    ]]]
  ]);

// Force Database Table Creation
add_action('admin_init', function() {
    if (!get_option('yith_ywsbs_db_created')) {
        // Initialize the installer
        if (class_exists('YITH_WC_Subscription_Installer')) {
            $installer = YITH_WC_Subscription_Installer();
            $installer->init();
            $installer->install();
        }
        
        // Alternative method for newer versions
        if (function_exists('yith_ywsbs_install')) {
            yith_ywsbs_install();
        }
        
        update_option('yith_ywsbs_db_created', time());
    }
});

// Debugging

add_action('template_redirect', function() {
    if (is_checkout()) {
      $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
      error_log(print_r(array_keys($available_gateways), true));
    }
  });

  add_action('ywsbs_subscription_status_active', function($subscription_id) {
    error_log("Subscription created: {$subscription_id}");
  }, 10, 1);
  
  add_action('woocommerce_checkout_order_processed', function($order_id) {
    error_log("Order processed: {$order_id}");
    $order = wc_get_order($order_id);
    error_log("Order contains subscriptions: " . print_r(YWSBS_Subscription_Order()->get_subscriptions($order_id), true));
  });

  add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    error_log("Order {$order_id} changed from {$old_status} to {$new_status}");
    $subscriptions = YWSBS_Subscription_Helper()->get_subscriptions_by_order($order_id);
    error_log("Associated subscriptions: " . print_r($subscriptions, true));
  }, 10, 3);

  add_action('init', function() {
    if (current_user_can('manage_woocommerce')) {
      error_log("Current user can manage subscriptions");
    } else {
      error_log("Current user CANNOT manage subscriptions");
    }
  });

  // Delete existing options
delete_option('yith_ywsbs_version');
delete_option('yith_ywsbs_db_version');

// Remove scheduled cron
wp_clear_scheduled_hook('yith_ywsbs_check_subscriptions_status');

// Full reinstallation
if (class_exists('YITH_WC_Subscription_Installer')) {
    $installer = new YITH_WC_Subscription_Installer();
    $installer->init();
    $installer->install();
    
    // Force initial data
    $installer->create_roles();
    $installer->setup_analytics();
    $installer->create_upload_dir();
}

add_action('admin_notices', function() {
    echo '<div class="notice notice-info">';
    echo '<p>PHP Version: ' . phpversion() . '</p>';
    echo '<p>YITH Subscription Version: ' . YITH_YWSBS_VERSION . '</p>';
    echo '</div>';
});