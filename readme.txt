Zobrazení stránky / průvodce aktivací je na základě ajax volání funkce dotypos_sync_page_view

JS funkce jl_fetchDotyposStock() volá PHP funkci jl_get_dotypos_stock_list(), ta volá PHP funkci jl_getDotyposAccessToken() ze souboru API.
Po vypsání skladů je tlačítkem Pokračovat zasláno ID a název skladu do PHP funkce jl_set_dotypos_webhook() a ta potom do jl_postWebhook() a čeká na dokončení. Po dokončení zapíše ID a název do databáze. JS pak skryje pole pro výběr skladu, respektive jej removne a zobrazí jaký sklad je aktivní a zbytek nastavení.