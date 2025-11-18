(function( $ ){

$.fn.gridEditor = function( options ) {

    var self = this;
    var grideditor = self.data('grideditor');

    var MAX_ROWS = 100;
    var MAX_IMAGES = 16;
    
    /** Methods **/
    
    if (arguments[0] == 'getHtml') {
        if (grideditor) {
            grideditor.deinit(true);
            var html = self.html();
            grideditor.init(true);
            return html;
        } else {
            return self.html();
        }
    }
    
    /** Initialize plugin */

    self.each(function(baseIndex, baseElem) {
        baseElem = $(baseElem);

        var settings = $.extend({
            'custom_filter'     : '',
            'content_types'     : ['tinymce'],
            'source_textarea'   : ''
        }, options);

        // Elems
        var canvas,
            mainControls,
            addRowGroup,
            htmlTextArea
        ;
        var colClasses = ['col-md-'];
        var curColClassIndex = 0; // Index of the column class we are manipulating currently
        var curMaxImage = 0;
        var curMaxRow = 0;
        var imageSelectorOptions = '<option value="product_image" selected="selected">{product_image}</option>' +
            '<option value="manufacturer_image">{manufacturer_image}</option>';
        var imageSelector;
        additionalImages = $.parseJSON(additionalImages);
        var additionalImagesNumber = x13allegro_template_images_nb || 5;

        for (var i = 0; i < additionalImagesNumber; i++) {
            imageSelectorOptions += '<option value="additional_image_' + (i+1) + '" ' +
                (typeof additionalImages[i] !== 'undefined' && additionalImages[i] ? '' : 'disabled="disabled"') +
                '>{additional_image_' + (i+1) + '}</option>';
        }

        imageSelector = '<select class="ge-select-image" name="image_selector">' + imageSelectorOptions + '</select>';
        var imagePlaceholder =
            '<div class="ge-image-placeholder">' +
                '<div class="ge-image-placeholder-inner">' +
                    '<span class="glyphicon glyphicon-image"></span>' +
                    '<span class="ge-image-selector" data-selector="product_image" data-number="0">{product_image}</span>' + imageSelector +
                '</div>' +
            '</div>';
        
        // Copy html to sourceElement if a source textarea is given
        if (settings.source_textarea) {
            var sourceEl = $(settings.source_textarea);
            
            sourceEl.addClass('ge-html-output');
            htmlTextArea = sourceEl;
                
            if (sourceEl.val()) {
                baseElem.html(sourceEl.val());
            }
        }
        
        // Wrap content if it is non-bootstrap
        if (baseElem.children().length && !baseElem.find('div.row').length) {
            var children = baseElem.children();
            var newRow = $('<div class="row"><div class="col-md-12"/></div>').appendTo(baseElem);
            newRow.find('.col-md-12').append(children);
        }

        setup();
        init();

        function setup() {
            /* Setup canvas */
            canvas = baseElem.addClass('ge-canvas');
            
            if (typeof htmlTextArea === 'undefined' || !htmlTextArea.length) {
                htmlTextArea = $('<textarea class="ge-html-output"/>').insertBefore(canvas);
            }

            /* Create main controls*/
            mainControls = $('<div class="ge-mainControls" />').insertBefore(htmlTextArea);
            var wrapper = $('<div class="ge-wrapper ge-top" />').appendTo(mainControls);

            // Add row
            addRowGroup = $('<div class="btn-group" />').appendTo(wrapper);
            var btn = $('<a class="btn btn-default" />')
                .attr('title', 'Dodaj nowy wiersz')
                .on('click', function() {
                    if (curMaxRow < MAX_ROWS) {
                        ++curMaxRow;
                        var row = createRow().appendTo(canvas);
                        init();
                        row.find('.ge-default').trigger('click');
                    }
                    else {
                        showErrorMessage('Osiągnięto maksymalną liczbę wierszy na opis oferty.');
                    }
                })
                .appendTo(addRowGroup)
            ;

            btn.append('<span class="glyphicon glyphicon-plus-sign"/>');
            btn.append('<span>Dodaj nowy wiersz</span>');

            if (!settings.source_textarea.val().trim().length) {
                btn.trigger('click');
            }

            // Make controls fixed on scroll
            var $window = $(window);
            $window.on('scroll', function(e) {
                if (
                    $window.scrollTop() > mainControls.offset().top &&
                    $window.scrollTop() < canvas.offset().top + canvas.height()
                ) {
                    if (wrapper.hasClass('ge-top')) {
                        wrapper
                            .css({
                                left: wrapper.offset().left,
                                width: wrapper.outerWidth()
                            })
                            .removeClass('ge-top')
                            .addClass('ge-fixed')
                        ;
                    }
                } else {
                    if (wrapper.hasClass('ge-fixed')) {
                        wrapper
                            .css({ left: '', width: '' })
                            .removeClass('ge-fixed')
                            .addClass('ge-top')
                        ;
                    }
                }
            });

            /* Init RTE on click */
            canvas.on('click', '.ge-content', function(e) {
                var rte = getRTE($(this).data('ge-content-type'));
                if (rte) {
                    rte.init(settings, $(this));
                }
            });

            canvas.on('change', 'select[name="image_selector"]', function () {
                var val = $(this).val();
                var oldVal = $(this).parent().find('.ge-image-selector').attr('data-selector');

                if (val !== 'product_image' && canvas.find('.ge-image-selector[data-selector="' + val + '"]').length > 0) {
                    var duplicateEl = canvas.find('.ge-image-selector[data-selector="' + val + '"]').eq(0);
                    duplicateEl.data('selector', oldVal).attr('data-selector', oldVal).text('{'+ oldVal +'}');
                    duplicateEl.parent().find('select[name="image_selector"]').val(oldVal);
                }

                $(this).parent().find('.ge-image-selector').data('selector', val).attr('data-selector', val).text('{'+ val +'}');
            });
        }

        function reset(callback) {
            deinit(true);
            init(true);
            calcImages();
            callback();
        }

        function init(reset) {
            runFilter(true);
            canvas.addClass('ge-editing');
            addAllColClasses();
            //wrapContent();
            if (typeof reset === 'undefined') {
                createRowControls();
                initImageSelectors();
            }
            makeSortable();
            switchLayout(curColClassIndex);
        }

        function deinit(reset) {
            canvas.removeClass('ge-editing');
            var contents = canvas.find('.ge-content').each(function() {
                var content = $(this);
                getRTE(content.data('ge-content-type')).deinit(settings, content);
            });
            if (typeof reset === 'undefined') {
                canvas.find('.ge-tools-drawer').remove();
            }
            removeSortable();
            runFilter();
        }

        function createRowControls() {
            canvas.find('.row').each(function() {
                var row = $(this);
                var activeBtn = false;

                if (row.find('> .ge-tools-drawer').length) {
                    activeBtn = row.find('> .ge-tools-drawer > a.active').index();
                    row.find('> .ge-tools-drawer').remove();
                }

                var drawer = $('<div class="ge-tools-drawer" />').prependTo(row);

                createTool(drawer, 'Usuń wiersz', 'pull-right', 'glyphicon-trash', function() {
                    if (window.confirm('Delete row?')) {
                        row.slideUp(function() {
                            --curMaxRow;
                            row.remove();
                        });
                    }
                });

                createTool(drawer, 'Przenieś', 'ge-move pull-right', 'glyphicon-move');

                createTool(drawer, 'Tekst', 'ge-default', 'glyphicon-text', function()
                {
                    var el = $(this);
                    if (!el.hasClass('active'))
                    {
                        row.find('.ge-tools-drawer a').removeClass('active');
                        row.find('.column').remove();
                        row.append(createColumn(12));

                        reset(function() {
                            el.addClass('active');
                        });
                    }
                });

                createTool(drawer, 'Zdjęcie', '', 'glyphicon-image', function()
                {
                    var el = $(this);
                    if (!el.hasClass('active'))
                    {
                        row.find('.ge-tools-drawer a').removeClass('active');
                        row.find('.column').remove();
                        row.append(createColumn(12, true));

                        reset(function() {
                            el.addClass('active');
                        });
                    }
                });

                createTool(drawer, 'Tekst i zdjęcie', '', 'glyphicon-text-image', function()
                {
                    var el = $(this);
                    if (!el.hasClass('active'))
                    {
                        row.find('.ge-tools-drawer a').removeClass('active');
                        row.find('.column').remove();
                        row.append(createColumn(6)).append(createColumn(6, true));

                        reset(function() {
                            el.addClass('active');
                        });
                    }
                });

                createTool(drawer, 'Zdjęcie i tekst', '', 'glyphicon-image-text', function()
                {
                    var el = $(this);
                    if (!el.hasClass('active'))
                    {
                        row.find('.ge-tools-drawer a').removeClass('active');
                        row.find('.column').remove();
                        row.append(createColumn(6, true)).append(createColumn(6));

                        reset(function() {
                            el.addClass('active');
                        });
                    }
                });

                createTool(drawer, 'Dwa zdjęcia', '', 'glyphicon-image-image', function()
                {
                    var el = $(this);
                    if (!el.hasClass('active'))
                    {
                        row.find('.ge-tools-drawer a').removeClass('active');
                        row.find('.column').remove();
                        row.append(createColumn(6, true)).append(createColumn(6, true));

                        reset(function() {
                            el.addClass('active');
                        });
                    }
                });

                if (activeBtn) {
                    row.find('> .ge-tools-drawer > a').eq(activeBtn).addClass('active');
                }
            });
        }

        function createTool(drawer, title, className, iconClass, eventHandlers) {
            var tool = $('<a title="' + title + '" class="' + className + '"><span class="glyphicon ' + iconClass + '"></span></a>')
                .appendTo(drawer)
            ;
            if (typeof eventHandlers == 'function') {
                tool.on('click', eventHandlers);
            }
            if (typeof eventHandlers == 'object') {
                $.each(eventHandlers, function(name, func) {
                    tool.on(name, func);
                });
            }
        }

        function createDetails(container, cssClasses) {
            var detailsDiv = $('<div class="ge-details" />');

            $('<input class="ge-id" />')
                .attr('placeholder', 'id')
                .val(container.attr('id'))
                .attr('title', 'Set a unique identifier')
                .appendTo(detailsDiv)
            ;

            var classGroup = $('<div class="btn-group" />').appendTo(detailsDiv);
            cssClasses.forEach(function(rowClass) {
                var btn = $('<a class="btn btn-xs btn-default" />')
                    .html(rowClass.label)
                    .attr('title', rowClass.title ? rowClass.title : 'Toggle "' + rowClass.label + '" styling')
                    .toggleClass('active btn-primary', container.hasClass(rowClass.cssClass))
                    .on('click', function() {
                        btn.toggleClass('active btn-primary');
                        container.toggleClass(rowClass.cssClass, btn.hasClass('active'));
                    })
                    .appendTo(classGroup)
                ;
            });

            return detailsDiv;
        }

        function addAllColClasses() {
            canvas.find('.column, div[class*="col-"]').each(function() {
                var col = $(this);

                var size = 2;
                var sizes = getColSizes(col);
                if (sizes.length) {
                    size = sizes[0].size;
                }

                var elemClass = col.attr('class');
                colClasses.forEach(function(colClass) {
                    if (elemClass.indexOf(colClass) == -1) {
                        col.addClass(colClass + size);
                    }
                });

                col.addClass('column');
            });
        }

        /**
         * Return the column size for colClass, or a size from a different
         * class if it was not found.
         * Returns null if no size whatsoever was found.
         */
        function getColSize(col, colClass) {
            var sizes = getColSizes(col);
            for (var i = 0; i < sizes.length; i++) {
                if (sizes[i].colClass == colClass) {
                    return sizes[i].size;
                }
            }
            if (sizes.length) {
                return sizes[0].size;
            }
            return null;
        }

        function getColSizes(col) {
            var result = [];
            colClasses.forEach(function(colClass) {
                var re = new RegExp(colClass + '(\\d+)', 'i');
                if (re.test(col.attr('class'))) {
                    result.push({
                        colClass: colClass,
                        size: parseInt(re.exec(col.attr('class'))[1])
                    });
                }
            });
            return result;
        }

        function setColSize(col, colClass, size) {
            var re = new RegExp('(' + colClass + '(\\d+))', 'i');
            var reResult = re.exec(col.attr('class'));
            if (reResult && parseInt(reResult[2]) !== size) {
                col.switchClass(reResult[1], colClass + size, 50);
            } else {
                col.addClass(colClass + size);
            }
        }

        function makeSortable() {
            canvas.find('.row').sortable({
                items: '> .column',
                connectWith: '.ge-canvas .row',
                handle: '> .ge-tools-drawer .ge-move',
                start: sortStart,
                helper: 'clone'
            });
            canvas.add(canvas.find('.column')).sortable({
                items: '> .row, > .ge-content',
                connectsWith: '.ge-canvas, .ge-canvas .column',
                handle: '> .ge-tools-drawer .ge-move',
                start: sortStart,
                helper: 'clone'
            });

            function sortStart(e, ui) {
                ui.placeholder.css({ height: ui.item.outerHeight()});
            }
        }

        function removeSortable() {
            canvas.add(canvas.find('.column')).add(canvas.find('.row')).sortable('destroy');
        }

        function createRow() {
            return $('<div class="row" />');
        }

        function createColumn(size, image) {
            var column = $('<div/>').addClass(colClasses.map(function(c) { return c + size; }).join(' '));

            if (typeof image !== 'undefined' && image) {
                column.append(imagePlaceholder);
            } else {
                column.append(createDefaultContentWrapper().html(
                    getRTE(settings.content_types[0]).initialContent)
                );
            }

            return column;
        }

        function calcImages()
        {
            curMaxImage = 0;

            canvas.find('.row').each(function(index, row) {
                $(row).find('.column').each(function(index, col)
                {
                    var image = $(col).find('.ge-image-placeholder');

                    if (image.length)
                    {
                        ++curMaxImage;

                        if (curMaxImage > MAX_IMAGES) {
                            showErrorMessage('Osiągnięto maksymalną ilość zdjęć na opis oferty.');
                            $(row).find('a.ge-default').trigger('click');
                        } else {
                            image.find('.ge-image-selector').data('number', curMaxImage).attr('data-number', curMaxImage);
                        }
                    }
                });
            });
        }

        /**
         * Run custom content filter on init and deinit
         */
        function runFilter(isInit) {
            if (settings.custom_filter.length) {
                $.each(settings.custom_filter, function(key, func) {
                    if (typeof func == 'string') {
                        func = window[func];
                    }

                    func(canvas, isInit);
                });
            }
        }

        /**
         * Wrap column content in <div class="ge-content"> where neccesary
         */
        function wrapContent() {
            canvas.find('.column').each(function() {
                var col = $(this);
                var contents = $();
                col.children().each(function() {
                    var child = $(this);
                    if (child.is('.row, .ge-tools-drawer, .ge-content, .ge-image-placeholder')) {
                        doWrap(contents);
                    } else {
                        contents = contents.add(child);
                    }
                });
                doWrap(contents);
            });
        }
        function doWrap(contents) {
            if (contents.length) {
                var container = createDefaultContentWrapper().insertAfter(contents.last());
                contents.appendTo(container);
                contents = $();
            }
        }

        function createDefaultContentWrapper() {
            return $('<div/>')
                .addClass('ge-content ge-content-type-' + settings.content_types[0])
                .attr('data-ge-content-type', settings.content_types[0])
            ;
        }

        function initImageSelectors() {
            canvas.find('.column .ge-image-placeholder').each(function(index, image)
            {
                if (typeof $(image).find('.ge-image-selector').attr('data-selector') === 'undefined') {
                    $(image).find('.ge-image-selector').data('selector', 'product_image').attr('data-selector', 'product_image').text('{product_image}');
                }

                if ($(image).find('select[name="image_selector"]').length == 0) {
                    $(image).find('.ge-image-selector').after(imageSelector);
                }

                // change disabled selector
                var val = $(image).find('.ge-image-selector').attr('data-selector');
                if ($(image).find('select[name="image_selector"] option[value="' + val + '"]').attr('disabled')) {
                    val = 'product_image';
                }

                $(image).find('select[name="image_selector"]').val(val);
            });

            calcImages();
        }

        function switchLayout(colClassIndex) {
            curColClassIndex = colClassIndex;

            var layoutClasses = ['ge-layout-desktop', 'ge-layout-tablet', 'ge-layout-phone'];
            layoutClasses.forEach(function(cssClass, i) {
                canvas.toggleClass(cssClass, i == colClassIndex);
            });
        }
        
        function getRTE(type) {
            return $.fn.gridEditor.RTEs[type];
        }
        
        function clamp(input, min, max) {
            return Math.min(max, Math.max(min, input));
        }

        baseElem.data('grideditor', {
            init: init,
            deinit: deinit
        });

    });

    return self;

};

$.fn.gridEditor.RTEs = {};

})( jQuery );

(function() {
    $.fn.gridEditor.RTEs.tinymce = {
        init: function(settings, contentAreas) {
            if (!window.tinymce) {
                console.error('tinyMCE not available! Make sure you loaded the tinyMCE js file.');
            }
            if (!contentAreas.tinymce) {
                console.error('tinyMCE jquery integration not available! Make sure you loaded the jquery integration plugin.');
            }
            var self = this;
            contentAreas.each(function() {
                var contentArea = $(this);
                if (!contentArea.hasClass('active')) {
                    if (contentArea.html() == self.initialContent) {
                        contentArea.html('');
                    }
                    contentArea.addClass('active');
                    var configuration = $.extend(
                        {},
                        (settings.tinymce && settings.tinymce.config ? settings.tinymce.config : {}),
                        {
                            inline: true,
                            oninit: function(editor) {
                                // Bring focus to text field
                                $('#' + editor.settings.id).focus();
                                
                                // Call original oninit function, if one was passed in the config
                                var callback;
                                try {
                                    callback = settings.tinymce.config.oninit;
                                } catch (err) {
                                    // No callback passed
                                }
                                
                                if (callback) {
                                    callback.call(this);
                                }
                            }
                        }
                    );
                    var tiny = contentArea.tinymce(configuration);
                }
            });
        },

        deinit: function(settings, contentAreas) {
            contentAreas.filter('.active').each(function() {
                var contentArea = $(this);
                var tiny = contentArea.tinymce();
                if (tiny) {
                    tiny.remove();
                }
                contentArea
                    .removeClass('active')
                    .removeAttr('id')
                    .removeAttr('style')
                    .removeAttr('spellcheck')
                ;
            });
        },

        initialContent: '<p>Dodaj tekst...</p>'
    };
})();
