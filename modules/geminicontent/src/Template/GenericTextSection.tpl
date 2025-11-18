<div class="gemini-generic-text-section">
    {if $display_section_title && $section_key != 'ogolny_tekst_przed_sekcjami'}
        <h4>{$section_title}:</h4>
    {/if}

    {* Logika do wyświetlania treści akapitu - obsługa pojedynczych stringów lub tablic akapitów *}
    {assign var="content_to_display" value=null}

    {if isset($description_text)}
        {assign var="content_to_display" value=$description_text}
    {elseif isset($preparation_method)}
        {assign var="content_to_display" value=$preparation_method}
    {elseif isset($allergen_info)}
        {assign var="content_to_display" value=$allergen_info}
    {elseif isset($storage_info)}
        {assign var="content_to_display" value=$storage_info}
    {elseif isset($origin_country)}
        {assign var="content_to_display" value=$origin_country}
    {elseif isset($producer_info)}
        {assign var="content_to_display" value=$producer_info}
    {elseif isset($additional_info)}
        {assign var="content_to_display" value=$additional_info}
    {elseif isset($generic_text)}
        {assign var="content_to_display" value=$generic_text}
    {/if}

    {if is_array($content_to_display)}
        {foreach $content_to_display as $paragraph}
            {if !empty($paragraph)} {* Upewnij się, że akapit nie jest pusty *}
                <p>{$paragraph nofilter}</p>
            {/if}
        {/foreach}
    {elseif !empty($content_to_display)}
        <p>{$content_to_display nofilter}</p>
    {/if}
</div>