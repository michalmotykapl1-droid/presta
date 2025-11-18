function X13Allegro()
{
    this.ajaxUrl = currentIndex;
    this.ajaxToken = xallegro_token;
    this.ajaxRequest = [];
    this.ajaxForceTerminateLoop = false;
    this.isModernLayout = false;
    this.presta17 = false;
    this.successAuctions = 0;
    this.errorAuctions = 0;
    this.successAuctionsMsg = '';
    this.errorAuctionsMsg = '';

    this.productizationName = '';
    this.productizationDescription = '';
    this.productizationMode = 'ASSIGN';
    this.productSelectMode = 0;
    this.titleMaxCount = 75;
    this.shippingRateMarketplaces = [];

    this._cast();
}

X13Allegro.prototype = {

    constructor: X13Allegro,

    _cast: function()
    {
        cast = function()
        {
            var type = $(this).data('cast');
            var unsigned = false;
            var allowEmpty = false;
            var precision = 2;

            // workaround for classes
            if (typeof type === 'undefined') {
                if ($(this).hasClass('xcast-float')) {
                    type = 'float';
                } else if ($(this).hasClass('xcast-int')) {
                    type = 'integer';
                } else {
                    return;
                }
            }

            if (typeof $(this).data('cast-unsigned') !== 'undefined' && $(this).data('cast-unsigned')) {
                unsigned = true;
            }
            // workaround for classes
            else if ($(this).hasClass('xcast-unsigned')) {
                unsigned = true;
            }

            if (typeof $(this).data('cast-allow-empty') !== 'undefined' && $(this).data('cast-allow-empty')) {
                allowEmpty = true;
            }
            // workaround for classes
            else if ($(this).hasClass('xcast-allow-empty')) {
                allowEmpty = true;
            }

            if (typeof $(this).data('cast-precision') !== 'undefined') {
                precision = parseInt($(this).data('cast-precision'));
            }
            // @todo workaround for classes

            var val = $(this).val();

            if (!val.length) {
                if (allowEmpty) {
                    return;
                }

                val = 0;
            }

            val = val.toString().replace(/[^\d,.\-]/g, '').replace(',', '.');

            if (val < 0 && !unsigned) {
                val *= -1;
            }

            if (isNaN(val) || val.length == 0) {
                val = 0;
            }

            if (type == 'float') {
                $(this).val(castFormat(val, precision));
            }
            else if (type == 'integer') {
                $(this).val(parseInt(val));
            }

            function castFormat(value, precision)
            {
                var newValue = parseFloat(value);
                var pow = Math.pow(10, 2);
                newValue *= pow;

                var nextDigit = Math.floor(newValue * 10) - 10 * Math.floor(newValue);
                newValue = (nextDigit >= 5 ? Math.ceil(newValue) : Math.floor(newValue));

                return (newValue / pow).toFixed(precision);
            }
        };

        if ($('[data-cast]').length) {
            $('[data-cast]').each(cast).on('focusout change xchange', cast);
        }
    },

    _shipmentsSwitch: function(element)
    {
        shipmentsSwitch = function()
        {
            if ($(this).is(':checked')) {
                $(this).parent().parent().find('input[type="text"]:not(.shipment-disabled)').removeAttr('disabled');
            } else {
                $(this).parent().parent().find('input[type="text"]:not(.shipment-disabled)').prop('disabled', 'disabled');
            }
        };

        $(element).on('change', shipmentsSwitch).each(shipmentsSwitch);
    },

    _countryCodeSwitch: function(element)
    {
        if (element.val() === 'PL') {
            $('#province, #post_code').removeAttr('disabled');
        } else {
            $('#province, #post_code').val('').prop('disabled', 'disabled');
        }
    },

    _titleCount: function(title)
    {
        var customChars = {
            '<': 4,
            '>': 4,
            '&': 5,
            '"': 6
        };

        var size = title.length;
        var count = 0;

        for (var key in customChars) {
            count = title.split(key).length - 1;
            size += (count*(customChars[key]-1));
        }

        return size;
    },

    _modalAlert: function($modalAlert, confirmCallback, cancelCallback)
    {
        $modalAlert.addClass('active');
        $modalAlert.find('.modal-body').css({
            'max-height': $(window).outerHeight() - 200,
            'overflow-y': 'auto'
        });
        $modalAlert.closest('.modal').css('overflow-y', 'hidden');

        $modalAlert.on('click', '.modal_alert-confirm', function(e) {
            e.preventDefault();
            $modalAlert.removeClass('active');
            $modalAlert.closest('.modal').css('overflow-y', 'auto');

            if (typeof confirmCallback === 'function') {
                confirmCallback();
            }
        });

        $modalAlert.on('click', '.modal_alert-cancel', function(e) {
            e.preventDefault();
            $modalAlert.removeClass('active');
            $modalAlert.closest('.modal').css('overflow-y', 'auto');

            if (typeof cancelCallback === 'function') {
                cancelCallback();
            }
        });
    },

    _configurationWiderColsFix: function($form)
    {
        $form.find('.form-group').each(function(index, element) {
            var confId = $(element).find('.control-label').parent();
            confId.find('.col-lg-3').removeClass('col-lg-3').addClass('col-lg-4');
            confId.find('.col-lg-offset-3').removeClass('col-lg-offset-3').addClass('col-lg-offset-4');
            confId.find('.col-lg-9').removeClass('col-lg-9').addClass('col-lg-8');
        });
    },

    _configurationDependencies: function()
    {
        handleFieldDependencies();

        var $fieldDependencies = getFieldDependencies();
        for (var i = 0; i < $fieldDependencies.length; i++) {
            $(document).off($fieldDependencies[i]).on('change', '[name="'+ $fieldDependencies[i] +'"]', function () {
                handleFieldDependencies($fieldDependencies[i]);
            }).bind(i);
        }

        function getFieldDependencies()
        {
            var fieldDependencies = [];
            $('.depends-on').each(function (index, node) {
                var $element = $(node);
                var $classes = $element.prop('class').split(/\s+/);
                for (var i = 0; i < $classes.length; i++) {
                    var current = $classes[i];
                    if (current.includes('depends-field')) {
                        var parts = current.replace('depends-field-', '').split(':');
                        fieldDependencies.push(parts[0]);
                    }
                }
            });

            return fieldDependencies;
        }

        function handleFieldDependencies(specificFieldName)
        {
            var specificField = specificFieldName || false;

            $('.depends-on').each(function (index, node) {
                var $element = $(node);
                var $classes = $element.prop('class').split(/\s+/);
                var $method = 'match';
                var $fieldName = false;
                var $fieldValue = false;
                var $fieldType = false;
                var $currentValue;
                var $typeOfTheField;

                if ($element.hasClass('depends-on-multiple')) {
                    $fieldValue = [];
                    $fieldName = [];
                    $fieldType = [];
                }

                for (var i = 0; i < $classes.length; i++) {
                    var current = $classes[i];

                    if (current.includes('depends-where')) {
                        if (current === 'depends-where-is-not') {
                            $method = 'not_match';
                        }
                    }

                    if (current.includes('depends-field')) {
                        var parts = current.replace('depends-field-', '').split(':');
                        var $nameOfTheField = parts[0];
                        var $valueOfTheField = parts[1].split('--');

                        if ($element.hasClass('depends-on-multiple')) {
                            $fieldName.push($nameOfTheField);
                            $fieldValue.push($valueOfTheField);
                        } else {
                            $fieldName = $nameOfTheField;
                            $fieldValue = $valueOfTheField;
                        }

                        if ($('input[name="'+ $nameOfTheField +'"]').length > 0) {
                            $typeOfTheField = $('input[name="'+ $nameOfTheField +'"]').attr('type');
                        } else if ($('textarea[name="'+ $nameOfTheField +'"]').length === 1) {
                            $typeOfTheField = 'textarea';
                        } else if ($('select[name="'+ $nameOfTheField +'"]').length === 1) {
                            $typeOfTheField = 'select';
                        }

                        if ($element.hasClass('depends-on-multiple')) {
                            $fieldType.push($typeOfTheField);
                        } else {
                            $fieldType = $typeOfTheField;
                        }
                    }
                }

                if ($element.hasClass('depends-on-multiple')) {
                    var showBasedOnMultiple = true;
                    for (var i = 0; i < $fieldName.length; i++) {
                        if ($fieldType[i] === 'checkbox' || $fieldType[i] === 'radio') {
                            $currentValue = $('[name="'+ $fieldName[i] +'"]:checked').val();
                        } else if ($fieldType[i] === 'select') {
                            $currentValue = $('[name="'+ $fieldName[i] +'"] option:selected').val();
                        } else {
                            $currentValue = $('[name="'+ $fieldName[i] +'"]').val();
                        }

                        if ($method === 'match') {
                            if (!inArray($currentValue, $fieldValue[i])) {
                                showBasedOnMultiple = false;
                            }
                        }
                        if ($method === 'not_match') {
                            if (inArray($currentValue, $fieldValue[i])) {
                                showBasedOnMultiple = false;
                            }
                        }
                    }

                    if (showBasedOnMultiple) {
                        $element.slideDown();
                    } else {
                        $element.slideUp();
                    }
                } else {
                    if (specificField && specificField !== $fieldName) {
                        return;
                    }

                    if ($fieldType === 'checkbox' || $fieldType === 'radio') {
                        $currentValue = $('[name="'+ $fieldName +'"]:checked').val();
                    } else if ($fieldType === 'select') {
                        $currentValue = $('[name="'+ $fieldName +'"] option:selected').val();
                    } else {
                        $currentValue = $('[name="'+ $fieldName +'"]').val();
                    }

                    if ($method === 'not_match' && $fieldName && $fieldValue) {
                        if ($fieldValue.includes($currentValue)) {
                            $element.slideUp();
                        } else {
                            $element.slideDown();
                        }
                    }
                    if ($method === 'match' && $fieldName && $fieldValue) {
                        if ($fieldValue.includes($currentValue)) {
                            $element.slideDown();
                        } else {
                            $element.slideUp();
                        }
                    }
                }
            });
        }

        function inArray(needle, haystack)
        {
            var length = haystack.length;
            for (var i = 0; i < length; i++) {
                if (haystack[i] === needle) return true;
            }
            return false;
        }
    },

    accountAuth: function()
    {
        var self = this;
        var authInterval;
        var authIntervalTime = 5;
        var authDeviceCode;

        var $modal = $(document).find('#account_authorization_modal');

        $(document).on('click', '#allegroAccountAuth', function (e) {
            e.preventDefault();

            self.ajaxPOST({
                action: 'authorizeApplication',
                accountId: $(this).data('account')

            }, function () {
                var cover = $('<div id="allegro_cover"></div>');
                cover.appendTo('body').hide().fadeIn(400);

            }, function (json) {
                $(document).find('body #allegro_cover').remove();

                if (!json.success) {
                    showErrorMessage(json.text);
                    return false;
                }

                authIntervalTime = json.authIntervalTime;
                authDeviceCode = json.authDeviceCode;

                $modal.find('.modal-header .x13allegro-modal-title-small span').text(json.accountUsername);
                $modal.find('.modal-body').html(json.html);
                $modal.modal('show');
            });
        });

        $(document).on('click', '#allegroAuthButton', function () {
            var button = $(this);

            $modal.find('.modal-header .close').remove();
            $modal.find('.modal-body #allegroAuthProcess').show();
            $modal.find('.modal-body #allegroAuthButton').prop('disabled', 'disabled').addClass('btn-disabled').hide();

            authInterval = setInterval(function () {
                self.ajaxPOST({
                    action: 'authorizeApplicationCheck',
                    accountId: button.data('account'),
                    authDeviceCode: authDeviceCode

                }, null , function (json) {
                    if (json.success) {
                        if (json.hasOwnProperty('authorized') && json.authorized) {
                            clearInterval(authInterval);

                            $modal.find('.modal-body #allegroAuthStart').hide();
                            $modal.find('.modal-body #allegroAuthProcess').hide();
                            $modal.find('.modal-body #allegroAuthSuccess #allegroAuthMarketplace').text(json.baseMarketplace);
                            $modal.find('.modal-body #allegroAuthSuccess').show();

                            if (json.configurationForm) {
                                $modal.find('.modal-body #allegroAuthConfiguration').html(json.configurationForm);
                                $modal.find('.modal-body #allegroAuthConfiguration #configuration_id_language').trigger('change');
                            } else {
                                $modal.find('.modal-body #allegroAuthSuccess #allegroAuthButtonFinish').show();
                            }
                        }
                    }
                    else {
                        clearInterval(authInterval);
                        $modal.find('.modal-body #allegroAuthStart').hide();
                        $modal.find('.modal-body #allegroAuthProcess').hide();
                        $modal.find('.modal-body #allegroAuthError #allegroAuthErrorMsg').text(json.text);
                        $modal.find('.modal-body #allegroAuthError').show();
                    }
                });
            }, authIntervalTime *1000);
        });

        $(document).on('change', '#configuration_id_language', function() {
            var $button = $modal.find('.modal-body #allegroAuthConfiguration #allegroAuthButtonSave');

            if ($(this).val() === '') {
                $button.addClass('disabled').prop('disabled', true);
            } else {
                $button.removeClass('disabled').prop('disabled', false);
            }
        });
    },

    auctionList: function()
    {
        var self = this;
        var $modalForm = $('#xallegro_auction_form_modal');
        var $modalListSettings = $('#xallegro_auction_list_settings');

        $('#xallegroFilterStatus').on('change', function () {
            $(this).parents('form').submit();
        });

        $('#xallegroFilterMarketplace').on('change', function () {
            $(this).parents('form').submit();
        });

        $('#showProductizationTools').on('click', function (e) {
            e.preventDefault();
            $('#productizationTools').toggle();
        });

        $('#auctionListSettings').on('click', function (e) {
            e.preventDefault();
            $modalListSettings.modal('show');
        });

        $modalListSettings.find('.auction-fields-list-table tbody').sortable({
            handle: '.auction-fields-list-sort',
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                return ui;
            },
            start: function (e, ui) {
                ui.placeholder.css({
                    height: ui.item.outerHeight(),
                    width: ui.item.outerWidth()
                });
            }
        });

        $modalListSettings.on('click', 'button[name="saveAuctionListSettings"]', function (e) {
            e.preventDefault();

            var fields = {};
            $modalListSettings.find('.auction-fields-list-check').each(function (index, element) {
                fields[$(element).val()] = +$(element).is(':checked');
            });

            self.ajaxPOST({
                action: 'saveAuctionListSettings',
                fields: fields
            }, null, function (json) {
                window.location.href = json.url;
            });
        });

        $(document).on('click', '#bulkUpdateAuctionsTrigger', function(e) {
            e.preventDefault();
            $('.bulk-actions .bulkUpdate').parent().click();
        });

        $(document).on('change', '.x-auction-list-auto_renew select', function() {
            var offerId = $(this).data('id');
            var autoRenew = $(this).val();

            self.ajaxPOST({
                action: 'changeAutoRenew',
                offerId: offerId,
                autoRenew: autoRenew
            }, null, function(json) {
                if (json.success) {
                    showSuccessMessage('Zaktualizowano auto wznawianie');
                } else {
                    showErrorMessage('Wystąpił błąd podczas aktualizacji auto wznawiania');
                }
            });
        });

        $(document).on('focusout', '.x-auction-form-list input', function() {
            if ($(this).data('max') !== '' && (parseInt($(this).prop('value')) > parseInt($(this).data('max')) && parseInt($(this).data('oos')) == 0)) {
                $(this).prop('value', $(this).data('max'));
            }

            if (parseInt($(this).prop('value')) < 1) {
                $(this).prop('value', 1);
            }
        });

        $(document).on('click', '.x-auction-form-list-delete', function(e) {
            e.preventDefault();

            $(this).closest('tr').remove();
            if ($modalForm.find('.x-auction-form-list-delete').length <= 0) {
                $modalForm.modal('hide');
            }
        });

        $(document).on('click', '.x-updater-redo-btn', function(e) {
            e.preventDefault();

            $modalForm.modal('hide');
            $('.bulk-actions .bulkUpdate').parent().click();
        });

        $(document).on('click', '.x-updater-logger-all', function (e) {
            e.preventDefault();

            $modalForm.find('.x-updater-logger-content').find('li').show();
            $(this).hide();
        });

        $(document).on('click', '.x-updater-logger-with-errors', function (e) {
            e.preventDefault();

            $modalForm.find('.x-updater-logger-content').find('li').show();
            $modalForm.find('.x-updater-logger-content').find('li:not(.as-error)').hide();
            $modalForm.find('.x-updater-logger-all').show();
        });

        $(document).on('click', '.x-updater-logger-with-warnings', function (e) {
            e.preventDefault();

            $modalForm.find('.x-updater-logger-content').find('li').show();
            $modalForm.find('.x-updater-logger-content').find('li:not(.as-warning)').hide();
            $modalForm.find('.x-updater-logger-all').show();
        });

        $(document).on('change', '[x-name="update-auction-entity"]', function () {
            if ($(this).val() != 0) {
                $modalForm.find('.x-auction-form-submit').show();
                $modalForm.find('.x-updater-entity').hide();
                $modalForm.find('.x-updater-extra-settings #updater_entity_' + $(this).val()).show();
            } else {
                $modalForm.find('.x-auction-form-submit, .x-updater-entity').hide();
            }
        });

        $(document).on('click', '.x-auction-form-submit', function(e) {
            e.preventDefault();
            processFormModal();
        });

        $(document).on('click', '.x-updater-action-close-popup', function(e) {
            e.preventDefault();
            $(this).attr('disabled', 'disabled');
            $modalForm.find('.x-updater-redo-btn').attr('disabled', 'disabled');
            window.location.reload();
        });

        // Update
        $('[x-name=action_update]').on('click', function(e) {
            e.preventDefault();

            $('input[name="xallegro_auctionBox[]"]').removeAttr('checked');
            $(this).parents('tr').find('input[name="xallegro_auctionBox[]"]').prop('checked', true);

            getFormModal(getSelectedAuctions('action_update'), 'update');
        });

        if ($('.bulkUpdate').length > 0) {
            var bulkUpdate = $('.bulkUpdate').parent();
            bulkUpdate.removeAttr('onclick');

            bulkUpdate.bind('click', function(e) {
                e.preventDefault();
                var items = getSelectedAuctions('action_update');

                if (items.length > 0) {
                    getFormModal(items, 'update');
                } else {
                    alert('Nie wybrano ofert do aktualizacji.');
                }
            });
        }

        // Finish
        $('[x-name=action_finish]').on('click', function(e) {
            e.preventDefault();

            $('input[name="xallegro_auctionBox[]"]').removeAttr('checked');
            $(this).parents('tr').find('input[name="xallegro_auctionBox[]"]').prop('checked', true);

            getFormModal(getSelectedAuctions('action_finish'), 'finish');
        });

        if ($('.bulkFinish').length > 0) {
            var bulkFinish = $('.bulkFinish').parent();
            bulkFinish.removeAttr('onclick');

            bulkFinish.bind('click', function(e) {
                e.preventDefault();
                var items = getSelectedAuctions('action_finish');

                if (items.length > 0) {
                    getFormModal(items, 'finish');
                } else {
                    alert('Nie wybrano ofert do zakończenia.');
                }
            });
        }

        // Resume
        $('[x-name=action_redo]').on('click', function(e) {
            e.preventDefault();

            $('input[name="xallegro_auctionBox[]"]').removeAttr('checked');
            $(this).parents('tr').find('input[name="xallegro_auctionBox[]"]').prop('checked', true);

            getFormModal(getSelectedAuctions('action_redo'), 'redo');
        });

        if ($('.bulkRedo').length > 0) {
            var bulkRedo = $('.bulkRedo').parent();
            bulkRedo.removeAttr('onclick');

            bulkRedo.bind('click', function(e) {
                e.preventDefault();
                var items = getSelectedAuctions('action_redo');

                if (items.length > 0) {
                    getFormModal(items, 'redo');
                } else {
                    alert('Nie wybrano ofert do wznowienia.');
                }
            });
        }

        // AutoRenew setting
        if ($('.bulkAutoRenew').length > 0) {
            var bulkAutoRenew = $('.bulkAutoRenew').parent();
            bulkAutoRenew.removeAttr('onclick');

            bulkAutoRenew.bind('click', function(e) {
                e.preventDefault();
                var items = getSelectedAuctions('action_unbind'); // get offers by unbind

                if (items.length > 0) {
                    getFormModal(items, 'auto_renew');
                } else {
                    alert('Nie wybrano ofert do zmiany ustawień wznawiania, lub wybrane oferty nie są powiązane.');
                }
            });
        }

        // Unbind
        if ($('.bulkUnbind').length > 0) {
            var bulkUnbind = $('.bulkUnbind').parent();
            bulkUnbind.removeAttr('onclick');

            bulkUnbind.bind('click', function(e) {
                e.preventDefault();
                var items = getSelectedAuctions('action_unbind');
                var formAction = $(this).closest('form').attr('action') + '&submitBulkunbind';

                if (items.length > 0) {
                    self._modalAlert($('#offer_unbind_modal_alert'), function() {
                        var formBulkUnbind = $('<form>', {
                            action: formAction,
                            method: 'POST'
                        });

                        for (var key in items) {
                            formBulkUnbind.append($('<input>', {
                                name: 'xallegro_auctionBox[]',
                                type: 'hidden',
                                value: items[key].id
                            }));
                        }

                        $('body').append(formBulkUnbind);
                        formBulkUnbind.submit();
                    });
                } else {
                    alert('Nie wybrano ofert do usunięcia powiązania, lub wybrane oferty nie są powiązane.');
                }
            });

            $('[x-name=action_unbind]').on('click', function(e) {
                e.preventDefault();

                $('input[name="xallegro_auctionBox[]"]').removeAttr('checked');
                $(this).parents('tr').find('input[name="xallegro_auctionBox[]"]').prop('checked', true);

                bulkUnbind.trigger('click');
            });
        }

        function getSelectedAuctions(action)
        {
            var $input = $('[name="xallegro_auctionBox[]"]:checked');
            var items = [];

            if ($input.length > 0) {
                $input.each(function() {
                    var actionIsset = $(this).closest('tr').find('[x-name=' + action + ']');
                    if (typeof actionIsset.data('id') !== 'undefined') {
                        items.push({
                            id: actionIsset.data('id'),
                            title: actionIsset.data('title')
                        });
                    }
                });
            }

            return items;
        }

        function getFormModal(auctions, formAction)
        {
            self.ajaxPOST({
                action: 'getAuctionFormModal',
                auctions: auctions,
                formAction: formAction
            }, function() {
                var cover = $('<div id="allegro_cover"></div>');
                cover.appendTo('body').hide().fadeIn(400);
            }, function (json) {
                $(document).find('body #allegro_cover').remove();

                if (json.success) {
                    $modalForm.html(json.html);

                    if (formAction == 'update') {
                        $modalForm.find('.x-auction-form-submit').hide();
                    }

                    $modalForm.modal('show');
                    $modalForm.find('[data-toggle="tooltip"]').tooltip();
                    self._cast();
                } else {
                    alert(json.message);
                }
            });
        }

        function processFormModal()
        {
            var action = $modalForm.find('input[name="action"]').val();
            var auctions = [];
            var hasErrors = false;
            var hasWarnings = false;
            var messageOnFinish = false;

            $modalForm.find('input[data-name="xAllegroAuctionId"]').each(function(index, element) {
                if (!$(element).is(':disabled')) {
                    auctions.push(parseFloat($(element).closest('tr').data('id')));
                }
            });

            var doRequest = function(auctionIndex, replayAction, replayData)
            {
                if (!auctions.length) {
                    $modalForm.find('.x-auction-form-submit').hide();
                    $modalForm.find('.x-updater-error-message').text('Brak ofert do aktualizacji.').show();
                }

                if (auctions.length == auctionIndex) {
                    $modalForm.find('.x-updater-action-close-popup').show();
                    $modalForm.find('.x-updater-progress-bar').addClass('is-finished');
                    $modalForm.find('.x-updater-start-title').hide();
                    $modalForm.find('.x-updater-end-title').show();

                    if (hasErrors) {
                        $modalForm.find('.x-updater-logger-with-errors').show();
                    }
                    if (hasWarnings) {
                        $modalForm.find('.x-updater-logger-with-warnings').show();
                    }
                    if (messageOnFinish) {
                        $modalForm.find('.x-updater-finish-message').text(messageOnFinish).show();
                    }

                    if (action == 'auctionUpdate') {
                        $modalForm.find('.x-updater-redo-btn').show();
                    }

                    return;
                }

                var auction = auctions[auctionIndex];
                var ajaxData = {
                    action: (typeof replayAction !== 'undefined' ? replayAction : action),
                    auction: auction,
                    auctionIndex: auctionIndex
                };

                if (action == 'auctionRedo') {
                    ajaxData = $.extend(ajaxData, {
                        auctionAutoRenew: $modalForm.find('.x-auction-form-list tr[data-id="' + auction + '"] select[data-name="xAllegroAuctionAutoRenew"]').val(),
                        auctionQuantity: $modalForm.find('.x-auction-form-list tr[data-id="' + auction + '"] input[data-name="xAllegroAuctionQuantity"]').val()
                    });
                }
                else if (action == 'auctionUpdate') {
                    var entity = $modalForm.find('[x-name="update-auction-entity"]').val();
                    var entityAdditionalData = $modalForm.find('.x-updater-extra-settings #updater_entity_' + entity).find('input, select, textarea');

                    ajaxData = $.extend(ajaxData, {
                        entity: entity,
                        additionalData: entityAdditionalData.serializeArray()
                    });
                }

                if (typeof replayData !== 'undefined' && replayData !== null) {
                    ajaxData = $.extend(ajaxData, {
                        replayData: replayData
                    });
                }

                self.ajaxPOST(ajaxData, function() {
                    if (auctionIndex == 0 && typeof replayAction === 'undefined') {
                        $modalForm.find('.modal-header .close').remove();
                        $modalForm.find('.x-auction-form-submit').hide();
                        $modalForm.find('.x-updater-methods').hide();

                        $modalForm.find('.x-auction-form-list').fadeOut(400, function() {
                            $modalForm.find('.x-updater-finish-message').text('').hide();
                            $modalForm.find('.x-updater-progress-to').text(auctions.length);
                            $modalForm.find('.x-updater-progress').fadeIn(400);
                            $modalForm.find('.x-updater-progress-bar-fill').css('width', '1%');
                            $modalForm.find('.x-updater-logger').show();
                        });
                    }
                }, function(json) {
                    if ("messageOnFinish" in json && json.messageOnFinish) {
                        messageOnFinish = json.messageOnFinish;
                    }

                    $modalForm.find('.x-updater-logger-content').find('li.as-placeholder').remove();

                    if (!json.success && !json.continue) {
                        $modalForm.find('.x-updater-action-close-popup').show();
                        $modalForm.find('.x-updater-progress').remove();
                        $modalForm.find('.x-updater-error-message').text(json.message).show();

                        return;
                    }
                    else if (!json.success) {
                        if ("asWarning" in json && json.asWarning) {
                            $modalForm.find('.x-updater-logger-content').prepend('<li class="as-warning">' + json.message + '</li>');
                            hasWarnings = true;
                        } else {
                            $modalForm.find('.x-updater-logger-content').prepend('<li class="as-error">' + json.message + '</li>');
                            hasErrors = true;
                        }
                    }

                    if (json.success && json.message != '') {
                        $modalForm.find('.x-updater-logger-content').prepend('<li class="' + ("asPlaceholder" in json && json.asPlaceholder ? 'as-placeholder' : '') + '">' + json.message + '</li>');
                    }

                    if ("replayAction" in json && json.replayAction) {
                        doRequest(auctionIndex, json.replayAction, ("replayData" in json ? json.replayData : null));
                    } else {
                        if ("processed" in json) {
                            var progress = (100 * json.processed) / auctions.length;
                            $modalForm.find('.x-updater-progress-bar-fill').css('width', Math.round(progress) + '%');
                            $modalForm.find('.x-updater-progress-from').html(json.processed);
                        }

                        doRequest(++auctionIndex);
                    }
                });
            };

            if (action == 'auctionAuto_renew') {
                self.ajaxPOST({
                    action: 'changeAutoRenew',
                    offerId: auctions,
                    autoRenew: $modalForm.find('select[name="allegro_auto_renew"]').val()
                }, null, function(json) {
                    if (json.success) {
                        window.location.reload();
                    } else {
                        showErrorMessage('Wystąpił błąd podczas aktualizacji auto wznawiania');
                    }
                });
            } else {
                doRequest(0);
            }
        }
    },

    auctionBind: function()
    {
        var self = this;

        $('input#name').autocomplete('index.php?controller=AdminXAllegroAuctionsList&token=' + token + '&ajax=1&action=getProductList', {}).result(function(event, data, formatted) {
            $('input#id_product').prop('value', data[1]);
            getProductAttributes(data[1]);
        });

        $('input#name').on('input', function() {
            if ($(this).prop('value') === '') {
                $('input#id_product').prop('value', '0');
            }
        });

        $('input#id_product').on('input', function(){
            var val = parseInt($(this).val());
            if (!isNaN(val) && val > 0) {
                getProductAttributes(val);
            }
        });

        function getProductAttributes(id_product)
        {
            self.ajaxPOST({
                action: 'getAttributes',
                id_product: id_product

            }, function() {
                $('fieldset#fieldset_0').parent().fadeTo('fast', 0.2);

            }, function(data)
            {
                $('select#id_product_attribute option').remove();

                $.each(data, function(index, value) {
                    $('select#id_product_attribute').append(new Option(value.attribute_designation, value.id_product_attribute));
                });

                if ($('select#id_product_attribute option').size() === 0) {
                    $('select#id_product_attribute').append(new Option('Brak', '0'));
                }

                $('fieldset#fieldset_0').parent().fadeTo('fast', 1);
            })
        }
    },

    refreshTags: function(only_products)
    {
        var self = this;
        var tagsBox = $('[x-name="tags"]');
        only_products = (typeof only_products !== 'undefined' && only_products ? 1 : 0);

        self.ajaxPOST({
            action: 'getTags',
            id_allegro_category: parseInt($('#allegro_category_current').val(), 10),
            id_xallegro_account: $('#id_xallegro_account').val(),
            productsIds: self.getAuctionProductsIds(),
            onlyProducts: only_products

        }, function() {
            if (!only_products) {
                tagsBox.fadeTo('fast', 0.2);
                tagsBox.find('.form-wrapper').html('');
            }

            $('div[x-name=product_tags]').html('');
            $('input[x-name="product_tags"]').removeAttr('checked');
            $('a[x-name="product_tags"]').prop('disabled', 'disabled');
            $('.tags-input-error').remove();

        }, function(data) {
            if (data['tags']) {
                $('a[x-name="product_tags"]').parent().show();

                if (!only_products) {
                    tagsBox.find('.form-wrapper').html((data['tags_category']));
                    tagsBox.fadeTo('fast', 1);
                }

                if (data['tags_product']) {
                    $('div[x-name=product_tags]').each(function () {
                        if (data.tags_product[$(this).attr('x-index')]) {
                            $(this).html('<div class="form-horizontal">'
                                + '<input type="hidden" id="xallegro_tags_account" name="xallegro_tags_account" value="' + $('[name=id_xallegro_account]').val() + '">'
                                + data.tags_product[$(this).attr('x-index')]
                                    .replace(/xallegro_tag\[\d+\]/g, 'item[' + $(this).attr('x-index') + '][tags]')
                                + '</div>').find('table').show();

                            $('input[x-name="product_tags"][x-index="' + $(this).attr('x-index') + '"]').prop('checked', 'checked');
                            $('a[x-name="product_tags"][x-index="' + $(this).attr('x-index') + '"]').removeAttr('disabled');
                        }
                        else {
                            $(this).html('<div class="form-horizontal">'
                                + '<input type="hidden" id="xallegro_tags_account" name="xallegro_tags_account" value="' + $('[name=id_xallegro_account]').val() + '">'
                                + data.tags.replace(/xallegro_tag\[\d+\]/g, 'item[' + $(this).attr('x-index') + '][tags]')
                                + '</div>').find('table').show();
                        }
                    });
                }
                else {
                    $('div[x-name=product_tags]').each(function () {
                        $(this).html('<div class="form-horizontal">'
                            + '<input type="hidden" id="xallegro_tags_account" name="xallegro_tags_account" value="' + $('[name=id_xallegro_account]').val() + '">'
                            + data.tags.replace(/xallegro_tag\[\d+\]/g, 'item[' + $(this).attr('x-index') + '][tags]')
                            + '</div>').find('table').show();
                    });
                }
            }
            else {
                $('a[x-name="product_tags"]').parent().hide();

                tagsBox.find('.panel-heading').after('<p class="tags-input-error">Tagi ofertowe są dostępne tylko dla kont korzystających z abonamentu Allegro.</p>');
                tagsBox.fadeTo('fast', 1);
            }
        });
    },

    getAuctionProductsIds: function()
    {
        var productsIds = [];
        $('[x-name=product_switch]').each(function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var row = $(this).parents('tr').next();
            var product = {};

            product['x_id'] = row.attr('x-id');
            product['id_product'] = row.find('input[name="item[' + row.attr('x-id') + '][id_product]"]').val();
            product['id_product_attribute'] = row.find('input[name="item[' + row.attr('x-id') + '][id_product_attribute]"]').val();

            productsIds.push(Object.values(product).join('_'));
        });

        return productsIds;
    },

    auctionForm: function()
    {
        var self = this;
        var bulkContainer = $('#bulk_container');
        var bulkBottom = parseInt(bulkContainer.css('bottom'));
        var bulkBottomStick = bulkBottom - bulkContainer.find('fieldset').outerHeight() -5;

        var stickyIO = new IntersectionObserver(function (entries)
        {
            if (entries[0].intersectionRatio === 1) {
                $(entries[0].target).css('bottom', bulkBottom + 'px');
            } else {
                $(entries[0].target).css('bottom', bulkBottomStick + 'px');
            }

            calculateSticky();
        }, {
            threshold: [0, 1]
        });

        function calculateSticky()
        {
            var rect = bulkContainer[0].getBoundingClientRect();
            var position;

            if (bulkContainer.hasClass('is-hidden')) {
                position = window.innerHeight - rect.height + (-1 * bulkBottomStick);
            } else {
                position = window.innerHeight - rect.height + (-1 * bulkBottom);
            }

            if (parseInt(position) <= parseInt(rect.top)) {
                bulkContainer.addClass('is-sticky');
            } else {
                bulkContainer.removeClass('is-sticky').addClass('is-hidden');
            }
        }

        stickyIO.observe(bulkContainer[0]);
        bulkContainer.css('visibility', 'visible');

        $(window).on('scroll', function() {
            calculateSticky();
        });

        var deliveryMarketplaceList = $('<div></div>').addClass('col-sm-4 col-lg-6').append(
            $('[x-name="shipping_rates"]').find('.x13allegro-delivery-marketplace-list').prop('outerHTML')
        );
        $('[x-name="allegro_account"]').find('fieldset > .row').append(deliveryMarketplaceList);

        $('#country_code').chosen();

        this._countryCodeSwitch($('#country_code'));

        $('#country_code').on('change', function () {
            self._countryCodeSwitch($(this));
        });

        this.attachmentsManager();

        $('select[x-name="template"]').chosen({width: '245'});
        $('select[x-name="bulk_template"]').chosen();

        $('select[x-name="responsible_producer"]').chosen({width: '100%'});
        $('select[x-name="responsible_person"]').chosen({width: '100%'});
        $('select[x-name="bulk_responsible_producer"]').chosen({width: '100%'});
        $('select[x-name="bulk_responsible_person"]').chosen({width: '100%'});


        /** ------ KONTA ALLEGRO ------------------------------------------------------------------------------------ */

        $('[name=id_xallegro_account]').removeAttr('onchange');

        var currentlySelectedAccount = $('#id_xallegro_account').val();
        $(document).on('change', '[name=id_xallegro_account]', function(e) {
            e.preventDefault();

            var select = $(this);

            self._modalAlert($('#account_change_modal_alert'), function() {
                self.ajaxAbort();

                $('#allegro-progress').hide();
                $('#account_switch_form').closest('#content').fadeTo('fast', 0.2);

                location.href = select.val();
            },
            function() {
                select.val(currentlySelectedAccount);
            });
        });

        /** ------ OPCJE PRODUKTÓW ---------------------------------------------------------------------------------- */

        $('[x-name=product_switch]').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).parent().parent().next().show();
            } else {
                $(this).parent().parent().next().hide();
            }

            calculateSticky();
        });

        $('[x-name=title]').on('input xchange', function() {
            var size = self._titleCount($(this).prop('value'));

            while (size > self.titleMaxCount) {
                $(this).prop('value', $(this).prop('value').slice(0,-1));
                size = self._titleCount($(this).prop('value'));
            }

            $(this).parent().find('[x-name=counter]').html(size);
        });

        $(document).on('click', '[x-name="productization_name_copy"]', function(e) {
            e.preventDefault();

            var $offerRow = $(this).closest('tr');
            var $offerTitle = $offerRow.find('[x-name="title"]');
            var allegroProductName = $offerRow.find('[x-name="allegro_product_name"]').val();

            $offerTitle.val(allegroProductName).trigger('input');
        });

        $(document).on('show.bs.modal', 'div[x-name="description_edit"]', function() {
            var $this = $(this);
            $this.find('[x-name="description_edit_mce"]').html($this.find('[x-name="description"]').val());

            tinymce.init($.extend({
                selector: '#' + $this.find('[x-name="description_edit_mce"]').attr('id')
            }, initTinyMceLite));

            $this.on('click', '[x-name=description_save]', function() {
                $this.find('[x-name="description"]').html(tinyMCE.activeEditor.getContent({format : 'raw'}));

                $this.off('click', '[x-name=description_cancel]');
                $this.modal('hide');
            });

            $this.on('click', '[x-name=description_cancel]', function() {
                self._modalAlert($this.find('#description_edit_modal_alert_confirm'), function() {
                    tinymce.activeEditor.setContent($this.find('[x-name="description"]').val());

                    $this.off('click', '[x-name=description_cancel]');
                    $this.modal('hide');
                });
            });
        });

        $('input[x-name="product_tags"]').on('change', function () {
            if ($(this).prop('checked')) {
                $(this).parent().find('a[x-name="product_tags"]').removeAttr('disabled');
            } else {
                $(this).parent().find('a[x-name="product_tags"]').attr('disabled', 'disabled');
            }
        });

        $('a[x-name=product_tags]').fancybox({
            autoDimensions: false,
            minWidth: 400,
            width: 400,
            height: 'auto',
            autoHeight: true,
            transitionIn: 'none',
            transitionOut: 'none',
            helpers: {
                overlay: {
                    locked: false
                }
            }
        });

        $('select[x-name=selling_mode]').on('change xchange', function () {
            var inputQty = $(this).parents('tr').find('input[x-name=quantity]');
            var inputPriceStarting = $(this).parents('tr').find('input[x-name=price_asking]');
            var inputPriceMinimal = $(this).parents('tr').find('input[x-name=price_minimal]');
            var selectDuration = $(this).parents('tr').find('select[x-name=duration]');
            var selectAutoRenew = $(this).parents('tr').find('select[x-name=auto_renew]');

            if ($(this).val() == 'BUY_NOW') {
                inputQty.removeAttr('disabled').val(inputQty.attr('x-start'));
                inputPriceStarting.prop('disabled', 'disabled').closest('.price-asking').hide();
                inputPriceMinimal.prop('disabled', 'disabled').closest('.price-minimal').hide();
                selectAutoRenew.removeAttr('disabled').show().prev().show();

                selectDuration.find('option').each(function () {
                    $(this).removeAttr('disabled').show();
                    if ($(this).data('default') == '1') {
                        selectDuration.val($(this).attr('value'));
                    }
                });
            }
            else {
                inputQty.prop('disabled', 'disabled').attr('x-start', inputQty.val()).val(1);
                inputPriceStarting.removeAttr('disabled').closest('.price-asking').show();
                inputPriceMinimal.removeAttr('disabled').closest('.price-minimal').show();
                selectAutoRenew.prop('disabled', 'disabled').hide().prev().hide();

                selectDuration.find('option').each(function () {
                    if ($(this).data('type') == 'BUY_NOW') {
                        $(this).prop('disabled', 'disabled').hide();
                    }
                });

                selectDuration.val('P3D');
            }
        });

        $('input[x-name=quantity]').on('focusout xchange', function() {
            if ($(this).attr('x-max') !== '' && (parseInt($(this).prop('value')) > parseInt($(this).attr('x-max')) && parseInt($(this).attr('x-oos')) == 0)) {
                $(this).prop('value', $(this).attr('x-max'));
            }

            if (parseInt($(this).prop('value')) < 1) {
                $(this).prop('value', 1);
            }
        });

        $('input[x-name="price_buy_now"]').on('change xchange', function () {
            var $basePriceInput = $(this);

            $basePriceInput.closest('tr').find('.marketplaces-prices > div').each(function (index, element) {
                var $marketplacePriceInput = $(element).find('input');

                if ($marketplacePriceInput.is(':read-only')) {
                    var marketplacePrice = ($basePriceInput.val() / $basePriceInput.data('rate')) * $marketplacePriceInput.data('rate');

                    // HUF must be rounded up to end with 0 or 5
                    if ($marketplacePriceInput.data('iso-code') === 'HUF') {
                        marketplacePrice = Math.round(marketplacePrice / 5) * 5;
                    }

                    $marketplacePriceInput.val(marketplacePrice).trigger('change');
                }
            });
        });

        $('input[x-name="send_tax"]').on('change', function () {
            var $input = $(this);
            var $noCategoryTax = $(this).closest('td').find('.no-category-tax');
            var $taxes = $(this).closest('tr').find('.marketplaces-taxes > div');
            var categoryId = $(this).closest('tr').find('input[x-name="category_id"]').val();

            if (categoryId == 0) {
                $input.parent().hide();
                $taxes.hide();
                $noCategoryTax.show();

                return;
            }

            $noCategoryTax.hide();
            $input.parent().show();

            $taxes.each(function (index, element) {
                if (self.shippingRateMarketplaces[$('#shipping_rate').val()].includes($(element).data('marketplace-id')) && $input.is(':checked')) {
                    $(element).show();
                } else {
                    $(element).hide();
                }
            });
        });

        $('.marketplaces-prices input').on('click', function () {
            var $input = $(this);

            if (!$input.is(':read-only')) {
                return;
            }

            self._modalAlert($('#price_edit_modal_alert'), function() {
                $input.prop('readonly', false);
            });
        });

        $('li[x-name=images] img').on('click', function() {
            var cell = $(this).parents('td');
            cell.find('li').removeClass('main_image');
            cell.find('[x-name=image_main]').val($(this).attr('x-value'));

            if (!$(this).parent().find('input').is(':checked')) {
                $(this).parent().find('input').prop('checked', 'checked').trigger('change');
            }

            $(this).parent().addClass('main_image');
        });

        $('input[x-name=images]').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).prop('checked', 'checked').parent().addClass('checked');

                if (!$(this).parents('td').find('[x-name=image_main]').val()) {
                    $(this).parent().find('img').trigger('click');
                }
            }
            else {
                $(this).removeAttr('checked').parent().removeClass('checked');

                if ($(this).parents('td').find('[x-name=image_main]').val() == $(this).val()) {
                    var firstChecked = $(this).parents('ul').find('li.checked')[0];
                    if (typeof firstChecked !== 'undefined') {
                        $(firstChecked).find('img').trigger('click');
                    }
                    else {
                        $(this).parent().removeClass('main_image');
                        $(this).parents('td').find('[x-name=image_main]').val(null);
                    }
                }
            }
        });

        $('[x-name="template"]').on('xchange', function() {
            $(this).trigger('chosen:updated');
        });

        $('[x-name=preview]').on('click', function() {
            var button = $(this);
            var xID = button.attr('x-id');
            var preview = window.open('', 'popupWindow', 'width=1200, height=600, scrollbars=yes');

            self.ajaxPOST({
                data: $('#allegro_main_form').find('tr[x-id="' + xID + '"] input, tr[x-id="' + xID + '"] select, tr[x-id="' + xID + '"] textarea').serializeArray(),
                action: 'preview'

            }, function() {
                var cover = $('<div id="allegro_cover"></div>');
                $(preview.document.body).append(cover);

            }, function(json) {
                $(preview.document.body).html(json.preview);
                $('#preview_form').remove();
            });

            return false;
        });

        $('input[x-name="preorder"]').on('change xchange', function() {
            if ($(this).prop('checked')) {
                $(this).parents('tr').find('input[x-name="preorder_date"]').removeAttr('disabled').show();
            } else {
                $(this).parents('tr').find('input[x-name="preorder_date"]').prop('disabled', true).hide();
            }
        });


        /** ------ MASOWA ZMIANA PARAMETRÓW ------------------------------------------------------------------------- */

        $('#bulk_container_show').on('click', function () {
            bulkContainer.css('bottom', bulkBottom).removeClass('is-hidden');
            return false;
        });

        $('#bulk_container_hide').on('click', function () {
            bulkContainer.css('bottom', bulkBottomStick).addClass('is-hidden');
            return false;
        });

        $('[x-name=bulk_select_all]').on('click', function (e) {
            e.preventDefault();

            $('[x-name=product_switch]:not(:checked):not(:disabled)').each(function() {
                $(this).prop('checked', 'checked').trigger('change');
            });
        });

        $('[x-name=bulk_select_none]').on('click', function (e) {
            e.preventDefault();

            $('[x-name=product_switch]:checked:not(:disabled)').each(function() {
                $(this).removeAttr('checked').trigger('change');
            });
        });

        $('[x-name=bulk_select_not_exposed]').on('click', function (e) {
            e.preventDefault();

            $('[x-name=product_switch]:not(:disabled)').each(function() {
                if ($(this).parents('tr').hasClass('exposed')) {
                    if ($(this).is(':checked')) {
                        $(this).removeAttr('checked').trigger('change');
                    }
                } else {
                    if (!$(this).is(':checked')) {
                        $(this).prop('checked', 'checked').trigger('change');
                    }
                }
            });

            calculateSticky();
        });

        $('select[x-name=bulk_productization_mode]').on('change', function() {
            var value = $(this).prop('value');

            if (value == '0') {
                return;
            }

            $('[x-name=product_switch][data-disabled="0"]').each(function() {
                $(this).closest('tr').find('[x-name="productization_mode"]').prop('value', value).trigger('change');
            });
        });

        $('#show_bulk_title_tags').on('click', function(e) {
            e.preventDefault();
            $(this).hide();
            $('#bulk_title_tags').show();
        });

        $('[x-name="bulk_title_before"]').on('click', function(e) {
            e.preventDefault();

            var bulkTitlePattern = $('input[name="bulk_title"]');
            var titlePattern = bulkTitlePattern.val().trim();

            if (!titlePattern.length) {
                return;
            }

            $('[x-name="product_switch"]:checked').each(function(i, el) {
                var itemId = $(el).parents('tr').attr('data-index');
                var titleCurrent = $('input[name="item[' + itemId + '][title]"]').val().trim();
                $('input[name="item[' + itemId + '][title]"]').val(formatTitle(itemId, titlePattern).trim() + ' ' + titleCurrent).trigger('xchange');
                bulkTitlePattern.prop('value', '');
            });
        });

        $('[x-name="bulk_title_after"]').on('click', function(e) {
            e.preventDefault();

            var bulkTitlePattern = $('input[name="bulk_title"]');
            var titlePattern = bulkTitlePattern.val().trim();

            if (!titlePattern.length) {
                return;
            }

            $('[x-name="product_switch"]:checked').each(function(i, el) {
                var itemId = $(el).parents('tr').attr('data-index');
                var titleCurrent = $('input[name="item[' + itemId + '][title]"]').val().trim();
                $('input[name="item[' + itemId + '][title]"]').val(titleCurrent + ' ' + formatTitle(itemId, titlePattern).trim()).trigger('xchange');
                bulkTitlePattern.prop('value', '');
            });
        });

        $('[x-name="bulk_title_change"]').on('click', function(e) {
            e.preventDefault();

            var bulkTitlePattern = $('input[name="bulk_title"]');
            var titlePattern = bulkTitlePattern.val().trim();

            if (!titlePattern.length) {
                return;
            }

            $('[x-name="product_switch"]:checked').each(function(i, el) {
                var itemId = $(el).parents('tr').attr('data-index');
                $('input[name="item[' + itemId + '][title]"]').val(formatTitle(itemId, titlePattern).trim()).trigger('xchange');
                bulkTitlePattern.prop('value', '');
            });
        });

        $('[x-name="bulk_productization_name_copy"]').on('click', function(e) {
            e.preventDefault();

            $('[x-name="product_switch"]:checked').each(function() {
                $(this).parent().parent().next().find('a[x-name="productization_name_copy"]').each(function() {
                    $(this).trigger('click');
                });
            });
        });

        $('[x-name=bulk_images_all]').on('click', function() {
            $('[x-name=product_switch]:checked').each(function() {
                var maxImages = $(this).parent().parent().next().find('.images-sortable').attr('x-max');
                $(this).parent().parent().next().find('input[x-name=images]').each(function(i) {
                    if (i < maxImages) {
                        $(this).prop('checked', true).trigger('change');
                    } else {
                        $(this).prop('checked', false).trigger('change');
                    }
                });
            });

            return false;
        });

        $('[x-name=bulk_images_del]').on('click', function() {
            $('[x-name=product_switch]:checked').each(function() {
                $(this).parent().parent().next().find('input[x-name=images]').each(function() {
                    $(this).prop('checked', false).trigger('change');
                });
            });

            return false;
        });

        $('[x-name=bulk_images_invert]').on('click', function() {
            $('[x-name=product_switch]:checked').each(function() {
                $(this).parent().parent().next().find('input[x-name=images]').each(function() {
                    $(this).prop('checked', !$(this).prop('checked')).trigger('change');
                });
            });

            return false;
        });

        $('[x-name=bulk_images_first]').on('click', function() {
            $('[x-name=product_switch]:checked').each(function() {
                $(this).parent().parent().next().find('input[x-name=images]').eq(0).prop('checked', true).trigger('change');
            });

            return false;
        });

        $('[x-name=promotionPackages] input').on('change', function() {
            var $input = $(this);

            $('[x-name=product_switch]').each(function() {
                if (!$(this).is(':checked')) {
                    return;
                }

                $(this).parent().parent().next().find('[x-name=' + $input.attr('x-name') + ']').prop('checked', !!$input.is(':checked')).trigger('change');
            });
        });

        $('a[x-name=bulk_price_buy_now]').on('click', function() {
            var markupAction = $(this).attr('x-action');
            var markup = parseInt($('input[x-name=bulk_price_buy_now]').val()) / 100;

            if (markupAction === 'down') {
                markup *= -1;
            }

            $('[x-name=product_switch]:checked').each(function() {
                var input = $(this).closest('tr').next().find('[x-name=price_buy_now]');
                var value = parseFloat(input.val());

                value += (value * markup);
                input.val(value).trigger('change');

                $('input[x-name="bulk_price_buy_now"]').prop('value', 0).trigger('change');
            });

            return false;
        });

        $('input[x-name=bulk_quantity]').on('focusout', function() {
            var bulkQty = $(this);
            var value = $(this).prop('value');
            var name = $(this).attr('x-name').substr(5);

            $('[x-name=product_switch]:checked').each(function() {
                var inputQty = $(this).parent().parent().next().find('[x-name=' + name + ']');
                if (!inputQty.is(':disabled')) {
                    inputQty.prop('value', value).trigger('xchange');
                }

                bulkQty.val(0).trigger('change');
            });
        });

        $('input[x-name=bulk_price_asking], input[x-name=bulk_price_minimal]').on('focusout', function() {
            var inputBulk = $(this);
            var value = $(this).prop('value');
            var name = $(this).attr('x-name').substr(5);

            $('[x-name=product_switch]:checked').each(function() {
                $(this).parent().parent().next().find('[x-name=' + name + ']').prop('value', value).trigger('xchange');
                inputBulk.prop('value', 0).trigger('change');
            });
        });

        $('input[x-name=bulk_send_tax]').on('change', function() {
            var input = $(this);

            $('[x-name=product_switch]').each(function() {
                if (!$(this).is(':checked')) {
                    return;
                }

                $(this).parent().parent().next().find('[x-name=send_tax]').prop('checked', !!input.is(':checked')).trigger('change');
            });
        });

        $('select[x-name=bulk_duration]').on('change', function() {
            var value = $(this).prop('value');
            var name = $(this).attr('x-name').substr(5);

            $('[x-name=product_switch]:checked').each(function() {
                var inputDuration = $(this).parent().parent().next().find('[x-name=' + name + ']');
                if (!inputDuration.find('option[value="' + value + '"]').is(':disabled')) {
                    inputDuration.prop('value', value).trigger('xchange');
                }
            });
        });

        $('select[x-name=bulk_quantity_type], select[x-name=bulk_selling_mode], select[x-name=bulk_auto_renew], select[x-name=bulk_template], select[x-name=bulk_size_table], select[x-name=bulk_wholesale_price]').on('change', function() {
            var value = $(this).prop('value');
            var name = $(this).attr('x-name').substr(5);

            $('[x-name=product_switch]:checked').each(function() {
                $(this).parent().parent().next().find('[x-name=' + name + ']').prop('value', value).trigger('xchange');
            });
        });

        $('input[x-name="bulk_preorder"]').on('change', function() {
            var input = $(this);

            if (input.is(':checked')) {
                $('input[name="bulk_preorder_date"]').removeAttr('disabled').show();
            } else {
                $('input[name="bulk_preorder_date"]').prop('disabled', true).hide();
            }

            $('[x-name=product_switch]:checked').each(function() {
                $(this).parent().parent().next().find('[x-name="preorder"]').prop('checked', input.is(':checked')).trigger('xchange');
            });
        });

        $('input[x-name="bulk_preorder_date"]').on('change', function() {
            var input = $(this);

            $('[x-name=product_switch]:checked').each(function() {
                $(this).parent().parent().next().find('[x-name="preorder_date"]').val(input.val());
            });
        });

        $('#itemTabBulk a').on('click', function(e) {
            e.preventDefault();
            var element = $(this).attr('data-item-tab');

            $('[x-name=product_switch]:checked').each(function() {
                var item = $(this).parent().parent().next();
                item.find('[aria-controls="itemTab_' + item.attr('x-id') + '_' + element + '"]').trigger('click');
            });
        });


        /** ------ PŁATNOŚĆ I DOSTAWA ------------------------------------------------------------------------------- */

        refreshShippingRates();

        $('select[name=pas]').on('change', function() {
            if ($(this).prop('value') == 0) {
                return;
            }

            self.ajaxPOST({
                action: 'getPas',
                id: $(this).prop('value')

            }, function() {
                $('[x-name=pas]').parent().fadeTo('fast', 0.2);

            }, function(json) {
                $.each(json, function(index, value) {
                    var input = $('[x-name=pas]').find('[name=' + index + ']');

                    if (input.is(':text') || input.is('textarea') || input.is(':hidden')) {
                        input.prop('value', value);
                    }
                    else if (input.is(':checkbox')) {
                        //do nothing
                    }
                    else if (input.is('select')) {
                        input.find('option').prop('selected', false);
                        input.find('option[value=' + value + ']').prop('selected', true);
                    }
                    else if (input.is(':radio')) {
                        input.filter("[value='" + value + "']").prop('checked', true);
                    }
                });

                $('#country_code').trigger('chosen:updated').trigger('change');
                $('[x-name=pas]').parent().fadeTo('fast', 1);
            });
        });

        $('#shipping_rate').on('change', function() {
            var select = $(this);

            self.ajaxPOST({
                action: 'changeShippingRate',
                shipping_rate: select.val()

            }, function () {
                $('[x-name=pas]').parent().fadeTo('fast', 0.2);

            }, function(json) {
                if (json.result) {
                    $('[x-name=shipping_rates]').find('tr[x-id]').each(function(index, el) {
                        var id = $(this).attr('x-id');

                        if (id in json.deliveryMethods && typeof json.deliveryMethods[id].values !== 'undefined') {
                            $(this).find('input:checkbox').prop('checked', true).trigger('change');
                            $(this).find('input:text').each(function() {
                                $(this).prop('value', json.deliveryMethods[id].values[$(this).data('name')]).trigger('focusout');
                            });

                            $(el).show();
                        }
                        else {
                            $(this).find('input:checkbox').prop('checked', false).trigger('change');
                            $(this).find('input:text').prop('value', 0).trigger('focusout').eq(2).prop('value', 1).trigger('focusout');
                            $(el).hide();
                        }
                    });

                    $('.delivery-marketplace').each(function (index, element) {
                        if (self.shippingRateMarketplaces[select.val()].includes($(element).data('marketplace-id'))) {
                            $(element).show();
                        } else {
                            $(element).hide();
                        }
                    });

                    $('[x-name="product"]').each(function() {
                        $(this).closest('tr').find('.marketplaces-prices > div').each(function (index, element) {
                            if (self.shippingRateMarketplaces[select.val()].includes($(element).data('marketplace-id'))) {
                                $(element).show();
                            } else {
                                $(element).hide();
                            }
                        });

                        $(this).closest('tr').find('[x-name="send_tax"]').trigger('change');
                    });

                    refreshShippingRates();
                    $('[x-name=pas]').parent().fadeTo('fast', 1);
                }
                else {
                    showErrorMessage(json.message);
                }
            });
        });

        function refreshShippingRates()
        {
            $('[x-name=shipping_rates]').find('table').each(function() {
                var checked = false;
                $(this).hide();

                $(this).find('input[x-name="switch"]').each(function() {
                    if ($(this).prop('checked')) {
                        checked = true;
                    }
                });

                if (checked) {
                    $(this).show();
                }
            });
        }

        function formatTitle(itemId, pattern)
        {
            var replace = {
                '{auction_title}': $('input[name="item[' + itemId + '][auction_title]"]').val(),
                '{product_id}': $('input[name="item[' + itemId + '][id_product]"]').val(),
                '{product_name}': $('input[name="item[' + itemId + '][product_name]"]').val(),
                '{product_name_attribute}': $('input[name="item[' + itemId + '][attribute_name]"]').val(),
                '{product_reference}': $('input[name="item[' + itemId + '][reference]"]').val(),
                '{product_ean13}': $('input[name="item[' + itemId + '][ean13]"]').val(),
                '{product_weight}': $('input[name="item[' + itemId + '][weight]"]').val(),
                '{manufacturer_name}': $('input[name="item[' + itemId + '][manufacturer]"]').val()
            };

            var regex = new RegExp(Object.keys(replace).join('|'), 'gi');
            return pattern.replace(regex, function(matched) {
                return replace[matched];
            });
        }


        /** ------ OTHER -------------------------------------------------------------------------------------------- */

        $('select[name=start]').on('change', function() {
            if ($(this).val() == 1) {
                $('input[name=start_time]').val(formatCurrentDate()).prop('disabled', false).show();
            } else {
                $('input[name=start_time]').prop('disabled', true).hide();
            }

            function formatCurrentDate()
            {
                var date = new Date();
                return ('0' + date.getDate()).slice(-2) + '.' +
                    ('0' + (date.getMonth()+1)).slice(-2) + '.' +
                    date.getFullYear() +
                    ' ' +
                    ('0' + date.getHours()).slice(-2) + ':' +
                    ('0' + date.getMinutes()).slice(-2);
            }
        });

        $('.xallegro-perform').parent().on('click', function(e) {
            e.preventDefault();

            $(this).addClass('allegro-send-auction-hidden');
            self.performAuctions(0);
        });

        $(document).on('click', '#allegro_success_show', function(e) {
            e.preventDefault();

            $(this).hide();
            $('.allegro-success-hidden').toggle();
        });
    },

    initPerformProductization: function()
    {
        var self = this;
        var item = 0;
        var auctionsToRetrieveCatalogInfos = $('.tr_auction_product:not(.product-disabled)').toArray();
        var categoryChangeInProgress = 0;

        var $sendAuctionBtn = $(document).find('.xallegro-perform').parent();
        var $bulkActionContainer = $(document).find('#bulk_container');
        var $modalBulkCategories = $('#bulk_change_category_modal');
        var $modalBulkCategoryParameters = $('#bulk_parameters_modal');
        var $modalBulkProductGPSR = $('#bulk_product_gpsr_modal');

        var parametersDepending = {};
        var parametersRequiredIf = {};

        self.categoryParametersForm($modalBulkCategoryParameters);

        $(document).find('div[x-name="product_category_fields"]').each(function(index, element) {
            self.categoryParametersForm($(element));
        });

        $('#allegro-valuemax').text(auctionsToRetrieveCatalogInfos.length);

        if (auctionsToRetrieveCatalogInfos.length) {
            $('#allegro-progress').show();
            getAllegroProducts(0);
        }
        
        function getAllegroProducts(auction)
        {
            var $auction = auction;

            if (typeof auction === 'number') {
                $auction = $(auctionsToRetrieveCatalogInfos[auction]);
                var $currentIndex = auction;
            }

            var $auctionIndex = parseInt($auction.data('index'));
            var $auctionDetails = $('tr[x-name="product"][x-id="' + $auctionIndex + '"]');

            if (self.ajaxForceTerminateLoop) {
                return;
            }

            self.ajaxPOST({
                action: 'searchInProductization',
                productId: $auctionDetails.find('[x-name="id_product"]').val(),
                productAttributeId: $auctionDetails.find('[x-name="id_product_attribute"]').val(),
                productName: $auctionDetails.find('[x-name="product_name"]').val(),
                productReference: $auctionDetails.find('[x-name="reference"]').val(),
                productEAN13: $auctionDetails.find('[x-name="ean13"]').val(),
                productISBN: $auctionDetails.find('[x-name="isbn"]').val(),
                productUPC: $auctionDetails.find('[x-name="upc"]').val(),
                productMPN: $auctionDetails.find('[x-name="mpn"]').val()
            }, function() {
                disableButtonsOnProcess();
            }, function(data) {
                item += 1;
                $('#allegro-valuenow').text(item);
                var valuemax = parseInt($('#allegro-valuemax').text());
                $('#allegro-progress .progress-bar').css('width', parseInt((item / valuemax) * 100) + '%');
                $('#allegro-progress .progress-bar').attr('aria-valuenow', parseInt((item / valuemax) * 100));

                if (parseInt((item / valuemax) * 100) == 100) {
                    $('#allegro-progress .progress-bar').addClass('progress-bar-success');
                    $('#allegro-progress').delay(1500).animate({opacity: 0}, 500, function() {
                        $('#allegro-progress').css('display', 'none');
                    });

                    enableButtonsOnProcess();
                }

                $auction.find('.xproductization-modal-btn').attr('data-toggle', 'modal');

                fillAllegroProductsResult($auction, data, 'init', function() {
                    $auction.find('.xproductization-product-selector').find('.modal-body .xproductization-search-progress').hide();

                    if (self.productizationMode != 'ASSIGN') {
                        $auction.find('.xproductization-mode-selector select').trigger('change');
                    }

                    if ($currentIndex + 1 < auctionsToRetrieveCatalogInfos.length) {
                        getAllegroProducts($currentIndex + 1);
                    }
                });
            });
        }

        function fillAllegroProductsResult($auction, data, mode, callback)
        {
            $auction.find('.xproductization-product-selector').find('.modal-body .xproductization-product-list .alert').remove();
            $auction.find('.xproductization-category').hide();
            $auction.find('.xproductization-product-name').hide();
            
            if (data.result) {
                $auction.find('.xproductization-indicator .xproductization-modal-btn').attr('data-products-count', data.nbProducts);
                $auction.find('.xproductization-product-selector').find('.modal-body .xproductization-product-list').html(data.productSelectionModal);
                $auction.find('.xproductization-status').text('Wybierz produkt').show();

                var nbText;
                if (data.nbProducts == 1) {
                    nbText = 'powiązanie';
                } else if (data.nbProducts < 5) {
                    nbText = 'powiązania';
                } else {
                    nbText = 'powiązań';
                }

                var byName = '';
                if (mode === 'init' && data.productsFoundMode == 'product_name') {
                    byName = ' (wyszukano po nazwie produktu)';
                }

                $auction.find('input[x-name="product_switch"]').prop('disabled', true).removeAttr('checked').trigger('change');
                $auction.find('.xproductization-mode-selector select').removeAttr('disabled');
                $auction.find('.xproductization-indicator .xproductization-modal-btn')
                    .attr('class', 'btn xproductization-modal-btn')
                    .html('<i class="icon-edit xpbutton-warning"></i><span class="xproductization-modal-btn-text"><span class="xproductization-modal-btn-text-up">Znaleziono ' + data.nbProducts + ' ' + nbText + byName + '</span><span class="xproductization-modal-btn-text-bottom"> wybierz produkt</span></span>');

                if (data.productChosen) {
                    var $auctionIndex = parseInt($auction.data('index'));
                    var $auctionDetails = $('tr[x-name="product"][x-id="' + $auctionIndex + '"]');
                    var $firstProductBtn = $auction.find('.xproductization-allegro-products [data-allegro-product]').first();

                    $firstProductBtn.parents('.xproductization-allegro-products').addClass('xproductization-products-loading');
                    $firstProductBtn.parents('.xproductization-allegro-products').find('a.disabled').attr('class', 'btn btn-primary').html('<i class="icon-plus"></i><span>Wybierz</span>');
                    $firstProductBtn.parents('.xproductization-allegro-products').find('.thumbnail').removeClass('xallegro-product-selected');

                    $firstProductBtn.attr('class', '').addClass('btn btn-allegro disabled').html('<i class="icon-check"></i><span>Wybrany</span>');
                    $firstProductBtn.closest('.thumbnail').addClass('xallegro-product-selected');

                    $auction.find('.xproductization-indicator .xproductization-modal-btn').attr('data-product-selected', $firstProductBtn.data('allegro-product'));
                    $auctionDetails.find('.xproductization-parameters-loading').show();
                    $auctionDetails.find('a[x-name="product_category_fields"]').attr('disabled', 'disabled');

                    $auctionDetails.find('.xproductization-gpsr-excluded').hide();
                    $auctionDetails.find('.xproductization-gpsr-empty-required').hide();
                    $auctionDetails.find('.xproductization-gpsr-loading').show();
                    $auctionDetails.find('a[x-name="product_gpsr"]').attr('disabled', 'disabled');

                    fillAllegroProductSelected($auction, data.productChosen, function() {
                        $firstProductBtn.parents('.xproductization-allegro-products').removeClass('xproductization-products-loading');

                        if (typeof callback === 'function') {
                            return callback();
                        }
                    });
                } else {
                    if (typeof callback === 'function') {
                        return callback();
                    }
                }
            } else {
                $auction.find('input[x-name="product_switch"]').prop('disabled', true).removeAttr('checked').trigger('change');
                $auction.find('.xproductization-mode-selector select').removeAttr('disabled');
                $auction.find('.xproductization-product-selector').find('.modal-body .xproductization-product-list').html(data.message);
                $auction.find('.xproductization-status').text('Nie znaleziono powiazania').show();
                $auction.find('.xproductization-indicator .xproductization-modal-btn').attr('data-products-count', 0);
                $auction.find('.xproductization-indicator .xproductization-modal-btn')
                    .attr('class', 'btn xproductization-modal-btn')
                    .html('<i class="icon-exclamation-triangle xpbutton-danger"></i><span class="xproductization-modal-btn-text"><span class="xproductization-modal-btn-text-up">Nie znaleziono powiązania</span><span class="xproductization-modal-btn-text-bottom">wyszukaj produkt</span></span>');

                if (typeof callback === 'function') {
                    callback();
                }
            }
        }

        function fillAllegroProductSelected($auction, data, callback)
        {
            var $auctionIndex = parseInt($auction.data('index'));
            var $auctionDetails = $('tr[x-name="product"][x-id="' + $auctionIndex + '"]');
            var $auctionTitleInput = $auctionDetails.find('[x-name="title"]');
            var productCount = $auction.find('.xproductization-indicator .xproductization-modal-btn').attr('data-products-count');

            if (parseInt(productCount) > 1) {
                $auction.find('.xproductization-indicator .xproductization-modal-btn')
                    .attr('class', 'btn xproductization-modal-btn')
                    .html('<i class="icon-list-alt xpbutton-ok"></i><span class="xproductization-modal-btn-text"><span class="xproductization-modal-btn-text-up">Wybrano produkt z ' + productCount + ' powiązań</span><span class="xproductization-modal-btn-text-bottom">zmień produkt</span></span>');
            } else {
                $auction.find('.xproductization-indicator .xproductization-modal-btn')
                    .attr('class', 'btn xproductization-modal-btn')
                    .html('<i class="icon-check xpbutton-success"></i><span class="xproductization-modal-btn-text"><span class="xproductization-modal-btn-text-up">Powiązano produkt</span><span class="xproductization-modal-btn-text-bottom">zmień produkt</span></span>');
            }

            if (data.parameters) {
                parametersDepending[$auctionDetails.attr('x-id')] = data.parametersDepending;
                parametersRequiredIf[$auctionDetails.attr('x-id')] = data.parametersRequiredIf;

                $auctionDetails.find('a[x-name="product_category_fields"]').removeAttr('disabled');
                $auctionDetails.find('div[x-name="product_category_fields"] .xproductization-parameters-wrapper')
                    .html('<div class="form-horizontal">'
                        + data.parameters
                            .replace(/category_fields/g, 'item[' + $auctionIndex + '][category_fields]')
                            .replace(/category_ambiguous_fields/g, 'item[' + $auctionIndex + '][category_ambiguous_fields]')
                        + '</div>');

                $auctionDetails.find('.product-category-fields .form-group').each(function() {
                    var $formGroup = $(this);
                    var $element = $formGroup.find('.xproductization-readonly');
                    var $select = $formGroup.find('select');

                    $element.attr('readonly', 'readonly');

                    // initialize chosen
                    // check if we need to show/hide ambiguous input
                    // check if we need to show/hide depending on parameter
                    $select.chosen({width: '250px'})
                        .trigger('change')
                        .trigger('chosen:updated');

                    if ($element.is(':checkbox')) {
                        // check if we need to show/hide depending on parameter
                        // blocking for change in productization parameters
                        $element.trigger('change')
                            .attr('onclick', 'return false;')
                            .attr('onkeydown', 'return false;');
                    }
                });

                $auctionDetails.find('a[x-name="product_category_fields"]').removeAttr('disabled');
            }

            $auctionDetails.find('.xproductization-parameters-loading').hide();
            $auctionDetails.find('.xproductization-gpsr-loading').hide();

            if (data.allegroProductCategorySimilar) {
                $auctionDetails.find('a[x-name="product_category_similar"]').removeAttr('disabled').parent().show();
                $auctionDetails.find('.xproductization-category-similar-count').text(data.allegroProductCategorySimilarCount).show();
            }

            if (self.productizationName == 'allegro') {
                $auctionTitleInput.val(data.allegroProductName).trigger('input');
            } else {
                $auctionTitleInput.val($auctionTitleInput.data('product-name')).trigger('input');
            }

            if ($auctionDetails.find('[x-name="productization_name_copy"]').length) {
                $auctionDetails.find('[x-name="productization_name_copy"]').show().removeClass('hidden');
            }

            if (self.productizationDescription == 'allegro' && data.allegroProductDescription != '') {
                $auctionDetails.find('select[x-name="template"]').prop('disabled', true).trigger('chosen:updated');
                $auctionDetails.find('a[x-name="preview"]').attr('disabled', 'disabled');
            }

            $auctionDetails.find('input[x-name="category_id"]').val(data.categoryId);
            $auctionDetails.find('input[x-name="category_is_leaf"]').val(1);
            $auctionDetails.find('input[x-name="category_gpsr"]').val(+data.categoryGPSR);
            $auctionDetails.find('input[x-name="allegro_product_id"]').val(data.allegroProductId);
            $auctionDetails.find('input[x-name="allegro_product_name"]').val(data.allegroProductName);
            $auctionDetails.find('input[x-name="allegro_product_images"]').val(data.allegroProductImages);
            $auctionDetails.find('input[x-name="allegro_product_description"]').val(data.allegroProductDescription);
            $auctionDetails.find('input[x-name="allegro_product_category_default"]').val(data.allegroProductCategoryDefault);
            $auctionDetails.find('input[x-name="allegro_product_category_similar"]').val(data.allegroProductCategorySimilar);
            $auction.find('.xproductization-status').hide();
            $auction.find('.xproductization-category').html('Kategoria: ' + data.categoryPath + ' (' + data.categoryId + ')').show();
            $auction.find('.xproductization-product-name a').attr('href', data.allegroProductUrl).text(data.allegroProductName);
            $auction.find('.xproductization-product-name').show();

            $auction.find('.xproductization-mode-selector select').removeAttr('disabled');

            if ('taxes' in data && Object.keys(data['taxes']).length) {
                $.each(data['taxes'], function(countryCode, taxes) {
                    var $marketplaceTaxes = $auctionDetails.find('.marketplaces-taxes [data-marketplace-country-code="' + countryCode + '"]');

                    if (typeof $marketplaceTaxes !== 'undefined') {
                        var $marketplaceTaxesSelect = $marketplaceTaxes.find('select');

                        $("option[value!='']", $marketplaceTaxesSelect).remove();
                        $.each(taxes, function(index, tax) {
                            $marketplaceTaxesSelect.append(new Option(tax['name'], tax['id']));
                        });

                        var taxRate = $marketplaceTaxes.data('tax-rate');
                        if ($marketplaceTaxesSelect.find('option[value="' + taxRate + '"]').length) {
                            $marketplaceTaxesSelect.val($marketplaceTaxes.data('tax-rate'));
                        } else {
                            $marketplaceTaxesSelect.val('');
                        }
                    }
                });
            }

            // reload multiple values, and check required
            self.categoryParametersForm($auctionDetails.find('div[x-name="product_category_fields"]'), true);
            checkRequiredEmptyParameters($auctionDetails.find('div[x-name="product_category_fields"]'));

            $auctionDetails.find('a[x-name="product_gpsr"]').removeAttr('disabled');

            if (data.categoryGPSR) {
                checkRequiredEmptyGPSR($auctionDetails.find('div[x-name="product_gpsr"]'));
            } else {
                $auctionDetails.find('.xproductization-gpsr-excluded').show();
            }

            // force tax visibility
            $auctionDetails.find('[x-name="send_tax"]').trigger('change');

            switch (self.productSelectMode) {
                // dont select
                case 0:
                    $auction.find('input[x-name="product_switch"]').prop('disabled', false);
                    break;

                // only issued
                case 2:
                    $auction.find('input[x-name="product_switch"]').prop('disabled', false);
                    if ($auction.find('input[x-name="product_switch"]').data('status') == 0) {
                        $auction.find('input[x-name="product_switch"]').prop('checked', true).trigger('change');
                    }
                    break;

                default:
                    $auction.find('input[x-name="product_switch"]').prop('disabled', false).prop('checked', true).trigger('change');
            }

            if (typeof callback === 'function') {
                callback();
            }
        }

        function refreshCategories($auctionDetails)
        {
            refreshParameters($auctionDetails);

            var selects = $auctionDetails.find('div[x-name=product_category] .allegro-category-select');
            var selects_l = selects.length;

            for (var i = 1; i < selects_l; i++) {
                selects.eq(i).remove();
            }

            selects.eq(0).find('select').val(0);
            $auctionDetails.find('div[x-name=product_category] .allegro-category-select select').chosen({width: '250px'});

            var categoryId = $auctionDetails.find('input[x-name="assoc_category_id"]').val();
            if (categoryId == 0) {
                return;
            }

            changeCategory(categoryId, 1, selects.eq(0), $auctionDetails, function (lastNode) {
                if (lastNode) {
                    $auctionDetails.find('.xproductization-category-assoc').show();
                }
            });
        }

        function refreshParameters($auctionDetails)
        {
            $auctionDetails.find('.xproductization-parameters-empty-required').hide();
            $auctionDetails.find('.xproductization-gpsr-excluded').hide();
            $auctionDetails.find('.xproductization-gpsr-empty-required').hide();
            $auctionDetails.find('.xproductization-category-assoc').hide();
            $auctionDetails.find('.xproductization-category-last-node').addClass('xproductization-category-empty').attr('data-original-title', 'Nie wybrano kategorii').show();

            $auctionDetails.find('a[x-name="product_category_fields"]').attr('disabled', 'disabled');
            $auctionDetails.find('a[x-name="product_gpsr"]').attr('disabled', 'disabled');
            $auctionDetails.find('a[x-name="product_category"] span').text('Wybór kategorii');
            $auctionDetails.prev().find('.xproductization-category').text('Nie wybrano kategorii').show();
        }

        function changeCategory(categoryId, fullPath, selectBox, $auctionDetails, callback)
        {
            var productsIds = [];
            var product = {};

            // if not bulk category change
            if (typeof $auctionDetails !== 'undefined') {
                product['x_id'] = $auctionDetails.attr('x-id');
                product['id_product'] = $auctionDetails.find('input[x-name="id_product"]').val();
                product['id_product_attribute'] = $auctionDetails.find('input[x-name="id_product_attribute"]').val();
                product['id_category_default'] = $auctionDetails.find('input[x-name="id_category_default"]').val();

                productsIds.push(Object.values(product).join('_'));
            }

            self.ajaxPOST({
                action: 'getCategories',
                id_allegro_category: categoryId,
                full_path: fullPath,
                productsIds: productsIds

            }, function() {
                if (typeof $auctionDetails !== 'undefined') {
                    refreshParameters($auctionDetails);
                    $auctionDetails.find('.xproductization-category-last-node').hide();
                    $auctionDetails.find('.xproductization-category-loading').show();
                    $auctionDetails.find('.xproductization-parameters-loading').show();
                    $auctionDetails.find('.xproductization-gpsr-excluded').hide();
                    $auctionDetails.find('.xproductization-gpsr-empty-required').hide();
                    $auctionDetails.find('.xproductization-gpsr-loading').show();
                }

                categoryChangeInProgress++;
                disableButtonsOnProcess();

                selectBox.closest('.modal-body').find('.category-input-error').remove();
                selectBox.closest('.modal-body').fadeTo('fast', 0.2);

            }, function(data) {
                selectBox.parent().find('.allegro-category-select select').chosen('destroy');

                if (data['categories'].length) {
                    var element = selectBox.clone().insertAfter(selectBox).hide();
                    $("option[value!='0']", element.find('select')).remove();

                    fillSelect(data['categories'], element.find('select'));
                    element.show();
                }
                else if (Object.keys(data['categories_array']).length) {
                    var first = true;
                    var thisSelectBox;

                    $.each(data['categories_array'], function(index, categories) {
                        if (first) {
                            thisSelectBox = selectBox;
                            first = false;
                        } else {
                            var selects = selectBox.parent().find('.allegro-category-select');
                            thisSelectBox = selects.eq(selects.length-1);
                        }

                        thisSelectBox.find('select').val(categories['id']);

                        if (categories['list'].length) {
                            var element = thisSelectBox.clone().insertAfter(thisSelectBox).hide();
                            $("option[value!='0']", element.find('select')).remove();

                            fillSelect(categories['list'], element.find('select'));
                            element.show();
                        }
                    });
                }

                if (typeof $auctionDetails !== 'undefined') {
                    if (data.fields_product) {
                        parametersDepending[$auctionDetails.attr('x-id')] = data.fields_product_depending;
                        parametersRequiredIf[$auctionDetails.attr('x-id')] = data.fields_product_required_if;

                        $auctionDetails.find('div[x-name="product_category_fields"] .xproductization-parameters-wrapper').html('<div class="form-horizontal">'
                            + data.fields_product
                                .replace(/category_fields/g, 'item[' + $auctionDetails.attr('x-id') + '][category_fields]')
                                .replace(/category_ambiguous_fields/g, 'item[' + $auctionDetails.attr('x-id') + '][category_ambiguous_fields]')
                            + '</div>');

                        $auctionDetails.find('a[x-name="product_category_fields"]').removeAttr('disabled');
                        $auctionDetails.find('div[x-name="product_category_fields"] .xproductization-parameters-wrapper select').each(function () {
                            // initialize chosen
                            // check if we need to show/hide ambiguous input
                            // check if we need to show/hide depending on parameter
                            $(this).chosen({width: '250px'}).trigger('change');
                        });
                        $auctionDetails.find('div[x-name="product_category_fields"] .xproductization-parameters-wrapper input[type="checkbox"]').each(function () {
                            // check if we need to show/hide depending on parameter
                            $(this).trigger('change');
                        });
                    }

                    $auctionDetails.find('.xproductization-category-loading').hide();
                    $auctionDetails.find('.xproductization-parameters-loading').hide();
                    $auctionDetails.find('.xproductization-gpsr-loading').hide();

                    $auctionDetails.find('input[x-name="category_id"]').val(categoryId);
                    $auctionDetails.find('input[x-name="category_is_leaf"]').val(data.last_node);
                    $auctionDetails.find('input[x-name="category_gpsr"]').val(+data.gpsr);

                    if (data['last_node']) {
                        $auctionDetails.find('.xproductization-category-last-node').hide();
                        $auctionDetails.find('a[x-name="product_category"] span').text('Kategoria: ' + categoryId);
                        $auctionDetails.prev().find('.xproductization-category').html('Kategoria: ' + data.category_path + ' (' + categoryId + ')').show();

                        $auctionDetails.find('a[x-name="product_gpsr"]').removeAttr('disabled');

                        if (data['gpsr']) {
                            checkRequiredEmptyGPSR($auctionDetails.find('div[x-name="product_gpsr"]'));
                        } else {
                            $auctionDetails.find('.xproductization-gpsr-excluded').show();
                        }
                    }
                    else {
                        $auctionDetails.find('.xproductization-category-last-node').removeClass('xproductization-category-empty').attr('data-original-title', 'Wybierz kategorię najniższego rzędu').show();
                        $auctionDetails.prev().find('.xproductization-category').text('Wybierz kategorię najniższego rzędu').show();
                    }

                    if (Object.keys(data['taxes']).length) {
                        $.each(data['taxes'], function(countryCode, taxes) {
                            var $marketplaceTaxes = $auctionDetails.find('.marketplaces-taxes [data-marketplace-country-code="' + countryCode + '"]');

                            if (typeof $marketplaceTaxes !== 'undefined') {
                                var $marketplaceTaxesSelect = $marketplaceTaxes.find('select');

                                $("option[value!='']", $marketplaceTaxesSelect).remove();
                                fillSelect(taxes, $marketplaceTaxesSelect);

                                var taxRate = $marketplaceTaxes.data('tax-rate');
                                if ($marketplaceTaxesSelect.find('option[value="' + taxRate + '"]').length) {
                                    $marketplaceTaxesSelect.val($marketplaceTaxes.data('tax-rate'));
                                } else {
                                    $marketplaceTaxesSelect.val('');
                                }
                            }
                        });
                    }

                    // reload multiple values, and check required
                    self.categoryParametersForm($auctionDetails.find('div[x-name="product_category_fields"]'), true);
                    checkRequiredEmptyParameters($auctionDetails.find('div[x-name="product_category_fields"]'));

                    // force tax visibility
                    $auctionDetails.find('[x-name="send_tax"]').trigger('change');
                }

                if (!data['last_node'] && !data['categories'].length && !Object.keys(data['categories_array']).length) {
                    selectBox.closest('.modal-body').find('input[x-name="product_category_input"]')
                        .after('<p class="category-input-error">Podano niepoprawny numer kategorii</p>');
                }

                selectBox.parent().find('.allegro-category-select select').chosen({width: '250px'});
                selectBox.closest('.modal-body').find('input[x-name="product_category_input"]').val(categoryId);
                selectBox.closest('.modal-body').find('input[x-name="product_category_input_current"]').val(categoryId);
                selectBox.closest('.modal-body').find('input[x-name="product_category_input_is_leaf"]').val(parseInt(data['last_node']));
                selectBox.closest('.modal-body').fadeTo('fast', 1);

                categoryChangeInProgress--;
                if (categoryChangeInProgress <= 0) {
                    categoryChangeInProgress = 0;
                    enableButtonsOnProcess();
                }

                if (typeof callback === 'function') {
                    callback(data.last_node);
                }

                function fillSelect(categories, element)
                {
                    $.each(categories, function(index, category) {
                        element.append(new Option(category['name'], category['id']));
                    });
                }
            });
        }

        function disableButtonsOnProcess()
        {
            $sendAuctionBtn.addClass('allegro-send-auction-hidden');
            $bulkActionContainer.find('input').prop('disabled', true);
            $bulkActionContainer.find('select').prop('disabled', true).trigger('chosen:updated');
            $bulkActionContainer.find('a, button').attr('disabled', 'disabled');
        }

        function enableButtonsOnProcess()
        {
            $sendAuctionBtn.removeClass('allegro-send-auction-hidden');
            $bulkActionContainer.find('input:not([data-keep-disabled])').prop('disabled', false);
            $bulkActionContainer.find('select').prop('disabled', false).trigger('chosen:updated');
            $bulkActionContainer.find('a, button').removeAttr('disabled');
        }

        $(document).on('keyup', '[x-name="productization_search"]', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                $(this).parent().find('button[name="productizationSearch"]').trigger('click');
            }
        });

        $(document).on('click', 'button[name="productizationSearch"]', function(e) {
            e.preventDefault();

            var $this = $(this);
            var $auction = $this.parents('.tr_auction_product');
            var $auctionIndex = parseInt($auction.data('index'));
            var $auctionDetails = $('tr[x-name="product"][x-id="' + $auctionIndex + '"]');

            self.ajaxPOST({
                action: 'searchInProductization',
                searchPhrase: $this.parent().find('input[name="productizationSearch_' + $auctionIndex + '"]').val(),
                productName: $auctionDetails.find('[x-name="product_name"]').val(),
                productReference: $auctionDetails.find('[x-name="reference"]').val(),
                productEAN13: $auctionDetails.find('[x-name="ean13"]').val(),
                productISBN: $auctionDetails.find('[x-name="isbn"]').val(),
                productUPC: $auctionDetails.find('[x-name="upc"]').val(),
                productMPN: $auctionDetails.find('[x-name="mpn"]').val()
            }, function () {
                $auction.find('.xproductization-search-progress').show();
                $auction.find('.xproductization-product-list').empty();
            }, function(data) {
                fillAllegroProductsResult($auction, data, 'manual', function() {
                    $auction.find('.xproductization-search-progress').hide();
                });
            });
        });

        $(document).on('click', '[data-allegro-product]', function(e) {
            e.preventDefault();

            if ($(this).parents('.xproductization-allegro-products').hasClass('xproductization-products-loading')) {
                return;
            }

            var $this = $(this);
            var $auction = $(this).parents('.tr_auction_product');
            var $auctionIndex = parseInt($auction.data('index'));
            var $auctionDetails = $('tr[x-name="product"][x-id="' + $auctionIndex + '"]');

            $this.parents('.xproductization-allegro-products').addClass('xproductization-products-loading');
            $this.parents('.xproductization-allegro-products').find('a.disabled').attr('class', 'btn btn-primary').html('<i class="icon-plus"></i><span>Wybierz</span>');
            $this.parents('.xproductization-allegro-products').find('.thumbnail').removeClass('xallegro-product-selected');

            $this.attr('class', '').addClass('btn btn-allegro disabled').html('<i class="icon-check"></i><span>Wybrany</span>');
            $this.closest('.thumbnail').addClass('xallegro-product-selected');

            $auction.find('.xproductization-indicator .xproductization-modal-btn').attr('data-product-selected', $this.data('allegro-product'));
            $auctionDetails.find('.xproductization-parameters-loading').show();
            $auctionDetails.find('a[x-name="product_category_fields"]').attr('disabled', 'disabled');

            $auctionDetails.find('.xproductization-gpsr-excluded').hide();
            $auctionDetails.find('.xproductization-gpsr-empty-required').hide();
            $auctionDetails.find('.xproductization-gpsr-loading').show();
            $auctionDetails.find('a[x-name="product_gpsr"]').attr('disabled', 'disabled');

            self.ajaxPOST({
                action: 'selectFromProductization',
                allegroProductId: $this.data('allegro-product'),
                productEAN13: $auctionDetails.find('[x-name="ean13"]').val(),
                productId: parseInt($auctionDetails.find('[x-name="id_product"]').val()),
                productAttributeId: parseInt($auctionDetails.find('[x-name="id_product_attribute"]').val())
            }, null, function(data) {
                fillAllegroProductSelected($auction, data, function() {
                    $this.parents('.modal').modal('hide');
                    $this.parents('.xproductization-allegro-products').removeClass('xproductization-products-loading');
                });
            });
        });

        $(document).on('click', '.xproductization-product-preview-description-button', function(e) {
            e.preventDefault();

            var $wrap = $(this).closest('.xproductization-allegro-products-wrap');
            var $modal = $('#xproductization_product_preview_description_modal');

            $modal.find('.x13allegro-modal-title-small').text($wrap.find('.xproductization-product-name').text());
            $modal.find('.modal-body').html($wrap.find('.xproductization-product-preview-description').html());

            self._modalAlert($modal);
        });

        $(document).on('click', '.xproductization-product-preview-images-button', function(e) {
            e.preventDefault();

            var $wrap = $(this).closest('.xproductization-allegro-products-wrap');
            var $modal = $('#xproductization_product_preview_images_modal');

            $modal.find('.x13allegro-modal-title-small').text($wrap.find('.xproductization-product-name').text());
            $modal.find('.modal-body').html($wrap.find('.xproductization-product-preview-images').html());

            self._modalAlert($modal);
        });

        $(document).on('change', '.xproductization-mode-selector select', function () {
            var $auction = $(this).parents('.tr_auction_product');
            var $auctionIndex = parseInt($auction.data('index'));
            var $auctionDetails = $('tr[x-name="product"][x-id="' + $auctionIndex + '"]');
            var $auctionTitleInput = $auctionDetails.find('[x-name="title"]');
            var valuePrevious = $(this).data('current');
            var value = $(this).val();

            // save current
            $(this).data('current', value);

            // reset all productization data
            $auctionDetails.find('input[x-name="allegro_product_id"]').val('');
            $auctionDetails.find('input[x-name="allegro_product_name"]').val('');
            $auctionDetails.find('input[x-name="allegro_product_images"]').val('');
            $auctionDetails.find('input[x-name="allegro_product_description"]').val('');
            $auctionDetails.find('input[x-name="allegro_product_category_default"]').val('');
            $auctionDetails.find('input[x-name="allegro_product_category_similar"]').val('');
            $auctionDetails.find('input[x-name="product_category_input"]').val('');
            $auctionDetails.find('a[x-name="product_category"]').attr('disabled', 'disabled').parent().hide();
            $auctionDetails.find('a[x-name="product_category_similar"]').attr('disabled', 'disabled').parent().hide();

            // reset offer title
            $auctionTitleInput.val($auctionTitleInput.data('product-name')).trigger('input');

            if ($auctionDetails.find('[x-name="productization_name_copy"]').length) {
                $auctionDetails.find('[x-name="productization_name_copy"]').hide().addClass('hidden');
            }

            if (value === 'ASSIGN') {
                var productCount = parseInt($auction.find('.xproductization-indicator .xproductization-modal-btn').attr('data-products-count'));
                var productSelected = $auction.find('.xproductization-indicator .xproductization-modal-btn').attr('data-product-selected');

                $auctionDetails.find('input[x-name="category_id"]').val(0);
                $auctionDetails.find('input[x-name="category_is_leaf"]').val(0);
                $auctionDetails.find('input[x-name="category_gpsr"]').val(0);
                $auction.find('.xproductization-indicator').show();
                $auction.find('.xproductization-category').hide();

                // force tax visibility
                $auctionDetails.find('[x-name="send_tax"]').trigger('change');

                if (productCount) {
                    if (productSelected) {
                        $auction.find('#selection_modal_' + $auctionIndex + ' a[data-allegro-product="' + productSelected + '"]').trigger('click');
                    } else {
                        $auction.find('.xproductization-status').show();
                        $auction.find('input[x-name="product_switch"]').prop('disabled', true).prop('checked', false).trigger('change');
                    }
                } else {
                    $auction.find('.xproductization-status').show();
                    $auction.find('input[x-name="product_switch"]').prop('disabled', true).prop('checked', false).trigger('change');
                }
            } else {
                $auction.find('input[x-name="product_switch"]').prop('disabled', false);

                switch (self.productSelectMode) {
                    // dont select
                    case 0: break;

                    // only issued
                    case 2:
                        if ($auction.find('input[x-name="product_switch"]').data('status') == 0) {
                            $auction.find('input[x-name="product_switch"]').prop('checked', true).trigger('change');
                        }
                        break;

                    default:
                        $auction.find('input[x-name="product_switch"]').prop('checked', true).trigger('change');
                }

                $auction.find('.xproductization-indicator').hide();
                $auction.find('.xproductization-status').hide();
                $auction.find('.xproductization-product-name').hide();

                if (valuePrevious === 'ASSIGN') {
                    $auctionDetails.find('input[x-name="category_id"]').val(0);
                    $auctionDetails.find('input[x-name="category_is_leaf"]').val(0);
                    $auctionDetails.find('input[x-name="category_gpsr"]').val(0);
                    $auction.find('.xproductization-category').text('Nie wybrano kategorii').show();

                    // force tax visibility
                    $auctionDetails.find('[x-name="send_tax"]').trigger('change');

                    refreshCategories($auctionDetails);
                }

                $auctionDetails.find('select[x-name="template"]').removeAttr('disabled').trigger('chosen:updated');
                $auctionDetails.find('a[x-name="preview"]').removeAttr('disabled');
                $auctionDetails.find('a[x-name="product_category"]').removeAttr('disabled').parent().show();
            }
        });

        $(document).on('click', '.xproductization-category-similar-count', function() {
            $(this).parents('td').find('a[x-name=product_category_similar]').trigger('click');
        });

        $(document).on('click', 'a[x-name="product_category_similar"]', function(e) {
            e.preventDefault();

            var $auctionDetails = $(this).parents('tr[x-name="product"]');
            var $modalSimilar = $auctionDetails.find('div[x-name="product_category_similar"]');

            self.ajaxPOST({
                action: 'getCategoriesSimilar',
                index: $auctionDetails.attr('x-id'),
                categoryDefault: $auctionDetails.find('input[x-name="allegro_product_category_default"]').val(),
                categorySimilar: $auctionDetails.find('input[x-name="allegro_product_category_similar"]').val(),
                categoryCurrent: $auctionDetails.find('input[x-name="category_id"]').val()
            },
            function() {
                var cover = $('<div id="allegro_cover"></div>');
                cover.appendTo('body').hide().fadeIn(400);
            },
            function(json) {
                $(document).find('body #allegro_cover').remove();

                $modalSimilar.find('.modal-body').html(json.html);
                $modalSimilar.modal('show');
            });
        });

        $(document).on('click', 'button.xproductization-category-similar', function(e) {
            e.preventDefault();

            var $auctionDetails = $(this).parents('tr[x-name="product"]');
            var $modalSimilar = $auctionDetails.find('div[x-name="product_category_similar"]');

            self.ajaxPOST({
                action: 'selectFromProductization',
                categoryCurrent: $modalSimilar.find('input[name="item[' + $auctionDetails.attr('x-id') + '][category_similar]"]:checked').val(),
                allegroProductId: $auctionDetails.find('input[x-name="allegro_product_id"]').val(),
                productEAN13: $auctionDetails.find('input[x-name="ean13"]').val(),
                productId: parseInt($auctionDetails.find('input[x-name="id_product"]').val()),
                productAttributeId: parseInt($auctionDetails.find('input[x-name="id_product_attribute"]').val())
            },
            function() {
                disableButtonsOnProcess();
                $auctionDetails.find('.xproductization-category-similar-count').hide();
                $auctionDetails.find('.xproductization-parameters-empty-required').hide();
                $auctionDetails.find('.xproductization-category-similar-loading').show();
                $auctionDetails.find('.xproductization-parameters-loading').show();
                $auctionDetails.find('.xproductization-gpsr-excluded').hide();
                $auctionDetails.find('.xproductization-gpsr-empty-required').hide();
                $auctionDetails.find('.xproductization-gpsr-loading').show();
                $auctionDetails.find('a[x-name="product_category_fields"]').attr('disabled', 'disabled');
                $auctionDetails.find('a[x-name="product_gpsr"]').attr('disabled', 'disabled');
                $auctionDetails.find('a[x-name="product_category_similar"]').attr('disabled', 'disabled');
            },
            function(data) {
                var $auction = $('.tr_auction_product[data-index="' + $auctionDetails.attr('x-id') + '"]');
                fillAllegroProductSelected($auction, data, function() {
                    enableButtonsOnProcess();
                    $auctionDetails.find('a[x-name="product_category_similar"]').removeAttr('disabled');
                    $auctionDetails.find('.xproductization-category-similar-loading').hide();
                    $auctionDetails.find('.xproductization-category-similar-count').show();
                    $modalSimilar.modal('hide');
                });
            });
        });

        $(document).on('change', '.allegro-category-select select', function() {
            var categoryId = $(this).prop('value');
            if (categoryId == 0) {
                return;
            }

            var selects = $(this).closest('.xproductization-category-list').find('.allegro-category-select');
            var selects_l = selects.length;
            var index = selects.index($(this).closest('.allegro-category-select'));

            for (var i = index + 1; i < selects_l; i++) {
                selects.eq(i).remove();
            }

            var $auctionIndex = $(this).data('index');
            var $auctionDetails;

            // if not bulk category change
            if (typeof $auctionIndex !== 'undefined') {
                $auctionDetails = $('tr[x-name="product"][x-id="' + parseInt($auctionIndex) + '"]');
            }

            var $button = $(this).closest('.modal-content').find('.modal-footer button')
            $button.attr('disabled', 'disabled');

            changeCategory(categoryId, 0, $(this).closest('.allegro-category-select'), $auctionDetails, function() {
                $button.removeAttr('disabled');
            });
        });

        $(document).on('focusout', 'input[x-name="product_category_input"]', function() {
            var categoryId = $(this).prop('value');
            var categoryIdCurrent = $(this).parent().find('input[x-name="product_category_input_current"]').prop('value');
            var $auctionIndex = $(this).data('index');
            var $auctionDetails;

            // if not bulk category change
            if (typeof $auctionIndex !== 'undefined') {
                $auctionDetails = $('tr[x-name="product"][x-id="' + parseInt($auctionIndex) + '"]');
            }

            if (categoryId == 0
                || (typeof categoryIdCurrent !== 'undefined'
                    && categoryId == categoryIdCurrent)
                || (typeof $auctionIndex !== 'undefined'
                    && categoryId == $auctionDetails.find('input[x-name="category_id"]').val())
            ) {
                return;
            }

            var selects = $(this).closest('.modal-content').find('.allegro-category-select');
            var selects_l = selects.length;

            for (var i = 1; i < selects_l; i++) {
                selects.eq(i).remove();
            }

            var $button = $(this).closest('.modal-content').find('.modal-footer button')
            $button.attr('disabled', 'disabled');

            changeCategory(categoryId, 1, selects.eq(0), $auctionDetails, function() {
                $button.removeAttr('disabled');
            });
        });

        $(document).on('keydown', 'input[x-name="product_category_input"]', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('focusout');
                return false;
            }
        });

        $(document).on('click', '.xproductization-category-last-node, .xproductization-category-assoc', function() {
            $(this).parents('td').find('a[x-name=product_category]').trigger('click');
        });

        $(document).on('chosen:updated', 'div[x-name=product_category_fields] .xproductization-parameters-wrapper select', function () {
            if ($(this).attr('readonly')) {
                var wasDisabled = $(this).is(':disabled');

                $(this).attr('disabled', 'disabled');
                $(this).data('chosen').search_field_disabled();

                if (wasDisabled) {
                    $(this).attr('disabled', 'disabled');
                } else {
                    $(this).removeAttr('disabled');
                }
            }
        });

        $(document).on('click', '.xproductization-parameters-empty-required', function() {
            $(this).parents('td').find('a[x-name=product_category_fields]').trigger('click');
        });

        $(document).on('hide.bs.modal', 'div[x-name="product_category_fields"]', function() {
            checkRequiredEmptyParameters($(this));
        });

        $(document).on('change', 'div[x-name="product_category_fields"] select.has-ambiguous-value', function() {
            var ambiguousId = $(this).data('id');
            var $ambiguousInput = $(this).closest('.form-wrapper').find('input#' + ambiguousId + '_ambiguous_field');
            var $ambiguousFormGroup = $ambiguousInput.closest('.form-group');

            if ($(this).data('ambiguous-value') == $(this).val()) {
                $ambiguousInput.prop('disabled', false);
                $ambiguousFormGroup.removeClass('hide');
            } else {
                $ambiguousInput.prop('disabled', true);
                $ambiguousFormGroup.addClass('hide');
            }
        });

        $(document).on('change', 'div[x-name="product_category_fields"] select', function() {
            var $formWrapper = $(this).closest('.form-wrapper');
            var auctionIndex = parseInt($(this).closest('tr[x-name="product"]').attr('x-id'));
            var changedParameterId = $(this).data('id');
            var currentValue = $(this).val();

            if (changedParameterId in parametersDepending[auctionIndex]) {
                for (var dependingParameterId in parametersDepending[auctionIndex][changedParameterId]) {
                    var $dependingInput = $formWrapper.find('input#' + dependingParameterId);
                    var $dependingInputFormGroup = $dependingInput.closest('.form-group');
                    var $dependingSelect = $formWrapper.find('select#' + dependingParameterId);
                    var $dependingSelectFormGroup = $dependingSelect.closest('.form-group');

                    var parametersWithValue = parametersDepending[auctionIndex][changedParameterId][dependingParameterId].parametersWithValue;
                    if (!parametersWithValue.length) {
                        continue;
                    }

                    for (var key in parametersWithValue) {
                        if (parametersWithValue[key].id != changedParameterId) {
                            continue;
                        }

                        if (parametersWithValue[key].oneOfValueIds.includes(currentValue)) {
                            if ($dependingInput.length) {
                                $dependingInput.prop('disabled', false);
                                $dependingInputFormGroup.removeClass('hide');
                            }
                            else if ($dependingSelect.length) {
                                $dependingSelect.prop('disabled', false).trigger('chosen:updated');
                                $dependingSelectFormGroup.removeClass('hide');
                            }
                        } else {
                            if ($dependingInput.length) {
                                $dependingInput.prop('disabled', true)
                                $dependingInputFormGroup.addClass('hide');
                            }
                            else if ($dependingSelect.length) {
                                $dependingSelect.prop('disabled', true).trigger('chosen:updated');
                                $dependingSelectFormGroup.addClass('hide');
                            }
                        }
                    }
                }
            }

            checkRequiredParameters($formWrapper);
        });

        $(document).on('change', 'div[x-name="product_category_fields"] input[type="checkbox"]', function() {
            var $formWrapper = $(this).closest('.form-wrapper');
            var $formGroup = $(this).closest('.form-group');
            var auctionIndex = parseInt($(this).closest('tr[x-name="product"]').attr('x-id'));
            var changedParameterId = $(this).data('id');

            if (changedParameterId in parametersDepending[auctionIndex]) {
                for (var dependingParameterId in parametersDepending[auctionIndex][changedParameterId]) {
                    var selectedIds = [];

                    $formGroup.find('input.category-field[type="checkbox"]:checked').each(function(index, element) {
                        selectedIds.push($(element).data('value'));
                    });

                    var $dependingInput = $formWrapper.find('input#' + dependingParameterId);
                    var $dependingInputFormGroup = $dependingInput.closest('.form-group');
                    var $dependingSelect = $formWrapper.find('select#' + dependingParameterId);
                    var $dependingSelectFormGroup = $dependingSelect.closest('.form-group');

                    var includes = [];
                    var parametersWithValue = parametersDepending[auctionIndex][changedParameterId][dependingParameterId].parametersWithValue;

                    if (parametersWithValue.length) {
                        for (var key in parametersWithValue) {
                            if (parametersWithValue[key].id != changedParameterId) {
                                continue;
                            }

                            includes = parametersWithValue[key].oneOfValueIds.filter(function (id) {
                                return (selectedIds.indexOf(id) !== -1);
                            });
                        }
                    }

                    if (includes.length) {
                        if ($dependingInput.length) {
                            $dependingInput.prop('disabled', false);
                            $dependingInputFormGroup.removeClass('hide');
                        }
                        else if ($dependingSelect.length) {
                            $dependingSelect.prop('disabled', false).trigger('chosen:updated');
                            $dependingSelectFormGroup.removeClass('hide');
                        }
                    } else {
                        if ($dependingInput.length) {
                            $dependingInput.prop('disabled', true)
                            $dependingInputFormGroup.addClass('hide');
                        }
                        else if ($dependingSelect.length) {
                            $dependingSelect.prop('disabled', true).trigger('chosen:updated');
                            $dependingSelectFormGroup.addClass('hide');
                        }
                    }
                }
            }
        });

        $(document).on(
            'click',
            'div.xproductization-describes-product input.xproductization-readonly, ' +
            'div.xproductization-describes-product .chosen-disabled',
            function() {
                if (window.confirm("Edytujesz dane z katalogu Allegro!\nCzy jesteś pewien?")) {
                    if ($(this).is('input')) {
                        if ($(this).is(':checkbox')) {
                            $(this).closest('div.form-group').find('.checkbox input:checkbox').each(function (index, el) {
                                $(el).removeAttr('readonly').removeAttr('onclick').removeAttr('onkeydown').removeClass('xproductization-readonly');
                            });
                        } else if ($(this).hasClass('multiple-values')) {
                            $(this).closest('div').find('input.xproductization-readonly').removeAttr('readonly').removeClass('xproductization-readonly');
                        } else {
                            $(this).removeAttr('readonly').removeClass('xproductization-readonly');
                        }
                    } else {
                        $(this).prev().removeAttr('readonly').removeClass('xproductization-readonly').trigger('chosen:updated');
                    }
                }
            }
        );

        $(document).on('click', '.x13gpsr-info-allegro-close', function(e) {
            e.preventDefault();
            $(this).closest('#allegro_products').find('.x13gpsr-info-allegro').remove();

            self.ajaxPOST({
                action: 'x13GPSRInfoHide'
            });
        });

        $(document).on('click', '.xproductization-gpsr-empty-required, .xproductization-gpsr-excluded', function() {
            $(this).parents('td').find('a[x-name=product_gpsr]').trigger('click');
        });

        $(document).on('hide.bs.modal', 'div[x-name="product_gpsr"]', function() {
            checkRequiredEmptyGPSR($(this));
        });

        $(document).on('change', '[x-name="marketed_before_gpsr_obligation"]', function () {
            var $requiredLabels = $(this).closest('[x-name="product_gpsr"]').find('label.xproductization-gpsr-required');
            var $requiredLabelsOptional = $requiredLabels.find('.xproductization-gpsr-optional');

            if (parseInt($(this).val()) === 1) {
                $requiredLabels.removeClass('required');
                $requiredLabelsOptional.show();
            }
            else {
                $requiredLabels.addClass('required');
                $requiredLabelsOptional.hide();
            }
        });

        $(document).on('change', '[x-name="safety_information_type"], [x-name="bulk_safety_information_type"]', function () {
            var $formGroup = $(this).closest('.form-group');
            var $textWrapper = $formGroup.find('.gpsr-safety-information-text-wrapper');
            var $attachmentWrapper = $formGroup.find('.gpsr-safety-information-attachment-wrapper');
            var val = $(this).val();

            if (val === 'ATTACHMENTS') {
                $textWrapper.hide();
                $attachmentWrapper.show();
            }
            else if (val === 'TEXT') {
                $attachmentWrapper.hide();
                $textWrapper.show();
            }
            else {
                $textWrapper.hide();
                $attachmentWrapper.hide();
            }
        });

        $(document).on('input', '[x-name="safety_information_text"], [x-name="bulk_safety_information_text"]', function () {
            var $counter = $(this).parent().find('.counter-wrapper');
            var count = $(this).val().length;

            if (count > parseInt($counter.data('max'))) {
                $counter.find('.counter-error').show();
            } else {
                $counter.find('.counter-error').hide();
            }

            $counter.find('.count').text(count);
        });

        $(document).on('change', '[x-name="safety_information_attachment_product"]', function () {
            // @todo
            //checkMaxAttachments();
        });

        $(document).on('click', '[x-name="safety_information_attachment_add"], [x-name="bulk_safety_information_attachment_add"]', function (e) {
            e.preventDefault();
            $(this).parent().find('input[type="file"]').trigger('click');
        });

        $(document).on('change', '[x-name="safety_information_attachment_file"]', function () {
            uploadAttachment($(this), $(this).closest('[x-name="product"]').attr('x-id'));
        });

        $(document).on('change', '[x-name="bulk_safety_information_attachment_file"]', function () {
            uploadAttachment($(this));
        });

        $(document).on('click', '.gpsr-safety-information-attachment-delete', function (e) {
            e.preventDefault();

            $(this).closest('tr').remove();

            // @todo
            //checkMaxAttachments();
        });

        function checkMaxAttachments($wrapper) {
            // @todo
            /*var selectedAttachments = 0;
            selectedAttachments += $wrapper.find('tr[data-type="attachment_offer"]').length;
            selectedAttachments += $wrapper.find('input[x-name="safety_information_attachment_product"]:checked').length;

            var $productAttachmentsInputs = $wrapper.find('input[x-name="safety_information_attachment_product"]:not(:checked)');
            var $buttonAttachmentAdd = $wrapper.find('[x-name="safety_information_attachment_add"]');
            var disabled = selectedAttachments >= parseInt($wrapper.data('max'));

            $productAttachmentsInputs.prop('disabled', disabled);
            $buttonAttachmentAdd.prop('disabled', disabled);*/
        }

        function checkRequiredEmptyGPSR($container) {
            if (parseInt($container.closest('[x-name="product"]').find('[x-name="category_gpsr"]').val()) === 0) {
                return;
            }

            var isEmptyRequired = false;
            var marketedBeforeGPSRObligation = parseInt($container.find('[x-name="marketed_before_gpsr_obligation"]:checked').val());
            var safetyInformationType = $container.find('[x-name="safety_information_type"]').val();

            if (!marketedBeforeGPSRObligation) {
                if (!$container.find('[x-name="responsible_producer"]').val() || !safetyInformationType) {
                    isEmptyRequired = true;
                }

                if (safetyInformationType === 'TEXT' && !$container.find('[x-name="safety_information_text"]').val()) {
                    isEmptyRequired = true;
                }
                else if (safetyInformationType === 'ATTACHMENTS') {
                    var selectedAttachments = 0;
                    selectedAttachments += $container.find('tr[data-type="attachment_offer"]').length;
                    selectedAttachments += $container.find('input[x-name="safety_information_attachment_product"]:checked').length;

                    if (!selectedAttachments) {
                        isEmptyRequired = true;
                    }
                }
            }

            if (isEmptyRequired) {
                $container.parent().find('.xproductization-gpsr-empty-required').show();
            } else {
                $container.parent().find('.xproductization-gpsr-empty-required').hide();
            }
        }

        $(document).on('click', '[x-name=bulk_change_category]', function (e) {
            e.preventDefault();

            var $checkedItems = $('[x-name="product_switch"]:checked');
            if (!$checkedItems.length) {
                self._modalAlert($('#bulk_change_category_modal_alert_empty'));
                return;
            }

            var selects = $modalBulkCategories.find('.allegro-category-select');
            var selects_l = selects.length;
            for (var i = 1; i < selects_l; i++) {
                selects.eq(i).remove();
            }

            selects.eq(0).find('select').val(0).trigger('chosen:updated');
            $modalBulkCategories.find('[x-name="product_category_input"]').val('');
            $modalBulkCategories.find('[x-name="product_category_input_current"]').val('');
            $modalBulkCategories.find('[x-name="product_category_input_is_leaf"]').val('0');
            $modalBulkCategories.find('.bulk-modal-product-radio').eq(0).prop('checked', true).trigger('change');
            $modalBulkCategories.find('.xproductization-bulk-modal-product-table tbody').empty();

            $checkedItems.each(function(index, element) {
                var itemIndex = $(element).parents('tr').attr('data-index');
                var $itemDetails = $('tr[x-name="product"][x-id="' + itemIndex + '"]');
                var productName = $itemDetails.find('input[x-name="product_name"]').val();
                var productAttributeName = $itemDetails.find('input[x-name="attribute_name"]').val();

                var $tr = $('<tr></tr>');
                $tr.append('<td><input type="checkbox" id="bulk_product_' + itemIndex + '" value="' + itemIndex + '"></td>');
                $tr.append('<td><label for="bulk_product_' + itemIndex + '">' + productName + (productAttributeName != '' ? ' - ' + productAttributeName : '') + '</label></td>');

                $modalBulkCategories.find('.xproductization-bulk-modal-product-table tbody').append($tr);
            });

            $modalBulkCategories.find('.allegro-category-select select').chosen({width: '250px'});
            $modalBulkCategories.modal('show');
        });

        $modalBulkCategories.on('click', '.bulk-category-submit', function(e) {
            e.preventDefault();

            if ($modalBulkCategories.find('[x-name="product_category_input_is_leaf"]').val() == '0') {
                self._modalAlert($('#bulk_change_category_modal_alert_leaf'));
                return;
            }

            var selectedCategory = $modalBulkCategories.find('[x-name="product_category_input_current"]').val();
            var productSelection = $modalBulkCategories.find('.bulk-modal-product-radio:checked').val();
            var products = [];

            $modalBulkCategories.find('.xproductization-bulk-modal-product-table tbody > tr').each(function(index, element) {
                var $input = $(element).find('td > input');
                if ((productSelection == 'chosen' && $input.prop('checked')) || productSelection == 'all') {
                    products.push($input.val());
                }
            });

            if (!products.length) {
                self._modalAlert($('#bulk_change_category_modal_alert_empty'));
                return;
            }

            for (var i = 0; i < products.length; i++) {
                $(document).find('tr[x-name="product"][x-id="' + products[i] + '"] input[x-name="product_category_input"]').val(selectedCategory).trigger('focusout');
            }

            $modalBulkCategories.modal('hide');
        });

        $(document).on('click', '[x-name=bulk_category_parameters]', function (e) {
            e.preventDefault();

            var $checkedItems = $('[x-name="product_switch"]:checked');
            if (!$checkedItems.length) {
                self._modalAlert($('#bulk_parameters_modal_alert_empty'));
                return;
            }

            var categories = {};

            $checkedItems.each(function(index, element) {
                var itemIndex = $(element).parents('tr').attr('data-index');
                var $itemDetails = $('tr[x-name="product"][x-id="' + itemIndex + '"]');

                if ($itemDetails.find('input[x-name="category_is_leaf"]').val() == '0') {
                    return;
                }

                var product = {};
                var categoryId = $itemDetails.find('input[x-name="category_id"]').val();

                product['index'] = itemIndex;
                product['productName'] = $itemDetails.find('input[x-name="product_name"]').val();
                product['productAttributeName'] = $itemDetails.find('input[x-name="attribute_name"]').val();

                if (typeof categories[categoryId] === 'undefined') {
                    categories[categoryId] = [];
                }

                categories[categoryId].push(product);
            });

            if (!Object.keys(categories).length) {
                self._modalAlert($('#bulk_parameters_modal_alert_leaf'));
                return;
            }

            self.ajaxPOST({
                action: 'getCategoriesParameters',
                categoryList: JSON.stringify(categories)
            },
            function() {
                var cover = $('<div id="allegro_cover"></div>');
                cover.appendTo('body').hide().fadeIn(400);
            },
            function(json) {
                $(document).find('body #allegro_cover').remove();

                if (json.success) {
                    $modalBulkCategoryParameters.find('.modal-body').html(json.modalContent);

                    // fix default input names
                    $modalBulkCategoryParameters.find('#itemTabBulkParametersContent .tab-pane').each(function(index, element) {
                        var html = $(element).html();
                        html = html.replace(/category_fields/g, 'category_fields[' + $(element).data('category') + ']');
                        html = html.replace(/category_ambiguous_fields/g, 'category_ambiguous_fields[' + $(element).data('category') + ']');
                        $(element).html(html);
                    });

                    $modalBulkCategoryParameters.find('[data-toggle="tooltip"]').tooltip();
                    $modalBulkCategoryParameters.find('select').chosen({width: '250px'});
                    $modalBulkCategoryParameters.modal('show');
                } else {
                    showErrorMessage(json.message);
                }
            });
        });

        $modalBulkCategoryParameters.on('click', '.bulk-parameters-submit', function(e) {
            e.preventDefault();

            var categories = {};

            $modalBulkCategoryParameters.find('#itemTabBulkParametersContent .tab-pane').each(function(index, tab) {
                var categoryId = $(tab).data('category');
                var productSelection = $(tab).find('.bulk-modal-product-radio:checked').val();

                if (!(categoryId in categories)) {
                    categories[categoryId] = {
                        overrideMode: $(tab).find('.bulk-parameters-override').prop('checked'),
                        products: [],
                        parameters: {}
                    };
                }

                $(tab).find('.xproductization-bulk-modal-product-table tbody > tr').each(function(index, tr) {
                    var $input = $(tr).find('td > input');
                    if ((productSelection == 'chosen' && $input.prop('checked')) || productSelection == 'all') {
                        categories[categoryId].products.push($input.val());
                    }
                });

                $(tab).find('.form-group').each(function(index, formGroup) {
                    if ($(formGroup).hasClass('category-field-checkbox')) {
                        $(formGroup).find('input[type="checkbox"].category-field').each(function(index, checkbox) {
                            if ($(checkbox).prop('checked')) {
                                var parameterId = $(checkbox).data('id');
                                if (!(parameterId in categories[categoryId].parameters)) {
                                    categories[categoryId].parameters[parameterId] = [];
                                }
                                categories[categoryId].parameters[parameterId].push($(checkbox).data('value'));
                            }
                        });
                    } else {
                        $(formGroup).find('.category-field').each(function(index, element) {
                            if ($(element).val() != '') {
                                if ($(element).parent().hasClass('multiple-values-group')) {
                                    var parameterId = $(element).closest('.multiple-values-list').data('id');
                                    if (!(parameterId in categories[categoryId].parameters)) {
                                        categories[categoryId].parameters[parameterId] = [];
                                    }
                                    categories[categoryId].parameters[parameterId].push($(element).val());
                                } else {
                                    categories[categoryId].parameters[$(element).attr('id')] = $(element).val();
                                }
                            }
                        });
                    }
                });
            });

            for (var categoryId in categories) {
                if (!Object.keys(categories[categoryId].parameters).length) {
                    continue;
                }

                if (!categories[categoryId].products.length) {
                    continue;
                }

                for (var i = 0; i < categories[categoryId].products.length; i++) {
                    var $productParameters = $(document).find('tr[x-name="product"][x-id="' + categories[categoryId].products[i] + '"] div[x-name="product_category_fields"]');

                    $($productParameters).find('.form-group').each(function(index, formGroup) {
                        if ($(formGroup).hasClass('category-field-checkbox')) {
                            var checked = $(formGroup).find('input[type="checkbox"].category-field:checked');
                            var checkboxParameterId = $(formGroup).find('.x13allegro-list-checkbox').data('id');

                            if (!$(formGroup).hasClass('xproductization-value')
                                && checkboxParameterId in categories[categoryId].parameters
                                && (!checked.length || categories[categoryId].overrideMode)
                            ) {
                                checked.prop('checked', false);

                                for (var valueIdKey in categories[categoryId].parameters[checkboxParameterId]) {
                                    $(formGroup).find('input[type="checkbox"][data-value="' + categories[categoryId].parameters[checkboxParameterId][valueIdKey] + '"].category-field').prop('checked', true);
                                }
                            }
                        } else if ($(formGroup).hasClass('is-multiple-value')) {
                            var notEmpty = $(formGroup).find('input[type="text"].category-field').filter(function() { return $(this).val() != ''; });
                            var parameterId = $(formGroup).find('.multiple-values-list').data('id');

                            if (!$(formGroup).hasClass('xproductization-value')
                                && parameterId in categories[categoryId].parameters
                                && (!notEmpty.length || categories[categoryId].overrideMode)
                            ) {
                                $(formGroup).find('input[type="text"].category-field').val('').trigger('input');
                                $(formGroup).find('.multiple-values-group:not(:first-child)').addClass('hide');
                                $(formGroup).find('.multiple-values-group:not(:first-child) .category-field-text-counter').addClass('hide');

                                for (var valueKey in categories[categoryId].parameters[parameterId]) {
                                    var $thisInput = $(formGroup).find('input[type="text"][id="' + parameterId + '_' + valueKey + '"].category-field');
                                    $thisInput.val(categories[categoryId].parameters[parameterId][valueKey]).trigger('input');
                                    $thisInput.closest('.multiple-values-group').find('.category-field-text-counter').removeClass('hide');
                                    $thisInput.closest('.multiple-values-group').removeClass('hide');
                                }
                            }
                        } else {
                            $(formGroup).find('.category-field').each(function(index, element) {
                                var parameterId = $(element).attr('id');

                                if (!$(element).hasClass('xproductization-value')
                                    && parameterId in categories[categoryId].parameters
                                    && ($(element).val() == '' || categories[categoryId].overrideMode)
                                ) {
                                    $(element).val(categories[categoryId].parameters[parameterId])

                                    if ($(element).prop('nodeName') == 'SELECT') {
                                        $(element).trigger('chosen:updated');
                                    } else {
                                        $(element).trigger('input');
                                    }
                                }
                            });
                        }
                    });

                    // reload multiple values, and check required
                    self.categoryParametersForm($productParameters, true);
                    checkRequiredEmptyParameters($productParameters);

                    $productParameters.find('select').each(function () {
                        // check if we need to show/hide ambiguous input
                        // check if we need to show/hide depending on parameter
                        $(this).trigger('change');
                    });
                    $productParameters.find('input[type="checkbox"]').each(function () {
                        // check if we need to show/hide depending on parameter
                        $(this).trigger('change');
                    });
                }
            }

            $modalBulkCategoryParameters.modal('hide');
        });

        $(document).on('click', '[x-name="bulk_product_gpsr"]', function (e) {
            e.preventDefault();

            var $checkedItems = $('[x-name="product_switch"]:checked');
            if (!$checkedItems.length) {
                self._modalAlert($('#bulk_product_gpsr_modal_alert_empty'));
                return;
            }

            $modalBulkProductGPSR.find('[x-name="bulk_marketed_before_gpsr_obligation"]').val('').trigger('change');
            $modalBulkProductGPSR.find('[x-name="bulk_responsible_producer"]').val('').trigger('chosen:updated');
            $modalBulkProductGPSR.find('[x-name="bulk_responsible_person"]').val('').trigger('chosen:updated');
            $modalBulkProductGPSR.find('[x-name="bulk_safety_information_type"]').val('').trigger('change');
            $modalBulkProductGPSR.find('[x-name="bulk_safety_information_text"]').val('').trigger('input');
            $modalBulkProductGPSR.find('.gpsr-safety-information-attachment-table > tbody').empty();
            $modalBulkProductGPSR.find('[x-name="bulk_product_gpsr_override"]').prop('checked', false);
            $modalBulkProductGPSR.find('.bulk-modal-product-radio').eq(0).prop('checked', true).trigger('change');
            $modalBulkProductGPSR.find('.xproductization-bulk-modal-product-table tbody').empty();

            $checkedItems.each(function(index, element) {
                var itemIndex = $(element).parents('tr').attr('data-index');
                var $itemDetails = $('tr[x-name="product"][x-id="' + itemIndex + '"]');

                if ($itemDetails.find('input[x-name="category_is_leaf"]').val() == '0' || $itemDetails.find('input[x-name="category_gpsr"]').val() == '0') {
                    return;
                }

                var productName = $itemDetails.find('input[x-name="product_name"]').val();
                var productAttributeName = $itemDetails.find('input[x-name="attribute_name"]').val();
                var $tr = $('<tr></tr>');
                $tr.append('<td><input type="checkbox" id="bulk_product_' + itemIndex + '" value="' + itemIndex + '"></td>');
                $tr.append('<td><label for="bulk_product_' + itemIndex + '">' + productName + (productAttributeName != '' ? ' - ' + productAttributeName : '') + '</label></td>');

                $modalBulkProductGPSR.find('.xproductization-bulk-modal-product-table tbody').append($tr);
            });

            if (!$modalBulkProductGPSR.find('.xproductization-bulk-modal-product-table tbody tr').length) {
                self._modalAlert($('#bulk_product_gpsr_modal_alert_leaf'));
                return;
            }

            $modalBulkProductGPSR.modal('show');
        });

        $modalBulkProductGPSR.on('click', '.bulk-product-gpsr-submit', function(e) {
            e.preventDefault();

            var productSelection = $modalBulkProductGPSR.find('.bulk-modal-product-radio:checked').val();
            var products = [];

            $modalBulkProductGPSR.find('.xproductization-bulk-modal-product-table tbody > tr').each(function(index, element) {
                var $input = $(element).find('td > input');
                if ((productSelection == 'chosen' && $input.prop('checked')) || productSelection == 'all') {
                    products.push($input.val());
                }
            });

            if (!products.length) {
                self._modalAlert($('#bulk_product_gpsr_modal_alert_empty'));
                return;
            }

            var overrideMode = $modalBulkProductGPSR.find('[x-name="bulk_product_gpsr_override"]').prop('checked');
            var marketedBeforeGPSR = $modalBulkProductGPSR.find('[x-name="bulk_marketed_before_gpsr_obligation"]').val();
            var responsibleProducer = $modalBulkProductGPSR.find('[x-name="bulk_responsible_producer"]').val();
            var responsiblePerson = $modalBulkProductGPSR.find('[x-name="bulk_responsible_person"]').val();
            var safetyInformationType = $modalBulkProductGPSR.find('[x-name="bulk_safety_information_type"]').val();
            var safetyInformationText = $modalBulkProductGPSR.find('[x-name="bulk_safety_information_text"]').val();
            var $safetyInformationAttachmentTable = $modalBulkProductGPSR.find('.gpsr-safety-information-attachment-table > tbody');

            for (var i = 0; i < products.length; i++) {
                var $itemDetails = $(document).find('tr[x-name="product"][x-id="' + products[i] + '"]');

                if (marketedBeforeGPSR !== '') {
                    $itemDetails.find('[x-name="marketed_before_gpsr_obligation"][value="' + marketedBeforeGPSR + '"]').prop('checked', true).trigger('change');
                }

                var $itemResponsibleProducer = $itemDetails.find('[x-name="responsible_producer"]');
                if ($itemResponsibleProducer.val() == '' || overrideMode) {
                    $itemResponsibleProducer.val(responsibleProducer).trigger('chosen:updated');
                }

                var $itemResponsiblePerson = $itemDetails.find('[x-name="responsible_person"]');
                if ($itemResponsiblePerson.val() == '' || overrideMode) {
                    $itemResponsiblePerson.val(responsiblePerson).trigger('chosen:updated');
                }

                var $itemSafetyInformationType = $itemDetails.find('[x-name="safety_information_type"]');
                if ($itemSafetyInformationType.val() == '' || overrideMode) {
                    $itemSafetyInformationType.val(safetyInformationType).trigger('change');

                    if (safetyInformationType === 'ATTACHMENTS' && $safetyInformationAttachmentTable.length) {
                        var $itemDetailsAttachmentTable = $itemDetails.find('.gpsr-safety-information-attachment-table > tbody');
                        $itemDetailsAttachmentTable.find('tr[data-type="attachment_offer"]').remove();

                        var attachmentsRows = $safetyInformationAttachmentTable.html();
                        attachmentsRows = attachmentsRows.replace(/_INDEX_/g, $itemDetails.attr('x-id'));
                        $itemDetailsAttachmentTable.append(attachmentsRows);
                    }
                    else if (safetyInformationType === 'TEXT') {
                        $itemDetails.find('[x-name="safety_information_text"]').val(safetyInformationText).trigger('input');
                    }
                }

                // @todo
                //checkMaxAttachments();

                checkRequiredEmptyGPSR($itemDetails);
            }

            $modalBulkProductGPSR.modal('hide');
        });

        $(document).on('change', '.bulk-modal-product-radio', function() {
            if ($(this).val() == 'all') {
                $(this).closest('.xproductization-bulk-product').find('.xproductization-bulk-modal-product-table').hide();
            } else {
                $(this).closest('.xproductization-bulk-product').find('.xproductization-bulk-modal-product-table').show();
            }
        });

        $(document).on('click', '.bulk-modal-product-table-select', function(e) {
            e.preventDefault();

            var selectAll = parseInt($(this).data('select'));
            $(this).closest('.xproductization-bulk-modal-product-table').find('tbody > tr').each(function(index, element) {
                $(element).find('td > input').prop('checked', !!selectAll);
            });
        });

        function checkRequiredParameters($container)
        {
            var auctionIndex = parseInt($container.closest('tr[x-name="product"]').attr('x-id'));

            for (var requiredParameterId in parametersRequiredIf[auctionIndex]) {
                var $requiredInputFormGroup = $container.find('input#' + requiredParameterId).closest('.form-group');
                var $requiredInputLabel = $requiredInputFormGroup.find('label');
                var $requiredSelectFormGroup = $container.find('select#' + requiredParameterId).closest('.form-group');
                var $requiredSelectLabel = $requiredSelectFormGroup.find('label');
                var isRequired = true;

                var parametersWithValue = parametersRequiredIf[auctionIndex][requiredParameterId].parametersWithValue;
                if (!parametersWithValue.length) {
                    continue;
                }

                parametersWithValue.forEach(function(parameter) {
                    var $parameterSelect = $container.find('select#' + parameter.id);
                    if ($parameterSelect.length) {
                        isRequired &= parameter.oneOfValueIds.includes($parameterSelect.val());
                    }
                });

                if (isRequired) {
                    if ($requiredInputFormGroup.length) {
                        $requiredInputFormGroup.addClass('required-parameter');
                        $requiredInputLabel.addClass('required');
                    }
                    else if ($requiredSelectFormGroup.length) {
                        $requiredSelectFormGroup.addClass('required-parameter');
                        $requiredSelectLabel.addClass('required');
                    }
                } else {
                    if ($requiredInputFormGroup.length) {
                        $requiredInputFormGroup.removeClass('required-parameter');
                        $requiredInputLabel.removeClass('required');
                    }
                    else if ($requiredSelectFormGroup.length) {
                        $requiredSelectFormGroup.removeClass('required-parameter');
                        $requiredSelectLabel.removeClass('required');
                    }
                }
            }
        }

        function checkRequiredEmptyParameters($container)
        {
            var isEmptyRequired = false;

            $container.find('[name*="[category_fields]"]:disabled').removeAttr('disabled').attr('readonly', true);

            $container.find('.form-group.required-parameter').each(function(i, el) {
                if ($(el).find('select').length) {
                    if (!$(el).find('select').val()) {
                        isEmptyRequired = true;
                    }
                } else if ($(el).find('.checkbox').length) {
                    var checked = false;
                    $(el).find('.checkbox').each(function(iCheck, elCheck) {
                        if ($(elCheck).find('input').prop('checked')) {
                            checked = true;
                        }
                    });
                    if (!checked) {
                        isEmptyRequired = true;
                    }
                } else if (!$(el).find('input').val()) {
                    isEmptyRequired = true;
                }
            });

            if (isEmptyRequired) {
                $container.parent().find('.xproductization-parameters-empty-required').show();
            } else {
                $container.parent().find('.xproductization-parameters-empty-required').hide();
            }
        }
    },

    categoryForm: function()
    {
        var self = this;

        var $fieldsetCategories = $('#fieldset_0_1');
        var $fieldsetParameters = $('#fieldset_2_3');
        var $modalForm = $('#xallegro_parameter_map_form_modal');

        var isProcessingCategoryFieldsValues = false;

        var parameterMapId = 0;
        var parameterMapRowInProgress = false;

        var parameterDictionary;
        var parameterMapRules;
        var parameterRangeMapRules;
        var parameterAmbiguousMapRules;
        var parameterAmbiguousValueId;
        var searchCollection;

        $fieldsetCategories.find('select').chosen({width: '200px'});
        this.categoryParametersForm($fieldsetParameters);
        this.categoryParametersForm($modalForm);

        if ($fieldsetParameters.find('.category-field-group').length) {
            $fieldsetParameters.find('#category_parameters_info').show();
        }

        $(document).on('click', '#copy_parameters_link', function(e) {
            e.preventDefault();
            $('.copy-parameters-content').toggle();
        });

        $fieldsetCategories.on('change', 'select', function() {
            var categoryId = $(this).prop('value');
            if (categoryId == 0) {
                return;
            }

            var selects = $fieldsetCategories.find('.allegro-category-select');
            var selects_l = selects.length;
            var index = selects.index($(this).closest('.allegro-category-select'));

            for (var i = index + 1; i < selects_l; i++) {
                selects.eq(i).remove();
            }

            self.changeCategory(categoryId, 0, $(this).closest('.allegro-category-select'), $fieldsetParameters);
        });

        $fieldsetCategories.on('focusout', '#allegro_category_input', function() {
            var categoryId = $(this).prop('value');
            if (categoryId == $('#allegro_category_current').val() || categoryId == 0) {
                return;
            }

            var selects = $fieldsetCategories.find('.allegro-category-select');
            var selects_l = selects.length;

            for (var i = 1; i < selects_l; i++) {
                selects.eq(i).remove();
            }

            self.changeCategory(categoryId, 1, selects.eq(0), $fieldsetParameters);
        });

        $fieldsetCategories.on('keydown', '#allegro_category_input', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('focusout');
                return false;
            }
        });

        $fieldsetParameters.find('select.category-field').each(function() {
            $(this).chosen({width: '250px'});
        });

        $fieldsetParameters.on('change', 'input[type="checkbox"].category-field, select.category-field', function() {
            submitCategoryFieldsValues($(this));
        });

        $fieldsetParameters.on('focusin', 'input[type="text"].category-field', function() {
            $(this).data('value', $(this).val());
        });

        $fieldsetParameters.on('focusout', 'input[type="text"].category-field', function() {
            if ($(this).data('value') != $(this).val()) {
                submitCategoryFieldsValues($(this));
            }

            $(this).removeData('value');
        });

        $fieldsetParameters.on('click', '.xallegro-fieldMap', function(e) {
            e.preventDefault();

            var $button = $(this);
            var cover = $('<div id="allegro_cover"></div>');
            cover.appendTo('body').hide().fadeIn(400);

            var processingCategoryFieldsValues = setInterval(function() {
                if (!isProcessingCategoryFieldsValues) {
                    clearInterval(processingCategoryFieldsValues);
                    getParameterMapForm($button);
                }
            }, 500);

            function getParameterMapForm($button)
            {
                parameterMapId = (parseInt($button.data('id')) || 0);

                var formData = new FormData();
                formData.append('ajax', 1);
                formData.append('token', self.ajaxToken);
                formData.append('action', 'getParameterMapForm');
                formData.append('id_xallegro_category', (parseInt($('#id_xallegro_category').val()) || 0));
                formData.append('parameterId', parameterMapId);

                $.ajax({
                    url: self.ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: formData,
                    contentType: false,
                    processData: false,
                    cache: false,
                    success: function(json) {
                        $(document).find('body #allegro_cover').remove();

                        if (json.success) {
                            parameterDictionary = json.parameterDictionary;
                            parameterMapRules = json.parameterMapRules;
                            parameterRangeMapRules = json.parameterRangeMapRules;
                            parameterAmbiguousMapRules = json.parameterAmbiguousMapRules;
                            parameterAmbiguousValueId = json.parameterAmbiguousValueId;
                            searchCollection = json.searchCollection;

                            $modalForm.html(json.html);
                            $modalForm.find('select.category-field').each(function() {
                                $(this).chosen({width: '250px'});
                            });

                            if (!parameterDictionary) {
                                var sortableHelper = function(e, ui) {
                                    ui.children().each(function() {
                                        $(this).width($(this).width());
                                    });
                                    return ui;
                                };

                                $modalForm.find('.xallegro-parameter-map-table tbody').sortable({
                                    handle: '.xallegro-parameter-sort',
                                    helper: sortableHelper,
                                    start: function (e, ui) {
                                        ui.placeholder.css({
                                            height: ui.item.outerHeight(),
                                            width: ui.item.outerWidth()
                                        });
                                    }
                                });
                            }

                            $modalForm.modal('show');
                            self._cast();
                        } else {
                            alert(json.message);
                        }
                    }
                });
            }
        });

        $modalForm.on('click', '.xallegro-parameter-map-add', function(e) {
            e.preventDefault();

            if (parameterMapRowInProgress) {
                return alertSaveMapping();
            }

            parameterMapRowInProgress = true;
            var $mapRow = $('<tr></tr>');

            if (!parameterDictionary) {
                $mapRow.append($('<td class="xallegro-parameter-sort text-center"></td>').html('<i class="icon-bars"></i>'));
            }

            if (parameterDictionary || parameterRangeMapRules) {
                var $selectValueId;

                if (parameterDictionary) {
                    $selectValueId = createSelectWithDictionary();
                } else {
                    $selectValueId = createSelectWithRangeMapRules();
                }

                $mapRow.append($('<td class="xallegro-parameter-valueId"></td>').html(
                    $selectValueId.prop('outerHTML')
                    + createInputParameterMap('valueId').prop('outerHTML')
                    + '<span></span>'
                ));
            }

            $mapRow.append($('<td class="xallegro-parameter-rule"></td>').html(
                createInputParameterMap('rule').prop('outerHTML')
                + '<span></span>'
            ));

            if (!parameterDictionary && !parameterRangeMapRules) {
                $mapRow.find('.xallegro-parameter-rule').append(createSelectWithMapRules());
            }

            var $inputRuleValue;
            if (parameterRangeMapRules) {
                $inputRuleValue = createInputParameterMap('ruleValue', 'rangeMin').addClass('rangeMin').prop('outerHTML')
                    + createInputParameterMap('ruleValue', 'rangeMax').addClass('rangeMax').prop('outerHTML');
            } else {
                $inputRuleValue = createInputParameterMap('ruleValue').prop('outerHTML');
            }

            $mapRow.append($('<td class="xallegro-parameter-ruleValue"></td>').html(
                $inputRuleValue
                + '<span></span>'
            ));

            var $buttonSave = $('<a title="Zapisz mapowanie"></a>').addClass('btn btn-primary xallegro-parameter-row-save').html('<i class="icon-save"></i>');
            var $buttonQuit = $('<a title="Anuluj mapowanie"></a>').addClass('btn xallegro-parameter-row-quit').html('<i class="icon-remove"></i>');
            var $buttonEdit = $('<a title="Edytuj mapowanie"></a>').addClass('btn xallegro-parameter-row-edit').html('<i class="icon-pencil"></i>').hide();
            var $buttonDelete = $('<a title="Usuń mapowanie"></a>').addClass('btn xallegro-parameter-row-delete').html('<i class="icon-trash"></i>').hide();

            $mapRow.append($('<td class="text-right"></td>').html(
                $buttonSave.prop('outerHTML')
                + $buttonQuit.prop('outerHTML')
                + $buttonEdit.prop('outerHTML')
                + $buttonDelete.prop('outerHTML')
            ));

            $mapRow.find('select.chosenType').chosen({width: '100%'});

            $modalForm.find('.xallegro-parameter-map-table tbody').append($mapRow);
        });

        $modalForm.on('change', '.xallegro-parameter-valueId select', function() {
            var valueId = $(this).val();
            var valueName = (valueId != '0' ? $(this).find('option[value="' + valueId + '"]').text() : '');

            var $cellValueId = $(this).closest('td');
            var $cellRule = $(this).closest('tr').find('.xallegro-parameter-rule');

            $cellValueId.data('value', valueId).data('name', valueName)
            $cellValueId.find('.ambiguous').remove();

            $cellRule
                .data('ambiguous', false)
                .data('range', false)
                .data('value', '0').data('name', '')
                .data('ambiguous-value', '0').data('ambiguous-name', '')
                .data('range-value', '0').data('range-name', '');

            $cellRule.find('select').val('0').trigger('change').chosen('destroy').remove();

            if (valueId == '0') {
                return;
            }

            $cellRule.append(createSelectWithMapRules(valueId));

            if (parameterRangeMapRules) {
                $cellRule.data('range', valueId);
            }

            if (parameterAmbiguousValueId == valueId) {
                $cellValueId.append('<div class="ambiguous">inna wartość</div>');
                $cellRule.data('ambiguous', true)
                    .append(createPlaceholder(undefined, 'wybierz warunek mapowania'));

                if (!$cellRule.find('.input-ambiguous-parameter-map').length) {
                    $cellRule.prepend(createInputAmbiguousParameterMap('rule'));
                }
            }

            $cellRule.find('select.chosenType').chosen({width: '100%'});
        });

        $modalForm.on('change', '.xallegro-parameter-rule select.rule', function() {
            var ruleId = $(this).val();
            var ruleName = (ruleId != '0' ? $(this).find('option[value="' + ruleId + '"]').text() : '');
            var ruleType = $(this).find('option[value="' + ruleId + '"]').data('type');
            var ruleSearchKey = $(this).find('option[value="' + ruleId + '"]').data('search-key');

            var $cellRule = $(this).closest('td');
            var $cellRuleValue = $(this).closest('tr').find('.xallegro-parameter-ruleValue');

            $cellRule.data('value', ruleId).data('name', ruleName);
            $cellRule.find('.ambiguous').chosen('destroy').remove();
            $cellRule.find('.placeholder').remove();

            $cellRuleValue.find('.ruleValue.chosenType').chosen('destroy');
            $cellRuleValue.find('.ruleValue.select2Type').select2('destroy');
            $cellRuleValue.find('.ruleValue').remove();
            $cellRuleValue.find('.ambiguousRuleValue').remove();

            $cellRuleValue
                .data('value', '0').data('name', '')
                .data('ambiguous-value', '0').data('ambiguous-name', '')
                .data('range-value', {}).data('range-name', {});

            if (ruleId == '0') {
                if ($cellRule.data('ambiguous')) {
                    $cellRule.append(createPlaceholder(undefined, 'wybierz warunek mapowania'));
                }
                return;
            }

            if (ruleType === 'search') {
                if ($cellRule.data('range') == 'range_split') {
                    $cellRuleValue.append(createSelect().addClass('ruleValue rangeMin select2Type'))
                        .append(createSelect().addClass('ruleValue rangeMax select2Type'));

                    initializeSelect2($cellRuleValue.find('select.ruleValue.rangeMin'), ruleId, ruleSearchKey);
                    initializeSelect2($cellRuleValue.find('select.ruleValue.rangeMax'), ruleId, ruleSearchKey);
                } else {
                    $cellRuleValue.append(createSelect().addClass('ruleValue select2Type'));
                    initializeSelect2($cellRuleValue.find('select.ruleValue'), ruleId, ruleSearchKey);
                }
            }
            else if (ruleType === 'choose') {
                if ($cellRule.data('range') == 'range_split') {
                    $cellRuleValue.append(createSelectWithMapRuleCollection(ruleId, 'rangeMin'))
                        .append(createSelectWithMapRuleCollection(ruleId, 'rangeMax'));
                } else {
                    $cellRuleValue.append(createSelectWithMapRuleCollection(ruleId));
                }
            } else {
                $cellRuleValue.append(createPlaceholderRuleValue());
                $cellRuleValue.data('value', ruleId).data('name', '');
            }

            if ($cellRule.data('ambiguous')) {
                $cellRule.append(createSelectWithAmbiguousMapRules(ruleId));
                $cellRuleValue.append(createPlaceholderambiguousRuleValue());

                if (!$cellRuleValue.find('.input-ambiguous-parameter-map').length) {
                    $cellRuleValue.prepend(createInputAmbiguousParameterMap('ruleValue'));
                }
            }

            $cellRule.find('select').chosen({width: '100%'});
            $cellRuleValue.find('select.chosenType').chosen({width: '100%'});
        });

        $modalForm.on('change', '.xallegro-parameter-rule select.ambiguous', function() {
            var ambiguousRuleId = $(this).val();
            var ambiguousRuleName = (ambiguousRuleId != '0' ? $(this).find('option[value="' + ambiguousRuleId + '"]').text() : '');
            var ambiguousRuleType = $(this).find('option[value="' + ambiguousRuleId + '"]').data('type');

            var $cellRule = $(this).closest('td');
            var $cellRuleValue = $(this).closest('tr').find('.xallegro-parameter-ruleValue');

            $cellRule.data('ambiguous-value', ambiguousRuleId).data('ambiguous-name', ambiguousRuleName);

            $cellRuleValue.find('.ambiguousRuleValue').remove();
            $cellRuleValue.data('ambiguous-value', '0').data('ambiguous-name', '');

            if (ambiguousRuleId == '0') {
                if ($cellRule.data('ambiguous')) {
                    $cellRuleValue.append(createPlaceholderambiguousRuleValue());
                }
                return;
            }

            if (ambiguousRuleType === 'text') {
                $cellRuleValue.append(createInputAmbiguousValue());
            } else {
                $cellRuleValue.append(createPlaceholderambiguousRuleValue());
                $cellRuleValue.data('ambiguous-value', ambiguousRuleId).data('ambiguous-name', '');
            }
        });

        $modalForm.on('change', '.xallegro-parameter-ruleValue select.ruleValue', function() {
            var ruleValueId = $(this).val();
            var ruleValueName = (ruleValueId != '0' ? $(this).find('option[value="' + ruleValueId + '"]').text() : '');

            if ($(this).hasClass('rangeMin') || $(this).hasClass('rangeMax')) {
                var dataValue = $(this).closest('td').data('range-value');
                var dataName = $(this).closest('td').data('range-name');
                var rangeData = ($(this).hasClass('rangeMin') ? 'rangeMin' : 'rangeMax');

                dataValue[rangeData] = ruleValueId;
                dataName[rangeData] = ruleValueName;

                $(this).closest('td')
                    .data('range-value', dataValue)
                    .data('range-name', dataName);
            } else {
                $(this).closest('td')
                    .data('value', ruleValueId)
                    .data('name', ruleValueName);
            }
        });

        $modalForm.on('input', '.xallegro-parameter-ruleValue input.ambiguous', function() {
            var ambiguousRuleValueId = $(this).val();

            $(this).closest('td')
                .data('ambiguous-value', (ambiguousRuleValueId != '' ? ambiguousRuleValueId : '0'))
                .data('ambiguous-name', ambiguousRuleValueId);
        });

        $modalForm.on('click', '.xallegro-parameter-row-save', function(e) {
            e.preventDefault();

            if (closeParameterMapping($(this).closest('tr'), true)) {
                calculateMapRows();
                parameterMapRowInProgress = false;
            }
        });

        $modalForm.on('click', '.xallegro-parameter-row-quit', function(e) {
            e.preventDefault();

            closeParameterMapping($(this).closest('tr'), false);
            parameterMapRowInProgress = false;
        });

        $modalForm.on('click', '.xallegro-parameter-row-edit', function(e) {
            e.preventDefault();

            if (parameterMapRowInProgress) {
                return alertSaveMapping();
            }

            var $row = $(this).closest('tr');
            var $cellValueId = $row.find('.xallegro-parameter-valueId');
            var $cellRule = $row.find('.xallegro-parameter-rule');
            var $cellRuleValue = $row.find('.xallegro-parameter-ruleValue');

            if (typeof $cellValueId !== 'undefined') {
                $cellValueId.find('span').hide();

                var $inputValueId = $cellValueId.find('input.input-parameter-map');

                if (parameterRangeMapRules) {
                    $cellValueId.append(createSelectWithRangeMapRules());
                    $cellRule.data('range', $inputValueId.val());
                } else {
                    $cellValueId.append(createSelectWithDictionary());
                }

                $cellValueId.find('select').val($inputValueId.val());
                $cellValueId.data('value', $inputValueId.val()).data('name', $inputValueId.data('name'));

                if (parameterAmbiguousValueId == $inputValueId.val()) {
                    $cellValueId.append('<div class="ambiguous">inna wartość</div>');
                }
            }

            $cellRule.find('span').hide();
            $cellRuleValue.find('span').hide();

            var $inputRule = $cellRule.find('input.input-parameter-map');
            $cellRule.append(createSelectWithMapRules($inputValueId.val()));
            $cellRule.find('select.rule').val($inputRule.val());
            $cellRule.data('value', $inputRule.val()).data('name', $inputRule.data('name'));

            var $inputRuleValue = $cellRuleValue.find('input.input-parameter-map');
            var $inputRuleValueRangeMin = $cellRuleValue.find('input.input-parameter-map.rangeMin');
            var $inputRuleValueRangeMax = $cellRuleValue.find('input.input-parameter-map.rangeMax');
            var ruleType = $cellRule.find('select.rule option[value="' + $inputRule.val() + '"]').data('type');
            var ruleSearchKey = $cellRule.find('select.rule option[value="' + $inputRule.val() + '"]').data('search-key');

            if ($cellRule.data('range') == 'range_split') {
                $cellRuleValue
                    .data('range-value', {
                        rangeMin: $inputRuleValueRangeMin.val(),
                        rangeMax: $inputRuleValueRangeMax.val()
                    })
                    .data('range-name', {
                        rangeMin: $inputRuleValueRangeMin.data('name'),
                        rangeMax: $inputRuleValueRangeMax.data('name')
                    });
            }
            else {
                $cellRuleValue.data('value', $inputRuleValue.val()).data('name', $inputRuleValue.data('name'));
            }

            if (ruleType === 'search') {
                if ($cellRule.data('range') == 'range_split') {
                    $cellRuleValue.append(createSelect().addClass('ruleValue rangeMin select2Type'))
                        .append(createSelect().addClass('ruleValue rangeMax select2Type'));

                    initializeSelect2($cellRuleValue.find('select.ruleValue.rangeMin'), $inputRule.val(), ruleSearchKey);
                    initializeSelect2($cellRuleValue.find('select.ruleValue.rangeMax'), $inputRule.val(), ruleSearchKey);

                    $cellRuleValue.find('select.ruleValue.rangeMin')
                        .append(new Option($inputRuleValueRangeMin.data('name'), $inputRuleValueRangeMin.val(), true, true))
                        .val($inputRuleValueRangeMin.val())
                        .trigger('change');

                    $cellRuleValue.find('select.ruleValue.rangeMax')
                        .append(new Option($inputRuleValueRangeMax.data('name'), $inputRuleValueRangeMax.val(), true, true))
                        .val($inputRuleValueRangeMax.val())
                        .trigger('change');
                } else {
                    $cellRuleValue.append(createSelect().addClass('ruleValue select2Type'));

                    initializeSelect2($cellRuleValue.find('select.ruleValue'), $inputRule.val(), ruleSearchKey);
                    $cellRuleValue.find('select.ruleValue')
                        .append(new Option($inputRuleValue.data('name'), $inputRuleValue.val(), true, true))
                        .val($inputRuleValue.val())
                        .trigger('change');
                }
            }
            else if (ruleType === 'choose') {
                if ($cellRule.data('range') == 'range_split') {
                    $cellRuleValue.append(createSelectWithMapRuleCollection($inputRule.val(), 'rangeMin'))
                        .append(createSelectWithMapRuleCollection($inputRule.val(), 'rangeMax'));

                    $cellRuleValue.find('select.ruleValue.rangeMin').val($inputRuleValueRangeMin.val());
                    $cellRuleValue.find('select.ruleValue.rangeMax').val($inputRuleValueRangeMax.val());
                } else {
                    $cellRuleValue.append(createSelectWithMapRuleCollection($inputRule.val()));
                    $cellRuleValue.find('select.ruleValue').val($inputRuleValue.val());
                }
            } else {
                $cellRuleValue.append(createPlaceholderRuleValue());
            }

            if (parameterAmbiguousValueId == $inputValueId.val()) {
                var $inputAmbiguousRule = $cellRule.find('input.input-ambiguous-parameter-map');
                $cellRule.append(createSelectWithAmbiguousMapRules($inputRule.val()));
                $cellRule.find('select.ambiguousRule').val($inputAmbiguousRule.val());
                $cellRule.data('ambiguous', true).data('ambiguous-value', $inputAmbiguousRule.val()).data('ambiguous-name', $inputAmbiguousRule.data('name'));

                var $inputAmbiguousRuleValue = $cellRuleValue.find('input.input-ambiguous-parameter-map');
                var ambiguousRuleType = $cellRule.find('select.ambiguousRule option[value="' + $inputAmbiguousRule.val() + '"]').data('type');
                $cellRuleValue.data('ambiguous-value', $inputAmbiguousRuleValue.val()).data('ambiguous-name', $inputAmbiguousRuleValue.data('name'));

                if (ambiguousRuleType === 'text') {
                    var $ambiguousInput = createInputAmbiguousValue().val($inputAmbiguousRuleValue.val());
                    $cellRuleValue.append($ambiguousInput);
                } else {
                    $cellRuleValue.append(createPlaceholderambiguousRuleValue());
                }
            }

            $row.find('.xallegro-parameter-row-save').show();
            $row.find('.xallegro-parameter-row-quit').show();
            $row.find('.xallegro-parameter-row-edit').hide();
            $row.find('.xallegro-parameter-row-delete').hide();

            $row.find('select.chosenType').chosen({width: '100%'});

            parameterMapRowInProgress = true;
        });

        $modalForm.on('click', '.xallegro-parameter-row-delete', function(e) {
            e.preventDefault();

            if (parameterMapRowInProgress) {
                return alertSaveMapping();
            }

            $(this).closest('tr').remove();
            calculateMapRows();
        });

        $modalForm.on('click', '.xallegro-parameter-map-close', function(e) {
            e.preventDefault();

            self._modalAlert(
                $modalForm.find('#xallegro_parameter_map_form_modal_alert_close'),
                function() {
                    $modalForm.modal('hide');
                });
        });

        $modalForm.on('click', '.xallegro-parameter-map-submit', function(e) {
            e.preventDefault();

            if (parameterMapRowInProgress) {
                return alertSaveMapping();
            }

            $modalForm.find('.xallegro-parameter-map-table tbody tr').each(function(trIndex, tr) {
                $(tr).find('td').each(function(tdIndex, td) {
                    $(td).find('.input-parameter-map, .input-ambiguous-parameter-map').each(function(inputIndex, input) {
                        var inputName = $(input).attr('name');
                        if (typeof inputName !== 'undefined') {
                            $(input).attr('name', inputName.replace(/parameter_map\[(.*?)]/, 'parameter_map[' + trIndex + ']'));
                        }
                    });
                });
            });

            var formData = new FormData($modalForm.find('form')[0]);
            formData.append('ajax', 1);
            formData.append('token', self.ajaxToken);
            formData.append('action', 'submitParameterMap');

            $.ajax({
                url: self.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                success: function(json) {
                    if (json.success) {
                        var $formGroup = $fieldsetParameters.find('button[data-id="' + parameterMapId + '"]').closest('.form-group');

                        var $parameterForm = $($.parseHTML(json.parameterForm));
                        $parameterForm.find('.form-group').each(function(index, element) {
                            if (!$(element).hasClass('is-ambiguous-value')) {
                                $formGroup.html($(element).html());
                                $formGroup.find('select.category-field').chosen({width: '250px'});
                            } else {
                                $formGroup.next().html($(element).html());
                            }
                        });

                        showSuccessMessage(json.message);
                        $modalForm.modal('hide');
                    } else {
                        showErrorMessage(json.message);
                    }
                }
            });
        });

        $modalForm.on('hide.bs.modal', function() {
            parameterMapRowInProgress = false;
        });

        $modalForm.on('click', '.checkbox label', function(e) {
            e.preventDefault();

            var $input = $(this).find('input');
            $input.prop('checked', !$input.is(':checked'));
        });

        function closeParameterMapping($row, closeAndSave)
        {
            var $cellValueId = $row.find('.xallegro-parameter-valueId');
            var $cellRule = $row.find('.xallegro-parameter-rule');
            var $cellRuleValue = $row.find('.xallegro-parameter-ruleValue');

            if (closeAndSave) {
                var isRangeSplit = ($cellRule.data('range') == 'range_split');
                var isAmbiguous = (typeof $cellRule.data('ambiguous') !== 'undefined' && $cellRule.data('ambiguous'));

                if (((parameterDictionary || parameterRangeMapRules) && typeof $cellValueId.data('value') === 'undefined')
                    || typeof $cellRule.data('value') === 'undefined'
                    || (!isRangeSplit && typeof $cellRuleValue.data('value') === 'undefined')
                    || ((parameterDictionary || parameterRangeMapRules) && $cellValueId.data('value') == '0')
                    || $cellRule.data('value') == '0'
                    || ((isRangeSplit && (!('rangeMin' in $cellRuleValue.data('range-value')) || !('rangeMax' in $cellRuleValue.data('range-value'))))
                        || (!isRangeSplit && $cellRuleValue.data('value') == '0'))
                    || (isAmbiguous && ($cellRule.data('ambiguous-value') == '0' || $cellRuleValue.data('ambiguous-value') == '0'))
                ) {
                    self._modalAlert($modalForm.find('#xallegro_parameter_map_form_modal_alert_before_save'));
                    return false;
                }

                $row.data('saved', '1');

                if (parameterDictionary || parameterRangeMapRules) {
                    $cellValueId.find('span').text($cellValueId.data('name'));
                    $cellValueId.find('input.input-parameter-map')
                        .data('name', $cellValueId.data('name'))
                        .val($cellValueId.data('value'));
                }

                $cellRule.find('span').text($cellRule.data('name'));
                $cellRule.find('input.input-parameter-map')
                    .data('name', $cellRule.data('name'))
                    .val($cellRule.data('value'));

                if (isRangeSplit) {
                    $cellRuleValue.find('span').html(
                        'od: ' + $cellRuleValue.data('range-name').rangeMin + '<br>'
                        + 'do: ' + $cellRuleValue.data('range-name').rangeMax
                    );

                    $cellRuleValue.find('input.input-parameter-map.rangeMin')
                        .data('name', $cellRuleValue.data('range-name').rangeMin)
                        .val($cellRuleValue.data('range-value').rangeMin);

                    $cellRuleValue.find('input.input-parameter-map.rangeMax')
                        .data('name', $cellRuleValue.data('range-name').rangeMax)
                        .val($cellRuleValue.data('range-value').rangeMax);
                }
                else {
                    $cellRuleValue.find('span').text($cellRuleValue.data('name'));
                    $cellRuleValue.find('input.input-parameter-map')
                        .data('name', $cellRuleValue.data('name'))
                        .val($cellRuleValue.data('value'));

                    if (isAmbiguous) {
                        var textRuleValue = $cellRuleValue.find('span').text();
                        var textAmbiguousRuleValue = $cellRuleValue.data('ambiguous-name');

                        $cellRuleValue.find('span').append(
                            (textRuleValue != '' ? '<br>' : '')
                            + 'inna wartość: '
                            + $cellRule.data('ambiguous-name')
                            + (textAmbiguousRuleValue != '' ? ' "' + textAmbiguousRuleValue + '"' : '')
                        );

                        $cellRule.find('input.input-ambiguous-parameter-map')
                            .data('name', $cellRule.data('ambiguous-name'))
                            .val($cellRule.data('ambiguous-value'));

                        $cellRuleValue.find('input.input-ambiguous-parameter-map')
                            .data('name', $cellRuleValue.data('ambiguous-name'))
                            .val($cellRuleValue.data('ambiguous-value'));
                    }
                }
            }

            if ($row.data('saved')) {
                if (parameterDictionary || parameterRangeMapRules) {
                    $cellValueId.find('select').chosen('destroy').remove();
                    $cellValueId.find('span').show();
                    $cellValueId.find('.ambiguous').remove();
                    removeDataAttributes($cellValueId);
                }

                $cellRule.find('select').chosen('destroy').remove();
                $cellRule.find('.placeholder').remove();
                $cellRule.find('span').show();
                removeDataAttributes($cellRule);

                $cellRuleValue.find('input[type="text"]').remove();
                $cellRuleValue.find('select.chosenType').chosen('destroy').remove();
                $cellRuleValue.find('select.select2Type').select2('destroy').remove();
                $cellRuleValue.find('.placeholder').remove();
                $cellRuleValue.find('span').show();
                removeDataAttributes($cellRuleValue);

                $row.find('.xallegro-parameter-row-save').hide();
                $row.find('.xallegro-parameter-row-quit').hide();
                $row.find('.xallegro-parameter-row-edit').show();
                $row.find('.xallegro-parameter-row-delete').show();
            } else {
                $row.remove();
            }

            return true;
        }

        function initializeSelect2($element, collection, collectionKey)
        {
            var searchQuery;

            $element.select2({
                ajax: {
                    transport: function(params, success) {
                        var pageSize = 1000;
                        var term = (params.data.term || '').toLowerCase();
                        var page = (params.data.page || 1);

                        searchQuery = term;

                        var results = searchCollection[collection]
                            .filter(function(element) {
                                if (!element.name) {
                                    return false;
                                }

                                return new RegExp(getMatchRegex(term), "i").test(element.name.toLowerCase());
                            })
                            .map(function(element) {
                                return {
                                    id: element[collectionKey],
                                    text: element.name
                                };
                            });

                        var paged = results.slice((page -1) * pageSize, page * pageSize);

                        success({
                            results: paged,
                            pagination: {
                                more: results.length >= page * pageSize
                            }
                        });
                    }
                },
                templateResult: function(item) {
                    if (item.loading) {
                        return item.text;
                    }

                    return renderMatchResults(item.text, searchQuery);
                }
            });

            function getMatchRegex(searchQuery)
            {
                return searchQuery.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
            }

            function renderMatchResults(text, searchQuery)
            {
                var match = text.toLowerCase().indexOf(searchQuery);
                var $result = $('<span></span>');

                if (match < 0) {
                    return $result.text(text);
                }

                $result.text(text.substring(0, match));
                var $match = $('<span class="select2-rendered__match"></span>');

                $match.text(text.substring(match, match + searchQuery.length));
                $result.append($match);
                $result.append(text.substring(match + searchQuery.length));

                return $result;
            }
        }

        function createSelectWithDictionary()
        {
            var $select = createSelect();
            for (var indexParameter in parameterDictionary) {
                $select.append(
                    $('<option></option>')
                        .attr('value', parameterDictionary[indexParameter].id)
                        .text(parameterDictionary[indexParameter].value)
                );
            }

            return $select.addClass('chosenType');
        }

        function createSelectWithMapRules(selectedValue)
        {
            var $select = createSelect();
            for (var indexRule in parameterMapRules) {
                if (typeof selectedValue !== 'undefined'
                    && 'ambiguous' in parameterMapRules[indexRule]
                    && parameterMapRules[indexRule].ambiguous
                    && selectedValue != parameterAmbiguousValueId
                ) {
                    continue;
                }

                $select.append(
                    $('<option></option>')
                        .attr('value', indexRule)
                        .attr('data-type', parameterMapRules[indexRule].type)
                        .attr('data-search-key', ('searchKey' in parameterMapRules[indexRule] ? parameterMapRules[indexRule].searchKey : false))
                        .text(parameterMapRules[indexRule].name)
                );
            }

            return $select.addClass('rule chosenType');
        }

        function createSelectWithMapRuleCollection(ruleId, className)
        {
            var $select = createSelect();

            if (typeof className !== 'undefined') {
                $select.addClass(className);
            }

            for (var indexRuleValue in parameterMapRules[ruleId].collection) {
                $select.append(
                    $('<option></option>')
                        .attr('value', indexRuleValue)
                        .text(parameterMapRules[ruleId].collection[indexRuleValue])
                );
            }

            return $select.addClass('ruleValue chosenType');
        }

        function createSelectWithRangeMapRules()
        {
            var $select = createSelect();
            for (var indexRange in parameterRangeMapRules) {
                $select.append(
                    $('<option></option>')
                        .attr('value', indexRange)
                        .text(parameterRangeMapRules[indexRange].name)
                );
            }

            return $select.addClass('chosenType');
        }

        function createSelectWithAmbiguousMapRules(ruleId)
        {
            var $select = createSelect();
            for (var indexRule in parameterAmbiguousMapRules) {
                if (typeof ruleId !== 'undefined'
                    && 'hiddenWhen' in parameterAmbiguousMapRules[indexRule]
                    && parameterAmbiguousMapRules[indexRule].hiddenWhen.includes(ruleId)
                ) {
                    continue;
                }

                $select.append(
                    $('<option></option>')
                        .attr('value', indexRule)
                        .attr('data-type', parameterAmbiguousMapRules[indexRule].type)
                        .text(parameterAmbiguousMapRules[indexRule].name)
                );
            }

            return $select.addClass('ambiguous ambiguousRule chosenType');
        }

        function createSelect()
        {
            var $select = $('<select></select>');

            return $select.append(
                $('<option></option>')
                    .attr('value', 0)
                    .text('-- Wybierz --')
            );
        }

        function createInputParameterMap(inputName, multiple)
        {
            return $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'xallegro_parameter_map[][' + inputName + ']' + (typeof multiple !== 'undefined' ? '[' + multiple + ']' : ''))
                .addClass('input-parameter-map');
        }

        function createInputAmbiguousParameterMap(inputName)
        {
            return $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'xallegro_parameter_map[][ambiguous][' + inputName + ']')
                .addClass('input-ambiguous-parameter-map');
        }

        function createInputAmbiguousValue()
        {
            return $('<input type="text" class="ambiguous ambiguousRuleValue">');
        }

        function createPlaceholderRuleValue()
        {
            return createPlaceholder('ruleValue');
        }

        function createPlaceholderambiguousRuleValue()
        {
            return createPlaceholder('ambiguousRuleValue');
        }

        function createPlaceholder(className, content)
        {
            var $placeholder = $('<div class="placeholder"></div>');

            if (typeof className !== 'undefined') {
                $placeholder.addClass(className);
            }
            if (typeof content !== 'undefined') {
                $placeholder.text(content);
            }

            return $placeholder;
        }

        function alertSaveMapping()
        {
            self._modalAlert($modalForm.find('#xallegro_parameter_map_form_modal_alert_save'));
            return false;
        }

        function calculateMapRows()
        {
            var $badgeMapRows = $modalForm.find('.modal-body #navTab_parameterMap_mapping .badge');
            var mapRows = $modalForm.find('.xallegro-parameter-map-table tbody tr').length;

            if (mapRows > 0) {
                $badgeMapRows.text(mapRows);
            } else {
                $badgeMapRows.empty();
            }
        }

        function removeDataAttributes($element)
        {
            $.each($element.data(), function(key) {
                var attr = 'data-' + key.replace(/([A-Z])/g, '-$1').toLowerCase();
                $element.removeAttr(attr);
            });

            $element.removeData();
        }

        function submitCategoryFieldsValues($element)
        {
            var xAllegroCategoryId = (parseInt($('#id_xallegro_category').val()) || 0);
            if (!xAllegroCategoryId) {
                return;
            }

            isProcessingCategoryFieldsValues = true;

            var formData = new FormData();
            formData.append('ajax', 1);
            formData.append('token', self.ajaxToken);
            formData.append('action', 'submitCategoryFieldsValues');
            formData.append('id_xallegro_category', xAllegroCategoryId);

            $element.closest('form')
                .serializeArray()
                .filter(function(element) {
                    return new RegExp(/category(_ambiguous)?_fields/).test(element.name.toLowerCase());
                })
                .map(function(element) {
                    formData.append(element.name, element.value);
                });

            $.ajax({
                url: self.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                success: function(json) {
                    if (json.success) {
                        showSuccessMessage(json.message);
                    } else {
                        showErrorMessage(json.message);
                    }

                    isProcessingCategoryFieldsValues = false;
                }
            });
        }
    },

    categoryParametersForm: function($container, reload)
    {
        $container.find('.category-field-group.is-multiple-value').each(function(index, element) {
            if ($(element).find('.multiple-values-group:not(.hide)').length > 1) {
                $(element).find('.multiple-values-delete').show();
            }

            var $showButton = $(element).find('.multiple-values-show');
            if (!$(element).find('.multiple-values-group.hide').length) {
                $showButton.hide();
            } else {
                $showButton.show();
            }
        });

        if (typeof reload !== 'undefined' && reload) {
            return;
        }

        $container.on('click', '.multiple-values-delete', function(e) {
            e.preventDefault();

            var $formGroup = $(this).closest('.form-group');
            var thisId = $(this).closest('div').find('input.category-field').attr('id');
            var values = [];
            var shownLength;

            $formGroup.find('.multiple-values-group:not(.hide)').each(function(index, element) {
                var $input = $(element).find('input.category-field');
                if ($input.attr('id') != thisId) {
                    values.push($input.val());
                }

                $input.val('').trigger('input');
            });

            shownLength = $formGroup.find('.multiple-values-group:not(.hide)').length;
            $formGroup.find('.multiple-values-group:not(.hide)').eq(shownLength -1).addClass('hide')
                .find('.category-field-text-counter').addClass('hide');

            $formGroup.find('.multiple-values-group:not(.hide)').each(function(index, element) {
                $(element).find('input.category-field').val(values[index]).trigger('input');
            });

            shownLength = $formGroup.find('.multiple-values-group:not(.hide)').length;
            var $lastShownCounter = $formGroup.find('.multiple-values-group:not(.hide)').eq(shownLength -1).find('.category-field-text-counter');
            if ($lastShownCounter.hasClass('hide')) {
                $lastShownCounter.removeClass('hide').addClass('hidefix');
            }

            if ($formGroup.find('.multiple-values-group.hide').length) {
                $formGroup.find('.multiple-values-show').show();
            }

            var $fieldDeleteButton = $formGroup.find('.multiple-values-delete');
            if ($formGroup.find('.multiple-values-group:not(.hide)').length > 1) {
                $fieldDeleteButton.show();
            } else {
                $fieldDeleteButton.hide();
            }

            $(this).closest('div').find('input.category-field').trigger('focusout');
        });

        $container.on('click', '.multiple-values-show', function(e) {
            e.preventDefault();

            var $formGroup = $(this).closest('.form-group');
            var $field = $formGroup.find('.multiple-values-group.hide').eq(0);
            var $fieldCounter = $field.find('.category-field-text-counter');
            var $previousCounter = $fieldCounter.closest('div').prev().find('.category-field-text-counter');

            $field.removeClass('hide');

            if ($formGroup.find('.multiple-values-group.hide').length < 1) {
                $(this).hide();
            } else {
                $fieldCounter.removeClass('hide').addClass('hidefix');
            }

            if ($previousCounter.hasClass('hidefix')) {
                $previousCounter.removeClass('hidefix').addClass('hide');
            }

            var $fieldDeleteButton = $formGroup.find('.multiple-values-delete');
            if ($formGroup.find('.multiple-values-group:not(.hide)').length > 1) {
                $fieldDeleteButton.show();
            } else {
                $fieldDeleteButton.hide();
            }
        });

        $container.on('input', '.category-field-text input.category-field', function () {
            var $counter = $(this).closest('div').find('.category-field-text-counter');
            if (!$counter.length) {
                return;
            }

            var length = $(this).val().length
            $counter.find('.counter').text(length);

            if (length > 0) {
                $counter.removeClass('hide hidefix');
            }
        });
    },

    pasForm: function()
    {
        var self = this;
        $('#country_code').chosen();
        this._countryCodeSwitch($('#country_code'));

        $('#country_code').on('change', function () {
            self._countryCodeSwitch($(this));
        });
    },

    pasShippingRatesForm: function()
    {
        this._shipmentsSwitch($('[data-cast=switch]'));
    },

    templateForm: function()
    {
        tinymce.init(initTinyMceLite);

        $('#new_content').gridEditor({
            content_types: ['tinymce'],
            source_textarea: $('#new_content_textarea'),
            tinymce: {
                config: initTinyMceLite
            }
        });

        // fix PS theme
        $('.ge-html-output').hide();

        $('#xallegro_template_form').on('submit', function() {
            $('#new_content_textarea').html($('#new_content').gridEditor('getHtml'));
            return true;
        });
    },

    carriersForm: function()
    {
        var $carrierSelect = $('select.allegro-carrier-select');
        var $operatorSelect = $('select.allegro-operator-select');

        $carrierSelect.on('mousedown', function(e) {
            e.preventDefault();
        });

        $carrierSelect.on('mouseup', function() {
            initializeSelect2($(this));
        });

        $operatorSelect.on('mousedown', function(e) {
            e.preventDefault();
        });

        $operatorSelect.on('mouseup', function() {
            initializeSelect2($(this));
        });

        $operatorSelect.each(function(i, el) {
            toggleOperatorInput($(el));
        });

        $operatorSelect.on('change', function() {
            toggleOperatorInput($(this));
        });

        // disable empty select & input fields to reduce POST max_input_vars
        $(document).on('click', 'button[name="submitAddxallegro_carrier"]', function() {
            $(this).closest('form').find('select').each(function (index, element) {
                disableEmptyInput(element);
            });
            $(this).closest('form').find('input').each(function (index, element) {
                disableEmptyInput(element);
            });
        });

        function toggleOperatorInput(select)
        {
            if (select.val() == 'OTHER') {
                select.parent().find('.allegro-operator-other').show();
            } else {
                select.parent().find('.allegro-operator-other').hide();
            }
        }

        function initializeSelect2($element)
        {
            if (!$element.hasClass('select2-hidden-accessible')) {
                $element.select2();
                $element.select2('open');
            }
        }

        function disableEmptyInput(element)
        {
            var value = $(element).val();

            if (value === '' || value === '0') {
                $(element).prop('disabled', true);
            }
        }
    },

    configurationForm: function()
    {
        var self = this;
        var $modalSynchronization = $('#xallegro_offer_full_synchronization_modal');

        // fix configuration col width
        self._configurationWiderColsFix($('#xallegro_configuration_form'));

        // handle dependencies
        self._configurationDependencies();

        // format numbers by class
        $('input.xcast').each(cast).on('focusout change', cast);

        // individual account settings button
        $('.mark-as-account-option').parents('div[class*="col-"]').prepend(
            '<div class="account-setting-indicator">' +
                '<a title="Ustawienie można konfigurować indywidualnie dla każdego konta Allegro" href="#xallegro_configuration_fieldset_advanced_settings">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm32 32h-64c-17.6 0-33.5 7.1-45.1 18.6 40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64zm-256 0c61.9 0 112-50.1 112-112S381.9 32 320 32 208 82.1 208 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C179.6 288 128 339.6 128 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zm-223.7-13.4C161.5 263.1 145.6 256 128 256H64c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4z"/></svg>' +
                '</a>' +
            '</div>'
        );

        $(document).on('click', '.js-x13-multiselect-disable-all', function(e) {
            e.preventDefault();

            $(this).parent().find('select[multiple]').each(function() {
                $(this).find('option').prop('selected', false);
            });
        });

        $(document).on('change', '.productization-search-checkbox', function() {
            var $select = $(this).closest('tr').find('.productization-search-select');
            if ($(this).prop('checked')) {
                $select.removeAttr('disabled');
            } else {
                $select.prop('disabled', 'disabled');
            }
        });

        $(document).on('change', 'input[id^="REGISTER_CUSTOMER_GROUP_"]', function () {
            var $groupDefault = $('#REGISTER_CUSTOMER_GROUP_DEFAULT');
            var selectedValue = $groupDefault.val();

            $('#conf_id_REGISTER_CUSTOMER_GROUP').find('input').each(function (index, element) {
                var $option = $groupDefault.find('option[value="' + $(element).val() + '"]');

                if ($(element).is(':checked')) {
                    $option.show();

                    if (!selectedValue) {
                        $groupDefault.val(findFirstSelectedOptionValue());
                    }
                } else {
                    $option.hide();

                    if ($(element).val() == selectedValue) {
                        $groupDefault.val(findFirstSelectedOptionValue());
                    }
                }
            });

            function findFirstSelectedOptionValue()
            {
                var $firstSelectedOption = $('#conf_id_REGISTER_CUSTOMER_GROUP').find('input:checked:first');

                if ($firstSelectedOption.length) {
                    return $firstSelectedOption.val()
                }

                return null;
            }
        });
        $('input[id^="REGISTER_CUSTOMER_GROUP_"]').trigger('change');

        $(document).on('click', '#syncAllAuctions', function(e) {
            e.preventDefault();
            $modalSynchronization.modal('show');
        });

        $modalSynchronization.on('click', 'button[name="startOfferFullSynchronization"]', function(e) {
            e.preventDefault();

            $modalSynchronization.find('.alert').hide();
            $modalSynchronization.find('button.close').hide();
            $modalSynchronization.find('button[name="closeOfferFullSynchronization"]').hide();
            $modalSynchronization.find('button[name="startOfferFullSynchronization"]').hide();

            self.offerFullSynchronization($modalSynchronization);
        });
    },

    logList: function()
    {
        var self = this;
        var $modalLogDetails = $('#log_details_modal');

        checkUnread();

        $(window).on('scroll', function () {
            checkUnread();
        });

        $(document).on('click', '#markAllLogsAsRead', function() {
            self.ajaxPOST({
                action: 'markAsRead'
            }, null, function() {
                window.location.reload();
            });
        });

        $(document).on('click', '[x-name="log_details"]', function(e) {
            e.preventDefault();

            var logId = $(this).data('id');

            self.ajaxPOST({
                action: 'getLogDetails',
                logId: logId
            }, null, function(json) {
                $modalLogDetails.find('.modal-body').html(json.html);
                $modalLogDetails.modal('show');
            });
        });

        function checkUnread()
        {
            var ids = [];

            $('[x-name="log_details"][data-displayed="0"]').each(function(index, element) {
                if (isInViewport($(element).closest('tr'))) {
                    $(element).data('displayed', 1).attr('data-displayed', 1);
                    ids.push($(element).data('id'));
                }
            });

            if (ids.length) {
                self.ajaxPOST({
                    action: 'markAsRead',
                    logId: ids
                });
            }
        }

        function isInViewport($el)
        {
            var elementTop = $el.offset().top;
            var elementBottom = elementTop + $el.outerHeight();
            var viewportTop = $(window).scrollTop() + $('#header_infos').outerHeight() + $('#content .page-head').outerHeight();
            var viewportBottom = viewportTop + $(window).height();

            return elementBottom > viewportTop && elementTop < viewportBottom;
        }
    },

    productsExtra: function(productId, images_max, descriptions_max)
    {
        var self = this;
        var changesInAccount = false;
        var imageAdditionalAutoIncrement = 0;
        var descriptionAdditionalAutoIncrement = 0;

        $('#xallegro_images_additional .form-group').each(function() {
            imageAdditionalAutoIncrement++;
        });

        $('#xallegro_description_additional .form-group').each(function(index, element) {
            if (!$(element).hasClass('hidden')) {
                initMCEDescriptionAdditional($(element).find('textarea').attr('id'));
            }

            descriptionAdditionalAutoIncrement++;
        });

        $(document).on('click', 'button[name="submitAddProductXAllegro"]', function(e) {
            e.preventDefault();
            self.productsExtraBeforeSave();

            $('#xallegro_description_additional .form-group:not(.hidden) textarea').each(function (index, element) {
                var $textarea = $('textarea#' + $(element).attr('id'));
                var mce = tinyMCE.get($(element).attr('id'));

                if (mce) {
                    $textarea.html(mce.getContent());
                }

                var content = $textarea.val().trim();
                if (content == '') {
                    $textarea.closest('.form-group').find('.xallegro-description-additional-delete').trigger('click');
                }
            });

            $.ajax({
                url: self.ajaxUrl + '&token=' + self.ajaxToken + '&ajax=1&action=saveProduct&productId=' + productId,
                type: 'POST',
                data: new FormData($('#module_x13allegro').closest('form')[0]),
                contentType: false,
                processData: false,
                cache: false,
                success: function (data) {
                    data = $.parseJSON(data);

                    if (data.result) {
                        showSuccessMessage(data.message);
                    } else {
                        $.each(data.message, function(index, message) {
                            showErrorMessage(message);
                        });
                    }

                    self.productsExtraAfterSave();
                }
            });

            changesInAccount = false;
        });

        $(document).on('click', '#xallegro_show_title_pattens', function(e) {
            e.preventDefault();

            $(this).hide();
            $('#xallegro_title_patterns').show();
        });

        $(document).on('input', '[name="xallegro_auction_title"]', function() {
            if ($(this).prop('value').indexOf('{') != '-1' || $(this).prop('value').indexOf('}') != '-1') {
                $('#xallegro_title_count').hide();
            }
            else {
                var count = self._titleCount($(this).prop('value'));
                var $counter = $(this).parents('.form-group').find('.xallegro_title_counter');

                $('#xallegro_title_count').show();
                $counter.html(count);

                if (count > self.titleMaxCount) {
                    $counter.parent().addClass('badge badge-danger');
                } else {
                    $counter.parent().removeClass('badge badge-danger');
                }
            }
        });

        $(document).on('change', '[name="xallegro_product_custom_account"]', function() {
            if (changesInAccount) {
                if (confirm(XAllegroCustomProductConfirmChangeAccount)) {
                    changesInAccount = false;
                    changeAccount(productId, $(this).val());
                } else {
                    $(this).val($('input[name="xallegro_product_custom_account_current"]').val());
                    return false;
                }
            } else {
                changeAccount(productId, $(this).val());
            }
        });

        $(document).on('click', '#xallegro_custom_prices_delete', function(e) {
            e.preventDefault();

            if (confirm(XAllegroCustomProductConfirmPriceDelete)) {
                self.ajaxPOST({
                    action: 'deleteCustomPrices',
                    productId: productId
                }, null, function(json) {
                    changeAccount(productId, $('input[name="xallegro_product_custom_account_current"]').val());

                    if (json.result) {
                        showSuccessMessage(json.message);
                    }
                });

                changesInAccount = true;
            }
        });

        $(document).on('change', '[name="xallegro_auction_title"], [name="xallegro_sync_quantity_allegro"], [name="xallegro_auto_renew"], [name="xallegro_sync_price"], .with-combinations, .wo-combinations', function() {
            changesInAccount = true;
        });

        $(document).on('click', '.with-combinations[readonly]', function (e) {
            if (confirm(XAllegroCustomProductConfirmChangePrice)) {
                $(document).find('.with-combinations').removeAttr('readonly');
                $(document).find('.wo-combinations').attr('readonly', true);
                changesInAccount = true;
            }
        });

        $(document).on('click', '.wo-combinations[readonly]', function (e) {
            if (confirm(XAllegroCustomProductConfirmChangePriceFlat)) {
                $(document).find('.with-combinations').attr('readonly', true);
                $(document).find('.wo-combinations').removeAttr('readonly');
                changesInAccount = true;
            }
        });

        $(document).on('change', 'select.allegro-price-method', function() {
            var $input = $(this).parents('tr').find('input.allegro-price-input');

            if ($(this).val() == 'price') {
                $input.data('cast-unsigned', false);
            } else {
                $input.data('cast-unsigned', true);
            }

            $input.trigger('change');
        });

        $(document).on('click', '.xallegro-image-additional-delete', function(e) {
            e.preventDefault();
            var $button = $(this);

            self.ajaxPOST({
                'action': 'deleteAdditionalImage',
                'productId': productId,
                'imageName': $button.data('name')
            }, null, function(json) {
                if (json.result) {
                    showSuccessMessage(json.message);
                    $('#xallegro_images_additional').html(json.html);
                } else {
                    $.each(json.message, function(index, message) {
                        showErrorMessage(message);
                    });
                }
            });
        });

        $(document).on('click', '.xallegro-image-additional-update', function(e) {
            e.preventDefault();

            $('input[name="xallegro_image_additional"]').data('update', $(this).data('name')).trigger('click');
        });

        $(document).on('click', '.addXAllegroImageAdditional', function(e) {
            e.preventDefault();

            var count = $('#xallegro_images_additional .form-group').length;
            if (count >= images_max) {
                return alert('Możesz dodać maksymalnie ' + images_max + ' dodatkowych zdjęć.');
            }

            $('input[name="xallegro_image_additional"]').trigger('click');
        });

        $('input[name="xallegro_image_additional"]').on('change', function() {
            var $input = $(this);
            var $button = $('.addXAllegroImageAdditional');
            var $buttonSave = $('[name="submitAddProductXAllegro"]');
            var file = $(this)[0].files[0];
            var formData = new FormData();

            formData.append('ajax', 1);
            formData.append('token', self.ajaxToken);
            formData.append('productId', productId);
            formData.append('action', 'uploadAdditionalImage');
            formData.append('imageAdditional', file, file.name);

            if ($(this).data('update')) {
                formData.append('imageAdditionalUpdate', $(this).data('update'));
                $(this).removeData('update').removeAttr('data-update');
            }

            $button.attr('disabled', 'disabled');
            $buttonSave.attr('disabled', 'disabled');

            $.ajax({
                url: self.ajaxUrl,
                method: 'POST',
                data: formData,
                async: true,
                cache: false,
                contentType: false,
                processData: false,
                xhr: function() {
                    return $.ajaxSettings.xhr();
                },
                success: function (json) {
                    json = JSON.parse(json);

                    if (json.result) {
                        showSuccessMessage(json.message);
                        $('#xallegro_images_additional').html(json.html);
                    } else {
                        $.each(json.message, function(index, message) {
                            showErrorMessage(message);
                        });
                    }

                    // clear file input
                    $input.wrap('<form>').closest('form').get(0).reset();
                    $input.unwrap();

                    $button.removeAttr('disabled');
                    $buttonSave.removeAttr('disabled');
                }
            });
        });

        $('#xallegro_description_additional').sortable({
            handle: '.xallegro-description-additional-move',
            helper: 'clone',
            start: function(e, ui) {
                ui.placeholder.css({height: ui.item.outerHeight()});
            },
            stop: function (e, ui) {
                $(ui.item).find('textarea').each(function () {
                    tinymce.execCommand('mceRemoveEditor', false, $(this).attr('id'));
                    tinymce.execCommand('mceAddEditor', true, $(this).attr('id'));
                });

                recalculateDescriptionAdditionalTagName();
            }
        });

        $(document).on('click', '.xallegro-description-additional-delete', function(e) {
            e.preventDefault();

            $(this).closest('.form-group').remove();
            $('#xallegro_description_additional').sortable('refresh');
            recalculateDescriptionAdditionalTagName();
        });

        $(document).on('click', '.addXAllegroDescriptionAdditional', function(e) {
            e.preventDefault();

            var count = $('#xallegro_description_additional .form-group').length;
            if (count >= descriptions_max) {
                return alert('Możesz dodać maksymalnie ' + descriptions_max + ' dodatkowych opisów.');
            }

            var $lastRow = $('#xallegro_description_additional .form-group').last();
            if ($lastRow.hasClass('hidden')) {
                $lastRow.removeClass('hidden').show();
                initMCEDescriptionAdditional($lastRow.find('textarea').attr('id'));
                return;
            }

            var $newRow = $lastRow.clone();
            var $newTextarea = $('<textarea></textarea>')
                .attr('id', 'xallegro_description_additional_' + descriptionAdditionalAutoIncrement)
                .attr('name', 'xallegro_description_additional[]')
                .attr('class', 'textarea-autosize xallegro_description_additional_' + descriptionAdditionalAutoIncrement);

            $newRow.find('.xallegro-description-additional-wrapper label').attr('for', 'xallegro_description_additional_' + descriptionAdditionalAutoIncrement);
            $newRow.find('.xallegro-description-additional-inner').empty().append($newTextarea);
            $('#xallegro_description_additional').append($newRow).sortable('refresh');

            recalculateDescriptionAdditionalTagName();
            initMCEDescriptionAdditional($newRow.find('textarea').attr('id'));
            descriptionAdditionalAutoIncrement++;
        });

        function recalculateDescriptionAdditionalTagName()
        {
            $('#xallegro_description_additional .form-group').each(function (index, element) {
                $(element).find('.xallegro-description-additional-tag').text('{product_description_additional_' + (index + 1) + '}');
            });
        }

        function initMCEDescriptionAdditional(selector)
        {
            tinySetup($.extend({
                editor_selector: selector
            }, initTinyMceLite));
        }

        function changeAccount(productId, accountId)
        {
            self.ajaxPOST({
                action: 'changeAccount',
                accountId: accountId,
                productId: productId
            }, null, function(json) {
                $('input[name="xallegro_product_custom_account_current"]').val(accountId);
                $('#xallegro_product_custom_form').html(json.html);
                self._cast();
            });
        }
    },

    productsExtraAfterSave: function()
    {
        $(document).find('[name*="xallegro_custom_price"][disabled]').removeAttr('disabled').attr('readonly', true);
    },

    productsExtraBeforeSave: function()
    {
        $(document).find('[name*="xallegro_custom_price"][readonly]').removeAttr('readonly').attr('disabled', true);
    },

    attachmentsManager: function()
    {
        var self = this;

        uploadAttachment = function ($input, index) {
            var $wrapper = $input.closest('.gpsr-safety-information-attachment-wrapper');
            var file = $input[0].files[0];
            var formData = new FormData();

            formData.append('ajax', 1);
            formData.append('token', self.ajaxToken);
            formData.append('action', 'uploadSafetyInformationAttachment');
            formData.append('file', file, file.name);

            var cover = $('<div id="allegro_cover"></div>');
            cover.appendTo($input.closest('.modal-content')).fadeIn(400);

            $.ajax({
                url: self.ajaxUrl,
                method: 'POST',
                data: formData,
                async: true,
                cache: false,
                contentType: false,
                processData: false,
                xhr: function() {
                    return $.ajaxSettings.xhr();
                },
                success: function (json) {
                    json = JSON.parse(json);

                    if (json.success) {
                        var attachmentsRow = json.attachmentsRow;

                        if (typeof index !== 'undefined') {
                            attachmentsRow = attachmentsRow.replace(/_INDEX_/g, index);
                        }

                        $wrapper.find('table > tbody').append(attachmentsRow);
                    } else {
                        showErrorMessage(json.message);
                    }

                    // clear file input
                    $input.wrap('<form>').closest('form').get(0).reset();
                    $input.unwrap();

                    if (typeof index !== 'undefined') {
                        // @todo
                        //checkMaxAttachments();
                    }

                    $input.closest('.modal').find('#allegro_cover').remove();
                }
            });
        };
    },

    tagManager: function(container, map_type, tags_auction_limit)
    {
        // convert DOM to jQuery object
        container = $(container);
        var self = this;

        changeTagsTable(container.find('#xallegro_tags_account').val());
        limitTags(container.find('#xallegro_tags_account').val());

        $(document).on('change', '#xallegro_tags_account', function() {
            changeTagsTable($(this).val());
            limitTags($(this).val());
        });

        $(document).on('click', '.xallegro-tag-edit', function(e) {
            e.preventDefault();
            var tr = $(this).parents('tr');

            var inputHtml = '<input type="text" name="xallegro_tag_input[' + $(this).data('tag-id') + ']" value="' + $(this).data('tag-name') + '" class="xallegro-tag-input fixed-width-xxl pull-left form-control">';

            if ($(this).hasClass('xactive')) {
                tr.find('.xallegro-tag-view').show();
                tr.find('.xallegro-tag-input').hide();
                tr.find('.xallegro-tag-save').hide();
                tr.find('.xallegro-tag-input').val(tr.find('.xallegro-tag-view').text());
                $(this).removeClass('xactive');

                return false;
            }

            container.find('.xallegro-tag-view').show();
            container.find('.xallegro-tag-input').hide();
            container.find('.xallegro-tag-save').hide();

            tr.find('.xallegro-tag-view').hide();
            tr.find('.xallegro-tag-input').show();
            tr.find('.xallegro-tag-save').show();
            $(this).addClass('xactive');

            if (!tr.find('.form-inline .xallegro-tag-input').length) {
                tr.find('.form-inline span').after(inputHtml);
            }
        });

        $(document).on('click', '.xallegro-tag-delete', function(e) {
            e.preventDefault();
            var tr = $(this).parents('tr');
            var id = tr.parents('table').data('id');

            self.ajaxPOST({
                action: 'tagDelete',
                id_xallegro_account: id,
                tagId: tr.data('id')

            }, null, function(data) {
                if (data.result) {
                    tr.remove();
                    limitTags(id);
                    showSuccessMessage(data.message);

                    // @fixme
                    if (map_type == 'auction') {
                        XAllegro.refreshTags(true);
                    }
                }
                else {
                    showErrorMessage(data.message);
                }
            });
        });

        $(document).on('click', '.xallegro-tag-save', function(e) {
            e.preventDefault();
            var tr = $(this).parents('tr');

            self.ajaxPOST({
                action: 'tagSave',
                id_xallegro_account: tr.parents('table').data('id'),
                tagId: tr.data('id'),
                tagName: tr.find('.xallegro-tag-input').val()

            }, null, function(data) {
                if (data.result) {
                    tr.find('.xallegro-tag-view').text(tr.find('.xallegro-tag-input').val()).show();
                    tr.find('.xallegro-tag-input').hide();
                    tr.find('.xallegro-tag-save').hide();

                    showSuccessMessage(data.message);

                    // @fixme
                    if (map_type == 'auction') {
                        XAllegro.refreshTags(true);
                    }
                }
                else {
                    showErrorMessage(data.message);
                }
            });
        });

        $(document).on('click', '.xallegro-tag-new', function(e) {
            e.preventDefault();
            var id = container.find('#xallegro_tags_account').val();
            var map_id = null;

            switch (map_type) {
                case 'product':
                    map_id = (self.presta17 ? $('input[name="form[id_product]"]').val() : $('input[name="id_product"]').val());
                    break;

                case 'category':
                    map_id = $('input[name="id_xallegro_category"]').val();
                    break;

                case 'manufacturer':
                    map_id = $('input[name="id_xallegro_manufacturer"]').val();
                    break;

                case 'auction':
                    map_id = parseInt($('#allegro_category_current').val(), 10);
                    break;

                default:
                    return;
            }

            self.ajaxPOST({
                action: 'tagNew',
                id_xallegro_account: id,
                tagMapId: map_id,
                tagMapType: map_type,
                tagName: container.find('#xallegro_tag_new').val()

            }, null, function(data) {
                if (data.result) {
                    container.find('#xallegro_tag_new').val('');
                    container.find('table[data-id="' + id + '"]').html(data.html);
                    limitTags(id);

                    showSuccessMessage(data.message);

                    // @fixme
                    if (map_type == 'auction') {
                        XAllegro.refreshTags(true);
                    }
                }
                else {
                    showErrorMessage(data.message);
                }
            });
        });

        $(document).on('change', '.xallegro-tag-map', function() {
            limitTags($(this).parents('table').data('id'));
        });

        function changeTagsTable(id)
        {
            if (!id) {
                return;
            }

            container.find('.xallegro-tags-table').hide();
            container.find('.xallegro-tags-table[data-id="' + id + '"]').parent().addClass('scroll-tags-table');
            container.find('.xallegro-tags-table[data-id="' + id + '"]').show();
        }

        function limitTags(id)
        {
            var table = container.find('.xallegro-tags-table[data-id="' + id + '"]');
            var checked = table.find('.xallegro-tag-map:checked').length;

            if (checked >= tags_auction_limit) {
                table.find('.xallegro-tag-map:not(:checked)').prop('disabled', 'disabled');
            } else {
                table.find('.xallegro-tag-map').removeAttr('disabled');
            }
        }
    },

    tagManagerRefresh: function()
    {
        $('#xallegro_tags_account').trigger('change');
    },

    orderShipping: function()
    {
        var self = this;

        $(document).on('click', '.xallegro-shipping-edit', function (e) {
            e.preventDefault();

            var $modal = $('#xallegro_order_shipping_edit_modal_' + $(this).data('id-carrier'));
            var $select = $modal.find('select[name="xallegro_shipping_carrier"]');

            $select.trigger('change');

            if (self.isModernLayout) {
                $select.select2();
            } else {
                $select.chosen({width: '250px'});
            }

            $modal.modal('show');
        });

        $(document).on('change', 'select[name="xallegro_shipping_carrier"]', function () {
            if ($(this).val() == 'OTHER') {
                $(this).parents('form').find('input[name="xallegro_shipping_carrier_name"]').parents('.form-group').show();
            } else {
                $(this).parents('form').find('input[name="xallegro_shipping_carrier_name"]').parents('.form-group').hide();
            }
        });

        $('button[name="saveShippingInfo"]').on('click', function (e) {
            e.preventDefault();

            var form = $(this).parents('form');

            self.ajaxPOST({
                action: 'saveShippingInfo',
                id_order: form.find('input#xallegro_id_order').val(),
                id_order_carrier: form.find('input#xallegro_id_order_carrier').val(),
                operatorId: form.find('select[name="xallegro_shipping_carrier"]').val(),
                operatorName: form.find('input[name="xallegro_shipping_carrier_name"]').val()

            }, null, function (data) {
                if (data.result) {
                    window.location.replace(data.redirect);
                }
                else {
                    showErrorMessage(data.message);
                }
            });
        });

        $('.xallegro-sync-shipping').parents('a').on('click', function (e) {
            e.preventDefault();
            self.syncShippingNumbers(-1, 0, [], []);
        });

        $(document).on('click', '#new_shipping_info_close', function (e) {
            e.preventDefault();
            $('#new_shipping_info').hide();

            self.ajaxPOST({action: 'hideShippingInfo'});
        });
    },

    orderFulfillmentStatus: function()
    {
        var self = this;
        var statusFulfillmentCurrent = $('select[name="xallegro_fulfillment_status"] option:selected').val();

        $('a.xallegro-fulfillment-status').on('click', function(e) {
            e.preventDefault();

            var button = $(this);
            var orderId = button.attr('data-orderId');
            var statusFulfillment = $('select[name="xallegro_fulfillment_status"] option:selected').val();

            self.ajaxPOST({
                action: 'sendFulfilmentStatus',
                orderId: orderId,
                statusFulfillment: statusFulfillment
            }, function() {
                button.addClass('disabled').attr('disabled', true);
            }, function(json) {
                if (json.status) {
                    window.location.replace(json.redirectLink);
                } else {
                    button.removeClass('disabled').removeAttr('disabled', true);
                    showErrorMessage(json.message);
                    $('select[name="xallegro_fulfillment_status"]').val(statusFulfillmentCurrent);
                }
            });
        });
    },

    orderInvoice: function()
    {
        var self = this;
        var $modalOrderInvoice = $('#xallegro_order_invoice_modal');

        $(document).on('click', '#xallegro_order_invoice_add', function (e) {
            e.preventDefault();

            $modalOrderInvoice.modal('show');
        });

        $(document).on('change', 'select[name="xallegro_order_invoice_type"]', function () {
            var $invoiceFile = $modalOrderInvoice.find('#xallegro_order_invoice_type_file');
            var $invoicePS = $modalOrderInvoice.find('#xallegro_order_invoice_type_prestashop');
            var $buttonSubmit = $modalOrderInvoice.find('#xallegro_order_invoice_submit');

            if ($(this).val() === 'file') {
                $invoicePS.hide();
                $invoiceFile.show();
                $buttonSubmit.removeAttr('disabled');
            } else {
                $invoiceFile.hide();
                $invoicePS.show();

                if (!parseInt($invoicePS.data('order-has-invoice'))) {
                    $buttonSubmit.prop('disabled', true);
                } else {
                    $buttonSubmit.removeAttr('disabled');
                }
            }
        });

        $(document).on('click', '#xallegro_order_invoice_file_button', function (e) {
            e.preventDefault();

            $('[name="xallegro_order_invoice_file"]').trigger('click');
        });

        $(document).on('change', 'input[name="xallegro_order_invoice_file"]', function () {
            var fileName = $(this).val();
            fileName = fileName.match(/[^\\/]*$/)[0];

            if (fileName.length) {
                $('#xallegro_order_invoice_file_desc').text(fileName);
            } else {
                $('#xallegro_order_invoice_file_desc').empty();
            }
        });

        $(document).on('click', '#xallegro_order_invoice_submit', function (e) {
            e.preventDefault();

            var $buttonSubmit = $(this);
            var $buttonCancel = $('#xallegro_order_invoice_cancel');
            var $buttonModalCancel = $modalOrderInvoice.find('.x13allegro-modal-close');
            var $alert = $modalOrderInvoice.find('.xallegro-order-invoice-error');
            var $typeSelector = $('select[name="xallegro_order_invoice_type"]');
            var $fileButton = $('#xallegro_order_invoice_file_button');
            var $fileInput = $('input[name="xallegro_order_invoice_file"]');
            var $invoiceNumberInput = $('input[name="xallegro_order_invoice_number"]');
            var invoiceFile = $fileInput[0].files[0];

            var formData = new FormData();
            formData.append('ajax', '1');
            formData.append('token', self.ajaxToken);
            formData.append('action', 'uploadInvoiceFile');
            formData.append('orderId', $buttonSubmit.data('order-id'));
            formData.append('invoiceType', $typeSelector.val());

            if ($typeSelector.val() === 'file' && typeof invoiceFile !== 'undefined') {
                formData.append('invoiceFile', invoiceFile, invoiceFile.name);
                formData.append('invoiceNumber', $invoiceNumberInput.val());
            }

            $buttonModalCancel.hide();
            $buttonCancel.prop('disabled', true);
            $buttonSubmit.prop('disabled', true);
            $typeSelector.prop('disabled', true);
            $fileButton.prop('disabled', true);
            $invoiceNumberInput.prop('disabled', true);
            $alert.empty().hide();

            $.ajax({
                url: self.ajaxUrl,
                method: 'POST',
                contentType: false,
                processData: false,
                data: formData,
                dataType: 'json',
                success: function(json) {
                    if (json.result) {
                        window.location.replace(json.redirectLink);
                    }
                    else {
                        $alert.text(json.message).show();

                        $buttonModalCancel.show();
                        $buttonCancel.removeAttr('disabled');
                        $buttonSubmit.removeAttr('disabled');
                        $typeSelector.removeAttr('disabled');
                        $fileButton.removeAttr('disabled');
                        $fileInput.val('').trigger('change');
                        $invoiceNumberInput.removeAttr('disabled');
                    }
                }
            });
        });
    },

    syncShippingNumbers: function(count, offset, ids_added, ids_not_added)
    {
        var self = this;

        self.ajaxPOST({
            action: 'syncShippingNumbers',
            count: count,
            offset: offset,
            ids_added: ids_added,
            ids_not_added: ids_not_added

        }, function () {
            if (!$('#allegro_cover').length) {
                var cover = $('<div id="allegro_cover"></div>');
                cover.appendTo('body').hide().fadeIn(400);
            }
        }, function (json) {
            if (json.success) {
                self.syncShippingNumbers(json.count, json.offset, json.ids_added, json.ids_not_added);
            }
            else {
                window.location.href = json.link;
            }
        });
    },

    changeCategory: function(categoryId, fullPath, selectBox, fieldsBox)
    {
        var self = this;
        if (typeof fullPath === 'undefined' || !fullPath) {
            fullPath = 0;
        }

        self.ajaxPOST({
            action: 'getCategories',
            id_allegro_category: categoryId,
            id_xallegro_category: (parseInt($('#id_xallegro_category').val()) || 0),
            full_path: fullPath

        }, function() {
            selectBox.closest('.panel').fadeTo('fast', 0.2);
            fieldsBox.fadeTo('fast', 0.2);
            fieldsBox.find('#category_parameters_info').hide();
            fieldsBox.find('.form-wrapper:not(#category_parameters_info)').remove();

            $('.category-input-error').remove();

        }, function(data) {
            selectBox.parent().find('.allegro-category-select select').chosen('destroy');

            if (data['categories'].length) {
                var element = selectBox.clone().insertAfter(selectBox).hide();
                $("option[value!='0']", element.find('select')).remove();

                fillSelect(data['categories'], element.find('select'));
                element.show();
            }
            else if (Object.keys(data['categories_array']).length) {
                var first = true;
                var thisSelectBox;

                $.each(data['categories_array'], function(index, categories) {
                    if (first) {
                        thisSelectBox = selectBox;
                        first = false;
                    }
                    else {
                        var selects = selectBox.parent().find('.allegro-category-select');
                        thisSelectBox = selects.eq(selects.length-1);
                    }

                    thisSelectBox.find('select').val(categories['id']);

                    if (categories['list'].length) {
                        var element = thisSelectBox.clone().insertAfter(thisSelectBox).hide();
                        $("option[value!='0']", element.find('select')).remove();

                        fillSelect(categories['list'], element.find('select'));
                        element.show();
                    }
                });
            }

            if (data['fields']) {
                fieldsBox.find('.panel-heading').after(data['fields']);
                fieldsBox.find('#category_parameters_info').show();
                fieldsBox.fadeTo('fast', 1);

                $('div[x-name=product_category_fields]').each(function () {
                    $(this).html('<div class="form-horizontal">'
                        + data.fields.replace(/category_fields/g, 'item[' + $(this).attr('x-index') + '][category_fields]')
                        + '</div>');
                });

                fieldsBox.find('select').each(function () {
                    $(this).chosen();
                });
            }

            if (data['last_node']) {
                if (!data['fields']) {
                    fieldsBox.find('.panel-heading').after('<p class="category-input-error">Brak cech dla wybranej kategorii</p>');
                    fieldsBox.find('#category_parameters_info').show();
                    fieldsBox.fadeTo('fast', 1);
                }
            }
            else if (!data['categories'].length && !Object.keys(data['categories_array']).length) {
                $('#allegro_category_input').parent().append('<p class="category-input-error">Podano niepoprawny numer kategorii</p>');
            }

            selectBox.parent().find('.allegro-category-select select').chosen({width: '200px'});
            selectBox.closest('.panel').fadeTo('fast', 1);

            $('#allegro_category_input').val(categoryId);
            $('#allegro_category_current').val(categoryId);
            $('#allegro_category_is_leaf').val(data.last_node);

            function fillSelect(categories, element)
            {
                $.each(categories, function(index, category) {
                    element.append(new Option(category['name'], category['id']));
                });
            }
        });
    },

    updateImagesPositions: function(index)
    {
        var images = [];

        $('[x-name="product"][x-id="' + index + '"]').find('input[x-name="images"]').each(function() {
            images.push($(this).val());
        });

        this.ajaxPOST({
            action: 'updateImagesPositions',
            id_product: $('[x-name="product"][x-id="' + index + '"]').find('input[x-name="id_product"]').val(),
            id_product_attribute: $('[x-name="product"][x-id="' + index + '"]').find('input[x-name="id_product_attribute"]').val(),
            images: images
        });
    },

    prepareAuctionsData: function(form)
    {
        var form_data = form.serializeArray();
        var data = {data: [], items: []};

        for (var i in form_data) {
            if (form_data[i].name.substr(0,18) === 'xallegro_tag_input') {
                continue;
            }
            
            if (form_data[i].name.substr(0,4) !== 'item') {
                data['data'].push({name : form_data[i].name, value: form_data[i].value});
            }
            else {
                var item_index = form_data[i].name.substr(0,10).replace(/[^0-9]/gi, '');

                if (typeof data['items'][item_index] === 'undefined') {
                    data['items'][item_index] = new Array();
                }

                data['items'][item_index].push({name : form_data[i].name, value: form_data[i].value});
            }
        }

        return data;
    },

    performAuctions: function(start_index)
    {
        var self = this;

        if (start_index == 0) {
            self.successAuctions = 0;
            self.errorAuctions = 0;
            self.successAuctionsMsg = '';
            self.errorAuctionsMsg = '';
            $('#allegro_perform_info_message').remove();
            $('#allegro_perform_success_message').remove();
            $('#allegro_perform_error_message').remove();
        }

        var form = $('#allegro_main_form');
        var items_length = $('[x-name="product_switch"]:checked').length;
        var limit = 1;
        var prepared_data = self.prepareAuctionsData(form);
        var data = prepared_data['data'];
        var items = prepared_data['items'];

        data[data.length] = {name: 'ajax', value: true};
        data[data.length] = {name: 'token', value: xallegro_token};
        data[data.length] = {name: 'start_index', value: start_index};
        data[data.length] = {name: 'action', value: 'performAuctions'};
        var send_data = data;

        var chunk = items.slice(start_index * limit, (start_index+1) * limit);
        
        for (var i in chunk) {
            send_data = send_data.concat(chunk[i]);
        }

        $.ajax({
            type: 'POST',
            url: currentIndex,
            dataType: 'json',
            data: send_data,
            beforeSend: function() {
                if ($('#allegro_perform_info_message').length == 0) {
                    $('html, body').animate({'scrollTop' : 0}, 'fast');

                    var infoMessageHTML = $(
                        '<div id="allegro_perform_info_message" class="alert">' +
                            '<p>' +
                                '<i class="icon-refresh icon-spin"></i> &nbsp; ' +
                                '<strong class="message">Wystawianie ofert</strong> (<b class="current">0</b> / <b class="total">' + items_length + '</b>)' +
                            '</p>' +
                        '</div>'
                    );
                    var messageHTML = $(
                        '<div id="allegro_perform_success_message" class="alert alert-success"></div>' +
                        '<div id="allegro_perform_error_message" class="alert alert-danger"></div>'
                    );

                    infoMessageHTML.insertBefore(form).hide().slideDown('slow');
                    messageHTML.insertBefore(form).hide();
                }
            },
            success: function(json) {
                $.each(json, function(index, data) {
                    $('#allegro_perform_info_message .current').text(parseInt($('#allegro_perform_info_message .current').text()) + 1);
                    $('tr[x-id="' + data.x_id + '"]').find('input[x-name="id_auction"]').val(data.id_auction);

                    if (data.success) {
                        self.successAuctions++;
                        self.successAuctionsMsg += '<p' + (self.successAuctions > 10 ? ' class="allegro-success-hidden" style="display: none;"' : '') + '>' + data.message + '</p>';

                        $('tr[x-id="' + data.x_id + '"]').prev().find('input[x-name="product_switch"]').removeAttr('checked').trigger('change').prop('disabled', 'disabled');
                    }
                    else {
                        self.errorAuctions++;
                        self.errorAuctionsMsg += '<p>' + data.message + '</p>';
                    }
                });

                if ((start_index+1) * limit >= items.length) {
                    $('#allegro_perform_info_message').hide();

                    if (self.successAuctionsMsg) {
                        var successTitle, successSubtitle1, successSubtitle2;
                        if (self.successAuctions == 1) {
                            successTitle = 'Oferta jest przygotowywana';
                            successSubtitle1 = 'Trwa sprawdzanie oferty po stronie Allegro, może to potrwać od kilku minut do dwóch godzin.';
                            successSubtitle2 = 'Po zatwierdzeniu oferta będzie widoczna pod linkiem';
                        } else {
                            successTitle = 'Oferty są przygotowywane';
                            successSubtitle1 = 'Trwa sprawdzanie ofert po stronie Allegro, może to potrwać od kilku minut do dwóch godzin.';
                            successSubtitle2 = 'Po zatwierdzeniu oferty będą widoczne pod linkami';
                        }

                        $('#allegro_perform_success_message').html(
                            '<h4>' +
                                '<strong>' + successTitle + '</strong><br/>' +
                                '<small>' + successSubtitle1 + '</small><br/>' +
                                '<small>' + successSubtitle2 + '</small><br/>' +
                            '</h4>' +
                            self.successAuctionsMsg +
                            (self.successAuctions > 10 ? '<br/><a href="#" id="allegro_success_show" class="button btn btn-default">Pokaż więcej</a>' : '')
                        ).slideDown('slow');
                    }

                    if (self.errorAuctionsMsg) {
                        $(document).find('.xallegro-perform').parent().removeClass('allegro-send-auction-hidden');

                        $('#allegro_perform_error_message').html(
                            '<h4><strong>Niepoprawne ustawienia ofert: ' + self.errorAuctions + '</strong><br/>' +
                                '<small>Popraw występujące błędy i kliknij przycisk "Wystaw oferty" ponownie.</small>' +
                            '</h4>' +
                            self.errorAuctionsMsg
                        ).slideDown('slow');
                    }

                    if (self.successAuctions && !self.errorAuctions) {
                        $(document).find('.xallegro-perform').parent().remove();

                        $('#allegro_main_form').slideUp('slow')
                            .after('<a href="' + xOffersListList + '" class="button btn btn-success">Przejdź do listy ofert</a>')
                            .after('<a href="' + xBackLink + '" class="button btn btn-default">Powrót do listy produktów</a>&nbsp;');
                    }
                }
                else {
                    self.performAuctions(start_index+1);
                }
            }
        });
    },

    offerFullSynchronization: function($modal)
    {
        var self = this;
        var accounts = [];

        $modal.find('.form-wrapper').show();

        getAccountsForSynchronization(function (json) {
            if (json.accounts.length) {
                accounts = json.accounts;

                for (var i = 0; i < accounts.length; i++) {
                    $modal.find('#synchronization_accounts').append(
                        '<li data-id="' + accounts[i].id + '">' + accounts[i].name + '</li>'
                    );
                }

                getOffersForSynchronization(0, 0, true, function () {
                    $modal.find('#synchronization_info span').text('Trwa aktualizacja pobranych ofert, może to chwilę potrwać.');
                    $modal.find('#synchronization_accounts').hide();
                    $modal.find('.progress').hide();

                    self.ajaxPOST({
                        action: 'synchronizeOffers'
                    }, null, function (json) {
                        if (json.success) {
                            $modal.find('#synchronization_info i').attr('class', 'icon-check');
                            $modal.find('#synchronization_info span').text('Zakończono synchronizację ofert.');
                            $modal.find('#offerFullSynchronizationAuctionsList').show();
                        } else {
                            $modal.find('#synchronization_info i').attr('class', 'icon-times');
                            $modal.find('#synchronization_info span').text(json.message);
                        }

                        $modal.find('button[name="closeOfferFullSynchronization"]').show();
                    });
                });
            } else {
                $modal.find('#synchronization_info i').attr('class', 'icon-times');
                $modal.find('#synchronization_info span').text('Brak aktywnych kont Allegro.');
                $modal.find('.progress').hide();
                $modal.find('button[name="closeOfferFullSynchronization"]').show();
            }
        });

        function getOffersForSynchronization(accountIndex, offset, startSynchronization, callbackFinish)
        {
            if (!(accountIndex in accounts)) {
                return callbackFinish();
            }

            var $currentAccountInfo = $modal.find('#synchronization_accounts li[data-id="' + accounts[accountIndex].id + '"]');

            if (!$currentAccountInfo.find('.badge').length) {
                $currentAccountInfo.append('&nbsp;&nbsp;<small><span class="badge">Pobieranie informacji o ofertach</span></small>');
            }

            self.ajaxPOST({
                action: 'getOffersForSynchronization',
                startSynchronization: +startSynchronization,
                accountId: accounts[accountIndex].id,
                offset: offset
            }, null, function (json) {
                if (json.success) {
                    if (json.result.count == 0) {
                        accountIndex++;
                        offset = 0;

                        $currentAccountInfo.find('.badge').addClass('badge-success').text('Pobrano');
                    } else {
                        var $progressBar = $modal.find('.progress-bar');
                        $progressBar.attr('aria-valuemax', json.result.totalCount);
                        $progressBar.attr('aria-valuenow', parseInt($progressBar.attr('aria-valuenow')) + json.result.count);
                        $progressBar.css('width', parseInt(($progressBar.attr('aria-valuenow') / $progressBar.attr('aria-valuemax')) * 100) + '%');

                        offset++;
                    }

                    getOffersForSynchronization(accountIndex, offset, false, callbackFinish);
                } else {
                    $modal.find('#synchronization_info i').attr('class', 'icon-times');
                    $modal.find('#synchronization_info span').text('Wystąpił błąd podczas synchronizacji ofert.');
                    $modal.find('.progress').hide();
                    $currentAccountInfo.find('.badge').addClass('badge-danger').text(json.message);
                    $modal.find('button[name="closeOfferFullSynchronization"]').show();
                }
            });
        }

        function getAccountsForSynchronization(callback)
        {
            self.ajaxPOST({
                action: 'getAccountsForSynchronization'
            }, null, function (json) {
                callback(json);
            });
        }
    },

    ajaxPOST: function(data, beforeSend, success, error)
    {
        var self = this;
        var defaultData = {
            'token': self.ajaxToken,
            'ajax': true
        };

        var request = $.ajax({
            url: self.ajaxUrl,
            method: 'POST',
            async: true,
            dataType: 'json',
            data: $.extend(defaultData, data),
            beforeSend: beforeSend,
            success: function(json) {
                if (json && json.apiError) {
                    $.each(json.messages, function (index, message) {
                        showErrorMessage(message);
                    });
                }
                else if (success) {
                    success(json);
                }
            },
            error: error
        });

        self.ajaxRequest.push(request);
    },

    ajaxAbort: function()
    {
        this.ajaxForceTerminateLoop = true;

        $.each(this.ajaxRequest, function(i, ajax) {
            ajax.abort();
        });
    }
};

var initTinyMceLite = {
    plugins: 'code, lists, spellchecker, paste, directionality, visualblocks, nonbreaking, visualchars, autoresize, table',
    toolbar1: 'undo,redo,|,cut,copy,paste,|,styleselect,bold,bullist,numlist,|,code',
    menu: {},
    browser_spellcheck: true,
    paste_data_images: false,
    style_formats: [
        {title: 'Nagłówek h1', block: 'h1'},
        {title: 'Nagłówek h2', block: 'h2'},
        {title: 'Akapit', block: 'p'}
    ],
    language: 'pl'
};
