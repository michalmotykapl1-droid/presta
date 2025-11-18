<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

use x13import\Component\ConfigurationDependencies;
use x13import\Component\ProcessLock;

class AdminXImportConfigurationController extends XImportController
{
    public function __construct()
    {
        $this->table = 'ximport_configuration';
        $this->identifier = 'id_ximport_configuration';
        $this->className = 'XImportConfiguration';

        parent::__construct();

        $this->tpl_folder = 'x_import_configuration/';

        $redirectType = [
            ['redirect_type' => '404', 'name' => $this->l('brak przekierowania, strona błędu (404)')],
            ['redirect_type' => '301-category', 'name' => $this->l('do kategorii głównej, przekierowanie permanentne (301)')],
            ['redirect_type' => '302-category', 'name' => $this->l('do kategorii głównej, przekierowanie tymczasowe (302)')]
        ];

        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            $redirectType[] = ['redirect_type' => '410', 'name' => $this->l('brak przekierowania, strona błędu (410)')];
        }
        if (version_compare(_PS_VERSION_, '8.1.0', '>=')) {
            $redirectType[] = ['redirect_type' => '200-displayed', 'name' => $this->l('brak przekierowania, produkt wycofany (200)')];
            $redirectType[] = ['redirect_type' => '404-displayed', 'name' => $this->l('brak przekierowania, produkt wycofany (404)')];
            $redirectType[] = ['redirect_type' => '410-displayed', 'name' => $this->l('brak przekierowania, produkt wycofany (410)')];
            $redirectType[] = ['redirect_type' => 'default', 'name' => $this->l('domyślne zachowanie z konfiguracji PrestaShop')];
        }

        $use_token = XImportConfiguration::get('USE_TOKEN');
        if ($use_token && !XImportConfiguration::get('TOKEN')) {
            XImportConfiguration::updateValue('TOKEN', Tools::passwdGen());
        }

        $token = '?token=' . XImportConfiguration::get('TOKEN');
        $cronLastUnLock = ProcessLock::getLastUnlock('cron');
        $updateLastUnLock = ProcessLock::getLastUnlock('update');

        $this->fields_options = array(
            'urls' => array(
                'title' =>	$this->l('Adresy skryptów'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	array(
                    'URL_CRON' => array(
                        'title' => $this->l('Adres pliku CRON'),
                        'type' => 'readonly',
                        'size' => 75,
                        'readonly' => trim((new Shop((int)Configuration::get('PS_SHOP_DEFAULT')))->getBaseURL(Configuration::get('PS_SSL_ENABLED'), false), '/') . $this->module->getPathUri() . 'cron.php' . ($use_token ? $token : ''),
                        'hint' => $this->l('Pobiera najnowsze pliki z importowanych hurtowni i aktualizuje produkty.'),
                        'desc' => $this->l('Wywołuj co 2 godziny w każdej 23 minucie') . '<br>' .
                            ($cronLastUnLock ? $this->l('Ostatnie uruchomienie') . ': ' . $cronLastUnLock->format('Y-m-d H:i') : '')
                    ),
                    'URL_UPDATE' => array(
                        'title' => $this->l('Adres pliku UPDATE'),
                        'type' => 'readonly',
                        'size' => 75,
                        'readonly' => trim((new Shop((int)Configuration::get('PS_SHOP_DEFAULT')))->getBaseURL(Configuration::get('PS_SSL_ENABLED'), false), '/') . $this->module->getPathUri() . 'update.php' . ($use_token ? $token : ''),
                        'hint' => $this->l('Dodaje nowe produkty i zdjęcia.'),
                        'desc' => $this->l('Wywołuj co 15 minut') . '<br>' .
                            ($updateLastUnLock ? $this->l('Ostatnie uruchomienie') . ': ' . $updateLastUnLock->format('Y-m-d H:i') : '')
                    ),
                    'USE_TOKEN' => array(
                        'title' => $this->l('Używaj tokenu bezpieczeństwa'),
                        'type' => 'bool'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            ),
            'general' => array(
                'title' =>	$this->l('Ustawienia podstawowe'),
                'image' => '../img/t/AdminPreferences.gif',
                'description' => ' <strong>'.$this->l('Nowe produkty').'</strong> '. 
                    $this->l('zawsze mają uzupełnione wszystkie dostępne parametry, bez względu na ustawienie opcji').' <strong>'.$this->l('Aktualizuj ...').'</strong><br/><br/> '.$this->l('Wszystkie poniższe opcje które zaczynają się od').' <strong>'.$this->l('Aktualizuj ...').'</strong> '. $this->l('zadziałają') .' <strong>'.$this->l('tylko').'</strong> '.$this->l('na już').' <strong>'.$this->l('zaimportowane').'</strong> '.$this->l('produkty.').'<br/>'.
                    $this->l('Np. zmieniając opcję ').' <strong>'.$this->l('Aktualizuj nazwy ').'</strong> '. $this->l('na').' <strong>'.$this->l('TAK').'</strong> '. $this->l(', spowoduje zaktualizowanie nazwy już dodanych produktów (zostaną zmienione na takie jakie znajdują się w').' <strong>'.$this->l('XMLu').'</strong> '. $this->l(' - wraz z').' <strong>'.$this->l('wzorcem nazwy produktów').'</strong> '. $this->l('jeśli jest uzupełniony).').'<br/>'
                    ,
                'fields' =>	array(
                    'UPDATE_NAME' => array(
                        'title' => $this->l('Aktualizuj nazwy'),
                        'type' => 'bool'
                    ),
                    'UPDATE_DESC' => array(
                        'title' => $this->l('Aktualizuj długie opisy'),
                        'type' => 'bool'
                    ),
                    'UPDATE_DESC_SHORT' => array(
                        'title' => $this->l('Aktualizuj krótkie opisy'),
                        'type' => 'bool'
                    ),
                    'UPDATE_LINK_REWRITE' => array(
                        'title' => $this->l('Aktualizuj przyjazny adres URL'),
                        'type' => 'bool',
                        'desc' => $this->l('Zalecamy nie używać tej opcji w większości przypadków') . '<br />' .
                            $this->l('Przy włączonej tej opcji po ręcznej zmianie przyjaznego adresu, moduł ustawi nowego adresu URL')
                    ),
                    'UPDATE_META' => array(
                        'title' => $this->l('Aktualizuj sekcję meta'),
                        'type' => 'bool'
                    ),
                    'UPDATE_TAGS' => array(
                        'title' => $this->l('Aktualizuj tagi'),
                        'type' => 'bool'
                    ),
                    'UPDATE_IS_VIRTUAL' => array(
                        'title' => $this->l('Aktualizuj typ produktu'),
                        'type' => 'bool',
                        'desc' => $this->l('Aktualizuje typ produktu (standardowy / wirtualny)')
                    ),
                    'UPDATE_REFERENCE' => array(
                        'title' => $this->l('Aktualizuj kod referencyjny'),
                        'type' => 'bool'
                    ),
                    'UPDATE_EAN13' => array(
                        'title' => $this->l('Aktualizuj EAN'),
                        'type' => 'bool'
                    ),
                    'UPDATE_UPC' => array(
                        'title' => $this->l('Aktualizuj UPC'),
                        'type' => 'bool'
                    ),
                    'UPDATE_MPN' => array(
                        'title' => $this->l('Aktualizuj MPN'),
                        'type' => 'bool',
                        'desc' => (version_compare(_PS_VERSION_, '1.7.7', '<') ? $this->l('Opcja dostępna od PrestaShop 1.7.7') : ''),
                        'disabled' => version_compare(_PS_VERSION_, '1.7.7', '<')
                    ),
                    'UPDATE_ISBN' => array(
                        'title' => $this->l('Aktualizuj ISBN'),
                        'type' => 'bool',
                        'desc' => (version_compare(_PS_VERSION_, '1.7.0', '<') ? $this->l('Opcja dostępna od PrestaShop 1.7') : ''),
                        'disabled' => version_compare(_PS_VERSION_, '1.7', '<')
                    ),
                    'UPDATE_CONDITION' => array(
                        'title' => $this->l('Aktualizuj pole "stan"'),
                        'type' => 'bool',
                        'desc' => $this->l('Aktualizuje stan produktu (nowy, uzywany, odświeżony)')
                    ),
                    'UPDATE_SHOW_CONDITION' => array(
                        'title' => $this->l('Aktualizuj widoczność pola "stan"'),
                        'type' => 'bool',
                        'desc' => (version_compare(_PS_VERSION_, '1.7.0', '<') ? $this->l('Opcja dostępna od PrestaShop 1.7') : ''),
                        'disabled' => version_compare(_PS_VERSION_, '1.7.0', '<')
                    ),
                    'UPDATE_ONLINE_ONLY' => array(
                        'title' => $this->l('Aktualizuj dostępność online'),
                        'type' => 'bool',
                        'desc' => $this->l('Aktualizuje informacje o dostępności wyłącznie w sklepie internetowym')
                    ),
                    'UPDATE_FEATURES' => array(
                        'title' => $this->l('Aktualizuj cechy'),
                        'type' => 'bool'
                    ),
                    'UPDATE_ACCESSORIES' => array(
                        'title' => $this->l('Aktualizuj powiązane produkty'),
                        'type' => 'bool'
                    ),
                    'UPDATE_ATTACHMENTS' => array(
                        'title' => $this->l('Aktualizuj załączniki'),
                        'type' => 'bool'
                    ),
                    'UPDATE_MANUFACTURER' => array(
                        'title' => $this->l('Aktualizuj producentów'),
                        'type' => 'bool'
                    ),
                    'UPDATE_SUPPLIER' => array(
                        'title' => $this->l('Aktualizuj dostawców'),
                        'type' => 'bool'
                    ),
                    'UPDATE_CARRIERS' => array(
                        'title' => $this->l('Aktualizuj przewoźników'),
                        'type' => 'bool',
                    ),
                    'UPDATE_WEIGHT' => array(
                        'title' => $this->l('Aktualizuj wagi'),
                        'type' => 'bool'
                    ),
                    'UPDATE_DIMENSIONS' => array(
                        'title' => $this->l('Aktualizuj wymiary'),
                        'type' => 'bool',
                    ),
                    'UPDATE_CATEGORY' => array(
                        'title' => $this->l('Aktualizuj powiązania w kategoriach'),
                        'type' => 'bool',
                        'desc' => $this->l('Aktualizuje produkty w kategoriach, zalecamy wyłączenie tej opcji po jendorazowej zmianie kategorii')
                    ),
                    'UPDATE_ATTRIBUTE_DEFAULT' => array(
                        'title' => $this->l('Domyślna kombinacja'),
                        'type' => 'select',
                        'identifier' => 'id_default_attribute',
                        'list' => array(
                            array('id_default_attribute' => '0', 'name' => $this->l('bez wpływu na cenę (najtańszy)')),
                            array('id_default_attribute' => '1', 'name' => $this->l('domyślnie wg. PrestaShop'))
                        )
                    ),
                    'hr_general' => array(
                        'name' => 'hr',
                        'type' => 'hr'
                    ),
                    'SAVE_DELETED_ATTRIBUTES' => array(
                        'title' => $this->l('Zachowaj nieistniejące kombinacje'),
                        'type' => 'bool',
                        'desc' => $this->l('Przy włączonej opcji wszystkie kombinacje, ktore nie istnieją już w hurtowni lub mają zerowy stan magazynowy zostaną zachowane') . '<br />' .
                            sprintf($this->l('Wyłaczenie tej opcji spowoduje usunięcie tych kombinacji %s(zalecane)%s'), '<b>', '</b>')
                    ),
                    'ONLINE_ONLY' => array(
                        'title' => $this->l('Ustaw produkty jako dostępne tylko online'),
                        'type' => 'bool',
                        'desc' => $this->l('Ustawia dla wszystkich produktów dostępność tylko w sklepie internetowym (nie sprzedawany w sklepie stacjonarnym)')
                    ),
                    'CATEGORY_PARENTS' => array(
                        'title' => $this->l('Dodawaj produkty do kategorii nadrzędnych'),
                        'type' => 'bool',
                        'desc' => $this->l('Dodaje produkty do wszystkich kategorii nadrzędnych od kategorii domyślnej')
                    ),
                    'SKIP_EMPTY_DESCRIPTIONS' => array(
                        'title' => $this->l('Pomijaj produkty bez opisu'),
                        'type' => 'bool'
                    ),
                    'TRIM_SUPPLIER_DOMAIN_EXTENSION' => array(
                        'title' => $this->l('Usuń rozszerzenie domeny z nazwy dostawcy'),
                        'desc' => $this->l('Usuwa z nazwy rozszerzenia: .pl, .com, .info, .shop, itp...') . '<br />' .
                            $this->l('Nie dotyczy własnej nazwy dostawcy (opcja zaawansowana)'),
                        'type' => 'bool'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            ),
            'stock' => array(
                'title' =>	$this->l('Ustawienia ilości i dostępności'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	array(
                    'UPDATE_QUANTITY' => array(
                        'title' => $this->l('Aktualizuj stany magazynowe'),
                        'type' => 'bool'
                    ),
                    'UPDATE_MINIMAL_QUANTITY' => array(
                        'title' => $this->l('Aktualizuj minimalną ilość sprzedaży'),
                        'type' => 'bool'
                    ),
                    'UPDATE_STOCK_LOCATION' => array(
                        'title' => $this->l('Aktualizuj miejsce magazynowania'),
                        'type' => 'bool'
                    ),
                    'UPDATE_LOW_STOCK_THRESHOLD' => array(
                        'title' => $this->l('Aktualizuj niski poziom produktów w magazynie'),
                        'type' => 'bool',
                        'desc' => (version_compare(_PS_VERSION_, '1.7.3', '<') ? $this->l('Opcja dostępna od PrestaShop 1.7.3') : ''),
                        'disabled' => version_compare(_PS_VERSION_, '1.7.3', '<')
                    ),
                    'UPDATE_LOW_STOCK_ALERT' => array(
                        'title' => $this->l('Aktualizuj powiadomienie o niskim poziomie produktów w magazynie'),
                        'type' => 'bool',
                        'desc' => (version_compare(_PS_VERSION_, '1.7.3', '<') ? $this->l('Opcja dostępna od PrestaShop 1.7.3') : ''),
                        'disabled' => version_compare(_PS_VERSION_, '1.7.3', '<')
                    ),
                    'UPDATE_AVAILABLE_DATE' => array(
                        'title' => $this->l('Aktualizuj datę dostępności'),
                        'type' => 'bool'
                    ),
                    'UPDATE_AVAILABLE_TEXT' => array(
                        'title' => $this->l('Aktualizuj etykiety dostępności'),
                        'type' => 'bool',
                        'desc' => $this->l('Aktualizuje etykiety dostepności (Etykieta, gdy w magazynie / Etykieta, gdy niedostępny (a ponowne zamówienie dozwolone))')
                    ),
                    'UPDATE_DELIVERY_LABEL_TEXT' => array(
                        'title' => $this->l('Aktualizuj czas dostawy'),
                        'type' => 'bool',
                        'desc' => $this->l('Aktualizuje czas dostawy (produktów dostępnych w magazynie / wyprzedanych produktów z możliwością rezerwacji)') .
                            (version_compare(_PS_VERSION_, '1.7.3', '<') ? '<br />' . $this->l('Opcja dostępna od PrestaShop 1.7.3') : ''),
                        'disabled' => version_compare(_PS_VERSION_, '1.7.3', '<'),
                    ),
                    'UPDATE_REDIRECT_NOT_AVAILABLE' => array(
                        'title' => $this->l('Aktualizuj przekierowania niedostępnych produktów'),
                        'type' => 'bool',
                        'disabled' => version_compare(_PS_VERSION_, '1.7.1', '<')
                    ),
                    'hr_stock1' => array(
                        'name' => 'hr_stock1',
                        'type' => 'hr'
                    ),
                    'IMPORT_REDIRECT_NOT_AVAILABLE' => array(
                        'title' => $this->l('Przekieruj niedostępne produkty'),
                        'type' => 'select',
                        'identifier' => 'redirect_type',
                        'list' => $redirectType,
                        'disabled' => version_compare(_PS_VERSION_, '1.7.1', '<')
                    ),
                    'IMPORT_UNAVAILABLE' => array(
                        'title' => $this->l('Importuj produkty niedostępne'),
                        'type' => 'bool'
                    ),
                    'MOD_ADVANCED_STOCK_NEW' => array(
                        'title' => $this->l('Zaawansowane ustawienia dostępności podczas importu'),
                        'desc' => '<b>' . $this->l('Dotyczy nowo dodawanych produktów niedostępnych') . '</b><br />' .
                            $this->l('Poniższe opcje mogą być nadpisane przez "Ustawienina zaawansowane hurtowni"') . '<br />' .
                            $this->l('Domyślnie wyłącza wszystko'),
                        'type' => 'bool',
                        'disabled' => !(bool)XImportConfiguration::get('IMPORT_UNAVAILABLE')
                    ),
                    'MOD_ADVANCED_STOCK_NEW_ACTIVE' => array(
                        'title' => $this->l('Aktywność'),
                        'type' => 'select',
                        'identifier' => 'id_new_active',
                        'list' => array(
                            array('id_new_active' => 0, 'name' => $this->l('wyłącz')),
                            array('id_new_active' => 1, 'name'  => $this->l('włącz'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_NEW')
                    ),
                    'MOD_ADVANCED_STOCK_NEW_VIS' => array(
                        'title' => $this->l('Widoczność'),
                        'type' => 'select',
                        'identifier' => 'id_new_vis',
                        'list' => array(
                            array('id_new_vis' => 'none', 'name' => $this->l('ukryj')),
                            array('id_new_vis' => 'catalog', 'name'  => $this->l('tylko katalog')),
                            array('id_new_vis' => 'search', 'name'  => $this->l('tylko wyszukiwanie')),
                            array('id_new_vis' => 'both', 'name'  => $this->l('wszędzie'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_NEW')
                    ),
                    'MOD_ADVANCED_STOCK_NEW_OOS' => array(
                        'title' => $this->l('Preferencje dostępności'),
                        'type' => 'select',
                        'identifier' => 'id_new_oos',
                        'list' => array(
                            array('id_new_oos' => 0, 'name' => $this->l('nie pozwól zamówić')),
                            array('id_new_oos' => 1, 'name'  => $this->l('pozwól zamówić')),
                            array('id_new_oos' => 2, 'name'  => $this->l('użyj domyślnego zachowania'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_NEW')
                    ),
                    'MOD_ADVANCED_STOCK_NEW_AFO' => array(
                        'title' => $this->l('Sprzedaż'),
                        'type' => 'select',
                        'identifier' => 'id_new_afo',
                        'list' => array(
                            array('id_new_afo' => 0, 'name' => $this->l('wyłącz (tryb katalogu)')),
                            array('id_new_afo' => 1, 'name'  => $this->l('włącz'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_NEW')
                    ),
                    'hr_stock2' => array(
                        'name' => 'hr_stock2',
                        'type' => 'hr'
                    ),
                    'MOD_ADVANCED_STOCK_UPD' => array(
                        'title' => $this->l('Zaawansowane ustawienia dostępności podczas aktualizacji'),
                        'desc' => '<b>' . $this->l('Dotyczy aktualizacji produktów niedostępnych, oraz obsługi produktów usuniętych z hurtowni') . '</b><br />' .
                            '<u>' . $this->l('Dotyczy tylko głównego produktu, dla kombinacji sprawdź opcję: "Zachowaj nieistniejące atrybuty"') . '</u><br />' .
                            $this->l('Poniższe opcje mogą być nadpisane przez "Ustawienina zaawansowane hurtowni"') . '<br />' .
                            $this->l('Domyślnie wyłącza wszystko i zeruje ilości'),
                        'type' => 'bool'
                    ),
                    'MOD_ADVANCED_STOCK_UPD_ACTIVE' => array(
                        'title' => $this->l('Aktywność'),
                        'type' => 'select',
                        'identifier' => 'id_upd_active',
                        'list' => array(
                            array('id_upd_active' => 0, 'name' => $this->l('wyłącz')),
                            array('id_upd_active' => 1, 'name'  => $this->l('włącz')),
                            array('id_upd_active' => 2, 'name'  => $this->l('nic nie rób'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_UPD')
                    ),
                    'MOD_ADVANCED_STOCK_UPD_VIS' => array(
                        'title' => $this->l('Widoczność'),
                        'type' => 'select',
                        'identifier' => 'id_upd_vis',
                        'list' => array(
                            array('id_upd_vis' => 'none', 'name' => $this->l('ukryj')),
                            array('id_upd_vis' => 'catalog', 'name'  => $this->l('tylko katalog')),
                            array('id_upd_vis' => 'search', 'name'  => $this->l('tylko wyszukiwanie')),
                            array('id_upd_vis' => 'both', 'name'  => $this->l('wszędzie')),
                            array('id_upd_vis' => 'nothing', 'name'  => $this->l('nic nie rób'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_UPD')
                    ),
                    'MOD_ADVANCED_STOCK_UPD_OOS' => array(
                        'title' => $this->l('Preferencje dostępności'),
                        'type' => 'select',
                        'identifier' => 'id_upd_oos',
                        'list' => array(
                            array('id_upd_oos' => 0, 'name' => $this->l('nie pozwól zamówić')),
                            array('id_upd_oos' => 1, 'name'  => $this->l('pozwól zamówić')),
                            array('id_upd_oos' => 2, 'name'  => $this->l('użyj domyślnego zachowania')),
                            array('id_upd_oos' => 4, 'name'  => $this->l('nic nie rób'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_UPD')
                    ),
                    'MOD_ADVANCED_STOCK_UPD_AFO' => array(
                        'title' => $this->l('Sprzedaż'),
                        'type' => 'select',
                        'identifier' => 'id_upd_afo',
                        'list' => array(
                            array('id_upd_afo' => 0, 'name' => $this->l('wyłącz (tryb katalogu)')),
                            array('id_upd_afo' => 1, 'name'  => $this->l('włącz')),
                            array('id_upd_afo' => 2, 'name'  => $this->l('nic nie rób'))
                        ),
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_UPD')
                    ),
                    'MOD_ADVANCED_STOCK_UPD_QTY_ZERO' => array(
                        'title' => $this->l('Zeruj ilość'),
                        'desc' => $this->l('Dotyczy wyłącznie produktów usuniętych z hurtowni'),
                        'type' => 'bool',
                        'disabled' => !(bool)XImportConfiguration::get('MOD_ADVANCED_STOCK_UPD')
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            ),
            'price' => array(
                'title' =>	$this->l('Ustawienia cen'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	array(
                    'UPDATE_PRICE' => array(
                        'title' => $this->l('Aktualizuj ceny'),
                        'type' => 'bool'
                    ),
                    'UPDATE_UNIT_PRICE' => array(
                        'title' => $this->l('Aktualizuj ceny jednostkowe'),
                        'type' => 'bool',
                        'desc' => $this->l('np. cena za kg')
                    ),
                    'UPDATE_UNITY' => array(
                        'title' => $this->l('Aktualizuj jednostki ceny'),
                        'type' => 'bool',
                    ),
                    'UPDATE_WHOLESALE_PRICE' => array(
                        'title' => $this->l('Aktualizuj ceny hurtowe'),
                        'type' => 'bool'
                    ),
                    'UPDATE_TAX' => array(
                        'title' => $this->l('Aktualizuj podatek'),
                        'type' => 'bool'
                    ),
                    'FORCE_TAX_ZERO' => array(
                        'title' => $this->l('Wymuszaj podatek 0%'),
                        'desc' => $this->l('Ustawia zawsze regułę podatkową 0%, cena produktu zostanie przeliczona').'<br/>'.
                                  $this->l('Jeśli sprzedajesz produkty z VATem opcja powinna być wyłączona'),
                        'type' => 'bool'
                    ),
                    'UPDATE_ADDITIONAL_SHIPPING_COST' => array(
                        'title' => $this->l('Aktualizuj dodatkowe koszty wysyłki'),
                        'type' => 'bool'
                    ),
                    'UPDATE_ON_SALE' => array(
                        'title' => $this->l('Aktualizuj pole "wyprzedaż"'),
                        'type' => 'bool'
                    ),
                    'UPDATE_SHOW_PRICE' => array(
                        'title' => $this->l('Aktualizuj widoczność ceny'),
                        'type' => 'bool',
                        'desc' => $this->l('Aktualizuje informacje, czy cena produktu ma być wyświetlana. Jeśli nie, produkt będzie wyświetlany w formie katalogu i nie będzie możliwy jego zakup')
                    ),
                    'CLEAR_SPECIFIC_PRICE' => array(
                        'title' => $this->l('Czyść ceny specyficzne'),
                        'type' => 'bool',
                        'desc' => $this->l('Włączenie tej opcji usunie wszystkie ceny specyficzne (promocje) dodane do produktu')
                    ),
                    'SKIP_PRICE_ZERO' => array(
                        'title' => $this->l('Pomijaj produkty z zerową ceną'),
                        'type' => 'bool',
                        'desc' => $this->l('Porównuje cenę brutto przed narzutem')
                    ),
                    'UNACTIVE_PRICE_ZERO' => array(
                        'title' => $this->l('Wyłączaj produkty z zerową ceną'),
                        'type' => 'bool'
                    ),
                    'PRICE_ROUND' => array(
                        'title' => $this->l('Zaokrąglanie cen z narzutem'),
                        'type' => 'select',
                        'identifier' => 'id_round',
                        'list' => array(
                            array('id_round' => '0', 'name' => $this->l('nie zaokrąglaj')),
                            array('id_round' => '1', 'name'  => $this->l('zaokrąglaj w górę do 00gr')),
                            array('id_round' => '2', 'name'  => $this->l('zaokrąglaj w górę do 99gr')),
                            array('id_round' => '7', 'name'  => $this->l('zaokrąglaj w górę do 9zł i 00gr')),
                            array('id_round' => '10', 'name'  => $this->l('zaokrąglaj w górę do pełnych dziesiątek')),
                            array('id_round' => '3', 'name'  => $this->l('zaokrąglaj standardowo do 00gr')),
                            array('id_round' => '4', 'name'  => $this->l('zaokrąglaj standardowo do 99gr')),
                            array('id_round' => '8', 'name'  => $this->l('zaokrąglaj standardowo do 9zł i 00gr')),
                            array('id_round' => '11', 'name'  => $this->l('zaokrąglaj standardowo do pełnych dziesiątek')),
                            array('id_round' => '5', 'name'  => $this->l('zaokrąglaj w dół do 00gr')),
                            array('id_round' => '6', 'name'  => $this->l('zaokrąglaj w dół do 99gr')),
                            array('id_round' => '9', 'name'  => $this->l('zaokrąglaj w dół do 9zł i 00gr')),
                            array('id_round' => '12', 'name'  => $this->l('zaokrąglaj w dół do pełnych dziesiątek'))
                        ),
                        'desc' => $this->l('Przy zaokrąglaniu i równaniu do pełnych złotówek najniższa możliwa cena produktu to 1 PLN') . '<br>' .
                            $this->l('Przy zaokrąglaniu i równaniu do pełnych dziesiątek najniższa możliwa cena produktu to 10 PLN'),
                    ),
                    'PRICE_MODIFY' => array(
                        'title' => $this->l('Modyfikuj cenę po zaokrągleniu'),
                        'desc' => $this->l('Dodaje/odejmuje podaną wartość od ceny wyliczonej po jej zaokrągleniu'),
                        'type' => 'text',
                        'class' => 'fixed-width-xxl x-cast x-cast-float x-cast-unsigned',
                        'defaultValue' => '0.00'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            ),
            'images' => array(
                'title' =>	$this->l('Ustawienia zdjęć'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	array(
                    'UPDATE_IMAGES' => array(
                        'title' => $this->l('Aktualizuj zdjęcia'),
                        'type' => 'bool',
                        'desc' => $this->l('Dodaje nowe zdjęcia jeśli pojawią się w hurtorni dla już zaimportowanych produktów') . '<br />' .
                            $this->l('Nigdy nie usuwa wcześniej pobranych zdjęć, jak i nie aktualizuje zdjęc z tego samego adresu - linku')
                    ),
                    'ALLOW_NO_IMAGES' => array(
                        'title' => $this->l('Importuj produkty bez zdjęć'),
                        'type' => 'bool'
                    ),
                    'DISABLE_NO_IMAGES' => array(
                        'title' => $this->l('Wyłączaj produkty bez zdjęć'),
                        'type' => 'bool'
                    ),
                    'MAX_IMAGE_SIZE' => array(
                        'title' => $this->l('Maksymalny rozmiar zdjęcia'),
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int',
                        'size' => 10,
                        'defaultValue' => '0',
                        'suffix' => 'MB'
                    ),
                    'MAX_IMAGE_PIXEL' => array(
                        'title' => $this->l('Maksymalny dłuższy bok zdjęcia'),
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int',
                        'size' => 10,
                        'defaultValue' => '0',
                        'suffix' => 'px'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            ),
            'adv_settings' => array(
                'title' =>  $this->l('Ustawienia zaawansowane'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' => array(
                    'CLEAR_CACHE' => array(
                        'title' => $this->l('Włącz czyszczenie cache po pomyślnym imporcie'),
                        'type' => 'bool',
                        'desc' => $this->l('Opcja spróbuje wyczyścić cache wszystkich modułów przypiętych do hookUpdateProduct'),
                    ),
                    'SEARCH_INDEX' => array(
                        'title' => $this->l('Dodaj brakujące produkty do indeksu po pomyślnej aktualizacji'),
                        'type' => 'bool',
                        'desc' => $this->l('Włącz tą opcje jeśli występuje problem z widocznością produktów w wyszukiwarce, po ich ponownym aktywowaniu przez moduł hurtowni')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            )
        );

        $this->tpl_option_vars['ionCubeLicenseInfo'] = $this->displayIonLicenseInfo();
    }

    public function renderOptions()
    {
        unset($this->toolbar_btn);
        $this->display = 'options';
        $this->initToolbar();
        $helper = new HelperXImportOptions();
        $this->setHelperDisplay($helper);
        $helper->id = $this->id;
        $helper->tpl_vars = $this->tpl_option_vars;
        $options = $helper->generateOptions($this->fields_options);

        return $options;
    }

    protected function processUpdateOptions()
    {
        $this->beforeUpdateOptions();

        foreach ($this->fields_options as $category_data) {
            if (!isset($category_data['fields'])) {
                continue;
            }

            foreach ($category_data['fields'] as $key => $options) {
                if ($options['type'] != 'hr') {
                    XImportConfiguration::updateValue($key, trim(Tools::getValue($key, '')));
                }
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminXImportConfiguration') . '&conf=6');
    }
}
