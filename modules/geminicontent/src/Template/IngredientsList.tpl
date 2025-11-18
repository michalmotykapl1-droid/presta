<div class="gemini-ingredients-list">
    <h3>Składniki:</h3>
    {if $ingredients}
        <table class="table table-bordered table-ingredients">
            <thead>
                <tr>
                    <th>Składnik</th>
                    <th class="text-center" style="width: 200px;">Certyfikat BIO</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$ingredients item=ingredient}
                    <tr>
                        <td>{$ingredient.name nofilter}</td>
                        <td class="text-center">
                            {if $ingredient.is_bio}
                                <i class="icon-check text-success"></i> <strong>BIO</strong>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p>Brak szczegółowych informacji o składnikach.</p>
    {/if}
</div>