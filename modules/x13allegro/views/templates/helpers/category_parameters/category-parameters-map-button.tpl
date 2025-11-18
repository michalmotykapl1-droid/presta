{if $map_button && (!isset($input.is_ambiguous_input) || !$input.is_ambiguous_input)}
    <button class="btn btn-default xallegro-fieldMap" data-id="{$input.map_id}" {if !$form_id}disabled="disabled"{/if} title="{$input.map_count_title}">
        <i class="icon-asterisk"></i>&nbsp;{l s='Mapuj pole' mod='x13allegro'}
        <span class="badge badge-info">{if $input.map_count > 0}{$input.map_count}{/if}</span>
    </button>
{/if}
