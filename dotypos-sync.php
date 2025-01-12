<?php
/**
 * DotyPos sync
 *
 * @package       DOTYPOSSYNC
 * @author        Jiří Liška
 * @version       2.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   DotyPos sync
 * Plugin URI:    https://liskajiri.cz/dotypos_woo_sync
 * Description:   Doplněk umožňující synchronizaci produktů mezi WooCommerce a Dotykačkou
 * Version:       2.0.0
 * Author:        Jiří Liška
 * Author URI:    https://liskajiri.cz
 * Text Domain:   dotypos-sync
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if (!defined("ABSPATH")) {
    exit();
}

// Define plugin version (for internal use)sss
define("DOTYPOSSYNC_VERSION", "2.0.0");

// Plugin Root File
define("DOTYPOSSYNC_PLUGIN_FILE", __FILE__);

// Plugin base
define("DOTYPOSSYNC_PLUGIN_BASE", plugin_basename(DOTYPOSSYNC_PLUGIN_FILE));

// Plugin Folder Path
define("DOTYPOSSYNC_PLUGIN_DIR", plugin_dir_path(DOTYPOSSYNC_PLUGIN_FILE));

// Plugin Folder URL
define("DOTYPOSSYNC_PLUGIN_URL", plugin_dir_url(DOTYPOSSYNC_PLUGIN_FILE));

//Připojení souborů
//Funkce pro Dotykačku
require_once DOTYPOSSYNC_PLUGIN_DIR . "functions/dotypos_functions.php";
//Funkce pro Wordpress
require_once DOTYPOSSYNC_PLUGIN_DIR . "functions/wordpress_functions.php";
//Dotpos GET API
require_once DOTYPOSSYNC_PLUGIN_DIR . "dotypos/dotypos_api.php";

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
    /*
    global $wpdb,$dotypos_sync_table_name;

    $sql = "DROP TABLE IF EXISTS $dotypos_sync_table_name;";
    $wpdb->query($sql);
    */
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

// Registrace skriptů
add_action("admin_enqueue_scripts", "dotypos_sync_enqueue_scripts");
function dotypos_sync_enqueue_scripts($hook)
{
    // Načtení skriptů pouze na specifické stránce (pokud máš stránku nastavení pluginu)
    // Nahraď 'my-plugin-page' názvem tvé stránky
    if ($hook !== "toplevel_page_dotypos-setting") {
        return;
    }

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
    //css
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
//Nový příjem webhooku
add_action( 'parse_request', function( $wp ){

    if ( preg_match( '/^\/dotypos-stockhook/', $_SERVER['REQUEST_URI'], $matches ) ) {
        send_nosniff_header();
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');

        // Přečtěte tělo požadavku
        $body = file_get_contents('php://input');
        // Dekódování JSON těla požadavku
        $data = json_decode($body, true); // Nastavte true pro získání dat jako pole

        if ($data) {
            // Zde můžete zpracovávat data
            dotypos_sync_control_stockhook($data);
        } else {
            // V případě chyby při dekódování JSON
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Bad JSON']);
        }

        exit; // Ukončete zpracování požadavku
    }
});

add_action( 'parse_request', function( $wp ){

    if ( preg_match( '/^\/dotypos-product-update/', $_SERVER['REQUEST_URI'], $matches ) ) {
        send_nosniff_header();
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');

        // Přečtěte tělo požadavku
        $body = file_get_contents('php://input');

        // Dekódování JSON těla požadavku
        $data = json_decode($body, true); // Nastavte true pro získání dat jako pole

        if ($data) {
            // Zde můžete zpracovávat data
            dotypos_sync_control_updatehook($data);
        } else {
            // V případě chyby při dekódování JSON
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Bad JSON']);
        }

        exit; // Ukončete zpracování požadavku
    }
});

//Zapnutí logování na dálku
add_action( 'parse_request', function( $wp ){
    global $wpdb;

    if ( preg_match( '/^\/activate_debug_logs/', $_SERVER['REQUEST_URI'], $matches ) ) {
        send_nosniff_header();
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');

        // Přečtěte tělo požadavku
        $body = file_get_contents('php://input');

        // Dekódování JSON těla požadavku
        $data = json_decode($body, true); // Nastavte true pro získání dat jako pole

        if ($data) {

            if($data['debug_logs']){

                $sql = $wpdb->prepare(
                    "INSERT INTO " .
                        DOTYPOSSYNC_TABLE_NAME .
                        " (`key`, `value`) 
                     VALUES (%s, %s) 
                     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
                    'debug_log',
                    $data['debug_logs']
                );

                // Kontrola, zda byl update úspěšný
                if ( false === $sql ) {
                    header('HTTP/1.1 400 Bad Request');
                    echo json_encode(['error' => 'Bad JSON']);
                } else {
                    header('HTTP/1.1 200');
                    echo json_encode(['success' => 'new_value '.$data['debug_logs']]);
                }

            }

        } else {
            // V případě chyby při dekódování JSON
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Bad JSON']);
        }

        exit; // Ukončete zpracování požadavku
    }
});



//logovací funkce
function central_logs($text,$content,$mode){

    global $wpdb;

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT value FROM " . DOTYPOSSYNC_TABLE_NAME . " WHERE `key` = %s",
            "debug_log"
        )
    );

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

    $table_name = DOTYPOSSYNC_TABLE_NAME; // Vaše tabulka
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
