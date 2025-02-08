<?php

function dotypos_sync_control_stockhook(WP_REST_Request $request){

    if(dotypos_sync_get_sync_setting('setting_from_dotypos_stockhook') === false){
        return;
    }

    $data = $request->get_json_params();

    if (!$data) {
        return new WP_REST_Response(['error' => 'Bad JSON'], 400);
    }


foreach($data as $row){ // Procházení dat webhooku

    $note = $row['note']; //Získání poznámky
    $new_quantity = ''; //Příprava nového stavu skladu

    //Podmínka kontroly poznámky a čísla dodacího listu a ukončení zpracování
    if(preg_match("/WooCommerce/",$note)){
        return;
    }elseif(preg_match("/WooCommerce/",$row['invoicenumber'])){
        return;
    }

    //Získání id produktu z webhooku
    $product_id = $row['product_id'];   

    //Získání plu a stavu skladu místo výše skladového pohybu
    $dotypos_stock_data = dotypos_sync_dotypos_stock_status($product_id);

    //Kontrola existence dat
    if(empty($dotypos_stock_data) || $dotypos_stock_data["plu"] == null){
        return;
    }

    //Získání jednotlivých dat
    $sku = $dotypos_stock_data["plu"];
    $new_quantity = $dotypos_stock_data["stock_status"];

    //Získání id produktu WooCommerce
    $woo_product_id = get_product_id_by_sku($sku);

    //Kontrola existence id produktu WooCommerce
    if(empty($woo_product_id)){
        return;
    }

    //Získání dat o produktu WooCommerce
    $woo_product_data = wc_get_product($woo_product_id);

    //Kontrola existence dat o produktu WooCommerce
    if(empty($woo_product_data)){
        return;
    }

    //Uložení nových dat
            // Nastavení správy zásob na true
            $set_stock_manage = $woo_product_data->set_manage_stock(true);
            // Nastaví nové množství na skladě a uloží změny
            $set_new_stock = $woo_product_data->set_stock_quantity($new_quantity);
            $save_products = $woo_product_data->save();
     
            $request_body = [
                "sku" => $sku,
                "quantity" => $quantity,
                "new_quantity" => $new_quantity
            ];

    }
}