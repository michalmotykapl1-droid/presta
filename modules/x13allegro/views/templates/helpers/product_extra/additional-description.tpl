<div class="form-group {if isset($additionDescriptionHidden) && $additionDescriptionHidden}hidden{/if}" {if isset($additionDescriptionHidden) && $additionDescriptionHidden}style="display: none;"{/if}>
    <div class="xallegro-description-additional-wrapper">
        <label for="xallegro_description_additional_{$additionDescriptionKey}">
            <a class="btn xallegro-description-additional-move">{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}<i class="material-icons">drag_indicator</i>{else}<i class="icon-bars"></i>{/if}</a>
            <span class="xallegro-description-additional-tag">{literal}{{/literal}product_description_additional_{$additionDescriptionKey + 1}{literal}}{/literal}</span>
            <a class="btn xallegro-description-additional-delete">{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}<i class="material-icons">delete</i>{else}<i class="icon-trash"></i>{/if}</a>
        </label>

        <div class="xallegro-description-additional-inner">
            <textarea
                id="xallegro_description_additional_{$additionDescriptionKey}"
                name="xallegro_description_additional[]"
                class="textarea-autosize xallegro_description_additional_{$additionDescriptionKey}" style="width: 100%;"
            >{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}{$additionDescriptionValue nofilter}{else}{$additionDescriptionValue}{/if}</textarea>
        </div>
    </div>
</div>
