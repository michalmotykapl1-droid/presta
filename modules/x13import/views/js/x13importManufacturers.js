(function ($, x13Import) {

    const classes = {
        manufacturerCreate: 'manufacturer-create'
    };
    const selectors = {
        manufacturerList: `#list-ximport_manufacturer`,
        manufacturerListTable: `#table-ximport_manufacturer`,
        manufacturerName: `.column-manufacturer`,
        manufacturerImportOption: `.column-import_option select`,
        manufacturerSelect: `.column-id_manufacturer select`,
        manufacturerImportProductsOption: `.column-import_products_option select`,
        manufacturerCreate: `.${classes.manufacturerCreate}`,
        manufacturerCreateInput: `.${classes.manufacturerCreate} input[type="text"]`,
        manufacturerCreateButton: `.${classes.manufacturerCreate} button`,
        saveChangesButton: `.manufacturer-save-changes`,
        restoreChangesButton: `.manufacturer-restore-changes`,
        bulkSaveChangesButton: `#bulk_manufacturer_save_changes`,
        bulkRestoreChangesButton: `#bulk_manufacturer_restore_changes`,
        bulkManufacturer: '.bulkManufacturer', // PS bulk icon
        bulkManufacturerModal: '#bulk_manufacturer',
        bulkManufacturerImportOption: '#bulk_manufacturer #bulk_manufacturer_import_option',
        bulkManufacturerImportProductsOption: '#bulk_manufacturer #bulk_manufacturer_import_products_option',
        bulkManufacturerSubmit: '#bulk_manufacturer button[type="submit"]',
        wholesalerImportOption: `#wholesaler_manufacturer_import_option`,
        wholesalerImportProductsOption: `#wholesaler_manufacturer_import_products_option`,
        wholesalerManufacturerDownload: `#wholesaler_manufacturer_download`
    }

    const importerSelect2 = new x13Import.importerSelect2();
    importerSelect2.insertIntoCache('manufacturers', x13ImportDef.manufacturersOnLoad);

    let manufacturersToSave = [];

    $(function () {
        const overlayLoader = new x13Import.overlayLoader($(selectors.manufacturerList));

        let bulkManufacturer = false;
        if ($(selectors.bulkManufacturer).length > 0) {
            bulkManufacturer = $(selectors.bulkManufacturer).parent();
        }

        $(document).on('change', selectors.manufacturerImportOption, function () {
            const value = $(this).val();
            const $manufacturerSelect = $(this).closest('tr').find(selectors.manufacturerSelect);

            if (value === x13ImportDef.manufacturerImportOption.ASSIGN_VALUE.value) {
                $manufacturerSelect.show();
            } else {
                $manufacturerSelect.hide();
            }

            showActionButtons($(this).data('x-manufacturer-id'));
        });

        $(document).on('change', selectors.manufacturerImportProductsOption, function () {
            showActionButtons($(this).data('x-manufacturer-id'));
        });

        $(document).on('click', selectors.manufacturerSelect, function () {
            importerSelect2.init($(this), {
                cacheKey: 'manufacturers',
                ajaxData: {
                    action: 'searchManufacturers'
                },
                map: {
                    id: 'id_manufacturer',
                    text: 'name'
                }
            });
        });

        $(document).on('select2:open', selectors.manufacturerSelect, function () {
            const xManufacturerId = $(this).data('x-manufacturer-id');

            importerSelect2.createCustomInput({
                name: `manufacturer_create[${xManufacturerId}]`,
                class: classes.manufacturerCreate,
                placeholder: 'Utwórz nowego producenta',
                data: {
                    id: 'x-manufacturer-id',
                    value: xManufacturerId
                }
            });
        });

        $(document).on('select2:close', selectors.manufacturerSelect, function () {
            importerSelect2.destroy($(this));
        });

        $(document).on('change', selectors.manufacturerSelect, function () {
            showActionButtons($(this).data('x-manufacturer-id'));
        });

        $(document).on('keyup', selectors.manufacturerCreateInput, function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                $(selectors.manufacturerCreateButton).trigger('click');
            }
        });

        $(document).on('click', selectors.manufacturerCreateButton, function () {
            const $select2Dropdown = $('.select2-dropdown');
            const $input = $select2Dropdown.find(selectors.manufacturerCreateInput);
            const value = ($input.val()).trim();

            if (!value.length) {
                return;
            }

            $(this).attr('disabled', true).find('i').attr('class', 'icon-circle-o-notch icon-spin');
            $input.prop('disabled', true);

            const $manufacturerSelect = $(selectors.manufacturerListTable).find(`${selectors.manufacturerSelect}[data-x-manufacturer-id="${$input.data('x-manufacturer-id')}"]`);
            const result = importerSelect2.searchInCache('manufacturers', 'name', value);

            if (result) {
                importerSelect2.chooseNewOption($manufacturerSelect, result.id_manufacturer, result.name);
                showSuccessMessage(`Producent <b>${result.name}</b> już istnieje.<br>Ustawiono powiązanie.`);
            } else {
                x13Import.ajaxPOST({
                    action: 'createManufacturer',
                    name: value
                }, function (json) {
                    if (!json.status) {
                        showErrorMessage(json.message);
                    } else {
                        importerSelect2.pushIntoCache('manufacturers', {
                            id_manufacturer: json.manufacturerId,
                            name: value
                        }, 'name');

                        importerSelect2.chooseNewOption($manufacturerSelect, json.manufacturerId, value);
                        showSuccessMessage(`Utworzono producenta <b>${value}</b>.<br>Ustawiono powiązanie.`);
                    }
                });
            }
        });

        // -------------------------------------------------------------------------------------------------------------

        $(document).on('change', `${selectors.wholesalerImportOption}, ${selectors.wholesalerImportProductsOption}`, function () {
            const key = $(this).data('key');
            const value = $(this).val();

            x13Import.ajaxPOST({
                action: 'saveWholesalerOption',
                wholesalerTab: x13ImportDef.wholesalerTab,
                key: key,
                value: value
            }, function (json) {
                if (!json.status) {
                    showErrorMessage(json.message);
                } else {
                    showSuccessMessage('Zapisano domyślne ustawienia producentów dla hurtowni');
                }
            });
        });

        $(document).on('click', selectors.wholesalerManufacturerDownload, function (e) {
            e.preventDefault();

            overlayLoader.create();
            x13Import.disableButton($(this), false);
            x13Import.disableButton($(selectors.bulkSaveChangesButton));
            x13Import.disableButton($(selectors.bulkRestoreChangesButton), false);
            $(selectors.wholesalerImportOption).prop('disabled', true);
            $(selectors.wholesalerImportProductsOption).prop('disabled', true);

            x13Import.progressBlock.init('Pobieranie informacji o producentach');
            x13Import.progressBlock.wholesaler(x13ImportDef.wholesalerTab);
            x13Import.progressBlock.wholesalerAction(x13ImportDef.wholesalerTab, x13Import.progressBlock.wholesalerActionProcess, 'pobieranie');

            x13Import.ajaxPOST({
                action: 'downloadManufacturers',
                wholesalerTab: x13ImportDef.wholesalerTab
            }, function (json) {
                if (!json.status) {
                    x13Import.progressBlock.wholesalerAction(x13ImportDef.wholesalerTab, x13Import.progressBlock.wholesalerActionError, json.message);
                    x13Import.progressBlock.refreshButton('Przeładuj stronę');
                } else {
                    x13Import.redirect(help_class_name, [
                        'manufacturersDownloadSuccess',
                        'wholesalerTab=' + x13ImportDef.wholesalerTab
                    ]);
                }
            });
        });

        $(document).on('click', selectors.saveChangesButton, function (e) {
            e.preventDefault();

            saveManufacturerChanges($(this).data('x-manufacturer-id'));
        });

        $(document).on('click', selectors.restoreChangesButton, function (e) {
            e.preventDefault();

            restoreManufacturerChanges($(this).data('x-manufacturer-id'));
        });

        $(document).on('click', selectors.bulkSaveChangesButton, function (e) {
            e.preventDefault();

            overlayLoader.create();
            x13Import.disableButton($(selectors.bulkSaveChangesButton));
            x13Import.disableButton($(selectors.bulkRestoreChangesButton), false);
            x13Import.disableButton($(selectors.wholesalerManufacturerDownload), false);
            $(selectors.wholesalerImportOption).prop('disabled', true);
            $(selectors.wholesalerImportProductsOption).prop('disabled', true);

            processSave();

            function processSave() {
                if (manufacturersToSave.length) {
                    saveManufacturerChanges(manufacturersToSave[0], function () {
                        processSave();
                    });
                } else {
                    x13Import.enableButton($(selectors.bulkSaveChangesButton));
                    x13Import.enableButton($(selectors.bulkRestoreChangesButton));
                    x13Import.enableButton($(selectors.wholesalerManufacturerDownload));
                    $(selectors.wholesalerImportOption).prop('disabled', false);
                    $(selectors.wholesalerImportProductsOption).prop('disabled', false);
                    overlayLoader.destruct();
                }
            }
        });

        $(document).on('click', selectors.bulkRestoreChangesButton, function (e) {
            e.preventDefault();

            for (const xManufacturerId of manufacturersToSave.slice(0)) {
                restoreManufacturerChanges(xManufacturerId);
            }
        });

        if (bulkManufacturer) {
            bulkManufacturer.removeAttr('onclick');
            bulkManufacturer.on('click', function (e) {
                e.preventDefault();

                if ($('[name="ximport_manufacturerBox[]"]:checked').length > 0) {
                    $(selectors.bulkManufacturerImportOption).val(null);
                    $(selectors.bulkManufacturerImportProductsOption).val(null);
                    $(selectors.bulkManufacturerModal).modal('show');
                } else {
                    alert('Nie wybrano producentów do masowej zmiany.');
                }
            });

            $(selectors.bulkManufacturerSubmit).on('click', function (e) {
                e.preventDefault();

                const importOption = $(selectors.bulkManufacturerImportOption).val();
                const importProductsOption = $(selectors.bulkManufacturerImportProductsOption).val();

                if (importOption !== '' || importProductsOption !== '') {
                    $('[name="ximport_manufacturerBox[]"]:checked').each(function (index, element) {
                        const $manufacturerRow = $(element).closest('tr');

                        if (importOption !== '') {
                            $manufacturerRow.find(selectors.manufacturerImportOption).val(importOption).trigger('change');
                        }
                        if (importProductsOption !== '') {
                            $manufacturerRow.find(selectors.manufacturerImportProductsOption).val(importProductsOption).trigger('change');
                        }
                    });
                }

                $(selectors.bulkFeatureGroupModal).modal('hide');
            });
        }

        $(window).on('beforeunload', function () {
            if (manufacturersToSave.length) {
                return true;
            }
        });
    });

    function showActionButtons(xManufacturerId) {
        $(selectors.manufacturerListTable).find(`${selectors.saveChangesButton}[data-x-manufacturer-id="${xManufacturerId}"]`).parent().show();
        $(selectors.bulkSaveChangesButton).show();
        $(selectors.bulkRestoreChangesButton).show();

        if (!manufacturersToSave.includes(xManufacturerId)) {
            manufacturersToSave.push(xManufacturerId);
        }
    }

    function hideActionButtons(xManufacturerId) {
        $(selectors.manufacturerListTable).find(`${selectors.saveChangesButton}[data-x-manufacturer-id="${xManufacturerId}"]`).parent().hide();

        manufacturersToSave.splice(manufacturersToSave.indexOf(xManufacturerId), 1);
        if (!manufacturersToSave.length) {
            $(selectors.bulkSaveChangesButton).hide();
            $(selectors.bulkRestoreChangesButton).hide();
        }
    }

    function saveManufacturerChanges(xManufacturerId, loopCallback) {
        const $manufacturerRow = $(selectors.manufacturerListTable).find(`${selectors.manufacturerName}[data-x-manufacturer-id="${xManufacturerId}"]`).closest('tr');
        const $saveButton = $manufacturerRow.find(selectors.saveChangesButton);
        const $restoreButton = $manufacturerRow.find(selectors.restoreChangesButton);

        const overlayLoader = new x13Import.overlayLoader($manufacturerRow);
        overlayLoader.showSpinner = false;

        // show overlayLoader and lock wholesaler import option
        // only on single row save
        if (typeof loopCallback === 'undefined') {
            overlayLoader.create();

            $(selectors.wholesalerImportOption).prop('disabled', true);
            $(selectors.wholesalerImportProductsOption).prop('disabled', true);
            x13Import.disableButton($(selectors.wholesalerManufacturerDownload), false);
        }

        x13Import.disableButton($saveButton);
        x13Import.disableButton($restoreButton, false);

        const manufacturerId = $manufacturerRow.find(selectors.manufacturerSelect).val();
        const importOption = $manufacturerRow.find(selectors.manufacturerImportOption).val();
        const importProductsOption = $manufacturerRow.find(selectors.manufacturerImportProductsOption).val();

        x13Import.ajaxPOST({
            action: 'saveManufacturerMapping',
            data: {
                xManufacturerId: parseInt(xManufacturerId),
                manufacturerId: (manufacturerId && manufacturerId !== '' ? parseInt(manufacturerId) : null),
                importOption: importOption,
                importProductsOption: importProductsOption
            }
        }, function (json) {
            if (!json.status) {
                showErrorMessage(json.message);
            } else {
                if (json.id_manufacturer) {
                    importerSelect2.chooseNewOption($manufacturerRow.find(selectors.manufacturerSelect), json.id_manufacturer, json.name);
                } else {
                    $manufacturerRow.find(selectors.manufacturerSelect).val(null).trigger('change');
                }

                $manufacturerRow.find(selectors.manufacturerSelect).data('value', json.id_manufacturer).attr('data-value', json.id_manufacturer);
                $manufacturerRow.find(selectors.manufacturerImportOption).data('value', json.import_option).attr('data-value', json.import_option);
                $manufacturerRow.find(selectors.manufacturerImportProductsOption).data('value', json.import_products_option).attr('data-value', json.import_products_option);

                hideActionButtons(xManufacturerId);
            }

            x13Import.enableButton($saveButton);
            x13Import.enableButton($restoreButton);

            if (typeof loopCallback === 'function') {
                loopCallback();
            } else {
                overlayLoader.destruct();

                $(selectors.wholesalerImportOption).prop('disabled', false);
                $(selectors.wholesalerImportProductsOption).prop('disabled', false);
                x13Import.enableButton($(selectors.wholesalerManufacturerDownload));
            }
        });
    }

    function restoreManufacturerChanges(xManufacturerId) {
        const $manufacturerRow = $(selectors.manufacturerListTable).find(`${selectors.manufacturerName}[data-x-manufacturer-id="${xManufacturerId}"]`).closest('tr');
        const $manufacturerImportOption = $manufacturerRow.find(selectors.manufacturerImportOption);
        const $manufacturerSelect = $manufacturerRow.find(selectors.manufacturerSelect);
        const $manufacturerImportProductsOption = $manufacturerRow.find(selectors.manufacturerImportProductsOption);

        $manufacturerImportOption.val($manufacturerImportOption.data('value')).trigger('change');
        $manufacturerSelect.val($manufacturerSelect.data('value')).trigger('change');
        $manufacturerImportProductsOption.val($manufacturerImportProductsOption.data('value')).trigger('change');

        hideActionButtons(xManufacturerId);
    }

}(jQuery, x13Import || {}));
