<div class="form-group row">
    <label for="allegro_preorder" class="control-label col-lg-3">
        {l s='Przedsprzedaż' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select id="allegro_preorder" name="allegro_preorder">
            <option value="0" selected="selected">{l s='Nie' mod='x13allegro'}</option>
            <option value="1">{l s='Tak' mod='x13allegro'}</option>
        </select>
    </div>
</div>
<div class="form-group row" style="display: none;">
    <label for="allegro_preorder_date" class="control-label col-lg-3">
        {l s='Wysyłka od' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <input type="text" id="allegro_preorder_date" name="allegro_preorder_date" class="datepicker fixed-width-md" value="{$smarty.now|date_format:'%d.%m.%Y'}" disabled="disabled">
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.datepicker').datepicker();

        $('select[name="allegro_preorder"]').on('change', function() {
            if (parseInt($(this).val()) == 1) {
                $('input[name="allegro_preorder_date"]').prop('disabled', false).parents('.form-group').show();
            } else {
                $('input[name="allegro_preorder_date"]').prop('disabled', true).parents('.form-group').hide();
            }
        });
    });
</script>
