<div class="gemini-nutritional-facts">
    <h3>Wartości odżywcze:</h3>
    {if $nutritional_facts}
        {* pobieramy pierwszy element tablicy *}
        {assign var=firstFact value=$nutritional_facts|@reset}

        {* warunek na istnienie - sprawdzamy, czy tablica jest asocjacyjna i zawiera 'display_name' i 'value' *}
        {if !empty($nutritional_facts)
            && is_array($firstFact)
            && isset($firstFact.display_name)
            && isset($firstFact.value)
        }
            <table class="table table-nutrition">
                <thead>
                    <tr><th>Wartość odżywcza</th><th>W 100 g</th></tr>
                </thead>
                <tbody>
                {foreach from=$nutritional_facts item=fact}
                    {* Pomiń puste elementy, które mogłyby zostać dodane przez fallback lub nierozpoznane frazy *}
                    {if is_array($fact) && isset($fact.display_name) && isset($fact.value) && !empty($fact.value)}
                        <tr>
                            <td>{$fact.display_name nofilter}</td>
                            <td>{$fact.value nofilter}</td>
                        </tr>
                    {elseif is_string($fact) && !empty($fact)}
                        {* Jeśli element jest prostym stringiem (nierozpoznana linia NF), wyświetl go w jednej komórce *}
                        <tr>
                            <td colspan="2">{$fact nofilter}</td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
        {else}
            {* Jeśli dane nie są w oczekiwanym formacie asocjacyjnym (np. tylko nazwy składników w tablicy numerycznej) *}
            <ul class="nutritional-list">
                {foreach from=$nutritional_facts item=item}
                    {* Obsłuż, jeśli to array bez display_name (np. z 'value' ale pustym 'display_name') *}
                    {if is_array($item) && isset($item.value) && !empty($item.value)}
                        <li>{$item.value nofilter}</li>
                    {elseif is_string($item) && !empty($item)}
                        <li>{$item nofilter}</li>
                    {/if}
                {/foreach}
            </ul>
        {/if}
    {else}
        <p>Brak szczegółowych danych o wartościach odżywczych.</p>
    {/if}
</div>