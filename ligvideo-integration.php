<?php
/**
 * Plugin Name: LigVideo Integration
 * Description: Integra LigVideo (AlouShop) ao WooCommerce para adicionar produtos ao carrinho via videochamada, suportando simples e variações por ID, e botão flutuante opcional.
 * Version:     1.4.8
 * Author:      Renan Macarroni
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ligvideo-integration
 */

if (!defined("ABSPATH")) {
    exit();
}

// Só inicializa se o WooCommerce estiver ativo
add_action("plugins_loaded", function () {
    if (class_exists("WooCommerce")) {
        new LigVideo_Integration();
    }
});

class LigVideo_Integration
{
    const PUBLIC_KEY_OPTION = "ligvideo_public_key";
    const PRIVATE_KEY_OPTION = "ligvideo_private_key";
    const STORE_ID_OPTION = "ligvideo_store_id";
    const BUTTON_ENABLED_OPTION = "ligvideo_button_enabled";
    const BUTTON_IMAGE_ID_OPTION = "ligvideo_button_image_id";

    public function __construct()
    {
        // Admin
        add_action("admin_menu", [$this, "add_admin_menu"]);
        add_action("admin_init", [$this, "register_settings"]);
        add_filter("plugin_action_links_" . plugin_basename(__FILE__), [
            $this,
            "add_plugin_action_links",
        ]);

        // Rewrite tags & REST
        add_action("init", [$this, "add_rewrite_tags"]);
        add_action("rest_api_init", [$this, "register_routes"]);

        // Frontend
        add_action("wp_footer", [$this, "render_floating_button"]);
        add_action("wp_enqueue_scripts", [$this, "enqueue_variation_js"]);

        // Registra a imagem do botão
        add_action('init', [$this, 'register_button_image']);
    }

    public function add_plugin_action_links($links)
    {
        $settings_url = admin_url("options-general.php?page=ligvideo-settings");
        $links[] = '<a href="' . esc_url($settings_url) . '">Configurações</a>';
        return $links;
    }

    public function add_admin_menu()
    {
        add_options_page(
            "LigVideo Integration Settings",
            "LigVideo Integration",
            "manage_options",
            "ligvideo-settings",
            [$this, "settings_page"]
        );
    }

    public function register_settings()
    {
        register_setting("ligvideo_settings", self::STORE_ID_OPTION, [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting("ligvideo_settings", self::PUBLIC_KEY_OPTION, [
            'sanitize_callback' => [$this, 'sanitize_base64']
        ]);
        register_setting("ligvideo_settings", self::PRIVATE_KEY_OPTION, [
            'sanitize_callback' => [$this, 'sanitize_base64']
        ]);
        register_setting("ligvideo_settings", self::BUTTON_ENABLED_OPTION, [
            'sanitize_callback' => 'rest_sanitize_boolean'
        ]);
        register_setting("ligvideo_settings", self::BUTTON_IMAGE_ID_OPTION, [
            'sanitize_callback' => 'absint'
        ]);
    }

    /**
     * Sanitiza uma string Base64
     *
     * @param string $value Valor a ser sanitizado
     * @return string Valor sanitizado
     */
    public function sanitize_base64($value) {
        // Remove espaços em branco e quebras de linha
        $value = preg_replace('/\s+/', '', $value);

        // Verifica se é uma string Base64 válida
        if (base64_decode($value, true) === false) {
            return '';
        }

        return $value;
    }

    public function settings_page()
    {
        $store_url = home_url("/");
        $produtos_url = rest_url("ligvideo/v1/produtos");
        $retorno_url = rest_url("ligvideo/v1/retorno");
        ?>
        <div class="wrap">
            <h1>Configurações LigVideo</h1>
            <h2>URLs de Integração</h2>
            <p><strong>URL da Loja:</strong> <code><?php echo esc_url(
                $store_url
            ); ?></code></p>
            <p><strong>URL dos Produtos:</strong> <code><?php echo esc_url(
                $produtos_url
            ); ?></code></p>
            <p><strong>URL de Retorno:</strong> <code><?php echo esc_url(
                $retorno_url
            ); ?></code></p>
            <form method="post" action="options.php">
                <?php settings_fields("ligvideo_settings"); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="<?php echo esc_attr(
                            self::STORE_ID_OPTION
                        ); ?>">ID da Loja</label></th>
                        <td><input type="text"
                                   id="<?php echo esc_attr(
                                       self::STORE_ID_OPTION
                                   ); ?>"
                                   name="<?php echo esc_attr(
                                       self::STORE_ID_OPTION
                                   ); ?>"
                                   value="<?php echo esc_attr(
                                       get_option(self::STORE_ID_OPTION)
                                   ); ?>"
                                   class="regular-text"/></td>
                    </tr>
                    <tr>
                        <th><label for="<?php echo esc_attr(
                            self::PUBLIC_KEY_OPTION
                        ); ?>">Chave Pública (Base64)</label></th>
                        <td><textarea id="<?php echo esc_attr(
                            self::PUBLIC_KEY_OPTION
                        ); ?>"
                                      name="<?php echo esc_attr(
                                          self::PUBLIC_KEY_OPTION
                                      ); ?>"
                                      rows="3" cols="50"><?php echo esc_textarea(
                                          get_option(self::PUBLIC_KEY_OPTION)
                                      ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="<?php echo esc_attr(
                            self::PRIVATE_KEY_OPTION
                        ); ?>">Chave Privada (Base64)</label></th>
                        <td><textarea id="<?php echo esc_attr(
                            self::PRIVATE_KEY_OPTION
                        ); ?>"
                                      name="<?php echo esc_attr(
                                          self::PRIVATE_KEY_OPTION
                                      ); ?>"
                                      rows="3" cols="50"><?php echo esc_textarea(
                                          get_option(self::PRIVATE_KEY_OPTION)
                                      ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="<?php echo esc_attr(
                            self::BUTTON_ENABLED_OPTION
                        ); ?>">Mostrar botão flutuante</label></th>
                        <td><input type="checkbox"
                                   id="<?php echo esc_attr(
                                       self::BUTTON_ENABLED_OPTION
                                   ); ?>"
                                   name="<?php echo esc_attr(
                                       self::BUTTON_ENABLED_OPTION
                                   ); ?>"
                                   value="1" <?php checked(
                                       1,
                                       get_option(self::BUTTON_ENABLED_OPTION),
                                       true
                                   ); ?>/>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p class="description">
                Acesse <a href="https://ligvideo.com.br/#!/" target="_blank">ligvideo.com.br</a> para mais informações.
            </p>
        </div>
        <style>
            .ligvideo-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                z-index: 9999;
                overflow: hidden;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                background: transparent;
            }
            .ligvideo-btn img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center;
            }
        </style>
        <?php
    }

    public function add_rewrite_tags()
    {
        add_rewrite_tag("%lig_produtos%", "([^&]+)");
    }

    public function register_routes()
    {
        register_rest_route("ligvideo/v1", "/produtos", [
            "methods" => "GET",
            "callback" => [$this, "rest_produtos"],
            "permission_callback" => "__return_true",
        ]);
        register_rest_route("ligvideo/v1", "/retorno", [
            "methods" => "GET",
            "callback" => [$this, "rest_retorno"],
            "permission_callback" => "__return_true",
        ]);
    }

    public function rest_produtos(WP_REST_Request $request)
    {
        $args = ["limit" => 100, "status" => "publish"];

        // Prioriza busca por código de barras (SKU)
        if ($sku = $request->get_param("codigo_barras")) {
            $args["sku"] = $sku;
        } elseif ($id = $request->get_param("id")) {
            $args["include"] = array_map("intval", explode(",", $id));
        } elseif ($nome = $request->get_param("nome")) {
            $args["search"] = $nome;
        }

        $dados = [];
        foreach (wc_get_products($args) as $p) {
            $produto_fotos = [wp_get_attachment_url($p->get_image_id())];
            $dados[] = [
                "produto_id" => $p->get_id(),
                "produto_nome" => $p->get_name(),
                "produto_preco" => (float) $p->get_price(),
                "produto_total" => (int) $p->get_stock_quantity(),
                "produto_fotos" => $produto_fotos,
            ];
        }

        $pub_key = get_option(self::PUBLIC_KEY_OPTION);
        if (empty($pub_key)) {
            return new WP_Error("no_key", "Chave pública não configurada.", [
                "status" => 500,
            ]);
        }

        $sealed = sodium_crypto_box_seal(
            wp_json_encode(["dados" => $dados]),
            base64_decode($pub_key)
        );
        return rest_ensure_response(
            sodium_bin2base64($sealed, SODIUM_BASE64_VARIANT_ORIGINAL)
        );
    }

    public function rest_retorno(WP_REST_Request $request)
    {
        // Instancia o logger do WooCommerce
        $logger = wc_get_logger();
        $context = ["source" => "rest_retorno"];

        $logger->debug("=== Início da rest_retorno ===", $context);

        // 1) pega o JSON
        $json = $request->get_param("prod");
        $logger->debug("JSON recebido: " . $json, $context);
        if (!$json) {
            $logger->error("Nenhum parâmetro prod enviado", $context);
            return new WP_Error("no_products", "Nenhum produto informado.", [
                "status" => 400,
            ]);
        }

        // 2) decodifica
        $items = json_decode(stripslashes($json), true);
        $logger->debug(
            "Array decodificado: " . json_encode($items),
            $context
        );
        if (!is_array($items)) {
            $logger->error("JSON inválido: não resultou em array", $context);
            return new WP_Error("invalid_format", "Formato inválido.", [
                "status" => 400,
            ]);
        }

        // 3) garante carrinho carregado e vazio
        if (is_null(WC()->cart)) {
            wc_load_cart();
            $logger->debug("Carrinho carregado via wc_load_cart()", $context);
        }
        WC()->cart->empty_cart();
        $logger->debug("Carrinho esvaziado", $context);

        // 4) itera itens
        foreach ($items as $item) {
            $orig_id = intval($item["id"]);
            $quantity = max(0, intval($item["qnt"]));
            $logger->debug(
                "Processando item ID={$orig_id}, qtd={$quantity}",
                $context
            );

            if ($quantity <= 0) {
                $logger->warning(
                    "Quantidade zerada ou negativa para ID {$orig_id}, pulando",
                    $context
                );
                continue;
            }

            $product = wc_get_product($orig_id);
            if (!$product) {
                $logger->error(
                    "Produto não encontrado para ID {$orig_id}",
                    $context
                );
                continue;
            }

            // 5) se for variação, pega pai + attrs
            if ($product->is_type("variation")) {
                /** @var WC_Product_Variation $product */
                $variation_id = $product->get_id();
                $parent_id = $product->get_parent_id();
                $attrs = $product->get_variation_attributes();

                $logger->debug(
                    "Variação detectada: var_id={$variation_id}, parent_id={$parent_id}, attrs=" .
                        json_encode($attrs),
                    $context
                );

                $added = WC()->cart->add_to_cart(
                    $parent_id,
                    $quantity,
                    $variation_id,
                    $attrs
                );
                if ($added) {
                    $logger->info(
                        "Variação adicionada: parent_id={$parent_id}, var_id={$variation_id}, qtd={$quantity}",
                        $context
                    );
                } else {
                    $logger->error(
                        "Falha ao adicionar variação var_id={$variation_id}",
                        $context
                    );
                }
            } else {
                // 6) produto simples
                $logger->debug(
                    "Produto simples detectado: ID={$orig_id}",
                    $context
                );

                $added = WC()->cart->add_to_cart($orig_id, $quantity);
                if ($added) {
                    $logger->info(
                        "Produto simples adicionado: ID={$orig_id}, qtd={$quantity}",
                        $context
                    );
                } else {
                    $logger->error(
                        "Falha ao adicionar produto simples ID={$orig_id}",
                        $context
                    );
                }
            }
        }

        // 7) recalcula e cookies
        WC()->cart->calculate_totals();
        $logger->debug("Totals calculados", $context);
        if (method_exists(WC()->session, "set_customer_session_cookie")) {
            WC()->session->set_customer_session_cookie(true);
            $logger->debug("Cookie de sessão do cliente definido", $context);
        }
        if (method_exists(WC()->cart, "maybe_set_cart_cookies")) {
            WC()->cart->maybe_set_cart_cookies();
            $logger->debug("Cookie de carrinho definido", $context);
        }

        // 8) redireciona
        $logger->debug("Pronto para redirecionar para /carrinho/", $context);
        wp_safe_redirect(home_url("/carrinho/"));
        exit();
    }

    private function log_debug($message) {
        // Usa o logger do WooCommerce
        $logger = wc_get_logger();
        $context = array('source' => 'ligvideo-integration');
        $logger->debug($message, $context);
    }

    public function register_button_image() {
        $logger = wc_get_logger();
        $context = array('source' => 'ligvideo-integration');

        $image_path = plugin_dir_path(__FILE__) . 'icon.png';
        $image_url = plugin_dir_url(__FILE__) . 'icon.png';

        $logger->debug("Iniciando registro da imagem do botão", $context);
        $logger->debug("Caminho da imagem: " . $image_path, $context);

        // Limpa o anexo antigo se existir
        $old_attachment_id = get_option(self::BUTTON_IMAGE_ID_OPTION);
        if ($old_attachment_id) {
            wp_delete_attachment($old_attachment_id, true);
            delete_option(self::BUTTON_IMAGE_ID_OPTION);
            $logger->debug("Anexo antigo removido: " . $old_attachment_id, $context);
        }

        if (!file_exists($image_path)) {
            $logger->error("Arquivo de imagem não encontrado em: " . $image_path, $context);
            return false;
        }

        // Cria um novo anexo
        $attachment = array(
            'guid'           => $image_url,
            'post_mime_type' => 'image/png',
            'post_title'     => 'LigVideo Button Icon',
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Primeiro, copia o arquivo para a pasta de uploads
        $upload_dir = wp_upload_dir();
        $target_path = $upload_dir['path'] . '/ligvideo-icon.png';

        if (!copy($image_path, $target_path)) {
            $logger->error("Falha ao copiar arquivo para: " . $target_path, $context);
            return false;
        }

        $attachment_id = wp_insert_attachment($attachment, $target_path);

        if (is_wp_error($attachment_id)) {
            $logger->error("Erro ao inserir anexo: " . $attachment_id->get_error_message(), $context);
            return false;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $target_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        update_option(self::BUTTON_IMAGE_ID_OPTION, $attachment_id);

        $logger->debug("Imagem registrada com sucesso. ID: " . $attachment_id, $context);
        return $attachment_id;
    }

    public function render_floating_button()
    {
        if (!get_option(self::BUTTON_ENABLED_OPTION)) {
            return;
        }

        if (is_null(WC()->cart)) {
            wc_load_cart();
        }

        $prods = [];
        $variacoes = [];

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item["product_id"];
            $variation_id = !empty($cart_item["variation_id"]) && $cart_item["variation_id"] > 0
                ? $cart_item["variation_id"]
                : null;

            $id = $variation_id ?? $product_id;

            $prods[] = [
                "id" => $id,
                "qnt" => $cart_item["quantity"],
            ];

            if ($variation_id) {
                $variacoes[] = [
                    "pai" => $product_id,
                    "filho" => $variation_id,
                ];
            }
        }

        $base = "https://cliente.ligvideo.com.br/#/home?id=" . urlencode(get_option(self::STORE_ID_OPTION));
        $url = $base;

        if (!empty($prods)) {
            $url .= "&prod=" . urlencode(wp_json_encode($prods));
        }

        if (!empty($variacoes)) {
            $url .= "&var=" . urlencode(wp_json_encode($variacoes));
        }

        // Busca o anexo da imagem do botão
        $attachment_id = get_option(self::BUTTON_IMAGE_ID_OPTION);
        $logger = wc_get_logger();
        $context = array('source' => 'ligvideo-integration');

        if (!$attachment_id) {
            $logger->debug("Nenhum ID de anexo encontrado, tentando registrar imagem", $context);
            $attachment_id = $this->register_button_image();
        }

        if ($attachment_id) {
            $image = wp_get_attachment_image($attachment_id, 'full', false, array(
                'class' => 'ligvideo-btn-img',
                'alt' => 'Video Call',
                'loading' => 'eager'
            ));

            if (!$image) {
                $logger->error("Falha ao gerar imagem para o anexo: " . $attachment_id, $context);
            }
        } else {
            $logger->error("Falha ao obter ID do anexo da imagem", $context);
        }

        // Registra o estilo com versão do plugin
        $plugin_version = '1.4.8'; // Versão atual do plugin
        wp_register_style('ligvideo-button-style', false, array(), $plugin_version);
        wp_enqueue_style('ligvideo-button-style');
        wp_add_inline_style('ligvideo-button-style', "
            .ligvideo-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                z-index: 9999;
                background: transparent;
                display: block;
            }
            .ligvideo-btn-img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
        ");

        if (isset($image)) {
            echo '<a id="ligvideo-btn" href="' . esc_url($url) . '" class="ligvideo-btn" target="_blank">' . wp_kses_post($image) . '</a>';
        }
    }

    public function enqueue_variation_js()
    {
        if (wp_script_is("wc-add-to-cart-variation", "enqueued")) {
            wp_add_inline_script(
                "wc-add-to-cart-variation",
                "
                jQuery( document ).on('found_variation', 'form.variations_form', function(event, variation) {
                    var vid = variation.variation_id;
                    var btn = jQuery('#ligvideo-btn');
                    if ( btn.length ) {
                        var href = btn.attr('href');
                        var newHref = href.replace(/prod=([^&]+)/, function(full, prodParam) {
                            try {
                                var arr = JSON.parse(decodeURIComponent(prodParam));
                                arr.forEach(function(it){ if(it.id == variation.product_id) it.id = vid; });
                                return 'prod=' + encodeURIComponent(JSON.stringify(arr));
                            } catch(e) {
                                return full;
                            }
                        });
                        btn.attr('href', newHref);
                    }
                });
            "
            );
        }
    }
}
