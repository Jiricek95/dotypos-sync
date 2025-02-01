<?php

function dotypos_sync_control_updatehook($data){


foreach($data as $row){
    
    
    if(!empty($row['plu'])){
        
        $sku = $row['plu'];
        $vat = $row['vat'];
        $price_without_vat = $row['pricewithoutvat'];
        $price_with_vat = $row['pricewithvat'];
        $versiondate = $row['versiondate'];
        $name = $row['name'];

        $dotypos_data = [
            'plu'=>$row['plu'] ? $row['plu'] : null,
            'vat'=>$row['vat'] ? $row['vat'] : null,
            'price_without_vat' => $row['price_without_vat'] ? $row['price_without_vat'] : null,
            'price_with_vat'=>$row['pricewithvat'] ? $row['pricewithvat'] : null,
            'versiondate'=>$row['versiondate'] ? $row['versiondate'] : null,
            'name'=>$row['name'] ? $row['name'] : null,
        ];
        
        //Woo data
        if($woo_product_id = dotypos_sync_get_product_id_by_sku($sku)){

            central_logs('První podmínka ',$woo_product_id,'debug');
            
            $woo_data = wc_get_product($woo_product_id);
            
            if(!$woo_data){

                central_logs('Druhá podmínka ',$woo_data,'debug');
           
                $regular_price = $woo_data->get_regular_price();
                $price = $woo_data->get_price();
                $sale_price = $woo_data->get_sale_price();

                if(dotypos_sync_get_sync_setting('setting_from_dotypos_price') === true){

                    central_logs('Třetí podmínka ',$woo_product_id,'debug');

                    if($regular_price != $dotypos_data['price_with_vat']){
                    
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