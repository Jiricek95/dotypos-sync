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

            $post_type_allowed = ['product','product_variation'];
            
            if (in_array($post_type,$post_type_allowed)) { // Ověření, že jde o produkt
            
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
                
                $oldData = [
                    "price" => (float) ($product ? $product->get_regular_price() : null),
                    "tax_slug" => $product ? $product->get_tax_class() : null
                ];
                
                $newData = [
                    "price" => isset($dotypos_data['price_with_vat']) ? (float) $dotypos_data['price_with_vat'] : null,
                    "tax_slug" => isset($tax_class_slug, $dotypos_data["vat"])
                        ? $tax_class_slug . " (" . (float) $dotypos_data["vat"] . ")"
                        : null
                ];
                
                

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
                        

                        central_logs("Změna u produktu -> ". $dotypos_data["plu"][0], "Nová data -> " . json_encode($newData,true) . " \n Stará data -> " . json_encode($oldData,true), "info");


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

/*
function dotypos_sync_update_price_by_sku($sku, $new_price, $tax_class_slug = '') {
    if (!$sku || !is_numeric($new_price)) {
        return;
    }

    // Najdi produkt/variantu podle SKU
    $product_id = wc_get_product_id_by_sku($sku);
    if (!$product_id) return;

    $product = wc_get_product($product_id);
    if (!$product) return;

    // Pokud cena není rozdílná, nic nedělej
    $current_regular = (float)$product->get_regular_price();
    if ((float)$new_price === $current_regular) return;

    // Nastavení ceny
    $product->set_regular_price($new_price);
    $product->set_price($new_price);
    $product->set_sale_price(''); // Zruší sleva
    $product->set_tax_status('taxable');
    if (!empty($tax_class_slug)) {
        $product->set_tax_class($tax_class_slug);
    }
    $product->save();

    // Aktualizace překladu, pokud existuje WPML
    if (function_exists('wpml_get_content_translation') && function_exists('icl_object_id')) {
        $trid = apply_filters('wpml_element_trid', null, $product_id, 'post_product');
        $translations = apply_filters('wpml_get_element_translations', null, $trid, 'post_product');

        if (!empty($translations) && is_array($translations)) {
            foreach ($translations as $lang => $translated_post) {
                if ((int)$translated_post->element_id === (int)$product_id) continue;

                $translated_product = wc_get_product($translated_post->element_id);
                if ($translated_product) {
                    $translated_product->set_regular_price($new_price);
                    $translated_product->set_price($new_price);
                    $translated_product->set_sale_price('');
                    $translated_product->set_tax_status('taxable');
                    if (!empty($tax_class_slug)) {
                        $translated_product->set_tax_class($tax_class_slug);
                    }
                    $translated_product->save();
                }
            }
        }
    }
}
    */