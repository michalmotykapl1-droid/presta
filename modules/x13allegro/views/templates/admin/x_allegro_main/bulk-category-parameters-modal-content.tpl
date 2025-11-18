<ul class="nav nav-tabs" id="itemTabBulkParameters">
    {foreach from=$categoryList key=categoryId item=item name=categoryList}
        <li class="label-tooltip {if $smarty.foreach.categoryList.first}active{/if}" data-toggle="tooltip" data-original-title="{'/'|implode:$categoryPaths[$categoryId]}">
            <a href="#itemTabBulkParameters_{$categoryId}" aria-controls="itemTabBulkParameters_{$categoryId}" role="tab" data-toggle="tab" data-item-tab="{$categoryId}">
                {foreach from=$categoryPaths[$categoryId] item=categoryName name=categoryIterator}
                    {if $smarty.foreach.categoryIterator.last}{$categoryName} <small>({$categoryId})</small>{/if}
                {/foreach}
            </a>
        </li>
    {/foreach}
</ul>

<div class="tab-content panel clearfix" id="itemTabBulkParametersContent" role="tabpanel">
    {foreach from=$categoryList key=categoryId item=item name=categoryList}
        <div class="tab-pane {if $smarty.foreach.categoryList.first}active{/if}" id="itemTabBulkParameters_{$categoryId}" data-category="{$categoryId}">
            <div class="xproductization-parameters-wrapper">
                <div class="form-horizontal">
                    {$categoryParameters[$categoryId]}
                </div>
            </div>

            <hr style="margin:20px 0 5px 0;">

            <div class="xproductization-bulk-parameters-override checkbox">
                <label>
                    <input type="checkbox" class="bulk-parameters-override" name="bulk_parameters_override[{$categoryId}]" value="1">
                    {l s='nadpisz uzupełnione już parametry' mod='x13allegro'}
                </label>
            </div>

            {include file='./bulk-product-selection.tpl' productSelection=$item radioNameSuffix=$categoryId}
        </div>
    {/foreach}
</div>
