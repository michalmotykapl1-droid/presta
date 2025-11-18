<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;
use x13allegro\Repository\PrestaShop\ProductAttributeRepository;
use x13allegro\Repository\PrestaShop\ProductFeatureRepository;
use x13allegro\Repository\PrestaShop\ManufacturerRepository;

/**
 * @return bool
 */
function upgrade_module_7_1_0()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    XAllegroConfiguration::updateValue('MARKUP_CALCULATION', 'WITHOUT_INDIVIDUAL_PRICE');
    XAllegroConfiguration::updateValue('IMAGES_UPLOAD_TYPE', 'CURL');
    XAllegroConfiguration::updateValue('AUCTION_DISABLE_ORDER_MESSAGE', 0);
    XAllegroConfiguration::updateValue('AUCTION_B2B_ONLY', 0);
    XAllegroConfiguration::updateValue('PRODUCTIZATION_SEARCH', json_encode([
        'product_name' => [
            'search' => (int)XAllegroConfiguration::get('PRODUCTIZATION_SEARCH_BY_NAME'),
            'select' => (XAllegroConfiguration::get('PRODUCTIZATION_SEARCH_BY_NAME') ? 'only_single' : 'none')
        ],
        'reference' => [
            'search' => 1,
            'select' => 'only_single'
        ],
        'GTIN' => [
            'search' => 1,
            'select' => (XAllegroConfiguration::get('PRODUCTIZATION_MAPPING_FIRST') ? 'always_first' : 'only_single')
        ],
        'MPN' => [
            'search' => 1,
            'select' => 'only_single'
        ]
    ]));

    // new "global-options" in ConfigurationAccount for all Accounts
    foreach (XAllegroAccount::getAllIds(false) as $row) {
        $config = new XAllegroConfigurationAccount($row['id_xallegro_account']);
        $config->updateValue('MARKUP_CALCULATION', XAllegroConfigurationAccount::GLOBAL_OPTION);
        $config->updateValue('AUCTION_DISABLE_ORDER_MESSAGE', XAllegroConfigurationAccount::GLOBAL_OPTION);
        $config->updateValue('AUCTION_B2B_ONLY', XAllegroConfigurationAccount::GLOBAL_OPTION);
    }

    // "active" DEFAULT 1 on upgrade
    Db::getInstance()->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_category`
            ADD `active` tinyint(1) unsigned NOT NULL DEFAULT 1 AFTER `id_categories`,
            ADD `fields_ambiguous_values` longtext NULL AFTER `fields_values`'
    );

    if (!DbAdapter::showColumnIndex('xallegro_category', 'active')) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_category`
                ADD INDEX(`active`)'
        );
    }

    // copy categories to backup
    Db::getInstance()->execute('CREATE TABLE `' . _DB_PREFIX_ . 'xallegro_category_backup710` LIKE `' . _DB_PREFIX_ . 'xallegro_category`');
    Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'xallegro_category_backup710` SELECT * FROM `' . _DB_PREFIX_ . 'xallegro_category`');

    // migrate categories
    $dataToMigrate = Db::getInstance()->executeS('
        SELECT `id_xallegro_category`, `fields_mapping`
        FROM `' . _DB_PREFIX_ . 'xallegro_category_backup710`'
    );

    if (!empty($dataToMigrate)) {
        $languageId = XAllegroConfiguration::get('AUCTION_LANGUAGE');
        $repository = [
            'attribute_group' => [],
            'attribute' => [],
            'feature' => [],
            'feature_value' => [],
            'manufacturer' => []
        ];

        foreach (ProductAttributeRepository::getAllAttributeValues($languageId) as $attributeValue) {
            if (!in_array($attributeValue['id_attribute_group'], $repository['attribute_group'])) {
                $repository['attribute_group'][] = $attributeValue['id_attribute_group'];
            }

            $repository['attribute'][] = $attributeValue['id_attribute'];
        }

        foreach (ProductFeatureRepository::getAllFeatureValues($languageId) as $featureValue) {
            if (!in_array($featureValue['id_feature'], $repository['feature'])) {
                $repository['feature'][] = $featureValue['id_feature'];
            }

            $repository['feature_value'][] = $featureValue['id_feature_value'];
        }

        foreach (ManufacturerRepository::getAll() as $manufacturer) {
            $repository['manufacturer'][] = $manufacturer['id_manufacturer'];
        }

        foreach ($dataToMigrate as $categoryToMigrate) {
            $fieldsMappingToMigrate = json_decode($categoryToMigrate['fields_mapping'], true);
            $fieldsMapping = [];

            foreach ($fieldsMappingToMigrate as $parameterId => $mapping) {
                if (!empty($mapping)) {
                    // select/checkbox/multiple/range
                    if (is_array($mapping)) {
                        foreach ($mapping as $valueId => $map) {
                            $escaped = _migration_escapeParameterMapping($map);
                            if ($escaped !== false) {
                                // multiple/range
                                if (is_int($valueId)) {
                                    if ($escaped['prefix'] == 'product' || in_array($escaped['suffix'], $repository[$escaped['prefix']])) {
                                        $fieldsMapping[$parameterId][] = [
                                            'rule' => $escaped['prefix'],
                                            'ruleValue' => $escaped['suffix']
                                        ];
                                    }
                                }
                                // select/checkbox
                                else {
                                    if ($escaped['prefix'] == 'product' || in_array($escaped['suffix'], $repository[$escaped['prefix']])) {
                                        $fieldsMapping[$parameterId][] = [
                                            'valueId' => $valueId,
                                            'rule' => $escaped['prefix'],
                                            'ruleValue' => $escaped['suffix']
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    // text
                    else {
                        $escaped = _migration_escapeParameterMapping($mapping);
                        if ($escaped !== false) {
                            if ($escaped['prefix'] == 'product' || in_array($escaped['suffix'], $repository[$escaped['prefix']])) {
                                $fieldsMapping[$parameterId][] = [
                                    'rule' => $escaped['prefix'],
                                    'ruleValue' => $escaped['suffix']
                                ];
                            }
                        }
                    }
                }
            }

            Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'xallegro_category`
                SET `fields_mapping` = "' .pSQL(json_encode($fieldsMapping)) . '"
                WHERE `id_xallegro_category` = ' . (int)$categoryToMigrate['id_xallegro_category']
            );
        }
    }

    return true;
}

function _migration_escapeParameterMapping($mapping)
{
    $pos = strrpos($mapping, '_');

    if ($pos !== false) {
        $prefix = substr($mapping, 0, $pos);
        $suffix = substr($mapping, $pos + 1, strlen($mapping));

        if ($suffix == 'ean') {
            $suffix = 'ean13';
        }

        return [
            'prefix' => $prefix,
            'suffix' => $suffix
        ];
    }

    return false;
}
