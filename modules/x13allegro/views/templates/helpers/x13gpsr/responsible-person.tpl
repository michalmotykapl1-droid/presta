{extends file='form-modifier.tpl'}

{block x13gpsrFormModifier}
    {if !empty($allegroAccount.responsiblePersons)}
        <div class="form-group">
            <label for="x13allegro_{$allegroAccount.accountId}_responsible_person" class="control-label col-lg-4">{l s='Osoba odpowiedzialna w Allegro' mod='x13allegro'}</label>
            <div class="col-lg-8">
                <select id="x13allegro_{$allegroAccount.accountId}_responsible_person" name="x13allegro_responsible_person[{$allegroAccount.accountId}]">
                    <option value="">{l s='-- Wybierz --' mod='x13allegro'}</option>
                    {foreach $allegroAccount.responsiblePersons as $responsiblePerson}
                        <option value="{$responsiblePerson->id}" {if $responsiblePerson->id == $allegroAccount.responsiblePersonAssigned}selected="selected"{/if}>{$responsiblePerson->name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {else}
        <div class="alert alert-info">
            {l s='Brak skonfigurowanych danych osób odpowiedzialnych w Allegro' mod='x13allegro'}
        </div>
    {/if}

    <div class="form-group">
        <label class="control-label col-lg-4">{l s='Utwórz i przypisz nową osobę odpowiedzialną w Allegro' mod='x13allegro'}</label>
        <div class="col-lg-8">
            <a href="#" class="x13gpsr-allegro-create btn btn-default {if !$formObjectId}disabled{/if}"><i class="icon-plus-circle"></i> {l s='Utwórz i przypisz' mod='x13allegro'}</a>

            {if !$formObjectId}
                <p class="help-block">{l s='Musisz najpierw zapisać nową osobę odpowiedzialną, aby uwotrzyć ją w Allegro' mod='x13allegro'}</p>
            {/if}
        </div>
    </div>
{/block}
