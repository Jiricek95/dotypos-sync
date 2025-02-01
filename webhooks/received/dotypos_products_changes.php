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
        
        //Woo data
        if($woo_product_id = dotypos_sync_get_product_id_by_sku($sku)){
            
            $woo_data = wc_get_product($woo_product_id);
            
            if(!$woo_data){
           
                $regular_price = $woo_data->get_regular_price();
                $price = $woo_data->get_price();
                $sale_price = $woo_data->get_sale_price();

                if(dotypos_sync_get_sync_setting('setting_from_dotypos_price') === true){

                    if($regular_price != $price_with_vat){
                    
                        if($sale_price != null){
                            
                            $woo_data->set_regular_price($price_with_vat);
                            $woo_data->set_tax_status('taxable');
                            $woo_data->set_tax_class($tax_class_slug);
                            $woo_data->save();
                            
                    }else{
                        $woo_data->set_regular_price($price_with_vat);
                            $woo_data->set_price($price_with_vat);
                            $woo_data->set_tax_status('taxable');
                            $woo_data->set_tax_class($tax_class_slug);
                            $woo_data->save();
                            
                        }
                        
                    }
                   
                    }

                    if(dotypos_sync_get_sync_setting('setting_from_dotypos_name') === true){

                        $woo_data->set_name($name);
                        $woo_data->save();

                        }
                       
                        }
                


            }else{
                
            }
        }else{
            
        }
    }
    
    
    }