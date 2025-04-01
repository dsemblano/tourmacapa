<?php
  
  add_action('wp_ajax_submit_loja_form', 'handle_loja_form_submission');
add_action('wp_ajax_nopriv_submit_loja_form', 'handle_loja_form_submission');

function handle_loja_form_submission() {
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

// Creating nanoid tables
function create_nanoid_codes_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nanoid_codes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        code varchar(100) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        user_id bigint(20) DEFAULT NULL,
        subscription_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_nanoid_codes_table');

// Subscription

function generate_vouchers_on_subscription_creation($subscription_id) {
    $subscription = ywsbs_get_subscription($subscription_id); // Get subscription object
    $user_id = $subscription->user_id; // Get user ID
    $voucher_count = 10; // Number of vouchers to generate

    for ($i = 1; $i <= $voucher_count; $i++) {
        $coupon_code = 'TUCUFOOD' . strtoupper(wp_generate_password(6, false)); // Generate a unique code
        $amount = '100'; // 100% discount (free meal)
        $discount_type = 'percent'; // Discount type

        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );

        $new_coupon_id = wp_insert_post($coupon);

        // Add coupon meta
        update_post_meta($new_coupon_id, 'discount_type', $discount_type);
        update_post_meta($new_coupon_id, 'coupon_amount', $amount);
        update_post_meta($new_coupon_id, 'individual_use', 'yes');
        update_post_meta($new_coupon_id, 'usage_limit', '1'); // Limit to one use
        update_post_meta($new_coupon_id, 'expiry_date', date('Y-m-d', strtotime('+1 month'))); // Set expiry date
        update_post_meta($new_coupon_id, 'customer_email', $subscription->billing_email); // Assign to user
    }
}

add_action('ywsbs_subscription_created', 'generate_vouchers_on_subscription_creation');

// Validation voucher
function voucher_validation_form() {
    if (isset($_POST['voucher_code'])) {
        $voucher_code = sanitize_text_field($_POST['voucher_code']);
        $coupon = new WC_Coupon($voucher_code);

        if ($coupon->get_id() && $coupon->get_usage_count() < $coupon->get_usage_limit()) {
            echo '<p>Voucher is valid!</p>';
            // Mark voucher as used
            $coupon->set_usage_count($coupon->get_usage_count() + 1);
            $coupon->save();
        } else {
            echo '<p>Invalid voucher or already used.</p>';
        }
    }

    echo '
    <form method="post">
        <label for="voucher_code">Enter Voucher Code:</label>
        <input type="text" name="voucher_code" id="voucher_code" required>
        <button type="submit">Validate</button>
    </form>
    ';
}

add_shortcode('voucher_validation', 'voucher_validation_form');

// Display nanoid codes
add_shortcode('display_nanoid_codes', 'display_nanoid_codes_shortcode');

function display_nanoid_codes_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nanoid_codes';
    $user_id = get_current_user_id();

    if (!$user_id) {
        return '<p>Please log in to view your codes.</p>';
    }

    // Fetch codes for the current user
    $codes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND status = 'active'",
        $user_id
    ));

    if (empty($codes)) {
        return '<p>No codes found.</p>';
    }

    // Display the codes
    $output = '<ul>';
    foreach ($codes as $code) {
        $output .= '<li>' . esc_html($code->code) . '</li>';
    }
    $output .= '</ul>';

    return $output;
}

// login codes
add_shortcode('view_my_codes', 'view_my_codes_shortcode');

function view_my_codes_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nanoid_codes';
    $user_id = get_current_user_id();

    // Check if the user is logged in
    if (!$user_id) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to view your codes.</p>';
    }

    // Fetch codes for the current user
    $codes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ));

    if (empty($codes)) {
        return '<p>No codes found for your account.</p>';
    }

    // Display the codes in a table
    $output = '<h3>Your Generated Codes</h3>';
    $output .= '<table class="nanoid-codes-table">';
    $output .= '<thead><tr><th>Code</th><th>Status</th><th>Created At</th></tr></thead>';
    $output .= '<tbody>';

    foreach ($codes as $code) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($code->code) . '</td>';
        $output .= '<td>' . esc_html($code->status) . '</td>';
        $output .= '<td>' . esc_html($code->created_at) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';

    return $output;
}

// criando a tabela para os códigos únicos - vouchers 
function create_recovery_codes_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'recovery_codes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        code varchar(100) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        user_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_recovery_codes_table');

// Gera o código quando um cliente se inscreve
add_action('ywsbs_subscription_created', 'generate_recovery_codes_on_subscription', 10, 1);

function generate_recovery_codes_on_subscription($subscription_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'recovery_codes';

    // Number of codes to generate
    $number_of_codes = 5; // Change this to the desired number of codes

    // Get the user ID from the subscription
    $subscription = ywsbs_get_subscription($subscription_id);
    $user_id = $subscription->user_id;

    // Generate and insert the codes
    for ($i = 0; $i < $number_of_codes; $i++) {
        $code = wp_generate_password(12, false); // Generate a unique code

        // Insert the code into the database
        $wpdb->insert(
            $table_name,
            array(
                'code' => $code,
                'user_id' => $user_id,
            )
        );
    }
}

// Admin page para os códigos
add_action('admin_menu', 'recovery_codes_admin_menu');

function recovery_codes_admin_menu() {
    add_menu_page(
        'Recovery Codes',
        'Recovery Codes',
        'manage_options',
        'recovery-codes',
        'recovery_codes_admin_page'
    );
}

function recovery_codes_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'recovery_codes';

    // Fetch all codes
    $codes = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">';
    echo '<h1>Recovery Codes</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Code</th><th>Status</th><th>User ID</th><th>Created At</th></tr></thead>';
    echo '<tbody>';
    foreach ($codes as $code) {
        echo '<tr>';
        echo '<td>' . esc_html($code->id) . '</td>';
        echo '<td>' . esc_html($code->code) . '</td>';
        echo '<td>' . esc_html($code->status) . '</td>';
        echo '<td>' . esc_html($code->user_id) . '</td>';
        echo '<td>' . esc_html($code->created_at) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

// Shortcode para a página de redençao - geralmente para o restaurante
add_shortcode('recovery_code_redemption', 'recovery_code_redemption_page');

function recovery_code_redemption_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recovery_code'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'recovery_codes';
        $code = sanitize_text_field($_POST['recovery_code']);

        // Check if the code exists and is active
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s AND status = 'active'",
            $code
        ));

        if ($result) {
            // Mark the code as used
            $wpdb->update(
                $table_name,
                array('status' => 'used'),
                array('id' => $result->id)
            );

            echo '<p>Code redeemed successfully!</p>';
        } else {
            echo '<p>Invalid or already used code.</p>';
        }
    }

    ob_start();
    ?>
    <form method="post">
        <label for="recovery_code">Digite o voucher:</label>
        <input type="text" name="recovery_code" id="recovery_code" required>
        <button type="submit">Redeem</button>
    </form>
    <?php
    return ob_get_clean();
}
