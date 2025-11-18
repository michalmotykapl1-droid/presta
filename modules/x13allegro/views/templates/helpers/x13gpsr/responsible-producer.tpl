{extends file='form-modifier.tpl'}

{block x13gpsrFormModifier}
    {if !empty($allegroAccount.responsibleProducers)}
        <div class="form-group">
            <label for="x13allegro_{$allegroAccount.accountId}_responsible_producer" class="control-label col-lg-4">{l s='Producent w Allegro' mod='x13allegro'}</label>
            <div class="col-lg-8">
                <select id="x13allegro_{$allegroAccount.accountId}_responsible_producer" name="x13allegro_responsible_producer[{$allegroAccount.accountId}]">
                    <option value="">{l s='-- Wybierz --' mod='x13allegro'}</option>
                    {foreach $allegroAccount.responsibleProducers as $responsibleProducer}
                        <option value="{$responsibleProducer->id}" {if $responsibleProducer->id == $allegroAccount.responsibleProducerAssigned}selected="selected"{/if}>{$responsibleProducer->name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {else}
        <div class="alert alert-info">
            {l s='Brak skonfigurowanych danych teleadresowych producentów w Allegro' mod='x13allegro'}
        </div>
    {/if}

    <div class="form-group">
        <label class="control-label col-lg-4">{l s='Utwórz i przypisz nowego producenta w Allegro' mod='x13allegro'}</label>
        <div class="col-lg-8">
            <a href="#" class="x13gpsr-allegro-create btn btn-default {if !$formObjectId}disabled{/if}"><i class="icon-plus-circle"></i> {l s='Utwórz i przypisz' mod='x13allegro'}</a>

            {if !$formObjectId}
                <p class="help-block">{l s='Musisz najpierw zapisać nowego producenta, aby utworzyć go w Allegro' mod='x13allegro'}</p>
            {/if}
        </div>
    </div>
{/block}
