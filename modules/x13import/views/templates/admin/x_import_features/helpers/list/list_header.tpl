{extends file="helpers/list/list_header.tpl"}

{$has_bulk_actions = true}
{$bulk_actions = true}

{block name="startForm"}
    {$smarty.block.parent}

    <input type="hidden" name="wholesalerTab" value="{$wholesalerTab}">
{/block}

{block name="preTable"}
    <ul class="nav nav-tabs nav-tab-wholesaler">
        {foreach from=$wholesalers item=wholesale name=wholesale}
            <li{if $wholesalerTab == $wholesale.name} class="active"{/if}><a href="{$link->getAdminLink('AdminXImportFeatures')}&wholesalerTab={$wholesale.name}">{$wholesale.name}</a></li>
        {/foreach}
    </ul>

    {if !$wholesalerHasFeatures}
        <div class="alert alert-info">
            <p>{l s='Hurtownia %s nie posiada cech.' mod='x13import' sprintf=[$wholesalerTab]}</p>
        </div>
    {else}
        <div class="form-group">
            <label for="wholesaler_feature_import_option" class="control-label col-lg-4">
                {l s='Domyślne ustawienia cech' mod='x13import'}
            </label>
            <div class="col-lg-8">
                <select id="wholesaler_feature_import_option" class="form-control fixed-width-xxl">
                    {foreach $wholesalerFeatureImportOption as $option}
                        <option value="{$option.value}" {if $wholesalerImportFeatures == $option.value}selected="selected"{/if}>{$option.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-offset-4 col-lg-8">
                <button id="wholesaler_feature_download" class="btn btn-default">
                    {if $list_total}
                        {l s='Aktualizuj informacje o cechach' mod='x13import'}
                    {else}
                        {l s='Pobierz informacje o cechach' mod='x13import'}
                    {/if}
                </button>
            </div>
        </div>

        <div class="clearfix">
            {include file="../../../progress_block.tpl"}
        </div>

        <div class="form-group ximport-bulk-actions">
            <div class="col-lg-6 text-right ximport-bulk-actions-buttons">
                <button title="{l s='Cofnij wszystkie zmiany' mod='x13import'}" id="bulk_feature_restore_changes" class="btn btn-default" style="display: none;">
                    <i class="icon-undo"></i>&nbsp;&nbsp;{l s='Cofnij wszystkie zmiany' mod='x13import'}
                </button>

                <button title="{l s='Zapisz wszystkie zmiany' mod='x13import'}" id="bulk_feature_save_changes" class="btn btn-primary" style="display: none;">
                    <i class="icon-save"></i>&nbsp;&nbsp;{l s='Zapisz wszystkie zmiany' mod='x13import'}
                </button>
            </div>
        </div>
    {/if}

    <div id="list-ximport_feature" {if !$wholesalerHasFeatures}style="display: none;"{/if}>
{/block}
