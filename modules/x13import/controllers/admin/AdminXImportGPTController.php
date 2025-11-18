<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

use x13import\Component\ConfigurationDependencies;

class AdminXImportGPTController extends XImportController
{
    public function __construct()
    {
        $this->table = 'ximport_gpt';
        $this->identifier = 'id_ximport_gpt';
        $this->className = 'XImportGPT';
        $this->multiple_fieldsets = true;

        parent::__construct();

        $this->tpl_folder = 'x_import_gpt/';

        $this->fields_list = [
            $this->identifier => [
                'title' => $this->l('ID'),
                'class' => 'fixed-width-sm'
            ],
            'name' => [
                'title' => $this->l('Nazwa')
            ],
            'wholesalers' => [
                'title' => $this->l('Hurtownie'),
                'search' => false,
                'orderby' => false
            ],
            'product_name' => [
                'title' => $this->l('Nazwa produktu'),
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'icon' => [
                    '0' => ['class' => 'icon-remove'],
                    '1' => ['class' => 'icon-check']
                ]
            ],
            'product_description' => [
                'title' => $this->l('Opis produktu'),
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'icon' => [
                    '0' => ['class' => 'icon-remove'],
                    '1' => ['class' => 'icon-check']
                ]
            ],
            'product_description_short' => [
                'title' => $this->l('Krótki opis produktu'),
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'icon' => [
                    '0' => ['class' => 'icon-remove'],
                    '1' => ['class' => 'icon-check']
                ]
            ],
            'product_meta_title' => [
                'title' => $this->l('Meta tytuł produktu'),
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'icon' => [
                    '0' => ['class' => 'icon-remove'],
                    '1' => ['class' => 'icon-check']
                ]
            ],
            'product_meta_description' => [
                'title' => $this->l('Meta opis produktu'),
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'icon' => [
                    '0' => ['class' => 'icon-remove'],
                    '1' => ['class' => 'icon-check']
                ]
            ],
            'priority' => [
                'title' => $this->l('Priorytet'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'search' => false
            ],
            'active' => [
                'title' => $this->l('Aktywny'),
                'type' => 'bool',
                'active' => 'status',
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ]
        ];

        $descriptionShortLimit = (int)Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT');
        if ($descriptionShortLimit <= 0) {
            $descriptionShortLimit = 800;
        }

        $this->fields_options = [
            'general' => [
                'title' =>	$this->l('Ustawienia ChatGPT'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	[
                    'GPT_API_KEY' => [
                        'title' => $this->l('Klucz API'),
                        'type' => 'text'
                    ],
                    'GPT_VERSION' => [
                        'title' => $this->l('Wersja i model'),
                        'desc' => $this->l('Maksymalna ilość tokenów dla obsługiwanych modeli')
                            . '<br>- <b>gpt-4</b> do 8192 tokenów'
                            . '<br>- <b>gpt-4-32k</b> do 32768 tokenów'
                            . '<br>- <b>gpt-3.5-turbo</b> do 4096 tokenów'
                            . '<br>- <b>gpt-3.5-turbo-16k</b> do 16384 tokenów',
                        'type' => 'select',
                        'identifier' => 'id_version',
                        'list' => [
                            ['id_version' => 'gpt-4', 'name' => 'GPT-4 (gpt-4)'],
                            ['id_version' => 'gpt-4-32k', 'name' => 'GPT-4 (gpt-4-32k)'],
                            ['id_version' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 (gpt-3.5-turbo)'],
                            ['id_version' => 'gpt-3.5-turbo-16k', 'name' => 'GPT-3.5 (gpt-3.5-turbo-16k)']
                        ]
                    ],
                    'GPT_TOKENS' => [
                        'title' => $this->l('Ilość tokenów'),
                        'desc' => $this->l('Maksymalna ilość tokenów do wygenerowania, współdzielona między zapytaniem a wygenerowanym tekstem.')
                            . '<br>' . $this->l('Dokładny limit różni się w zależności od modelu.')
                            . '<br>' . $this->l('Jeden token to około 4 znaki dla standardowego tekstu w języku angielskim.'),
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int'
                    ],
                    'GPT_TEMPERATURE' => [
                        'title' => $this->l('Temperatura'),
                        'desc' => $this->l('Kontroluje losowość. Temperatura bliższa zeru powoduje że, model staje się deterministyczny i powtarzalny.'),
                        'type' => 'range',
                        'range' => [
                            'min' => 0,
                            'max' => 2,
                            'step' => 0.01
                        ],
                        'cast' => [
                            'type' => 'float',
                            'precision' => 2
                        ]
                    ],
                    'GPT_PRESENCE_PENALTY' => [
                        'title' => $this->l('Kara obecności'),
                        'desc' => $this->l('Jak bardzo "karać" kolejne tokeny na podstawie tego, czy pojawiły się do tej pory w generowanym tekście.')
                            . '<br>' . $this->l('Zwiększa prawdopodobieństwo wygenerowania nowych wątków.'),
                        'type' => 'range',
                        'range' => [
                            'min' => 0,
                            'max' => 2,
                            'step' => 0.01
                        ],
                        'cast' => [
                            'type' => 'float',
                            'precision' => 2
                        ]
                    ],
                    'GPT_FREQUENCY_PENALTY' => [
                        'title' => $this->l('Kara częstotliwości'),
                        'desc' => $this->l('Jak bardzo "karać" kolejne tokeny na podstawie ich częstotliwości występowania w generowanym tekście.')
                            . '<br>' . $this->l('Zmniejsza prawdopodobieństwo powtórzenia tej samej linii dosłownie.'),
                        'type' => 'range',
                        'range' => [
                            'min' => 0,
                            'max' => 2,
                            'step' => 0.01
                        ],
                        'cast' => [
                            'type' => 'float',
                            'precision' => 2
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Zapisz')
                ]
            ],
            'product_fields_length' => [
                'title' =>	$this->l('Limity znaków'),
                'description' => $this->l('Możesz zdefiniować limity znaków dla tekstów generowanych przez ChatGPT. Nie powinny one przekraczać maksymalnych limitów podanych poniżej, w przeciwnym wypadku zostaną skrócone automatycznie.'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	[
                    'GPT_PRODUCT_NAME_LENGTH' => [
                        'title' => $this->l('Nazwa produktu'),
                        'desc' => $this->l('Maksymalna ilość znaków') . ': 128',
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int'
                    ],
                    'GPT_PRODUCT_DESCRIPTION_LENGTH' => [
                        'title' => $this->l('Opis produktu'),
                        'desc' => $this->l('Maksymalna ilość znaków') . ': 21844',
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int'
                    ],
                    'GPT_PRODUCT_DESCRIPTION_SHORT_LENGTH' => [
                        'title' => $this->l('Krótki opis produktu'),
                        'desc' => $this->l('Maksymalna ilość znaków') . ': ' . $descriptionShortLimit,
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int'
                    ],
                    'GPT_PRODUCT_META_TITLE_LENGTH' => [
                        'title' => $this->l('Meta tytuł produktu'),
                        'desc' => $this->l('Zalecana maksymalna ilość znaków') . ': 70'
                            . '<br>' . $this->l('Maksymalna ilość znaków') . ': 128',
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int'
                    ],
                    'GPT_PRODUCT_META_DESCRIPTION_LENGTH' => [
                        'title' => $this->l('Meta opis produktu'),
                        'desc' => $this->l('Zalecana maksymalna ilość znaków') . ': 160'
                            . '<br>' . $this->l('Maksymalna ilość znaków') . ': 255',
                        'type' => 'text',
                        'class' => 'fixed-width-sm x-cast x-cast-int'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Zapisz')
                ]
            ]
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryUI('slider');
        $this->addJS($this->module->getPathUri() . 'views/js/x13importGPT.js');
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_gpt_prompt'] = array(
                'href' => $this->context->link->getAdminLink('AdminXImportGPT') . '&add' . $this->table,
                'desc' => $this->l('Nowe zapytanie GPT'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->_select .= 'GROUP_CONCAT(b.`wholesaler_code`) as wholesalers';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'ximport_gpt_wholesaler` b
            ON (b.`id_ximport_gpt` = a.`id_ximport_gpt`)';
        $this->_group .= 'GROUP BY a.`id_ximport_gpt`';

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->fields_form[]['form'] = [
            'legend' => [
                'title' => $this->l('Ustawienia zapytania ChatGPT')
            ],
            'input' => [
                [
                    'label' => $this->l('Nazwa pomocnicza'),
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true
                ],
                [
                    'label' => $this->l('Hurtownie'),
                    'desc' => $this->l('Wybór konkretnej hurtowni ma zawsze pierwszeństwo priorytetu.'),
                    'name' => 'wholesalers',
                    'required' => true,
                    'type' => 'checkbox',
                    'values' => [
                        'query' => array_merge(
                            [['code' => 'ALL_WHOLESALERS', 'name' => $this->l('-- Wszystkie hurtownie --')]],
                            XImportWholesalers::getWholesalers()
                        ),
                        'id' => 'code',
                        'name' => 'name'
                    ]
                ],
                [
                    'label' => $this->l('Priorytet'),
                    'desc' => $this->l('Wyższy priorytet ma pierwszeństwo nad innymi zapytaniami z tym samym polem do wygenerowania.'),
                    'name' => 'priority',
                    'type' => 'range',
                    'range' => [
                        'min' => 1,
                        'max' => 10,
                        'step' => 1
                    ],
                    'cast' => [
                        'type' => 'int'
                    ]
                ],
                [
                    'label' => $this->l('Aktywny'),
                    'name' => 'active',
                    'type' => 'switch',
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Zapisz')
            ]
        ];

        $this->fields_form[]['form'] = [
            'legend' => [
                'title' => $this->l('Nazwa produktu')
            ],
            'input' => [
                [
                    'label' => $this->l('Generuj nazwę produktu'),
                    'name' => 'product_name',
                    'type' => 'switch',
                    'values' => [
                        ['id' => 'product_name_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'product_name_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ]
                ],
                [
                    'label' => $this->l('Treść zapytania'),
                    'desc' => $this->getPromptVariables(),
                    'name' => 'product_name_prompt',
                    'type' => 'textarea',
                    'rows' => 5,
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_name' => 1]
                    )
                ],
                [
                    'label' => $this->l('Maksymalna ilość słów'),
                    'desc' => $this->l('Puste pole (luz zero) nie ogranicza ilości słów.')
                        . '<br>' . $this->l('Uwzględnia maksymalną ilość znaków') . ': ' . XImportConfiguration::get('GPT_PRODUCT_NAME_LENGTH'),
                    'name' => 'product_name_words_limit',
                    'type' => 'text',
                    'class' => 'fixed-width-sm x-cast x-cast-int',
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_name' => 1]
                    )
                ]
            ],
            'submit' => [
                'title' => $this->l('Zapisz')
            ]
        ];

        $this->fields_form[]['form'] = [
            'legend' => [
                'title' => $this->l('Opis produktu')
            ],
            'input' => [
                [
                    'label' => $this->l('Generuj opis produktu'),
                    'name' => 'product_description',
                    'type' => 'switch',
                    'values' => [
                        ['id' => 'product_description_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'product_description_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ]
                ],
                [
                    'label' => $this->l('Treść zapytania'),
                    'desc' => $this->getPromptVariables(),
                    'name' => 'product_description_prompt',
                    'type' => 'textarea',
                    'rows' => 5,
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_description' => 1]
                    )
                ],
                [
                    'label' => $this->l('Maksymalna ilość słów'),
                    'desc' => $this->l('Puste pole (luz zero) nie ogranicza ilości słów.')
                        . '<br>' . $this->l('Uwzględnia maksymalną ilość znaków') . ': ' . XImportConfiguration::get('GPT_PRODUCT_DESCRIPTION_LENGTH'),
                    'name' => 'product_description_words_limit',
                    'type' => 'text',
                    'class' => 'fixed-width-sm x-cast x-cast-int',
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_description' => 1]
                    )
                ]
            ],
            'submit' => [
                'title' => $this->l('Zapisz')
            ]
        ];

        $this->fields_form[]['form'] = [
            'legend' => [
                'title' => $this->l('Krótki opis produktu')
            ],
            'input' => [
                [
                    'label' => $this->l('Generuj krótki opis produktu'),
                    'name' => 'product_description_short',
                    'type' => 'switch',
                    'values' => [
                        ['id' => 'product_description_short_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'product_description_short_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ]
                ],
                [
                    'label' => $this->l('Treść zapytania'),
                    'desc' => $this->getPromptVariables(),
                    'name' => 'product_description_short_prompt',
                    'type' => 'textarea',
                    'rows' => 5,
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_description_short' => 1]
                    )
                ],
                [
                    'label' => $this->l('Maksymalna ilość słów'),
                    'desc' => $this->l('Puste pole (luz zero) nie ogranicza ilości słów.')
                        . '<br>' . $this->l('Uwzględnia maksymalną ilość znaków') . ': ' . XImportConfiguration::get('GPT_PRODUCT_DESCRIPTION_SHORT_LENGTH'),
                    'name' => 'product_description_short_words_limit',
                    'type' => 'text',
                    'class' => 'fixed-width-sm x-cast x-cast-int',
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_description_short' => 1]
                    )
                ]
            ],
            'submit' => [
                'title' => $this->l('Zapisz')
            ]
        ];

        $this->fields_form[]['form'] = [
            'legend' => [
                'title' => $this->l('Meta tytuł produktu')
            ],
            'input' => [
                [
                    'label' => $this->l('Generuj meta tytuł produktu'),
                    'name' => 'product_meta_title',
                    'type' => 'switch',
                    'values' => [
                        ['id' => 'product_meta_title_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'product_meta_title_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ]
                ],
                [
                    'label' => $this->l('Treść zapytania'),
                    'desc' => $this->getPromptVariables(),
                    'name' => 'product_meta_title_prompt',
                    'type' => 'textarea',
                    'rows' => 5,
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_meta_title' => 1]
                    )
                ],
                [
                    'label' => $this->l('Maksymalna ilość słów'),
                    'desc' => $this->l('Puste pole (luz zero) nie ogranicza ilości słów.')
                        . '<br>' . $this->l('Uwzględnia maksymalną ilość znaków') . ': ' . XImportConfiguration::get('GPT_PRODUCT_META_TITLE_LENGTH'),
                    'name' => 'product_meta_title_words_limit',
                    'type' => 'text',
                    'class' => 'fixed-width-sm x-cast x-cast-int',
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_meta_title' => 1]
                    )
                ]
            ],
            'submit' => [
                'title' => $this->l('Zapisz')
            ]
        ];

        $this->fields_form[]['form'] = [
            'legend' => [
                'title' => $this->l('Meta opis produktu')
            ],
            'input' => [
                [
                    'label' => $this->l('Generuj meta opis produktu'),
                    'name' => 'product_meta_description',
                    'type' => 'switch',
                    'values' => [
                        ['id' => 'product_meta_description_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'product_meta_description_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ]
                ],
                [
                    'label' => $this->l('Treść zapytania'),
                    'desc' => $this->getPromptVariables(),
                    'name' => 'product_meta_description_prompt',
                    'type' => 'textarea',
                    'rows' => 5,
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_meta_description' => 1]
                    )
                ],
                [
                    'label' => $this->l('Maksymalna ilość słów'),
                    'desc' => $this->l('Puste pole (luz zero) nie ogranicza ilości słów.')
                        . '<br>' . $this->l('Uwzględnia maksymalną ilość znaków') . ': ' . XImportConfiguration::get('GPT_PRODUCT_META_DESCRIPTION_LENGTH'),
                    'name' => 'product_meta_description_words_limit',
                    'type' => 'text',
                    'class' => 'fixed-width-sm x-cast x-cast-int',
                    'form_group_class' => ConfigurationDependencies::fieldDependsOn(
                        ConfigurationDependencies::fieldMatch(),
                        ['product_meta_description' => 1]
                    )
                ]
            ],
            'submit' => [
                'title' => $this->l('Zapisz')
            ]
        ];

        return parent::renderForm();
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

        return $helper->generateOptions($this->fields_options);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $name = trim(Tools::getValue('name'));
            $wholesalers = Tools::getValue('wholesalers', []);

            if (empty($name)) {
                $this->errors[] = $this->l('Uzupełnij pole Nazwa pomocnicza');
            }
            if (empty($wholesalers)) {
                $this->errors[] = $this->l('Zaznacz przynajmniej jedną hurtwonie, lub wybierz opcje "Wszystkie hurtownie"');
            }

            if (!empty($this->errors)) {
                $this->display = (Validate::isLoadedObject($this->object) ? 'edit' : 'add');
                return false;
            }

            if (in_array('ALL_WHOLESALERS', $wholesalers)) {
                $_POST['wholesalers'] = [];
            } else {
                $_POST['wholesalers'] = $wholesalers;
            }
        }

        return parent::postProcess();
    }

    public function processSave()
    {
        /** @var XImportGPT $result */
        $result = parent::processSave();

        if (Validate::isLoadedObject($result)) {
            $result->saveWholesalers(Tools::getValue('wholesalers', []));
        }

        return $result;
    }

    /**
     * @param XImportGPT $obj
     * @return array
     */
    public function getFieldsValue($obj)
    {
        $this->fields_value = parent::getFieldsValue($obj);

        if (Validate::isLoadedObject($obj)) {
            $wholesalers = $obj->getWholesalers();

            if (is_array($wholesalers) && !empty($wholesalers)) {
                foreach ($wholesalers as $wholesaler) {
                    $this->fields_value['wholesalers_' . $wholesaler] = true;
                }
            } else {
                $this->fields_value['wholesalers_ALL_WHOLESALERS'] = true;
            }
        }

        return $this->fields_value;
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

        foreach ($this->_list as &$row) {
            if (!$row['wholesalers']) {
                $row['wholesalers'] = 'Wszystkie hurtownie';
            }
        }
    }

    protected function processUpdateOptions()
    {
        $this->beforeUpdateOptions();

        foreach ($this->fields_options as $group) {
            foreach ($group['fields'] as $key => $options) {
                XImportConfiguration::updateValue($key, trim(Tools::getValue($key, '')));
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminXImportGPT') . '&conf=6');
    }

    /**
     * @return string
     */
    private function getPromptVariables()
    {
        return '<b>' . $this->l('Dostępne znaczniki') . ':</b>'
            . '<br>{product_name}' . ' - ' . $this->l('nazwa produktu')
            . '<br>{product_reference}' . ' - ' . $this->l('kod referencyjny (indeks)')
            . '<br>{product_ean13}' . ' - ' . $this->l('kod EAN13')
            . '<br>{product_manufacturer}' . ' - ' . $this->l('producent')
            . '<br>{product_supplier}' . ' - ' . $this->l('dostawca');
    }
}
