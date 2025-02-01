<?php
function get_settings_data() {
    global $wpdb;

    // Získání celé tabulky
    $table_name = DOTYPOSSYNC_TABLE_NAME;
    $query = "SELECT * FROM {$table_name}";
    $setting_data = $wpdb->get_results($query, ARRAY_A);

    $cloudid = null;
    $refresh_token_dotypos = null;
    $warehouse_id = null;
    $webhook_id = null;
    $webhook_changes_id = null;
    $sync_dotypos_sockhook = null;
    $sync_woo_stockhook = null;

    foreach($setting_data as $row){
        if($row["key"] === 'cloudid'){
            $cloudid = $row['value'];
        }
        if($row["key"] === 'refresh_token_dotypos'){
            $refresh_token_dotypos = $row['value'];
        }
        if($row["key"] === 'warehouse_id'){
            $warehouse_id = $row['value'];
        }
        if($row["key"] === 'sync_dotypos_sockhook'){
            $sync_dotypos_sockhook = $row['value'];
        }
        if($row["key"] === 'sync_woo_stockhook'){
            $sync_woo_stockhook = $row['value'];
        }
        if($row["key"] === 'webhook_id'){
            $webhook_id = $row['value'];
        }
        if($row["key"] === 'webhook_changes_id'){
            $webhook_changes_id = $row['value'];
        }
    }

    $data = [
        'cloudid' => $cloudid,
        'refresh_token_dotypos' => $refresh_token_dotypos,
        'warehouse_id' => $warehouse_id,
        'sync_dotypos_sockhook' => $sync_dotypos_sockhook,
        'sync_woo_stockhook' => $sync_woo_stockhook,
        'webhook_id' => $webhook_id,
        'webhook_changes_id' => $webhook_changes_id
    ];

    return $data;
}

function dotypos_sync_getDotyposAccessToken(){
    global $wpdb;

    central_logs("Spuštěna funkce dotypos_sync_getDotyposAccessToken()","","debug");

    $setting_data = get_settings_data();

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.dotykacka.cz/v2/signin/token',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{"_cloudId": '.$setting_data["cloudid"].'}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: User '.$setting_data["refresh_token_dotypos"]
  ),
));

$response = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if(!empty($response)){
    if($status_code == 201){
        $access_token = json_decode($response,true);

        $setting_data['access_token'] = $access_token["accessToken"];

        return $setting_data;
    }else{
        //Logovat odpověď a status_code
        central_logs("Odpověď funkce dotypos_sync_getDotyposAccessToken() - {$response} - {$status_code}","","debug");
        return;
    }
}else{
    //Logovat odpověď a status_code
    central_logs("Odpověď funkce dotypos_sync_getDotyposAccessToken() - {$response} - {$status_code}","","debug");
    return;
}

curl_close($curl);

}
/*Get funkce */
function dotypos_sync_getDotyposStockList(){
    global $wpdb,$logger;

    $access_token_data = dotypos_sync_getDotyposAccessToken();

    if(!empty($access_token_data)){

        $data = $access_token_data;
        $filter = "filter=deleted%7Ceq%7Cfalse";
        $request_url = 'https://api.dotykacka.cz/v2/clouds/'.$data["cloudid"].'/warehouses?'.$filter;


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
    'Authorization: Bearer '.$data["access_token"]
  ),
));

$response = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if(!empty($response)){
    if($status_code == 200){

        $data_response = json_decode($response,true);

        //return json_encode($data_response["data"],true);
        return $data_response["data"];

    }else{
        //Logovat odpověď a status_code
        central_logs("Odpověď funkce dotypos_sync_getDotyposStockList() - \n Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","debug");  
        central_logs("Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","info");        
      
        return;
    }
}else{
    //Logovat odpověď a status_code
    central_logs("Odpověď funkce dotypos_sync_getDotyposStockList() - \n Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","debug");
    central_logs("Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","info");        

    return;
}

curl_close($curl);

    }
}
/*Konec get funkcí */


/*Post funkce*/
function dotypos_sync_webhook_create($post_data) {
    global $wpdb;

    if (!empty($post_data)) {
        $access_token_data = dotypos_sync_getDotyposAccessToken();

        if (!empty($access_token_data)) {
            $data = $access_token_data;
            $stockhook_url = get_site_url() . 'wp-json/dotypos/v1/dotypos-stockhook/';

            // Definice prvního webhooku (STOCKLOG)
            $stockhook_data = [
                "_cloudId" => $data["cloudid"],
                "url" => $stockhook_url,
                "method" => "POST",
                "payloadEntity" => "STOCKLOG",
                "_warehouseId" => $post_data["warehouse_id"],
                "payloadVersion" => "V1"
            ];

            // Vytvoření prvního webhooku
            $stockhook_response = dotypos_create_webhook($data["cloudid"], $data["access_token"], $stockhook_data);

            if (!empty($stockhook_response) && $stockhook_response['status_code'] == 200) {
                $stockhook_id = $stockhook_response['data']['id'];
                $webhook_changes_url = get_site_url() . 'wp-json/dotypos/v1/dotypos-product-update/';

                // Definice druhého webhooku (PRODUCT)
                $producthook_data = [
                    "_cloudId" => $data["cloudid"],
                    "url" => $webhook_changes_url,
                    "method" => "POST",
                    "payloadEntity" => "PRODUCT",
                    "payloadVersion" => "V1"
                ];

                // Vytvoření druhého webhooku
                $producthook_response = dotypos_create_webhook($data["cloudid"], $data["access_token"], $producthook_data);

                if (!empty($producthook_response) && $producthook_response['status_code'] == 200) {
                    $producthook_id = $producthook_response['data']['id'];

                    // Vrácení obou ID
                    $return_data = [
                        "warehouse_id" => $post_data["warehouse_id"],
                        "warehouse_name" => $post_data["warehouse_name"],
                        "webhook_id" => $stockhook_id,
                        "webhook_changes_id" => $producthook_id
                    ];

                    return $return_data;
                } else {
                    // Logování chyby při vytváření druhého webhooku
                    central_logs("PRODUCT Webhook Error: {$producthook_response['response']} - {$producthook_response['status_code']}", "", "debug");
                }
            } else {
                // Logování chyby při vytváření prvního webhooku
                central_logs("STOCKLOG Webhook Error: {$stockhook_response['response']} - {$stockhook_response['status_code']}", "", "debug");
            }
        } else {
            // Vrácení již existujícího webhook_id
            $return_data = [
                "webhook_id" => $access_token_data['webhook_id']
            ];
            return $return_data;
        }
    }
}
function dotypos_create_webhook($cloudid, $access_token, $webhook_data) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.dotykacka.cz/v2/clouds/' . $cloudid . '/webhooks',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($webhook_data),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            'Authorization: Bearer ' . $access_token
        ),
    ));

    $response = curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
        'response' => $response,
        'status_code' => $status_code,
        'data' => json_decode($response, true)
    ];
}

function dotypos_sync_dotypos_productid($plu){
    global $wpdb;

    $access_token_data = dotypos_sync_getDotyposAccessToken();

    if(!empty($access_token_data)){

        $data = $access_token_data;
        $filter = "filter=plu%7Cin%7C".$plu.";deleted%7Ceq%7Cfalse";
        $request_url = 'https://api.dotykacka.cz/v2/clouds/'.$data["cloudid"].'/products?'.$filter;


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
            CURLOPT_HEADER => true, // Získání hlaviček v odpovědi
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json; charset=UTF-8',
                'Content-Type: application/json; charset=UTF-8',
                'Authorization: Bearer ' . $data["access_token"]
            ),
        ));
        
        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Oddělení hlaviček od těla odpovědi
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        // Parsování hlaviček
        $header_lines = explode("\r\n", $headers);
        $etag = '';
        foreach ($header_lines as $header_line) {
            if (stripos($header_line, 'ETag:') === 0) {
                $etag = trim(substr($header_line, 5));
                break;
            }
        }
        
        // Logování a práce s daty
        central_logs("Odpověď funkce dotypos_sync_dotypos_productid() - \n Request -> {$request_url} \n Response -> {$body} \n Status code -> {$status_code} \n ETag -> {$etag}", "", "debug");
        
        if (!empty($body)) {
            if ($status_code == 200) {
                $data_response = json_decode($body, true);
                if ($data_response["totalItemsCount"] == 1) {
                    $return_data = [
                        "dotypos_product_id" => $data_response['data'][0]['id'],
                        "eTag" => $etag,
                        "price_with_vat" => $data_response['data'][0]['priceWithVat'],
                        "vat" => $data_response['data'][0]['vat'],
                        "name" => $data_response['data'][0]['name']
                    ];

                    return $return_data;
                }
            
        }else{
        //Logovat odpověď a status_code
        central_logs("Odpověď funkce dotypos_sync_dotypos_productid() - \n Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","debug");  
        central_logs("Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","info");        
      
        return;
    }
}else{
    //Logovat odpověď a status_code
    central_logs("Odpověď funkce dotypos_sync_dotypos_productid() - \n Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","debug");
    central_logs("Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","info");        

    return;
}

curl_close($curl);

    }   
}


function dotypos_sync_dotypos_stock_status($productid){
    global $wpdb,$logger;

    $access_token_data = dotypos_sync_getDotyposAccessToken();

    if(!empty($access_token_data)){

        $data = $access_token_data;
        $request_url = 'https://api.dotykacka.cz/v2/clouds/'.$data["cloudid"].'/warehouses/'.$data["warehouse_id"].'/products/'.$productid;


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
    'Authorization: Bearer '.$data["access_token"]
  ),
));

$response = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
central_logs("Odpověď funkce dotypos_sync_dotypos_stock_status() - \n Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","debug");  


    if($status_code == 200){

        $data_response = json_decode($response,true);
    
        $stock_status = $data_response["stockQuantityStatus"];
        $purchase_price = $data_response["purchasePriceWithoutVat"];

        return ["stock_status"=>$stock_status,"purchase_price"=>$purchase_price];

    }elseif($status_code == 404){
    
        return ["stock_status"=>0,"purchase_price"=>null];

    }else{
        //Logovat odpověď a status_code
        central_logs("Request -> {$request_url} \n Response -> {$response} \n Status code -> {$status_code}","","info");        
        return;
    }


curl_close($curl);

    }   
}


function dotypos_sync_send_stock_dotypos($post_data){

$dotypos_product_id = $post_data["dotypos_product_id"] ? $post_data["dotypos_product_id"] : null;
$quantity = $post_data["quantity"] ? $post_data["quantity"] : null;
$purchase_price = $post_data["purchase_price"] ? $post_data["purchase_price"] : null;
$operation = $post_data["operation"] ? $post_data["operation"] : null;
$note = $post_data["note"] ? $post_data["note"] : null;

 $request_url = null;
 $request_body_pre = null;

if($dotypos_product_id != null || $quantity != null || $operation !== null){
    $access_token_data = dotypos_sync_getDotyposAccessToken();

    if(!empty($access_token_data)){

        $data = $access_token_data;

        if($operation == "stockup"){

$request_url = 'https://api.dotykacka.cz/v2/clouds/'.$data["cloudid"].'/warehouses/'.$data["warehouse_id"].'/stockups';

$request_body_pre = [
    "invoiceNumber" => "WooCommerce",
    "items" => [
        [
            "_productId" => $dotypos_product_id,
            "quantity" => $quantity,
            "purchasePrice" => $purchase_price
        ]
    ]
        ];

        }

        if($operation == "sale"){

$request_url = 'https://api.dotykacka.cz/v2/clouds/'.$data["cloudid"].'/warehouses/'.$data["warehouse_id"].'/sales';

$request_body_pre = [
    "items" => [
        [
            "_productId" => $dotypos_product_id,
            "quantity" => $quantity,
            "note" => $note
        ]
    ]
];

        }



if($request_url != null || $request_body_pre != null){


    
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
        'Authorization: Bearer '.$data["access_token"]
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    central_logs("Odpověď funkce dotypos_sync_send_stock_dotypos() - \n Request -> {$request_url} \n Request body -> {$request_body} \n Response -> {$response} \n Status code -> {$status_code}","","debug");  

    if($status_code == 200){

        return $response = [
            "response_msg" => 'Stav skladu byl upraven'
        ];
    
    }else{
        

    }

}


    }
}

}

function dotypos_sync_update_product($post_data){

    $dotypos_product_id = $post_data["dotypos_product_id"] ? $post_data["dotypos_product_id"] : null;
    $priceWithVat = $post_data["regular_price"];
    $priceWithoutVat = $post_data["price_without_vat"] ? $post_data["price_without_vat"] : null;
    $eTag = $post_data["eTag"] ? $post_data["eTag"] : null;
    $name = $post_data["name"];

    if($dotypos_product_id != null || $eTag != null){
        $access_token_data = dotypos_sync_getDotyposAccessToken();
    
        if(!empty($access_token_data)){
    
            $data = $access_token_data;

            $request_url = 'https://api.dotykacka.cz/v2/clouds/'.$data["cloudid"].'/products/'.$post_data["dotypos_product_id"];

            $request_body_pre = [
                "priceWithoutVat" => $priceWithoutVat,
                "priceWithVat" => $priceWithVat,
                "name"=>$name,
            ];
    
    if($request_url != null || $request_body_pre != null){
        
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
          CURLOPT_CUSTOMREQUEST => 'PATCH',
          CURLOPT_POSTFIELDS => $request_body,
          CURLOPT_HTTPHEADER => array(
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            'Authorization: Bearer '.$data["access_token"],
            'If-Match: '.$eTag
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        central_logs("Odpověď funkce dotypos_sync_update_product() - \n Request -> {$request_url} \n Request body -> {$request_body} \n Response -> {$response} \n Status code -> {$status_code}","","debug");  
    
        if($status_code == 200){
        
        }else{
            
    
        }
    
    }
    
    
        }
    }
    
    }
?>