<div id="guide">
    <div class="step active" id="step-1">
        <button style="display: none;" onclick="merge_database()">Migrace databáze</button>
        <h2>K čemu je doplněk</h2>
        <p>Doplněk umožňuje synchronizaci produktů mezi Dotykačkou a WooCommerce</p>
        <button class="next button button-primary">Další</button>
    </div>
    <div class="step" id="step-2">
        <h2>Propojení s Dotykačkou</h2>
        <p>Stisknutím tlačítka propojíte doplněk se Vzdálenou správou Dotykačky</p>
        <button class="button button-primary" onclick="setDotypos()">Propojit</button>
        <button class="next button button-primary" style="display: none;">Další</button>
    </div>
    <div class="step" id="step-3">
        <h2>Krok 3</h2>
        <p>Nyní máte propojeno, stisnutím tlačítka dokončit</p>
        <button class="button button-primary" onclick="ds_refreshPage()">Dokončit</button>
    </div>
</div>


<script>

        // Když uživatel klikne na tlačítko "Další"
        $('.next').on('click', function() {
            var currentStep = $(this).closest('.step'); 
            next_step(currentStep);
    });
function next_step(currentStep){

        var nextStep = currentStep.next('.step'); // Najdi další krok

        if (nextStep.length) {
            // Zobraz další krok s animací
            currentStep.removeClass('active').addClass('exit-left'); // Posuň aktuální krok vlevo
            nextStep.addClass('active'); // Zobraz další krok
        } else {
            // Pokud není další krok, můžeš provést nějakou akci (např. ukončení průvodce)
            alert('Průvodce dokončen!');
        }

}
</script>