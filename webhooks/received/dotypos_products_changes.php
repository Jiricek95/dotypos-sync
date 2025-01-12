<?php

//Přepis na databázi
$table_name = $wpdb->prefix . 'dotypos_sync_config';

function dotypos_sync_control_updatehook($data){
global $wpdb;


if(dotypos_sync_get_sync_setting('setting_from_dotypos_price') === false){
    return;
    }
    
    dotypos_sync_dotypos_product_update_process($data,$vat_sync);

}

function dotypos_sync_dotypos_product_update_process($data,$vat_sync){

foreach($data as $row){
    
    
    if(!empty($row['plu'])){
        
        $sku = $row['plu'];
        $vat = $row['vat'];
        $price_without_vat = $row['pricewithoutvat'];
        $price_with_vat = $row['pricewithvat'];
        $versiondate = $row['versiondate'];
        
        //Woo data
        if($woo_product_id = get_product_id_by_sku($sku)){
            
            
            if($woo_data = get_product_price($woo_product_id)){

                $logger = wc_get_logger();
                $context = array( 'source' => 'dotypos-j-l' );
                $logger->log( 'info','Woo data - '. json_encode($woo_data, true), $context );
           
                $regular_price = $woo_data["regular_price"];
                $price = $woo_data["price"];
                $sale_price = $woo_data["sale_price"];
                
                if($regular_price != $price_with_vat){
                    
                    if($sale_price != null){
                        
                        update_post_meta($woo_product_id, '_regular_price', $price_with_vat);
                        wc_delete_product_transients($woo_product_id);
                        
                }else{
                        update_post_meta($woo_product_id, '_regular_price', $price_with_vat);
                        update_post_meta($woo_product_id, '_price', $price_with_vat);
                        wc_delete_product_transients($woo_product_id);
                        
                    }
                    
                }
                //Pokud je aktivní vat sync
                if($vat_sync == true){
                    //Pokud jsou aktivní daně ve WooCommerce
                    if(wc_tax_enabled()){
                        //Získání vat class
                        $tax_class_slug = get_tax_class_by_rate($vat);
                        
                        if (!is_null($tax_class_slug)) {
                            // Aktualizace DPH
                        update_post_meta( $woo_product_id, '_tax_status', 'taxable' );
                        update_post_meta( $woo_product_id, '_tax_class', $tax_class_slug );
                        } else {
                            
                        }
                                
                            }
                }

                $change = [
                    "sku" => $sku,
                    "price" => $price_with_vat,
                    "vat" => $vat
                ];

            }else{
                
            }
        }else{
            
        }
    }
    
    
    
    
    
    
    }
}
