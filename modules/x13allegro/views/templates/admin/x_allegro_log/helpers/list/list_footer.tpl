{extends file="helpers/list/list_footer.tpl"}

{block name="after"}
    <script>
        $(function() {
            var XAllegro = new X13Allegro();
            XAllegro.logList();
        });
    </script>

    <div class="modal" id="log_details_modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document" style="width: 650px;">
            <div class="modal-content">
                <div class="modal-header x13allegro-modal-header">
                    <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="x13allegro-modal-title">{l s='Szczegóły loga' mod='x13allegro'}</h4>
                    <h6 class="x13allegro-modal-title-small"><span></span></h6>
                </div>
                <div class="modal-body x13allegro-modal-body"></div>
                <div class="modal-footer x13allegro-modal-footer"></div>
            </div>
        </div>
    </div>
{/block}
