{extends file="helpers/options/options.tpl"}

{block name="input"}
    {if $field['type'] == 'checkbox'}
        <div class="col-lg-9">
            {foreach $field['choices'] AS $choice}
                <p class="checkbox clearfix">
                    {strip}
                        <label class="col-lg-5" for="{$key}_{$choice.key}" id="choice_{$key}_{$choice.key}">
                            <input type="checkbox" name="{$key}[{$choice.key}]" id="{$key}_{$choice.key}" value="{$choice.key}" {if isset($field['value'][$choice.key])}checked="checked"{/if} {if isset($choice.disabled) && $choice.disabled}disabled="disabled"{/if} /> <span class="choice-label">{$choice.name}</span> {if isset($choice.desc) && $choice.desc}<span class="help-block">{$choice.desc}</span>{/if}
                        </label>
                    {/strip}
                </p>
            {/foreach}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="after"}
    <script type="text/javascript">
        var XAllegro = new X13Allegro();
        XAllegro._configurationWiderColsFix($('#xallegro_log_form'));
        XAllegro._configurationDependencies();
    </script>
{/block}
