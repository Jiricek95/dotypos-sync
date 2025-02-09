//Alert
customAlert.showAlert('Toto je informační zpráva', function() {
    console.log('Uživatel zavřel alert.');
});
//Potvrzovací dialog
customAlert.showConfirm(
    'Opravdu chcete tuto akci provést?', 
    function() {
        console.log('Uživatel potvrdil akci.');
    },
    function() {
        console.log('Uživatel odmítl akci.');
    }
);


//Použití s ajax
customAlert.showConfirm(
    'Opravdu chcete odeslat požadavek?',
    function() { // Akce při potvrzení
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
                customAlert.showAlert(response.data.response_msg);

                if (doAction == 'variant_stock_from_dotypos') {
                    $($variationForm.find('input[name="variable_stock[' + index + ']"]')).text(response.data.new_stock.stock_status);
                    $($variationForm.find('input[name="variable_stock[' + index + ']"]')).attr('value', response.data.new_stock.stock_status);
                }
            }
        });
    },
    function() { // Akce při odmítnutí
        console.log('Uživatel odmítl změnu skladu.');
    }
);

