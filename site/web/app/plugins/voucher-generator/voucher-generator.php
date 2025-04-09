<?php
/**
 * Plugin Name: Voucher Generator
 * Description: Gera vouchers após a finalização do pedido no WooCommerce e preenche os campos ACF.
 * Version: 1.5-debug
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Hidehalo\Nanoid\Client;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Teste de existência da classe
// if (!class_exists('Endroid\QrCode\QrCode')) {
//     error_log('[Voucher] Classe QrCode não encontrada!');
// } else {
//     error_log('[Voucher] Classe QrCode carregada com sucesso');
// }

/**
 * Função auxiliar para log de debug.
 */
function log_voucher_debug($msg) {
    error_log("[Voucher Debug] " . $msg);
}

add_action('woocommerce_order_status_completed', function ($order_id) {
    log_voucher_debug("Order completed hook triggered for order ID: $order_id");

    // Gera os vouchers imediatamente
    do_action('generate_vouchers_for_order', $order_id);
}, 10, 1);


/**
 * Função que gera os vouchers para um pedido.
 */
add_action('generate_vouchers_for_order', function ($order_id) {
    log_voucher_debug("Generating voucher for order: $order_id");

    $order = wc_get_order($order_id);
    if (!$order) {
        log_voucher_debug("Invalid order: $order_id");
        return;
    }

    $user_id = $order->get_user_id();
    $billing_email = $order->get_billing_email();

    // Para debug, removemos a checagem de is_virtual
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;

        $quantity = $item->get_quantity();
        $client = new Client();

        for ($i = 0; $i < $quantity; $i++) {
            $code = $client->generateId(10);
            log_voucher_debug("Generated code: $code");

            $voucher_id = wp_insert_post([
                'post_type'    => 'voucher',
                'post_title'   => 'Voucher ' . $code,
                'post_status'  => 'publish',
                'post_author'  => $user_id, // pode ser 0 se for guest
                'post_content' => '',
            ]);

            if (is_wp_error($voucher_id) || !$voucher_id) {
                log_voucher_debug("Error inserting voucher for code: $code");
                continue;
            }

            // Gera QR Code e salva como imagem
            try {
                $qrCode = QrCode::create($code)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setSize(300)
                ->setMargin(10)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

                $writer = new PngWriter();
                $result = $writer->write($qrCode);

                $upload_dir = wp_upload_dir();
                $filename = 'qr-code-' . $code . '.png';
                $filepath = $upload_dir['path'] . '/' . $filename;

                file_put_contents($filepath, $result->getString());

                // Anexa imagem ao WordPress
                $wp_filetype = wp_check_filetype($filename, null);
                $attachment = [
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title'     => sanitize_file_name($filename),
           'post_content'   => '',
           'post_status'    => 'inherit'
                ];
                $attach_id = wp_insert_attachment($attachment, $filepath);

                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
                wp_update_attachment_metadata($attach_id, $attach_data);

                $qr_url = wp_get_attachment_url($attach_id);
            } catch (Exception $e) {
                log_voucher_debug("Erro ao gerar QR Code: " . $e->getMessage());
                $qr_url = 'https://via.placeholder.com/150?text=' . $code;
                $attach_id = null;
            }

            update_post_meta($voucher_id, 'voucher_code', $code);
            update_post_meta($voucher_id, 'redeemed', false);
            update_post_meta($voucher_id, 'order_id', $order_id);
            update_post_meta($voucher_id, 'user_id', $user_id);
            update_post_meta($voucher_id, 'qr_code_url', $qr_url);

            log_voucher_debug("Saved post_meta for voucher ID $voucher_id with code: $code");

            if (function_exists('update_field')) {
                update_field('field_67f1c593d9d77', $billing_email, $voucher_id);         // email_customer
                update_field('field_67f1c5c5d9d78', $product->get_price(), $voucher_id);  // price_voucher
                update_field('field_67f1c669d9d7b', $code, $voucher_id);                  // voucher_id
                if ($attach_id) {
                    update_field('field_67f1c5e9d9d79', $attach_id, $voucher_id);         // qr_code_voucher (Image field)
                }
                log_voucher_debug("Updated ACF fields for voucher ID $voucher_id");
            } else {
                log_voucher_debug("update_field not available for voucher ID $voucher_id");
            }
        }
    }

}, 20);

/**
 * Shortcode para exibir vouchers: [user_vouchers]
 * Filtra por usuário logado OU por e-mail via parâmetro
 */
add_shortcode('user_vouchers', function ($atts) {
    $atts = shortcode_atts(['email' => ''], $atts);
    $email = sanitize_email($atts['email']);
    $current_user_id = get_current_user_id();
    $is_admin = current_user_can('manage_options');

    if (!$is_admin && !$current_user_id && !$email) {
        return '<div>Por favor, faça login ou informe seu e-mail para visualizar os vouchers.</div>';
    }

    $args = [
        'post_type'  => 'voucher',
        'numberposts' => -1,
        'meta_query' => []
    ];

    if ($is_admin) {
        // mostra todos
    } elseif ($current_user_id) {
        $args['meta_query'][] = [
            'key'   => 'user_id',
            'value' => $current_user_id
        ];
    } elseif ($email) {
        $args['meta_query'][] = [
            'key'   => 'email_customer',
            'value' => $email
        ];
    }

    $vouchers = get_posts($args);
    if (empty($vouchers)) {
        return '<div>Nenhum voucher encontrado.</div>';
    }

    // Agrupar vouchers por e-mail
    $grouped = [];
    foreach ($vouchers as $voucher) {
        $email_key = get_post_meta($voucher->ID, 'email_customer', true);
        if (!$email_key) {
            $user_id = get_post_meta($voucher->ID, 'user_id', true);
            if ($user_id) {
                $user_data = get_userdata($user_id);
                $email_key = $user_data ? $user_data->user_email : 'Desconhecido';
            } else {
                $email_key = 'Desconhecido';
            }
        }
        $grouped[$email_key][] = $voucher;
    }

    ob_start();
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">';

    foreach ($grouped as $email => $user_vouchers) {
        echo '<div style="border:1px solid #ccc; border-radius:6px; padding:15px; background:#f9f9f9;">';
        echo "<h3 style='text-align:center; font-size:16px; margin-bottom:15px;'>{$email}</h3>";

        foreach ($user_vouchers as $voucher) {
            $code     = get_post_meta($voucher->ID, 'voucher_code', true);
            $redeemed = get_post_meta($voucher->ID, 'redeemed', true);
            $qr       = get_post_meta($voucher->ID, 'qr_code_url', true);
            $created  = get_the_date('d/m/Y', $voucher);
            $status   = $redeemed
            ? '<span style="color:green; font-weight:bold;">✔ Usado</span>'
            : '<span style="color:red; font-weight:bold;">Disponível</span>';

            echo '<div style="border:1px solid #ddd; padding:10px; margin-bottom:10px; border-radius:5px; background:#fff;">';
            echo '<p><strong>Código:</strong> ' . esc_html($code) . '</p>';
            echo '<p><strong>Status:</strong> ' . $status . '</p>';
            echo '<p><strong>Data:</strong> ' . esc_html($created) . '</p>';
            if ($qr) {
                echo '<img src="' . esc_url($qr) . '" alt="QR Code" style="width:100px; margin-top:10px;">';
            } else {
                echo '<p><em>QR Code não disponível</em></p>';
            }
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
});




add_action('woocommerce_order_item_meta_end', function($item_id, $item, $order){
    $order_id = $order->get_id();

    // Busca vouchers associados a este pedido
    $vouchers = get_posts([
        'post_type'   => 'voucher',
        'numberposts' => -1,
        'meta_query'  => [
            [
                'key'   => 'order_id',
                'value' => $order_id
            ]
        ]
    ]);

    if (empty($vouchers)) return;

    echo '<div style="margin-top:20px;">';
    echo '<h3 style="margin-bottom:10px;">Vouchers Gerados</h3>';

    foreach ($vouchers as $voucher) {
        $code     = get_post_meta($voucher->ID, 'voucher_code', true);
        $redeemed = get_post_meta($voucher->ID, 'redeemed', true);
        $qr       = get_post_meta($voucher->ID, 'qr_code_url', true);
        $created  = get_the_date('d/m/Y', $voucher);
        $status   = $redeemed
        ? '<span style="color:green; font-weight:bold;">✔ Usado</span>'
        : '<span style="color:red; font-weight:bold;">Disponível</span>';

        echo '<div style="border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:8px; background:#f9f9f9;">';
        echo '<p><strong>Código do Voucher:</strong> ' . esc_html($code) . '</p>';
        echo '<p><strong>Status:</strong> ' . $status . '</p>';
        echo '<p><strong>Data de Geração:</strong> ' . esc_html($created) . '</p>';
        if ($qr) {
            echo '<img src="' . esc_url($qr) . '" alt="QR Code do Voucher" style="width:120px; margin-top:10px;">';
        } else {
            echo '<p><em>QR Code não disponível</em></p>';
        }
        echo '</div>';
    }

    echo '</div>';
}, 10, 3);



/**
 * Página de teste no admin para criação manual de voucher.
 */
add_action('admin_menu', function () {
    add_submenu_page('tools.php', 'Test Voucher', 'Test Voucher', 'manage_options', 'test-voucher', function () {
        if (isset($_POST['create_voucher'])) {
            $client = new Client();
            $code = $client->generateId(10);

            $voucher_id = wp_insert_post([
                'post_type'   => 'voucher',
                'post_title'  => 'Voucher ' . $code,
                'post_status' => 'publish',
            ]);

            $qr_url = 'https://via.placeholder.com/150?text=' . $code;

            update_post_meta($voucher_id, 'voucher_code', $code);
            update_post_meta($voucher_id, 'redeemed', false);
            update_post_meta($voucher_id, 'user_id', get_current_user_id());
            update_post_meta($voucher_id, 'qr_code_url', $qr_url);

            if (function_exists('update_field')) {
                update_field('field_67f1c593d9d77', 'test@example.com', $voucher_id);
                update_field('field_67f1c5c5d9d78', 999, $voucher_id);
                update_field('field_67f1c5e9d9d79', $qr_url, $voucher_id);
                update_field('field_67f1c669d9d7b', 50.00, $voucher_id);
            }

            echo '<div class="notice notice-success"><p>Voucher de teste criado com sucesso! Código: <strong>' . esc_html($code) . '</strong></p></div>';
        }

        echo '<div class="wrap"><h1>Testar geração de voucher</h1>';
        echo '<form method="post"><input type="submit" name="create_voucher" class="button button-primary" value="Criar voucher de teste"></form></div>';
    });
});

/**
 * Página de administração para resgatar vouchers.
 */
add_action('admin_menu', function () {
    add_menu_page('Redeem Voucher', 'Redeem Voucher', 'manage_options', 'redeem-voucher', 'render_redeem_voucher_page', 'dashicons-tickets-alt', 26);
});

function render_redeem_voucher_page() {
    echo '<div class="wrap"><h1>Resgatar Voucher</h1>';
    echo '<form method="post">';
    echo '<label for="voucher_code">Código do Voucher:</label><br>';
    echo '<input type="text" name="voucher_code" required style="width:300px"/><br><br>';
    echo '<input type="submit" name="redeem_voucher" class="button button-primary" value="Resgatar">';
    echo '</form>';

    if (isset($_POST['redeem_voucher'])) {
        $code = sanitize_text_field($_POST['voucher_code']);
        $voucher = get_posts([
            'post_type' => 'voucher',
            'meta_key' => 'voucher_code',
            'meta_value' => $code,
            'posts_per_page' => 1
        ]);

        if ($voucher) {
            $voucher_id = $voucher[0]->ID;
            $already_redeemed = get_post_meta($voucher_id, 'redeemed', true);

            if ($already_redeemed) {
                echo '<div class="notice notice-error"><p>Este voucher já foi resgatado.</p></div>';
            } else {
                update_post_meta($voucher_id, 'redeemed', true);
                echo '<div class="notice notice-success"><p>Voucher ' . esc_html($code) . ' resgatado com sucesso.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Voucher não encontrado.</p></div>';
        }
    }
    echo '</div>';
}

/**
 * Endpoint REST API para resgatar voucher via POST /wp-json/voucher/v1/redeem
 */
add_action('rest_api_init', function () {
    register_rest_route('voucher/v1', '/redeem', [
        'methods' => 'POST',
        'callback' => 'voucher_api_redeem',
        'permission_callback' => '__return_true',
    ]);
});

function voucher_api_redeem($request) {
    $code = sanitize_text_field($request['code']);

    if (!$code) {
        return new WP_Error('no_code', 'Nenhum código fornecido.', ['status' => 400]);
    }

    $voucher = get_posts([
        'post_type' => 'voucher',
        'meta_key' => 'voucher_code',
        'meta_value' => $code,
        'posts_per_page' => 1
    ]);

    if (!$voucher) {
        return new WP_Error('not_found', 'Voucher não encontrado.', ['status' => 404]);
    }

    $voucher_id = $voucher[0]->ID;
    $already_redeemed = get_post_meta($voucher_id, 'redeemed', true);

    if ($already_redeemed) {
        return new WP_Error('already_redeemed', 'Voucher já foi resgatado.', ['status' => 409]);
    }

    update_post_meta($voucher_id, 'redeemed', true);

    return [
        'success' => true,
        'message' => 'Voucher resgatado com sucesso.',
        'code' => $code,
    ];
}
