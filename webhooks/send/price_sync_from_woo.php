<?php

//Přepis na databázi
$table_name = $wpdb->prefix . 'dotypos_j_l_config';

//Kontrola zda se má synchronizovat cena
function dotypos_j_l_control_update_woo_price($woo_data){
global $wpdb;
global $dotypos_j_l_table_name;
    $sku = $woo_data["sku"];
    $woo_product_id = get_product_id_by_sku_any_status($sku);
    if(empty($woo_product_id)){

        return;

    }else {
        
        $result = $wpdb->get_row( "SELECT sync_price_from_woo,vat_sync FROM $dotypos_j_l_table_name");
   
        if($result != null){
            $vat_sync = $result->vat_sync;
            if($result->sync_price_from_woo == 1){
                dotypos_j_l_dotypos_product_update_process_from_woo($woo_data,$vat_sync);
            }
    }else {
        $text = 'Nebylo zjištěno nastavení pro synchronizaci price_sync_from_woo.php';
        $content = '';
        central_logs($text,$content);
    }

    }
}


function dotypos_j_l_dotypos_product_update_process_from_woo($woo_data,$vat_sync){
    global $wpdb;
    global $dotypos_j_l_table_name;
    
    //Data z $woo_data
    $sku = $woo_data["sku"];
    $regular_price = $woo_data["regular_price"];
    
    $result = $wpdb->get_row( "SELECT cloudid_dotypos,refresh_token_dotypos FROM $dotypos_j_l_table_name");
    
    
if($result != null){

        $cloudid = $result->cloudid_dotypos;
        $refreshToken = $result->refresh_token_dotypos;
        
        if($access_token = dotypos_j_l_dotypos_access_token($cloudid,$refreshToken)){
            
if($response_data = dotypos_j_l_get_dotypos_product_by_sku($sku, $access_token, $cloudid)){

    $etag = $response_data["etag"];
    $body = $response_data["body"];

    // Dekódování JSON řetězce do PHP pole
    $data = json_decode($body, true); // true znamená, že výsledek bude pole, ne objekt

    // Kontrola, zda je 'price_with_vat' dostupný v dekódovaném JSONu
    if(isset($data["data"][0]['priceWithVat'])) {
        $price_with_vat = $data["data"][0]['priceWithVat'];
        $dotypos_product_id = $data["data"][0]["id"];
        $dotypos_vat = $data["data"][0]["vat"];

        $vat = '';
        if($vat_sync == 1){
            $vat = get_tax_rate_by_sku($sku);
            if(empty($vat)){
                $vat = 1;
            }
        }else {
            $vat = $dotypos_vat;
        }
        
        if($price_with_vat != $regular_price){

            $price_without_vat = floatval($regular_price) / floatval($vat);

            $request_body_pre = [
                "id" => $dotypos_product_id,
                "priceWithVat"=>$regular_price,
                "priceWithoutVat"=>$price_without_vat,
                "vat"=>$vat
            ];

            $request_body = json_encode([$request_body_pre],true);

            dotypos_j_l_put_price($cloudid,$access_token,$request_body,$etag,$sku);
            

            
        }
        
    } else {
        // 'price_with_vat' nebyl nalezen
        
    }
}

            
        }
    
}else{// Pokud nebyl získán cloudid z databáze
    $text = 'Nebyl získán cloudid nebo refreshtoken z databáze';
    $content = '';
    central_logs($text,$content);
}
}

function dotypos_j_l_put_price($cloudid,$access_token,$request_body,$etag,$sku){

    $request_url = 'https://api.dotykacka.cz/v2/clouds/'.$cloudid.'/products/';


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $request_url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'PUT',
  CURLOPT_POSTFIELDS => $request_body,
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json; charset=UTF-8',
    'Content-Type: application/json; charset=UTF-8',
    'If-Match: '.$etag,
    'Authorization: Bearer '.$access_token
  ),
));

$response = curl_exec($curl);

curl_close($curl);

$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if($status_code != 200){
    $text = 'Response';
    $content = $response . ' - Request - '.$request_body . ' - Request URL - '.$request_url.'Status code - '.$status_code;
    central_logs($text,$content);
}
$text = 'Změna produktu z Woo SKU - '.$sku.' ->';
$content = $request_body;
central_logs($text,$content);

}