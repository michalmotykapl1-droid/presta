<div id="xallegro_binding_stats_container" class="panel">
    <h3><i class="icon-bar-chart"></i> {l s='Statystyki powiązań dla filtrów' mod='x13allegro'}</h3>
    <div id="xallegro_binding_stats_loading">
        <i class="icon-refresh icon-spin"></i> {$loading_message}
    </div>
    <div id="xallegro_binding_stats_content" style="display:none;"></div>
    <div id="xallegro_binding_stats_error" class="alert alert-danger" style="display:none;"></div>
</div>

<script type="text/javascript">
    $(window).on('load', function() {
        var statsUrl = '{$stats_ajax_url|escape:'html':'UTF-8'}';
        var statsParams = {};
        
        // Bezpieczne parsowanie parametrów przekazanych z PHP jako string JSON
        try {
            // Używamy literału do pobrania JSONa wygenerowanego w PHP. Musimy użyć escape dla 'quotes'.
            var paramsJson = '{$stats_ajax_params_json|escape:'quotes':'UTF-8'}';
            // Zamieniamy encje HTML (np. &quot;) na cudzysłowy, jeśli escape:'quotes' je wstawiło
            paramsJson = paramsJson.replace(/&quot;/g, '"');
            statsParams = $.parseJSON(paramsJson);
        } catch (e) {
            console.error("Error parsing stats params", e);
            $('#xallegro_binding_stats_loading').hide();
            $('#xallegro_binding_stats_error').text('Błąd inicjalizacji skryptu (JSON Parse Error).').show();
            return;
        }
        
        // Dodanie wymaganych parametrów PrestaShop AJAX
        statsParams.ajax = 1;
        // Dodanie tokena, jeśli jest dostępny w globalnym JS (standard w PrestaShop Admin)
        if (typeof token !== 'undefined') {
            statsParams.token = token;
        }

        $.ajax({
            url: statsUrl,
            type: 'POST',
            data: statsParams,
            dataType: 'json',
            success: function(response) {
                $('#xallegro_binding_stats_loading').hide();
          
               if (response.success) {
                    $('#xallegro_binding_stats_content').html(response.html).show();
                } else {
                    $('#xallegro_binding_stats_error').html(response.message).show();
                }
            },
      
               error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error in binding stats:", textStatus, errorThrown);
                $('#xallegro_binding_stats_loading').hide();
                $('#xallegro_binding_stats_error').text('Błąd ładowania statystyk (AJAX Error): ' + textStatus).show();
            }
        });
    });
</script>