<?php

// Add custom buttons to the stock status field for simple products
add_action('woocommerce_product_options_stock_status', 'dotypos_sync_stock_button_product');

function dotypos_sync_stock_button_product() {
    global $post;

    // Check if the product is simple (no variations)
    $product = wc_get_product($post->ID);
    if ($product && $product->is_type('simple')) {
        ?>
        <div id="custom-stock-buttons" class="options_group">
            <p class="form-field _stock_status_field" style="display: flex;flex-direction: row;">
                <button id="from_dotypos" class="button button-primary" setting-id = "setting_from_dotypos_stockhook" style="display: flex;align-items:center;margin-right: 5px;" data-id="<?php echo $post->ID; ?>">Nahrát stav skladu z Dotykačky a uložit</button>
                <button id="to_dotypos" class="button button-secondary" setting-id = "setting_from_woo_stockhook" style="display: flex;align-items:center;margin-right: 5px;" data-id="<?php echo $post->ID; ?>">Odeslat stav skladu do Dotykačky a uložit</button>
            </p>
            <p class="response-msg" style="text-align: center;font-size: 20px;"></p>
        </div>
        <?php
    }
}

// Add custom buttons to the stock status field for variations
add_action('woocommerce_variation_options_inventory', 'dotypos_sync_stock_button_variant', 10, 3);

function dotypos_sync_stock_button_variant($loop, $variation_data, $variation) {
    ?>
    <div class="form-row form-row-full" id="custom-variants-stock-buttons">
        <div class="button-wrap" style="display: flex;flex-direction: row;">
        <button class="custom-variant-stock-button button button-primary" id="variant_stock_from_dotypos" setting-id = "setting_from_dotypos_stockhook" style="display: flex;align-items:center;margin-right: 5px;" data-variation-id="<?php echo $variation->ID; ?>">Nahrát stav skladu z Dotykačky a uložit</button>
        <button class="custom-variant-stock-button button button-secondary" id="variant_stock_to_dotypos" setting-id = "setting_from_woo_stockhook" style="display: flex;align-items:center;margin-right: 5px;" data-variation-id="<?php echo $variation->ID; ?>">Odeslat stav skladu do Dotykačky a uložit</button>
        </div>
        <p class="response-msg"></p>
    </div>
    <?php
}

// AJAX action handler for custom stock button
//Do prvních uvozovek se píše action, který ajax zasílá
add_action('wp_ajax_custom_stock_button_listen', 'dotypos_sync_stock_button_action');

function dotypos_sync_stock_button_action() {
     
    if(empty($_POST['setting']) || dotypos_sync_get_sync_setting($_POST['setting']) === false){
        $response_data = [
            "response_msg" => 'Synchronizace není povolena',
            "actionType" => 'cancel'
            ]; 
            wp_send_json_success($response_data);
    }
    
    if (isset($_POST['doAction'])) {

        /*Blok pro získání údajů z Dotykačky */
        if(isset($_POST['product_id']) && isset($_POST['sku']) && isset($_POST['stock'])){

            $woo_product_id = intval($_POST['product_id']);
            $sku = strval($_POST['sku']);
            $woo_stock = intval($_POST['stock']);

            $dotypos_product_id = dotypos_sync_dotypos_productid($sku);

            if(empty($dotypos_product_id)){
                $response_data = [
                    "response_msg" => 'Produkt nebyl v Dotykačce nalezen',
                    "actionType" => 'cancel'
                    ]; 
                    wp_send_json_success($response_data);
            }

            $dotypos_stock_status = dotypos_sync_dotypos_stock_status($dotypos_product_id["dotypos_product_id"]);

            if(empty($dotypos_stock_status)){
                $response_data = [
                    "response_msg" => 'Nepovedlo se získat stav skladu produktu z Dotykačky',
                    "actionType" => 'cancel'
                    ]; 
                    wp_send_json_success($response_data);
            }

        }else{
            $response_data = [
                "response_msg" => 'Nebyla poskytnuta všechna data',
                "actionType" => 'cancel'
                ]; 
                wp_send_json_success($response_data);
        }

        /*Blok pro zpracování při získání stavu skladu z Dotykačky */
        if($_POST['doAction'] == 'stock_from_dotypos_product' || $_POST['doAction'] == 'variant_stock_from_dotypos'){

            /*Kontrola zda je povolena synchronizace*/ 
            if(!empty($dotypos_stock_status)){

                    if($dotypos_stock_status["stock_status"] != $woo_stock){
                    
                    //Když se povede uložit stav skladu
                    if($woo_product = wc_get_product($woo_product_id)){
                    /*
                        $woo_product->set_stock_quantity($dotypos_stock_status["stock_status"]);
                        $woo_product->set_manage_stock(true); 
                        $woo_product->save();
                    */
                        $response_msg = 'Stav skladu aktualizován';
                        $response_data = [
                            "new_stock" => $dotypos_stock_status,
                            "response_msg" => $response_msg,
                            "actionType" => 'save'
                            ]; 
                            wp_send_json_success($response_data);
                    }else{
                        $response_data = [
                            "response_msg" => 'Nepodařilo se uložit nový sklad produktu',
                            "actionType" => 'cancel'
                            ]; 
                            wp_send_json_success($response_data);
                    }
                        
                    }else{

                        $response_msg = 'Stav skladu je shodný';

                        $response_data = [
                            "new_stock" => $dotypos_stock_status,
                            "response_msg" => $response_msg,
                            "actionType" => 'save'
                            ]; 
                            wp_send_json_success($response_data);
                    }       

            }else {

                $response_data = [
                    "response_msg" => 'Produkt nebyl nalezen v Dotykačce'
                    ]; 
                    wp_send_json_success($response_data);
                
            }
        
    }

    /*Blok pro odeslání stavu skladu do Dotykačky */
    if($_POST['doAction'] == 'stock_to_dotypos_product' || $_POST['doAction'] == 'variant_stock_to_dotypos'){

        if($woo_stock == $dotypos_stock_status["stock_status"]){
            $response_data = [
                "response_msg" => 'Stav skladu je shodný',
                "actionType" => 'cancel'
                ]; 
                wp_send_json_success($response_data);
        }else{

            $new_stock_status_form_dotypos = $woo_stock - $dotypos_stock_status["stock_status"];

            $post_data = [
                "dotypos_product_id"=> $dotypos_product_id,
                "quantity"=>$new_stock_status_form_dotypos,
                "purchase_price"=>$dotypos_stock_status["purchase_price"],
                "operation"=>"stockup",
                "note"=>""
            ];

            $response = dotypos_sync_send_stock_dotypos($post_data);
            if(empty($response)){
            
                $response_data = [
                    "response_msg" => 'Nepodařilo se aktualizovat stav skladu',
                    "actionType" => 'cancel'
                    ]; 
                    wp_send_json_success($response_data);
            
            }else{
                        $response_data = [
                            "response_msg" => $response['response_msg'],
                            "actionType" => 'cancel'
                            ]; 
                            wp_send_json_success($response_data);
            
                       }

        }           
        
    }

}else{
            /*Chybí doaction */
            $response_data = [
                "response_msg" => 'Chyba autorizace',
                "actionType" => 'cancel'
                ]; 
                wp_send_json_success($response_data);
}

}

//Funkce pro získání produktu woo podle sku / plu
function dotypos_sync_product_by_sku($sku) {
    global $wpdb;

    if(!empty($sku) && $sku != null){

    $result_sku = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE `meta_key` = %s and `meta_value` = %s",
            '_sku',
            $sku
        )
    );

    if(!empty($result_sku)){


        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta WHERE `post_id` = %d",
                $result_sku
            ),
            ARRAY_A
        );
        
        $json_result = json_encode($results);

        if(!empty($results)){

            wp_send_json(["status" => "success", "messages" => "", "data" => $results]);

        }else{

            wp_send_json(["status" => "error", "messages" => "Post id not exists"]);
        }

    }else{

        wp_send_json(["status" => "error", "messages" => "Sku not exists"]);

    }

}else{
    wp_send_json(["status" => "error", "messages" => "Sku is empty"]);
}

}

//Sync po uložení produktu
/* === Spouštění synchronizace po uložení produktu === */
/*
add_action('save_post_product', 'dotypos_sync_schedule_product_sync', 99, 1);

function dotypos_sync_schedule_product_sync($product_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($product_id)) {
        return;
    }

    add_action('shutdown', function () use ($product_id) {
        dotypos_sync_handle_product_save_action($product_id);
    });
}

function dotypos_sync_handle_product_save_action($product_id) {
    static $processed_products = [];

    if (in_array($product_id, $processed_products, true)) {
        return;
    }

    $processed_products[] = $product_id;

    wp_remote_post(rest_url('dotypos/v1/sync-to'), [
        'body'    => json_encode(['product_id' => $product_id]),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);
}

// === Registrace REST API endpointu === 
add_action('rest_api_init', function () {
    register_rest_route('dotypos/v1', '/sync-to', [
        'methods'  => 'POST',
        'callback' => 'dotypos_sync_data_sync_to_dotypos',
        'permission_callback' => '__return_true', // Povolení přístupu
    ]);
});

// === Zpracování synchronizace === 
function dotypos_sync_data_sync_to_dotypos($request) {
    $params = $request->get_json_params();
    $product_id = $params['product_id'] ?? null;

    if (!$product_id || get_post_type($product_id) !== 'product') {
        return new WP_Error('invalid_product', 'Neplatné ID produktu', ['status' => 400]);
    }

    // Získání metadat produktu
    $sku = get_post_meta($product_id, '_sku', true);
    $regular_price = get_post_meta($product_id, '_regular_price', true);
    $price = get_post_meta($product_id, '_price', true);
    $tax_class_slug = get_post_meta($product_id, '_tax_class', true);
    $status = get_post_status($product_id);
    $name = get_the_title($product_id);

    if ($status !== 'publish' || empty($sku)) {
        return;
    }

    // Data k synchronizaci
    $sync_data = [
        'id' => $product_id,
        'name' => $name,
        'price' => $regular_price,
        'status' => $status,
        'sku' => $sku,
        'tax_class' => $tax_class_slug,
    ];

    $product_tax_rate = null;
    $tax_rates = dotypos_sync_get_taxes_wc();
    foreach ($tax_rates as $key => $value) {
        if ($key == $tax_class_slug) {
            $product_tax_rate = ($value / 100) + 1;
        }
    }

    if (dotypos_sync_get_sync_setting('setting_from_woo_price') === true) {
        $regular_price = get_post_meta($product_id, '_regular_price', true);
    }

    if (dotypos_sync_get_sync_setting('setting_from_woo_name') === true) {
        $name = get_the_title($product_id);
    }

    $dotypos_product_info = dotypos_sync_dotypos_productid($sku);

    if (!empty($dotypos_product_info) && $dotypos_product_info !== null) {
        if ((float) $regular_price == (float) $dotypos_product_info['price_with_vat']) {
            return;
        }

        $price_without_vat = (float) $regular_price / (float) $dotypos_product_info["vat"];

        $woo_data = [
            "regular_price" => $regular_price ? $regular_price : $dotypos_product_info["price_with_vat"],
            "sku" => $sku,
            "dotypos_product_id" => $dotypos_product_info["dotypos_product_id"],
            "eTag" => $dotypos_product_info["eTag"],
            "price_without_vat" => $price_without_vat,
            "name" => $name ? $name : $dotypos_product_info["name"],
            "vat" => $product_tax_rate ? $product_tax_rate : $dotypos_product_info["vat"],
        ];

        dotypos_sync_update_product($woo_data);
    }
}
*/
//Získání změny ceny u produktu
add_action('woocommerce_product_object_updated_props', 'check_regular_price_change_and_get_sku', 10, 2);

function check_regular_price_change_and_get_sku($product, $changed_props) {
    if (in_array('regular_price', $changed_props)) {

        $sku = $product->get_sku();
        if (!$product || $product->get_status() != 'publish' || $sku == '') {
            return;
        }
    
        // Data k synchronizaci
        $sync_data = [
            'id' => $product_id,
            'name' => $product->get_name(),
            'price' => $product->get_regular_price(),
            'status' => $product->get_status(),
            'sku'=>$product->get_sku(),
            'tax_class'=>$product->get_tax_class(),
        ];
    
        $regular_price = '';
        $name = '';
        $price_without_vat = '';
    
        if(dotypos_sync_get_sync_setting('setting_from_woo_price') === true){
            $regular_price = $product->get_regular_price();
    
            $product_tax_class = $product->get_tax_class();
            $product_tax_rate = null;
            $tax_rates = dotypos_sync_get_taxes_wc();
            foreach($tax_rates as $key=>$value){
                if($key == $product_tax_class){
                    $product_tax_rate = ($value / 100) + 1;
                }
            }
            
            
        }
        if(dotypos_sync_get_sync_setting('setting_from_woo_name') === true){
            $name = $product->get_name();
        }
    
        $dotypos_product_info = dotypos_sync_dotypos_productid($sku);
    
        if(!empty($dotypos_product_info) && $dotypos_product_info !== null){
    
            $price_without_vat = $regular_price / $dotypos_product_info["vat"];
    
            $woo_data = [
                "regular_price" => $regular_price ? $regular_price : $dotypos_product_info["price_with_vat"],
                "sku" => $sku,
                "dotypos_product_id" => $dotypos_product_info["dotypos_product_id"],
                "eTag" => $dotypos_product_info["eTag"],
                "price_without_vat" => $price_without_vat,
                "name" => $name ? $name : $dotypos_product_info["name"],
                "vat"=> $product_tax_rate ? $product_tax_rate : $dotypos_product_info["vat"],
            ];
            
            dotypos_sync_update_product($woo_data);
        }
}
}
function dotypos_sync_get_taxes_wc() {
    global $wpdb;

    // Dotaz pro získání všech sazeb daně a jejich vazby na daňové třídy
    $results = $wpdb->get_results(
        "
        SELECT 
            tax_rates.tax_rate,
            IFNULL(tax_classes.slug, '') AS tax_class
        FROM 
            {$wpdb->prefix}woocommerce_tax_rates AS tax_rates
        LEFT JOIN 
            {$wpdb->prefix}wc_tax_rate_classes AS tax_classes
        ON 
            tax_classes.slug = tax_rates.tax_rate_class
        ",
        ARRAY_A
    );

    // Vytvoření asociativního pole class => rate
    $tax_rates = [];
    foreach ($results as $row) {
        $tax_class = $row['tax_class']; // Daňová třída
        $tax_rate = $row['tax_rate'];  // Sazba daně

        // Uložíme sazbu daně pod správnou daňovou třídu
        $tax_rates[$tax_class] = $tax_rate;
    }

    // Vrácení jako JSON
   return $tax_rates;
}


//Tvorba nového produktu
//add_action('woocommerce_new_product', 'moje_funkce_pri_novem_produktu', 10, 1);


//Získání id produktu na základě sku
function dotypos_sync_get_product_id_by_sku($sku){

    global $wpdb;

            $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE `meta_key` = '_sku' and `meta_value` = %s LIMIT 1",
                $sku
            )
        );
        
return $result ?: null;
}































/*
Pro testování

add_action('wp_ajax_get_sku', 'dotypos_sync_get_sku_id');
function dotypos_sync_get_sku_id(){

    $sku = $_POST['sku'];

wp_send_json(dotypos_sync_get_product_id_by_sku($sku));
}
*/
/*Ajax pro test
    jQuery.ajax({
        url: dotypos_scripts.ajax_url, // WordPress AJAX handler
        method: 'POST',
        data: {
            action: 'get_sku',
            sku: 9988
        },
        success: function(response) {
console.log(response);

        },
        error: function() {

        }
    });

*/