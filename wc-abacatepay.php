<?php
/*
Plugin Name: Abacate Pay PIX - AlfaStageLabs
Plugin URI: https://github.com/AlfaStage/wc-abacatepay
Description: Integração PIX com correção na gravação de IDs e busca robusta no Webhook.
Version: 5.2
Author: AlfaStageLabs
Author URI: https://github.com/AlfaStage
Text Domain: wc-abacatepay
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Registro e Limpeza do Cron Job
register_activation_hook( __FILE__, 'alfastage_abacate_activate' );
register_deactivation_hook( __FILE__, 'alfastage_abacate_deactivate' );

function alfastage_abacate_activate() {
    if ( ! wp_next_scheduled( 'abacatepay_check_expired_orders' ) ) {
        wp_schedule_event( time(), 'every_minute', 'abacatepay_check_expired_orders' );
    }
}

function alfastage_abacate_deactivate() {
    $timestamp = wp_next_scheduled( 'abacatepay_check_expired_orders' );
    if ( $timestamp ) { wp_unschedule_event( $timestamp, 'abacatepay_check_expired_orders' ); }
}

add_filter( 'cron_schedules', function ( $schedules ) {
    $schedules['every_minute'] = array( 'interval' => 60, 'display' => 'Cada 1 Minuto' );
    return $schedules;
} );

// 2. Função Cron para cancelar pedidos expirados
add_action( 'abacatepay_check_expired_orders', 'alfastage_process_expired_orders' );
function alfastage_process_expired_orders() {
    $orders = wc_get_orders( array( 'limit' => 20, 'status' => 'on-hold', 'payment_method' => 'abacatepay' ) );
    foreach ( $orders as $order ) {
        $expires_at = $order->get_meta( '_abacate_pix_expires_at' );
        if ( $expires_at && time() > strtotime( $expires_at ) ) {
            $order->update_status( 'cancelled', 'Cancelado: Tempo do PIX expirou.' );
        }
    }
}

// 3. Inicialização do Gateway
add_action( 'plugins_loaded', 'alfastage_init_gateway_class' );

function alfastage_init_gateway_class() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    class WC_AbacatePay_Gateway extends WC_Payment_Gateway {
        public $api_key;
        public $webhook_secret;
        public $expiration_minutes;

        public function __construct() {
            $this->id = 'abacatepay'; 
            $this->icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciICB2aWV3Qm94PSIwIDAgNDggNDgiIHdpZHRoPSIxNTBweCIgaGVpZ2h0PSIxNTBweCIgYmFzZVByb2ZpbGU9ImJhc2ljIj48cGF0aCBmaWxsPSIjNGRiNmFjIiBkPSJNMTEuOSwxMmgtMC42OGw4LjA0LTguMDRjMi42Mi0yLjYxLDYuODYtMi42MSw5LjQ4LDBMMzYuNzgsMTJIMzYuMWMtMS42LDAtMy4xMSwwLjYyLTQuMjQsMS43NglsLTYuOCw2Ljc3Yy0wLjU5LDAuNTktMS41MywwLjU5LTIuMTIsMGwtNi44LTYuNzdDMTUuMDEsMTIuNjIsMTMuNSwxMiwxMS45LDEyeiIvPjxwYXRoIGZpbGw9IiM0ZGI2YWMiIGQ9Ik0zNi4xLDM2aDAuNjhsLTguMDQsOC4wNGMtMi42MiwyLjYxLTYuODYsMi42MS05LjQ4LDBMMTEuMjIsMzZoMC42OGMxLjYsMCwzLjExLTAuNjIsNC4yNC0xLjc2CWw2LjgtNi43N2MwLjU5LTAuNTksMS41My0wLjU5LDIuMTIsMGw2LjgsNi43N0MzMi45OSwzNS4zOCwzNC41LDM2LDM2LjEsMzZ6Ii8+PHBhdGggZmlsbD0iIzRkYjZhYyIgZD0iTTQ0LjA0LDI4Ljc0TDM4Ljc4LDM0SDM2LjFjLTEuMDcsMC0yLjA3LTAuNDItMi44My0xLjE3bC02LjgtNi43OGMtMS4zNi0xLjM2LTMuNTgtMS4zNi00Ljk0LDAJbC02LjgsNi43OEMxMy45NywzMy41OCwxMi45NywzNCwxMS45LDM0SDkuMjJsLTUuMjYtNS4yNmMtMi42MS0yLjYyLTIuNjEtNi44NiwwLTkuNDhMOS4yMiwxNGgyLjY4YzEuMDcsMCwyLjA3LDAuNDIsMi44MywxLjE3CWw2LjgsNi43OGMwLjY4LDAuNjgsMS41OCwxLjAyLDIuNDcsMS4wMnMxLjc5LTAuMzQsMi40Ny0xLjAybDYuOC02Ljc4QzM0LjAzLDE0LjQyLDM1LjAzLDE0LDM2LjEsMTRoMi42OGw1LjI2LDUuMjYJQzQ2LjY1LDIxLjg4LDQ2LjY1LDI2LjEyLDQ0LjA0LDI4Ljc0eiIvPjwvc3ZnPg==';
            $this->method_title = 'Abacate Pay - PIX';
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->api_key = $this->get_option( 'api_key' );
            $this->webhook_secret = $this->get_option( 'webhook_secret' );
            $this->expiration_minutes = $this->get_option( 'expiration_minutes', '15' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'show_pix_on_thankyou_page' ) );
            add_action( 'woocommerce_api_wc_abacatepay_gateway', array( $this, 'webhook_handler' ) );
            add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_abacate_id_in_admin' ) );
        }

        public function init_form_fields() {
            $base_webhook = add_query_arg( 'wc-api', 'wc_abacatepay_gateway', home_url( '/' ) );
            $secret = $this->get_option( 'webhook_secret' );
            $final_url = !empty($secret) ? $base_webhook . '?webhookSecret=' . $secret : $base_webhook;
            $webhook_desc = empty($secret) ? '<b style="color: #d63638;">⚠️ Salve uma senha primeiro!</b>' : 'Copie e cole no painel do Abacate Pay.';

            $this->form_fields = array(
                'enabled' => array( 'title' => 'Habilitar', 'type' => 'checkbox', 'default' => 'yes' ),
                'title' => array( 'title' => 'Título', 'type' => 'text', 'default' => 'PIX (Aprovação Imediata)' ),
                'description' => array( 'title' => 'Descrição', 'type' => 'textarea', 'default' => 'Pague com o app do seu banco lendo o QR Code.' ),
                'expiration_minutes' => array( 'title' => 'Tempo de Expiração (Min)', 'type' => 'number', 'default' => '15' ),
                'api_key' => array( 'title' => 'API Token', 'type' => 'password' ),
                'webhook_secret' => array( 'title' => 'Senha do Webhook', 'type' => 'text', 'default' => wp_generate_password(10, false) ),
                'webhook_url_clean' => array(
                    'title' => 'URL do Webhook', 'type' => 'text', 'description' => $webhook_desc, 'default' => $final_url,
                    'custom_attributes' => array('readonly' => 'readonly', 'onclick' => 'this.select(); document.execCommand("copy"); alert("URL Copiada!");'),
                    'css' => 'background-color: #f0f0f1; cursor: copy; width: 100%; font-family: monospace;'
                )
            );
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            try {
                $payload = array(
                    'amount' => intval( round( $order->get_total() * 100 ) ),
                    'expiresIn' => intval( $this->expiration_minutes ) * 60,
                    'description' => 'Pedido ' . $order_id,
                    'metadata' => array( 'externalId' => (string) $order_id )
                );
                $response = wp_remote_post( 'https://api.abacatepay.com/v1/pixQrCode/create', array(
                    'method' => 'POST',
                    'headers' => array( 'Authorization' => 'Bearer ' . $this->api_key, 'Content-Type' => 'application/json' ),
                    'body' => json_encode( $payload ), 'timeout' => 30
                ));
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                $data = $body['data'] ?? ($body[0]['data'] ?? null);

                if ( $data && isset($data['id']) ) {
                    // Salva de forma robusta e garante que apareça nos campos personalizados
                    $order->update_meta_data( '_abacate_pix_code', $data['brCode'] );
                    $order->update_meta_data( '_abacate_pix_id', $data['id'] );
                    $order->update_meta_data( 'abacate_transaction_id', $data['id'] ); // Campo Visível
                    $order->update_meta_data( '_abacate_pix_base64', $data['brCodeBase64'] ?? '' );
                    $order->update_meta_data( '_abacate_pix_expires_at', $data['expiresAt'] ?? '' );
                    
                    $order->update_status( 'on-hold', 'Aguardando PIX. ID: ' . $data['id'] );
                    $order->save();
                    
                    // Força a gravação no banco de dados clássico por segurança
                    update_post_meta( $order_id, 'abacate_transaction_id', $data['id'] );

                    WC()->cart->empty_cart();
                    return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
                }
                wc_add_notice( 'Erro ao gerar PIX. Tente novamente.', 'error' );
                return array('result' => 'fail');
            } catch ( Exception $e ) { return array('result' => 'fail'); }
        }

        public function show_pix_on_thankyou_page( $order_id ) {
            $order = wc_get_order( $order_id );
            if( $order->has_status('cancelled') ) {
                echo '<div style="background: #ffebeb; padding: 20px; text-align:center;"><h3>Pedido Expirado</h3></div>';
                return;
            }
            if(!$order || $order->get_payment_method() !== $this->id || $order->is_paid()) return;

            $code = $order->get_meta( '_abacate_pix_code' );
            $b64 = $order->get_meta( '_abacate_pix_base64' );
            $expires_at = $order->get_meta( '_abacate_pix_expires_at' );
            $src = (strpos($b64, 'data:') === 0) ? $b64 : 'data:image/png;base64,'.$b64;
            ?>
            <div id="abacate-pix-box" class="abacate-pix-container" style="margin: 30px 0; padding: 25px; border: 2px solid #e1e1e1; border-radius: 8px; text-align: center; background: #fff;">
                <h3>Pague com PIX</h3>
                <?php if($expires_at): ?>
                    <div id="pix-countdown" style="font-size: 1.2em; color: #d63638; font-weight: bold; margin-bottom: 15px;">Expira em: <span id="timer-display">--:--</span></div>
                <?php endif; ?>
                <img src="<?php echo esc_attr($src); ?>" style="max-width: 250px; margin: 0 auto 15px; display:block;">
                <textarea id="pix-code-field" readonly style="width: 100%; height: 80px; font-size: 12px;"><?php echo esc_textarea($code); ?></textarea>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('pix-code-field').value); alert('Copiado!');" style="margin-top:10px; padding: 10px 20px; background: #2271b1; color: #fff; border:none; border-radius:4px; cursor:pointer;">Copiar Código</button>
            </div>
            
            <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var expiresAt = new Date("<?php echo esc_js($expires_at); ?>").getTime();
                var timer = setInterval(function() {
                    var distance = expiresAt - new Date().getTime();
                    if (distance < 0) { clearInterval(timer); location.reload(); return; }
                    var m = Math.floor((distance % 3600000) / 60000), s = Math.floor((distance % 60000) / 1000);
                    document.getElementById("timer-display").innerHTML = (m<10?"0"+m:m) + ":" + (s<10?"0"+s:s);
                }, 1000);

                var orderId = <?php echo $order_id; ?>;
                var checkPaid = setInterval(function() {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=alfastage_check_paid&order_id=' + orderId)
                    .then(response => response.json())
                    .then(data => { if(data.paid) { clearInterval(checkPaid); location.reload(); } });
                }, 5000);
            });
            </script>
            <?php
        }

        public function display_abacate_id_in_admin( $order ) {
            $tx_id = $order->get_meta( 'abacate_transaction_id' );
            if ( ! $tx_id ) $tx_id = $order->get_meta( '_abacate_pix_id' );
            if ( $tx_id ) echo '<p><strong>Abacate Pay ID:</strong> <br><code>' . esc_html( $tx_id ) . '</code></p>';
        }

        public function webhook_handler() {
            $logger = wc_get_logger();
            $s = $this->get_option('webhook_secret');
            if(empty($s) || ($_GET['webhookSecret'] ?? '') !== $s) { status_header(403); exit; }
            
            $payload = file_get_contents('php://input');
            $d = json_decode($payload, true);
            $logger->info( 'WEBHOOK RECEBIDO: ' . $payload, array( 'source' => 'abacatepay' ) );

            // Tenta pegar o ID da transação em todos os lugares possíveis
            $tx_id = $d['data']['pixQrCode']['id'] ?? ($d['data']['billing']['id'] ?? ($d['data']['id'] ?? null));
            
            // Tenta pegar o ID do pedido via metadata
            $order_id = $d['data']['metadata']['externalId'] ?? null;

            $order = null;
            if ( $order_id ) {
                $order = wc_get_order($order_id);
            }
            
            // Se não achou pelo Order ID, busca no banco pelo Transaction ID
            if ( !$order && $tx_id ) {
                $orders = wc_get_orders(array(
                    'limit' => 1,
                    'meta_key' => '_abacate_pix_id',
                    'meta_value' => $tx_id
                ));
                if (!empty($orders)) $order = $orders[0];
            }

            if ( $order && ! $order->is_paid() ) {
                $status = $d['data']['pixQrCode']['status'] ?? ($d['data']['status'] ?? '');
                $event = $d['event'] ?? '';
                
                if ( in_array($status, ['PAID','COMPLETED']) || $event === 'billing.paid' ) {
                    $order->payment_complete( $tx_id );
                    $order->add_order_note( 'Pagamento confirmado via Webhook Abacate Pay.' );
                    $logger->info( 'WEBHOOK: Pedido #' . $order->get_id() . ' marcado como PAGO.', array( 'source' => 'abacatepay' ) );
                }
            }
            status_header(200); exit;
        }
    }
    add_filter( 'woocommerce_payment_gateways', function( $methods ) { $methods[] = 'WC_AbacatePay_Gateway'; return $methods; } );
}

// AJAX Endpoint
add_action('wp_ajax_alfastage_check_paid', 'alfastage_check_paid_callback');
add_action('wp_ajax_nopriv_alfastage_check_paid', 'alfastage_check_paid_callback');
function alfastage_check_paid_callback() {
    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);
    wp_send_json(array('paid' => ($order && $order->is_paid())));
}

// PIX no E-mail
add_action( 'woocommerce_email_before_order_table', 'alfastage_abacate_pix_email', 15, 4 );
function alfastage_abacate_pix_email( $order, $sent_to_admin, $plain_text, $email ) {
    if ( $sent_to_admin || $order->get_payment_method() !== 'abacatepay' || !$order->has_status('on-hold') ) return;
    $code = $order->get_meta( '_abacate_pix_code' );
    $b64 = $order->get_meta( '_abacate_pix_base64' );
    if ( !$code ) return;
    $src = (strpos($b64, 'data:') === 0) ? $b64 : 'data:image/png;base64,'.$b64;
    echo '<div style="text-align:center; border: 1px solid #eee; padding: 20px; margin-bottom: 30px;">';
    echo '<h2>Pagamento via PIX</h2><p>Escaneie o QR Code abaixo:</p>';
    echo '<img src="'.$src.'" style="width: 200px; height: 200px; margin-bottom: 10px;">';
    echo '<p><strong>Copia e Cola:</strong></p><p style="word-break: break-all; font-size: 11px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">' . esc_html($code) . '</p></div>';
}

// Blocos
add_action( 'woocommerce_blocks_payment_method_type_registration', function( $registry ) {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) return;
    class WC_AbacatePay_Blocks_Support extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
        protected $name = 'abacatepay';
        public function initialize() { $this->settings = get_option( 'woocommerce_abacatepay_settings', [] ); }
        public function is_active() { return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled']; }
        public function get_payment_method_script_handles() {
            wp_register_script('wc-abacatepay-blocks', plugin_dir_url( __FILE__ ) . 'block.js', array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ), '5.2', true);
            return array( 'wc-abacatepay-blocks' );
        }
        public function get_payment_method_data() {
            return array(
                'title' => $this->get_setting( 'title' ),
                'description' => $this->get_setting( 'description' ),
                'icon_url' => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciICB2aWV3Qm94PSIwIDAgNDggNDgiIHdpZHRoPSIxNTBweCIgaGVpZ2h0PSIxNTBweCIgYmFzZVByb2ZpbGU9ImJhc2ljIj48cGF0aCBmaWxsPSIjNGRiNmFjIiBkPSJNMTEuOSwxMmgtMC42OGw4LjA0LTguMDRjMi42Mi0yLjYxLDYuODYtMi42MSw5LjQ4LDBMMzYuNzgsMTJIMzYuMWMtMS42LDAtMy4xMSwwLjYyLTQuMjQsMS43NglsLTYuOCw2Ljc3Yy0wLjU5LDAuNTktMS41MywwLjU5LTIuMTIsMGwtNi44LTYuNzdDMTUuMDEsMTIuNjIsMTMuNSwxMiwxMS45LDEyeiIvPjxwYXRoIGZpbGw9IiM0ZGI2YWMiIGQ9Ik0zNi4xLDM2aDAuNjhsLTguMDQsOC4wNGMtMi42MiwyLjYxLTYuODYsMi42MS05LjQ4LDBMMTEuMjIsMzZoMC42OGMxLjYsMCwzLjExLTAuNjIsNC4yNC0xLjc2CWw2LjgtNi43N2MwLjU5LTAuNTksMS41My0wLjU5LDIuMTIsMGw2LjgsNi43N0MzMi45OSwzNS4zOCwzNC41LDM2LDM2LjEsMzZ6Ii8+PHBhdGggZmlsbD0iIzRkYjZhYyIgZD0iTTQ0LjA0LDI4Ljc0TDM4Ljc4LDM0SDM2LjFjLTEuMDcsMC0yLjA3LTAuNDItMi44My0xLjE3bC02LjgtNi43OGMtMS4zNi0xLjM2LTMuNTgtMS4zNi00Ljk0LDAJbC02LjgsNi43OEMxMy45NywzMy41OCwxMi45NywzNCwxMS45LDM0SDkuMjJsLTUuMjYtNS4yNmMtMi42MS0yLjYyLTIuNjEtNi44NiwwLTkuNDhMOS4yMiwxNGgyLjY4YzEuMDcsMCwyLjA3LDAuNDIsMi44MywxLjE3CWw2LjgsNi43OGMwLjY4LDAuNjgsMS41OCwxLjAyLDIuNDcsMS4wMnMxLjc5LTAuMzQsMi40Ny0xLjAybDYuOC02Ljc4QzM0LjAzLDE0LjQyLDM1LjAzLDE0LDM2LjEsMTRoMi42OGw1LjI2LDUuMjYJQzQ2LjY1LDIxLjg4LDQ2LjY1LDI2LjEyLDQ0LjA0LDI4Ljc0eiIvPjwvc3ZnPg==',
                'supports' => array( 'products' ),
            );
        }
    }
    $registry->register( new WC_AbacatePay_Blocks_Support() );
} );
