<?php

/**
 * dotypos_j_l_stock_from_woo
 *
 * @param  mixed $sku
 * @param  mixed $new_stock
 * @return void
 */
function dotypos_j_l_stock_from_woo($sku,$new_stock) {
    global $wpdb;
    global $dotypos_j_l_table_name;

    //Získání ID produktu z WooCommerce pomocí SKU
    $woo_product_id = get_product_id_by_sku_any_status($sku);
    if(empty($woo_product_id)){

        return $response = [
            "response_msg" => 'Nebyla získána data o produktu z WooCommerce'
        ];

    }else {
        //Získání údajů DTK z Databáze
    $result = $wpdb->get_row( "SELECT cloudid_dotypos,refresh_token_dotypos,warehouse_id FROM $dotypos_j_l_table_name");


if($result != null){
        $warehouse_id = $result->warehouse_id;
        $cloudid = $result->cloudid_dotypos;
        $refreshToken = $result->refresh_token_dotypos;

if($access_token = dotypos_j_l_dotypos_access_token($cloudid,$refreshToken)){

    if($dotypos_product_info = dotypos_j_l_get_dotypos_product_by_sku($sku,$access_token,$cloudid)){

        if(!empty($dotypos_product_info)){
            $body = $dotypos_product_info["body"];
            $data = json_decode($body,true);
            $dotypos_product_id = $data["data"][0]["id"];
            $dotypos_stock_status = "";
            $purchase_price = "";
        if($dotypos_stock_info = dotypos_j_l_get_dotypos_stockstatus_by_product_id_with_purchase_price($access_token,$cloudid,$dotypos_product_id,$warehouse_id)){
            if(!empty($dotypos_stock_info)){
                
                $dotypos_stock_status = $dotypos_stock_info["dotypos_stock_status"];
                $purchase_price = $dotypos_stock_info["dotypos_purchase_price"];
            }
        }

        }else{
            return $response = [
                "response_msg" => 'Nebyla získána data o produktu'
            ];
        }
        
        if($dotypos_stock_status != null){

            if($new_stock == $dotypos_stock_status){
                return $response = [
                    "response_msg" => 'Stav skladu je shodný'
                ];
            }
            $stock_diference = $new_stock - $dotypos_stock_status;
           

$request_url = 'https://api.dotykacka.cz/v2/clouds/'.$cloudid.'/warehouses/'.$warehouse_id.'/stockups';

$request_body_pre = [
    "invoiceNumber" => "WooCommerce",
    "currency" => "CZK",
    "items" => [
        [
            "_productId" => $dotypos_product_id,
            "quantity" => $stock_diference,
            "purchasePrice" => $purchase_price
        ]
    ]
        ];

$request_body = json_encode($request_body_pre,true);


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $request_url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => $request_body,
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json; charset=UTF-8',
    'Content-Type: application/json; charset=UTF-8',
    'Authorization: Bearer '.$access_token
  ),
));

$response = curl_exec($curl);

curl_close($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if($status_code == 200){

    $text = 'Změna stavu u produktu Woo SKU - '.$sku.' ->';
    $content = $request_body;
    central_logs($text,$content);

    return $response = [
        "response_msg" => 'Stav skladu byl upraven'
    ];

}else{

    $text = 'Response';
    $content = $response . ' - Request - '.$request_body . ' - Request URL - '.$request_url;
    central_logs($text,$content);
    
}
        }else{

            $text = 'Nebyl získán sklad Dotykačky';
            $content = '';
            central_logs($text,$content);

        }
    }else{
        $text = 'Nebylo získáno ID produktu z Dotykačky';
        $content = '';
        central_logs($text,$content);

        return;
    }

}else{
    $text = 'Nebyl získán accessToken při přenosu skaldu z Woo do Dotypos';
    $content = '';
    central_logs($text,$content);
}
}else{
    $text = 'Nebyl nalezen cloud nebo refreshtoken';
    $content = '';
    central_logs($text,$content);
}
}
}