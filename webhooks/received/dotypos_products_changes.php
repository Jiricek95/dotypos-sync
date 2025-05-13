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
            
                $product = wc_get_product($woo_product_id);
                if (!$product) return;
                
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
                    $new_price = (float) $dotypos_data['price_with_vat'];
                    $current_regular = (float) $product->get_regular_price();
                
                    if ($new_price !== $current_regular) {
                        $product->set_regular_price($new_price);
                        $product->set_price($new_price); // pokud není sleva, nastavíme i běžnou cenu
                        $product->set_sale_price(''); // zrušíme případnou slevu – nebo nastav, pokud máš
                        $product->set_tax_status('taxable');
                        $product->set_tax_class($tax_class_slug);
                        $product->save();




                        if (function_exists('wpml_get_content_translation') && function_exists('icl_object_id')) {
                        
                            $translations = apply_filters('wpml_get_element_translations', null, apply_filters('wpml_element_trid', null, $woo_product_id, 'post_product'), 'post_product');
                        
                            if (!empty($translations) && is_array($translations)) {
                                foreach ($translations as $lang => $translated_post) {
                                    if ((int)$translated_post->element_id === (int)$woo_product_id) {
                                        continue;
                                    }
                        
                                    $translated_product = wc_get_product($translated_post->element_id);
                                    if ($translated_product) {
                                        $translated_product->set_regular_price($new_price);
                                        $translated_product->set_price($new_price);
                                        $translated_product->set_sale_price('');
                                        $translated_product->set_tax_status('taxable');
                                        $translated_product->set_tax_class($tax_class_slug);
                                        $translated_product->save();
                                    }
                                }
                            }
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