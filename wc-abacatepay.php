<?php
/*
Plugin Name: Abacate Pay PIX - AlfaStageLabs
Plugin URI: https://github.com/AlfaStage/wc-abacatepay
Description: Integração PIX com correção de leitura do Webhook.
Version: 4.6
Author: AlfaStageLabs
Author URI: https://github.com/AlfaStage
Text Domain: wc-abacatepay
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'alfastage_init_gateway_class' );

function alfastage_init_gateway_class() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    class WC_AbacatePay_Gateway extends WC_Payment_Gateway {
        
        public $api_key;
        public $webhook_secret;
        public $expiration_minutes;

        public function __construct() {
            $this->id                 = 'abacatepay'; 
            $this->icon               = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciICB2aWV3Qm94PSIwIDAgNDggNDgiIHdpZHRoPSIxNTBweCIgaGVpZ2h0PSIxNTBweCIgYmFzZVByb2ZpbGU9ImJhc2ljIj48cGF0aCBmaWxsPSIjNGRiNmFjIiBkPSJNMTEuOSwxMmgtMC42OGw4LjA0LTguMDRjMi42Mi0yLjYxLDYuODYtMi42MSw5LjQ4LDBMMzYuNzgsMTJIMzYuMWMtMS42LDAtMy4xMSwwLjYyLTQuMjQsMS43NglsLTYuOCw2Ljc3Yy0wLjU5LDAuNTktMS41MywwLjU5LTIuMTIsMGwtNi44LTYuNzdDMTUuMDEsMTIuNjIsMTMuNSwxMiwxMS45LDEyeiIvPjxwYXRoIGZpbGw9IiM0ZGI2YWMiIGQ9Ik0zNi4xLDM2aDAuNjhsLTguMDQsOC4wNGMtMi42MiwyLjYxLTYuODYsMi42MS05LjQ4LDBMMTEuMjIsMzZoMC42OGMxLjYsMCwzLjExLTAuNjIsNC4yNC0xLjc2CWw2LjgtNi43N2MwLjU5LTAuNTksMS41My0wLjU5LDIuMTIsMGw2LjgsNi43N0MzMi45OSwzNS4zOCwzNC41LDM2LDM2LjEsMzZ6Ii8+PHBhdGggZmlsbD0iIzRkYjZhYyIgZD0iTTQ0LjA0LDI4Ljc0TDM4Ljc4LDM0SDM2LjFjLTEuMDcsMC0yLjA3LTAuNDItMi44My0xLjE3bC02LjgtNi43OGMtMS4zNi0xLjM2LTMuNTgtMS4zNi00Ljk0LDAJbC02LjgsNi43OEMxMy45NywzMy41OCwxMi45NywzNCwxMS45LDM0SDkuMjJsLTUuMjYtNS4yNmMtMi42MS0yLjYyLTIuNjEtNi44NiwwLTkuNDhMOS4yMiwxNGgyLjY4YzEuMDcsMCwyLjA3LDAuNDIsMi44MywxLjE3CWw2LjgsNi43OGMwLjY4LDAuNjgsMS41OCwxLjAyLDIuNDcsMS4wMnMxLjc5LTAuMzQsMi40Ny0xLjAybDYuOC02Ljc4QzM0LjAzLDE0LjQyLDM1LjAzLDE0LDM2LjEsMTRoMi42OGw1LjI2LDUuMjYJQzQ2LjY1LDIxLjg4LDQ2LjY1LDI2LjEyLDQ0LjA0LDI4Ljc0eiIvPjwvc3ZnPg==';
            $this->has_fields         = false;
            $this->method_title       = 'Abacate Pay - PIX';
            $this->method_description = 'PIX via Abacate Pay (AlfaStageLabs).';

            $this->init_form_fields();
            $this->init_settings();

            $this->title              = $this->get_option( 'title' );
            $this->description        = $this->get_option( 'description' );
            $this->enabled            = $this->get_option( 'enabled' );
            $this->api_key            = $this->get_option( 'api_key' );
            $this->webhook_secret     = $this->get_option( 'webhook_secret' );
            $this->expiration_minutes = $this->get_option( 'expiration_minutes', '15' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'show_pix_on_thankyou_page' ) );
            add_action( 'woocommerce_api_wc_abacatepay_gateway', array( $this, 'webhook_handler' ) );
            add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_abacate_id_in_admin' ) );
        }

        public function is_available() {
            return ( 'yes' === $this->enabled && ! empty( $this->api_key ) );
        }

        public function init_form_fields() {
            $base_webhook = add_query_arg( 'wc-api', 'wc_abacatepay_gateway', home_url( '/' ) );
            $secret = $this->get_option( 'webhook_secret' );
            $final_url = $base_webhook . ( $secret ? '?webhookSecret=' . $secret : '' );

            $this->form_fields = array(
                'enabled' => array( 'title' => 'Habilitar', 'type' => 'checkbox', 'default' => 'yes' ),
                'title'   => array( 'title' => 'Título', 'type' => 'text', 'default' => 'PIX (Aprovação Imediata)' ),
                'description' => array( 'title' => 'Descrição', 'type' => 'textarea', 'default' => 'Pague com o app do seu banco.' ),
                'expiration_minutes' => array( 'title' => 'Tempo de Expiração (Min)', 'type' => 'number', 'default' => '15' ),
                'api_key' => array( 'title' => 'API Token', 'type' => 'password' ),
                'webhook_secret' => array( 'title' => 'Senha do Webhook', 'type' => 'text', 'default' => wp_generate_password(10, false) ),
                'webhook_url_display' => array(
                    'title' => 'URL do Webhook',
                    'type' => 'title',
                    'description' => '<b>Copie esta URL:</b> <br><input type="text" readonly value="' . esc_attr($final_url) . '" style="width:100%; background:#eee;">'
                )
            );
        }

        public function process_payment( $order_id ) {
            $logger = wc_get_logger();
            $order = wc_get_order( $order_id );
            
            try {
                $amount_cents = intval( round( $order->get_total() * 100 ) );
                $expires_in_seconds = intval( $this->expiration_minutes ) * 60;
                
                $payload = array(
                    'amount'      => $amount_cents,
                    'expiresIn'   => $expires_in_seconds,
                    'description' => 'Pedido ' . $order_id,
                    'metadata'    => array(
                        'externalId' => (string) $order_id
                    )
                );

                $logger->info( '>>> REQ ABACATE: ' . json_encode($payload), array( 'source' => 'abacatepay' ) );

                $response = wp_remote_post( 'https://api.abacatepay.com/v1/pixQrCode/create', array(
                    'method' => 'POST',
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->api_key,
                        'Content-Type'  => 'application/json',
                    ),
                    'body' => json_encode( $payload ),
                    'timeout' => 30
                ));

                if ( is_wp_error( $response ) ) {
                    $logger->error( '!!! ERRO CONEXAO: ' . $response->get_error_message(), array( 'source' => 'abacatepay' ) );
                    wc_add_notice( 'Erro de conexão.', 'error' );
                    return array('result' => 'fail');
                }

                $body_raw = wp_remote_retrieve_body( $response );
                $http_code = wp_remote_retrieve_response_code( $response );
                $body = json_decode( $body_raw, true );

                $logger->info( '<<< RESPOSTA (' . $http_code . '): ' . $body_raw, array( 'source' => 'abacatepay' ) );

                $data = null;
                if ( isset($body['data']['id']) ) {
                    $data = $body['data'];
                } elseif ( isset($body[0]['success']) && $body[0]['success'] ) {
                    $data = $body[0]['data'];
                }

                if ( $http_code >= 200 && $http_code < 300 && $data ) {
                    $order->update_meta_data( '_abacate_pix_code', $data['brCode'] );
                    $order->update_meta_data( '_abacate_pix_id', $data['id'] );
                    $order->update_meta_data( '_abacate_pix_base64', $data['brCodeBase64'] ?? '' );
                    $order->update_meta_data( '_abacate_pix_expires_at', $data['expiresAt'] ?? '' );
                    $order->update_meta_data( 'abacate_transaction_id', $data['id'] );
                    
                    $order->update_status( 'on-hold', 'Aguardando PIX' );
                    $order->save();
                    WC()->cart->empty_cart();

                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url( $order ),
                    );
                } else {
                    $msg_erro = $body['error'] ?? ($body[0]['error'] ?? 'Dados inválidos.');
                    wc_add_notice( 'Erro no pagamento: ' . $msg_erro, 'error' );
                    return array('result' => 'fail');
                }

            } catch ( Exception $e ) {
                $logger->critical( 'Erro Fatal: ' . $e->getMessage(), array( 'source' => 'abacatepay' ) );
                wc_add_notice( 'Erro interno.', 'error' );
                return array('result' => 'fail');
            }
        }

        public function show_pix_on_thankyou_page( $order_id ) {
            $order = wc_get_order( $order_id );
            if(!$order || $order->get_payment_method() !== $this->id || $order->has_status(['processing','completed'])) return;

            $code = $order->get_meta( '_abacate_pix_code' );
            $b64  = $order->get_meta( '_abacate_pix_base64' );
            $expires_at = $order->get_meta( '_abacate_pix_expires_at' );
            $src = (strpos($b64, 'data:') === 0) ? $b64 : 'data:image/png;base64,'.$b64;
            
            ?>
            <div class="abacate-pix-container" style="margin: 30px 0; padding: 25px; border: 2px solid #e1e1e1; border-radius: 8px; text-align: center; background: #fff;">
                <h3 style="margin-top:0;">Pague com PIX</h3>
                <?php if($expires_at): ?>
                    <div id="pix-countdown" style="font-size: 1.2em; color: #d63638; font-weight: bold; margin-bottom: 15px;">
                        Expira em: <span id="timer-display">--:--</span>
                    </div>
                <?php endif; ?>
                <?php if($b64): ?>
                    <img src="<?php echo esc_attr($src); ?>" style="max-width: 250px; height: auto; display: block; margin: 0 auto 15px; border: 1px solid #ccc; padding: 5px;">
                <?php endif; ?>
                <div style="margin-top: 15px;">
                    <label style="display:block; font-weight:600; margin-bottom: 5px;">Código Copia e Cola:</label>
                    <textarea id="pix-code-field" readonly style="width: 100%; height: 80px; font-size: 12px; background: #f7f7f7; padding: 10px; border: 1px solid #ccc;"><?php echo esc_textarea($code); ?></textarea>
                    <button type="button" id="btn-copy-pix" style="margin-top: 10px; padding: 10px 20px; cursor: pointer; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 16px;">Copiar Código</button>
                </div>
            </div>
            <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById('btn-copy-pix').addEventListener('click', function() {
                    var copyText = document.getElementById("pix-code-field");
                    copyText.select();
                    copyText.setSelectionRange(0, 99999);
                    navigator.clipboard.writeText(copyText.value).then(function() { alert("Código copiado!"); });
                });
                <?php if($expires_at): ?>
                var expiresAt = new Date("<?php echo esc_js($expires_at); ?>").getTime();
                var x = setInterval(function() {
                    var now = new Date().getTime();
                    var distance = expiresAt - now;
                    if (distance < 0) {
                        clearInterval(x);
                        document.getElementById("pix-countdown").innerHTML = "QR CODE EXPIRADO";
                        document.querySelector(".abacate-pix-container").style.opacity = "0.5";
                        return;
                    }
                    var m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var s = Math.floor((distance % (1000 * 60)) / 1000);
                    document.getElementById("timer-display").innerHTML = (m<10?"0"+m:m) + ":" + (s<10?"0"+s:s);
                }, 1000);
                <?php endif; ?>
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
            
            // 1. Validação do Secret
            $s = $this->get_option('webhook_secret');
            if(empty($s) || ($_GET['webhookSecret'] ?? '') !== $s) { 
                status_header(403); exit('Forbidden'); 
            }
            
            // 2. Leitura dos dados
            $payload = file_get_contents('php://input');
            $data = json_decode($payload, true);
            
            // Log para debug
            $logger->info( 'WEBHOOK RAW: ' . $payload, array( 'source' => 'abacatepay' ) );

            // 3. Extração do ID da transação
            // Tenta pegar o ID dentro de 'pixQrCode' (formato novo) ou 'billing' ou raiz
            $tx_id = null;
            if ( isset( $data['data']['pixQrCode']['id'] ) ) {
                $tx_id = $data['data']['pixQrCode']['id'];
            } elseif ( isset( $data['data']['id'] ) ) {
                $tx_id = $data['data']['id'];
            } elseif ( isset( $data['data']['billing']['id'] ) ) {
                $tx_id = $data['data']['billing']['id'];
            }

            if ( ! $tx_id ) {
                $logger->error( 'WEBHOOK: ID não encontrado no payload.', array( 'source' => 'abacatepay' ) );
                status_header(200); exit;
            }

            // 4. Busca do Pedido pelo ID salvo
            $orders = wc_get_orders(array(
                'limit' => 1,
                'meta_key' => '_abacate_pix_id',
                'meta_value' => $tx_id
            ));

            if ( empty($orders) ) {
                $logger->error( "WEBHOOK: Pedido não encontrado para o ID $tx_id", array( 'source' => 'abacatepay' ) );
                status_header(200); exit;
            }

            $order = $orders[0];

            // 5. Validação do Status
            if ( ! $order->is_paid() ) {
                $event = $data['event'] ?? '';
                $status_pix = $data['data']['pixQrCode']['status'] ?? '';
                $status_billing = $data['data']['status'] ?? '';

                $pago = false;

                // Verifica se é evento de pago ou se o status interno é PAID
                if ( $event === 'billing.paid' || $status_pix === 'PAID' || $status_billing === 'PAID' || $status_billing === 'COMPLETED' ) {
                    $pago = true;
                }

                if ( $pago ) {
                    $order->payment_complete( $tx_id );
                    $order->add_order_note( "Pagamento confirmado via Webhook Abacate Pay (ID: $tx_id)." );
                    $logger->info( "WEBHOOK: Pedido #{$order->get_id()} atualizado para PAGO.", array( 'source' => 'abacatepay' ) );
                }
            } else {
                $logger->info( "WEBHOOK: Pedido #{$order->get_id()} já estava pago.", array( 'source' => 'abacatepay' ) );
            }

            status_header(200);
            exit;
        }
    }
    add_filter( 'woocommerce_payment_gateways', function( $methods ) { $methods[] = 'WC_AbacatePay_Gateway'; return $methods; } );
}

add_action( 'woocommerce_blocks_loaded', 'alfastage_abacatepay_blocks_init' );
function alfastage_abacatepay_blocks_init() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) return;
    class WC_AbacatePay_Blocks_Support extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
        protected $name = 'abacatepay';
        public function initialize() { $this->settings = get_option( 'woocommerce_abacatepay_settings', [] ); }
        public function is_active() { return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled']; }
        public function get_payment_method_script_handles() {
            wp_register_script('wc-abacatepay-blocks', plugin_dir_url( __FILE__ ) . 'block.js', array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ), '4.6', true);
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
    add_action( 'woocommerce_blocks_payment_method_type_registration', function( $registry ) { $registry->register( new WC_AbacatePay_Blocks_Support() ); } );
}
