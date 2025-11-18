(function ($, x13Import) {

    var IMPORT_LIMIT = 10;

    $(function () {
        var buttonCategoriesDownload = $('.x-categories-download').parent();
        var buttonCategoriesImport = $('.x-categories-import').parent();

        $(buttonCategoriesDownload).on('click', function(e) {
            if ($(this).attr('disabled')) {
                return false;
            }

            e.preventDefault();
            x13Import.disableProcessButton($(this));
            window.scrollTo(0, 0);

            x13Import.progressBlock.init('Pobieranie kategorii z hurtowni');

            var self = $(this);
            x13Import.getWholesalers(function(wholesalers) {
                var requestStatus = true;
                var requestLoop = function(index) {
                    if (index in wholesalers) {
                        categoriesDownload(wholesalers[index], function(status) {
                            requestStatus &= status;
                            requestLoop(++index);
                        });
                    } else {
                        x13Import.enableProcessButton(self);
                        if (requestStatus) {
                            x13Import.redirect(help_class_name, 'categoriesDownloadSuccess');
                        } else {
                            x13Import.progressBlock.messageBefore('W jednej z hurtowni wystąpił błąd podczas pobierania kategorii');
                            x13Import.progressBlock.refreshButton('Przeładuj stronę');
                        }
                    }
                }

                if (wholesalers.length) {
                    requestLoop(0);
                } else {
                    x13Import.progressBlock.description('Brak hurtowni');
                    x13Import.enableProcessButton(self);
                }
            });
        });

        $(buttonCategoriesImport).on('click', function(e) {
            if ($(this).attr('disabled')) {
                return false;
            }

            e.preventDefault();
            window.scrollTo(0, 0);

            x13Import.progressBlock.init('Import kategorii do sklepu', 'Wybierz metodę importu kategorii');
            categoriesImportForm();

            var self = $(this);
            $(document).on('click', 'button[name="submitCategoriesImportForm"]', function(e) {
                e.preventDefault();

                var categoriesInput; // PS 1.5 backward compatibility
                var categoriesImportMode = parseInt($('input[name="categoriesImportMode"]:checked').val());
                var categoriesImportDefault = parseInt($('select[name="categoriesImportDefault"]').val());
                var categoriesOverwrite = $('input[name="categoriesOverwrite"]:checked').length;
                var categoriesAssigned = [];
                var categoriesToAssign = [];

                if ($('#categoriesAssigned').length) {
                    categoriesInput = '#categoriesAssigned input:checked';
                } else {
                    categoriesInput = '#categories-treeview input:checked';
                }

                $(categoriesInput).each(function() {
                    categoriesAssigned.push(parseInt($(this).val()));
                });

                $('input[name="ximport_categoryBox[]"]:checked').each(function() {
                    categoriesToAssign.push(parseInt($(this).val()));
                });

                if (categoriesImportMode !== 1) {
                    if (categoriesAssigned.length === 0) {
                        alert('Wybierz kategorie sklepu do przypisania');
                    }
                    else if (categoriesToAssign.length === 0) {
                        alert('Wybierz kategorie z hurtowni do importu');
                    }
                    else {
                        var requestLoopSelected = function(index) {
                            x13Import.progressBlock.bar(Math.min(100, (index * 100) / categoriesToAssign.length));

                            if (index < categoriesToAssign.length) {
                                categoriesImportSelected(
                                    categoriesImportMode,
                                    categoriesOverwrite,
                                    categoriesAssigned,
                                    categoriesToAssign.slice(index, index + IMPORT_LIMIT),
                                    categoriesImportDefault,
                                    function() {
                                        requestLoopSelected(index + IMPORT_LIMIT);
                                    }
                                );
                            } else {
                                finishImport();
                            }
                        }

                        prepareProgressBlock();
                        requestLoopSelected(0);
                    }
                } else {
                    prepareProgressBlock();

                    categoriesImportCount(function(count) {
                        var requestLoop = function(index) {
                            x13Import.progressBlock.bar(Math.min(100, (index * 100) / count));

                            if (index < count) {
                                categoriesImport(IMPORT_LIMIT, function(finish) {
                                    if (!finish) {
                                        requestLoop(index + IMPORT_LIMIT);
                                    } else {
                                        finishImport();
                                    }
                                });
                            } else {
                                finishImport();
                            }
                        }

                        if (count) {
                            requestLoop(0);
                        } else {
                            x13Import.progressBlock.description('Brak kategorii do importu');
                            x13Import.progressBlock.bar();
                            x13Import.enableProcessButton(self);
                        }
                    });
                }

                function prepareProgressBlock() {
                    x13Import.disableProcessButton(self);
                    $('#categories_import_form').hide();
                    x13Import.progressBlock.description();
                    x13Import.progressBlock.bar(0);
                }

                function finishImport() {
                    categoriesRegenerateTree(function() {
                        x13Import.redirect(help_class_name, 'categoriesImportSuccess');
                    });
                }
            });
        });
    });

    function categoriesDownload(wholesaler, callback) {
        var ajaxData = {
            action: 'categoriesDownload',
            wholesaler: wholesaler
        };

        x13Import.ajax(
            ajaxData,
            function() {
                x13Import.progressBlock.wholesalerAction(wholesaler, x13Import.progressBlock.wholesalerActionProcess, 'pobieranie kategorii');
            },
            function(json) {
                if (typeof json.status === "undefined" && json.error) {
                    // backport to old wholesalers versions with die(['error'])
                    x13Import.progressBlock.wholesalerAction(wholesaler, x13Import.progressBlock.wholesalerActionError, json.error);
                } else if (json.status === true) {
                    x13Import.progressBlock.wholesalerAction(wholesaler, x13Import.progressBlock.wholesalerActionSuccess, 'zakończono');
                } else {
                    x13Import.progressBlock.wholesalerAction(wholesaler, x13Import.progressBlock.wholesalerActionError, json.message);
                }

                callback(json.status);
            }
        );
    }

    function categoriesImportForm() {
        var ajaxData = {
            action: 'categoriesImportForm'
        };

        x13Import.ajax(
            ajaxData,
            null,
            function(json) {
                x13Import.progressBlock.form(json.form);
            }
        );
    }

    function categoriesImportCount(callback) {
        var ajaxData = {
            action: 'categoriesImportCount'
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

    function categoriesImport(limit, callback) {
        var ajaxData = {
            action: 'categoriesImport',
            limit: limit
        };

        x13Import.ajax(
            ajaxData,
            null,
            function(json) {
                if (json.status !== true) {
                    // @todo
                }

                callback(json.finish);
            }
        );
    }

    function categoriesImportSelected(
        categoriesImportMode,
        categoriesOverwrite,
        categoriesAssigned,
        categoriesToAssign,
        categoriesImportDefault,
        callback
    ) {
        var ajaxData = {
            action: 'categoriesImportSelected',
            categoriesImportMode: categoriesImportMode,
            categoriesOverwrite: categoriesOverwrite,
            categoriesAssigned: categoriesAssigned,
            categoriesToAssign: categoriesToAssign,
            categoriesImportDefault: categoriesImportDefault
        };

        x13Import.ajax(
            ajaxData,
            null,
            function(json) {
                if (json.status !== true) {
                    // @todo
                }

                callback();
            }
        );
    }

    function categoriesRegenerateTree(callback) {
        var ajaxData = {
            action: 'categoriesRegenerateTree'
        };

        x13Import.ajax(
            ajaxData,
            null,
            function() {
                callback();
            }
        );
    }

}(jQuery, x13Import || {}));
