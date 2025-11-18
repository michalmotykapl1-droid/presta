var x13Import = (function ($, module) {

    $(function() {
        $(document).on('focusout change x-cast', '.x-cast', function() {
            let type = $(this).data('cast');
            let unsigned = false;
            let allowEmpty = false;
            let precision = 2;

            // workaround for classes
            if (typeof type === 'undefined') {
                if ($(this).hasClass('x-cast-float')) {
                    type = 'float';
                } else {
                    type = 'integer';
                }
            }

            if (typeof $(this).data('cast-unsigned') !== 'undefined' && $(this).data('cast-unsigned')) {
                unsigned = true;
            }
            // workaround for classes
            else if ($(this).hasClass('x-cast-unsigned')) {
                unsigned = true;
            }

            if (typeof $(this).data('cast-allow-empty') !== 'undefined' && $(this).data('cast-allow-empty')) {
                allowEmpty = true;
            }
            // workaround for classes
            else if ($(this).hasClass('x-cast-blank')) {
                allowEmpty = true;
            }

            if (typeof $(this).data('cast-precision') !== 'undefined') {
                precision = parseInt($(this).data('cast-precision'));
            }
            // @todo workaround for classes

            let val = $(this).val();

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

            if (isNaN(val) || val.length === 0) {
                val = 0;
            }

            if (type === 'float') {
                $(this).val(castFormat(val, precision));
            }
            else if (type === 'integer') {
                $(this).val(parseInt(val));
            }

            function castFormat(value, precision)
            {
                let newValue = parseFloat(value);
                let pow = Math.pow(10, 2);
                newValue *= pow;

                let nextDigit = Math.floor(newValue * 10) - 10 * Math.floor(newValue);
                newValue = (nextDigit >= 5 ? Math.ceil(newValue) : Math.floor(newValue));

                return (newValue / pow).toFixed(precision);
            }
        });

        $('.x-cast').trigger('x-cast');

        new x13Import.configurationDependencies();
    });

    module.getWholesalers = function (callback) {
        var ajaxData = {
            action: 'getWholesalers'
        };

        x13Import.ajax(
            ajaxData,
            null,
            function(json) {
                json.wholesalers.forEach(function(value) {
                    x13Import.progressBlock.wholesaler(value);
                });

                callback(json.wholesalers);
            },
            function() {
                callback([]);
            }
        );
    }

    module.disableProcessButton = function(button) {
        button.addClass('disabled').attr('disabled', 'disabled');
        button.find('i').attr('data-class', button.find('i').attr('class')).attr('class', 'process-icon-loading');
    }

    module.enableProcessButton = function(button) {
        button.removeClass('disabled').removeAttr('disabled');
        button.find('i').attr('class', button.find('i').attr('data-class')).removeAttr('data-class');
    }

    module.disableButton = function($button, withIcon) {
        $button.addClass('disabled').attr('disabled', 'disabled');

        withIcon = (typeof withIcon !== 'undefined' ? !!withIcon : true);
        const $icon = $button.find('i');

        if ($icon.length && withIcon) {
            $icon.attr('data-class', $icon.attr('class')).attr('class', 'icon-circle-o-notch icon-spin');
        }
    }

    module.enableButton = function($button) {
        $button.removeClass('disabled').removeAttr('disabled');

        const $icon = $button.find('i');
        if ($icon.length && $icon.attr('data-class')) {
            $icon.attr('class', $icon.attr('data-class')).removeAttr('data-class');
        }
    }

    module.redirect = function(controller, params) {
        var ajaxData = {
            action: 'getRedirectLink',
            linkController: controller,
            linkParams: params
        };

        this.ajax(
            ajaxData,
            null,
            function(json) {
                if (json.redirectLink) {
                    setTimeout(function() {
                        location.replace(json.redirectLink);
                    }, 1000);
                }
            }
        );
    }

    module.ajax = function(data, beforeSend, success, error) {
        var defaultData = {
            token: x13importToken,
            ajax: true
        };

        $.ajax({
            url: currentIndex,
            method: 'POST',
            async: true,
            dataType: 'json',
            data: $.extend(defaultData, data),
            beforeSend: beforeSend,
            success: function(json) {
                if (success) {
                    success(json);
                }
            },
            error: error
        });
    }

    module.ajaxPOST = function(data, success, error) {
        this.ajax(data, null, success, error);
    }

    return module;

}(jQuery, x13Import || {}));

x13Import.progressBlock = (function ($) {
    
    var progressBlockElement,
        progressBlockPattern;

    $(function() {
        progressBlockElement = $('#ximport_progress_block');
        progressBlockPattern = $('#_pattern_ximport_progress_block');

        progressBlockElement.find('.x-close a').on('click', function (e) {
            e.preventDefault();
            progressBlockElement.slideUp('slow');
        });
    });

    var progressBlock = {};
    progressBlock.wholesalerActionProcess = 'badge';
    progressBlock.wholesalerActionSuccess = 'badge badge-success';
    progressBlock.wholesalerActionError = 'badge badge-danger';

    progressBlock.init = function (header, description)
    {
        progressBlockElement.find('.x-header').text(header);
        progressBlockElement.find('.x-description').text((description ? description : ''));
        progressBlockElement.find('.x-bar').hide();
        progressBlockElement.find('.x-bar-visual > div').css('width', 0);
        progressBlockElement.find('.x-form').empty();
        progressBlockElement.find('.x-content').html(progressBlockPattern.find('#_pattern_content').html());
        progressBlockElement.find('.x-close').hide();
        progressBlockElement.slideDown('slow');
    }

    progressBlock.description = function (description) {
        if (description === undefined) {
            progressBlockElement.find('.x-description').hide();
        } else {
            progressBlockElement.find('.x-description').text(description).show();
        }
    }

    progressBlock.bar = function (value) {
        if (value === undefined) {
            progressBlockElement.find('.x-bar').hide();
        } else {
            progressBlockElement.find('.x-bar').show();
            progressBlockElement.find('.x-bar-visual > div').css('width', value + '%');
        }
    }

    progressBlock.messageBefore = function (message) {
        if (message === undefined) {
            progressBlockElement.find('.x-message-before').hide();
        } else {
            progressBlockElement.find('.x-message-before').text(message).show();
        }
    }

    progressBlock.messageAfter = function (message) {
        if (message === undefined) {
            progressBlockElement.find('.x-message-after').hide();
        } else {
            progressBlockElement.find('.x-message-after').text(message).show();
        }
    }

    progressBlock.form = function (html) {
        if (html === undefined) {
            progressBlockElement.find('.x-form').hide();
        } else {
            progressBlockElement.find('.x-form').html(html).show();
        }
    }

    progressBlock.refreshButton = function (message) {
        if (message === undefined) {
            progressBlockElement.find('.x-refresh-button').hide();
        } else {
            progressBlockElement.find('.x-refresh-button').show().find('a').append('&nbsp;&nbsp;' + message);
        }
    }

    progressBlock.wholesaler = function (wholesalerName) {
        var block = progressBlockPattern.find('#_pattern_list_wholesalers > li').clone();

        $('.x-list-wholesaler', block).text(wholesalerName);
        block.attr('id', 'wholesaler' + wholesalerName);
        block.appendTo(progressBlockElement.find('.x-list'));
    }

    progressBlock.wholesalerAction = function (wholesalerName, action, message) {
        var wholesalerElement = progressBlockElement.find('.x-list #wholesaler' + wholesalerName).find('.x-list-wholesaler-action');
        wholesalerElement.addClass(action);

        if (message !== undefined) {
            wholesalerElement.text(message);
        }
    }

    progressBlock.close = function () {
        progressBlockElement.find('.x-close').show();
    }

    return progressBlock;

}(jQuery));

x13Import.overlayLoader = (function ($) {

    function OverlayLoader(...$element) {
        this.container = [];
        this.className = 'ximport-overlay-loader';
        this.showSpinner = true;

        for (const $el of $element) {
            // add relative positioning
            // loader has absolute position
            $el.css('position', 'relative');

            this.container.push($el);
        }
    }

    OverlayLoader.prototype = {
        constructor: OverlayLoader,

        create: function() {
            for (const $container of this.container) {
                if (!$container.find(`.${this.className}`).length) {
                    const $loader = $(`<div class="${this.className}"></div>`);

                    if (this.showSpinner) {
                        $loader.append(`<i class="icon-circle-o-notch icon-spin"></i>`);
                    }

                    $container.append($loader);
                }
            }
        },

        destruct: function() {
            for (const $container of this.container) {
                if ($container.find(`.${this.className}`).length) {
                    $container.find(`.${this.className}`).remove();
                }
            }
        }
    };

    return OverlayLoader;

}(jQuery));

x13Import.importerSelect2 = (function ($) {

    function ImporterSelect2() {
        this.select2Cache = {};
        this.select2CustomInputClass = 'ximport-select2-custom-input';
    }

    ImporterSelect2.prototype = {
        constructor: ImporterSelect2,

        init: function($element, options) {
            if ($element.hasClass('select2-hidden-accessible')) {
                return;
            }

            const self = this;
            let searchQuery;

            $element.select2({
                dropdownCssClass: ':all:',
                selectionCssClass: ':all:',
                ajax: {
                    transport: function (params, success) {
                        const page = (params.data.page || 1);
                        searchQuery = (params.data.term || '').toLowerCase();

                        if (options.cacheKey in self.select2Cache && self.select2Cache[options.cacheKey].length) {
                            success(self.returnResults(self.select2Cache[options.cacheKey], searchQuery, page, options.map));
                        } else {
                            x13Import.ajaxPOST(options.ajaxData, function (json) {
                                self.select2Cache[options.cacheKey] = json;
                                success(self.returnResults(self.select2Cache[options.cacheKey], searchQuery, page, options.map));
                            });
                        }
                    }
                },
                templateResult: function(item) {
                    if (item.loading) {
                        return item.text;
                    }

                    return self.renderResults(item.text, searchQuery);
                }
            }).select2('open');

            $('.select2-dropdown').addClass('bootstrap');
        },

        destroy: function($element) {
            if (!$element.hasClass('select2-hidden-accessible')) {
                return;
            }

            $('.select2-dropdown').find(`.${this.select2CustomInputClass}`).remove();
            $element.select2('destroy');
        },

        chooseNewOption: function($element, optionId, optionValue) {
            if (!$element.find(`option[value="${optionId}"]`).length) {
                $element.append(new Option(optionValue, optionId, true, true));
            }

            if ($element.hasClass('select2-hidden-accessible')) {
                $element.select2('close');
            }

            $element.val(optionId).trigger('change');
        },

        searchInCache: function(cacheKey, elementKey, value) {
            if (!(cacheKey in this.select2Cache) || !this.select2Cache[cacheKey].length) {
                return false;
            }

            const result = this.select2Cache[cacheKey]
                .filter(function(element) {
                    return value.trim().toLowerCase() === element[elementKey].toLowerCase();
                })
                .map(function(element) {
                    return element;
                });

            if (result.length) {
                return result[0];
            }

            return false;
        },

        insertIntoCache: function(cacheKey, object) {
            this.select2Cache[cacheKey] = object;
        },

        pushIntoCache: function(cacheKey, object, sortKey) {
            if (!(cacheKey in this.select2Cache)) {
                this.select2Cache[cacheKey] = [];
            }

            this.select2Cache[cacheKey].push(object);

            if (typeof sortKey !== 'undefined') {
                this.select2Cache[cacheKey].sort(function(a, b) {
                    return a[sortKey].localeCompare(b[sortKey]);
                });
            }
        },

        createCustomInput: function(options) {
            const $inputGroup = $(`<div class="input-group ${this.select2CustomInputClass} ${options.class}"></div>`);

            $inputGroup.append(`<input type="text" name="${options.name}" class="${options.class}" data-${options.data.id}="${options.data.value}" placeholder="${options.placeholder}">`);
            $inputGroup.append(`<button class="btn btn-primary ${options.class}"><i class="icon-plus"></i></button>`);
            $('.select2-dropdown').append($inputGroup);
        },

        returnResults: function(results, searchQuery, page, mapOptions) {
            const pageSize = 100;

            results = results.filter(function(element) {
                return new RegExp(searchQuery.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&"), "i")
                    .test(element[mapOptions.text].toLowerCase());
            })
            .map(function (element) {
                return {
                    id: element[mapOptions.id],
                    text: element[mapOptions.text]
                }
            });

            return {
                results: results.slice((page -1) * pageSize, page * pageSize),
                pagination: {
                    more: results.length >= page * pageSize
                }
            };
        },

        renderResults: function(text, searchQuery) {
            const match = text.toLowerCase().indexOf(searchQuery);
            const $result = $('<span></span>');

            if (match < 0) {
                return $result.text(text);
            }

            $result.text(text.substring(0, match));
            const $match = $('<span class="select2-rendered__match"></span>');

            $match.text(text.substring(match, match + searchQuery.length));
            $result.append($match);
            $result.append(text.substring(match + searchQuery.length));

            return $result;
        }
    }

    return ImporterSelect2;

}(jQuery));

x13Import.configurationDependencies = (function ($) {

    function ConfigurationDependencies() {
        handleFieldDependencies();

        var $fieldDependencies = getFieldDependencies();
        for (var i = 0; i < $fieldDependencies.length; i++) {
            $(document).off($fieldDependencies[i]).on('change', '[name="'+ $fieldDependencies[i] +'"]', function () {
                handleFieldDependencies($fieldDependencies[i]);
            }).bind(i);
        }
    }

    ConfigurationDependencies.prototype = {
        constructor: ConfigurationDependencies
    }

    function getFieldDependencies() {
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

    function handleFieldDependencies(specificFieldName) {
        var specificField = specificFieldName || false;
        $('.depends-on').each(function (index, node) {
            var $element = $(node);
            var $classes = $element.prop('class').split(/\s+/);
            var $method = 'match';
            var $fieldName = false,
                $fieldValue = false,
                $fieldType = false,
                $currentValue,
                $typeOfTheField;
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

                    if($('input[name="'+ $nameOfTheField +'"]').length > 0){
                        $typeOfTheField = $('input[name="'+ $nameOfTheField +'"]').attr('type');
                    }else if($('textarea[name="'+ $nameOfTheField +'"]').length === 1){
                        $typeOfTheField = 'textarea';
                    }else if($('select[name="'+ $nameOfTheField +'"]').length === 1){
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
                    if ($fieldType[i] === 'checkbox' || $fieldType[i] === 'radio'){
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
                    $element.show();
                } else {
                    $element.hide();
                }
            } else {
                if (specificField && specificField !== $fieldName) {
                    return;
                }

                if ($fieldType === 'checkbox' || $fieldType === 'radio'){
                    $currentValue = $('[name="'+ $fieldName +'"]:checked').val();
                } else if ($fieldType === 'select') {
                    $currentValue = $('[name="'+ $fieldName +'"] option:selected').val();
                } else {
                    $currentValue = $('[name="'+ $fieldName +'"]').val();
                }

                if ($method === 'not_match' && $fieldName && $fieldValue) {
                    if ($fieldValue.includes($currentValue)) {
                        $element.hide();
                    } else {
                        $element.show();
                    }
                }
                if ($method === 'match' && $fieldName && $fieldValue) {
                    if ($fieldValue.includes($currentValue)) {
                        $element.show();
                    } else {
                        $element.hide();
                    }
                }
            }
        });
    }

    function inArray(needle, haystack) {
        var length = haystack.length;
        for (var i = 0; i < length; i++) {
            if (haystack[i] === needle) return true;
        }
        return false;
    }

    return ConfigurationDependencies;

}(jQuery));
