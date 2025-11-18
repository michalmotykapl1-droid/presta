{$isModernLayout = version_compare($smarty.const._PS_VERSION_, '1.7.7.0', '>=')}
{if $isModernLayout}
<div class="card mt-2 d-print-none">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <h3 class="card-header-title">
                    {l s='Numer śledzenia Allegro' mod='x13allegro'}
                </h3>
            </div>
        </div>
    </div>
    <div class="card-body">
{/if}
<div {if !$isModernLayout}class="tab-pane"{/if} id="xallegro_shipping">
    {if !$isModernLayout}
        <h4 class="visible-print">{l s='Allegro' mod='x13allegro'}</h4>
    {/if}

    {if !$order->isVirtual()}
        <div class="form-horizontal">
            <div class="table-responsive">
                <table class="table" id="xallegro_shipping_table">
                    <thead>
                        <tr>
                            <th><span class="title_box">{l s='Przewoźnik' mod='x13allegro'}</span></th>
                            <th><span class="title_box">{l s='Operator Allegro' mod='x13allegro'}</span></th>
                            <th><span class="title_box">{l s='Numer śledzenia' mod='x13allegro'}</span></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$order_shipping item=line}
                            <tr>
                                <td>
                                    {if !$line.carrier_name}
                                        <span class="badge badge-danger">{l s='brak przewoźnika' mod='x13allegro'}</span>
                                    {else}
                                        {$line.carrier_name}
                                    {/if}
                                </td>
                                <td>
                                    {if !empty($line.package_info) && isset($carrier_list[$line.package_info.id_operator])}
                                        {$carrier_list[$line.package_info.id_operator].name}{if !empty($line.package_info.operator_name)}: {$line.package_info.operator_name}{/if}
                                    {else}
                                        <span class="badge badge-danger">{l s='uzupełnij operatora Allegro' mod='x13allegro'}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if !$line.tracking_number}
                                        <span class="badge badge-danger">{l s='uzupełnij numer śledzenia' mod='x13allegro'}</span>
                                    {elseif !$line.package_info.same_number && $line.package_info.send}
                                        {$line.package_info.send_tracking_number} <span class="badge badge-warning">{l s='uaktualnij' mod='x13allegro'}</span>
                                    {else}
                                        {$line.package_info.order_tracking_number}
                                    {/if}
                                </td>
                                <td>
                                    {if !$line.package_info.send || !$line.package_info.same_number}
                                        <a href="#" class="btn btn-default pull-right xallegro-shipping-edit" data-id-carrier="{$line.id_carrier|intval}" style="margin-right: 5px;">
                                            <i class="icon-pencil"></i>&nbsp;{if !isset($carrier_list[$line.package_info.id_operator])}{l s='Uzupełnij operatora' mod='x13allegro'}{else}{l s='Edytuj operatora' mod='x13allegro'}{/if}
                                        </a>
                                        <a href="{$link->getAdminLink('AdminXAllegroOrderShipping')}&submitFilterorder=1&orderFilter_id_order={$order->id}" class="btn {if !$line.package_info.send || ($line.package_info.send && !$line.package_info.same_number)}btn-primary{else}btn-default{/if} pull-right" {if !$line.tracking_number || !isset($carrier_list[$line.package_info.id_operator])}style="display: none;" {/if}>
                                            {if !$line.package_info.same_number && $line.package_info.send}{l s='Wyślij ponownie' mod='x13allegro'}{else}{l s='Wyślij nr śledzenia' mod='x13allegro'}{/if}
                                        </a>
                                    {else}
                                        <span class="badge badge-success">{l s='Wysłany' mod='x13allegro'}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $line.id_carrier && isset($line.package_info.id_operator) && (!$line.package_info.send || ($line.package_info.send && !$line.package_info.same_number))}
                                        <a href="{$link->getAdminLink('AdminXAllegroOrderShipping')}&submitFilterorder=1&orderFilter_id_order={$order->id}" class="btn btn-default" style="float: right;">
                                            {if $line.package_info.send_enabled}
                                                {if $isModernLayout}<i class="material-icons text-success">toggle_on</i>{else}<i class="icon-power-off text-success"></i>{/if} {l s='Włączone wysyłanie (zmień)' mod='x13allegro'}
                                            {else}
                                                {if $isModernLayout}<i class="material-icons text-danger">toggle_off</i>{else}<i class="icon-power-off text-danger"></i>{/if} {l s='Wyłączone wysyłanie (zmień)' mod='x13allegro'}
                                            {/if}
                                            {if $line.package_info.error}&nbsp;<span class="badge badge-danger">Błędów: {$line.package_info.error}</span>{/if}
                                        </a>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {/if}
</div>
{if $isModernLayout}
</div><!-- .card-body -->
</div><!-- .card -->
{/if}

{foreach from=$order_shipping item=line}
    <div class="modal" id="xallegro_order_shipping_edit_modal_{$line.id_carrier|intval}" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="" class="form-horizontal bootstrap">
                    <div class="modal-header x13allegro-modal-header">
                        <h4 class="x13allegro-modal-title">{l s='Edytuj szczegóły wysyłki' mod='x13allegro'}</h4>
                        <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                    </div>

                    <div class="modal-body x13allegro-modal-body">
                        <div class="container-fluid">
                            <input type="hidden" name="xallegro_id_order_carrier" id="xallegro_id_order_carrier" value="{$line.id_order_carrier|htmlentities}" />
                            <input type="hidden" name="xallegro_id_order" id="xallegro_id_order" value="{$order->id|intval}" />

                            <div class="form-group">
                                <label for="xallegro_shipping_carrier_{$line.id_carrier|intval}" class="{if !$isModernLayout}col-lg-4{/if} form-control-label control-label">{l s='Operator Allegro' mod='x13allegro'}</label>
                                <div class="{if !$isModernLayout}col-lg-8{/if}">
                                    <select name="xallegro_shipping_carrier" id="xallegro_shipping_carrier_{$line.id_carrier|intval}" class="custom-select">
                                        {foreach from=$carrier_list item=carrier}
                                            <option value="{$carrier.id}" {if !empty($line.package_info) && $line.package_info.id_operator == {$carrier.id}}selected="selected"{/if}>{$carrier.name|escape:'html':'UTF-8'}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="xallegro_shipping_carrier_name_{$line.id_carrier|intval}" class="{if !$isModernLayout}col-lg-4{/if} form-control-label control-label">{l s='Nazwa operatora Allegro' mod='x13allegro'}</label>
                                <div class="{if !$isModernLayout}col-lg-8{/if}">
                                    <input type="text" name="xallegro_shipping_carrier_name" id="xallegro_shipping_carrier_name_{$line.id_carrier|intval}" class="form-control" value="{if !empty($line.package_info)}{$line.package_info.operator_name}{/if}" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer x13allegro-modal-footer">
                        <button type="button" id="xallegro_order_invoice_cancel" class="btn btn-left btn-default" data-dismiss="modal">{l s='Anuluj' mod='x13allegro'}</button>
                        <button type="submit" name="saveShippingInfo" class="btn btn-primary">{l s='Zapisz' mod='x13allegro'}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
{/foreach}

<script type="text/javascript">
    var XAllegro = new X13Allegro();
    XAllegro.ajaxUrl = "{$link->getAdminLink('AdminXAllegroOrderShipping', false)}";
    XAllegro.ajaxToken = "{$order_shipping_token}";
    XAllegro.isModernLayout = {version_compare($smarty.const._PS_VERSION_, '1.7.7.0', '>=')|intval};
    XAllegro.orderShipping();
</script>
