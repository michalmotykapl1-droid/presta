(function ($, x13Import) {

    const classes = {
        featureGroupCreate: 'feature-create',
        featureValueCreate: 'feature-value-create'
    };
    const selectors = {
        featureList: `#list-ximport_feature`,
        featureListTable: `#table-ximport_feature`,
        featureGroupName: `.column-name`,
        featureGroupImportOption: `.column-import_option select`,
        featureGroupSelect: `.column-id_feature select`,
        featureGroupImportAsCustom: `.column-import_as_custom input[type="checkbox"]`,
        featureGroupCreate: `.${classes.featureGroupCreate}`,
        featureGroupCreateInput: `.${classes.featureGroupCreate} input[type="text"]`,
        featureGroupCreateButton: `.${classes.featureGroupCreate} button`,
        featureValueRow: `.feature-value-row`,
        featureValueRowOption: `.feature-value-row-option`,
        featureValueImportDefaultOption: `.feature-value-row-option select`,
        featureValueBox: `.column-value input[type="checkbox"]`,
        featureValueImportOption: `.column-feature_value_import_option select`,
        featureValueSelect: `.column-id_feature_value select`,
        featureValueCreate: `.${classes.featureValueCreate}`,
        featureValueCreateInput: `.${classes.featureValueCreate} input[type="text"]`,
        featureValueCreateButton: `.${classes.featureValueCreate} button`,
        saveChangesButton: `.feature-save-changes`,
        restoreChangesButton: `.feature-restore-changes`,
        bulkFeatureGroup: '.bulkFeatureGroup', // PS bulk icon
        bulkFeatureGroupModal: '#bulk_feature_group',
        bulkFeatureGroupImportOption: '#bulk_feature_group #bulk_feature_group_import_option',
        bulkFeatureGroupImportAsCustom: '#bulk_feature_group #bulk_feature_group_import_as_custom',
        bulkFeatureGroupSubmit: '#bulk_feature_group button[type="submit"]',
        bulkFeatureValue: '.bulkFeatureValue', // PS bulk icon
        bulkFeatureValueModal: '#bulk_feature_value',
        bulkFeatureValueImportOption: '#bulk_feature_value #bulk_feature_value_import_option',
        bulkFeatureValueSubmit: '#bulk_feature_value button[type="submit"]',
        bulkSaveChangesButton: `#bulk_feature_save_changes`,
        bulkRestoreChangesButton: `#bulk_feature_restore_changes`,
        wholesalerImportOption: `#wholesaler_feature_import_option`,
        wholesalerFeatureDownload: `#wholesaler_feature_download`
    }

    const importerSelect2 = new x13Import.importerSelect2();
    importerSelect2.insertIntoCache('featureGroups', x13ImportDef.featureGroupsOnLoad);

    let featuresLoaded = [];
    let featuresToSave = [];
    let featureBoxChecked = false;

    $(function () {

        const overlayLoader = new x13Import.overlayLoader($(selectors.featureList));

        let bulkFeatureGroup = false;
        if ($(selectors.bulkFeatureGroup).length > 0) {
            bulkFeatureGroup = $(selectors.bulkFeatureGroup).parent();
        }

        let bulkFeatureValue = false;
        if ($(selectors.bulkFeatureValue).length > 0) {
            bulkFeatureValue = $(selectors.bulkFeatureValue).parent();
        }

        $(document).on('click', selectors.featureGroupName, function () {
            if ($(this).data('disabled')) {
                return;
            }

            $(this).data('disabled', true);

            const $self = $(this);
            const $featureRow = $self.closest('tr');
            const xFeatureId = $self.data('x-feature-id');

            if (!featuresLoaded.includes(xFeatureId)) {
                $self.find('i.icon-list').attr('class', 'icon-circle-o-notch icon-spin');

                x13Import.ajaxPOST({
                    action: 'loadFeaturesValues',
                    xFeatureId: xFeatureId,
                    featureId: $featureRow.find(selectors.featureGroupSelect).val(),
                    featureImportOption: $featureRow.find(selectors.featureGroupImportOption).val(),
                    isOdd: +$featureRow.hasClass('odd')
                }, function (json) {
                    $featureRow.after(json.featuresValuesList);
                    $self.data('disabled', false);
                    $self.find('i.icon-circle-o-notch').attr('class', 'icon-list');

                    featuresLoaded.push(xFeatureId);
                });
            } else {
                const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${xFeatureId}"]`);

                $featureValueRow.toggle();
                $featureValueRow.find(selectors.featureValueBox).prop('checked', false).trigger('change');
                $(selectors.featureListTable).find(`${selectors.featureValueRowOption}[data-x-feature-id="${xFeatureId}"]`).toggle();

                $self.data('disabled', false);
            }
        });

        // -------------------------------------------------------------------------------------------------------------

        $(document).on('change', selectors.featureGroupImportOption, function () {
            const value = $(this).val();
            const xFeatureId = $(this).data('x-feature-id');
            const $featureGroupSelect = $(this).closest('tr').find(selectors.featureGroupSelect);
            const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${xFeatureId}"]`);
            const $featureValueImportOption = $featureValueRow.find(selectors.featureValueImportOption);
            const $featureValueSelect = $featureValueRow.find(selectors.featureValueSelect);

            if (value === x13ImportDef.featureImportOption.ASSIGN_VALUE.value) {
                $featureGroupSelect.show();
            } else {
                $featureGroupSelect.hide();
            }

            if (value === x13ImportDef.featureImportOption.DONT_IMPORT.value) {
                if ($featureValueRow.length) {
                    $featureValueImportOption.addClass('readonly');
                    $featureValueSelect.addClass('readonly');
                }
            } else {
                if ($featureValueRow.length) {
                    $featureValueImportOption.removeClass('readonly');
                    $featureValueSelect.removeClass('readonly');
                }
            }

            showActionButtons(xFeatureId);
        });

        $(document).on('change', selectors.featureGroupImportAsCustom, function () {
            showActionButtons($(this).data('x-feature-id'));
        });

        $(document).on('click', selectors.featureGroupSelect, function () {
            importerSelect2.init($(this), {
                cacheKey: 'featureGroups',
                ajaxData: {
                    action: 'searchFeatures'
                },
                map: {
                    id: 'id_feature',
                    text: 'name'
                }
            });
        });

        $(document).on('select2:open', selectors.featureGroupSelect, function () {
            const xFeatureId = $(this).data('x-feature-id');

            importerSelect2.createCustomInput({
                name: `feature_group_create[${xFeatureId}]`,
                class: classes.featureGroupCreate,
                placeholder: 'Utwórz nową grupę cech',
                data: {
                    id: 'x-feature-id',
                    value: xFeatureId
                }
            });
        });

        $(document).on('select2:close', selectors.featureGroupSelect, function () {
            importerSelect2.destroy($(this));
        });

        $(document).on('change', selectors.featureGroupSelect, function () {
            const xFeatureId = $(this).data('x-feature-id');
            const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${xFeatureId}"]`);

            showActionButtons(xFeatureId);

            if (!$featureValueRow.length) {
                return;
            }

            $featureValueRow.data('feature-id', $(this).val()).attr('data-feature-id', $(this).val());
            $featureValueRow.find(selectors.featureValueSelect).val(null).trigger('change');
        });

        $(document).on('keyup', selectors.featureGroupCreateInput, function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                $(selectors.featureGroupCreateButton).trigger('click');
            }
        });

        $(document).on('click', selectors.featureGroupCreateButton, function () {
            const $select2Dropdown = $('.select2-dropdown');
            const $input = $select2Dropdown.find(selectors.featureGroupCreateInput);
            const value = ($input.val()).trim();

            if (!value.length) {
                return;
            }

            $(this).attr('disabled', true).find('i').attr('class', 'icon-circle-o-notch icon-spin');
            $input.prop('disabled', true);

            const $featureGroupSelect = $(selectors.featureListTable).find(`${selectors.featureGroupSelect}[data-x-feature-id="${$input.data('x-feature-id')}"]`);
            const result = importerSelect2.searchInCache('featureGroups', 'name', value);

            if (result) {
                importerSelect2.chooseNewOption($featureGroupSelect, result.id_feature, result.name);
                showSuccessMessage(`Grupa cech <b>${result.name}</b> już istnieje.<br>Ustawiono powiązanie.`);
            } else {
                x13Import.ajaxPOST({
                    action: 'createFeature',
                    name: value
                }, function (json) {
                    if (!json.status) {
                        showErrorMessage(json.message);
                    } else {
                        importerSelect2.pushIntoCache('featureGroups', {
                            id_feature: json.featureId,
                            name: value
                        }, 'name');

                        importerSelect2.chooseNewOption($featureGroupSelect, json.featureId, value);
                        showSuccessMessage(`Utworzono grupę cech <b>${value}</b>.<br>Ustawiono powiązanie.`);
                    }
                });
            }
        });

        // -------------------------------------------------------------------------------------------------------------

        $(document).on('change', selectors.featureValueBox, function () {
            const xFeatureId = $(this).closest('tr').data('x-feature-id');
            const $checked = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${xFeatureId}"] ${selectors.featureValueBox}:checked`);

            if (!$checked.length) {
                featureBoxChecked = false;
                return;
            }

            if (!featureBoxChecked) {
                featureBoxChecked = xFeatureId;
            }
            else if (featureBoxChecked !== xFeatureId) {
                $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${featureBoxChecked}"] ${selectors.featureValueBox}`).prop('checked', false);
                featureBoxChecked = xFeatureId;
            }
        });

        $(document).on('change', selectors.featureValueImportDefaultOption, function () {
            showActionButtons($(this).data('x-feature-id'));
        });

        $(document).on('change', selectors.featureValueImportOption, function () {
            const value = $(this).val();
            const $featureValueRow = $(this).closest('tr');
            const $featureValueSelect = $featureValueRow.find(selectors.featureValueSelect);

            if (value === x13ImportDef.featureValueImportOption.ASSIGN_VALUE.value) {
                $featureValueSelect.show();
            } else {
                $featureValueSelect.hide();
            }

            showActionButtons($featureValueRow.data('x-feature-id'));
        });

        $(document).on('click', selectors.featureValueSelect, function () {
            const featureId = $(this).closest('tr').data('feature-id');

            importerSelect2.init($(this), {
                cacheKey: 'featureValues_' + featureId,
                ajaxData: {
                    action: 'searchFeatureValues',
                    featureId: featureId
                },
                map: {
                    id: 'id_feature_value',
                    text: 'value'
                }
            });
        });

        $(document).on('select2:open', selectors.featureValueSelect, function () {
            const xFeatureValueId = $(this).data('x-feature-value-id');

            importerSelect2.createCustomInput({
                name: `feature_value_create[${xFeatureValueId}]`,
                class: classes.featureValueCreate,
                placeholder: 'Utwórz nową wartość cechy',
                data: {
                    id: 'x-feature-value-id',
                    value: xFeatureValueId
                }
            });
        });

        $(document).on('select2:close', selectors.featureValueSelect, function () {
            importerSelect2.destroy($(this));
        });

        $(document).on('change', selectors.featureValueSelect, function () {
            showActionButtons($(this).closest('tr').data('x-feature-id'));
        });

        $(document).on('keyup', selectors.featureValueCreateInput, function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                $(selectors.featureValueCreateButton).trigger('click');
            }
        });

        $(document).on('click', selectors.featureValueCreateButton, function () {
            const $select2Dropdown = $('.select2-dropdown');
            const $input = $select2Dropdown.find(selectors.featureValueCreateInput);
            const value = ($input.val()).trim();

            if (!value.length) {
                return;
            }

            $(this).attr('disabled', true).find('i').attr('class', 'icon-circle-o-notch icon-spin');
            $input.prop('disabled', true);

            const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-value-id="${$input.data('x-feature-value-id')}"]`);
            const xFeatureId = $featureValueRow.data('x-feature-id');
            let featureId = $featureValueRow.data('feature-id');

            const $featureGroupSelect = $(selectors.featureListTable).find(`${selectors.featureGroupSelect}[data-x-feature-id="${xFeatureId}"]`);
            const featureGroupName = $(selectors.featureListTable).find(`${selectors.featureGroupName}[data-x-feature-id="${xFeatureId}"] > span`).text();

            if (!featureId) {
                const result = importerSelect2.searchInCache('featureGroups', 'name', featureGroupName);

                if (result) {
                    featureId = result.id_feature;

                    // load FeatureValue to cache without reloading select2
                    x13Import.ajaxPOST({
                        action: 'searchFeaturesValues',
                        featureId: featureId
                    }, function (json) {
                        importerSelect2.insertIntoCache('featureValues_' + featureId, json);

                        // continue with creating FeatureValue
                        continueProcessingInput();
                    });

                    importerSelect2.chooseNewOption($featureGroupSelect, result.id_feature, result.name);
                    showSuccessMessage(`Grupa cech <b>${result.name}</b> już istnieje.<br>Ustawiono powiązanie.`);
                } else {
                    // continue with creating Feature & FeatureValue
                    continueProcessingInput();
                }
            } else {
                // continue with creating FeatureValue
                continueProcessingInput();
            }

            function continueProcessingInput() {
                const $featureValueSelect = $($featureValueRow).find(selectors.featureValueSelect);
                const result = importerSelect2.searchInCache('featureValues_' + featureId, 'value', value);

                if (result) {
                    importerSelect2.chooseNewOption($featureValueSelect, result.id_feature_value, result.value);
                    showSuccessMessage(`Wartość <b>${result.value}</b> dla grupy cech <b>${featureGroupName}</b> już istnieje.<br>Ustawiono powiązanie.`);
                } else {
                    x13Import.ajaxPOST({
                        action: 'createFeatureValue',
                        featureId: featureId,
                        name: featureGroupName,
                        value: value
                    }, function (json) {
                        if (!json.status) {
                            showErrorMessage(json.message);
                        } else {
                            if (!featureId) {
                                importerSelect2.pushIntoCache('featureGroups', {
                                    id_feature: json.featureId,
                                    name: featureGroupName
                                }, 'name');

                                importerSelect2.chooseNewOption($featureGroupSelect, json.featureId, featureGroupName);
                                showSuccessMessage(`Utworzono grupę cech <b>${featureGroupName}</b>.<br>Ustawiono powiązanie.`);
                            }

                            importerSelect2.pushIntoCache('featureValues_' + featureId, {
                                id_feature_value: json.featureValueId,
                                value: value
                            }, 'value');

                            importerSelect2.chooseNewOption($featureValueSelect, json.featureValueId, value);
                            showSuccessMessage(`Utworzono wartość <b>${value}</b> dla grupy cech <b>${featureGroupName}</b>.<br>Ustawiono powiązanie.`);
                        }
                    });
                }
            }
        });

        // -------------------------------------------------------------------------------------------------------------

        $(document).on('change', selectors.wholesalerImportOption, function () {
            const value = $(this).val();

            x13Import.ajaxPOST({
                action: 'saveWholesalerOption',
                wholesalerTab: x13ImportDef.wholesalerTab,
                value: value
            }, function (json) {
                if (!json.status) {
                    showErrorMessage(json.message);
                } else {
                    showSuccessMessage('Zapisano domyślne ustawienia cech dla hurtowni');
                }
            });
        });

        $(document).on('click', selectors.wholesalerFeatureDownload, function (e) {
            e.preventDefault();

            overlayLoader.create();
            x13Import.disableButton($(this), false);
            x13Import.disableButton($(selectors.bulkSaveChangesButton));
            x13Import.disableButton($(selectors.bulkRestoreChangesButton), false);
            $(selectors.wholesalerImportOption).prop('disabled', true);

            x13Import.progressBlock.init('Pobieranie informacji o cechach');
            x13Import.progressBlock.wholesaler(x13ImportDef.wholesalerTab);
            x13Import.progressBlock.wholesalerAction(x13ImportDef.wholesalerTab, x13Import.progressBlock.wholesalerActionProcess, 'pobieranie');

            x13Import.ajaxPOST({
                action: 'downloadFeatures',
                wholesalerTab: x13ImportDef.wholesalerTab
            }, function (json) {
                if (!json.status) {
                    x13Import.progressBlock.wholesalerAction(x13ImportDef.wholesalerTab, x13Import.progressBlock.wholesalerActionError, json.message);
                    x13Import.progressBlock.refreshButton('Przeładuj stronę');
                } else {
                    x13Import.redirect(help_class_name, [
                        'featuresDownloadSuccess',
                        'wholesalerTab=' + x13ImportDef.wholesalerTab
                    ]);
                }
            });
        });

        $(document).on('click', selectors.saveChangesButton, function (e) {
            e.preventDefault();

            saveFeatureChanges($(this).data('x-feature-id'));
        });

        $(document).on('click', selectors.restoreChangesButton, function (e) {
            e.preventDefault();

            restoreFeatureChanges($(this).data('x-feature-id'));
        });

        $(document).on('click', selectors.bulkSaveChangesButton, function (e) {
            e.preventDefault();

            overlayLoader.create();
            x13Import.disableButton($(selectors.bulkSaveChangesButton));
            x13Import.disableButton($(selectors.bulkRestoreChangesButton), false);
            x13Import.disableButton($(selectors.wholesalerFeatureDownload), false);
            $(selectors.wholesalerImportOption).prop('disabled', true);

            processSave();

            function processSave() {
                if (featuresToSave.length) {
                    saveFeatureChanges(featuresToSave[0], function () {
                        processSave();
                    });
                } else {
                    x13Import.enableButton($(selectors.bulkSaveChangesButton));
                    x13Import.enableButton($(selectors.bulkRestoreChangesButton));
                    x13Import.enableButton($(selectors.wholesalerFeatureDownload));
                    $(selectors.wholesalerImportOption).prop('disabled', false);
                    overlayLoader.destruct();
                }
            }
        });

        $(document).on('click', selectors.bulkRestoreChangesButton, function (e) {
            e.preventDefault();

            for (const xFeatureId of featuresToSave.slice(0)) {
                restoreFeatureChanges(xFeatureId);
            }
        });

        if (bulkFeatureGroup) {
            bulkFeatureGroup.removeAttr('onclick');
            bulkFeatureGroup.on('click', function (e) {
                e.preventDefault();

                if ($('[name="ximport_featureBox[]"]:checked').length > 0) {
                    $(selectors.bulkFeatureGroupImportOption).val(null);
                    $(selectors.bulkFeatureGroupImportAsCustom).val(null);
                    $(selectors.bulkFeatureGroupModal).modal('show');
                } else {
                    alert('Nie wybrano grup cech do masowej zmiany.');
                }
            });

            $(selectors.bulkFeatureGroupSubmit).on('click', function (e) {
                e.preventDefault();

                const importOption = $(selectors.bulkFeatureGroupImportOption).val();
                const importAsCustom = $(selectors.bulkFeatureGroupImportAsCustom).val();

                if (importOption !== '' || importAsCustom !== '') {
                    $('[name="ximport_featureBox[]"]:checked').each(function (index, element) {
                        const $featureGroupRow = $(element).closest('tr');

                        if (importOption !== '') {
                            $featureGroupRow.find(selectors.featureGroupImportOption).val(importOption).trigger('change');
                        }
                        if (importAsCustom !== '') {
                            $featureGroupRow.find(selectors.featureGroupImportAsCustom).prop('checked', !!parseInt(importAsCustom)).trigger('change');
                        }
                    });
                }

                $(selectors.bulkFeatureGroupModal).modal('hide');
            });
        }

        if (bulkFeatureValue) {
            bulkFeatureValue.removeAttr('onclick');
            bulkFeatureValue.on('click', function (e) {
                e.preventDefault();

                if (featureBoxChecked) {
                    $(selectors.bulkFeatureValueImportOption).val(null);
                    $(selectors.bulkFeatureValueModal).modal('show');
                } else {
                    alert('Nie wybrano wartości cech do masowej zmiany.');
                }
            });

            $(selectors.bulkFeatureValueSubmit).on('click', function (e) {
                e.preventDefault();

                const importOption = $(selectors.bulkFeatureValueImportOption).val();

                if (importOption !== '') {
                    const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${featureBoxChecked}"]`);
                    $featureValueRow.each(function (index, element) {
                        if ($(element).find(selectors.featureValueBox).is(':checked')) {
                            $(element).find(selectors.featureValueImportOption).val(importOption).trigger('change');
                        }
                    });
                }

                $(selectors.bulkFeatureValueModal).modal('hide');
            });
        }

        $(window).on('beforeunload', function () {
            if (featuresToSave.length) {
                return true;
            }
        });
    });

    function showActionButtons(xFeatureId) {
        $(selectors.featureListTable).find(`${selectors.saveChangesButton}[data-x-feature-id="${xFeatureId}"]`).parent().show();
        $(selectors.bulkSaveChangesButton).show();
        $(selectors.bulkRestoreChangesButton).show();

        if (!featuresToSave.includes(xFeatureId)) {
            featuresToSave.push(xFeatureId);
        }
    }

    function hideActionButtons(xFeatureId) {
        $(selectors.featureListTable).find(`${selectors.saveChangesButton}[data-x-feature-id="${xFeatureId}"]`).parent().hide();

        featuresToSave.splice(featuresToSave.indexOf(xFeatureId), 1);
        if (!featuresToSave.length) {
            $(selectors.bulkSaveChangesButton).hide();
            $(selectors.bulkRestoreChangesButton).hide();
        }
    }

    function saveFeatureChanges(xFeatureId, loopCallback) {
        const $featureRow = $(selectors.featureListTable).find(`${selectors.featureGroupName}[data-x-feature-id="${xFeatureId}"]`).closest('tr');
        const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${xFeatureId}"]`);
        const $featureValueOptionRow = $(selectors.featureListTable).find(`${selectors.featureValueRowOption}[data-x-feature-id="${xFeatureId}"]`);
        const $saveButton = $featureRow.find(selectors.saveChangesButton);
        const $restoreButton = $featureRow.find(selectors.restoreChangesButton);

        const overlayLoader = new x13Import.overlayLoader($featureRow, $featureValueRow, $featureValueOptionRow);
        overlayLoader.showSpinner = false;

        // show overlayLoader and lock wholesaler import option
        // only on single row save
        if (typeof loopCallback === 'undefined') {
            overlayLoader.create();

            $(selectors.wholesalerImportOption).prop('disabled', true);
            x13Import.disableButton($(selectors.wholesalerFeatureDownload), false);
        }

        x13Import.disableButton($saveButton);
        x13Import.disableButton($restoreButton, false);

        const featureId = $featureRow.find(selectors.featureGroupSelect).val();
        const importAsCustom = +$featureRow.find(selectors.featureGroupImportAsCustom).is(':checked');
        const importOption = $featureRow.find(selectors.featureGroupImportOption).val();
        let importOptionDefault = null;

        if ($featureValueOptionRow.length) {
            importOptionDefault = $featureValueOptionRow.find('select').val();
        }

        let featureValues = [];
        if ($featureValueRow.length) {
            $featureValueRow.each(function (index, element) {
                const featureValueId = $(element).find(selectors.featureValueSelect).val();

                featureValues.push({
                    xFeatureValueId: parseInt($(element).data('x-feature-value-id')),
                    featureValueId: (featureValueId && featureValueId !== '' ? parseInt(featureValueId) : null),
                    importOption: $(element).find(selectors.featureValueImportOption).val()
                });
            });
        }

        x13Import.ajaxPOST({
            action: 'saveFeatureMapping',
            data: {
                xFeatureId: parseInt(xFeatureId),
                featureId: (featureId && featureId !== '' ? parseInt(featureId) : null),
                importAsCustom: importAsCustom,
                importOption: importOption,
                importOptionDefault: importOptionDefault,
                featureValues: featureValues
            }
        }, function (json) {
            if (!json.status) {
                showErrorMessage(json.message);
            } else {
                if (json.id_feature) {
                    importerSelect2.chooseNewOption($featureRow.find(selectors.featureGroupSelect), json.id_feature, json.name);
                } else {
                    $featureRow.find(selectors.featureGroupSelect).val(null).trigger('change');
                }

                $featureRow.find(selectors.featureGroupImportAsCustom).data('checked', json.import_as_custom).attr('data-checked', json.import_as_custom);
                $featureRow.find(selectors.featureGroupImportOption).data('value', json.import_option).attr('data-value', json.import_option);
                $featureRow.find(selectors.featureGroupSelect).data('value', json.id_feature).attr('data-value', json.id_feature);

                if ($featureValueOptionRow.length) {
                    $featureValueOptionRow.find('select').data('value', json.import_default_option).attr('data-value', json.import_default_option);
                }

                if ($featureValueRow.length && 'featureValues' in json) {
                    for (const featureValue of json.featureValues) {
                        const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-value-id="${featureValue.id}"]`);

                        if (json.id_feature && featureValue.id_feature_value) {
                            if (!importerSelect2.searchInCache('featureValues_' + json.id_feature, 'value', featureValue.value)) {
                                importerSelect2.pushIntoCache('featureValues_' + json.id_feature, {
                                    id_feature_value: featureValue.id_feature_value,
                                    value: featureValue.value
                                }, 'value');
                            }

                            importerSelect2.chooseNewOption($featureValueRow.find(selectors.featureValueSelect), featureValue.id_feature_value, featureValue.value);
                        } else {
                            $featureValueRow.find(selectors.featureValueSelect).val(null).trigger('change');
                        }

                        $featureValueRow.find(selectors.featureValueImportOption).data('value', featureValue.import_option).attr('data-value', featureValue.import_option);
                        $featureValueRow.find(selectors.featureValueSelect).data('value', featureValue.id_feature_value).attr('data-value', featureValue.id_feature_value);
                    }
                }

                hideActionButtons(xFeatureId);
            }

            x13Import.enableButton($saveButton);
            x13Import.enableButton($restoreButton);

            if (typeof loopCallback === 'function') {
                loopCallback();
            } else {
                overlayLoader.destruct();

                $(selectors.wholesalerImportOption).prop('disabled', false);
                x13Import.enableButton($(selectors.wholesalerFeatureDownload));
            }
        });
    }

    function restoreFeatureChanges(xFeatureId) {
        const $featureRow = $(selectors.featureListTable).find(`${selectors.featureGroupName}[data-x-feature-id="${xFeatureId}"]`).closest('tr');
        const $featureValueRow = $(selectors.featureListTable).find(`${selectors.featureValueRow}[data-x-feature-id="${xFeatureId}"]`);
        const $featureValueImportDefaultOption = $(selectors.featureListTable).find(`${selectors.featureValueImportDefaultOption}[data-x-feature-id="${xFeatureId}"]`);
        const $featureGroupImportOption = $featureRow.find(selectors.featureGroupImportOption);
        const $featureGroupSelect = $featureRow.find(selectors.featureGroupSelect);
        const $featureGroupImportAsCustom = $featureRow.find(selectors.featureGroupImportAsCustom);

        $featureGroupImportOption.val($featureGroupImportOption.data('value')).trigger('change');
        $featureGroupSelect.val($featureGroupSelect.data('value')).trigger('change');
        $featureGroupImportAsCustom.prop('checked', !!$featureGroupImportAsCustom.data('checked')).trigger('change');

        if ($featureValueImportDefaultOption.length) {
            $featureValueImportDefaultOption.val($featureValueImportDefaultOption.data('value')).trigger('change');
        }

        if ($featureValueRow.length) {
            $featureValueRow.each(function (index, element) {
                const $featureValueImportOption = $(element).find(selectors.featureValueImportOption);
                const $featureValueSelect = $(element).find(selectors.featureValueSelect);

                $featureValueImportOption.val($featureValueImportOption.data('value')).trigger('change');
                $featureValueSelect.val($featureValueSelect.data('value')).trigger('change');
            });
        }

        hideActionButtons(xFeatureId);
    }

}(jQuery, x13Import || {}));
