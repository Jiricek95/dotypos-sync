<?php

function product_transfer_from_dotypos() {
    
    $access_token_data = dotypos_sync_getDotyposAccessToken();
    
 if($access_token_data){     
$request_url = 'https://api.dotykacka.cz/v2/clouds/'.$access_token_data["cloudid"].'/products?page=1&limit=100&filter=deleted%7Ceq%7Cfalse';

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
                    $name = $product['name'];
                    $price_with_vat = $product['priceWithVat']; 
                    $price_without_vat = $product['priceWithoutVat'];
                    $vat = $product['vat'];
                    
           if(empty(dotypos_sync_get_product_id_by_sku($sku))){
               
               //Vytvoření produktu
               dotypos_j_l_wc_create_product($sku,$name,$price_with_vat,$vat);
               
           }
                }

            }

            if (!is_null($next_page)) {
                $requestUrl = 'https://api.dotykacka.cz/v2/clouds/'.$cloudid.'/products?page='.$next_page.'&limit=100&filter=deleted%7Ceq%7Cfalse';
                curl_close($curl);
            }
        }else{
            


        }
    } while (!is_null($next_page));
}

}


function dotypos_j_l_wc_create_product($sku,$name,$price_with_vat,$vat){
    $tax_class_slug = '';

    $tax_rates = dotypos_sync_get_taxes_wc();
    foreach($tax_rates as $key=>$value){
        central_logs('Tax rates ',$vat,'debug');
        if(($value / 100) + 1 == $vat){
            $tax_class_slug = $key;
        }
    }
    // Vytvoření produktu
$post_id = wp_insert_post( array(
    'post_title' => $name,
    'post_status' => 'draft',
    'post_type' => "product",
));

// Nastavení produktu jako jednoduchého produktu
wp_set_object_terms( $post_id, 'simple', 'product_type' );

// Přidání SKU
update_post_meta( $post_id, '_sku', $sku);

// Nastavení ceny
update_post_meta( $post_id, '_regular_price', $price_with_vat);
update_post_meta( $post_id, '_price', $price_with_vat);

if(wc_tax_enabled()){

if (!is_null($tax_class_slug)) {
    // Aktualizace DPH
update_post_meta( $post_id, '_tax_status', 'taxable' );
update_post_meta( $post_id, '_tax_class', $tax_class_slug );
} else {
    
}
        
    }


}


