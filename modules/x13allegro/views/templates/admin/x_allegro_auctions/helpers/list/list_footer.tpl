{extends file="helpers/list/list_footer.tpl"}

{block name="after"}
    <div class="modal" id="xallegro_auction_form_modal" data-backdrop="static" data-keyboard="false"></div>

    <div class="modal" id="xallegro_auction_list_settings" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header x13allegro-modal-header">
                    <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="x13allegro-modal-title">{l s='Ustawienia listy' mod='x13allegro'}</h4>
                </div>
                <div class="modal-body x13allegro-modal-body">
                    <div class="alert alert-info">
                        {l s='Nowo dodane kolumny w aktualizacji modułu będą zawsze domyślnie widoczne. Zapisz wybrane ustawienie aby to zmienić.' mod='x13allegro'}
                    </div>

                    <table class="auction-fields-list-table table">
                        <colgroup>
                            <col width="30px">
                            <col width="30px">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th></th>
                                <th class="text-center"><i class="icon-sort"></i></th>
                                <th>{l s='Kolumna' mod='x13allegro'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if !isset($auctionFieldsListSettings.default)}
                                {$fieldsListProfile = $auctionFieldsList}
                            {else}
                                {$fieldsListProfile = $auctionFieldsListSettings.default}
                            {/if}

                            {foreach $fieldsListProfile as $fieldId => $enabled}
                                <tr>
                                    <td class="text-center"><input type="checkbox" value="{$fieldId}" class="auction-fields-list-check" {if $enabled}checked="checked"{/if} {if isset($auctionFieldsList[$fieldId].settings.readonly)}disabled="disabled"{/if}></td>
                                    <td class="auction-fields-list-sort text-center"><i class="icon-bars"></i></td>
                                    <td>
                                        {if isset($auctionFieldsList[$fieldId].settings.title)}{$auctionFieldsList[$fieldId].settings.title}{else}{$auctionFieldsList[$fieldId].title}{/if}
                                        {if isset($auctionFieldsList[$fieldId].settings.desc)}<small><i>({$auctionFieldsList[$fieldId].settings.desc})</i></small>{/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer x13allegro-modal-footer">
                    <button type="button" name="saveAuctionListSettings" class="btn btn-primary">{l s='Zapisz' mod='x13allegro'}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal_alert xallegro_modal_alert" id="offer_unbind_modal_alert">
        <div class="modal_alert-content">
            <div class="modal_alert-message">
                <h2>{l s='Czy na pewno chcesz usunąć wybrane powiązania ofert?' mod='x13allegro'}</h2>
            </div>
            <div class="modal_alert-buttons">
                <button type="button" class="btn btn-default modal_alert-cancel">{l s='Nie' mod='x13allegro'}</button>
                <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Tak' mod='x13allegro'}</button>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            var XAllegro = new X13Allegro();
            XAllegro.auctionList();
        });
    </script>
{/block}
