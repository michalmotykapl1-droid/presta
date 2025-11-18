<div class="form-group row">
    <label for="allegro_title_mode" class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select id="allegro_title_mode" name="allegro_title_mode">
            <option value="1" selected="selected">{l s='ustaw nowy tytuł' mod='x13allegro'}</option>
            <option value="2">{l s='dodaj frazę do tytułu' mod='x13allegro'}</option>
            <option value="3">{l s='zmień w tytule' mod='x13allegro'}</option>
            <option value="4">{l s='skopiuj nazwę przypisanego produktu Allegro do tytułu' mod='x13allegro'}</option>
        </select>
    </div>
</div>

<div class="clearfix">
    <div class="form-group row">
        <label for="allegro_title_pattern" class="control-label col-lg-3">
            {l s='Format nowego tytułu' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <input type="text" id="allegro_title_pattern" name="allegro_title_pattern" value="{$data.titlePatternDefault}">
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-info">
                <p>{l s='W powyższej opcji aktualizacji można korzystać z poniższych znaczników:' mod='x13allegro'}</p>
                <a href="#" class="allegro_show_title_tags">{l s='Pokaż dostępne znaczniki' mod='x13allegro'}</a>
                <div class="help-block" style="font-style: italic; display:none;">
                    <small>
                        <span class="x13allegro_black">{l s='{product_id}' mod='x13allegro'}</span> - ID produktu<br>
                        <span class="x13allegro_black">{l s='{product_name}' mod='x13allegro'}</span> - Nazwa produktu<br>
                        <span class="x13allegro_black">{l s='{product_name_attribute}' mod='x13allegro'}</span> - Nazwa atrybutu<br>
                        <span class="x13allegro_black">{l s='{product_short_desc}' mod='x13allegro'}</span> - Krótki opis<br>
                        <span class="x13allegro_black">{l s='{product_reference}' mod='x13allegro'}</span> - Kod referencyjny (indeks) produktu<br>
                        <span class="x13allegro_black">{l s='{product_ean13}' mod='x13allegro'}</span> - Kod EAN13<br>
                        <span class="x13allegro_black">{l s='{product_weight}' mod='x13allegro'}</span> - Waga produktu<br>
                        <span class="x13allegro_black">{l s='{product_price}' mod='x13allegro'}</span> - Cena produktu<br>
                        <span class="x13allegro_black">{l s='{manufacturer_name}' mod='x13allegro'}</span> - Nazwa producent<br>
                        <span class="x13allegro_black">{l s='{attribute_group_X}' mod='x13allegro'}</span> - Nazwa i wartość grupy atrybutów X (grupa atrybutów musi być przypisana do kombinacji produktu)<br>
                        <span class="x13allegro_black">{l s='{attribute_group_value_X}' mod='x13allegro'}</span> - Wartość grupy atrybutów X (grupa atrybutów musi być przypisana do kombinacji produktu)<br>
                        <span class="x13allegro_black">{l s='{feature_X}' mod='x13allegro'}</span> - Nazwa i wartość cechy X (cecha musi być przypisana do produktu)<br>
                        <span class="x13allegro_black">{l s='{feature_value_X}' mod='x13allegro'}</span> - Wartość cechy X (cecha musi być przypisana do produktu)			
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="alert alert-warning">
                {l s='Format tytułu oferty zostanie użyty do aktualizacji wszystkich wybranych ofert, oprócz tych z indywidualnym tytułem.' mod='x13allegro'}
            </div>
        </div>
    </div>
</div>

<div class="clearfix" style="display: none;">
    <div class="form-group row">
        <label for="allegro_title_add_mode" class="control-label col-lg-3">
            {l s='Dodaj frazę' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select id="allegro_title_add_mode" name="allegro_title_add_mode">
                <option value="1" selected="selected">{l s='dodaj przed tytułem' mod='x13allegro'}</option>
                <option value="2">{l s='dodaj po tytule' mod='x13allegro'}</option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label for="allegro_title_add" class="control-label col-lg-3">
            {l s='Fraza' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <input type="text" id="allegro_title_add" name="allegro_title_add" value="">
        </div>
    </div>
</div>

<div class="clearfix" style="display: none;">
    <div class="form-group row">
        <label for="allegro_title_replace_from" class="control-label col-lg-3">
            {l s='Zamień frazę z tytułu' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <input type="text" id="allegro_title_replace_from" name="allegro_title_replace_from" value="">
        </div>
    </div>
    <div class="form-group row">
        <label for="allegro_title_replace_to" class="control-label col-lg-3">
            {l s='na' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <input type="text" id="allegro_title_replace_to" name="allegro_title_replace_to" value="">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-warning">
            <p>{l s='Tytuł zostanie zaktualizowany we wszystkich zaznaczonych ofertach, w których nie było sprzedaży lub licytacji.' mod='x13allegro'}</p>
            <p>{l s='Jeśli nowo wygenerowany tytuł będzie dłuższy niż %s znaków, zostanie on automatycznie skrócony.' mod='x13allegro' sprintf=$data.titleMaxSize}</p>
        </div>
    </div>
</div>

<script>
    $(document).on('change', '[name="allegro_title_mode"]', function () {
        var value = parseInt($(this).val());

        if (value === 1) {
            $('input[name="allegro_title_pattern"]').parents('.form-group').parent().show();
            $('select[name="allegro_title_add_mode"]').parents('.form-group').parent().hide();
            $('input[name="allegro_title_replace_from"]').parents('.form-group').parent().hide();
        }
        else if (value === 2) {
            $('input[name="allegro_title_pattern"]').parents('.form-group').parent().hide();
            $('select[name="allegro_title_add_mode"]').parents('.form-group').parent().show();
            $('input[name="allegro_title_replace_from"]').parents('.form-group').parent().hide();
        }
        else if (value === 3) {
            $('input[name="allegro_title_pattern"]').parents('.form-group').parent().hide();
            $('select[name="allegro_title_add_mode"]').parents('.form-group').parent().hide();
            $('input[name="allegro_title_replace_from"]').parents('.form-group').parent().show();
        }
        else {
            $('input[name="allegro_title_pattern"]').parents('.form-group').parent().hide();
            $('select[name="allegro_title_add_mode"]').parents('.form-group').parent().hide();
            $('input[name="allegro_title_replace_from"]').parents('.form-group').parent().hide();
        }
    });

    $(document).on('click', '.allegro_show_title_tags', function(e) {
        e.preventDefault();
        $(this).hide().next().show();
    });
</script>
