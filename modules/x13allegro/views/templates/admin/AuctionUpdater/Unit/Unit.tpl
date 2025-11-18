<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Jednostka' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_unit">
            {foreach $data.unitOptions as $value => $name}
                <option value="{$value}">{$name}</option>
            {/foreach}
        </select>
    </div>
</div>
