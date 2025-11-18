<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\XAllegroApi;

final class AdminXAllegroTemplatesController extends XAllegroController
{
    /** @var XAllegroTemplate */
    public $object;

    public function __construct()
    {
        $this->table = 'xallegro_template';
        $this->identifier = 'id_xallegro_template';
        $this->className = 'XAllegroTemplate';

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroTemplates'));

        $this->fields_list = array(
            'id_xallegro_template' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 20,
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Nazwa szablonu'),
                'width' => 'auto'
            ),
            'default' => array(
                'title' => $this->l('Domyślny'),
                'width' => 70,
                'class' => 'fixed-width-sm',
                'align' => 'center',
                'active' => 'default',
                'type' => 'bool'
            ),
            'active' => array(
                'title' => $this->l('Aktywny'),
                'width' => 70,
                'align' => 'center',
                'active' => 'active',
                'type' => 'bool',
                'class' => 'fixed-width-sm'
            )
        );

        $this->addRowAction('edit');
        $this->addRowAction('xduplicate');
        $this->addRowAction('delete');

        $this->tpl_folder = 'x_allegro_templates/';
    }


    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
        {
            $this->page_header_toolbar_btn['allegro_current'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroTemplates') . '&addxallegro_template',
                'desc' => $this->l('Dodaj nowy szablon'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initToolbar()
    {
        if ($this->display == 'add' || $this->display == 'edit') {
            $this->toolbar_btn['save_and_stay'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Zapisz i zostań'),
                'class' => 'process-icon-save-and-stay '
            );
        }

        parent::initToolbar();
    }

    public function renderForm()
    {
        $this->loadObject(true);
        $additional_images = array();

        for ($i = 0; $i < X13_ALLEGRO_TEMPLATE_IMAGES_NB; $i++)
        {
            $image = null;

            if (Validate::isLoadedObject($this->object) && isset($this->object->additional_images[$i]) && $this->object->additional_images[$i])
            {
                $ids = explode('_', $this->object->additional_images[$i]);
                $link = $this->context->link->getAdminLink('AdminXAllegroTemplates') .
                    '&deleteAdditionalImage=' . $ids[1] . '&update' . $this->table . '&' . $this->identifier . '=' . $this->object->id;
                list($width, $height) = getimagesize(X13_ALLEGRO_IMG_TEMPLATE . $this->object->additional_images[$i]);
                $size = filesize(X13_ALLEGRO_IMG_TEMPLATE . $this->object->additional_images[$i]);

                $image = '<img src="' . $this->context->shop->getBaseURL(Configuration::get('PS_SSL_ENABLED')) .
                    X13_ALLEGRO_IMG_TEMPLATE_URL . $this->object->additional_images[$i] . '" class="imgm img-thumbnail" style="max-width: 150px; float: left;">' .
                    '<span style="display: inline-block; margin-left: 6px;">' . $this->l('wymiary') . ': ' . $width . 'x' . $height . '<br>' .
                    $this->l('rozmiar') . ': ' . round($size/1024/1024, 2) . ' MB<br>' .
                    '<a href="' . $link . '" class="btn btn-default button" style="margin-top: 6px;">' . $this->l('Usuń zdjęcie') . '</a></span>';
            }

            $additional_images[] = array(
                'type' => 'file',
                'label' => $this->l('Dodatkowe zdjęcie') . ' ' . ($i+1),
                'name' => 'additional_image_' . ($i+1),
                'display_image' => true,
                'image' => $image
            );
        }

        $this->fields_form = array(
            'tinymce' => false,
            'legend' => array(
                'title' => $this->l('Szablon'),
            ),
            'input' =>
                array_merge(
                    array(
                        array(
                            'type' => 'hidden',
                            'name' => $this->identifier
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Nazwa'),
                            'name' => 'name',
                            'size' => 30,
                            'required' => true
                        ),
                        array(
                            'type' => 'new_content',
                            'label' => $this->l('Struktura szablonu'),
                            'name' => 'new_content',
                            'id' => 'new_content',
                            'rows' => 60,
                            'cols' => 100
                        )
                    ),
                    $additional_images,
                    array (
                        array(
                            'type' => $this->bootstrap ? 'switch' : 'radio',
                            'label' => $this->l('Domyślny'),
                            'name' => 'default',
                            'required' => false,
                            'class' => 't',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'value' => 1,
                                    'label' => $this->l('Tak')
                                ),
                                array(
                                    'value' => 0,
                                    'label' => $this->l('Nie')
                                )
                            )
                        ),
                        array(
                            'type' => $this->bootstrap ? 'switch' : 'radio',
                            'label' => $this->l('Aktywny'),
                            'name' => 'active',
                            'required' => false,
                            'class' => 't',
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'value' => 1,
                                    'label' => $this->l('Tak')
                                ),
                                array(
                                    'value' => 0,
                                    'label' => $this->l('Nie')
                                )
                            ),
                            'default_value' => 1
                        )
                    )
                ),
            'submit' => array(
                'title' => $this->l('Zapisz')
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        $variables = array(
            'auction_title' => $this->l('Tytuł oferty'),
            'auction_description' => $this->l('Opis oferty (z ewentualnymi zmianami podczas wystawiania)'),
            'auction_price' => $this->l('Cena "Kup Teraz"'),

            'product_name' => $this->l('Nazwa produktu'),
            'product_reference' => $this->l('Kod referencyjny (indeks)'),
            'product_price' => $this->l('Cena produktu w sklepie'),
            'product_price_base' => $this->l('Podstawowa cena produktu w sklepie (bez promocji i rabatów grupowych)'),
            'product_ean13' => $this->l('Kod EAN13'),
            'product_isbn' => $this->l('Kod ISBN (dostępny od PrestaShop 1.7)'),
            'product_weight' => $this->l('Waga produktu'),
            'product_attribute_name' => $this->l('Nazwa atrybutu (jeśli posiada)'),
            'product_attribute_reference' => $this->l('Kod referencyjny (indeks) atrybutu (jeśli posiada)'),
            'product_description' => $this->l('Opis produktu w sklepie'),
            'product_description_short' => $this->l('Krótki opis produktu w sklepie'),
            'product_description_additional_X' => $this->l('Dodatkowy opis produktu'),
            '<span style="color: darkgrey; text-decoration: line-through;">product_description_custom</span>' => 'Używaj {product_description_additional_1}',
            'product_features' => $this->l('Lista cech produktu. Aby wykluczyć wyświetlanie wybranych cech, dodaj ich identyfikatory według wzoru {product_features!X/Y/Z} (gdzie X,Y,Z to identyfikatory grup cech)'),
            'product_attributes' => $this->l('Lista atrybutów wystawianego produktu'),
            'product_attributes_all' => $this->l('Lista wszystkich dostępnych kombinacji produktu'),
            'product_customization_fields' => $this->l('Lista pól dostosowywania produktu (wymagane pola zostaną oznaczone gwiazdką "*")'),
            'product.POLE' => $this->l('Wartośc pola z tabeli product'),
            'product_lang.POLE' => $this->l('Wartość pola z tabeli product_lang'),

            'feature_name_X' => $this->l('Nazwa cechy o identyfikatorze X dla produktu'),
            'feature_value_X' => $this->l('Wartość cechy o identyfikatorze X dla produktu'),

            'attribute_name_X' => $this->l('Nazwa atrybutu o identyfikatorze X dla produktu'),
            'attribute_value_X' => $this->l('Wartość  atrybutu o identyfikatorze X dla produktu'),

            'manufacturer_name' => $this->l('Nazwa producenta'),
            'manufacturer_description' => $this->l('Opis producenta'),
            'manufacturer_description_short' => $this->l('Krótki opis producenta')
        );

        $this->loadObject(true);

        $this->context->smarty->assign(array(
            'template_variables' => $variables,
            'template_additional_images' => json_encode($this->object->additional_images ? $this->object->additional_images : array()),
            'folder_admin' => basename(_PS_ADMIN_DIR_),
            'new_content' => $this->object->content
        ));

        return parent::renderForm();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/css/grideditor.css');

        if (method_exists('Media', 'addJsDef')) {
            Media::addJsDef(
                [
                    'x13allegro_template_images_nb' => X13_ALLEGRO_TEMPLATE_IMAGES_NB
                ]
            );
        }

        $this->addJqueryUI('ui.sortable');
        $this->addJS($this->module->getPathUri() . 'views/js/tinymce/tinymce.min.js');
        $this->addJS($this->module->getPathUri() . 'views/js/tinymce/jquery.tinymce.min.js');
        $this->addJS($this->module->getPathUri() . 'views/js/jquery.grideditor.js');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('deleteAdditionalImage')) {
            $this->object = new XAllegroTemplate(Tools::getValue($this->identifier));

            foreach (glob(X13_ALLEGRO_IMG_TEMPLATE . $this->object->id . '_' . (int)Tools::getValue('deleteAdditionalImage') . '_*') as $image) {
                @unlink($image);
            }

            unset($this->object->additional_images[(int)Tools::getValue('deleteAdditionalImage')]);
            $this->object->save();
        }

        if (Tools::isSubmit('submitAdd' . $this->table)
            || Tools::isSubmit('submitAdd' . $this->table . 'AndStay')
        ) {
            $_POST['content'] = preg_replace("/<select[^>]*>(.*?)<\/select>/i", '', Tools::getValue('new_content'));
        }
        else if (Tools::isSubmit('duplicate' . $this->table))
        {
            $template = new XAllegroTemplate(Tools::getValue($this->identifier));

            if (!Validate::isLoadedObject($template)) {
                $this->errors[] = $this->l('Niepoprawny objekt');
            }
            else {
                $newTemplate = clone $template;
                $newTemplate->id = null;
                $newTemplate->name .= ' - Kopia';
                $newTemplate->default = false;

                if ($newTemplate->add()) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroTemplates') . '&conf=19');
                }
                else {
                    $this->errors[] = $this->l('Wystąpił błąd podczas kopiowania szablonu.');
                }

            }
        }

        parent::postProcess();

        if (!empty($_FILES) && Validate::isLoadedObject($this->object)) {
            $this->object = new XAllegroTemplate(Tools::getValue($this->identifier));
            $additional_images = array();
            $new_images = false;

            for ($i = 0; $i < X13_ALLEGRO_TEMPLATE_IMAGES_NB; $i++)
            {
                $index = 'additional_image_' . ($i+1);
                $old_image = (isset($this->object->additional_images[$i]) ? $this->object->additional_images[$i] : null);

                if (isset($_FILES[$index]) && $_FILES[$index]['size'] > 0)
                {
                    $errors = array();

                    if (false !== ($validate = ImageManager::validateUpload($_FILES[$index]))) {
                        $errors[] = $validate;
                    }

                    if ($validate === false)
                    {
                        list($width, $height) = getimagesize($_FILES[$index]['tmp_name']);
                        $extension = strtolower(pathinfo($_FILES[$index]['name'], PATHINFO_EXTENSION));

                        if ($width < XAllegroApi::PHOTO_MIN_LENGTH && $height < XAllegroApi::PHOTO_MIN_LENGTH) {
                            $errors[] = sprintf($this->l('Dodatkowe zdjęcie %d musi posiadać dłuższy bok min. %d pikseli.'), ($i+1), XAllegroApi::PHOTO_MIN_LENGTH);
                        }
                        if ($width > XAllegroApi::PHOTO_MAX_LENGTH || $height > XAllegroApi::PHOTO_MAX_LENGTH) {
                            $errors[] = sprintf($this->l('Dodatkowe zdjęcie %d nie może być większe niż %d x %d pikseli.'), ($i+1), XAllegroApi::PHOTO_MAX_LENGTH, XAllegroApi::PHOTO_MAX_LENGTH);
                        }
                        if (!in_array($extension, array('jpg', 'png'))) {
                            $errors[] = sprintf($this->l('Dodatkowe zdjęcie %d nieprawidłowe rozszerzenie, dostępne rozszerzenia to: %s'), ($i+1), ' .jpg, .png');
                        }
                    }

                    if (empty($errors))
                    {
                        $name = $this->object->id . '_' . $i . '_' . (int)microtime(true) . '.' . $extension;

                        if ($old_image && file_exists(X13_ALLEGRO_IMG_TEMPLATE . $old_image)) {
                            unlink(X13_ALLEGRO_IMG_TEMPLATE . $old_image);
                        }

                        if (!is_dir(X13_ALLEGRO_IMG_TEMPLATE)) {
                            mkdir(X13_ALLEGRO_IMG_TEMPLATE, 0775, true);
                        }

                        $new_images = true;
                        $additional_images[$i] = $name;
                        @move_uploaded_file($_FILES[$index]['tmp_name'], X13_ALLEGRO_IMG_TEMPLATE . $name);

                        $this->module->sessionMessages->confirmations($this->l('Dodatkowe zdjęcie') . ' ' . ($i+1) . ': ' . $this->l('zapisano!'));
                    }
                    else {
                        $this->module->sessionMessages->errors(array_merge($this->errors, $errors));
                        $additional_images[$i] = $old_image;
                    }
                }
                else {
                    $additional_images[$i] = $old_image;
                }
            }

            $this->object->additional_images = $additional_images;
            $this->object->save();

            if (!empty($errors)) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroTemplates') . '&update' . $this->table . '&' . $this->identifier . '=' . $this->object->id);
            }
            else if ($new_images) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroTemplates') . '&conf=4&update' . $this->table . '&' . $this->identifier . '=' . $this->object->id);
            }
        }
    }

    public function displayXDuplicateLink($token = null, $id, $name = null)
    {
        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_xduplicate.tpl');
        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminXAllegroTemplates') . '&duplicate' . $this->table . '&' . $this->identifier . '=' . $id,
            'action' => $this->l('Skopiuj'),
            'id' => $id
        ));

        return $tpl->fetch();
    }
}
