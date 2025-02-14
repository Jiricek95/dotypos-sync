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
            
            $woo_data = wc_get_product($woo_product_id);
            
            if($woo_data){

                $tax_class_slug = '';

                $tax_rates = dotypos_sync_get_taxes_wc();
                foreach($tax_rates as $key=>$value){
                    if(((float) $value / 100) + 1 == (float)$dotypos_data["vat"]){

                        $tax_class_slug = $key;
                    }
                }
				
           
                $regular_price = $woo_data->get_regular_price();
                $price = $woo_data->get_price();
                $sale_price = $woo_data->get_sale_price();

                if(dotypos_sync_get_sync_setting('setting_from_dotypos_price') === true){

                    if($dotypos_data['price_with_vat'] !== null && $regular_price != $dotypos_data['price_with_vat']){
                    
                        if($sale_price != null){
                            
                            $woo_data->set_regular_price($dotypos_data['plu']);
                            $woo_data->set_tax_status('taxable');
                            $woo_data->set_tax_class($tax_class_slug);
                            $woo_data->save();
                            
                    }else{
                        $woo_data->set_regular_price($dotypos_data['price_with_vat']);
                            $woo_data->set_price($dotypos_data['price_with_vat']);
                            $woo_data->set_tax_status('taxable');
                            $woo_data->set_tax_class($tax_class_slug);
                            $woo_data->save();
                            
                        }
                        
                    }
                   
                    }

                    if(dotypos_sync_get_sync_setting('setting_from_dotypos_name') === true){

                        $woo_data->set_name($dotypos_data['name']);
                        $woo_data->save();

                        }
                       
                        }
                


            }else{
                
            }
        }else{
            
        }
    }
	
    
}