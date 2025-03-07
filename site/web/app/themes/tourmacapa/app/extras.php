<?php
function create_nanoid_codes_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nanoid_codes';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL query to create the table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        code varchar(100) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        user_id bigint(20) DEFAULT NULL,
        subscription_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Include the upgrade file and run dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Log the result
    error_log('NanoID codes table created or updated.'); // Debug log
}
register_activation_hook(__FILE__, 'create_nanoid_codes_table');

// hooking into sub plugin
use Hidehalo\Nanoid\Client;

add_action('ywsbs_subscription_created', 'generate_nanoid_codes_on_subscription', 10, 1);

function generate_nanoid_codes_on_subscription($subscription_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'nanoid_codes';

    // Get the subscription and user ID
    $subscription = ywsbs_get_subscription($subscription_id);
    if (!$subscription) {
        error_log('Subscription not found: ' . $subscription_id);
        return;
    }
    $user_id = $subscription->user_id;

    // Initialize NanoID client
    $client = new Client();
    $number_of_codes = 5; // Number of codes to generate

    // Generate and insert the codes
    for ($i = 0; $i < $number_of_codes; $i++) {
        $code = $client->generateId($size = 8, $mode = Client::MODE_DYNAMIC); // Generate a NanoID

        // Insert the code into the database
        $wpdb->insert(
            $table_name,
            array(
                'code' => $code,
                'user_id' => $user_id,
                'subscription_id' => $subscription_id,
            )
        );

        error_log('NanoID code generated: ' . $code); // Debug log
    }
}

// My codes page
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

// Page with all users with cupom codes
add_shortcode('view_all_subscriptions_and_codes', 'view_all_subscriptions_and_codes_shortcode');

function view_all_subscriptions_and_codes_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nanoid_codes';

    // Fetch all subscriptions and codes
    $results = $wpdb->get_results("
        SELECT 
            u.user_login AS username,
            u.display_name AS display_name,
            n.code AS code,
            n.status AS status,
            n.created_at AS created_at
        FROM {$table_name} n
        INNER JOIN {$wpdb->users} u ON n.user_id = u.ID
        ORDER BY n.created_at DESC
    ");

    if (empty($results)) {
        return '<p>No subscriptions or codes found.</p>';
    }

    // Display the data in a table
    $output = '<h3>All Subscriptions and Generated Codes</h3>';
    $output .= '<table class="subscriptions-codes-table">';
    $output .= '<thead><tr><th>Username</th><th>Display Name</th><th>Code</th><th>Status</th><th>Created At</th></tr></thead>';
    $output .= '<tbody>';

    foreach ($results as $row) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($row->username) . '</td>';
        $output .= '<td>' . esc_html($row->display_name) . '</td>';
        $output .= '<td>' . esc_html($row->code) . '</td>';
        $output .= '<td>' . esc_html($row->status) . '</td>';
        $output .= '<td>' . esc_html($row->created_at) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';

    return $output;
}

// User page by voucher codes
function custom_voucher_rewrite_rule() {
    add_rewrite_rule(
        '^vouchers/([^/]+)/?$', // Match URLs like /vouchers/username
        'index.php?pagename=vouchers&username=$matches[1]', // Pass the username as a query variable
        'top'
    );
}
add_action('init', 'custom_voucher_rewrite_rule');

// Add the username query variable
function custom_add_query_vars($vars) {
    $vars[] = 'username'; // Add 'username' to the list of query variables
    return $vars;
}
add_filter('query_vars', 'custom_add_query_vars');

// List all users with links to codes
add_shortcode('list_users_with_voucher_links', 'list_users_with_voucher_links_shortcode');

function list_users_with_voucher_links_shortcode() {
    $users = get_users(array(
        'fields' => array('ID', 'user_login', 'display_name'),
    ));

    if (empty($users)) {
        return '<p>No users found.</p>';
    }

    $output = '<h3>List of Users</h3>';
    $output .= '<ul>';

    foreach ($users as $user) {
        $voucher_url = home_url("/vouchers/{$user->user_login}/");
        $output .= '<li><a href="' . esc_url($voucher_url) . '">' . esc_html($user->display_name) . '</a></li>';
    }

    $output .= '</ul>';

    return $output;
}


// redemption page
add_shortcode('redeem_nanoid_code', 'redeem_nanoid_code_shortcode');

function redeem_nanoid_code_shortcode()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nanoid_code'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nanoid_codes';
        $code = sanitize_text_field($_POST['nanoid_code']);

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
        <label for="nanoid_code">Enter NanoID Code:</label>
        <input type="text" name="nanoid_code" id="nanoid_code" required>
        <button type="submit">Redeem</button>
    </form>
<?php
    return ob_get_clean();
}
?>
