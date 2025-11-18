<div class="panel col-lg-12">
    <fieldset>
        <div class="panel-heading">{l s='Dostępne znaczniki' mod='x13allegro'}</div>

        <table class="table" cellspacing="0" cellpadding="0" style="margin: auto;">
            <thead>
            <tr>
                <th>{l s='Znacznik' mod='x13allegro'}</th>
                <th>{l s='Opis' mod='x13allegro'}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $variables as $key => $value}
                <tr class="{cycle values=",alt_row odd"}">
                    <td><span class="x13allegro_black">{literal}{{/literal}{$key}{literal}}{/literal}</span></td>
                    <td>{$value}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </fieldset>
</div>
