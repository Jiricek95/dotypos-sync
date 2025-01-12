<!DOCTYPE html>
<html lang="<?php echo substr(get_locale(), 0, 2); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <title>Dotypos Sync</title>
</head>
<body>

<div id="content">

<?php
global $wpdb;
    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT value FROM " . DOTYPOSSYNC_TABLE_NAME . " WHERE `key` = %s",
            "refresh_token_dotypos"
        )
    );

    if (!empty($result)) {
        require_once DOTYPOSSYNC_PLUGIN_DIR ."pages/include/setting.php";
    } else {
        require_once DOTYPOSSYNC_PLUGIN_DIR ."pages/include/guide.php";
    }
?>
</div>

 
</body>
</html>