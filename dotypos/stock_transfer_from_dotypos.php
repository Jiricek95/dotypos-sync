<?php

function stock_transfer_from_dotypos() {
    
    $access_token_data = dotypos_sync_getDotyposAccessToken();
    
 if($access_token_data){   
$request_url = 'https://api.dotykacka.cz/v2/clouds/'.$access_token_data['cloudid'].'/warehouses/'.$access_token_data['warehouse_id'].'/products?page=1&limit=100&filter=deleted%7Ceq%7Cfalse&sort=name';

    do {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $request_url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            'Authorization: Bearer '.$access_token_data["access_token"]
          ),
        ));

        $response = curl_exec($curl);

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($status_code == 404){
        }

        if ($status_code == 200) {
            $data = json_decode($response, true);
            $current_page = $data["currentPage"];
            $next_page = $data["nextPage"];
           
            foreach ($data["data"] as $product) {
                
                if(!empty($product['plu'][0])){
                    $sku = $product['plu'][0];
                    $quantity = $product["stockQuantityStatus"];
                    
           if($woo_product_id = dotypos_sync_get_product_id_by_sku($sku)){

            
            $woo_products = wc_get_product($woo_product_id);
           //Načte daný produkt
           
                    // Nastavení správy zásob na true
                    $woo_products->set_manage_stock(true);
                   // Nastaví nové množství na skladě a uloží změny
                    $woo_products->set_stock_quantity($quantity);
                    $woo_products->save();
               
           }
                }

            }

            if (!is_null($next_page)) {
                $requestUrl = 'https://api.dotykacka.cz/v2/clouds/'.$cloudid.'/warehouses/'.$warehouse_id.'/products?page='.$next_page.'&limit=100&filter=deleted%7Ceq%7Cfalse';
                curl_close($curl);
            }
}else{


        }
    } while (!is_null($next_page));
}

}