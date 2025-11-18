{if isset($shipments) && !empty($shipments)}
    {if isset($shipments['free'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
            <tr>
                <th>
                    <span class="toggle-shipments"><strong>{l s='Darmowe opcje dostawy' mod='x13allegro'}</strong></span>
                </th>
            </tr>
            </thead>
            <tbody>
                {foreach $shipments['free'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['in_advance_courier'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='Kurier' mod='x13allegro'}</strong> - {l s='płatność z góry' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['in_advance_courier'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['in_advance_package'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='Paczka' mod='x13allegro'}</strong> - {l s='płatność z góry' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['in_advance_package'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['in_advance_letter'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='List ' mod='x13allegro'}</strong> - {l s='płatność z góry' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['in_advance_letter'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['in_advance_pos'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='Odbiór w punkcie' mod='x13allegro'}</strong> - {l s='płatność z góry' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['in_advance_pos'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['cash_on_delivery_courier'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='Kurier' mod='x13allegro'}</strong> - {l s='płatność przy odbiorze' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['cash_on_delivery_courier'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['cash_on_delivery_package'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='Paczka' mod='x13allegro'}</strong> - {l s='płatność przy odbiorze' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['cash_on_delivery_package'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['cash_on_delivery_letter'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='List ' mod='x13allegro'}</strong> - {l s='płatność przy odbiorze' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['cash_on_delivery_letter'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['cash_on_delivery_pos'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='Odbiór w punkcie' mod='x13allegro'}</strong> - {l s='płatność przy odbiorze' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['cash_on_delivery_pos'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}

    {if isset($shipments['abroad'])}
        <table class="table shipments-table" cellspacing="0" cellpadding="0" style="width: 100%">
            <thead>
                <tr>
                    <th>
                        <span class="toggle-shipments"><strong>{l s='Wysyłka za granicę' mod='x13allegro'}</strong> - {l s='płatność z góry' mod='x13allegro'}</span>
                    </th>
                    <th class="shipments-thead" width="140"><span>{l s='Pierwsza sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="140"><span>{l s='Kolejna sztuka' mod='x13allegro'}</span></th>
                    <th class="shipments-thead" width="100"><span>{l s='Ilość w paczce' mod='x13allegro'}</span></th>
                </tr>
            </thead>
            <tbody>
                {foreach $shipments['abroad'] as $row}
                    {include file="./shipments-line.tpl" row=$row class={cycle values=",alt_row odd"}}
                {/foreach}
            </tbody>
        </table>
    {/if}
{/if}
