<div class="panel">
    <div class="panel-heading"><i class="icon-cog"></i> {l s='Lista zamówień zaimportowanych z erli.pl' mod='pherli'}</div>
    <div class="form-horizontal">
        {if empty($orders)}
            <p class="alert alert-warning">{l s='Nie ma jeszcze dodanych zamówień z erli.pl' mod='pherli'}</p>
        {else}
            <button type="button" class="btn btn-success btnRefresh"><i class="material-icons" style="font-size: 12px;">replay</i> Odśwież</button>
            <form method="post">
                <table class="table tablePackageList">
                    <thead>
                    <tr>
{*                        <th><input type="checkbox" name="all" class="selectAllCheckbox" /></th>*}
                        <th>{l s='ID' mod='pherli'}</th>
                        <th>{l s='ID zamówienia' mod='pherli'}</th>
                        <th>{l s='Indeks erli.pl' mod='pherli'}</th>
                        <th>{l s='Klient' mod='pherli'}</th>
                        <th>{l s='Razem' mod='pherli'}</th>
                        <th>{l s='Dostawa' mod='pherli'}</th>
                        <th>{l s='Status' mod='pherli'}</th>
                        <th>{l s='Data dodania' mod='pherli'}</th>
                        <th>{l s='Akcje' mod='pherli'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $orders as $order}
                        <tr>
                            <td>{$order.id_order}</td>
                            <td>{if $order.in_shop == 0}--{else}<a href="{$order.link}">{$order.id_order_shop}</a>{/if}</td>
                            <td>{$order.id_payload}</td>
                            <td>{$order.customer.firstname} {$order.customer.lastname}</td>
                            <td>{$order.total_payment}</td>
                            <td>{$order.delivery.name}</td>
                            <td>{$order.status}</td>
                            <td>{$order.date_add}</td>
                            <td>
                                <a href="{$orderShop}&details={$order.id_order}" class="btn btn-xs btn-primary">szczegóły zamówienia</a>
                                {if $order.in_shop == 0} &nbsp;|&nbsp;<a href="{$orderShop}&addOrderShop={$order.id_order}" class="btn btn-xs btn-success">DODAJ ZAMÓWIENIE DO SKLEPU</a>{else}{/if}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                {*<div class="paginPackageListBox" style="display: block;min-height: 60px;">
                    <div class="col-lg-12">
                        <div class="pagination">
                            {l s='Display'}
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                {$selected_pagination}
                                <i class="icon-caret-down"></i>
                            </button>
                            <ul class="dropdown-menu">
                                {foreach $pagination AS $value}
                                    <li>
                                        <a href="javascript:void(0);" class="pagination-items-page" data-items="{$value|intval}" data-list-id="1">{$value}</a>
                                    </li>
                                {/foreach}
                            </ul>
                            / {$list_total} {l s='result(s)'}

                        </div>
                        <script type="text/javascript">
                            $('.pagination-items-page').on('click',function(e){
                                e.preventDefault();
                                $('#'+$(this).data("list-id")+'-pagination-items-page').val($(this).data("items")).closest("form").submit();
                            });
                        </script>
                        <ul class="pagination pull-right">
                            <li {if $plPage <= 1}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="1" data-list-id="1">
                                    <i class="icon-double-angle-left"></i>
                                </a>
                            </li>
                            <li {if $plPage <= 1}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="{$plPage - 1}" data-list-id="1">
                                    <i class="icon-angle-left"></i>
                                </a>
                            </li>
                            {assign var=p value=0}
                            {while $p++ < $pages_all}
                                {if $p < $plPage-2}
                                    <li class="disabled">
                                        <a href="javascript:void(0);">&hellip;</a>
                                    </li>
                                    {assign var=p value=$plPage-3}
                                {elseif $p > $plPage+2}
                                    <li class="disabled">
                                        <a href="javascript:void(0);">&hellip;</a>
                                    </li>
                                    {assign p $pages_all}
                                {else}
                                    <li {if $p == $plPage}class="active"{/if}>
                                        <a href="javascript:void(0);" class="pagination-link" data-page="{$p}" data-list-id="1">{$p}</a>
                                    </li>
                                {/if}
                            {/while}
                            <li {if $plPage >= $pages_all}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="{$plPage + 1}" data-list-id="1">
                                    <i class="icon-angle-right"></i>
                                </a>
                            </li>
                            <li {if $plPage >= $pages_all}class="disabled"{/if}>
                                <a href="javascript:void(0);" class="pagination-link" data-page="{$pages_all}" data-list-id="1">
                                    <i class="icon-double-angle-right"></i>
                                </a>
                            </li>
                        </ul>
                        <script type="text/javascript">
                            $('.pagination-link').on('click',function(e){
                                e.preventDefault();

                                if (!$(this).parent().hasClass('disabled'))
                                    $('#submitFilterr'+$(this).data("list-id")).val($(this).data("page")).closest("form").submit();
                            });
                            $(document).ready(function() {
                                $('.selectAllCheckbox').on('click', function () {
                                    var sel = false;
                                    if ($(this).is(':checked')) {
                                        sel = true;
                                    }
                                    $('.productChkBox').each(function(){
                                        if (sel === true) {
                                            $(this).attr('checked', 'checked');
                                        } else {
                                            $(this).removeAttr('checked');
                                        }
                                    });
                                });
                            });
                        </script>
                    </div>
                </div>
                <div class="">
                    <div class="d-inline-block" bulkurl="{$bulkUrl}active_all"  redirecturl="{$bulkUrl}" redirecturlnextpage="{$bulkUrl}">
                        <div class="btn-group dropdown bulk-catalog">
                            <button type="button" id="product_bulk_menu" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                Działania masowe
                                <i class="icon-caret-up"></i>
                            </button>
                            <div class="dropdown-menu">
                                <button type="submit" class="dropdown-item" name="add_to_erli">
                                    <i class="material-icons">radio_button_checked</i>
                                    Dodaj do erli.pl
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" name="remove_from_erli">
                                    <i class="material-icons">delete</i>
                                    Usuń z erli.pl
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <form method="post">
                <input type="hidden" name="plPage" value="1" id="submitFilterr1" />
                <input type="hidden" name="plUrl" value="{$currentPage}" id="submitFilter1" />
            </form>
            <form method="post">
                <input type="hidden" id="1-pagination-items-page" name="ERLI_PL_PERPAGE" value="{$selected_pagination|intval}" />
            </form>*}
        {/if}
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('.btnRefresh').on('click', function () {
            location.reload();
        })
    });
</script>
