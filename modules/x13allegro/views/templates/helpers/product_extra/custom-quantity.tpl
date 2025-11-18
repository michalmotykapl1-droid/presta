<div class="form-group clearfix">
    <label for="xallegro_sync_quantity_allegro" class="control-label form-control-label col-lg-3">
        <span>{l s='Aktualizuj stany magazynowe w Allegro' mod='x13allegro'}</span>
    </label>
    <div class="col-lg-3">
        <select class="custom-select" name="xallegro_sync_quantity_allegro" id="xallegro_sync_quantity_allegro">
            <option value="" {if $syncQuantityAllegro === null}selected="selected"{/if}>{l s='użyj ustawienia z wszystkich kont (jeśli istnieje) lub globalnej opcji konta' mod='x13allegro'}</option>
            <option value="1" {if $syncQuantityAllegro !== null && $syncQuantityAllegro == '1'}selected="selected"{/if}>{l s='Tak' mod='x13allegro'}</option>
            <option value="0" {if $syncQuantityAllegro !== null && $syncQuantityAllegro == '0'}selected="selected"{/if}>{l s='Nie' mod='x13allegro'}</option>
        </select>

        <div class="help-block form-text">
            {l s='Domyśle ustawienie dla wybranego konta' mod='x13allegro'}: {if $syncQuantityAllegroDefault}{l s='Tak' mod='x13allegro'}{else}{l s='Nie' mod='x13allegro'}{/if}
        </div>
    </div>
</div>

<div class="form-group clearfix">
    <label for="xallegro_auto_renew" class="control-label form-control-label col-lg-3">
        <span>{l s='Włącz auto wznawianie ofert' mod='x13allegro'}</span>
    </label>
    <div class="col-lg-3">
        <select class="custom-select" name="xallegro_auto_renew" id="xallegro_auto_renew">
            <option value="" {if $autoRenew === null}selected="selected"{/if}>{l s='użyj ustawienia z wszystkich kont (jeśli istnieje) lub globalnej opcji konta' mod='x13allegro'}</option>
            <option value="1" {if $autoRenew !== null && $autoRenew == '1'}selected="selected"{/if}>{l s='Tak' mod='x13allegro'}</option>
            <option value="0" {if $autoRenew !== null && $autoRenew == '0'}selected="selected"{/if}>{l s='Nie' mod='x13allegro'}</option>
        </select>

        <div class="help-block form-text">
            {l s='Domyśle ustawienie dla wybranego konta' mod='x13allegro'}: {if $autoRenewDefault}{l s='Tak' mod='x13allegro'}{else}{l s='Nie' mod='x13allegro'}{/if}
        </div>
    </div>
</div>
