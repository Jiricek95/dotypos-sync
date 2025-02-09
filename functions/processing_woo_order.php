<?php

//Logika pro objednávky
global $processing_order; // Přidání globální proměnné

$processing_order = false;

function process_order_items($order) {
    global $wpdb;
    global $processing_order;

if(dotypos_sync_get_sync_setting('setting_from_woo_stockhook') === false){
return;
}

    $processing_order = true; // Nastavení flagu na true

    // Kontrola, zda je $order objekt nebo ID
    if (is_numeric($order)) {
        // Pokud je $order ID, načteme objekt objednávky
        $order = wc_get_order($order);
    }


    // Zkontroluj, zda je $order objektem a má metodu get_id
    if (is_object($order) && method_exists($order, 'get_id')) {

        $order_id = $order->get_id();
        $order_status = $order->get_status();
        $items = $order->get_items();

            
            if (!empty($items)) {
                    foreach ($items as $item) {
                        $product_id = $item->get_product_id();
                        $product = $item->get_product();
                        $sku = $product->get_sku();
                        $quantity = $item->get_quantity();
                        $refund = 0;
                        if($order_status == 'cancelled' || $order_status == 'refunded'){
                            $quantity = -$quantity;
                            $refund = 1;
                        }
                        if (!empty($sku)) {

                            $dotypos_product_id = dotypos_sync_dotypos_productid($sku);
                            if(!empty($dotypos_product_id) && $dotypos_product_id != null){
                                $post_data = [
                                    "dotypos_product_id" => $dotypos_product_id["dotypos_product_id"],
                                    "quantity" => $quantity,
                                    "note" => "WooCommerce - ".$order_id,
                                    "operation" => "sale"
                                ];
                                dotypos_sync_send_stock_dotypos($post_data);
                            }

                        }
                    }

            } else {

            }
        
    } else {

    }



    $processing_order = false; // Nastavení flagu zpět na false

}


function process_order_status_change($order_id, $old_status, $new_status, $order) {
     // Zpracování objednávky, pokud se stav změní z "draft" na cokoliv jiného než "cancelled"
     if($old_status != 'cancelled' || $old_status != 'refunded'){
    if ($new_status == 'cancelled' || $new_status == 'refunded') {
        process_order_items($order_id);
    }
}
    // Vráceno = refunded
    // Zrušeno = cancelled
    //Zkontrolovat jak provést Vrácení v DTK a zamezit odesílání změn stavu skladu u produktu na základě změnu stavy objednávky.
    // Změna u produktu musí být odeslána pouze v případě, kdy změnu provede uživatel přímo v detailu produktu.
}

function dotypos_j_l_admin_order_create($order_id){
    if (is_admin() && isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'update-order_' . $order_id)) {

        process_order_items($order_id);

     }
}

// Pro klasický checkout z FE
add_action('woocommerce_checkout_order_created', 'process_order_items', 10, 1);
// Pro checkout bloky z FE
add_action('woocommerce_store_api_checkout_order_processed', 'process_order_items', 10, 1);
// Hook pro vytvoření nové objednávky z adminu
add_action('woocommerce_new_order', 'dotypos_j_l_admin_order_create', 10, 1);
//Hook pro změnu objednávky. Použiji na cancelled
add_action('woocommerce_order_status_changed', 'process_order_status_change', 10, 4);
