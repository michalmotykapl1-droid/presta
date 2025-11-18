(function($) {
    $.XImport = function() {
        categoriesList = function() {
            var activeMarkup = false;

            // hack dropdown Enter event
            $('#form-ximport_category').find('button.dropdown-toggle').each(function (index, element) {
                $(element).attr('type', 'button');
            });

            $(document).on('mouseup', function(e) {
                if (activeMarkup !== false && !activeMarkup.is(e.target) && activeMarkup.has(e.target).length === 0) {
                    updateMarkup();
                }
            });

            $('.markup_container').on('keyup', 'input.markup_edit', function (e) {
                e.preventDefault();

                if (e.key === 'Enter' || e.keyCode === 13) {
                    updateMarkup();
                }
            });

            $('span.markup_edit, span.markup_type_edit').on('click', function() {
                activeMarkup = $(this).parent();
                activeMarkup.find('span.markup_edit').hide().next().attr('x-focus', true).show().css('display', 'inline-block').trigger('focus').trigger('x-cast');
                activeMarkup.find('span.markup_type_edit').hide().next().show().css('display', 'inline-block');

                // hack submit Enter event
                $('#form-ximport_category').attr('onsubmit', 'return false;');
            });

            function updateMarkup()
            {
                var input = activeMarkup.find('input.markup_edit');
                var select = activeMarkup.find('select.markup_type_edit');

                input.trigger('x-cast');
                input.removeAttr('x-focus').hide().prev().text(input.val()).show();
                select.hide().prev().text(select.find('option:selected').text()).show();
                $('#form-ximport_category').removeAttr('onsubmit', 'return false;');
                activeMarkup = false;

                doAdminAjax({
                    controller: help_class_name,
                    action: 'updateMarkup',
                    token: $('input[name=token]').val(),
                    ajax: true,
                    id: input.attr('x-id'),
                    markup: input.val(),
                    markupType: select.val()
                },
                function(response) {
                    var data = $.parseJSON(response);
                    showSuccessMessage(data.confirmations);
                });
            }

            $('.x-import a.list-action-enable').on('click', function() {
                var self = $(this);

                doAdminAjax(
                    {
                        controller: help_class_name,
                        action: 'updateImport',
                        token: $('input[name=token]').val(),
                        id: $(this).parents('tr').find('input[name="ximport_categoryBox[]"]').val(),
                        ajax: true
                    },
                    function(response)
                    {
                        var data = $.parseJSON(response);
                        showSuccessMessage(data.confirmations);
                        changeStatus(self);
                    }
                );

                return false;
            });

            var bulkMarkup = false;
            if ($('.bulkMarkup').length > 0) {
                bulkMarkup = $('.bulkMarkup').parent();
            }
            else if ($('[name="submitBulkmarkupximport_category"]').length > 0) {
                bulkMarkup = $('[name="submitBulkmarkupximport_category"]');
            }

            var bulkTittlePattern = false;
            if ($('.bulkTittlePattern').length > 0) {
                bulkTittlePattern = $('.bulkTittlePattern').parent();
            }
            else if ($('[name="submitBulktittlePatternximport_category"]').length > 0) {
                bulkTittlePattern = $('[name="submitBulktittlePatternximport_category"]');
            }

            if (bulkMarkup) {
                bulkMarkup.removeAttr('onclick');
                bulkMarkup.on('click', function(e) {
                    e.preventDefault();

                    if ($('[name="ximport_categoryBox[]"]:checked').length > 0) {
                        $("#bulk_markup_hidden").empty();
                        $('[name="ximport_categoryBox[]"]:checked').each(function() {
                            $("#bulk_markup_hidden").prepend('<input type="hidden" name="ximport_categoryBox[]" value="' + $(this).val() + '">');
                        });

                        $("#ximport_bulk_popup_markup").modal('show');
                    }
                    else {
                        alert('Nie wybrano kategorii.');
                    }
                });
            }

            if (bulkTittlePattern) {
                bulkTittlePattern.removeAttr('onclick');
                bulkTittlePattern.on('click', function(e) {
                    e.preventDefault();

                    if ($('[name="ximport_categoryBox[]"]:checked').length > 0) {
                        $("#bulk_title_pattern_hidden").empty();
                        $('[name="ximport_categoryBox[]"]:checked').each(function() {
                            $("#bulk_title_pattern_hidden").prepend('<input type="hidden" name="ximport_categoryBox[]" value="' + $(this).val() + '">');
                        });

                        $("#ximport_bulk_popup_title_pattern").modal('show');
                    }
                    else {
                        alert('Nie wybrano kategorii.');
                    }
                });
            }
        };

        excludeList = function() {
            $('.x-exclude a').on('click', function() {
                var self = $(this);

                doAdminAjax(
                    {
                        controller: help_class_name,
                        action: 'excludeProducts',
                        token: $('input[name=token]').val(),
                        id: $(this).parents('tr').find('input[name="ximport_productBox[]"]').val(),
                        ajax: true
                    },
                    function(response)
                    {
                        var data = $.parseJSON(response);
                        showSuccessMessage(data.confirmations);
                        changeStatus(self);
                    }
                );

                return false;
            });

            $('.x-exclude-price a').on('click', function() {
                var self = $(this);

                doAdminAjax(
                    {
                        controller: help_class_name,
                        action: 'excludeProductsPrices',
                        token: $('input[name=token]').val(),
                        id: $(this).parents('tr').find('input[name="ximport_productBox[]"]').val(),
                        ajax: true
                    },
                    function(response)
                    {
                        var data = $.parseJSON(response);
                        showSuccessMessage(data.confirmations);
                        changeStatus(self);
                    }
                );

                return false;
            });

            $('.x-exclude-quantity a').on('click', function() {
                var self = $(this);

                doAdminAjax(
                    {
                        controller: help_class_name,
                        action: 'excludeProductsQuantities',
                        token: $('input[name=token]').val(),
                        id: $(this).parents('tr').find('input[name="ximport_productBox[]"]').val(),
                        ajax: true
                    },
                    function(response)
                    {
                        var data = $.parseJSON(response);
                        showSuccessMessage(data.confirmations);
                        changeStatus(self);
                    }
                );

                return false;
            });
        };

        changeStatus = function (object) {
            //Prestashop 1.5
            if (object.find('img').length) {
                var status = object.find('img').attr('src').split('/');
                if (status[3] === 'enabled.gif') {
                    object.attr('title', 'Wyłączone')
                        .children('img')
                        .attr('src', '../img/admin/disabled.gif')
                        .attr('title', 'Wyłączone')
                        .blur();
                }
                else {
                    object.attr('title', 'Włączone')
                        .children('img')
                        .attr('src', '../img/admin/enabled.gif')
                        .attr('title', 'Włączone')
                        .blur();
                }
            }
            // bootstrap
            else {
                if (object.hasClass('action-disabled')) {
                    object.removeClass('action-disabled')
                        .addClass('action-enabled')
                        .children('.icon-check').removeClass('hidden')
                        .siblings('.icon-remove').addClass('hidden');
                }
                else if (object.hasClass('action-enabled')) {
                    object.removeClass('action-enabled')
                        .addClass('action-disabled')
                        .children('.icon-remove').removeClass('hidden')
                        .siblings('.icon-check').addClass('hidden');
                }
                object.blur();
            }
        };

        return self;
    }
})(jQuery);
