<form id="categories_import_form" action="#" method="post">
    <div class="form-group{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '<')} clearfix{/if}">
        <div class="radio">
            <label for="categoriesImportMode_1">
                <input type="radio" name="categoriesImportMode" value="1" id="categoriesImportMode_1" checked="checked">
                {l s='Importuj kategorie hurtowni do kategorii głównej sklepu [1](domyślnie)[/1]' tags=['<strong>'] mod='x13import'}
            </label>
        </div>
        <div class="radio">
            <label for="categoriesImportMode_2">
                <input type="radio" name="categoriesImportMode" value="2" id="categoriesImportMode_2">
                {l s='Importuj kategorie hurtowni do wybranej kategorii' mod='x13import'}
            </label>
        </div>
        <div class="radio">
            <label for="categoriesImportMode_3">
                <input type="radio" name="categoriesImportMode" value="3" id="categoriesImportMode_3">
                {l s='Importuj najgłębszą kategorie hurtowni do wybranej kategorii' mod='x13import'}
            </label>
        </div>
        {if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>')}
            <div class="radio">
                <label for="categoriesImportMode_4">
                    <input type="radio" name="categoriesImportMode" value="4" id="categoriesImportMode_4">
                    {l s='Przypisz najgłębszą kategorie hurtowni do wybranej kategorii' mod='x13import'}
                </label>
            </div>
        {/if}
    </div>

    <div class="form-group{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '<')} clearfix{/if}">
        <div id="categoriesImportMode_tree" style="display: none;">
            {$categoryTree}
        </div>
    </div>

    <div class="form-group{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '<')} clearfix{/if}" style="display: none;">
        <label for="categoriesImportDefault" class="control-label">Kategoria główna:</label>
        <select name="categoriesImportDefault" class="fixed-width-xxl" id="categoriesImportDefault"></select>
    </div>

    <div class="form-group{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '<')} clearfix{/if}" style="display: none;">
        <div class="checkbox">
            <label for="categoriesOverwrite">
                <input type="checkbox" name="categoriesOverwrite" value="1" id="categoriesOverwrite">
                {l s='Nadpisz już przypisane kategorie' mod='x13import'}
            </label>
        </div>
    </div>

    <button type="submit" name="submitCategoriesImportForm" class="btn btn-default">
        <i class="icon-download"></i>&nbsp;&nbsp;Importuj kategorie do sklepu
    </button>
</form>

<script>
    $(document).on('change', 'input[name="categoriesImportMode"]', function () {
        var importMode = parseInt($(this).val());

        if (importMode === 1) {
            $('#categoriesImportMode_tree').hide();
            $('input[name="categoriesOverwrite"]').closest('.form-group').hide();

        } else {
            $('#categoriesImportMode_tree').show();
            $('input[name="categoriesOverwrite"]').closest('.form-group').show();
        }

        if (importMode === 4) {
            $('select[name="categoriesImportDefault"]').closest('.form-group').show();
            $('#categoriesAssigned input').removeAttr('checked').attr('type', 'checkbox').attr('name', 'categoriesAssigned[]');
            removeSelectedCategories();
        } else {
            $('select[name="categoriesImportDefault"]').closest('.form-group').hide();
            $('#categoriesAssigned input').removeAttr('checked').attr('type', 'radio').attr('name', 'categoriesAssigned');
            removeSelectedCategories();
        }
    });

    $(document).on('change', '#categoriesAssigned input', function () {
        changeCategoriesDefault();
    });

    function removeSelectedCategories() {
        $('.tree-folder-name').removeClass('tree-selected');
        $('.tree-item-name').removeClass('tree-selected');
        changeCategoriesDefault();
    }

    function changeCategoriesDefault() {
        var defaultCategorySelect = $('#categoriesImportDefault');
        defaultCategorySelect.empty();

        $('#categoriesAssigned input:checked').each(function() {
            defaultCategorySelect.append(
                '<option ' + (($(this).attr('value') == defaultCategorySelect.val()) ? 'selected="selected"' : '') +
                ' value="' + $(this).attr('value') + '">' + $(this).siblings('label').text() +
                '</option>');
        });
    }
</script>
