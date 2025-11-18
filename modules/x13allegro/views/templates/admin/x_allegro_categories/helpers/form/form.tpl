{extends file="helpers/form/form.tpl"}

{block name="input_row"}
    {if isset($input.copy_parameters)}
        {if $input.name == 'copy_parameters'}
            <hr />
            <div class="form-group">
                <div class="col-lg-9 col-lg-offset-3">
                    <a href="#" id="copy_parameters_link"><b>{l s='Skopiuj parametry z innego mapowania' mod='x13allegro'} <i class="icon-caret-down"></i></b></a>
                </div>
            </div>
        {/if}

        <div class="copy-parameters-content" style="display: none;">
            {if $form_id}
                {$smarty.block.parent}

                {if $input.name == 'copy_parameters_mode'}
                    <div class="form-group">
                        <div class="col-lg-9 col-lg-offset-3">
                            <button type="submit" name="submitCopyParameters" class="btn btn-default"><i class="icon-copy"></i>&nbsp;&nbsp;{l s='Kopiuj parametry' mod='x13allegro'}</button>
                        </div>
                    </div>
                {/if}
            {elseif !$form_id && $input.name == 'copy_parameters_mode'}
                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        {l s='Musisz najpierw zapisać tę ścieżkę kategorii.' mod='x13allegro'}
                    </div>
                </div>
            {/if}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="field"}
	{if $input.name == 'id_allegro_category'}
        {$parent_id = 0}
        {foreach $input.path as $value}
            <div class="allegro-category-select allegro-category-select-inline">
                <select name="{$input.name}[]">
                    <option value="0">{l s='-- Wybierz --' mod='x13allegro'}</option>
                    {foreach $input.categories as $category}
                        {if $parent_id == $category.parent_id}
                            <option value="{$category.id}" {if $value !== null && $value.id == $category.id}selected="selected"{/if}>
                                {$category.name}
                            </option>
                        {/if}
                    {/foreach}
                </select>
            </div>

            {if $value !== null}
                {$parent_id = $value.id}
            {/if}
        {/foreach}

        <div id="xallegro_category_int_group">
            <label for="allegro_category_input" class="control-label">{l s='Numer kategorii' mod='x13allegro'}:</label>
            <input type="text" id="allegro_category_input" class="fixed-width-xxl" name="allegro_category_input" value="{$allegro_category_input}" data-cast="string" />
            <input type="hidden" id="allegro_category_current" name="allegro_category_current" value="{$allegro_category_input}" data-cast="string" />
        </div>
    {elseif $input.name == 'tag-manager'}
        {if empty($input.content)}
            <div class="alert alert-info">
                <p>{l s='Zapisz tę ścieżkę kategorii aby umożliwić mapowanie tagów.' mod='x13allegro'}</p>
            </div>
        {else}
            {$input.content}
        {/if}
    {else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name="other_input"}
    {if $key == 'category_parameters'}
        {$field}

        <div id="category_parameters_info" class="form-wrapper row" style="display: none;">
            <span class="col-lg-4"></span>
            <span class="col-lg-8">
                <span class="x13allegro-category-parameters-info-required">*</span> - pole wymagane<br/>
                <span class="x13allegro-category-parameters-info-productization"></span> - parametr z <strong>Katalogu Allegro</strong>
            </span>
        </div>
    {/if}
{/block}

{block name="after"}
    <div class="modal" id="xallegro_parameter_map_form_modal" data-backdrop="static" data-keyboard="false"></div>

    <script>
        var XAllegro = new X13Allegro();
        XAllegro.categoryForm();
    </script>
{/block}
