<?php

function dotypos_j_l_control_stockhook($data){
global $wpdb;
global $dotypos_j_l_table_name;
    
    $result = $wpdb->get_row( "SELECT sync_stock_from_dotypos FROM $dotypos_j_l_table_name");
    if($result != null){
        if($result->sync_stock_from_dotypos == 1){
            dotypos_j_l_dotypos_stockhook_process($data);
        }
    }
}

function dotypos_j_l_dotypos_stockhook_process($data){


foreach($data as $row){
    $note = $row['note'];
    $new_quantity = '';
        if(preg_match("/WooCommerce/",$note)){
        return;
    }elseif(preg_match("/WooCommerce/",$row['invoicenumber'])){
        return;
    }else{
    $product_id = $row['product_id'];
    $quantity = $row['quantity'];
    $transactiontype = $row['transactiontype'];         
            
            
    if($sku = dotypos_read_plu($product_id)){
    //Získávání ID produktu na základě SKU (Vlastní funkce)
       if($woo_product_id = get_product_id_by_sku($sku)){

//Získávání stavu skladu
$woo_product_quantity = get_stock_status($woo_product_id);
if(!empty($woo_product_quantity) || $woo_product_quantity != null || $woo_product_quantity >= 0){

           if($transactiontype == 'INVENTORY'){
               $new_quantity = $row['quantitystatus'];
           }else{
            
            //Výpočet nového množství   
            $new_quantity = number_format($woo_product_quantity) + number_format($quantity);

           }
           
            $woo_products = wc_get_product($woo_product_id);

           //Načte daný produkt
           if($woo_products){
            
                    // Nastavení správy zásob na true
                  $set_stock_manage = $woo_products->set_manage_stock(true);
                   // Nastaví nové množství na skladě a uloží změny
                  $set_new_stock = $woo_products->set_stock_quantity($new_quantity);
                  $save_products = $woo_products->save();

                 $request_body = [
                    "sku" => $sku,
                    "quantity" => $quantity,
                    "new_quantity" => $new_quantity
                 ];

                  $text = 'Změna stavu skladu z Dotykačky ->';
                  $content = json_encode($request_body,true);
                  central_logs($text,$content);
               
           }
        }else{

        }
           
      } 
        
    }else{

    }
}
    }
}
