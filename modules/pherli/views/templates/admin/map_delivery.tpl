<form method="post">
    <div class="panel">
        <div class="panel-heading"><i class="icon-cog"></i> {l s='Mapowanie metod dostawy z erli.pl' mod='pherli'}</div>
        <div class="form-horizontal">
            <div class="rows">
{*                {if empty($vendors)}*}
{*                    <p class="alert alert-warning">Wystąpił problem z pobraniem firm przewozowych. Odśwież stronę.</p>*}
{*                {/if}*}

                {foreach $delivery as $del}
                    <div class="form-group">
                        <label class="control-label col-lg-3 text-right">{$del.name}:</label>
                        <div class="col-lg-2">
                            <select name="delivery[{$del.id_delivery}]">
                                <option value=""> {l s='- wybierz czas -' mod='pherli'} </option>
                                {foreach $carriers as $carrier}
                                    <option value="{$carrier.id_reference}"{if $carrier.id_reference == $del.id_carrier} selected="selected"{/if}> {$carrier.name} </option>
                                {/foreach}
                            </select>
                        </div>
{*                        <div class="col-lg-2">*}
{*                            <select name="vendor[{$del.id_delivery}]">*}
{*                                <option value=""> {l s='- wybierz firmę przewozową -' mod='pherli'} </option>*}
{*                                {foreach $vendors as $vendor}*}
{*                                    <option value="{$vendor}"{if $vendor == $del.vendor} selected="selected"{/if}> {$vendor} </option>*}
{*                                {/foreach}*}
{*                            </select>*}
{*                        </div>*}
                    </div>
                {/foreach}

            </div>{* end row *}
        </div>
        <div class="panel-footer">
            <a href="{$backUrl}" class="btn btn-default pull-left"><i class="process-icon-back"></i> powrót</a>
            <button id="configuration_form_submit_btn" class="btn btn-default pull-right" type="submit" value="1" name="submitMapDeliveryShop"><i class="process-icon-save"></i> {l s='Save' mod='pherli'}</button>
        </div>
    </div>
</form>
