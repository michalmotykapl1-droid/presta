{extends file="helpers/list/list_header.tpl"}

{block name="preTable"}
    <div class="row">
        <div class="col-sm-12 text-right">
            {if $unreadLogsCount}
                <div class="form-group">
                    <p>{l s='Nieprzeczytanych logów o błędach' mod='x13allegro'}: {$unreadLogsCount}</p>
                    <a class="btn btn-default" href="#" id="markAllLogsAsRead">
                        <i class="icon-check-sign"></i> {l s='Oznacz wszystkie jako przeczytane' mod='x13allegro'}
                    </a>
                </div>
            {/if}
        </div>
    </div>
{/block}
