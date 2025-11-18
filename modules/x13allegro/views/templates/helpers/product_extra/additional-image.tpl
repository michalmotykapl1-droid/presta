<div class="form-group clearfix">
    <div class="xallegro-image-additional-wrapper clearfix">
        <img src="{$additionalImage}" alt="" class="imgm img-thumbnail">
        <span class="img-description">
            <strong>{l s='Dodatkowe zdjęcie' mod='x13allegro'} {$additionalImageKey}</strong><br>
            {l s='wymiary' mod='x13allegro'}: {$additionalImageWidth}x{$additionalImageHeight}<br>
            {l s='rozmiar' mod='x13allegro'}: {$additionalImageSize} MB
        </span>
        <a class="btn xallegro-image-additional-delete" data-name="{$additionalImageName}">
            {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}<i class="material-icons">delete</i>{else}<i class="icon-trash"></i>{/if}
        </a>
        <a class="btn xallegro-image-additional-update" data-name="{$additionalImageName}">
            {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}<i class="material-icons">edit</i>{else}<i class="icon-edit"></i>{/if}
        </a>
    </div>
</div>
