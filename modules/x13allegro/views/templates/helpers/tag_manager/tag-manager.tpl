{if !empty($tag_manager.users)}
    {if $tag_manager_user_change}
        <div class="form-group clearfix">
            <label for="xallegro_tags_account" class="control-label form-control-label col-lg-3">
                <span>{l s='Konto Allegro' mod='x13allegro'}</span>
            </label>

            <div class="col-lg-3">
                <select id="xallegro_tags_account" name="xallegro_tags_account" class="custom-select">
                    {foreach $tag_manager.users as $user}
                        <option value="{$user.id}" {if !$user.enabled}disabled="disabled"{/if}>{$user.username}</option>
                    {/foreach}
                </select>
            </div>

            <div class="col-lg-9 col-lg-offset-3">
                <div class="help-block form-text">{l s='Tagi ofertowe są dostępne tylko dla kont korzystających z abonamentu Allegro.' mod='x13allegro'}</div>
            </div>
        </div>
    {else}
        <input type="hidden" id="xallegro_tags_account" name="xallegro_tags_account" value="{$user_id}">
    {/if}

    <div class="form-group clearfix">
        <label for="" class="control-label form-control-label col-lg-3">
            <span>{l s='Tagi' mod='x13allegro'}</span>
        </label>

        <div class="col-lg-{if $tag_manager_map_type == 'auction'}9{else}5{/if}">
            <div class="{if $tag_manager_editable}{/if}">
                {foreach $tag_manager.tags as $user_id => $tags}
                    {include file="./tag-manager-table.tpl"}
                {/foreach}
            </div>
        </div>

        <div class="col-lg-9 col-lg-offset-3">
            <div class="help-block form-text">
                {if $tag_manager_map_type == 'product'}
                    {l s='Aby przypisać tag do produktu zaznacz checkbox i zapisz produkt.' mod='x13allegro'}<br>
                {elseif $tag_manager_map_type == 'category'}
                    {l s='Aby przypisać tag do kategorii zaznacz checkbox i zapisz powiązanie.' mod='x13allegro'}<br>
                {elseif $tag_manager_map_type == 'manufacturer'}
                    {l s='Aby przypisać tag do producenta zaznacz checkbox i zapisz powiązanie.' mod='x13allegro'}<br>
                {/if}

                {l s='Możesz przypisać maksymalnie %d tagów do jednej oferty.' sprintf=[$tag_manager_auction_limit] mod='x13allegro'}
            </div>
        </div>
    </div>

    <div class="form-group clearfix">
        <label for="xallegro_tag_new" class="control-label form-control-label col-lg-3">
            <span>{l s='Dodaj nowy tag' mod='x13allegro'}</span>
        </label>

        <div class="col-lg-{if $tag_manager_map_type == 'auction'}9{else}5{/if} form-inline">
            <input id="xallegro_tag_new" type="text" name="xallegro_tag_new" class="fixed-width-xxl pull-left form-control" value="" size="{$tag_manager_max_chars}">
            <button class="btn btn-default btn-secondary xallegro-tag-new" style="margin-left: 10px;">{if $tag_manager_17 && $tag_manager_map_type == 'product'}<i class="material-icons">add_circle</i>{else}<i class="icon-plus-sign"></i>{/if}&nbsp;{l s='Dodaj' mod='x13allegro'}</button>
        </div>

        <div class="col-lg-9 col-lg-offset-3">
            <div class="help-block form-text">{l s='Maksymalna ilość znaków: %d' sprintf=[$tag_manager_max_chars] mod='x13allegro'}</div>
        </div>
    </div>
{else}
    {l s='Brak zautoryzowanego konta Allegro' mod='x13allegro'}
{/if}

<script type="text/javascript">
    if (typeof XAllegroTagManager === 'undefined') {
        var XAllegroTagManager = new X13Allegro();
        XAllegroTagManager.ajaxUrl = "{$tag_manager_url}";
        XAllegroTagManager.ajaxToken = "{$tag_manager_token}";
        XAllegroTagManager.presta17 = {$tag_manager_17|intval};
        XAllegroTagManager.tagManager(document.getElementById('{$tag_manager_container}'), '{$tag_manager_map_type}', {$tag_manager_auction_limit});
    }
    else {
        XAllegroTagManager.tagManagerRefresh();
    }
</script>
<style>
    .scroll-tags-table {
        position: relative;
        height: 300px;
        padding: 0;
    }
    .scroll-tags-table table tbody {
        position: absolute;
        height: 285px;
        width: 100%;
        overflow: auto;
    }
    .scroll-tags-table table tbody tr {
        display: flex;
    }
</style>
