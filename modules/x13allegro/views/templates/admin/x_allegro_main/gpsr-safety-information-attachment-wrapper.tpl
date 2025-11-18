{if !isset($bulk)}
    {$bulk = false}
{/if}

<div class="gpsr-safety-information-attachment-wrapper" data-max="{$safetyInformationAttachmentMax}" style="display: none;">
    <table class="table gpsr-safety-information-attachment-table">
        <colgroup>
            <col width="30px">
            <col>
            <col width="100px">
        </colgroup>
        <thead>
            <tr>
                <th></th>
                <th>Załącznik</th>
                <th>Typ</th>
            </tr>
        </thead>
        <tbody>
            {if !empty($productAttachments)}
                {foreach $productAttachments as $attachment}
                    <tr data-type="attachment_product">
                        <td><input type="checkbox" name="item[{$index}][safety_information_attachment_product][]" x-name="safety_information_attachment_product" value="{$attachment.id_attachment}"></td>
                        <td>{$attachment.name}</td>
                        <td>{$attachment.type|upper}</td>
                    </tr>
                {/foreach}
            {/if}
        </tbody>
    </table>

    <input type="file" accept="{', '|implode:$safetyInformationAttachmentMimeTypes}" x-name="{if $bulk}bulk_{/if}safety_information_attachment_file" style="display: none;">
    <a class="btn btn-default button bt-icon" x-name="{if $bulk}bulk_{/if}safety_information_attachment_add" style="margin-top: 10px !important;">
        <i class="icon-plus-sign"></i> Dodaj załącznik
    </a>
    <p class="help-block">
        Możesz przesłać maksymalnie {$safetyInformationAttachmentMax} załączników z informacją o bezpieczeństwie do jednej oferty<br>
        Dozwolone typy załączników: {', '|implode:$safetyInformationAttachmentExtensions}<br>
        Maksymalny rozmiar jednego załącznika: {$safetyInformationAttachmentMaxFilesize}MB
    </p>
</div>
