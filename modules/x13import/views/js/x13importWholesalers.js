(function ($, x13Import) {

    var DELETE_LIMIT = 25;

    $(function () {
        $(document).on('change', 'input[name*="AUTO_META_TITLE"]', function () {
            if (parseInt($('input[name*="AUTO_META_TITLE"]:checked').val()) === 1) {
                $('input[name="wholesaler[' + $(this).attr('data-wholesaler') + '][AUTO_META_TITLE_SCHEMA]"]').closest('.form-group').show();
            } else {
                $('input[name="wholesaler[' + $(this).attr('data-wholesaler') + '][AUTO_META_TITLE_SCHEMA]"]').closest('.form-group').hide();
            }
        });

        $('input[name*="AUTO_META_TITLE"]').trigger('change');

        var buttonProductsDelete = $('.x-products-delete').parent();
        var buttonProductsOff = $('.x-products-off').parent();

        $(buttonProductsDelete).on('click', function(e) {
            if ($(this).attr('disabled')) {
                return false;
            }

            var wholesalerName = $(this).data('wholesaler');
            var wholesalerCode = $(this).data('wholesaler-code');
            var self = $(this);

            if (!confirm('Czy na pewno chcesz usunąć wszystkie produkty' + (wholesalerCode !== undefined ? ' z tej hurtowni' : '') + '?')) {
                return false;
            }

            e.preventDefault();
            x13Import.disableProcessButton($(this));
            window.scrollTo(0, 0);

            x13Import.progressBlock.init('Usuwanie produktów z hurtowni');

            if (wholesalerCode !== undefined) {
                countProducts(wholesalerCode, function(count) {
                    var requestLoop = function(index) {
                        x13Import.progressBlock.bar(Math.min(100, (index * 100) / count));

                        if (index < count) {
                            productsDelete(wholesalerName, wholesalerCode, DELETE_LIMIT, function(finish) {
                                if (!finish) {
                                    requestLoop(index + DELETE_LIMIT);
                                } else {
                                    x13Import.enableProcessButton(self);
                                    x13Import.progressBlock.wholesalerAction(wholesalerName, x13Import.progressBlock.wholesalerActionSuccess, 'zakończono, usunięte produkty: ' + count);
                                }
                            });
                        } else {
                            x13Import.enableProcessButton(self);
                            x13Import.progressBlock.wholesalerAction(wholesalerName, x13Import.progressBlock.wholesalerActionSuccess, 'zakończono, usunięte produkty: ' + count);
                        }
                    }

                    if (count) {
                        x13Import.progressBlock.wholesaler(wholesalerName);
                        requestLoop(0);
                    } else {
                        x13Import.progressBlock.description('Brak produktów do usunięcia');
                        x13Import.progressBlock.bar();
                        x13Import.progressBlock.close();
                        x13Import.enableProcessButton(self);
                    }
                });
            } else {
                countProducts('', function(count) {
                    var requestLoopAll = function(index) {
                        x13Import.progressBlock.bar(Math.min(100, (index * 100) / count));

                        if (index < count) {
                            productsDeleteAll(DELETE_LIMIT, function(finish) {
                                if (!finish) {
                                    requestLoopAll(index + DELETE_LIMIT);
                                } else {
                                    x13Import.enableProcessButton(self);
                                    x13Import.progressBlock.close();
                                    x13Import.progressBlock.description('Zakończono, usunięte produkty: ' + count);
                                }
                            });
                        } else {
                            x13Import.enableProcessButton(self);
                            x13Import.progressBlock.close();
                            x13Import.progressBlock.description('Zakończono, usunięte produkty: ' + count);
                        }
                    }

                    if (count) {
                        requestLoopAll(0);
                    } else {
                        x13Import.progressBlock.description('Brak produktów do usunięcia');
                        x13Import.progressBlock.bar();
                        x13Import.progressBlock.close();
                        x13Import.enableProcessButton(self);
                    }
                });
            }
        });

        $(buttonProductsOff).on('click', function(e) {
            if ($(this).attr('disabled')) {
                return false;
            }

            var wholesalerName = $(this).data('wholesaler');
            var wholesalerCode = $(this).data('wholesaler-code');
            var self = $(this);

            if (!confirm('Czy na pewno chcesz wyłączyć wszystkie produkty' + (wholesalerCode !== undefined ? ' z tej hurtowni' : '') + '?')) {
                return false;
            }

            e.preventDefault();
            x13Import.disableProcessButton($(this));
            window.scrollTo(0, 0);

            x13Import.progressBlock.init('Wyłączanie produktów z hurtowni');

            if (wholesalerCode !== undefined) {
                x13Import.progressBlock.wholesaler(wholesalerName);
                productsOff(wholesalerName, wholesalerCode, function() {
                    x13Import.progressBlock.close();
                    x13Import.enableProcessButton(self);
                });
            } else {
                productsOffAll(function() {
                    x13Import.progressBlock.close();
                    x13Import.enableProcessButton(self);
                });
            }
        });
    });

    function countProducts(wholesalerCode, callback) {
        var ajaxData = {
            action: 'countProducts',
            wholesalerCode: wholesalerCode
        };

        x13Import.ajax(
            ajaxData,
            null,
            function(json) {
                callback(json.count);
            },
            function() {
                callback(0);
            }
        );
    }

    function productsDelete(wholesalerName, wholesalerCode, limit, callback) {
        var ajaxData = {
            action: 'productsDelete',
            wholesalerCode: wholesalerCode,
            limit: limit
        };

        x13Import.ajax(
            ajaxData,
            function() {
                x13Import.progressBlock.wholesalerAction(wholesalerName, x13Import.progressBlock.wholesalerActionProcess, 'usuwanie produktów');
            },
            function(json) {
                callback(json.finish);
            }
        );
    }

    function productsDeleteAll(limit, callback) {
        var ajaxData = {
            action: 'productsDelete',
            limit: limit
        };

        x13Import.ajax(
            ajaxData,
            null,
            function(json) {
                callback(json.finish);
            }
        );
    }

    function productsOff(wholesalerName, wholesalerCode, callback) {
        var ajaxData = {
            action: 'productsOff',
            wholesalerCode: wholesalerCode
        };

        x13Import.ajax(
            ajaxData,
            function() {
                x13Import.progressBlock.wholesalerAction(wholesalerName, x13Import.progressBlock.wholesalerActionProcess, 'wyłącznie produktów');
            },
            function(json) {
                x13Import.progressBlock.wholesalerAction(wholesalerName, x13Import.progressBlock.wholesalerActionSuccess, 'zakończono, wyłączone produkty: ' + json.count);
                callback();
            }
        );
    }

    function productsOffAll(callback) {
        var ajaxData = {
            action: 'productsOff'
        };

        x13Import.ajax(
            ajaxData,
            null,
            function(json) {
                x13Import.progressBlock.description('Zakończono, wyłączone produkty: ' + json.count);
                callback();
            }
        );
    }

}(jQuery, x13Import || {}));
