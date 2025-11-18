<div class="form-group row">
    <div class="col-lg-12">
        <div class="alert alert-info">
            {l s='Istnieje kilka możliwości aktualizacji parametrów oferty podczas łączenia jej z Katalogiem Allegro. Prosimy wybrać w jaki sposób moduł ma je przekazać podczas łączenia.' mod='x13allegro'}
        </div>
        <select name="allegro_productization_parameters">
            <option value="skip">{l s='Zostaw własne parametry' mod='x13allegro'}</option>
            <option value="fill">{l s='Dopełnij własne parametry, parametrami z Katalogu Allegro' mod='x13allegro'}</option>
            <option value="reset">{l s='Użyj parametrów z Katalogu Allegro' mod='x13allegro'}</option>
        </select>
    </div>
</div>

<h4>{l s='Dodatkowe opcje' mod='x13allegro'}</h4>
<div class="alert alert-warning">
    <b>Uwaga:</b> jeżeli chcesz użyć opisu albo zdjęć dostarczonych przez Allegro, wypełnione wcześniej zdjęcia i opis zostaną bezpowrotnie usunięte z oferty.
</div>

<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Zdjęcia' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_productization_images">
            <option value="0">{l s='Nie aktualizuj zdjęć wg Katalogu Allegro' mod='x13allegro'}</option>
            <option value="1">{l s='Użyj zdjęć z Katalogu Allegro (jeśli istnieją)' mod='x13allegro'}</option>
        </select>
    </div>
</div>

<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Opis' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_productization_description">
            <option value="0">{l s='Nie aktualizuj opisu wg Katalogu Allegro' mod='x13allegro'}</option>
            <option value="1">{l s='Użyj opisu z Katalogu Allegro (jeśli istnieje)' mod='x13allegro'}</option>
        </select>
    </div>
</div>

<hr>