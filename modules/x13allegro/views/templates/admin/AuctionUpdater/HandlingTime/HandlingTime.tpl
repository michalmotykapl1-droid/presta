<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Czas wysyłki' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_handling_time">
            {foreach $data.handlingTimeOptions as $value => $name}
                <option value="{$value}" {if $value == $data.defaultHandlingTimeOption}selected="selected"{/if}>{$name}</option>
            {/foreach}
        </select>
    </div>
</div>
