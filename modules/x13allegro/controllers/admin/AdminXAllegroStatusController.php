<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\Model\Marketplace\Enum\Marketplace;
use x13allegro\Api\Model\Order\Enum\FulfillmentStatus;
use x13allegro\Component\Configuration\ConfigurationDependencies;
use x13allegro\Repository\FulfillmentStatusRepository;

final class AdminXAllegroStatusController extends XAllegroController
{
    /** @var XAllegroStatus[] */
    private $allegroOrderStates = [];

    /** @var FulfillmentStatusRepository[] */
    private $allegroFulfillmentStates = [];

    public function __construct()
    {
        parent::__construct();

		$this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroStatus'));

        $this->toolbar_title = $this->l('Powiązania statusów zamówień');
        $this->multiple_fieldsets = true;
        $this->tpl_folder = 'x_allegro_status/';
    }

    public function init()
    {
        parent::init();

        $this->display = 'edit';
        $this->allegroOrderStates = XAllegroStatus::values();
        $this->allegroFulfillmentStates = FulfillmentStatusRepository::values(true);
    }

    public function renderForm()
    {
        $statusByMarketplace = XAllegroConfiguration::get('ORDER_STATUS_BY_MARKETPLACE');
        $orderStates = OrderState::getOrderStates($this->context->language->id);
        $marketplaces = Marketplace::values();

        // default Order states
        $inputOrderStates = $this->prepareStatusesTab('default', $orderStates);
        $tabOrderStates = null;

        if ($statusByMarketplace) {
            $tabOrderStates['marketplace_default'] = $this->l('Domyślny');

            foreach ($marketplaces as $marketplace) {
                $inputOrderStates = array_merge($inputOrderStates, $this->prepareStatusesTab($marketplace->getValue(), $orderStates));
                $tabOrderStates['marketplace_' . $marketplace->getValue()] = $marketplace->getValueTranslated();
            }
        }

        $inputAllegroStates = [];
        foreach ($this->allegroFulfillmentStates as $fulfillmentState) {
            $inputAllegroStates[] = [
                'label' => $fulfillmentState->getValue(),
                'type' => 'select',
                'allegro_fulfillment' => $fulfillmentState->getKey(),
                'name' => 'allegro_fulfillment[' . $fulfillmentState->getKey() . '][]',
                'class' => 'fixed-width-xxl',
                'options' => [
                    'query' => $orderStates,
                    'id' => 'id_order_state',
                    'name' => 'name'
                ]
            ];
        }

        $this->fields_form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Powiązania statusów PrestaShop')
                    ],
                    'tabs' => $tabOrderStates,
                    'input' => $inputOrderStates,
                    'submit' => [
                        'title' => $this->l('Zapisz')
                    ]
                ]
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Powiązania statusów Allegro')
                    ],
                    'input' => $inputAllegroStates,
                    'submit' => [
                        'title' => $this->l('Zapisz')
                    ]
                ]
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Dodatkowe ustawienia')
                    ],
                    'input' => [
                        [
                            'label' => $this->l('Zmień status Allegro po przekazaniu numeru przewozowego'),
                            'name' => 'shipping_number_status',
                            'configName' => 'ORDER_ALLEGRO_SHIPPING_STATUS',
                            'type' => 'switch',
                            'values' => [
                                ['id' => 'default_on', 'value' => 1, 'label' => $this->l('Tak')],
                                ['id' => 'default_off', 'value' => 0, 'label' => $this->l('Nie')]
                            ]
                        ],
                        [
                            'label' => $this->l('Status Allegro po przekazaniu numeru przewozowego'),
                            'name' => 'shipping_number_status_fulfillment',
                            'configName' => 'ORDER_ALLEGRO_SHIPPING_STATUS_FULFILLMENT',
                            'type' => 'select',
                            'class' => 'fixed-width-xxl',
                            'options' => [
                                'query' => FulfillmentStatus::toChoseList(true),
                                'id' => 'id',
                                'name' => 'name'
                            ],
                            'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                                ConfigurationDependencies::fieldMatch(),
                                ['shipping_number_status' => 1]
                            )
                        ],
                        [
                            'label' => $this->l('Podziel statusy zamówień PrestaShop według rynków Allegro'),
                            'name' => 'status_by_marketplace',
                            'configName' => 'ORDER_STATUS_BY_MARKETPLACE',
                            'type' => 'switch',
                            'values' => [
                                ['id' => 'default_on', 'value' => 1, 'label' => $this->l('Tak')],
                                ['id' => 'default_off', 'value' => 0, 'label' => $this->l('Nie')]
                            ]
                        ]
                    ],
                    'submit' => [
                        'title' => $this->l('Zapisz')
                    ]
                ]
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
            else {
                if (XAllegroStatus::updateStatuses(Tools::getValue('allegro_status', []), (bool)Tools::getValue('status_by_marketplace'))
                    && FulfillmentStatusRepository::updateStatuses(Tools::getValue('allegro_fulfillment', []))
                    && XAllegroConfiguration::updateValue('ORDER_ALLEGRO_SHIPPING_STATUS', Tools::getValue('shipping_number_status'))
                    && XAllegroConfiguration::updateValue('ORDER_ALLEGRO_SHIPPING_STATUS_FULFILLMENT', Tools::getValue('shipping_number_status_fulfillment'))
                    && XAllegroConfiguration::updateValue('ORDER_STATUS_BY_MARKETPLACE', Tools::getValue('status_by_marketplace'))
                ) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroStatus') . '&conf=4');
                }

                $this->errors[] = $this->l('Wystąpił błąd podczas aktualizacji powiązań');
            }
        }

        $this->display = 'edit';

        return true;
    }

    public function getFieldsValue($obj)
    {
        foreach ($this->fields_form as $fieldset) {
            if (isset($fieldset['form']['input'])) {
                foreach ($fieldset['form']['input'] as $input) {
                    if (!isset($this->fields_value[$input['name']])) {
                        if (isset($input['allegro_status'])) {
                            $orderState = $this->allegroOrderStates[$input['allegro_status']]->getOrderState($input['allegro_marketplace'], $this->context->language->id, $input['allegro_marketplace'] != 'default');
                            $this->fields_value[$input['name'] . '[id_order_state]'] = (Validate::isLoadedObject($orderState) ? $orderState->id : null);
                        } else if (isset($input['allegro_fulfillment'])) {
                            $this->fields_value[$input['name']] = $this->allegroFulfillmentStates[$input['allegro_fulfillment']]->getAssignedOrderStatesIds();
                        } else if (isset($input['configName'])) {
                            $this->fields_value[$input['name']] = XAllegroConfiguration::get($input['configName']);
                        }
                    }
                }
            }
        }

        return $this->fields_value;
    }

    /**
     * @param string $marketplaceId
     * @param array $orderStates
     * @return array
     */
    private function prepareStatusesTab($marketplaceId, array $orderStates)
    {
        $inputOrderStates = [];
        foreach ($this->allegroOrderStates as $allegroStatus) {
            $state = $allegroStatus->getAllegroState($this->context->language->id);
            $inputOrderStates[] = [
                'label' => $allegroStatus->getValueTranslated(),
                'hint' => ($allegroStatus->equals(XAllegroStatus::CANCELLED()) ? $this->l('Ten status nie przywraca ilości produktów, zamawiający może ponowić płatność') : null),
                'allegro_status' => $allegroStatus->getKey(),
                'allegro_marketplace' => $marketplaceId,
                'name' => 'allegro_status[' . $marketplaceId . '][' . $allegroStatus->getKey() . ']',
                'tab' => 'marketplace_' . $marketplaceId,
                'class' => 'fixed-width-xxl',
                'type' => 'select',
                'options' => [
                    'query' => $this->prepareStatusesArray($state, $orderStates),
                    'id' => 'id_order_state',
                    'name' => 'name'
                ]
            ];
        }

        return $inputOrderStates;
    }

    /**
     * @param OrderState $orderState
     * @param array $statuses
     * @return array
     */
    private function prepareStatusesArray(OrderState $orderState, array $statuses)
    {
        $statusesArray = [];
        foreach ($statuses as $status) {
            $statusesArray[] = [
                'id_order_state' => $status['id_order_state'],
                'name' => $status['name'],
                'disabled' => ($status['logable'] != $orderState->logable
                    || $status['paid'] != $orderState->paid
                    || $status['invoice'] != $orderState->invoice)
            ];
        }

        return $statusesArray;
    }
}
