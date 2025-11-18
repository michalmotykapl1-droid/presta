<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

final class AdminXAllegroPasController extends XAllegroController
{
    private $profileController;
    private $shippingRatesController;

    protected $allegroAutoLogin = true;
    protected $allegroAccountSwitch = true;

    public function __construct()
    {
        parent::__construct();

        $this->profileController = new AdminXAllegroPasProfileController();
        $this->profileController->token = $this->token;
        $this->profileController->init();

        $this->shippingRatesController = new AdminXAllegroPasShippingRatesController();
        $this->shippingRatesController->token = $this->token;
        $this->shippingRatesController->init();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroPas'));
        $this->tpl_folder = 'x_allegro_pas/';
    }

    public function init()
    {
        foreach ($_GET as $get => $value) {
            // add, update, delete
            if (preg_match('/^((?!id_).*)xallegro_(.*)$/', $get, $m)) {
                if ($m[2] == 'delivery') {
                    $controller = $this->context->link->getAdminLink('AdminXAllegroPasProfile');
                    $identifier = $this->profileController->identifier;
                }
                else if ($m[2] == 'delivery_rate') {
                    $controller = $this->context->link->getAdminLink('AdminXAllegroPasShippingRates');
                    $identifier = $this->shippingRatesController->identifier;
                }
                else {
                    continue;
                }

                $url = $controller . '&' . $get . '&' . $identifier . '=' . Tools::getValue($identifier);
                if (strpos($m[1], 'submitBulk') !== false) {
                    unset($_POST['token']);
                    $url .= '&' . http_build_query($_POST);
                }

                Tools::redirectAdmin($url);
            }

            unset($m);
        }

        parent::init();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
        {
            $this->page_header_toolbar_btn['allegro_delivery'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroPasProfile') . '&add' . $this->profileController->table,
                'desc' => $this->l('Dodaj nowy profil dostawy'),
                'icon' => 'process-icon-new'
            );

            $this->page_header_toolbar_btn['allegro_delivery_rate'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroPasShippingRates') . '&add' . $this->shippingRatesController->table,
                'desc' => $this->l('Dodaj nowy cennik'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initContent()
    {
        parent::initContent();

        $messages = [
            'confirmations',
            'informations',
            'warnings',
            'errors'
        ];

        foreach ($messages as $message) {
            $this->{$message} = array_merge(
                $this->{$message},
                $this->profileController->{$message},
                $this->shippingRatesController->{$message}
            );
        }
    }

    public function renderList()
    {
        return $this->profileController->renderList() .
            $this->shippingRatesController->renderList();
    }

    public function ajaxProcessUpdatePositions()
    {
        $this->shippingRatesController->ajaxProcessUpdatePositions();
    }
}
