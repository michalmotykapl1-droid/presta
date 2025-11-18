<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\Authorization\Token;
use x13allegro\Api\Authorization\TokenSet;
use x13allegro\Api\DataProvider\AfterSaleServicesProvider;
use x13allegro\Api\DataProvider\MarketplacesProvider;
use x13allegro\Api\Model\Marketplace\Enum\Marketplace;
use x13allegro\Api\XAllegroApi;
use x13allegro\Component\Logger\LogType;
use x13allegro\Exception\ModuleException;

final class AdminXAllegroAccountsController extends XAllegroController
{
    /** @var XAllegroAccount */
    public $object;

    public function __construct()
    {
        $this->table = 'xallegro_account';
        $this->identifier = 'id_xallegro_account';
        $this->className = 'XAllegroAccount';
        $this->multiple_fieldsets = true;

        parent::__construct();
        
        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroAccounts'));

        $this->fields_list = array(
            'id_xallegro_account' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 50,
                'class' => 'fixed-width-xs'
            ),
            'username' => array(
                'title' => $this->l('Nazwa użytkownika'),
                'width' => 300
            ),
            'default' => array(
                'title' => $this->l('Domyślny'),
                'active' => 'default',
                'width' => 100,
                'class' => 'fixed-width-sm',
                'align' => 'center',
                'type' => 'bool'
            ),
            'active' => array(
                'title' => $this->l('Aktywny'),
                'active' => 'active',
                'width' => 100,
                'class' => 'fixed-width-sm',
                'align' => 'center',
                'type' => 'bool'
            ),
            'sandbox' => array(
                'title' => $this->l('Sandbox'),
                'width' => 100,
                'class' => 'fixed-width-sm',
                'align' => 'center',
                'type' => 'bool',
                'icon' => array(
                    '0' => array('class' => 'icon-remove'),
                    '1' => array('class' => 'icon-check')
                ),
            ),
            'base_marketplace' => array(
                'title' => $this->l('Rynek bazowy'),
                'width' => 100,
                'class' => 'fixed-width-xl',
                'callback' => 'printMarketplace',
                'search' => false
            ),
            'expire_refresh' => array(
                'title' => $this->l('Ważność autoryzacji'),
                'callback' => 'printExpire',
                'search' => false
            )
        );

        $this->tpl_folder = 'x_allegro_accounts/';

        if (Tools::isSubmit('cancelAuthorization')) {
            (new XAllegroAccount(Tools::getValue('id_xallegro_account')))->getTokenManager()->clearTokenSet();
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
        {
            $this->page_header_toolbar_btn['allegro_current'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroAccounts') . '&addxallegro_account',
                'desc' => $this->l('Dodaj nowe konto'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->addRowAction('xAuthorize');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        if ((defined('X13_ALLEGRO_DEBUG') && X13_ALLEGRO_DEBUG) || (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_)) {
            $this->addRowAction('xCancelAuthorization');
        }

        $this->specificConfirmDelete = $this->l('Usunąć wybrane konto?') . '\n\n' .
            $this->l('Spowoduje to usunięcie wszystkich powiązań ofert do produktów dla wybranego konta!');

        if (!Tools::usingSecureMode() && Configuration::get('PS_SSL_ENABLED'))
        {
            $href = 'https://' . Tools::safeOutput(Tools::getServerName()) . Tools::safeOutput($_SERVER['REQUEST_URI']);

            $this->errors[] = '<b>' . $this->l('Uwaga! SSL jest włączony.')  . '</b><br>' .
                $this->l('Aby poprawnie autoryzować konto Allegro zaloguj się do trybu bezpiecznego (https://)') .
                ': <a href="' . $href . '">' . $href .'</a>';
        }

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Dane integracji konta Allegro'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Nazwa użytkownika'),
                    'desc' => $this->l('Uzupełnij jedną z poniższych wartości') . ':'
                        . '<br> - ' . $this->l('ID konta Allegro')
                        . '<br> - ' . $this->l('login konta Allegro')
                        . '<br> - ' . $this->l('email przypisany do konta Allegro'),
                    'name' => 'username',
                    'size' => 30,
                    'class' => 'fixed-width-xxl',
                    'required' => true
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Sandbox'),
                    'name' => 'sandbox',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'sandbox_on',
                            'value' => 1,
                            'label' => $this->l('Tak')
                        ),
                        array(
                            'id' => 'sandbox_off',
                            'value' => 0,
                            'label' => $this->l('Nie')
                        )
                    ),
                    'desc' => $this->l('Portal testowy Allegro')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Domyślne konto'),
                    'name' => 'default',
                    'required' => false,
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
                    ),
                    'desc' => $this->l('Automatycznie wybierane podczas wystawiania przedmiotów')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Aktywne konto'),
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

    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);

        foreach ($this->_list as &$item) {
            $item['expire_refresh'] = (new Token($item['refresh_token']))->getExpirationDate();
        }
    }

    public function printExpire($id, $row)
    {
        if (!$row['expire_refresh'] instanceof DateTime) {
            return '<span class="badge badge-danger">' . $this->l('autoryzuj konto') . '</span>';
        }

        $now = new DateTime();
        $interval = $now->diff($row['expire_refresh']);
        $days = (int)$interval->format('%R%a');
        $hours = (int)$interval->format('%R%h');

        if (($days < 1 && $hours < 1) || empty($row['access_token']) || empty($row['refresh_token'])) {
            return '<span class="badge badge-danger">' . $this->l('autoryzuj konto') . '</span>';
        }
        if ($days < 14) {
            return '<span class="badge badge-warning">' . $this->l('niedługo wygaśnie') . '</span>';
        }

        return '<span class="badge badge-success">' . $this->l('zautoryzowane') . '</span>';
    }

    public function printMarketplace($id, $row)
    {
        if ($row['base_marketplace']) {
            try {
                return Marketplace::from($row['base_marketplace'])->getValueTranslated();
            }
            catch (\UnexpectedValueException $ex) {
                return $this->l('Nieobsługiwany rynek') . ' (' . $row['base_marketplace'] .  ')';
            }
        }

        return '';
    }

    public function displayXAuthorizeLink($token = null, $id, $name = null)
    {
        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_authorize.tpl');
        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminXAllegroAccounts') . '&authorize&id_xallegro_account=' . $id,
            'action' => $this->l('Autoryzuj'),
            'title' => $this->l('Autoryzacja użytkownika'),
            'id_account' => $id
        ));

        return $tpl->fetch();
    }

    public function displayXCancelAuthorizationLink($token = null, $id, $name = null)
    {
        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_cancel_authorization.tpl');
        $tpl->assign([
            'href' => $this->context->link->getAdminLink('AdminXAllegroAccounts') . '&cancelAuthorization&id_xallegro_account=' . $id,
            'action' => $this->l('Usuń autoryzacje'),
            'title' => $this->l('Usuń autoryzacje użytkownika'),
        ]);

        return $tpl->fetch();
    }

    public function ajaxProcessAuthorizeApplication()
    {
        $account = new XAllegroAccount((int)Tools::getValue('accountId'));

        if (!Validate::isLoadedObject($account) || !$account->active) {
            die(json_encode([
                'success' => false,
                'text' => $this->l('Autoryzacja konta Allegro: Konto nieaktywne.')
            ]));
        }

        try {
            $result = (new XAllegroApi($account))->auth()->getDeviceCode();

            $this->log
                ->account($account->id)
                ->info(LogType::AUTHORIZATION_DEVICE_CODE());

            $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_authorize_modal.tpl');
            $tpl->assign(array(
                'accountId' => $account->id,
                'accountAuthUrl' => $result->verification_uri_complete,
                'redirectUrl' => $this->context->link->getAdminLink('AdminXAllegroAccounts'),
                'configurationUrl' => $this->context->link->getAdminLink('AdminXAllegroConfiguration') . '#xallegro_configuration_fieldset_advanced_settings'
            ));

            $html = $tpl->fetch();
        }
        catch (Exception $ex) {
            die(json_encode([
                'success' => false,
                'text' => (string)$ex
            ]));
        }

        die(json_encode([
            'success' => true,
            'html' => $html,
            'authIntervalTime' => $result->interval,
            'authDeviceCode' => $result->device_code,
            'accountUsername' => $account->username
        ]));
    }

    public function ajaxProcessAuthorizeApplicationCheck()
    {
        $account = new XAllegroAccount((int)Tools::getValue('accountId'));

        if (!Validate::isLoadedObject($account) || !$account->active) {
            die(json_encode([
                'success' => false,
                'text' => $this->l('Autoryzacja konta Allegro: Konto nieaktywne.')
            ]));
        }

        $authResource = (new XAllegroApi($account))->auth();

        try {
            $result = $authResource->authorizeDevice(Tools::getValue('authDeviceCode'));

            // save tokens
            $account->getTokenManager()->setTokenSet(
                new TokenSet($result->access_token, $result->refresh_token)
            );

            $me = (new XAllegroApi($account))->account()->me();
            $account = $account->setBaseMarketplace($me->baseMarketplace->id);

            $accountIdentity = false;
            if ($account->username == $me->id
                || $account->username == $me->login
                || $account->username == $me->email
            ) {
                $accountIdentity = true;
            }

            // block authorization when typed username does not match Allegro account
            if (!$accountIdentity) {
                $account->getTokenManager()->clearTokenSet();

                throw new ModuleException($this->l("Wpisana nazwa użytkownika ($account->username) nie jest tożsama ze zautoryzowanym kontem Allegro"));
            }
            // block authorization when Marketplace is not supported by module
            //if (!Marketplace::isValid($me->baseMarketplace->id)) {
            if ($me->baseMarketplace->id != XAllegroApi::MARKETPLACE_PL) {
                $account->getTokenManager()->clearTokenSet();

                throw new ModuleException($this->l("Nieobsługiwany rynek ({$me->baseMarketplace->id})"));
            }
        }
        catch (Exception $ex) {
            if ($authResource->getCode() === 400
                && in_array($authResource->getResponse()->error, ['authorization_pending', 'slow_down'])
            ) {
                // continue authInterval
                die(json_encode([
                    'success' => true
                ]));
            }

            die(json_encode([
                'success' => false,
                'text' => (string)$ex
            ]));
        }

        $marketplacesProvider = new MarketplacesProvider($account->base_marketplace);
        $configurationForm = false;

        // if id_language is empty then authorization is performed for the first time
        if (!$account->id_language) {
            $emptyOption = [
                'id' => '',
                'name' => $this->l('-- wybierz --')
            ];

            $afterSaleServices = [];

            $shopLanguages[] = $emptyOption;
            foreach (Language::getLanguages() as $language) {
                $marketplaceLanguage = $marketplacesProvider->getMarketplaceLanguage();

                $shopLanguages[] = [
                    'id' => $language['id_lang'],
                    'name' => $language['name'],
                    'isMarketplaceLanguage' => (Validate::isLoadedObject($marketplaceLanguage) && $language['id_lang'] == $marketplaceLanguage->id)
                ];
            }

            try {
                $api = new XAllegroApi($account);
                $afterSaleServicesProvider = new AfterSaleServicesProvider($api);

                foreach ($afterSaleServicesProvider->getAllServices() as $afterSaleServiceGroup => $afterSaleService) {
                    $afterSaleServices[$afterSaleServiceGroup][] = $emptyOption;

                    foreach ($afterSaleService as $service) {
                        $afterSaleServices[$afterSaleServiceGroup][] = (array)$service;
                    }
                }
            }
            catch (Exception $ex) {}

            $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_authorize_configuration_modal.tpl');
            $tpl->assign([
                'formAction' => $this->context->link->getAdminLink('AdminXAllegroAccounts'),
                'cancelAction' => $this->context->link->getAdminLink('AdminXAllegroAccounts') . '&id_xallegro_account=' . $account->id . '&cancelAuthorization',
                'accountId' => $account->id,
                'shopLanguages' => $shopLanguages,
                'afterSaleServices' => $afterSaleServices
            ]);

            $configurationForm = $tpl->fetch();
        }

        die(json_encode([
            'success' => true,
            'authorized' => true,
            'baseMarketplace' => $marketplacesProvider->getMarketplaceName(),
            'configurationForm' => $configurationForm
        ]));
    }
}
