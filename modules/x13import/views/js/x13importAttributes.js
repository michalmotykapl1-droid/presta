(function ($, x13Import) {

    const classes = {
        attributeGroupCreate: 'attribute-group-create',
        attributeCreate: 'attribute-create'
    };
    const selectors = {
        attributeList: `#list-ximport_attribute_gorup`,
        attributeListTable: `#table-ximport_attribute_group`,
        attributeGroupName: `.column-group_name`,
        attributeGroupImportOption: `.column-import_option select`,
        attributeGroupSelect: `.column-id_attribute_group select`,
        attributeGroupCreate: `.${classes.attributeGroupCreate}`,
        attributeGroupCreateInput: `.${classes.attributeGroupCreate} input[type="text"]`,
        attributeGroupCreateButton: `.${classes.attributeGroupCreate} button`,
        attributeRow: `.attribute-row`,
        attributeRowOption: `.attribute-row-option`,
        attributeImportDefaultOption: `.attribute-row-option select`,
        attributeBox: `.column-name input[type="checkbox"]`,
        attributeImportOption: `.column-attribute_import_option select`,
        attributeSelect: `.column-id_attribute select`,
        attributeCreate: `.${classes.attributeCreate}`,
        attributeCreateInput: `.${classes.attributeCreate} input[type="text"]`,
        attributeCreateButton: `.${classes.attributeCreate} button`,
        saveChangesButton: `.attribute-save-changes`,
        restoreChangesButton: `.attribute-restore-changes`,
        bulkAttributeGroup: '.bulkAttributeGroup', // PS bulk icon
        bulkAttributeGroupModal: '#bulk_attribute_group',
        bulkAttributeGroupImportOption: '#bulk_attribute_group #bulk_attribute_group_import_option',
        bulkAttributeGroupSubmit: '#bulk_attribute_group button[type="submit"]',
        bulkAttribute: '.bulkAttribute', // PS bulk icon
        bulkAttributeModal: '#bulk_attribute',
        bulkAttributeImportOption: '#bulk_attribute #bulk_attribute_import_option',
        bulkAttributeSubmit: '#bulk_attribute button[type="submit"]',
        bulkSaveChangesButton: `#bulk_attribute_save_changes`,
        bulkRestoreChangesButton: `#bulk_attribute_restore_changes`,
        wholesalerImportOption: `#wholesaler_attribute_import_option`,
        wholesalerAttributeDownload: `#wholesaler_attribute_download`
    }

    const importerSelect2 = new x13Import.importerSelect2();
    importerSelect2.insertIntoCache('attributeGroups', x13ImportDef.attributeGroupsOnLoad);

    let attributesLoaded = [];
    let attributesToSave = [];
    let attributeBoxChecked = false;

    $(function () {

        const overlayLoader = new x13Import.overlayLoader($(selectors.attributeList));

        let bulkAttributeGroup = false;
        if ($(selectors.bulkAttributeGroup).length > 0) {
            bulkAttributeGroup = $(selectors.bulkAttributeGroup).parent();
        }

        let bulkAttribute = false;
        if ($(selectors.bulkAttribute).length > 0) {
            bulkAttribute = $(selectors.bulkAttribute).parent();
        }

        $(document).on('click', selectors.attributeGroupName, function () {
            if ($(this).data('disabled')) {
                return;
            }

            $(this).data('disabled', true);

            const $self = $(this);
            const $attributeGroupRow = $self.closest('tr');
            const xAttributeGroupId = $self.data('x-attribute-group-id');

            if (!attributesLoaded.includes(xAttributeGroupId)) {
                $self.find('i.icon-list').attr('class', 'icon-circle-o-notch icon-spin');

                x13Import.ajaxPOST({
                    action: 'loadAttributeNames',
                    xAttributeGroupId: xAttributeGroupId,
                    attributeGroupId: $attributeGroupRow.find(selectors.attributeGroupSelect).val(),
                    attributeGroupImportOption: $attributeGroupRow.find(selectors.attributeGroupImportOption).val(),
                    isOdd: +$attributeGroupRow.hasClass('odd')
                }, function (json) {
                    $attributeGroupRow.after(json.attributeNamesList);
                    $self.data('disabled', false);
                    $self.find('i.icon-circle-o-notch').attr('class', 'icon-list');

                    attributesLoaded.push(xAttributeGroupId);
                });
            } else {
                const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${xAttributeGroupId}"]`);

                $attributeRow.toggle();
                $attributeRow.find(selectors.attributeBox).prop('checked', false).trigger('change');
                $(selectors.attributeListTable).find(`${selectors.attributeRowOption}[data-x-attribute-group-id="${xAttributeGroupId}"]`).toggle();

                $self.data('disabled', false);
            }
        });

        // -------------------------------------------------------------------------------------------------------------

        $(document).on('change', selectors.attributeGroupImportOption, function () {
            const value = $(this).val();
            const xAttributeGroupId = $(this).data('x-attribute-group-id');
            const $attributeGroupSelect = $(this).closest('tr').find(selectors.attributeGroupSelect);
            const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${xAttributeGroupId}"]`);
            const $attributeImportOption = $attributeRow.find(selectors.attributeImportOption);
            const $attributeSelect = $attributeRow.find(selectors.attributeSelect);

            if (value === x13ImportDef.attributeGroupImportOption.ASSIGN_VALUE.value) {
                $attributeGroupSelect.show();
            } else {
                $attributeGroupSelect.hide();
            }

            if (value === x13ImportDef.attributeGroupImportOption.DONT_IMPORT.value) {
                if ($attributeRow.length) {
                    $attributeImportOption.addClass('readonly');
                    $attributeSelect.addClass('readonly');
                }
            } else {
                if ($attributeRow.length) {
                    $attributeImportOption.removeClass('readonly');
                    $attributeSelect.removeClass('readonly');
                }
            }

            showActionButtons(xAttributeGroupId);
        });

        $(document).on('click', selectors.attributeGroupSelect, function () {
            importerSelect2.init($(this), {
                cacheKey: 'attributeGroups',
                ajaxData: {
                    action: 'searchAttributeGroups'
                },
                map: {
                    id: 'id_attribute_group',
                    text: 'name'
                }
            });
        });

        $(document).on('select2:open', selectors.attributeGroupSelect, function () {
            const xAttributeGroupId = $(this).data('x-attribute-group-id');

            importerSelect2.createCustomInput({
                name: `attribute_group_create[${xAttributeGroupId}]`,
                class: classes.attributeGroupCreate,
                placeholder: 'Utwórz nową grupę atrybutów',
                data: {
                    id: 'x-attribute-group-id',
                    value: xAttributeGroupId
                }
            });
        });

        $(document).on('select2:close', selectors.attributeGroupSelect, function () {
            importerSelect2.destroy($(this));
        });

        $(document).on('change', selectors.attributeGroupSelect, function () {
            const xAttributeGroupId = $(this).data('x-attribute-group-id');
            const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${xAttributeGroupId}"]`);

            showActionButtons(xAttributeGroupId);

            if (!$attributeRow.length) {
                return;
            }

            $attributeRow.data('attribute-group-id', $(this).val()).attr('data-attribute-group-id', $(this).val());
            $attributeRow.find(selectors.attributeSelect).val(null).trigger('change');
        });

        $(document).on('keyup', selectors.attributeGroupCreateInput, function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                $(selectors.attributeGroupCreateButton).trigger('click');
            }
        });

        $(document).on('click', selectors.attributeGroupCreateButton, function () {
            const $select2Dropdown = $('.select2-dropdown');
            const $input = $select2Dropdown.find(selectors.attributeGroupCreateInput);
            const value = ($input.val()).trim();

            if (!value.length) {
                return;
            }

            $(this).attr('disabled', true).find('i').attr('class', 'icon-circle-o-notch icon-spin');
            $input.prop('disabled', true);

            const $attributeGroupSelect = $(selectors.attributeListTable).find(`${selectors.attributeGroupSelect}[data-x-attribute-group-id="${$input.data('x-attribute-group-id')}"]`);
            const result = importerSelect2.searchInCache('attributeGroups', 'name', value);

            if (result) {
                importerSelect2.chooseNewOption($attributeGroupSelect, result.id_attribute_group, result.name);
                showSuccessMessage(`Grupa atrybutów <b>${result.name}</b> już istnieje.<br>Ustawiono powiązanie.`);
            } else {
                x13Import.ajaxPOST({
                    action: 'createAttributeGroup',
                    name: value
                }, function (json) {
                    if (!json.status) {
                        showErrorMessage(json.message);
                    } else {
                        importerSelect2.pushIntoCache('attributeGroups', {
                            id_attribute_group: json.attributeGroupId,
                            name: value
                        }, 'name');

                        importerSelect2.chooseNewOption($attributeGroupSelect, json.attributeGroupId, value);
                        showSuccessMessage(`Utworzono grupę atrybutów <b>${value}</b>.<br>Ustawiono powiązanie.`);
                    }
                });
            }
        });

        // -------------------------------------------------------------------------------------------------------------

        $(document).on('change', selectors.attributeBox, function () {
            const xAttributeGroupId = $(this).closest('tr').data('x-attribute-group-id');
            const $checked = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${xAttributeGroupId}"] ${selectors.attributeBox}:checked`);

            if (!$checked.length) {
                attributeBoxChecked = false;
                return;
            }

            if (!attributeBoxChecked) {
                attributeBoxChecked = xAttributeGroupId;
            }
            else if (attributeBoxChecked !== xAttributeGroupId) {
                $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${attributeBoxChecked}"] ${selectors.attributeBox}`).prop('checked', false);
                attributeBoxChecked = xAttributeGroupId;
            }
        });

        $(document).on('change', selectors.attributeImportDefaultOption, function () {
            showActionButtons($(this).data('x-attribute-group-id'));
        });

        $(document).on('change', selectors.attributeImportOption, function () {
            const value = $(this).val();
            const $attributeRow = $(this).closest('tr');
            const $attributeSelect = $attributeRow.find(selectors.attributeSelect);

            if (value === x13ImportDef.attributeGroupImportOption.ASSIGN_VALUE.value) {
                $attributeSelect.show();
            } else {
                $attributeSelect.hide();
            }

            showActionButtons($attributeRow.data('x-attribute-group-id'));
        });

        $(document).on('click', selectors.attributeSelect, function () {
            const attributeGroupId = $(this).closest('tr').data('attribute-group-id');

            importerSelect2.init($(this), {
                cacheKey: 'attributes_' + attributeGroupId,
                ajaxData: {
                    action: 'searchAttributeNames',
                    attributeGroupId: attributeGroupId
                },
                map: {
                    id: 'id_attribute',
                    text: 'name'
                }
            });
        });

        $(document).on('select2:open', selectors.attributeSelect, function () {
            const xAttributeId = $(this).data('x-attribute-id');

            importerSelect2.createCustomInput({
                name: `attribute_create[${xAttributeId}]`,
                class: classes.attributeCreate,
                placeholder: 'Utwórz nową wartość atrybutu',
                data: {
                    id: 'x-attribute-id',
                    value: xAttributeId
                }
            });
        });

        $(document).on('select2:close', selectors.attributeSelect, function () {
            importerSelect2.destroy($(this));
        });

        $(document).on('change', selectors.attributeSelect, function () {
            showActionButtons($(this).closest('tr').data('x-attribute-group-id'));
        });

        $(document).on('keyup', selectors.attributeCreateInput, function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                $(selectors.attributeCreateButton).trigger('click');
            }
        });

        $(document).on('click', selectors.attributeCreateButton, function () {
            const $select2Dropdown = $('.select2-dropdown');
            const $input = $select2Dropdown.find(selectors.attributeCreateInput);
            const value = ($input.val()).trim();

            if (!value.length) {
                return;
            }

            $(this).attr('disabled', true).find('i').attr('class', 'icon-circle-o-notch icon-spin');
            $input.prop('disabled', true);

            const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-id="${$input.data('x-attribute-id')}"]`);
            const xAttributeGroupId = $attributeRow.data('x-attribute-group-id');
            let attributeGroupId = $attributeRow.data('attribute-group-id');

            const $attributeGroupSelect = $(selectors.attributeListTable).find(`${selectors.attributeGroupSelect}[data-x-attribute-group-id="${xAttributeGroupId}"]`);
            const attributeGroupName = $(selectors.attributeListTable).find(`${selectors.attributeGroupName}[data-x-attribute-group-id="${xAttributeGroupId}"] > span`).text();

            if (!attributeGroupId) {
                const result = importerSelect2.searchInCache('attributeGroups', 'name', attributeGroupName);

                if (result) {
                    attributeGroupId = result.id_attribute_group;

                    // load Attribute to cache without reloading select2
                    x13Import.ajaxPOST({
                        action: 'searchAttributeNames',
                        attributeGroupId: attributeGroupId
                    }, function (json) {
                        importerSelect2.insertIntoCache('attributes_' + attributeGroupId, json);

                        // continue with creating Attribute
                        continueProcessingInput();
                    });

                    importerSelect2.chooseNewOption($attributeGroupSelect, result.id_attribute_group, result.name);
                    showSuccessMessage(`Grupa atrybutów <b>${result.name}</b> już istnieje.<br>Ustawiono powiązanie.`);
                } else {
                    // continue with creating AttributeGroup & Attribute
                    continueProcessingInput();
                }
            } else {
                // continue with creating Attribute
                continueProcessingInput();
            }

            function continueProcessingInput() {
                const $attributeSelect = $($attributeRow).find(selectors.attributeSelect);
                const result = importerSelect2.searchInCache('attributes_' + attributeGroupId, 'name', value);

                if (result) {
                    importerSelect2.chooseNewOption($attributeSelect, result.id_attribute, result.name);
                    showSuccessMessage(`Wartość <b>${result.name}</b> dla grupy atrybutów <b>${attributeGroupName}</b> już istnieje.<br>Ustawiono powiązanie.`);
                } else {
                    x13Import.ajaxPOST({
                        action: 'createAttribute',
                        attributeGroupId: attributeGroupId,
                        groupName: attributeGroupName,
                        name: value
                    }, function (json) {
                        if (!json.status) {
                            showErrorMessage(json.message);
                        } else {
                            if (!attributeGroupId) {
                                importerSelect2.pushIntoCache('attributeGroups', {
                                    id_attribute_group: json.attributeGroupId,
                                    name: attributeGroupName
                                }, 'name');

                                importerSelect2.chooseNewOption($attributeGroupSelect, json.attributeGroupId, attributeGroupName);
                                showSuccessMessage(`Utworzono grupę atrybutów <b>${attributeGroupName}</b>.<br>Ustawiono powiązanie.`);
                            }

                            importerSelect2.pushIntoCache('attributes_' + attributeGroupId, {
                                id_attribute: json.attributeId,
                                name: value
                            }, 'name');

                            importerSelect2.chooseNewOption($attributeSelect, json.attributeId, value);
                            showSuccessMessage(`Utworzono wartość <b>${value}</b> dla grupy atrybutów <b>${attributeGroupName}</b>.<br>Ustawiono powiązanie.`);
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
                    showSuccessMessage('Zapisano domyślne ustawienia atrybutów dla hurtowni');
                }
            });
        });

        $(document).on('click', selectors.wholesalerAttributeDownload, function (e) {
            e.preventDefault();

            overlayLoader.create();
            x13Import.disableButton($(this), false);
            x13Import.disableButton($(selectors.bulkSaveChangesButton));
            x13Import.disableButton($(selectors.bulkRestoreChangesButton), false);
            $(selectors.wholesalerImportOption).prop('disabled', true);

            x13Import.progressBlock.init('Pobieranie informacji o atrybutach');
            x13Import.progressBlock.wholesaler(x13ImportDef.wholesalerTab);
            x13Import.progressBlock.wholesalerAction(x13ImportDef.wholesalerTab, x13Import.progressBlock.wholesalerActionProcess, 'pobieranie');

            x13Import.ajaxPOST({
                action: 'downloadAttributes',
                wholesalerTab: x13ImportDef.wholesalerTab
            }, function (json) {
                if (!json.status) {
                    x13Import.progressBlock.wholesalerAction(x13ImportDef.wholesalerTab, x13Import.progressBlock.wholesalerActionError, json.message);
                    x13Import.progressBlock.refreshButton('Przeładuj stronę');
                } else {
                    x13Import.redirect(help_class_name, [
                        'attributesDownloadSuccess',
                        'wholesalerTab=' + x13ImportDef.wholesalerTab
                    ]);
                }
            });
        });

        $(document).on('click', selectors.saveChangesButton, function (e) {
            e.preventDefault();

            saveAttributeChanges($(this).data('x-attribute-group-id'));
        });

        $(document).on('click', selectors.restoreChangesButton, function (e) {
            e.preventDefault();

            restoreAttributeChanges($(this).data('x-attribute-group-id'));
        });

        $(document).on('click', selectors.bulkSaveChangesButton, function (e) {
            e.preventDefault();

            overlayLoader.create();
            x13Import.disableButton($(selectors.bulkSaveChangesButton));
            x13Import.disableButton($(selectors.bulkRestoreChangesButton), false);
            x13Import.disableButton($(selectors.wholesalerAttributeDownload), false);
            $(selectors.wholesalerImportOption).prop('disabled', true);

            processSave();

            function processSave() {
                if (attributesToSave.length) {
                    saveAttributeChanges(attributesToSave[0], function () {
                        processSave();
                    });
                } else {
                    x13Import.enableButton($(selectors.bulkSaveChangesButton));
                    x13Import.enableButton($(selectors.bulkRestoreChangesButton));
                    x13Import.enableButton($(selectors.wholesalerAttributeDownload));
                    $(selectors.wholesalerImportOption).prop('disabled', false);
                    overlayLoader.destruct();
                }
            }
        });

        $(document).on('click', selectors.bulkRestoreChangesButton, function (e) {
            e.preventDefault();

            for (const xAttributeGroupId of attributesToSave.slice(0)) {
                restoreAttributeChanges(xAttributeGroupId);
            }
        });

        if (bulkAttributeGroup) {
            bulkAttributeGroup.removeAttr('onclick');
            bulkAttributeGroup.on('click', function (e) {
                e.preventDefault();

                if ($('[name="ximport_attribute_groupBox[]"]:checked').length > 0) {
                    $(selectors.bulkAttributeGroupImportOption).val(null);
                    $(selectors.bulkAttributeGroupModal).modal('show');
                } else {
                    alert('Nie wybrano grup atrybutów do masowej zmiany.');
                }
            });

            $(selectors.bulkAttributeGroupSubmit).on('click', function (e) {
                e.preventDefault();

                const importOption = $(selectors.bulkAttributeGroupImportOption).val();

                if (importOption !== '') {
                    $('[name="ximport_attribute_groupBox[]"]:checked').each(function (index, element) {
                        $(element).closest('tr').find(selectors.attributeGroupImportOption).val(importOption).trigger('change');
                    });
                }

                $(selectors.bulkAttributeGroupModal).modal('hide');
            });
        }

        if (bulkAttribute) {
            bulkAttribute.removeAttr('onclick');
            bulkAttribute.on('click', function (e) {
                e.preventDefault();

                if (attributeBoxChecked) {
                    $(selectors.bulkAttributeImportOption).val(null);
                    $(selectors.bulkAttributeModal).modal('show');
                } else {
                    alert('Nie wybrano wartości atrybutów do masowej zmiany.');
                }
            });

            $(selectors.bulkAttributeSubmit).on('click', function (e) {
                e.preventDefault();

                const importOption = $(selectors.bulkAttributeImportOption).val();

                if (importOption !== '') {
                    const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${attributeBoxChecked}"]`);
                    $attributeRow.each(function (index, element) {
                        if ($(element).find(selectors.attributeBox).is(':checked')) {
                            $(element).find(selectors.attributeImportOption).val(importOption).trigger('change');
                        }
                    });
                }

                $(selectors.bulkAttributeModal).modal('hide');
            });
        }

        $(window).on('beforeunload', function () {
            if (attributesToSave.length) {
                return true;
            }
        });
    });

    function showActionButtons(xAttributeGroupId) {
        $(selectors.attributeListTable).find(`${selectors.saveChangesButton}[data-x-attribute-group-id="${xAttributeGroupId}"]`).parent().show();
        $(selectors.bulkSaveChangesButton).show();
        $(selectors.bulkRestoreChangesButton).show();

        if (!attributesToSave.includes(xAttributeGroupId)) {
            attributesToSave.push(xAttributeGroupId);
        }
    }

    function hideActionButtons(xAttributeGroupId) {
        $(selectors.attributeListTable).find(`${selectors.saveChangesButton}[data-x-attribute-group-id="${xAttributeGroupId}"]`).parent().hide();

        attributesToSave.splice(attributesToSave.indexOf(xAttributeGroupId), 1);
        if (!attributesToSave.length) {
            $(selectors.bulkSaveChangesButton).hide();
            $(selectors.bulkRestoreChangesButton).hide();
        }
    }

    function saveAttributeChanges(xAttributeGroupId, loopCallback) {
        const $attributeGroupRow = $(selectors.attributeListTable).find(`${selectors.attributeGroupName}[data-x-attribute-group-id="${xAttributeGroupId}"]`).closest('tr');
        const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${xAttributeGroupId}"]`);
        const $attributeOptionRow = $(selectors.attributeListTable).find(`${selectors.attributeRowOption}[data-x-attribute-group-id="${xAttributeGroupId}"]`);
        const $saveButton = $attributeGroupRow.find(selectors.saveChangesButton);
        const $restoreButton = $attributeGroupRow.find(selectors.restoreChangesButton);

        const overlayLoader = new x13Import.overlayLoader($attributeGroupRow, $attributeRow, $attributeOptionRow);
        overlayLoader.showSpinner = false;

        // show overlayLoader and lock wholesaler import option
        // only on single row save
        if (typeof loopCallback === 'undefined') {
            overlayLoader.create();

            $(selectors.wholesalerImportOption).prop('disabled', true);
            x13Import.disableButton($(selectors.wholesalerAttributeDownload), false);
        }

        x13Import.disableButton($saveButton);
        x13Import.disableButton($restoreButton, false);

        const attributeGroupId = $attributeGroupRow.find(selectors.attributeGroupSelect).val();
        const importOption = $attributeGroupRow.find(selectors.attributeGroupImportOption).val();
        let importOptionDefault = null;

        if ($attributeOptionRow.length) {
            importOptionDefault = $attributeOptionRow.find('select').val();
        }

        let attributeNames = [];
        if ($attributeRow.length) {
            $attributeRow.each(function (index, element) {
                const attributeId = $(element).find(selectors.attributeSelect).val();

                attributeNames.push({
                    xAttributeId: parseInt($(element).data('x-attribute-id')),
                    attributeId: (attributeId && attributeId !== '' ? parseInt(attributeId) : null),
                    importOption: $(element).find(selectors.attributeImportOption).val()
                });
            });
        }

        x13Import.ajaxPOST({
            action: 'saveAttributeMapping',
            data: {
                xAttributeGroupId: parseInt(xAttributeGroupId),
                attributeGroupId: (attributeGroupId && attributeGroupId !== '' ? parseInt(attributeGroupId) : null),
                importOption: importOption,
                importOptionDefault: importOptionDefault,
                attributeNames: attributeNames
            }
        }, function (json) {
            if (!json.status) {
                showErrorMessage(json.message);
            } else {
                if (json.id_attribute_group) {
                    importerSelect2.chooseNewOption($attributeGroupRow.find(selectors.attributeGroupSelect), json.id_attribute_group, json.group_name);
                } else {
                    $attributeGroupRow.find(selectors.attributeGroupSelect).val(null).trigger('change');
                }

                $attributeGroupRow.find(selectors.attributeGroupImportOption).data('value', json.import_option).attr('data-value', json.import_option);
                $attributeGroupRow.find(selectors.attributeGroupSelect).data('value', json.id_attribute_group).attr('data-value', json.id_attribute_group);

                if ($attributeOptionRow.length) {
                    $attributeOptionRow.find('select').data('value', json.import_default_option).attr('data-value', json.import_default_option);
                }

                if ($attributeRow.length && 'attributeNames' in json) {
                    for (const attribute of json.attributeNames) {
                        const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-id="${attribute.id}"]`);

                        if (json.id_attribute_group && attribute.id_attribute) {
                            if (!importerSelect2.searchInCache('attributes_' + json.id_attribute_group, 'name', attribute.name)) {
                                importerSelect2.pushIntoCache('attributes_' + json.id_attribute_group, {
                                    id_attribute: attribute.id_attribute,
                                    name: attribute.name
                                }, 'name');
                            }

                            importerSelect2.chooseNewOption($attributeRow.find(selectors.attributeSelect), attribute.id_attribute, attribute.name);
                        } else {
                            $attributeRow.find(selectors.attributeSelect).val(null).trigger('change');
                        }

                        $attributeRow.find(selectors.attributeImportOption).data('value', attribute.import_option).attr('data-value', attribute.import_option);
                        $attributeRow.find(selectors.attributeSelect).data('value', attribute.id_attribute).attr('data-value', attribute.id_attribute);
                    }
                }

                hideActionButtons(xAttributeGroupId);
            }

            x13Import.enableButton($saveButton);
            x13Import.enableButton($restoreButton);

            if (typeof loopCallback === 'function') {
                loopCallback();
            } else {
                overlayLoader.destruct();

                $(selectors.wholesalerImportOption).prop('disabled', false);
                x13Import.enableButton($(selectors.wholesalerAttributeDownload));
            }
        });
    }

    function restoreAttributeChanges(xAttributeGroupId) {
        const $attributeGroupRow = $(selectors.attributeListTable).find(`${selectors.attributeGroupName}[data-x-attribute-group-id="${xAttributeGroupId}"]`).closest('tr');
        const $attributeRow = $(selectors.attributeListTable).find(`${selectors.attributeRow}[data-x-attribute-group-id="${xAttributeGroupId}"]`);
        const $attributeImportDefaultOption = $(selectors.attributeListTable).find(`${selectors.attributeImportDefaultOption}[data-x-attribute-group-id="${xAttributeGroupId}"]`);
        const $attributeGroupImportOption = $attributeGroupRow.find(selectors.attributeGroupImportOption);
        const $attributeGroupSelect = $attributeGroupRow.find(selectors.attributeGroupSelect);

        $attributeGroupImportOption.val($attributeGroupImportOption.data('value')).trigger('change');
        $attributeGroupSelect.val($attributeGroupSelect.data('value')).trigger('change');

        if ($attributeImportDefaultOption.length) {
            $attributeImportDefaultOption.val($attributeImportDefaultOption.data('value')).trigger('change');
        }

        if ($attributeRow.length) {
            $attributeRow.each(function (index, element) {
                const $attributeImportOption = $(element).find(selectors.attributeImportOption);
                const $attributeSelect = $(element).find(selectors.attributeSelect);

                $attributeImportOption.val($attributeImportOption.data('value')).trigger('change');
                $attributeSelect.val($attributeSelect.data('value')).trigger('change');
            });
        }

        hideActionButtons(xAttributeGroupId);
    }

}(jQuery, x13Import || {}));
