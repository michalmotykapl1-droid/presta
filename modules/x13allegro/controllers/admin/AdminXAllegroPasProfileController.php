<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

final class AdminXAllegroPasProfileController extends XAllegroController
{
    protected $allegroAutoLogin = true;
    protected $allegroAccountSwitch = true;

    /** @var XAllegroPas */
    public $object;

    public function __construct()
    {
        $this->table = 'xallegro_delivery';
        $this->identifier = 'id_xallegro_delivery';
        $this->className = 'XAllegroPas';

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroPasProfile'));
        $this->tpl_folder = 'x_allegro_pas_profile/';

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );
    }

    public function renderList()
    {
        if (Tools::getValue('controller') == 'AdminXAllegroPasProfile' && empty($this->errors)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroPas'));
        }

        $this->initToolbar();

        if (method_exists($this, 'initPageHeaderToolbar')) {
            $this->initPageHeaderToolbar();
        }

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = array(
            'id_xallegro_delivery' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 50,
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Nazwa profilu')
            ),
            'default' => array(
                'title' => $this->l('Domyślny'),
                'active' => 'default',
                'width' => 100,
                'align' => 'center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
            ),
            'active' => array(
                'title' => $this->l('Aktywny'),
                'active' => 'active',
                'width' => 100,
                'align' => 'center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
            )
        );

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Profil dostawy'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Nazwa profilu'),
                    'name' => 'name',
                    'size' => 30,
                    'class' => 'fixed-width-xxl',
                    'required' => true
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Czas wysyłki'),
                    'name' => 'handling_time',
                    'required' => true,
                    'class' => 'fixed-width-xxl',
                    'options' => array(
                        'query' => XAllegroPas::getHandlingTimeOptions(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Dodatkowe informacje o dostawie'),
                    'name' => 'additional_info',
                    'class' => 'fixed-width-xxl',
                    'rows' => 5,
                    'cols' => 35
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Kraj'),
                    'name' => 'country_code',
                    'class' => 'fixed-width-xxl',
                    'required' => true,
                    'options' => array(
                        'query' => XAllegroPas::getCountryCodeOptions(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Województwo'),
                    'name' => 'province',
                    'required' => true,
                    'class' => 'fixed-width-xxl',
                    'options' => array(
                        'query' => XAllegroPas::getProvinceOptions(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Kod pocztowy'),
                    'name' => 'post_code',
                    'required' => true,
                    'size' => 12,
                    'class' => 'fixed-width-md',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Miasto'),
                    'name' => 'city',
                    'class' => 'fixed-width-xxl',
                    'required' => true,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Opcje faktury'),
                    'name' => 'invoice',
                    'class' => 'fixed-width-xxl',
                    'required' => true,
                    'options' => array(
                        'query' => XAllegroPas::getInvoiceOptions(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => $this->bootstrap ? 'switch' : 'radio',
                    'label' => $this->l('Domyślny profil'),
                    'name' => 'default',
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'default_on',
                            'value' => 1,
                            'label' => $this->l('Tak')
                        ),
                        array(
                            'id' => 'default_off',
                            'value' => 0,
                            'label' => $this->l('Nie')
                        )
                    )
                ),
                array(
                    'type' => $this->bootstrap ? 'switch' : 'radio',
                    'label' => $this->l('Aktywny profil'),
                    'name' => 'active',
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Tak')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Nie')
                        )
                    ),
                    'default_value' => 1
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz')
            )
        );

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table) && Tools::getValue('country_code') == 'PL') {
            if (!Tools::getValue('province')) {
                $this->errors[] = $this->l('Województwo jest wymagane dla kraju Polska');
            }

            if (!Tools::getValue('post_code')) {
                $this->errors[] = $this->l('Kod pocztowy jest wymagany dla kraju Polska');
            }
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
}
