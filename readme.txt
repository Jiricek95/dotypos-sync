Zobrazení stránky / průvodce aktivací je na základě ajax volání funkce dotypos_sync_page_view

JS funkce jl_fetchDotyposStock() volá PHP funkci jl_get_dotypos_stock_list(), ta volá PHP funkci jl_getDotyposAccessToken() ze souboru API.
Po vypsání skladů je tlačítkem Pokračovat zasláno ID a název skladu do PHP funkce jl_set_dotypos_webhook() a ta potom do jl_postWebhook() a čeká na dokončení. Po dokončení zapíše ID a název do databáze. JS pak skryje pole pro výběr skladu, respektive jej removne a zobrazí jaký sklad je aktivní a zbytek nastavení.

dotypos_sync_handle_product_save_action a dotypos_sync_data_sync_to_dotypos zajišťují sync z Woo do DTK. Wordpress_function


Hodnoty databáze
setting_from_dotypos_price -> Sync ceny z Dotykačky do Woo
setting_from_dotypos_name -> Sync názvu z Dotykačky
setting_from_woo_price -> Sync ceny z Woo
setting_from_woo_name -> Sync názvu z Woo
setting_from_dotypos_stockhook -> Sync skladu z Dotykačky
setting_from_woo_stockhook -> Sync skladu z Woo