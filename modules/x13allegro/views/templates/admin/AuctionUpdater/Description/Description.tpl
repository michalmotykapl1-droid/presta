<div class="form-group row">
    <label for="allegro_description_mode" class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select id="allegro_description_mode" name="allegro_description_mode">
            <option value="1" selected="selected">{l s='dodaj do opisu' mod='x13allegro'}</option>
            <option value="2">{l s='znajdź i zamień' mod='x13allegro'}</option>
        </select>
    </div>
</div>

<div class="clearfix">
    <div class="form-group row">
        <label for="allegro_description_add_mode" class="control-label col-lg-3">
            {l s='Dodaj do opisu' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select id="allegro_description_add_mode" name="allegro_description_add_mode">
                <option value="1" selected="selected">{l s='dodaj na początku opisu' mod='x13allegro'}</option>
                <option value="2">{l s='dodaj na końcu opisu' mod='x13allegro'}</option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label for="allegro_description_add" class="control-label col-lg-3">
            {l s='Treść' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <textarea id="allegro_description_add" name="allegro_description_add"></textarea>
        </div>
    </div>
</div>

<div class="clearfix" style="display: none;">
    <div class="form-group row">
        <label for="allegro_description_replace_from" class="control-label col-lg-3">
            {l s='Szukaj w opisie' mod='x13allegro'}
        </label>
        <div class="col-lg-9 regex-group">
            <span class="input-group-addon" style="display: none;">/</span>
            <input type="text" id="allegro_description_replace_from" name="allegro_description_replace_from" value="">
            <span class="input-group-addon" style="display: none;">/</span>
        </div>
    </div>
    <div class="form-group row">
        <label for="allegro_description_replace_to" class="control-label col-lg-3">
            {l s='Zamień na' mod='x13allegro'}
        </label>
        <div class="col-lg-9 regex-group">
            <span class="input-group-addon" style="display: none;">/</span>
            <input type="text" id="allegro_description_replace_to" name="allegro_description_replace_to" value="">
            <span class="input-group-addon" style="display: none;">/</span>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-lg-offset-3 col-lg-9">
            <label for="allegro_description_regex" class="control-label">
                <input type="checkbox" id="allegro_description_regex" name="allegro_description_regex" value="1">
                {l s='Użyj wyrażeń regularnych' mod='x13allegro'}
            </label>
            <div class="help-block">
                <b>{l s='Uwaga!!!' mod='x13allegro'}</b><br>
                {l s='Nie walidujemy wyrażeń regularnych, używasz tej opcji na własną odpowiedzialność.' mod='x13allegro'}<br>
                {l s='Nieprawidłowo użyte wyrażenia regularne mogą spowodować całkowite popsucie opisu oferty.' mod='x13allegro'}
            </div>
        </div>
    </div>
</div>

<style>
    .mce-panel {
      background-color: transparent;
      border: none;
      box-shadow: none;
      -webkit-box-shadow: none;
      -moz-box-shadow: none;
    }
</style>
<script>
    $(document).on('change', '[name="allegro_description_mode"]', function () {
        if (parseInt($(this).val()) === 1) {
            $('select[name="allegro_description_add_mode"]').parents('.form-group').parent().show();
            $('input[name="allegro_description_replace_from"]').parents('.form-group').parent().hide();
        }
        else {
            $('select[name="allegro_description_add_mode"]').parents('.form-group').parent().hide();
            $('input[name="allegro_description_replace_from"]').parents('.form-group').parent().show();
        }
    });

    $(document).on('change', '[name="allegro_description_regex"]', function () {
        if ($(this).prop('checked')) {
            $(this).parents('.form-group').parent().find('.regex-group').addClass('input-group').find('.input-group-addon').show();
        } else {
            $(this).parents('.form-group').parent().find('.regex-group').removeClass('input-group').find('.input-group-addon').hide();
        }
    });

    $(document).on('focus', 'textarea[name="allegro_description_add"]', function () {
        initTinyMce();
    });

    initTinyMce();

    function initTinyMce() {
        tinymce.remove();
        tinymce.init($.extend({
            selector: '#allegro_description_add',
            setup: function (editor) {
                editor.on('init', function () {
                    $.fancybox.update();
                });
            },
            init_instance_callback: function (editor) {
                editor.on('Change', function (e) {
                    if (e.lastLevel !== null && typeof e.originalEvent !== "undefined") {
                        $(document).find('textarea[name="allegro_description_add"]').html(e.level.content);
                    }
                });
            }
        }, initTinyMceLite));
    }
</script>
