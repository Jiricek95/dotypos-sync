<?php
/**
 * DotyPos sync
 *
 * @package       DOTYPOSSYNC
 * @author        Jiří Liška
 * @version       2.0.28
 *
 * @wordpress-plugin
 * Plugin Name:   DotyPos sync
 * Plugin URI:    https://liskajiri.cz/dotypos_woo_sync
 * Description:   Doplněk umožňující synchronizaci produktů mezi WooCommerce a Dotykačkou
 * Version:       2.0.28
 * Author:        Jiří Liška
 * Author URI:    https://liskajiri.cz
 * Text Domain:   dotypos-sync
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if (!defined("ABSPATH")) {
    exit();
}

// Define plugin version (for internal use)
define("DOTYPOSSYNC_VERSION", "2.0.28");

// Plugin Root File
define("DOTYPOSSYNC_PLUGIN_FILE", __FILE__);

// Plugin base
define("DOTYPOSSYNC_PLUGIN_BASE", plugin_basename(DOTYPOSSYNC_PLUGIN_FILE));

// Plugin Folder Path
define("DOTYPOSSYNC_PLUGIN_DIR", plugin_dir_path(DOTYPOSSYNC_PLUGIN_FILE));

// Plugin Folder URL
define("DOTYPOSSYNC_PLUGIN_URL", plugin_dir_url(DOTYPOSSYNC_PLUGIN_FILE));
//Github token
define('GITHUB_ACCESS_TOKEN', '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');
//Připojení souborů
//Funkce pro Dotykačku
require_once DOTYPOSSYNC_PLUGIN_DIR . "functions/dotypos_functions.php";
//Funkce pro Wordpress
require_once DOTYPOSSYNC_PLUGIN_DIR . "functions/wordpress_functions.php";
//Dotpos GET API
require_once DOTYPOSSYNC_PLUGIN_DIR . "dotypos/dotypos_api.php";
//Order porcess
require_once DOTYPOSSYNC_PLUGIN_DIR . "functions/processing_woo_order.php";

// Kontrola verze pro dbDelta
require_once ABSPATH . "wp-admin/includes/upgrade.php";

// Definice konstanty pro název tabulky
define("DOTYPOSSYNC_TABLE_NAME", $wpdb->prefix . "dotypos_sync_config");

//Globální proměnná pro název tabulky v databázi
global $wpdb;

//Registrace domény pro překladové soubory
add_action('init', 'dotypos_sync_load_textdomain');
function dotypos_sync_load_textdomain()
{
    load_plugin_textdomain(
        'dotypos-sync',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}


//Hook spouštěný instalací
register_activation_hook(__FILE__, "dotypos_sync_install_database");

// Instalace tabulky databáze
function dotypos_sync_install_database()
{
    global $wpdb;
    $table = DOTYPOSSYNC_TABLE_NAME;

    // SQL dotaz na ověření existence tabulky
    $query = $wpdb->prepare("SHOW TABLES LIKE %s", DOTYPOSSYNC_TABLE_NAME);

    // Získání výsledku
    $result = $wpdb->get_var($query);

    if ($result === DOTYPOSSYNC_TABLE_NAME) {
        error_log("Table -> " . DOTYPOSSYNC_TABLE_NAME . " is exists");
    } else {
        //Vytvoření tabulky
        // Získání kódování a sady znaků databáze
        $charset_collate = $wpdb->get_charset_collate();

        // SQL příkaz pro vytvoření tabulky
        $sql =
            "CREATE TABLE " .
            DOTYPOSSYNC_TABLE_NAME .
            "(
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` TEXT NOT NULL,
            `value` TEXT NOT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        error_log($sql);

        // Spuštění příkazu
        dbDelta($sql);
    }
}

//Hook spouštěný při odinstalaci
register_uninstall_hook(__FILE__, "dotypos_sync_uninstall");
//Odstranění při odmazání doplňku
function dotypos_sync_uninstall()
{
    
    global $wpdb;

    $sql = "DROP TABLE IF EXISTS ".DOTYPOSSYNC_TABLE_NAME.";";
    $wpdb->query($sql);
    
}

// Přidání akce do WordPress, která zaregistruje vaši funkci menu
add_action("admin_menu", "dotypos_sync_add_admin_menu");
// Funkce pro registraci položky menu
function dotypos_sync_add_admin_menu()
{
    add_menu_page(
        "DotyPOS Nastavení", // Název stránky
        "DotyPOS", // Název menu
        "manage_options", // Oprávnění nutné pro zobrazení menu
        "dotypos-setting", // Slug menu
        "dotypos_page", // Funkce, která zobrazí obsah stránky nastavení
        "dashicons-admin-generic", // Ikona menu
        20 // Pozice v menu
    );
}



add_action("admin_enqueue_scripts", "dotypos_sync_enqueue_scripts");
function dotypos_sync_enqueue_scripts($hook)
{
    // Načtení skriptů pouze na stránce nastavení pluginu nebo detailu produktu
    if ($hook === "toplevel_page_dotypos-setting" || $hook === "post.php") {
        // Načti jQuery (není nutné explicitně kontrolovat, zda je již enqueued, pokud používáš jako závislost)
        wp_enqueue_script(
            "sweetalert",
            "https://cdn.jsdelivr.net/npm/sweetalert2@10",
            ["jquery"],
            null,
            true
        );
        wp_enqueue_script(
            "scripts",
            DOTYPOSSYNC_PLUGIN_URL . "js/scripts.js",
            ["jquery"],
            null,
            true
        );
        wp_enqueue_script(
            "loader-scripts",
            DOTYPOSSYNC_PLUGIN_URL . "js/libraries/loader.js",
            ["jquery"],
            null,
            true
        );

        // Načti CSS
        wp_enqueue_style(
            "dotypos-sync-style",
            DOTYPOSSYNC_PLUGIN_URL . "/pages/style/style.css",
            [],
            "1.0.0",
            "all"
        );

        // Localize script (pro AJAX)
        wp_localize_script("scripts", "dotypos_scripts", [
            "ajax_url" => admin_url("admin-ajax.php"),
            "html_path" => DOTYPOSSYNC_PLUGIN_URL . "pages/include/", // Absolutní URL k HTML složce
        ]);
    }
}


// Funkce, která zobrazí obsah stránky nastavení
function dotypos_page()
{
    include_once "pages/dotypos_page.php";
}

//Zobrazení stránky
add_action("wp_ajax_dotypos_sync_page_view", "dotypos_sync_page_view");
function dotypos_sync_page_view()
{
    global $wpdb;

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT value FROM " . DOTYPOSSYNC_TABLE_NAME . " WHERE `key` = %s",
            "refresh_token_dotypos"
        )
    );

    if (!empty($result)) {
        // Hodnota existuje a není prázdná
        wp_send_json(["status" => "success"]);
    } else {
        // Hodnota pro daný klíč neexistuje nebo je prázdná
        wp_send_json(["status" => "empty"]);
    }
}

//Dotypos token save
add_action("wp_ajax_dotypos_token_save", "dotypos_token_save");

function dotypos_token_save() {
    global $wpdb;
    if (!empty($_GET["token"]) && !empty($_GET["cloudid"])) {
        $data = [
            "refresh_token_dotypos" => $_GET["token"],
            "cloudid" => $_GET["cloudid"],
        ];

        $results = [];
        foreach ($data as $key => $value) {
            $sql = $wpdb->prepare(
                "INSERT INTO " . DOTYPOSSYNC_TABLE_NAME . " (`key`, `value`) 
                 VALUES (%s, %s) 
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
                $key,
                $value
            );

            $results[$key] = $wpdb->query($sql) !== false;
        }

        // Zkontrolujeme výsledky operací
        if (in_array(false, $results, true)) {
            $message = "Propojení nebylo úspěšné, zkuste aktualizovat stránku nebo ji ukončete a propojení vyvolejte znovu.";
        } else {
            $message = "Propojení bylo úspěšné, můžete uzavřít stránku.";
        }
    } else {
        $message = "Chybí parametry pro zpracování.";
    }

    // Vrátíme HTML odpověď
    echo "<!DOCTYPE html>
    <html lang='cs'>
    <head>
        <meta charset='UTF-8'>
        <title>Dotykačka Propojení</title>
    </head>
    <body>
        <div style='text-align: center; margin-top: 50px;'>
            <p>$message</p>
            <button onclick='closeWindow()'>Zavřít</button>
        </div>
        <script>
            function closeWindow() {
                if (window.opener) {
                    // Pošleme zprávu původní stránce
                    window.opener.postMessage('integration_success', '*');
                }
                window.close(); // Zavřeme okno
            }
        </script>
    </body>
    </html>";
    exit;
}


// Funkce pro vložení nebo aktualizaci do databáze
function dotypos_sync_db_insert_update($key, $value)
{
    global $wpdb;

    $sql = $wpdb->prepare(
        "INSERT INTO " .
            DOTYPOSSYNC_TABLE_NAME .
            " (`key`, `value`) 
         VALUES (%s, %s) 
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
        $key,
        $value
    );

    if ($wpdb->query($sql)) {
        return;
    } else {
        return null;
    }
}

//Načtení cloudid z databáze
add_action("wp_ajax_dotypos_sync_cloud", "dotypos_sync_cloud");
function dotypos_sync_cloud()
{
    global $wpdb;

    // Zkontroluj, že pole 'fields' je definováno
    if (isset($_POST["fields"]) && is_array($_POST["fields"])) {
        $fields = $_POST["fields"]; // Získání pole 'fields' z AJAXu

        // Dynamické vytvoření placeholderů (%s) pro každý klíč
        $placeholders = implode(", ", array_fill(0, count($fields), "%s"));

        // Příprava dotazu s připravenými hodnotami
        $query = $wpdb->prepare(
            "SELECT `key`, `value` FROM " .
                DOTYPOSSYNC_TABLE_NAME .
                " WHERE `key` IN ($placeholders)",
            ...$fields // Rozbalení pole 'fields' pro připravené hodnoty
        );

        // Provedení dotazu
        $results = $wpdb->get_results($query, ARRAY_A);

        // Odeslání výsledků zpět do JavaScriptu
        wp_send_json($results);
    } else {
        // Pokud není pole 'fields' definováno nebo je prázdné
        wp_send_json_error(["message" => "Missing or invalid fields"]);
    }
}

//Načtení seznamu skladů z Dotykačky
add_action(
    "wp_ajax_dotypos_sync_dotypos_stock_list",
    "dotypos_sync_dotypos_stock_list"
);
function dotypos_sync_dotypos_stock_list()
{
    $dotypos_stock_list = dotypos_sync_getDotyposStockList();

    if (!empty($dotypos_stock_list)) {
        wp_send_json($dotypos_stock_list);
    }
}

//Potvrzení výběru skladu a vytvoření webhooku
add_action(
    "wp_ajax_dotypos_sync_set_dotypos_webhook",
    "dotypos_sync_set_dotypos_webhook"
);
function dotypos_sync_set_dotypos_webhook()
{
    global $wpdb;

    if ($_POST["warehouse_id"] && $_POST["warehouse_name"]) {
        $post_data = [
            "warehouse_id" => $_POST["warehouse_id"],
            "warehouse_name" => $_POST["warehouse_name"],
        ];

        $webhook_data = dotypos_sync_webhook_create($post_data);

        if (!empty($webhook_data)) {
            // Výsledky operací
            $results = [];
            // Cyklus přes data
            foreach ($webhook_data as $key => $value) {
                $sql = $wpdb->prepare(
                    "INSERT INTO " .
                        DOTYPOSSYNC_TABLE_NAME .
                        " (`key`, `value`) 
                 VALUES (%s, %s) 
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
                    $key,
                    $value
                );

                // Uložíme výsledek (true/false podle úspěchu)
                $results[$key] = $wpdb->query($sql) !== false;
            }

    // Souhrnná odpověď
    if (in_array(false, $results, true)) {
        // Pokud některý insert/update selhal
        wp_send_json_error(); // Tady vracíme chybu
    } else {
        // Pokud všechny operace proběhly úspěšně
        wp_send_json_success(); // Tady vracíme úspěch
    }
        } else {
            // Chybí GET parametry
            wp_send_json_error();
        }
    }
}


/*
//Příjem dat z Dotykačky
*/
require_once DOTYPOSSYNC_PLUGIN_DIR ."webhooks/received/dotypos_stockhook.php";
require_once DOTYPOSSYNC_PLUGIN_DIR ."webhooks/received/dotypos_products_changes.php";
add_action('rest_api_init', function () {
    // Endpoint pro příjem webhooku skladu
    register_rest_route('dotypos/v1', '/dotypos-stockhook/', [
        'methods' => 'POST',
        'callback' => 'dotypos_sync_control_stockhook',
        'permission_callback' => '__return_true',
    ]);

    // Endpoint pro aktualizaci produktů
    register_rest_route('dotypos/v1', '/dotypos-product-update/', [
        'methods' => 'POST',
        'callback' => 'dotypos_sync_control_updatehook',
        'permission_callback' => '__return_true',
    ]);

    // Endpoint pro zapnutí logování
    register_rest_route('dotypos/v1', '/activate-debug-logs/', [
        'methods' => 'POST',
        'callback' => 'dotypos_activate_debug_logs',
        'permission_callback' => '__return_true',
    ]);
});

// 🔹 Funkce pro zapnutí logování
function dotypos_activate_debug_logs(WP_REST_Request $request) {
    global $wpdb;

    // Získání JSON dat
    $data = $request->get_json_params();

    if (!$data || empty($data['debug_logs'])) {
        return new WP_REST_Response(['error' => 'Bad JSON'], 400);
    }

    // Vložení nebo aktualizace hodnoty v databázi
    $sql = $wpdb->prepare(
        "INSERT INTO " . DOTYPOSSYNC_TABLE_NAME . 
        " (`key`, `value`) 
         VALUES (%s, %s) 
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
        'debug_log',
        $data['debug_logs']
    );

    if ($wpdb->query($sql) === false) {
        return new WP_REST_Response(['error' => 'Database error'], 400);
    }

    return new WP_REST_Response(['success' => 'new_value ' . $data['debug_logs']], 200);
}



//logovací funkce
function central_logs($text,$content,$mode){

    global $wpdb;
/*
    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT value FROM " . DOTYPOSSYNC_TABLE_NAME . " WHERE `key` = %s",
            "debug_log"
        )
    );
*/

$result = 1;
    if (!empty($result) && $result == 1 && $mode == 'debug') {

            $logger = wc_get_logger();
            $context = array( 'source' => 'dotypos-j-l' );
            $logger->log( 'Debug', $text .' - '. $content, $context );
        
    } elseif($mode == 'info') {

        $logger = wc_get_logger();
        $context = array( 'source' => 'dotypos-j-l' );
        $logger->log( 'info', $text .' - '. $content, $context );

    }
}


add_action('wp_ajax_save_checkbox_setting', 'save_checkbox_setting');
function save_checkbox_setting() {
    global $wpdb;

    if (!isset($_POST['data_id']) || !isset($_POST['value'])) {
        wp_send_json_error('Neplatné parametry nebo nonce.');
    }

    $data_id = sanitize_text_field($_POST['data_id']);
    $value = intval($_POST['value']);

    // Zápis do databáze
    $table_name = DOTYPOSSYNC_TABLE_NAME; // Vaše tabulka
    $result = $wpdb->replace(
        $table_name,
        ['key' => $data_id, 'value' => $value],
        ['%s', '%d']
    );

    if ($result !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Chyba při ukládání do databáze.');
    }
}

add_action('wp_ajax_load_checkbox_settings', 'load_checkbox_settings');
function load_checkbox_settings() {
    global $wpdb;

   
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT `key`, `value` FROM ".DOTYPOSSYNC_TABLE_NAME." WHERE `key` LIKE %s",
            'setting_%'
        ),
        ARRAY_A
    );

    if ($results) {
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['key']] = $row['value'];
        }
        wp_send_json_success($settings);
    } else {
        wp_send_json_error('Žádné nastavení nenalezeno.');
    }
}

function dotypos_sync_get_stock_sync_from_dotypos(){

    global $wpdb;

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT value FROM " . DOTYPOSSYNC_TABLE_NAME . " WHERE `key` = %s",
            "setting_from_dotypos_stockhook"
        )
    );

    if (!empty($result) || $result == 1) {
        // Hodnota existuje a není prázdná
        return true;
    } else {
        // Hodnota pro daný klíč neexistuje nebo je prázdná
        return false;
    }

}
function dotypos_sync_get_stock_sync_from_woo(){

    global $wpdb;

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT value FROM " . DOTYPOSSYNC_TABLE_NAME . " WHERE `key` = %s",
            "setting_from_woo_stockhook"
        )
    );

    if (!empty($result) || $result == 1) {
        // Hodnota existuje a není prázdná
        return true;
    } else {
        // Hodnota pro daný klíč neexistuje nebo je prázdná
        return false;
    }
    
}


//Zsíkání nastavení
function dotypos_sync_get_sync_setting($setting_key){

    global $wpdb;

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT value FROM " . DOTYPOSSYNC_TABLE_NAME . " WHERE `key` = %s",
            $setting_key
        )
    );

    // Vrácení true nebo false
    return (!empty($result) || $result == 1);

}


function dotypos_check_for_updates($transient) {
    static $already_run = false;
    if ($already_run) {
        return $transient;
    }
    $already_run = true;

    central_logs('Spuštění funkce','','debug');

    if (empty($transient->checked)) {
        return $transient;
    }

    $repo_owner = 'Jiricek95';  
    $repo_name  = 'dotypos-sync';  
    $plugin_slug = plugin_basename(__FILE__);  

    $url = "https://api.github.com/repos/$repo_owner/$repo_name/releases/latest";

    // ✅ Přidání autentizace, pokud je dostupný token
    $headers = [
        'User-Agent' => 'WordPress-Plugin-Updater',
    ];

    if (defined('GITHUB_ACCESS_TOKEN')) {
        $headers['Authorization'] = 'token ' . GITHUB_ACCESS_TOKEN;
    }

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => $headers,
    ]);

    if (is_wp_error($response)) {
        return $transient;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    central_logs('Data aktualizace',json_encode($data,true),'debug');

    if (!$data || empty($data['tag_name']) || empty($data['assets'])) {
        return $transient;
    }

    $new_version = $data['tag_name'];
    $download_url = null;

    foreach ($data['assets'] as $asset) {
        if (strpos($asset['name'], '.zip') !== false) {
            $download_url = $asset['browser_download_url'];
            break;
        }
    }

    if (!$download_url) {
        return $transient;
    }

    if (version_compare($transient->checked[$plugin_slug], $new_version, '<')) {
        $transient->response[$plugin_slug] = (object) [
            'slug'        => $repo_name,
            'new_version' => $new_version,
            'package'     => $download_url,
            'tested'      => '6.4',
            'requires'    => '6.0',
        ];
    }

    return $transient;
}
add_filter('site_transient_update_plugins', 'dotypos_check_for_updates');
