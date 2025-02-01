function setDotypos() {
    //Získání URL pro redirect
    const baseUrl = window.location.protocol + "//" + window.location.host;
    //const baseUrl = 'http://localhost/wordpress';
    $.ajax({
        url: 'https://liskajiri.cz/wordpress-dotypos/secreted_key/fetch_keys.php',
        data: {
            secreted_key_value: '5GkhbFhvGIosfslDSS67'
        },
        type: 'GET', // Metoda HTTP
        dataType: 'json', // Typ očekávané odpovědi
        success: function(data) {
            if (data.status === 'success') {
                // Zpracování dat
                const key = data.key;
                const secret = data.secret;

                openCenteredWindow('https://admin.dotykacka.cz/client/connect?client_id=' + key + '&client_secret=' + secret + '&scope=*&redirect_uri=' + baseUrl + '/wp-admin/admin-ajax.php?action=dotypos_token_save&state=', 600, 800);
                // Můžete zde provést další akce s proměnnými `key` a `secret`
            } else {
                console.error('Chyba při načítání klíčů:', data.secret);
            }
        },
        error: function(xhr, status, error) {
            console.error('Došlo k chybě:', error);
        }
    });
}
//Otevření okna
function openCenteredWindow(url, width, height) {
    // Vypočítání polohy, kde se má okno otevřít uprostřed obrazovky
    var left = (window.screenX || window.screenLeft) + ((window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth) - width) / 2;
    var top = (window.screenY || window.screenTop) + ((window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight) - height) / 2;

    // Otevření nového okna s nastavenými rozměry a polohou
    window.open(url, '_blank', 'width=' + width + ', height=' + height + ', left=' + left + ', top=' + top);

    window.addEventListener('message', function(event) {
        // Kontrola identity a bezpečnosti zprávy je důležitá
        if (event.data === 'closed') {
            $(".loader_bg").css("display", "block");
            $(".loader_text").text("Zapisuji údaje");
            // Proveďte akce po uzavření okna, např. aktualizace stránky
            window.location.reload();
        }
    }, false);
}

window.addEventListener('message', function(event) {
    if (event.data === 'integration_success') {
        //alert('Propojení proběhlo úspěšně!');
        // Zavoláme funkci nebo aktualizujeme stránku
        location.reload();
    }
}, false);


//Obecná funkce pro aktualizaci stránky
function jl_refreshPage() {
    location.reload(); // Aktualizuje stránku
}

function license_key_check() {

}

//Funkce pro načtení obsahu 2.html
function jl_fetchAllSetting() {

}

//Funkce pro načtení nastavení synchronizace
function jl_getDotyposSettingsCloud() {
    $.fn.loader('show', 'Načítám obsah...');
    var fields = ['cloudid', 'warehouse_id', 'warehouse_name'];
    $.ajax({
        url: dotypos_scripts.ajax_url, // WordPress AJAX handler
        method: 'POST',
        data: {
            action: 'dotypos_sync_cloud',
            fields: fields
        },
        success: function(response) {
            // Odpověď obsahuje data (pole objektů)
            let data = response;

            // Procházení dat a hledání konkrétního 'cloudid'
            let cloudid = null;
            let warehouse_id = null;
            let warehouse_name = null;
            // Procházení pole a získání hodnoty pro 'cloudid'
            data.forEach(function(item) {
                if (item.key === 'cloudid') {
                    cloudid = item.value; // Uložení hodnoty 'cloudid'
                }
                if (item.key === 'warehouse_id') {
                    warehouse_id = item.value;
                }
                if (item.key === 'warehouse_name') {
                    warehouse_name = item.value;
                }
            });

            // Kontrola hodnoty 'cloudid' a aktualizace HTML
            if (cloudid && cloudid !== 'undefined' && cloudid !== null) {
                jQuery('#dotypos_cloud').html('<h2>Dotypos ID Vzdálené správy</h2><br /><div><span class="cloudid">' + cloudid + '</span><span class="dashicons dashicons-trash jl-delete-icon" onclick="jl_deleteIntegration(event, this)"></span></div>');
            }
            if (warehouse_id != 'undefiend' && warehouse_id != null) {
                jQuery('#selected_stock').append(warehouse_name);
                load_setting_sync();
            } else {
                jl_fetchDotyposStock();
            }


            $.fn.loader({ action: 'remove' });

        },
        error: function() {

        }
    });
}


//Načtení skladů z Dotykačky
function jl_fetchDotyposStock() {

    const $stock_element = $('#stock_select');
    $stock_element.css('display', 'block');

    $.fn.loader({
        action: 'show',
        text: 'Načítám seznam skladů...',
        target: '#stock_select',
        type: 'inline'
    });

    $.ajax({
        url: dotypos_scripts.ajax_url, // WordPress AJAX handler
        method: 'POST',
        data: {
            action: 'dotypos_sync_dotypos_stock_list',
        },
        dataType: 'json',
        success: function(response) {

            var responseData = response;
            const $select = $('#warehouse-select');
            // Vygeneruj HTML select box
            responseData.forEach(item => {
                $select.append(`<option value="${item.id}">${item.name}</option>`);
            });

            // Přidej funkci pro potvrzení výběru
            $('#confirm-selection').on('click', function() {

                $.fn.loader({
                    action: 'show',
                    text: 'Ukládám výběr skladu'
                });


                const warehouse_id = $select.val();
                const warehouse_name = $select.find('option:selected').text();

                $.ajax({
                    url: dotypos_scripts.ajax_url, // WordPress AJAX handler
                    method: 'POST',
                    data: {
                        action: 'dotypos_sync_set_dotypos_webhook',
                        warehouse_id: warehouse_id,
                        warehouse_name: warehouse_name,
                    },
                    success: function(response) {

                        if (response.success == true) {
                            $stock_element.remove();
                            jl_getDotyposSettingsCloud();
                        }


                        $.fn.loader({ action: 'remove' });

                    },
                    error: function() {

                    }
                });


            });

            $.fn.loader({
                action: 'remove',
                target: '#stock_select'
            });

        },
        error: function() {

        }
    });
}


function jl_showElements() {
    jQuery('#settings_box').css('display', 'block');
}

//Zrušení celé integrace
function jl_deleteIntegration(event, element) {
    // Zabránění výchozí akci tlačítka
    event.preventDefault();

    // Získání ID z data atributu tlačítka
    const connectionId = jQuery(element).data('id');

    // Potvrzení akce
    const confirmation = confirm('Opravdu chcete zrušit propojení?');
    if (confirmation) {
        // AJAX požadavek
        jQuery.ajax({
            url: dotypos_scripts.ajax_url, // URL získaná z PHP
            type: 'POST',
            data: {
                action: 'delete_connection', // Název akce
                id: connectionId, // ID propojení
                nonce: dotypos_scripts.nonce, // Nonce pro zabezpečení
            },
            success: function(response) {
                if (response.success) {
                    //alert('Propojení bylo úspěšně zrušeno.');
                    location.reload(); // Obnovit stránku nebo aktualizovat DOM
                } else {
                    // alert('Nastala chyba: ' + response.data);
                }
            },
            error: function() {
                //alert('Chyba při komunikaci se serverem.');
            },
        });
    }
}



function load_setting_sync() {
    // Načítání checkboxů při načtení stránky
    jQuery.ajax({
        url: dotypos_scripts.ajax_url,
        type: 'POST',
        data: {
            action: 'load_checkbox_settings'
        },
        success: function(response) {
            if (response.success) {
                // Nastavuje checkboxy, které přišly z databáze
                $.each(response.data, function(key, value) {
                    $(`.setting-checkbox[data-id="${key}"]`).prop('checked', value === "1");
                });

                jl_showElements();
            } else {
                //alert('Chyba při načítání nastavení: ' + response.data);
                jl_showElements();
            }
        },
        error: function() {
            //alert('Chyba při komunikaci se serverem.');
            jl_showElements();
        },
    });



    $('.setting-checkbox').on('change', function() {

        let checkbox = $(this);
        let dataId = checkbox.data('id');
        let value = checkbox.is(':checked') ? 1 : 0;

        jQuery.ajax({
            url: dotypos_scripts.ajax_url, // URL získaná z PHP
            type: 'POST',
            data: {
                action: 'save_checkbox_setting',
                data_id: dataId,
                value: value
            },
            success: function(response) {
                if (response.success) {
                    //alert('Nastavení bylo uloženo.');
                } else {
                    //alert('Chyba při ukládání: ' + response.data);
                }
            },
            error: function() {
                //alert('Chyba při komunikaci se serverem.');
            },
        });
    });


}

//Práce se skladem v administraci
jQuery(document).ready(function($) {
    // Move custom buttons for simple products
    if (jQuery('#custom-stock-buttons').length) {
        jQuery('#custom-stock-buttons').insertAfter('.form-field._stock_field');
    }


    $('#from_dotypos').on('click', function(event) {
        event.preventDefault();
        var $button = $(this);
        var stock = $('#_stock').val();
        var sku = $('#_sku').val();
        var postId = $(this).attr('data-id');
        var setting = $(this).attr('setting-id');
        $('.response-msg').empty();

        // Uloží původní text tlačítka
        var originalText = $button.html();

        if (typeof sku !== 'undefined' && sku !== null && sku.length > 0) {



            // Nahradí text tlačítka spinnerem
            $button.html('<span class="spinner is-active" style="float:none; margin-left: 5px; visibility: visible;"></span> Načítám...');

            $.ajax({
                url: dotypos_scripts.ajax_url,
                type: 'POST',
                data: {
                    action: 'custom_stock_button_listen',
                    product_id: postId,
                    sku: sku,
                    stock: stock,
                    doAction: 'stock_from_dotypos_product',
                    setting: setting
                },
                success: function(response) {
                    $button.html(originalText);
                    //$('.response-msg').text(response.data.response_msg);
                    showCustomAlert(response.data.response_msg, response.data.actionType);


                    $('#_stock').text(response.data.new_stock.stock_status);
                    $('#_stock').attr('value', response.data.new_stock.stock_status);

                }
            });

        } else {
            $('.response-msg').text('Není vyplněno SKU');
        }

    });

    $('#to_dotypos').on('click', function() {
        event.preventDefault();
        var $button = $(this);
        var stock = $('#_stock').val();
        var sku = $('#_sku').val();
        var postId = $(this).attr('data-id');
        var setting = $(this).attr('setting-id');
        $('.response-msg').empty();

        // Uloží původní text tlačítka
        var originalText = $button.html();

        if (typeof sku !== 'undefined' && sku !== null && sku.length > 0) {

            // Nahradí text tlačítka spinnerem
            $button.html('<span class="spinner is-active" style="float:none; margin-left: 5px; visibility: visible;"></span> Načítám...');

            $.ajax({
                url: dotypos_scripts.ajax_url,
                type: 'POST',
                data: {
                    action: 'custom_stock_button_listen',
                    product_id: postId,
                    sku: sku,
                    stock: stock,
                    doAction: 'stock_to_dotypos_product',
                    setting: setting
                },
                success: function(response) {
                    $button.html(originalText);
                    //$('.response-msg').text(response.data.response_msg);
                    showCustomAlert(response.data.response_msg, response.data.actionType);

                }
            });
        } else {
            $('.response-msg').text('Není vyplněno SKU');
        }


    });


    // Handle clicks on the custom buttons for variations
    $(document).on('click', '.custom-variant-stock-button', function(event) {
        event.preventDefault(); // Zabraň aktualizaci stránky
        var $button = $(this);
        var variationId = $button.data('variation-id');
        var buttonId = $button.hasClass('button-primary') ? 1 : 2;
        var $variationForm = $button.closest('.woocommerce_variation');
        var doAction = $button.attr('id');
        var setting = $(this).attr('setting-id');
        $('.response-msg').empty();
        // Uloží původní text tlačítka
        var originalText = $button.html();

        // Calculate the index of the clicked variation
        var index = $('.woocommerce_variation').index($variationForm);

        // Retrieve SKU and stock status for the clicked variant
        var sku = $variationForm.find('input[name="variable_sku[' + index + ']"]').val();
        var stock = $variationForm.find('input[name="variable_stock[' + index + ']"]').val();



        if (typeof sku !== 'undefined' && sku !== null && sku.length > 0) {

            // Nahradí text tlačítka spinnerem
            $button.html('<span class="spinner is-active" style="float:none; margin-left: 5px; visibility: visible;"></span> Načítám...');


            $.ajax({
                url: dotypos_scripts.ajax_url,
                type: 'POST',
                data: {
                    action: 'custom_stock_button_listen',
                    product_id: variationId,
                    sku: sku,
                    stock: stock,
                    doAction: doAction,
                    setting: setting
                },
                success: function(response) {
                    $button.html(originalText);
                    //$('.response-msg').text(response.data.response_msg);
                    showCustomAlert(response.data.response_msg, response.data.actionType);

                    if (doAction == 'variant_stock_from_dotypos') {
                        $($variationForm.find('input[name="variable_stock[' + index + ']"]')).text(response.data.new_stock.stock_status);
                        $($variationForm.find('input[name="variable_stock[' + index + ']"]')).attr('value', response.data.new_stock.stock_status);
                    }

                }
            });

        }

    });
});

function showCustomAlert(message, actionType) {

    console.log(actionType);

    actionType = actionType ? actionType : 'cancel';

    console.log(actionType);

    Swal.fire({
        title: message,
        text: '',
        icon: 'info',
        confirmButtonText: 'OK',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then(() => {
        if (actionType === 'save') {
            // Akce pro "confirm"
            var saveButton = document.getElementById('save-post');
            if (saveButton) {
                saveButton.click(); // Kliknutí na tlačítko pro uložení produktu
            } else {
                document.getElementById('publish').click();
            }
        } else if (actionType === 'cancel') {

        }
    });
}