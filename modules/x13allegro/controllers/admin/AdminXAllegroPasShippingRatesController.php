<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\DataProvider\DeliveryMethodsProvider;
use x13allegro\Api\Model\ShippingRates\ShippingRate;
use x13allegro\Json\JsonMapBuilder;

final class AdminXAllegroPasShippingRatesController extends XAllegroController
{
    protected $_default_pagination = 1000;

    protected $allegroAutoLogin = true;

    /** @var StdClass */
    private $shippingRate;

    public function __construct()
    {
        $this->table = 'xallegro_delivery_rate';
        $this->identifier = 'id_xallegro_delivery_rate';
        $this->list_id = 'xallegro_delivery_rate';
        $this->multiple_fieldsets = true;

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroPasShippingRates'));
        $this->tpl_folder = 'x_allegro_pas_shipping_rates/';
    }

    public function renderList()
    {
        if (Tools::getValue('controller') == 'AdminXAllegroPasShippingRates' && empty($this->errors)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroPas'));
        }

        $this->initToolbar();

        if (method_exists($this, 'initPageHeaderToolbar')) {
            $this->initPageHeaderToolbar();
        }

        $this->addRowAction('edit');

        $this->fields_list = array(
            'name' => array(
                'title' => $this->l('Cennik dostaw'),
                'search' => false,
                'orderby' => false
            ),
            'default' => array(
                'title' => $this->l('Domyślny'),
                'active' => 'default',
                'align' => 'center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'search' => false,
                'orderby' => false
            )
        );

        return parent::renderList();
    }

    public function renderForm()
    {
        $id = Tools::getValue($this->identifier);
        $deliveryMethods = array();

        try {
            $dataProvider = new DeliveryMethodsProvider(
                $this->allegroApi->sale()->deliveryMethods()->getAll()->deliveryMethods
            );

            if ($id) {
                $this->shippingRate = $this->allegroApi->sale()->shippingRates()->getDetails($id);
            }

            $deliveryMethods = $dataProvider->getDeliveryMethodsWithShippingRates($this->shippingRate, Tools::getValue('deliveryMethods', []));
            $deliveryMethods = $dataProvider->groupDeliveryMethods($deliveryMethods);
        }
        catch (Exception $ex) {
            $this->errors[] = (string)$ex;
        }

        $this->fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Cennik dostawy'),
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => $this->identifier
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Nazwa cennika'),
                    'name' => 'name',
                    'size' => 30,
                    'class' => 'fixed-width-xxl',
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz')
            )
        );

        $this->fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Opcje dostawy'),
            ),
            'delivery_methods' => $deliveryMethods,
            'submit' => array(
                'title' => $this->l('Zapisz')
            )
        );

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table))
        {
            $deliveryMethods = Tools::getValue('deliveryMethods');
            $shippingRateId = Tools::getValue($this->identifier);
            $shippingRateName = Tools::getValue('name');

            if (!$deliveryMethods) {
                $this->errors[] = $this->l('Nie wybrano żadnej metody dostawy');
            }
            else if (!$shippingRateName) {
                $this->errors[] = $this->l('Nie podano nazwy cennika dostawy');
            }

            if (!empty($this->errors)) {
                $this->display = 'edit';
                return false;
            }

            /** @var ShippingRate $shippingRate */
            $shippingRate = (new JsonMapBuilder('ShippingRate'))->map(new ShippingRate());
            $shippingRate->name = $shippingRateName;

            foreach ($deliveryMethods as $id => $deliveryMethod) {
                if ($deliveryMethod['enabled']) {
                    $shippingRate->rate($id, $deliveryMethod['firstItemRate'], $deliveryMethod['nextItemRate'], $deliveryMethod['maxQuantityPerPackage']);
                }
            }

            try {
                if ($shippingRateId) {
                    $shippingRate->id = $shippingRateId;
                    $this->allegroApi->sale()->shippingRates()->update($shippingRate);
                }
                else {
                    $this->allegroApi->sale()->shippingRates()->create($shippingRate);
                }
            }
            catch (Exception $ex) {
                $this->errors[] = (string)$ex;
                $this->display = 'edit';
                return false;
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroPas') . '&conf=4');
        }
        else if (Tools::getIsset('active' . $this->table) || Tools::getIsset('default' . $this->table)) {
            return (new XAllegroConfigurationAccount($this->allegroApi->getAccount()->id))
                ->updateValue('SHIPPING_RATE_DEFAULT_ID', Tools::getValue($this->identifier));
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        if (!empty($this->errors)) {
            $this->display = 'edit';
        }

        parent::initContent();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        try {
            $shippingRateDefaultId = (new XAllegroConfigurationAccount($this->allegroApi->getAccount()->id))->get('SHIPPING_RATE_DEFAULT_ID');
            $results = $this->allegroApi->sale()->shippingRates()->getAll()->shippingRates;

            foreach ($results as $shippingRate) {
                $this->_list[] = array(
                    $this->identifier => $shippingRate->id,
                    'name' => $shippingRate->name,
                    'default' => $shippingRate->id === $shippingRateDefaultId
                );
            }
        }
        catch (Exception $ex) {
            $this->errors[] = (string)$ex;
        }

        $this->_listTotal = count($this->_list);
    }

    public function getFieldsValue($obj)
    {
        parent::getFieldsValue($obj);

        $this->fields_value[$this->identifier] = (is_object($this->shippingRate) ? $this->shippingRate->id : null);
        $this->fields_value['name'] = Tools::getValue('name', is_object($this->shippingRate) ? $this->shippingRate->name : null);

        return $this->fields_value;
    }
}
