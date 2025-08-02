<?php
    $productsToChange = [];
    $i = 0;
    $p = 0;

        $page = 1;

        do {

            $request_url =
            "https://api.dotykacka.cz/v2/clouds/399909622/warehouses/182944383/products?page=".$page."&limit=100&filter=deleted%7Ceq%7Cfalse&sort=name";

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $request_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json; charset=UTF-8",
                    "Content-Type: application/json; charset=UTF-8",
                    "Authorization: Bearer eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3NTM4ODk3MDEsInN1YiI6ImppcmkubGlza2FAZG90eWthY2thLmN6IiwidmVyc2lvbiI6MSwidXNlci1pZCI6MjEwMzM0OCwidG9rZW4taWQiOjE2ODE3OTEwODk3NzIwNzksImFjY2VzcyI6eyJjbG91ZC1pZHMiOlszOTk5MDk2MjJdLCJhY3RpdmUiOnsiY2xvdWQtaWQiOjM5OTkwOTYyMiwicGVybWlzc2lvbnMiOiJmMzkvZjM5L2YzOS9mLy8vLzM5L2YzLy9mZjkvLzM5L2YzOS8vMzkvZi8vLy8zOS8vMy8vZjM5L2YvOS8vMzkvZjMvLy8vLy8vLy8vZjM4QWYzOS9mMzkvZjMvL2YvOS8vMy8vZi85Ly8vLy9mLy8vLzMvLy8vOS8vLy8vLy8vLy8zLy8vLy8vLy8vLyJ9fSwiY2xpZW50LWlkIjoic3RvY2t1ai5jeiIsInJvbGVzIjpbIlJPTEVfVVNFUiIsIlJPTEVfQURNSU4iLCJST0xFX1NVUEVSX0FETUlOIiwiUk9MRV9BRE1JTklTVFJBVElWRSJdfQ.QLUs8TgImH1GXRJLe0Qf3SH2T5s3_2E1hwxIIw4jbnyF1Hd-afND_Kr1gGodvfmRd1PF6MyyxxorT1HwM1SoHIgmTYZGSuoAy-Q2W_Om2COWUAPI7ELQPPblv1oXMAVeYqx7fG0u2Hj8a6cGxD1y5rUnjmGqqFKiB2_HxFCl6b173hcCI-3XtffJ8KaENp7q4GK2WqMLi47EIcliX7_FoLUy795CBIVvRw3jTWzQIDJhuML1IxcJ5jdvG-A6TIHQN1d7yRW1uIk5g6Kwxx_2xNWWy3RwrGcLYBY90f3X_HJAkdOuKxwZam75enGVS58xt7ztEys671bPmYBwNnMUHg",
                ],
            ]);

            $response = curl_exec($curl);

            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($status_code == 404) {
                continue;
            }

            if ($status_code == 200) {
                $data = json_decode($response, true);
                $current_page = $data["currentPage"];
                $next_page = $data["nextPage"];

                foreach ($data["data"] as $product) {
                    if (!empty($product["plu"][0])) {
                        $sku = $product["plu"][0];
                        $quantity = $product["stockQuantityStatus"];

                        $productsToChange[] = [
                            "sku" => $sku,
                            "quantity" => $quantity
                        ];

                    }

                    $p++;
                }

                if (!is_null($next_page)) {
                    
                    $page = $next_page;
                    
                }
            } else {
            }
            $i++;
            echo "Dávka: ".$i . "Počet produktů: ".$data["totalItemsOnPage"] . "<br />";
        } while (!is_null($next_page));

?>