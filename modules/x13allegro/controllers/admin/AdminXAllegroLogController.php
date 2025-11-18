<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Component\Configuration\ConfigurationDependencies;
use x13allegro\Component\Logger\LogLevel;
use x13allegro\Component\Logger\LogType;
use x13allegro\Repository\LoggerRepository;

final class AdminXAllegroLogController extends XAllegroController
{
    public function __construct()
    {
        $this->table = 'xallegro_log';
        $this->identifier = 'id_xallegro_log';
        $this->explicitSelect = true;
        $this->list_no_link = true;

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroLog'));

        $accountList = [];
        /** @var XAllegroAccount $account */
        foreach (XAllegroAccount::getAll(false) as $account) {
            $accountList[$account->id] = $account->username;
        }

        $this->fields_list = [
            'level' => [
                'title' => $this->l('Poziom'),
                'class' => 'fixed-width-md',
                'filter_key' => 'a!level',
                'type' => 'select',
                'list' => $this->getLogFilterList(LogLevel::class, 'level'),
                'callback' => 'printLogLevel',
                'orderby' => false
            ],
            'type' => [
                'title' => $this->l('Typ'),
                'class' => 'fixed-width-xxl',
                'filter_key' => 'a!type',
                'type' => 'select',
                'list' => $this->getLogFilterList(LogType::class, 'type'),
                'callback' => 'printLogType',
                'orderby' => false
            ],
            'username' => [
                'title' => $this->l('Konto Allegro'),
                'class' => 'fixed-width-md',
                'filter_key' => 'a!id_xallegro_account',
                'type' => 'select',
                'list' => $accountList,
                'orderby' => false
            ],
            'id_offer' => [
                'title' => $this->l('Oferta'),
                'class' => 'fixed-width-md',
                'orderby' => false
            ],
            'product' => [
                'title' => $this->l('Produkt'),
                'class' => 'fixed-width-xl',
                'orderby' => false,
                'search' => false
            ],
            'order_reference' => [
                'title' => $this->l('Zamówienie'),
                'class' => 'fixed-width-md',
                'filter_key' => 'o!reference',
                'orderby' => false
            ],
            'message' => [
                'title' => $this->l('Wiadomość'),
                'orderby' => false,
                'search' => false
            ],
            'counter' => [
                'title' => $this->l('Licznik'),
                'class' => 'center fixed-width-xs',
                'search' => false,
                'callback' => 'printCounter'
            ],
            'last_occurrence' => [
                'title' => $this->l('Data'),
                'class' => 'fixed-width-lg',
                'type' => 'datetime'
            ]
        ];

        $this->fields_options = [
            'general' => [
                'title' =>	$this->l('Ustawienia powiadomień email'),
                'description' => $this->l('Powiadomienia wysyłane są tylko dla zdarzeń który wystąpiły podczas uruchomienia pliku "sync.php"'),
                'image' => false,
                'fields' =>	[
                    'LOG_SEND' => [
                        'title' => $this->l('Wysyłaj powiadomienia o zdarzeniach'),
                        'type' => 'bool',
                    ],
                    'LOG_SEND_LEVEL' => [
                        'title' => $this->l('Poziom zdarzenia dla którego wysyłać powiadomienia email'),
                        'type' => 'checkbox', // type checkbox uses json_decode
                        'choices' => [
                            ['key' => LogLevel::INFO, 'name' => LogLevel::INFO()->getValueTranslated()],
                            ['key' => LogLevel::ERROR, 'name' => LogLevel::ERROR()->getValueTranslated()],
                            ['key' => LogLevel::EXCEPTION, 'name' => LogLevel::EXCEPTION()->getValueTranslated()]
                        ],
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['LOG_SEND' => 1]
                        )
                    ],
                    'LOG_SEND_EMAIL_LIST' => [
                        'title' => $this->l('Lista adresów email'),
                        'desc' => $this->l('Adresy emailowe podawaj w nowej linii'),
                        'type' => 'textarea',
                        'rows' => 4,
                        'auto_value' => false,
                        'value' => $this->printEmailList(),
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['LOG_SEND' => 1]
                        )
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Zapisz')
                ]
            ]
        ];

        $this->tpl_folder = 'x_allegro_log/';
    }

    public function renderList()
    {
        $this->_select .= '
            a.`id_xallegro_log`,
            a.`displayed`,
            ac.`username`,
            IF(a.`id_offer` = 0, NULL, a.`id_offer`) as `id_offer`,
            p.`product`';

        $this->_join .= '
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_account` ac
                ON (ac.`id_xallegro_account` = a.`id_xallegro_account`)
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o
                ON (o.`id_order` = a.`id_order`)
            LEFT JOIN (
                SELECT
                    a2.`id_xallegro_log`,
                    CONCAT_WS(" - ", pl.`name`, GROUP_CONCAT(attrl.`name` ORDER BY attrg.`id_attribute_group` SEPARATOR " ")) as `product`
                FROM `' . _DB_PREFIX_ . 'xallegro_log` a2
                LEFT JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                    ON (ps.`id_product` = a2.`id_product`
                        AND ps.`id_shop` = a2.`id_shop`)
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON (pl.`id_product` = ps.`id_product`
                        AND pl.`id_shop` = a2.`id_shop`
                        AND pl.`id_lang` = ' . (int)$this->context->language->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas
                    ON (pas.`id_product_attribute` = a2.`id_product_attribute`
                        AND pas.`id_shop` = a2.`id_shop`)
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                    ON (pac.`id_product_attribute` = pas.`id_product_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute` attr
                    ON (attr.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` attrl
                    ON (attrl.`id_attribute` = attr.`id_attribute`
                        AND attrl.`id_lang` = ' . (int)$this->context->language->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` attrg
                    ON (attrg.`id_attribute_group` = attr.`id_attribute_group`)
                GROUP BY a2.`id_xallegro_log`
            ) p ON p.`id_xallegro_log` = a.`id_xallegro_log`';

        $this->_defaultOrderBy = 'last_occurrence';
        $this->_defaultOrderWay = 'DESC';

        $this->addRowAction('showDetails');

        $this->tpl_list_vars['unreadLogsCount'] = LoggerRepository::getUnreadLogs();

        return parent::renderList();
    }

    public function beforeUpdateOptions()
    {
        $this->redirect_after = $this->context->link->getAdminLink('AdminXAllegroLog') . '&conf=6';

        $emailList = explode(';', str_replace(["\n", "\r", "\n\r", "\r\n", ","], ';', Tools::getValue('LOG_SEND_EMAIL_LIST', '')));
        $_POST['LOG_SEND_EMAIL_LIST'] = array_values(array_filter(array_map('trim', $emailList)));
    }

    /**
     * @param mixed $value
     * @param array $row
     * @return string
     */
    public function printLogLevel($value, $row)
    {
        /** @var LogLevel $logLevel */
        $logLevel = LogLevel::$value();

        switch ($logLevel->getKey()) {
            case LogLevel::ERROR:
                $badge = 'badge-danger';
                break;
            case LogLevel::EXCEPTION:
                $badge = 'badge-dark';
                break;

            default:
                $badge = 'badge-info';
        }

        return '<span class="badge badge-x13allegro ' . $badge . '">' . $logLevel->getValueTranslated() . '</span>';
    }

    /**
     * @param mixed $value
     * @param array $row
     * @return string
     */
    public function printLogType($value, $row)
    {
        /** @var LogType $logLevel */
        $logType = LogType::$value();

        return $logType->getValueTranslated();
    }

    /**
     * @param mixed $value
     * @param array $row
     * @return string
     */
    public function printCounter($value, $row)
    {
        if (in_array($row['level'], [LogLevel::ERROR, LogLevel::EXCEPTION])) {
            return '<span class="badge badge-danger">' . $value . '</span>';
        }

        return $value;
    }

    /**
     * @return string
     */
    public function printEmailList()
    {
        if ($value = XAllegroConfiguration::get('LOG_SEND_EMAIL_LIST')) {
            $value = json_decode($value, true);

            if (is_array($value)) {
                return implode("\r", $value);
            }
        }

        return '';
    }

    /**
     * @param string $token
     * @param string $id
     * @param string|null $name
     * @return string
     */
    public function displayShowDetailsLink($token, $id, $name = null)
    {
        $listKey = array_search($id, array_column($this->_list, 'id_xallegro_log'));

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_show_details.tpl');
        $tpl->assign([
            'action' => $this->l('Szczegóły'),
            'title' => $this->l('Pokaż wszystkie informacje z loga'),
            'id' => $id,
            'displayed' => $this->_list[$listKey]['displayed']
        ]);

        return $tpl->fetch();
    }

    /**
     * @return void
     */
    public function ajaxProcessGetLogDetails()
    {
        $logDetails = LoggerRepository::getLogDetails(Tools::getValue('logId'));

        $logDetails['level'] = $this->printLogLevel($logDetails['level'], []);
        $logDetails['type'] = $this->printLogType($logDetails['type'], []);

        $employee = new Employee($logDetails['id_employee']);
        if (Validate::isLoadedObject($employee)) {
            $logDetails['employee'] = "$employee->firstname $employee->lastname";
        }

        $allegroAccount = new XAllegroAccount($logDetails['id_xallegro_account']);
        if (Validate::isLoadedObject($allegroAccount)) {
            $logDetails['allegroAccount'] = $allegroAccount->username;
        }

        $order = new Order($logDetails['id_order']);
        if (Validate::isLoadedObject($order)) {
            $logDetails['order'] = $order->reference;
        }

        $product = new Product($logDetails['id_product'], false, $this->context->language->id, $logDetails['id_shop']);
        if (Validate::isLoadedObject($product)) {
            $logDetails['product'] = $product->name;

            $productCombination = $product->getAttributeCombinationsById($logDetails['id_product_attribute'], $this->context->language->id);
            if (!empty($productCombination)) {
                $logDetails['product'] .= ' - ' . implode(' ', array_column($productCombination, 'attribute_name'));
            }
        }

        $logMessage = json_decode($logDetails['message']);
        if (json_last_error() === JSON_ERROR_NONE) {
            $logDetails['isJson'] = true;
            $logDetails['message'] = json_encode($logMessage, JSON_PRETTY_PRINT);
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_show_details_modal.tpl');
        $tpl->assign($logDetails);

        die(json_encode([
            'html' => $tpl->fetch()
        ]));
    }

    /**
     * @return void
     */
    public function ajaxProcessMarkAsRead()
    {
        die(LoggerRepository::markAsRead(Tools::getValue('logId', [])));
    }

    /**
     * @param string $class
     * @param string $column
     * @return array
     */
    private function getLogFilterList($class, $column)
    {
        $result = Db::getInstance()->executeS('
            SELECT DISTINCT(`' . $column . '`)
            FROM `' . _DB_PREFIX_ . 'xallegro_log`'
        );

        if (!$result) {
            return [];
        }

        $list = [];
        foreach ($result as $row) {
            /** @var \x13allegro\Component\Enum $enum */
            $enum = $class::{$row[$column]}();
            $enumTranslated = $enum->getValueTranslated();

            if (!empty($enumTranslated)) {
                $list[$enum->getKey()] = $enumTranslated;
            }
        }

        return $list;
    }
}
