<?php

// Add custom buttons to the stock status field for simple products
add_action('woocommerce_product_options_stock_status', 'dotypos_j_l_stock_button_product');

function dotypos_j_l_stock_button_product() {
    global $post;

    // Check if the product is simple (no variations)
    $product = wc_get_product($post->ID);
    if ($product && $product->is_type('simple')) {
        ?>
        <div id="custom-stock-buttons" class="options_group">
            <p class="form-field _stock_status_field" style="display: flex;flex-direction: row;">
                <button id="from_dotypos" class="button button-primary" style="display: flex;align-items:center;margin-right: 5px;" data-id="<?php echo $post->ID; ?>">Nahrát stav skladu z Dotykačky a uložit</button>
                <button id="to_dotypos" class="button button-secondary" style="display: flex;align-items:center;margin-right: 5px;" data-id="<?php echo $post->ID; ?>">Odeslat stav skladu do Dotykačky a uložit</button>
            </p>
            <p class="response-msg" style="text-align: center;font-size: 20px;"></p>
        </div>
        <?php
    }
}

// Add custom buttons to the stock status field for variations
add_action('woocommerce_variation_options_inventory', 'dotypos_j_l_stock_button_variant', 10, 3);

function dotypos_j_l_stock_button_variant($loop, $variation_data, $variation) {
    ?>
    <div class="form-row form-row-full" id="custom-variants-stock-buttons">
        <div class="button-wrap" style="display: flex;flex-direction: row;">
        <button class="custom-variant-stock-button button button-primary" id="variant_stock_from_dotypos" style="display: flex;align-items:center;margin-right: 5px;" data-variation-id="<?php echo $variation->ID; ?>">Nahrát stav skladu z Dotykačky a uložit</button>
        <button class="custom-variant-stock-button button button-secondary" id="variant_stock_to_dotypos" style="display: flex;align-items:center;margin-right: 5px;" data-variation-id="<?php echo $variation->ID; ?>">Odeslat stav skladu do Dotykačky a uložit</button>
        </div>
        <p class="response-msg"></p>
    </div>
    <?php
}

// AJAX action handler for custom stock button
//Do prvních uvozovek se píše action, který ajax zasílá
add_action('wp_ajax_custom_stock_button_listen', 'dotypos_j_l_stock_button_action');

function dotypos_j_l_stock_button_action() {
    // Zde přidej své vlastní operace
    //$response = array('message' => 'Operace byla úspěšná!');

    if (isset($_POST['doAction'])) {
        if($_POST['doAction'] == 'stock_from_dotypos_product' || $_POST['doAction'] == 'variant_stock_from_dotypos'){

        if(isset($_POST['product_id']) && isset($_POST['sku']) && isset($_POST['stock'])){
            $woo_product_id = intval($_POST['product_id']);
            $sku = strval($_POST['sku']);
            $woo_stock = intval($_POST['stock']);

            if($dotypos_stock_status = dotypos_j_l_get_dotypos_stock_status_by_sku($sku)){
                
                if(!empty($dotypos_stock_status)){

                    if($dotypos_stock_status != $woo_stock){
                    
                    //Když se povede uložit stav skladu
                    if($woo_product = wc_get_product($woo_product_id)){
                        $woo_product->set_stock_quantity($dotypos_stock_status);
                        $woo_product->set_manage_stock(true); 
                        $woo_product->save();

                        $response_msg = 'Stav skladu aktualizován';
                        $response_data = [
                            "new_stock" => $dotypos_stock_status,
                            "response_msg" => $response_msg
                            ]; 
                            wp_send_json_success($response_data);
                    }else{
                        central_logs('Nepodařilo se uložit nový sklad produktu -',$woo_product_id);
                        //central_logs('Nepodařilo se uložit nový sklad produktu -',$woo_product_id);
                        $response_data = [
                            "response_msg" => 'Nepodařilo se uložit nový sklad produktu'
                            ]; 
                            wp_send_json_success($response_data);
                    }
                        
                    }else{

                        $response_msg = 'Stav skladu je shodný';

                        $response_data = [
                            "new_stock" => $dotypos_stock_status,
                            "response_msg" => $response_msg
                            ]; 
                            wp_send_json_success($response_data);
                    }
                            
                        
                    

            }else {

                $response_data = [
                    "response_msg" => 'Produkt nebyl nalezen v Dotykačce'
                    ]; 
                    wp_send_json_success($response_data);
                
            }

        }else{
          $response_data = [
                "response_msg" => 'Produkt nebyl nalezen v Dotykačce'
                ]; 
                wp_send_json_success($response_data);
        }

        }
    }
    if($_POST['doAction'] == 'stock_to_dotypos_product' || $_POST['doAction'] == 'variant_stock_to_dotypos'){

        if(isset($_POST['product_id']) && isset($_POST['sku']) && isset($_POST['stock'])){

            $woo_product_id = intval($_POST['product_id']);
            $sku = strval($_POST['sku']);
            $woo_stock = intval($_POST['stock']);

           if($response = dotypos_j_l_stock_from_woo($sku,$woo_stock)){

            $response_data = [
                "response_msg" => $response['response_msg']
                ]; 
                wp_send_json_success($response_data);

           }else {
            $response_data = [
                "response_msg" => 'Produkt nebyl nalezen v Dotykačce'
                ]; 
                wp_send_json_success($response_data);
           }
           
        }
    }
    }

}

//Kontrola vytvoření nového produktu ve WooCommerce
add_action('save_post', 'check_new_product_sku', 10, 3);

function check_new_product_sku($post_id, $post, $update) {
    /*
    global $wpdb;

    $result = $wpdb->get_row( "SELECT sync_new_product_from_woo FROM $dotypos_j_l_table_name ");
if($result != null){
    // Zkontrolujeme, zda se jedná o produkt
    if ($post->post_type != 'product') {
        return;
    }

 if($result->sync_new_product_from_woo == 1){

    // Získáme SKU produktu, pokud existuje
    $sku = get_post_meta($post_id, '_sku', true) ?: null;

    // Pokud SKU existuje, pokračujeme se zpracováním
    if ($sku) {
        // Získáme další informace o produktu
        $name = get_the_title($post_id) ?: null;
        $price = get_post_meta($post_id, '_price', true) ?: 0;
        $stock_status = get_post_meta($post_id, '_stock', true) ?: null;

        $tax_rate = d_j_l_tax_rate_by_sku($sku);

        
        
        //Kontrola existence v DTK a odeslání
        if($dotypos_product_info = d_j_l_get_dotypos_product_by_sku($sku)){
            central_logs($dotypos_product_info,'Info');
            return;
        }else{
            d_j_l_post_new_product_to_dotypos($sku,$price,$tax_rate,$name);
        }
    }
}else{
    return;
}
}
*/
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