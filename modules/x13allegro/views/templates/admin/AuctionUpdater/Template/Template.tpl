<div class="form-group row">
    <div class="col-lg-12">
        <label class="control-label col-lg-3">
            {l s='Szablon oferty' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select name="allegro_auction_template">
                {foreach $data.templateList as $template}
                    <option value="{$template.id}" {if $template.default}selected="selected"{/if}>{$template.name}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>
<div class="form-group row">
    <div class="col-lg-12">
        <label class="control-label col-lg-3">
            {l s='Zdjęcia do szablonu' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select name="allegro_auction_template_images">
                <option value="all" {if $data.selectImages == 'all'}selected="selected"{/if}>{l s='wszystkie' mod='x13allegro'}</option>
                <option value="first" {if $data.selectImages == 'first'}selected="selected"{/if}>{l s='tylko pierwsze' mod='x13allegro'}</option>
            </select>
        </div>
    </div>
</div>
