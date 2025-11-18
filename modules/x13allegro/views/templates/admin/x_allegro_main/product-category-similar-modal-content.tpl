<div class="category-similar-list">
    {foreach $categoryList as $categoryId => $categoryPath}
        <label>
            <input type="radio" name="item[{$index}][category_similar]" value="{$categoryId}" {if $categoryId == $categoryCurrent}checked="checked"{/if}>
            {$categoryPath} ({$categoryId})
        </label>
    {/foreach}
</div>
