(function ($, x13Import) {

    $(function () {
        $('.x13import-option-slider').each(function () {
            let $el = $(this);

            $el.slider({
                min: $el.data('min'),
                max: $el.data('max'),
                value: $el.data('value'),
                step: $el.data('step'),
                slide: function (event, ui) {
                    $el.closest('.form-group').find('.x13import-option-slider-input input').val(ui.value).trigger('change');
                }
            });
        });

        $(document).on('change', '.x13import-option-slider-input input', function () {
            const $slider = $(this).closest('.form-group').find('.x13import-option-slider');
            const sliderMin = parseFloat($slider.slider('option', 'min'));
            const sliderMax = parseFloat($slider.slider('option', 'max'));
            let value = $(this).val();

            if (value < sliderMin) {
                value = sliderMin;
            } else if (value > sliderMax) {
                value = sliderMax;
            }

            $(this).val(value)
            $slider.slider('value', value);
        });

        $(document).on('change', '#ximport_gpt_form input[name*="wholesalers"]', function () {
            const $wholesalers = $(this).closest('.form-group').find('input[value!="ALL_WHOLESALERS"]');

            if ($(this).attr('value') === 'ALL_WHOLESALERS' && $(this).is(':checked')) {
                $wholesalers.prop('checked', false).prop('disabled', true);
            } else {
                $wholesalers.prop('disabled', false);
            }
        });
    });

}(jQuery, x13Import || {}));
