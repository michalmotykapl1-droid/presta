<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\DataProvider\AfterSaleServicesProvider;
use x13allegro\Api\DataProvider\MarketplacesProvider;
use x13allegro\Api\Model\Marketplace\Enum\Marketplace;
use x13allegro\Api\Model\Offers\Enum\SellingModeType;
use x13allegro\Api\XAllegroApi;
use x13allegro\Component\Cache\Json;
use x13allegro\Component\Configuration\ConfigurationDependencies;
use x13allegro\Component\Logger\LogType;
use x13allegro\Component\ProcessLock;
use x13allegro\SyncManager\Order\Data\Model\OrderMessage;
use x13allegro\SyncManager\Offer\Enum\PriceUpdateSettings;
use x13allegro\SyncManager\Offer\OfferFullSynchronization;

final class AdminXAllegroConfigurationController extends XAllegroController
{
    public function __construct()
    {
        $this->table = 'xallegro_configuration';
        $this->identifier = 'id_xallegro_configuration';
        $this->className = 'XAllegroConfiguration';

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroConfiguration'));
        $this->tpl_folder = 'x_allegro_configuration/';

        if (Tools::isSubmit('740closeConfigError')) {
            XAllegroConfiguration::updateValue('740_CONFIG_ERROR', 1);
        }
        if (Tools::isSubmit('720closeAuctionsArchive')) {
            XAllegroConfiguration::updateValue('720_AUCTIONS_ARCHIVE', 1);
        }
        if (Tools::isSubmit('720closeAuctionsRebuild')) {
            XAllegroConfiguration::updateValue('720_AUCTIONS_REBUILD', 1);
        }
        if (Tools::isSubmit('630CloseCarrierIndexMessage')) {
            XAllegroConfiguration::updateValue('630_CARRIER_PACKAGE_INFO_INDEX', 1);
        }

        if (Tools::isSubmit('clearAllegroCache')) {
            (new Json())->clearAll();
            $this->confirmations[] = $this->l('Wyczyszczono pamięć podręczną Allegro API');
        }
        if (Tools::isSubmit('disableAutoRenewForOlderOffers')) {
            $this->disableAutoRenewForOlderOffers();
            XAllegroConfiguration::updateValue('732_DISABLE_AUTO_RENEW_FOR_OLDER_OFFERS', 1);
            $this->confirmations[] = $this->l('Wyłączono automatycznie wznawianie zduplikowanych ofert');
        }
    }

    public function init()
    {
        parent::init();

        $this->tpl_option_vars['ionCubeLicenseInfo'] = $this->displayIonLicenseInfo();

        if (!Tools::isSubmit('update_module') && !Tools::isSubmit('process_update_module')) {
            $this->getFieldsOptions();

            Hook::exec('action'.$this->controller_name.'OptionsModifier', array(
                'options' => &$this->fields_options,
                'option_vars' => &$this->tpl_option_vars,
            ));
        }
    }

    private function getFieldsOptions()
    {
        $externalIdList = [
            ['id_external' => XAllegroAuction::EXTERNAL_NONE, 'name' => $this->l('brak')],
            ['id_external' => XAllegroAuction::EXTERNAL_ID, 'name' => $this->l('ID produktu')],
            ['id_external' => XAllegroAuction::EXTERNAL_REFERENCE, 'name' => $this->l('kod referencyjny (indeks)')],
            ['id_external' => XAllegroAuction::EXTERNAL_EAN, 'name' => $this->l('kod kreskowy EAN-13')],
            ['id_external' => XAllegroAuction::EXTERNAL_UPC, 'name' => $this->l('kod kreskowy UPC')]
        ];

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $externalIdList[] = ['id_external' => XAllegroAuction::EXTERNAL_ISBN, 'name' => $this->l('kod książki ISBN')];
        }
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $externalIdList[] = ['id_external' => XAllegroAuction::EXTERNAL_MPN, 'name' => $this->l('kod MPN')];
        }

        $taxRulesGroups = [];
        foreach (TaxRulesGroup::getTaxRulesGroups() as $taxRuleGroup) {
            $taxRulesGroups[] = [
                'id' => $taxRuleGroup['id_tax_rules_group'],
                'name' => $taxRuleGroup['name']
            ];
        }

        $orderEmployees = [];
        $employees = (new PrestaShopCollection(Employee::class))
            ->where('active', '=', 1);

        /** @var Employee $employee */
        foreach ($employees->getResults() as $employee) {
            $orderEmployees[] = [
                'id' => $employee->id,
                'name' => $employee->firstname . ' ' . $employee->lastname
            ];
        }

        $orderContacts = [];
        foreach (Contact::getContacts($this->context->language->id) as $contact) {
            $orderContacts[] = [
                'id' => $contact['id_contact'],
                'name' => $contact['name'] . ' (' . $contact['email'] . ')'
            ];
        }

        $groups = Group::getGroups($this->context->language->id);
        $customerGroups = [];
        foreach ($groups as $group) {
            $customerGroups[] = [
                'id' => $group['id_group'],
                'key' => $group['id_group'],
                'name' => $group['name']
            ];
        }

        $shopLanguages = [];
        foreach (Language::getLanguages() as $language) {
            $shopLanguages[] = [
                'id' => $language['id_lang'],
                'name' => $language['name']
            ];
        }

        if (version_compare(_PS_VERSION_, '1.7.1.2', '>=') && empty($orderContacts)) {
            $this->warnings[] = $this->l('Brak utworzonych kontaktów sklepowych, wiadomości do sprzedającego nie będą działały poprawnie!');
        }

        $front_hooks = [
            ['id_hook' => 'displayProductAllegroAuctionLink', 'name' => 'displayProductAllegroAuctionLink'],
            ['id_hook' => 'displayLeftColumnProduct', 'name' => 'displayLeftColumnProduct'],
            ['id_hook' => 'displayRightColumnProduct', 'name' => 'displayRightColumnProduct']
        ];

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $front_hooks[] = ['id_hook' => 'displayProductAdditionalInfo', 'name' => 'displayProductAdditionalInfo'];
            $front_hooks[] = ['id_hook' => 'displayProductButtons', 'name' => 'displayProductButtons'];
        }

        $syncLastSuccess = XAllegroConfiguration::get('SYNC_LAST_TIME');
        if ($syncLastSuccess) {
            $syncLastSuccess = (new DateTime($syncLastSuccess))->format('Y-m-d H:i');
        } else {
            $syncLastSuccess = '<strong>' . $this->l('jeszcze nie został uruchomiony') . '</strong>';
        }

        $marketplacesDefault = Marketplace::toChoseList();
        array_walk($marketplacesDefault, function (&$item) {
            $item['key'] = $item['id'];
            $item['name'] = preg_replace('/^(\w+)/u', '<b>$1</b>', $item['name']);
            unset($item['id']);
        });

        $marketplacesCurrencies = [];
        foreach (Marketplace::toChoseList() as $marketplace) {
            $currency = (new MarketplacesProvider($marketplace['id']))->getMarketplaceCurrency();
            $marketplacesCurrencies[$currency->id] = [
                'marketplace' => $marketplace['name'],
                'currencyId' => $currency->id,
                'currencyName' => $currency->name,
                'currencySign' => $currency->sign,
                'currencyRate' => $currency->conversion_rate
            ];
        }

        $allegroAccounts = [];
        $allegroAccountsFields = [];
        $allegroAccountEmptyOption = [
            'id' => '',
            'name' => $this->l('-- wybierz --')
        ];
        $allegroAccountGlobalOption = [
            'id' => XAllegroConfigurationAccount::GLOBAL_OPTION,
            'name' => $this->l('używaj ustawienia z opcji globalnych')
        ];

        /** @var XAllegroAccount $account */
        foreach (XAllegroAccount::getAll() as $account) {
            $allegroAccounts['allegro_account_' . $account->id] = $account->username;
            $accountLogged = false;
            $afterSaleServices = [];

            try {
                $accountLogged = $account->getTokenManager()->getAccessToken();

                $api = new XAllegroApi($account);
                $afterSaleServicesProvider = new AfterSaleServicesProvider($api);

                foreach ($afterSaleServicesProvider->getAllServices() as $afterSaleServiceGroup => $afterSaleService) {
                    $afterSaleServices[$afterSaleServiceGroup][] = $allegroAccountEmptyOption;

                    foreach ($afterSaleService as $service) {
                        $afterSaleServices[$afterSaleServiceGroup][] = (array)$service;
                    }
                }
            }
            catch (Exception $ex) {}

            $allegroAccountsFields['separator_account_advanced_settings_'.$account->id] = [
                'name' => 'separator_account_advanced_settings_'.$account->id,
                'type' => 'separator',
                'heading' => 'Ustawienia konta - '.$account->username,
                'tab' => 'allegro_account_' . $account->id,
            ]; // ------------------------------------------------------------------------------------------------------

            $allegroAccountsFields['id_language'.$account->id] = [
                'title' => $this->l('Język wystawianych ofert'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'account' => (int) $account->id,
                'key' => 'id_language',
                'identifier' => 'id',
                'list' => $shopLanguages
            ]; // XAllegroAccount->id_language

            $allegroAccountsFields['return_policy'.$account->id] = [
                'title' => $this->l('Warunki zwrotów'),
                'type' => ($accountLogged ? 'select' : 'badge_authorize'),
                'tab' => 'allegro_account_'.$account->id,
                'account' => (int) $account->id,
                'accountLogged' => $accountLogged,
                'key' => 'return_policy',
                'identifier' => 'id',
                'list' => (isset($afterSaleServices['returnPolicies']) ? $afterSaleServices['returnPolicies'] : [])
            ]; // XAllegroAccount->return_policy

            $allegroAccountsFields['implied_warranty'.$account->id] = [
                'title' => $this->l('Reklamacje'),
                'type' => ($accountLogged ? 'select' : 'badge_authorize'),
                'tab' => 'allegro_account_'.$account->id,
                'account' => (int) $account->id,
                'accountLogged' => $accountLogged,
                'key' => 'implied_warranty',
                'identifier' => 'id',
                'list' => (isset($afterSaleServices['impliedWarranties']) ? $afterSaleServices['impliedWarranties'] : [])
            ]; // XAllegroAccount->implied_warranty

            $allegroAccountsFields['warranty'.$account->id] = [
                'title' => $this->l('Gwarancje (opcjonalnie)'),
                'type' => ($accountLogged ? 'select' : 'badge_authorize'),
                'tab' => 'allegro_account_'.$account->id,
                'account' => (int) $account->id,
                'accountLogged' => $accountLogged,
                'key' => 'warranty',
                'identifier' => 'id',
                'list' => (isset($afterSaleServices['warranties']) ? $afterSaleServices['warranties'] : [])
            ]; // XAllegroAccount->warranty

            $allegroAccountsFields['separator_global_advanced_settings_'.$account->id] = [
                'name' => 'separator_global_advanced_settings_'.$account->id,
                'type' => 'separator',
                'heading' => 'Podstawowe ustawienia wystawiania',
                'tab' => 'allegro_account_' . $account->id,
            ]; // ------------------------------------------------------------------------------------------------------

            $allegroAccountsFields['AUCTION_DISABLE_ORDER_MESSAGE_'.$account->id] = [
                'title' => $this->l('Wyłącz uwagi do zakupu (wiadomość dla sprzedającego)'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'AUCTION_DISABLE_ORDER_MESSAGE',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => '1', 'name' => $this->l('tak').' - '.$this->l('brak pola "uwagi do zakupu"')],
                    ['id' => '0', 'name' => $this->l('nie').' - '.$this->l('opcjonalne pole "uwagi do zakupu"')]
                ]
            ]; // AUCTION_DISABLE_ORDER_MESSAGE

            $allegroAccountsFields['AUCTION_B2B_ONLY_'.$account->id] = [
                'title' => $this->l('Oferta tylko dla klientów biznesowych'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'AUCTION_B2B_ONLY',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => '1', 'name' => $this->l('tak')],
                    ['id' => '0', 'name' => $this->l('nie')]
                ]
            ]; // AUCTION_B2B_ONLY

            $allegroAccountsFields['separator_price_advanced_settings_'.$account->id] = [
                'name' => 'separator_price_advanced_settings_'.$account->id,
                'type' => 'separator',
                'heading' => 'Ustawienia cen i synchronizacja',
                'tab' => 'allegro_account_' . $account->id,
            ]; // ------------------------------------------------------------------------------------------------------

            $allegroAccountsFields['MARKUP_PERCENT_'.$account->id] = [
                'title' => $this->l('Narzut ceny dla produktów'),
                'desc' => $this->l('Pozostaw puste aby użyć ustawień globalnych'),
                'type' => 'text',
                'class' => 'fixed-width-sm xcast xcast-float xcast-unsigned xcast-allow-empty',
                'size' => 10,
                'suffix' => '%',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'MARKUP_PERCENT',
            ]; // MARKUP_PERCENT

            $allegroAccountsFields['MARKUP_VALUE_'.$account->id] = [
                'title' => $this->l('Marża ceny dla produktów'),
                'desc' => $this->l('Pozostaw puste aby użyć ustawień globalnych'),
                'type' => 'text',
                'class' => 'fixed-width-sm xcast xcast-float xcast-unsigned xcast-allow-empty',
                'size' => 10,
                'suffix' => $this->l('zł'),
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'MARKUP_VALUE',
            ]; // MARKUP_VALUE

            $allegroAccountsFields['MARKUP_CALCULATION_'.$account->id] = [
                'title' => $this->l('Dolicz narzut/marżę do'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'MARKUP_CALCULATION',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => 'WITHOUT_INDIVIDUAL_PRICE', 'name' => $this->l('tylko do produktów bez ceny indywidualnej')],
                    ['id' => 'ALL', 'name' => $this->l('wszystkich produktów')]
                ],
            ]; // MARKUP_CALCULATION

            $allegroAccountsFields['AUCTION_CALCULATE_FEES_'.$account->id] = [
                'title' => $this->l('Doliczaj prowizje za sprzedaż i promowanie'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'AUCTION_CALCULATE_FEES',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => '0', 'name' => $this->l('nie doliczaj')],
                    ['id' => '2', 'name' => $this->l('tylko dla produktów bez ceny indywidualnej')],
                    ['id' => '1', 'name' => $this->l('dla wszystkich produktów')]
                ],
            ]; // AUCTION_CALCULATE_FEES

            $allegroAccountsFields['AUCTION_PRICE_CUSTOMER_GROUP_'.$account->id] = [
                'title' => $this->l('Przeliczaj ceny według grupy'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'AUCTION_PRICE_CUSTOMER_GROUP',
                'identifier' => 'id',
                'list' =>  array_merge([$allegroAccountGlobalOption], $customerGroups),
            ]; // AUCTION_PRICE_CUSTOMER_GROUP

            $allegroAccountsFields['PRICE_UPDATE_'.$account->id] = [
                'title' => $this->l('Aktualizuj ceny na Allegro'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'PRICE_UPDATE',
                'identifier' => 'id',
                'list' => array_merge([$allegroAccountGlobalOption], PriceUpdateSettings::toChoseList())
            ]; // PRICE_UPDATE

            $allegroAccountsFields['AUCTION_CHECK_BADGES_'.$account->id] = [
                'title' => $this->l('Sprawdzaj kampanie promocyjne przypisane do ofert'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'AUCTION_CHECK_BADGES',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => '1', 'name' => $this->l('tak')],
                    ['id' => '0', 'name' => $this->l('nie')],
                ],
            ]; // AUCTION_CHECK_BADGES

            $allegroAccountsFields['separator_stock_advanced_settings_'.$account->id] = [
                'name' => 'separator_stock_advanced_settings_'.$account->id,
                'type' => 'separator',
                'heading' => 'Ustawienia ilości, aktywności i synchronizacja',
                'tab' => 'allegro_account_'.$account->id,
            ]; // ------------------------------------------------------------------------------------------------------

            $allegroAccountsFields['QUANITY_ALLEGRO_UPDATE_' . $account->id] = [
                'title' => $this->l('Aktualizuj stany magazynowe w Allegro'),
                'type' => 'select',
                'tab' => 'allegro_account_' . $account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'QUANITY_ALLEGRO_UPDATE',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => '1', 'name' => $this->l('tak')],
                    ['id' => '0', 'name' => $this->l('nie')],
                ],
            ]; // QUANITY_ALLEGRO_UPDATE

            $allegroAccountsFields['CLOSE_AUCTION_TRESHOLD_' . $account->id] = [
                'title' => $this->l('Ilość produktu poniżej której wymuszamy zamknięcie oferty'),
                'desc' => $this->l('Pozostaw puste aby użyć ustawień globalnych'),
                'type' => 'text',
                'tab' => 'allegro_account_' . $account->id,
                'class' => 'fixed-width-sm xcast xcast-int xcast-allow-empty',
                'suffix' => $this->l('szt.'),
                'configurationAccount' => (int) $account->id,
                'key' => 'CLOSE_AUCTION_TRESHOLD',
            ]; // CLOSE_AUCTION_TRESHOLD

            $allegroAccountsFields['QUANTITY_AUTO_RENEW_'.$account->id] = [
                'title' => $this->l('Włącz auto wznawianie ofert'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'QUANTITY_AUTO_RENEW',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => '1', 'name' => $this->l('tak')],
                    ['id' => '0', 'name' => $this->l('nie')]
                ]
            ]; // QUANTITY_AUTO_RENEW

            $allegroAccountsFields['QUANTITY_AUTO_RENEW_THRESHOLD_'.$account->id] = [
                'title' => $this->l('Ilość produktu powyżej której wznowimy ofertę'),
                'desc' => $this->l('Pozostaw puste aby użyć ustawień globalnych'),
                'type' => 'text',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'QUANTITY_AUTO_RENEW_THRESHOLD',
                'class' => 'fixed-width-sm xcast xcast-int xcast-allow-empty',
                'suffix' => $this->l('szt.')
            ]; // QUANTITY_AUTO_RENEW_THRESHOLD

            $allegroAccountsFields['OFFER_RENEW_KEEP_PROMOTION_'.$account->id] = [
                'title' => $this->l('Ustawienia promowania wznawianej oferty'),
                'type' => 'select',
                'tab' => 'allegro_account_'.$account->id,
                'configurationAccount' => (int) $account->id,
                'key' => 'OFFER_RENEW_KEEP_PROMOTION',
                'identifier' => 'id',
                'list' => [
                    $allegroAccountGlobalOption,
                    ['id' => '1', 'name' => $this->l('zostaw ustawione opcje promowania')],
                    ['id' => '0', 'name' => $this->l('usuń opcje promowania')]
                ]
            ]; // OFFER_RENEW_KEEP_PROMOTION
        }

        $this->fields_options = [
            'general' => [
                'title' =>	$this->l('Podstawowe ustawienia wystawiania'),
                'image' => false,
                'fields' =>	[
                    // ZMIANA: Dodajemy tutaj nowy, POPRAWIONY przełącznik do statystyk
                    'X13_ALLEGRO_STATS_ENABLED' => [
                        'title' => $this->l('Wyświetlaj statystyki powiązań'),
                        'hint' => $this->l('Włącza panel podsumowujący liczbę ofert powiązanych i niepowiązanych nad listą aukcji. Może nieznacznie obciążyć serwer przy dużej liczbie ofert.'),
                        'type' => 'bool'
                    ],
                    'hr_stats_separator' => [
                        'name' => 'hr_stats_separator',
                        'type' => 'hr'
                    ],
                    'TITLE_PATTERN' => [
                        'title' => $this->l('Domyślny tytuł oferty'),
                        'desc' =>
                            '<span class="x13allegro_black">{product_id}</span> '.$this->l('- ID produktu') . '<br />' .
                            '<span class="x13allegro_black">{product_name}</span> '.$this->l('- Nazwa produktu') . '<br />' .
                            '<span class="x13allegro_black">{product_name_attribute}</span> '.$this->l('- Nazwa atrybutu') . '<br />' .
                            '<span class="x13allegro_black">{product_short_desc}</span> '.$this->l('- Krótki opis') . '<br />' .
                            '<span class="x13allegro_black">{product_reference}</span> '.$this->l('- Kod referencyjny (indeks) produktu') . '<br />' .
                            '<span class="x13allegro_black">{product_ean13}</span> '.$this->l('- Kod EAN13') . '<br />' .
                            '<span class="x13allegro_black">{product_weight}</span> '.$this->l('- Waga produktu') .'<br />' .
                            '<span class="x13allegro_black">{product_price}</span> '.$this->l('- Cena produktu') . '<br />' .
                            '<span class="x13allegro_black">{manufacturer_name}</span> '.$this->l('- Nazwa producenta') . '<br />' .
                            '<span class="x13allegro_black">{attribute_group_X}</span> '.$this->l('- Nazwa i wartość grupy atrybutów X (grupa atrybutów musi być przypisana do kombinacji produktu)') . '<br />' .
                            '<span class="x13allegro_black">{attribute_group_value_X}</span> '.$this->l('- Wartość grupy atrybutów X (grupa atrybutów musi być przypisana do kombinacji produktu)') . '<br />' .
                            '<span class="x13allegro_black">{feature_X}</span> '.$this->l('- Nazwa i wartość cechy X (cecha musi być przypisana do produktu)') . '<br />' .
                            '<span class="x13allegro_black">{feature_value_X}</span> '.$this->l('- Wartość cechy X (cecha musi być przypisana do produktu)'),
                        'type' => 'text'
                    ],
                    'SELECT_ALL' => [
                        'title' => $this->l('Domyślnie zaznaczone produkty do wystawienia'),
                        'type' => 'select',
                        'identifier' => 'id_select_all',
                        'list' => [
                            ['id_select_all' => 0, 'name' => $this->l('nie zaznaczaj')],
                            ['id_select_all' => 1, 'name' => $this->l('wszystkie')],
                            ['id_select_all' => 2, 'name' => $this->l('tylko niewystawione')]
                        ]
                    ],
                    'PRODUCTIZATION_MODE' => [
                        'title' => $this->l('Domyślny tryb wystawiania oferty'),
                        'type' => 'select',
                        'identifier' => 'id_productization_mode',
                        'list' => [
                            ['id_productization_mode' => XAllegroAuction::PRODUCTIZATION_ASSIGN, 'name' => $this->l('wystaw według katalogu (domyślne)')],
                            ['id_productization_mode' => XAllegroAuction::PRODUCTIZATION_NEW, 'name' => $this->l('wystaw jako nowy produkt')]
                        ]
                    ],
                    'QUANTITY_DEFAULT' => [
                        'title' => $this->l('Domyślna ilość produktów w ofercie'),
                        'desc' => $this->l('Puste pole (luz zero) oznacza maksymalną dostępną ilość produktów'),
                        'suffix' => $this->l('szt.'),
                        'class' => 'fixed-width-sm xcast xcast-int',
                        'type' => 'text'
                    ],
                    'DURATION_DEFAULT' => [
                        'title' => $this->l('Domyślny czas trwania oferty'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => XAllegroAuction::getPublicationDurationOptions()
                    ],
                    'AUCTION_EXTERNAL' => [
                        'title' => $this->l('Sygnatura oferty (external.id)'),
                        'desc' => $this->l('Wewnętrzny identyfikator oferty'),
                        'type' => 'select',
                        'identifier' => 'id_external',
                        'list' => $externalIdList
                    ],
                    'AUCTION_DISABLE_ORDER_MESSAGE' => [
                        'title' => $this->l('Wyłącz uwagi do zakupu (wiadomość dla sprzedającego)'),
                        'class' => 'mark-as-account-option',
                        'type' => 'bool'
                    ],
                    'AUCTION_B2B_ONLY' => [
                        'title' => $this->l('Oferta tylko dla klientów biznesowych'),
                        'class' => 'mark-as-account-option',
                        'type' => 'bool'
                    ],
                    'hr_general_1' => [
                        'name' => 'hr_general_1',
                        'type' => 'hr'
                    ],
                    'SELECT_IMAGES' => [
                        'title' => $this->l('Domyślnie zaznaczone zdjęcia'),
                        'desc' => $this->l('Włączenie tej opcji spowoduje zaznaczenie wybranej ilości zdjęć podczas wystawiania oferty'),
                        'type' => 'select',
                        'identifier' => 'id_selection',
                        'list' => [
                            ['id_selection' => '0', 'name' => $this->l('brak zaznaczonych')],
                            ['id_selection' => 'first', 'name' => $this->l('tylko pierwsze')],
                            ['id_selection' => 'all', 'name' => $this->l('wszystkie')],
                        ],
                    ],
                    'IMAGES_COMBINATION' => [
                        'title' => $this->l('Przenieś wszystkie zdjęcia do każdej kombinacji produktu'),
                        'type' => 'bool',
                    ],
                    'IMAGES_TYPE' => [
                        'title' => $this->l('Typ zdjęcia produktu'),
                        'desc' => $this->l('Minimalne rozmiary zdjęcia - dłuższy bok min. 500px'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => $this->getImageTypes('products')
                    ],
                    'IMAGES_MANUFACTURER_TYPE' => [
                        'title' => $this->l('Typ zdjęcia producenta'),
                        'desc' => $this->l('Minimalne rozmiary zdjęcia - dłuższy bok min. 500px'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => $this->getImageTypes('manufacturers')
                    ],
                    'IMAGES_UPLOAD_TYPE' => [
                        'title' => $this->l('Sposób wysyłania zdjęć do Allegro'),
                        'desc' => $this->l('Zalecamy przesyłanie zdjęć za pomocą cURL - w przypadku pracy lokalnie lub dodatkowych blokad po stronie serwera możesz skorzystać z opcji binarnej'),
                        'type' => 'select',
                        'identifier' => 'id_upload_type',
                        'list' => [
                            ['id_upload_type' => 'CURL', 'name' => $this->l('cURL (domyślnie)')],
                            ['id_upload_type' => 'BINARY', 'name' => $this->l('binarnie')]
                        ],
                    ],
                    'IMAGES_CACHE' => [
                        'title' => $this->l('Cache’owanie zdjęć'),
                        'desc' => $this->l('Zachęcamy, aby nie blokować możliwości cache’owania zdjęć po stronie Allegro - mechanizm ten wpływa pozytywnie na szybkość i niezawodność wystawiania ofert'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => [
                            ['id' => 0, 'name' => $this->l('brak cache')],
                            ['id' => 12, 'name' => $this->l('12 godzin')],
                            ['id' => 24, 'name' => $this->l('24 godziny')],
                            ['id' => 120, 'name' => $this->l('5 dni')],
                            ['id' => 168, 'name' => $this->l('7 dni (domyślne)')]
                        ]
                    ]
                ],
                'submit' => ['title' => $this->l('Zapisz')]
            ],
            'productization' => [
                'title' =>	$this->l('Ustawienia wystawiania w Katalogu Allegro / Produktyzacja'),
                'fields' =>	[
                    'PRODUCTIZATION_SEARCH' => [
                        'title' => $this->l('Wyszukaj produkt w katalogu Allegro'),
                        'type' => 'checkbox' // type checkbox uses json_decode
                    ],
                    'PRODUCTIZATION_NAME' => [
                        'title' => $this->l('Tytuł oferty przy wystawianiu do Katalogu Allegro'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => [
                            ['id' => 'prestashop',  'name' => $this->l('użyj nazwy produktu z PrestaShop')],
                            ['id' => 'prestashop_copy', 'name' => $this->l('użyj nazwy produktu z PrestaShop, z możliwością skopiowania z Katalogu Allegro')],
                            ['id' => 'allegro',  'name' => $this->l('użyj nazwy produktu z Katalogu Allegro')]
                        ]
                    ],
                    'PRODUCTIZATION_DESCRIPTION' => [
                        'title' => $this->l('Opis przy wystawianiu do Katalogu Allegro'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => [
                            ['id' => 'prestashop',  'name' => $this->l('użyj opisu z PrestaShop i wybranego szablonu')],
                            ['id' => 'allegro', 'name' => $this->l('użyj opisu pobranego z Katalogu Allegro (jeśli istnieje)')]
                        ]
                    ],
                    'PRODUCTIZATION_IMAGES' => [
                        'title' => $this->l('Zdjęcia przy wystawianiu do Katalogu Allegro'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => [
                            ['id' => 'prestashop', 'name' => $this->l('użyj zdjęć z PrestaShop i wybranego szablonu')],
                            ['id' => 'allegro', 'name' => $this->l('użyj zdjęć pobranych z Katalogu Allegro (jeśli istnieją)')],
                            ['id' => 'merge', 'name' => $this->l('połącz zdjęcia z PrestaShop i Katalogu Allegro (ryzyko zduplikowanych zdjęć)')]
                        ]
                    ],
                    'hr_productization_1' => [
                        'name' => 'hr_productization_1',
                        'type' => 'hr'
                    ],
                    'PRODUCTIZATION_SHOW_REFERENCE' => [
                        'title' => $this->l('Wyświetl kod referencyjny produktu podczas wystawiania'),
                        'type' => 'bool'
                    ],
                    'PRODUCTIZATION_SHOW_GTIN' => [
                        'title' => $this->l('Wyświetl kod GTIN produktu podczas wystawiania'),
                        'desc' => $this->l('EAN13, ISBN oraz UPC - jeśli jest wpisany w produkcie'),
                        'type' => 'bool'
                    ],
                    'PRODUCTIZATION_SHOW_MPN' => [
                        'title' => $this->l('Wyświetl kod MPN produktu podczas wystawiania'),
                        'type' => 'bool'
                    ]
                ],
                'submit' => ['title' => $this->l('Zapisz')]
            ],
            'product_link' => [
                'title' =>	$this->l('Link do oferty na stronie produktu'),
                'description' => (version_compare(_PS_VERSION_, '1.7.0.0', '>=')
                    ? $this->l('Umieść ten specjalny znacznik w jednym z plików, aby użyć tej funkcji na stronie produktu') .
                        '<br>catalog/_partials/product-add-to-cart.tpl
                        <br>catalog/_partials/product-additional-info.tpl
                        <br>catalog/_partials/product-customization.tpl
                        <br>catalog/_partials/product-details.tpl
                        <br>catalog/_partials/product-prices.tpl
                        <br>catalog/_partials/product-discounts.tpl
                        <br>catalog/_partials/product-variants.tpl'
                    : $this->l('Umieść ten specjalny znacznik w pliku \'product.tpl \', aby użyć tej funkcji na stronie produktu')) .
                    '<br><br>{hook h=\'displayProductAllegroAuctionLink\' product=$product}',
                'fields' =>	[
                    'FRONT_DISPLAY_LINK' => [
                        'title' => $this->l('Wyświetl link do oferty na stronie produktu'),
                        'type' => 'bool'
                    ],
                    'FRONT_DISPLAY_LINK_HOOK' => [
                        'title' => $this->l('Hook wyświetlający link'),
                        'type' => 'select',
                        'identifier' => 'id_hook',
                        'list' => $front_hooks,
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['FRONT_DISPLAY_LINK' => 1]
                        )
                    ]
                ],
                'submit' => ['title' => $this->l('Zapisz')]
            ],
            'sync_prices' => [
                'title' =>	$this->l('Ustawienia cen i synchronizacja'),
                'fields' =>	[
                    'MARKUP_PERCENT' => [
                        'title' => $this->l('Narzut ceny dla produktów'),
                        'suffix' => '%',
                        'class' => 'fixed-width-sm xcast xcast-float xcast-unsigned mark-as-account-option',
                        'type' => 'text'
                    ],
                    'MARKUP_VALUE' => [
                        'title' => $this->l('Marża ceny dla produktów'),
                        'suffix' => 'zł',
                        'class' => 'fixed-width-sm xcast xcast-float xcast-unsigned mark-as-account-option',
                        'type' => 'text'
                    ],
                    'MARKUP_CALCULATION' => [
                        'title' => $this->l('Dolicz narzut/marżę do'),
                        'class' => 'mark-as-account-option',
                        'type' => 'select',
                        'identifier' => 'id_markup_calculation',
                        'list' => [
                            ['id_markup_calculation' => 'WITHOUT_INDIVIDUAL_PRICE', 'name' => $this->l('tylko do produktów bez ceny indywidualnej')],
                            ['id_markup_calculation' => 'ALL', 'name' => $this->l('wszystkich produktów')]
                        ]
                    ],
                    'AUCTION_CALCULATE_FEES' => [
                        'title' => $this->l('Doliczaj prowizje za sprzedaż i promowanie'),
                        'class' => 'mark-as-account-option',
                        'type' => 'select',
                        'identifier' => 'id_fees_calculation',
                        'list' => [
                            ['id_fees_calculation' => '0', 'name' => $this->l('nie doliczaj')],
                            ['id_fees_calculation' => '2', 'name' => $this->l('tylko dla produktów bez ceny indywidualnej')],
                            ['id_fees_calculation' => '1', 'name' => $this->l('dla wszystkich produktów')]
                        ]
                    ],
                    'AUCTION_PRICE_CUSTOMER_GROUP' => [
                        'title' => $this->l('Przeliczaj ceny według grupy'),
                        'class' => 'mark-as-account-option',
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => $customerGroups
                    ],
                    'AUCTION_MARKETPLACE_CONVERSION_RATE' => [
                        'title' => $this->l('Kurs wymiany walut dla rynków zagranicznych'),
                        'type' => 'select',
                        'identifier' => 'id_marketplace_conversion_rate',
                        'list' => [
                            ['id_marketplace_conversion_rate' => 'CURRENCY', 'name' => $this->l('przeliczaj wg waluty danego rynku')],
                            ['id_marketplace_conversion_rate' => 'VALUE', 'name' => $this->l('własny kurs wymiany')]
                        ]
                    ],
                    'AUCTION_MARKETPLACE_CONVERSION_RATE_VALUE' => [
                        'title' => '',
                        'type' => 'checkbox', // type checkbox uses json_decode
                        'currencies' => $marketplacesCurrencies,
                        'currencyDefault' => Currency::getDefaultCurrency(),
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['AUCTION_MARKETPLACE_CONVERSION_RATE' => 'VALUE']
                        )
                    ],
                    'PRICE_ROUND' => [
                        'title' => $this->l('Zaokrąglaj ceny do pełnej kwoty'),
                        'type' => 'bool'
                    ],
                    'PRICE_BASE' => [
                        'title' => $this->l('Używaj tylko ceny bazowej produktu'),
                        'desc' => $this->l('Włączenie tej opcji spowoduje pominięcie cen promocyjnych i rabatów grupowych'),
                        'type' => 'bool'
                    ],
                    'PRICE_TAX' => [
                        'title' => $this->l('Wyślij wartość podatku VAT do Allegro'),
                        'type' => 'bool'
                    ],
                    'hr_sync_prices_1' => [
                        'name' => 'hr_sync_prices_1',
                        'type' => 'hr'
                    ],
                    'PRICE_UPDATE' => [
                        'title' => $this->l('Aktualizuj ceny na Allegro'),
                        'class' => 'mark-as-account-option',
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => PriceUpdateSettings::toChoseList()
                    ],
                    'AUCTION_CHECK_BADGES' => [
                        'title' => $this->l('Sprawdzaj kampanie promocyjne przypisane do ofert'),
                        'desc' => $this->l('Sprawdzaj podczas aktualizacji cen czy Allegro przypisało ofertę do kampanii ze specjalną ceną/ofertą') . '<br>' .
                            $this->l('Jeżeli tak, pominiemy aktualizacje ceny takiej oferty'),
                        'class' => 'mark-as-account-option',
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldNotMatch(),
                            ['PRICE_UPDATE' => 0]
                        )
                    ]
                ],
                'submit' => ['title' => $this->l('Zapisz')]
            ],
            'sync_quantities' => [
                'title' =>	$this->l('Ustawienia ilości, aktywności i synchronizacja'),
                'fields' =>	[
                    'QUANITY_CHECK' => [
                        'title' => $this->l('Nadzoruj stany magazynowe'),
                        'desc' => $this->l('Wyłączenie tej opcji spowoduje możliwość sprzedaży ilości niezależnie od stanu magazynowego w sklepie'),
                        'type' => 'bool'
                    ],
                    'QUANITY_ALLEGRO_UPDATE' => [
                        'title' => $this->l('Aktualizuj stany magazynowe w Allegro'),
                        'class' => 'mark-as-account-option',
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['QUANITY_CHECK' => 1]
                        )
                    ],
                    'QUANITY_ALLEGRO_ALWAYS_MAX' => [
                        'title' => $this->l('Utrzymuj maksymalne stany magazynowe na Allegro'),
                        'desc' => $this->l('Ustawia zawsze dostępną ilość z PrestaShop na Allegro') . '<br>' .
                            $this->l('np.: przy zwiększaniu ilości z 5 na 10, na Allegro będzie dostępne 10 sztuk produktu'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['QUANITY_CHECK' => 1, 'QUANITY_ALLEGRO_UPDATE' => 1]
                        )
                    ],
                    'QUANITY_ALLEGRO_VALUE_MAX' => [
                        'title' => $this->l('Maksymalny stan magazynowy na Allegro'),
                        'desc' => $this->l('Puste pole (luz zero) oznacza maksymalną dostępną ilość produktów') . '<br>' .
                            $this->l('Wpisanie w tej opcji np.: 100 sztuk, ustawi maksymalnie 100 sztuk na sprzedaż w Allegro (mimo posiadania większej ilości na stanie)'),
                        'suffix' => $this->l('szt.'),
                        'class' => 'fixed-width-sm xcast xcast-int',
                        'type' => 'text',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['QUANITY_CHECK' => 1, 'QUANITY_ALLEGRO_UPDATE' => 1]
                        )
                    ],
                    'CLOSE_AUCTION_TRESHOLD' => [
                        'title' => $this->l('Ilość produktu poniżej której wymuszamy zamknięcie oferty'),
                        'desc' => $this->l('Puste pole (luz zero) aby nie używać tej opcji') . '<br>' .
                            $this->l('Opcja przydatna szczególnie w sytuacji, gdy chcemy mieć pewność, że zawsze będziemy mieli określoną ilość sztuk w sklepie pomimo sprzedaży na Allegro') . '<br>' .
                            $this->l('Domyślnie, oferta zamykana jest gdy jej stan zejdzie poniżej 1 sztuki'),
                        'suffix' => $this->l('szt.'),
                        'class' => 'fixed-width-sm xcast xcast-int mark-as-account-option',
                        'type' => 'text',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['QUANITY_CHECK' => 1, 'QUANITY_ALLEGRO_UPDATE' => 1]
                        )
                    ],
                    'PRODUCT_ASSOC_CLOSE_UNACTIVE' => [
                        'title' => $this->l('Zamykaj oferty na Allegro po wyłączeniu produktu w sklepie'),
                        'desc' => $this->l('Po wyłączeniu produktu w sklepie, jeśli był on powiązany z ofertą Allegro, zostanie ona zamknięta') . '<br>' .
                            $this->l('Bazuje na hooku "actionObjectProductUpdateAfter"'),
                        'type' => 'bool'
                    ],
                    'PRODUCT_ASSOC_CLOSE_UNACTIVE_DB' => [
                        'title' => $this->l('Zamykaj oferty na Allegro według wyłączonych produktów w bazie danych'),
                        'desc' => $this->l('Sprawdza wyłączone produkty w bazie danych, jeśli był on powiązany z ofertą Allegro, zostanie ona zamknięta') . '<br>' .
                            $this->l('Roziązanie dla integracji które zarządzają aktywnością produktów bez wykorzystywania hooków, np.: x13import'),
                        'type' => 'bool'
                    ],
                    'PRODUCT_ASSOC_CLOSE_DELETED' => [
                        'title' => $this->l('Zamykaj oferty na Allegro po usunięciu produktu lub kombinacji'),
                        'desc' => $this->l('Po usunięciu produktu/kombinacji ze sklepu, jeśli był on powiązany z ofertą Allegro, zostanie ona zamknięta'),
                        'type' => 'bool'
                    ],
                    'PRODUCT_ASSOC_CLOSE_SKIP_BID_AUCTION' => [
                        'title' => $this->l('Pomijaj zamykanie Licytacji gdy posiadają one oferty kupna'),
                        'desc' => $this->l('Jeśli ta opcja nie będzie aktywna, a posiadasz ofertę w trybie licytacji z przynajmniej jedną ofertą kupna, osoba licytująca automatycznie wygra taką ofertę w momencie jej zamknięcia.'),
                        'type' => 'bool'
                    ],
                    'hr_sync_quantities_1' => [
                        'name' => 'hr_sync_quantities_1',
                        'type' => 'hr'
                    ],
                    'DISABLE_AUTO_RENEW_FOR_OLDER_OFFERS' => [
                        'title' => $this->l('Wyłącz automatycznie wznawianie zduplikowanych ofert'),
                        'desc' => $this->l('Indywidualnie wyłącza opcje automatycznego wznawiania dla starszych ofert powiązanych z tym samym produktem/kombinacją (duplikatów)') . '<br>' .
                            $this->l('Jeśli wystawiasz produkty/kombinacje poraz kolejny jako nowa oferta po zakończeniu poprzedniej, tworzysz duplikaty które zostaną wznowione') . '<br>' .
                            '<b>' . $this->l('Użyj tej opcji, przed włączeniem automatycznego wznawiania, aby nie wznawiać starszych zduplikowanych ofert!') . '</b><br>' .
                            '<b>' . $this->l('Działanie jest jednorazowe i obejmuje wszystkie przypisane oferty!') . '</b>',
                        'type' => 'button',
                        'button_label' => $this->l('Wyłącz automatycznie wznawianie zduplikowanych ofert'),
                        'button_id' => 'disableAutoRenewForOlderOffers',
                        'button_class' => 'btn btn-default',
                        'button_href' => $this->context->link->getAdminLink('AdminXAllegroConfiguration') . '&disableAutoRenewForOlderOffers'
                    ],
                    'QUANTITY_AUTO_RENEW' => [
                        'title' => $this->l('Włącz automatycznie wznawianie ofert'),
                        'desc' => $this->l('Automatycznie wznawia zakończone oferty, gdy powiązany produkt wróci na stan w magazynie') . '<br>' .
                            $this->l('Dotyczy tylko ofert których format sprzedaży to "Kup teraz"'),
                        'class' => 'mark-as-account-option',
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['QUANITY_CHECK' => 1, 'QUANITY_ALLEGRO_UPDATE' => 1]
                        )
                    ],
                    'QUANTITY_AUTO_RENEW_THRESHOLD' => [
                        'title' => $this->l('Ilość produktu powyżej której automatycznie wznowimy ofertę'),
                        'desc' => $this->l('Nie może być niższa niż wartość w opcji "Ilość produktu poniżej której wymuszamy zamknięcie oferty"'),
                        'suffix' => $this->l('szt.'),
                        'class' => 'fixed-width-sm xcast xcast-int mark-as-account-option',
                        'type' => 'text',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['QUANITY_CHECK' => 1, 'QUANITY_ALLEGRO_UPDATE' => 1, 'QUANTITY_AUTO_RENEW' => 1]
                        )
                    ],
                    'PRODUCT_ASSOC_RENEW_ONLY_ACTIVE' => [
                        'title' => $this->l('Wznawiaj automatycznie oferty tylko dla włączonych produktów'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['QUANITY_CHECK' => 1, 'QUANITY_ALLEGRO_UPDATE' => 1, 'QUANTITY_AUTO_RENEW' => 1]
                        )
                    ],
                    'OFFER_RENEW_KEEP_PROMOTION' => [
                        'title' => $this->l('Ustawienia promowania wznawianej oferty'),
                        'class' => 'mark-as-account-option',
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => [
                            ['id' => 1, 'name' => $this->l('zostaw ustawione opcje promowania')],
                            ['id' => 0, 'name' => $this->l('usuń opcje promowania')]
                        ]
                    ],
                    'OFFER_RENEW_MAX_DAYS' => [
                        'title' => $this->l('Wznawiaj tylko oferty nie starsze niż'),
                        'desc' => $this->l('Puste pole (luz zero) aby nie używać tej opcji') . '<br>' .
                            sprintf($this->l('Nie ma możliwości wznowienia oferty po %d dniach od jej zakończenia (zostaje przeniesiona do archiwum Allegro)'), XAllegroApi::OFFER_DAYS_BEFORE_ARCHIVE) .
                            (XAllegroAuction::countClosedEmptyEndDate()
                                ? '<br><b>' . $this->l('Twoje zmapowane oferty nie posiadają uzupełnionej daty zakończenia') . '</b>' .
                                    '<br><b>' . $this->l('Aby powyższe ustawienie działało poprawnie uruchom opcje "Wymuś stan ofert według Allegro"') . '</b>'
                                : ''),
                        'suffix' => $this->l('dni'),
                        'class' => 'fixed-width-sm xcast xcast-int',
                        'type' => 'text'
                    ],
                    'OFFER_RENEW_SKIP_ENDED_BY_ADMIN' => [
                        'title' => $this->l('Nie wznawiaj automatycznie ofert zamkniętych przez pracownika Allegro'),
                        'desc' => $this->l('Oferty zamknięte przez pracownika Allegro zostaną oznaczone na liście ofert jako "Nie wznawiaj"'),
                        'type' => 'bool'
                    ],
                    'hr_sync_quantities_2' => [
                        'name' => 'hr_sync_quantities_2',
                        'type' => 'hr'
                    ],
                    'INACTIVE_PRODUCTS_SKIP' => [
                        'title' => $this->l('Pomijaj nieaktywne produkty podczas wystawiania, lub ręcznego wznawiania ofert'),
                        'type' => 'bool'
                    ],
                    'QUANITY_ALLEGRO_OOS' => [
                        'title' => $this->l('Pomijaj produkty z flagą "Pozwól zamawiać"'),
                        'desc' => $this->l('Produkty w sklepie oznaczone jako "Pozwól zamawiać" zostaną pominięte podczas wystawiania oraz aktualizacji stanów magazynowych na Allegro'),
                        'type' => 'bool'
                    ],
                    'QUANITY_ALLEGRO_HOOK_SKIP' => [
                        'title' => $this->l('Pomijaj aktualizację ilości za pomocą hooka'),
                        'desc' => $this->l('Nie aktualizuje stanów magazynowych na Allegro w momencie wywołania hooka "actionObjectStockAvailableUpdateAfter" ') . '<br>' .
                            $this->l('Hook uruchamia się podczas składania zamówień przez użytkowników na sklepie, zmiany stanów magazynowych podczas edycji produktu, lub przez API PrestaShop') . '<br>' .
                            $this->l('Użycie zalecane tylko w momencie zauważalnych spadków wydajności'),
                        'type' => 'bool'
                    ]
                ],
                'submit' => ['title' => $this->l('Zapisz')]
            ],
            'sync_orders' => [
                'title' =>	$this->l('Synchronizacja zamówień'),
                'fields' =>	[
                    'IMPORT_ORDERS' => [
                        'title' => $this->l('Importuj zamówienia z Allegro do sklepu'),
                        'type' => 'bool'
                    ],
                    'ORDER_IMPORT_UNASSOC_PRODUCTS' => [
                        'title' => $this->l('Pobieraj zamówienia i pozycje dla niepowiązanych ofert'),
                        'desc' => $this->l('Dodaje niepowiązane oferty do szczegółów zamówienia jako pusty produkt') . '<br>' .
                            $this->l('Zapewnia 100% pokrycia zamówienia na wygenerowanej fakturze'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_IMPORT_UNASSOC_PRODUCTS_EXTERNAL' => [
                        'title' => $this->l('Użyj sygnatury oferty (external.id) dla niepowiązanych ofert'),
                        'desc' => $this->l('Dodaje sygnaturę oferty, jako kod referencyjny (indeks) produktu, dla niepowiązanej oferty dodanej do szczegółów zamówienia') . '<br>' .
                            $this->l('Domyślny kod referencyjny niepowiązanej oferty') . ': "x13allegro-empty-product"',
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1, 'ORDER_IMPORT_UNASSOC_PRODUCTS' => 1]
                        )
                    ],
                    'ORDER_IMPORT_UNASSOC_SUMMARY' => [
                        'title' => $this->l('Pobieraj pełną kwotę zamówienia do podsumowania'),
                        'desc' => $this->l('W przypadku nie pobierania niepowiązanych ofert można zdecydować jaka kwota będzie widnieć w podsumowaniu zamówienia') . '<br>' .
                            $this->l('Wyłączenie tej opcji powoduje, że kwota w podsumowaniu obliczana jest na podstawie tylko powiązanych ofert'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1, 'ORDER_IMPORT_UNASSOC_PRODUCTS' => 0]
                        )
                    ],
                    'QUANITY_SHOP_UPDATE' => [
                        'title' => $this->l('Aktualizuj stany magazynowe w sklepie, po zakupie na Allegro'),
                        'desc' => $this->l('Zdejmuje stany magazynowe w sklepie na podstawie zamówień pobranych z Allegro') . '<br>' .
                            $this->l('Działa niezależnie od funkcji "Importuj zamówienia z Allegro do sklepu"'),
                        'type' => 'bool'
                    ],
                    'ORDER_DATE_FROM_CHECKOUT_FORM' => [
                        'title' => $this->l('Użyj daty sprzedaży jako data utworzenia zamówienia'),
                        'desc' => $this->l('Uzupełnia datę utworzenia zamówienia na podstawie faktycznej daty sprzedaży z formularza pozakupowego Allegro') . '<br>' .
                            $this->l('Domyślnie data utworzenia zamówienia jest datą jego importu do sklepu'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_ADD_PAYMENT_WHEN_COD' => [
                        'title' => $this->l('Uzupełniaj płatność dla nowych zamówień COD (Płatność przy odbiorze)'),
                        'desc' => $this->l('Domyślnie moduł nie dodaje płatności dla nowych zamówień ze statusem "Płatność przy odbiorze".') . '<br>' .
                            $this->l('Płatność uzupełnia się w momencie zmiany na status z włączoną opcją "opłacone", identyfikator transakcji Allegro także zostanie uzupełniony') . '<br>' .
                            $this->l('Jest to domyślne zachowanie PrestaShop, po włączeniu tej opcji płatność zostanie dodana od razu w momencie tworzenia nowego zamówienia'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_UNASSOC_CARRIER_TAX_ID' => [
                        'title' => $this->l('Wymuś podatek dla niepowiązanych przewoźników'),
                        'desc' => $this->l('Ustawia wybrany podatek do kosztów wysyłki w przypadku, gdy dostawca w zamówieniu nie jest powiązany z przewoźnikiem w PrestaShop'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => array_merge([['id' => 0, 'name' => 'Nie wymuszaj']], $taxRulesGroups),
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'hr_sync_orders_1' => [
                        'name' => 'hr_sync_orders_1',
                        'type' => 'hr',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'REGISTER_CUSTOMER' => [
                        'title' => $this->l('Utwórz nowe konto w sklepie dla kupującego'),
                        'desc' => $this->l('Wyłączenie tej opcji spowoduje użycie konta Gościa - jeśli dostępne') . '<br>' .
                            $this->l('Nowe konta tworzone są automatycznie w przypadku gdy konto Gościa jest wyłączone') . '<br>' .
                            $this->l('W przypadku gdy nie tworzymy nowych kont dla kupujących, automatycznie przypisujemy grupę') . ': ' .
                            (new Group((int)Configuration::get('PS_GUEST_GROUP'), $this->context->language->id))->name,
                        'type' => 'bool',
                        'disabled' => !Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
                        'defaultValue' => Configuration::get('PS_GUEST_CHECKOUT_ENABLED') ? null : 1,
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'REGISTER_CUSTOMER_GROUP' => [
                        'title' => $this->l('Przypisz nowe konto kupującego do grupy'),
                        'type' => 'checkbox', // type checkbox uses json_decode
                        'choices' => $customerGroups,
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1, 'REGISTER_CUSTOMER' => 1]
                        )
                    ],
                    'REGISTER_CUSTOMER_GROUP_DEFAULT' => [
                        'title' => $this->l('Domyślna grupa kupującego'),
                        'type' => 'select',
                        'identifier' => 'id_group',
                        'list' => $groups,
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1, 'REGISTER_CUSTOMER' => 1]
                        )
                    ],
                    'ORDER_SEND_CUSTOMER_MAIL' => [
                        'title' => $this->l('Wysyłaj e-maile sklepowe do klientów'),
                        'desc' => $this->l('Wysyła e-maile po zmianie statusu zamówienia, dodaniu numeru śledzienna, etc.'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'REGISTER_CUSTOMER_SEND_MAIL' => [
                        'title' => $this->l('Wysyłaj e-mail do klienta po utworzeniu konta'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1, 'REGISTER_CUSTOMER' => 1, 'ORDER_SEND_CUSTOMER_MAIL' => 1]
                        )
                    ],
                    'hr_sync_orders_2' => [
                        'name' => 'hr_sync_orders_2',
                        'type' => 'hr',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_MESSAGE_CONFIGURATION' => [
                        'title' => $this->l('Wiadomość do zamówienia'),
                        'type' => 'checkbox', // type checkbox uses json_decode
                        'choices' => [
                            ['key' => OrderMessage::BUYER_LOGIN, 'name' => $this->l('login kupującego')],
                            ['key' => OrderMessage::BUYER_MESSAGE, 'name' => $this->l('wiadomość od kupującego')],
                            ['key' => OrderMessage::SELLER_LOGIN, 'name' => $this->l('login sprzedającego')],
                            ['key' => OrderMessage::CHECKOUT_FORM_ID, 'name' => $this->l('numer zamówienia')],
                            ['key' => OrderMessage::OFFERS, 'name' => $this->l('lista ofert')],
                            ['key' => OrderMessage::DELIVERY, 'name' => $this->l('informacje o dostawie')],
                            ['key' => OrderMessage::PAYMENT, 'name' => $this->l('informacje o płatności')]
                        ],
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_SEND_MAIL' => [
                        'title' => $this->l('Otrzymuj dodatkowe powiadomienie o złożeniu zamówienia'),
                        'desc' => $this->l('Aby otrzymywać powiadomienia konieczny jest zainstalowany i poprawnie skonfigurowany moduł "ps_emailalerts/mailalerts"'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_CUSTOMER_MESSAGE_CONTACT' => [
                        'title' => $this->l('Kontakt dla wiadomości do sprzedawcy'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => $orderContacts,
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'hr_sync_orders_3' => [
                        'name' => 'hr_sync_orders_3',
                        'type' => 'hr',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'CONTEXT_EMPLOYEE' => [
                        'title' => $this->l('Pracownik przypisany do operacji wykonywanych przez moduł'),
                        'desc' => $this->l('Pracownik który będzie przypisany do operacji zmian statusów zamówień i ruchów magazynowych') . '<br>' .
                            $this->l('Język tego pracownika użyty zostanie w wiadomościach "Kontakt dla wiadomości do sprzedawcy"'),
                        'type' => 'select',
                        'identifier' => 'id',
                        'list' => $orderEmployees,
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'hr_sync_orders_4' => [
                        'name' => 'hr_sync_orders_4',
                        'type' => 'hr',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_ALLEGRO_SEND_SHIPPING' => [
                        'title' => $this->l('Wysyłaj numery śledzenia do Allegro'),
                        'desc' => $this->l('Automatycznie wysyła uzupełnione numery śledzenia przesyłek po uruchomieniu zadania CRON'),
                        'type' => 'bool',
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ],
                    'ORDER_INVOICE_AUTO_PS_INVOICE' => [
                        'title' => $this->l('Wysyłaj fakturę PrestaShop do Allegro'),
                        'desc' => $this->l('Automatycznie wysyła fakturę PrestaShop w momencie jej wygenerowania') . '<br>' .
                            $this->l('Faktury PrestaShop muszą być włączone'),
                        'type' => 'bool',
                        'disabled' => !Configuration::get('PS_INVOICE'),
                        'defaultValue' => Configuration::get('PS_INVOICE') ? null : 0,
                        'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                            ConfigurationDependencies::fieldMatch(),
                            ['IMPORT_ORDERS' => 1]
                        )
                    ]
                ],
                'submit' => ['title' => $this->l('Zapisz')]
            ],
            'cron' => [
                'title' =>	$this->l('Synchronizacja CRON'),
                'fields' =>	[
                    'CRON_URL' => [
                        'title' => $this->l('Adres wywołania dla CRON'),
                        'desc' => $this->l('Ostatnie poprawne uruchomienie') . ': ' . $syncLastSuccess . '<br><hr/>' .
                            $this->l('Jeśli Twój serwer ma problem z długością odpowiedzi przy zadaniu CRON, dodaj parametr "noprint" do linku. Uruchomienie zadania z parametrem ukryje szczegóły działania procesu synchronizacji (logi zostaną utworzone ale nie będą wyświetlone).'),
                        'type' => 'text',
                        'disabled' => true,
                        'defaultValue' => trim((new Shop((int)Configuration::get('PS_SHOP_DEFAULT')))->getBaseURL(Configuration::get('PS_SSL_ENABLED'), false), '/') .
                            $this->module->getPathUri() . 'sync.php?token=' . XAllegroConfiguration::get('SYNC_TOKEN')
                    ],
                    'hr_cron_1' => [
                        'name' => 'hr_cron_1',
                        'type' => 'hr'
                    ],
                    'UPDATE_OFFERS_CHUNK' => [
                        'title' => $this->l('Liczba synchronizowanych ofert'),
                        'desc' => $this->l('Liczba powiązanych ofert, których ceny/ilości/status zostaną sprawdzone i zaktualizowane podczas jednorazowego uruchomienia skryptu sync.php') . '<br>' .
                            $this->l('Liczba zdarzeń z dziennika zmian ofert Allegro, dla każdego konta, które zostaną przetworzone podczas jednorazowego uruchomienia skryptu sync.php') . '<br>' .
                            '<b>' . $this->l('Uwaga!!! Zbyt duży limit może spowolnić wykonywanie skryptu, lub przekroczyć maksymalny czas wykonania na serwerze') . '</b> (max: ' . XAllegroApi::EVENT_OFFER_MAX . ')',
                        'class' => 'fixed-width-sm xcast xcast-int',
                        'type' => 'text'
                    ],
                    'IMPORT_ORDERS_CHUNK' => [
                        'title' => $this->l('Liczba synchronizowanych zdarzeń zamówień'),
                        'desc' => $this->l('Liczba zdarzeń z dziennika zamówień, dla każdego konta, które zostaną przetworzone podczas jednorazowego uruchomienia skryptu sync.php') . '<br>' .
                            '<b>' . $this->l('Uwaga!!! Zbyt duży limit może spowolnić wykonywanie skryptu, lub przekroczyć maksymalny czas wykonania na serwerze') . '</b> (max: ' . XAllegroApi::EVENT_ORDER_MAX . ')',
                        'class' => 'fixed-width-sm xcast xcast-int',
                        'type' => 'text'
                    ],
                    'DELETE_ARCHIVED_OFFERS' => [
                        'title' => $this->l('Usuń zarchiwizowane oferty starsze niż'),
                        'desc' => $this->l('Usuwa zarchiwizowane powiązania ofert w bazie danych') . '<br>' .
                            $this->l('Opcja wprowadzona w ramach zabezpieczenia przed ewentualnymi błędami związanymi z pobraniem szczegółów ofert z Allegro API') . '<br>' .
                            '<b>' . $this->l('Uwaga!!! Ustawienie wartości zerowej spowoduje natychmiastowe usunięcie powiązań zarchiwizowanych oferty') . '</b>',
                        'suffix' => $this->l('dni'),
                        'class' => 'fixed-width-sm xcast xcast-int',
                        'type' => 'text'
                    ]
                ],
                'submit' => ['title' => $this->l('Zapisz')]
            ],
            'troubleshooting' => [
                'title' => $this->l('Rozwiązywanie problemów'),
                'fields' => [
                    'FORCE_AUCTION_STATE_BY_ALLEGRO' => [
                        'title' => $this->l('Wymuszenie stanu ofert według informacji z Allegro'),
                        'desc' => 'Więcej szczegółów na temat działania tego mechanizmu znajduje się w <a target="_blank" href="https://x13.pl/doc/dokumentacja-integracja-allegro-z-prestashop#problemy-aktualizacja-cen-i-ilosci">dokumentacji</a>.',
                        'type' => 'button',
                        'button_label' => $this->l('Wymuś stan ofert według Allegro'),
                        'button_id' => 'syncAllAuctions',
                        'button_class' => 'btn btn-default'
                    ],
                    'CLEAR_ALLEGRO_CACHE' => [
                        'title' => $this->l('Wyczyść pamięć podręczną Allegro API'),
                        'desc' => $this->l('Moduł zapisuje niektóre dane z API do plików pamięci podręcznej aby zwiększyć wydajność działania modułu w panelu administracyjnym sklepu.') . '<br>' .
                            $this->l('Jeśli nie widzisz świeżo wprowadzonych zmian wykonanych przez panel Allegro, wyczyść pamięć podręczną.'),
                        'type' => 'button',
                        'button_label' => $this->l('Wyczyść pamięć podręczną'),
                        'button_id' => 'clearAllegroCache',
                        'button_class' => 'btn btn-default',
                        'button_href' => $this->context->link->getAdminLink('AdminXAllegroConfiguration') . '&clearAllegroCache'
                    ],
                    'FORCE_CURL_HTTP_VERSION_1_1' => [
                        'title' => $this->l('Wymuś protokół HTTP/1.1 do komunikacji z Allegro API'),
                        'desc' => $this->l('Użyj tej opcji, jeśli Twój serwer ma problemy z połączeniem się z Allegro API na protokole HTTP 2.'),
                        'type' => 'bool'
                    ]
                ]
            ],
            'advanced_settings' => [
                'title' => $this->l('Indywidualne ustawienia dla kont Allegro'),
                'tabs' => $allegroAccounts,
                'submit' => ['title' => $this->l('Zapisz')]
            ]
        ];

        $this->fields_options['advanced_settings']['fields'] = $allegroAccountsFields;

        foreach ($this->fields_options as &$fieldset) {
            foreach ($fieldset['fields'] as &$field) {
                $field['visibility'] = Shop::CONTEXT_ALL;
            }
        }

        if (XAllegroConfiguration::get('732_DISABLE_AUTO_RENEW_FOR_OLDER_OFFERS')) {
            unset($this->fields_options['sync_quantities']['fields']['DISABLE_AUTO_RENEW_FOR_OLDER_OFFERS']);
        }

        return $this->fields_options;
    }

    public function initPageHeaderToolbar()
    {
        if ($this->module->update) {
            $this->page_header_toolbar_btn['update_module'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroConfiguration').'&update_module',
                'desc' => $this->l('Aktualizacja modułu'),
                'icon' => 'process-icon-refresh',
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initToolbar()
    {
        if ($this->module->update) {
            $this->toolbar_btn['update_module'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroConfiguration').'&update_module',
                'desc' => $this->l('Aktualizacja modułu'),
                'class' => 'process-icon-refresh-index'
            );
        }

        parent::initToolbar();
    }

    public function postProcess()
    {
        // sprawdzaj poprawnosc API przed aktualizacja
        if (!$this->viewAccess()) {
            return false;
        }

        if (Tools::isSubmit('update_module'))
        {
            $xAllegroUpdate = new XAllegroUpdate($this->module);

            if ($xAllegroUpdate->downloadUpdate()) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroConfiguration') . '&process_update_module');
            }
            else {
                $this->errors[] = $this->l('Aktualizacja nie powiodła się! Wystąpił problem podczas pobierania i wypakowywania plików nowej wersji. Skontaktuj się z dostawcą modułu.');
            }
        }
        else if (Tools::isSubmit('process_update_module'))
        {
            $xAllegroUpdate = new XAllegroUpdate($this->module);

            if ($xAllegroUpdate->processUpdate())
            {
                Module::upgradeModuleVersion($this->module->name, $this->module->version);
                XAllegroConfiguration::updateValue('VERSION', $this->module->version);

                $this->module->sessionMessages->confirmations($this->l('Moduł X13Allegro został zaktualizowany do wersji ') . $this->module->version);
                $this->module->sessionMessages->confirmations($this->module->getConfirmations());
            }
            else {
                $this->module->sessionMessages->errors($this->l('Aktualizacja nie powiodła się! Skontaktuj się z dostawcą modułu.'));
                $this->module->sessionMessages->errors($this->module->getErrors());
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroConfiguration'));
        }

        return parent::postProcess();
    }

    public function ajaxProcessGetAccountsForSynchronization()
    {
        $accounts = [];
        foreach (XAllegroAccount::getAll() as $account) {
            $accounts[] = [
                'id' => $account->id,
                'name' => $account->username
            ];
        }

        die(json_encode([
            'success' => true,
            'accounts' => $accounts
        ]));
    }

    public function ajaxProcessGetOffersForSynchronization()
    {
        try {
            // lock sync.php during OfferFullSynchronization process
            $processLock = new ProcessLock('offer_full_synchronization', $this->module);
            $processLock->lock();

            $synchronization = new OfferFullSynchronization();
            if ((int)Tools::getValue('startSynchronization')) {
                $synchronization->actionBeforeSynchronization();
            }

            $accountId = (int)Tools::getValue('accountId');
            $offset = (int)Tools::getValue('offset');
            $limit = 1000;

            $offers = $synchronization->getOffers($accountId, $limit, $offset);

            if ($offers['count']) {
                $this->log
                    ->account($accountId)
                    ->logDatabase()
                    ->info(LogType::OFFER_FULL_SYNCHRONIZATION(), "Information about {$offers['count']} offers has been downloaded (offset: $offset, limit: $limit)");
            }

            die(json_encode([
                'success' => true,
                'result' => $offers
            ]));
        }
        catch (Exception $ex) {
            die(json_encode([
                'success' => false,
                'message' => (string)$ex
            ]));
        }
    }

    public function ajaxProcessSynchronizeOffers()
    {
        try {
            // lock sync.php during OfferFullSynchronization process
            $processLock = new ProcessLock('offer_full_synchronization', $this->module);
            $processLock->lock();

            (new OfferFullSynchronization())->synchronizeOffers();
            $processLock->unLock();

            $this->log
                ->logDatabase()
                ->info(LogType::OFFER_FULL_SYNCHRONIZATION(), 'Synchronization completed');

            // hide rebuild warning after success
            XAllegroConfiguration::updateValue('720_AUCTIONS_REBUILD', 1);

            die(json_encode([
                'success' => true
            ]));
        }
        catch (Exception $ex) {
            die(json_encode([
                'success' => false,
                'message' => (string)$ex
            ]));
        }
    }

    private function disableAutoRenewForOlderOffers()
    {
        $result = Db::getInstance()->executeS('
            SELECT GROUP_CONCAT(`id_auction` ORDER BY `id_auction`) as `offers`
            FROM `' . _DB_PREFIX_ . 'xallegro_auction`
            WHERE `selling_mode` = "' . pSQL(SellingModeType::BUY_NOW) . '"
            GROUP BY `id_xallegro_account`, `id_product`, `id_product_attribute`'
        );

        if (!empty($result)) {
            foreach ($result as $row) {
                $offers = explode(',', $row['offers']);

                if (count($offers) > 1) {
                    array_pop($offers);

                    foreach ($offers as $offerId) {
                        XAllegroAuction::updateAuctionAutoRenew($offerId, 0);
                    }
                }
            }
        }
    }

    /**
     * @param string $type
     * @return array
     */
    private function getImageTypes($type)
    {
        $types = [[
            'id' => '',
            'name' => 'oryginalny rozmiar'
        ]];

        foreach (ImageType::getImagesTypes($type, true) as $imageType) {
            if ($imageType['width'] >= 500 || $imageType['height'] >= 500) {
                $types[] = array(
                    'id' => $imageType['name'],
                    'name' => $imageType['name'] . ' (' . $imageType['width'] . ' x ' . $imageType['height'] . ')'
                );
            }
        }

        return $types;
    }
}