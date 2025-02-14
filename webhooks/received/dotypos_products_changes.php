<?php

function dotypos_sync_control_updatehook(WP_REST_Request $request){

    $data = $request->get_json_params();

    if (!$data) {
        return new WP_REST_Response(['error' => 'Bad JSON'], 400);
    }
    
foreach($data as $row){
    
    if(!empty($row['plu'])){

        $dotypos_data = [
            'plu'=> isset($row['plu']) ? $row['plu'] : null,
            'vat'=>isset($row['vat']) ? $row['vat'] : null,
            'price_without_vat' => isset($row['pricewithoutvat']) ? $row['pricewithoutvat'] : null,
            'price_with_vat'=>isset($row['pricewithvat']) ? $row['pricewithvat'] : null,
            'versiondate'=>isset($row['versiondate']) ? $row['versiondate'] : null,
            'name'=>isset($row['name']) ? $row['name'] : null,
        ];
		
        
        //Woo data
        if($woo_product_id = dotypos_sync_get_product_id_by_sku($dotypos_data['plu'])){
            
            $post_type = get_post_type($woo_product_id);
            
            if ($post_type === 'product') { // Ověření, že jde o produkt
            
                // Získání cen z postmeta
                $regular_price = get_post_meta($woo_product_id, '_regular_price', true);
                $price = get_post_meta($woo_product_id, '_price', true);
                $sale_price = get_post_meta($woo_product_id, '_sale_price', true);
            
                // Výpočet daňové třídy
                $tax_class_slug = '';
                $tax_rates = dotypos_sync_get_taxes_wc();
                foreach ($tax_rates as $key => $value) {
                    if (((float) $value / 100) + 1 == (float) $dotypos_data["vat"]) {
                        $tax_class_slug = $key;
                    }
                }
            
                // Synchronizace ceny z Dotykačky
                if (dotypos_sync_get_sync_setting('setting_from_dotypos_price') === true) {
                    if ($dotypos_data['price_with_vat'] !== null && $regular_price != $dotypos_data['price_with_vat']) {
            
                        if ($sale_price != null) {
                            update_post_meta($woo_product_id, '_regular_price', $dotypos_data['price_with_vat']);
                            update_post_meta($woo_product_id, '_tax_status', 'taxable');
                            update_post_meta($woo_product_id, '_tax_class', $tax_class_slug);
                        } else {
                            update_post_meta($woo_product_id, '_regular_price', $dotypos_data['price_with_vat']);
                            update_post_meta($woo_product_id, '_price', $dotypos_data['price_with_vat']);
                            update_post_meta($woo_product_id, '_tax_status', 'taxable');
                            update_post_meta($woo_product_id, '_tax_class', $tax_class_slug);
                        }
                    }
                }
            
                // Synchronizace názvu produktu
                if (dotypos_sync_get_sync_setting('setting_from_dotypos_name') === true) {
                    wp_update_post([
                        'ID'         => $woo_product_id,
                        'post_title' => $dotypos_data['name']
                    ]);
                }
            
                // Vyčištění cache produktu
                clean_post_cache($woo_product_id);
            }
                


            }else{
                
            }
        }else{
            
        }
    }
	
    
}