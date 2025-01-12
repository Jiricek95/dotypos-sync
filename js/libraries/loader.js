(function($) {
    // Funkce pro dynamické přidání loaderu na stránku
    $.fn.loader = function(options) {
        // Výchozí nastavení
        var settings = $.extend({
            action: 'show', // Akce: 'show', 'hide', nebo 'remove'
            text: 'Načítám...', // Text loaderu
            target: 'body', // Cílový prvek (defaultně body)
            method: 'append', // Metoda: 'insert' nebo 'append'
            type: 'full' // Typ loaderu: 'full' (celostránkový) nebo 'inline' (v rámci prvku)
        }, options);

        // Dynamicky vložení HTML loaderu, pokud ještě není v cílovém prvku
        if ($(settings.target).find('.loader-container').length === 0) {
            var loaderHTML = `
                <div class="loader-container loader_${settings.type}">
                    <div class="loader"></div>
                    <div class="loader_text">${settings.text}</div>
                </div>
            `;

            // Vložení do cíle na základě zvolené metody
            if (settings.method === 'insert') {
                $(settings.target).prepend(loaderHTML);
            } else {
                $(settings.target).append(loaderHTML);
            }
        } else {
            // Pokud loader už existuje, aktualizuje text
            $(settings.target).find('.loader_text').text(settings.text);
        }

        // Dynamicky vložení CSS pro loader
        if ($('#loader-styles').length === 0) {
            var styles = `
                <style id="loader-styles">
                    .loader-container {
                        display: none; /* Skryté defaultně */
                        z-index: 1000;
                    }
                    .loader_full {
                        position: fixed;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.4);
                    }
                    .loader_inline {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(255, 255, 255, 0.8);
                    }
                    .loader {
                        border: 16px solid #f3f3f3; /* Světlý okraj */
                        border-top: 16px solid #3498db; /* Modrý okraj */
                        border-radius: 50%; /* Kruhový tvar */
                        width: 120px; /* Šířka */
                        height: 120px; /* Výška */
                        animation: spin 2s linear infinite; /* Animace */
                        margin: 50px auto;
                        position: relative;
                    }
                    .loader_text {
                        margin-top: 20px;
                        position: relative;
                        text-align: center;
                        color: white;
                        font-size: 24px;
                    }
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            `;
            $('head').append(styles);
        }

        // Zobrazení nebo skrytí loaderu na základě akce
        if (settings.action === 'show') {
            $(settings.target).find('.loader_text').text(settings.text);
            $(settings.target).find('.loader-container').fadeIn();
        } else if (settings.action === 'hide') {
            $(settings.target).find('.loader-container').fadeOut();
        } else if (settings.action === 'remove') {
            $(settings.target).find('.loader-container').remove();
        }
    };
}(jQuery));

/*
Celostránkový
$.fn.loader({
    action: 'show',
    text: 'Načítám data...',
    type: 'full'
});


Inline
$.fn.loader({
    action: 'show',
    text: 'Zpracovávám obsah...',
    target: '#content',
    type: 'inline'
});


Skrytí
$.fn.loader({
    action: 'hide',
    target: '#content' // nebo body pro full-width
});


Odstranění
$.fn.loader({
    action: 'remove',
    target: '#content' // nebo body pro full-width
});


*/