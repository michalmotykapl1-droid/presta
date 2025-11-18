{if $input.restrictions.string_length}
    <p class="help-block category-field-text-counter {if isset($iterator) && $iterator == 1 && empty($fields_value[$input.name])}hidefix{elseif empty($fields_value[$input.name])}hide{/if}">
        <span class="counter">{$fields_value[$input.name]|count_characters:true}</span>/{$input.restrictions.string_length}
    </p>
{/if}
