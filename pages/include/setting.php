<div id="dotypos_cloud">
</div>

<div id="stock_select" style="display: none;">
    <h2><?php _e('Vyberte sklad', 'dotypos_sync'); ?></h2>
    <select id="warehouse-select"></select>
    <button id="confirm-selection" class="button button-primary">
        <?php _e('Potvrdit', 'dotypos_sync'); ?>
    </button>
</div>

<div id="settings_box" style="display: none;">
    <div id="selected_stock">
        <h2><?php _e('Vybraný sklad', 'dotypos_sync'); ?></h2>
    </div>

    <div id="dotypos_settings">
        <h2><?php _e('Synchronizace z Dotykačky', 'dotypos_sync'); ?></h2>
        <div class="setting-content">
            <label>
                <input type="checkbox" class="setting-checkbox" data-id="setting_from_dotypos_price" />
                <?php _e('Synchronizace cen z Dotykačky', 'dotypos_sync'); ?>
            </label>
            <label>
                <input type="checkbox" class="setting-checkbox" data-id="setting_from_dotypos_name" />
                <?php _e('Synchronizace názvu z Dotykačky', 'dotypos_sync'); ?>
            </label>
        </div>
    </div>

    <div id="woo_settings">
        <h2><?php _e('Synchronizace z WooCommerce', 'dotypos_sync'); ?></h2>
        <div class="setting-content">
            <label>
                <input type="checkbox" class="setting-checkbox" data-id="setting_from_woo_price" />
                <?php _e('Synchronizace cen z WooCommerce', 'dotypos_sync'); ?>
            </label>
            <label>
                <input type="checkbox" class="setting-checkbox" data-id="setting_from_woo_name" />
                <?php _e('Synchronizace názvu z WooCommerce', 'dotypos_sync'); ?>
            </label>
        </div>
    </div>

    <div id="stock_sync_settings">
        <h2><?php _e('Synchronizace skladových pohybů', 'dotypos_sync'); ?></h2>
        <div class="setting-content">
            <label>
                <input type="checkbox" class="setting-checkbox" data-id="setting_from_woo_stockhook" />
                <?php _e('Z Dotykačky do WooCommerce', 'dotypos_sync'); ?>
            </label>
            <label>
                <input type="checkbox" class="setting-checkbox" data-id="setting_from_woo_stockhook" />
                <?php _e('Z WooCommerce do Dotykačka', 'dotypos_sync'); ?>
            </label>
        </div>
    </div>

    <div class="operations">
        <h2><?php _e('Přenos produktů z Dotykačky do WooCommerce', 'dotypos_sync'); ?></h2>
        <button onclick="product_transfer_from_dotypos_action()">
            <?php _e('Spustit přenos produktů', 'dotypos_sync'); ?>
        </button>
        <br /><br /><br /><br />
        <h3><?php _e('Přenos stavu skladu z Dotykačky do WooCommerce', 'dotypos_sync'); ?></h3>
        <button onclick="stock_transfer_from_dotypos_action()">
            <?php _e('Spustit přenos stavu skladu', 'dotypos_sync'); ?>
        </button>
    </div>
</div>

<script>
    $(document).ready(function() {
        jl_getDotyposSettingsCloud();
    });
</script>
