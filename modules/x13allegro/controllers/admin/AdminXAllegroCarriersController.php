<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\DataProvider\CarrierOperatorsProvider;
use x13allegro\Api\DataProvider\DeliveryMethodsProvider;

final class AdminXAllegroCarriersController extends XAllegroController
{
    protected $allegroAutoLogin = true;

    public function __construct()
    {
        $this->table = 'xallegro_carrier';

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroCarriers'));
        $this->tpl_folder = 'x_allegro_carriers/';
    }

    public function init()
    {
        parent::init();

        $this->display = 'edit';
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/js/select2/css/select2.min.css');
        $this->addJS($this->module->getPathUri() . 'views/js/select2/js/select2.full.min.js');
    }

    public function renderForm()
    {
        $carriers =
        $deliveryMethods =
        $carrierOperators = [];

        foreach (Carrier::getCarriers($this->context->language->id, false, false, false, null, null) as $carrier) {
            $carriers[] = [
                'id' => $carrier['id_reference'],
                'name' => $carrier['name']
            ];
        }

        try {
            $dataProvider = new DeliveryMethodsProvider(
                $this->allegroApi->sale()->deliveryMethods()->getAll()->deliveryMethods
            );

            $deliveryMethods = $dataProvider->getDeliveryMethods();
            $deliveryMethods = $dataProvider->groupDeliveryMethods($deliveryMethods);

            $carrierOperators = (new CarrierOperatorsProvider($this->allegroApi))->getCarriers();
        }
        catch (Exception $ex) {
            $this->errors[] = (string)$ex;
        }

        $deliveryMethodsGroups = [
            'free' => (!empty($deliveryMethods['free']) ? $deliveryMethods['free'] : []),
            'in_advance_courier' => (!empty($deliveryMethods['in_advance_courier']) ? $deliveryMethods['in_advance_courier'] : []),
            'in_advance_package' => (!empty($deliveryMethods['in_advance_package']) ? $deliveryMethods['in_advance_package'] : []),
            'in_advance_letter' => (!empty($deliveryMethods['in_advance_letter']) ? $deliveryMethods['in_advance_letter'] : []),
            'in_advance_pos' => (!empty($deliveryMethods['in_advance_pos']) ? $deliveryMethods['in_advance_pos'] : []),
            'cash_on_delivery_courier' => (!empty($deliveryMethods['cash_on_delivery_courier']) ? $deliveryMethods['cash_on_delivery_courier'] : []),
            'cash_on_delivery_package' => (!empty($deliveryMethods['cash_on_delivery_package']) ? $deliveryMethods['cash_on_delivery_package'] : []),
            'cash_on_delivery_letter' => (!empty($deliveryMethods['cash_on_delivery_letter']) ? $deliveryMethods['cash_on_delivery_letter'] : []),
            'cash_on_delivery_pos' => (!empty($deliveryMethods['cash_on_delivery_pos']) ? $deliveryMethods['cash_on_delivery_pos'] : []),
            'abroad' => (!empty($deliveryMethods['abroad']) ? $deliveryMethods['abroad'] : [])
        ];

        // default Carriers mapping
        $inputCarriers = $this->prepareCarriersTab('default', $deliveryMethodsGroups, $carriers, $carrierOperators);
        $tabCarriers['account_default'] = $this->l('Domyślny');

        /** @var XAllegroAccount $account */
        foreach (XAllegroAccount::getAll() as $account) {
            $tabCarriers['account_' . $account->id] = $account->username;
            $inputCarriers = array_merge($inputCarriers, $this->prepareCarriersTab($account->id, $deliveryMethodsGroups, $carriers, $carrierOperators));
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Powiązania przewoźników')
            ],
            'tabs' => $tabCarriers,
            'input' => $inputCarriers,
            'submit' => [
                'title' => $this->l('Zapisz')
            ]
        ];

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            if ($this->tabAccess['edit'] !== '1') {
                $this->errors[] = $this->l('Nie masz uprawnień do edycji w tym miejscu.');
            }
            else if (Tools::getValue('id_fields_shipment')) {
                if (XAllegroCarrier::updateCarriers(Tools::getValue('id_fields_shipment'))) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroCarriers') . '&conf=4');
                }
            }
            else {
                $this->errors[] = $this->l('Nie przesłano przewoźników');
            }
        }

        $this->display = 'edit';

        return true;
    }

    public function getFieldsValue($obj)
    {
        $assignedCarriers = XAllegroCarrier::getAll();

        foreach ($this->fields_form as $fieldset) {
            if (isset($fieldset['form']['input'])) {
                foreach ($fieldset['form']['input'] as $input) {
                    if (!isset($input['id_fields_shipment'])) {
                        continue;
                    }

                    if (!isset($this->fields_value[$input['name']])) {
                        $this->fields_value[$input['name']] = (isset($assignedCarriers[$input['account_id']][$input['id_fields_shipment']])
                            ? $assignedCarriers[$input['account_id']][$input['id_fields_shipment']]['id_carrier'] : 0);
                    }

                    $inputOperatorId = $input['name_operator'] . '[id_operator]';
                    if (!isset($this->fields_value[$inputOperatorId])) {
                        $this->fields_value[$inputOperatorId] = (isset($assignedCarriers[$input['account_id']][$input['id_fields_shipment']])
                            ? $assignedCarriers[$input['account_id']][$input['id_fields_shipment']]['id_operator'] : '');
                    }

                    $inputOperatorName = $input['name_operator'] . '[operator_name]';
                    if (!isset($this->fields_value[$inputOperatorName])) {
                        $this->fields_value[$inputOperatorName] = (isset($assignedCarriers[$input['account_id']][$input['id_fields_shipment']])
                            ? $assignedCarriers[$input['account_id']][$input['id_fields_shipment']]['operator_name'] : '');
                    }
                }
            }
        }

        return $this->fields_value;
    }

    /**
     * @param int $accountId
     * @param array $deliveryMethods
     * @param array $carriers
     * @param array $carrierOperators
     * @return array
     */
    private function prepareCarriersTab($accountId, array $deliveryMethods, array $carriers, array $carrierOperators)
    {
        $inputCarriers = [];

        foreach ($deliveryMethods as $type => $methods) {
            if (empty($methods)) {
                continue;
            }

            $inputCarriers[] = [
                'name' => 'separator_price_advanced_settings_' . $accountId,
                'type' => 'separator',
                'heading' => $this->translateHeadingType($type),
                'delivery_type' => $type,
                'tab' => 'account_' . $accountId,
            ];

            foreach ($methods as $deliveryMethod) {
                $inputCarriers[] = [
                    'label' => $deliveryMethod['name'],
                    'delivery_type' => $type,
                    'id_fields_shipment' => $deliveryMethod['id'],
                    'name' => 'id_fields_shipment[' . $accountId . '][' . $deliveryMethod['id'] . '][id_carrier]',
                    'name_operator' => 'id_fields_shipment[' . $accountId . '][' . $deliveryMethod['id'] . ']',
                    'account_id' => $accountId,
                    'tab' => 'account_' . $accountId,
                    'class' => 'allegro-carrier-select',
                    'type' => 'select',
                    'options' => [
                        'query' => $carriers,
                        'id' => 'id',
                        'name' => 'name',
                        'default' => [
                            'label' => $this->l('-- Wybierz --'),
                            'value' => 0
                        ]
                    ],
                    'options_operators' => [
                        'query' => $carrierOperators,
                        'default' => [
                            'label' => $this->l('-- Wybierz operatora przesyłki --'),
                            'value' => null
                        ]
                    ]
                ];
            }
        }

        return $inputCarriers;
    }

    /**
     * @param $type
     * @return string
     */
    private function translateHeadingType($type)
    {
        switch ($type) {
            case 'free': return $this->l('Darmowe opcje dostawy');
            case 'in_advance_courier': return $this->l('Kurier - płatność z góry');
            case 'in_advance_package': return $this->l('Paczka - płatność z góry');
            case 'in_advance_letter': return $this->l('List - płatność z góry');
            case 'in_advance_pos': return $this->l('Odbiór w punkcie - płatność z góry');
            case 'cash_on_delivery_courier': return $this->l('Kurier - płatność przy odbiorze');
            case 'cash_on_delivery_package': return $this->l('Paczka - płatność przy odbiorze');
            case 'cash_on_delivery_letter': return $this->l('List - płatność przy odbiorze');
            case 'cash_on_delivery_pos': return $this->l('Odbiór w punkcie - płatność przy odbiorze');
            case 'abroad': return $this->l('Wysyłka za granicę - płatność z góry');
            default: return '';
        }
    }
}
