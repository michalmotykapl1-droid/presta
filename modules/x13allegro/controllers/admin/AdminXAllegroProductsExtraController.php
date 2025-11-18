<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Repository\ProductCustomRepository;

final class AdminXAllegroProductsExtraController extends XAllegroController
{
    /** @var XAllegroHelperProductExtra */
    private $xHelperProduct;

    public function init()
    {
        parent::init();

        $product = new Product((int)Tools::getValue('productId'));

        if (!Validate::isLoadedObject($product)) {
            die(json_encode([
                'result' => false,
                'message' => $this->l('Musisz zapisać produkt przed zmianą ustawień Allegro')
            ]));
        }

        $this->xHelperProduct = new XAllegroHelperProductExtra(
            new XAllegroProduct(null, $product->id)
        );
    }

    public function ajaxProcessSaveProduct()
    {
        $this->xHelperProduct->setAccountId((int)Tools::getValue('xallegro_product_custom_account'));
        $this->xHelperProduct->processProductExtra($this->errors);

        Hook::exec('actionX13AllegroAdminProductsExtraSave', [
            'id_product' => (int)Tools::getValue('productId')
        ]);

        if (empty($this->errors)) {
            die(json_encode([
                'result' => true,
                'message' => $this->l('Ustawienia Allegro zostały zapisane'),
                'html' => $this->xHelperProduct->generateImagesAdditionalForm()
            ]));
        }
        else {
            die(json_encode([
                'result' => false,
                'message' => $this->errors
            ]));
        }
    }

    public function ajaxProcessChangeAccount()
    {
        $this->xHelperProduct->setAccountId((int)Tools::getValue('accountId'));

        die(json_encode([
            'html' => $this->xHelperProduct->generateProductCustomForm(),
        ]));
    }

    public function ajaxProcessDeleteCustomPrices()
    {
        ProductCustomRepository::deletePrices((int)Tools::getValue('productId'));

        die(json_encode([
            'result' => true,
            'message' => $this->l('Poprawnie skasowano ceny dedykowane')
        ]));
    }

    public function ajaxProcessUploadAdditionalImage()
    {
        $this->xHelperProduct->processImagesAdditional($this->errors, Tools::getValue('imageAdditionalUpdate'))
            ->saveProduct();

        if (empty($this->errors)) {
            die(json_encode([
                'result' => true,
                'message' => $this->l('Dodano nowe zdjęcie'),
                'html' => $this->xHelperProduct->generateImagesAdditionalForm()
            ]));
        }
        else {
            die(json_encode([
                'result' => false,
                'message' => $this->errors
            ]));
        }
    }

    public function ajaxProcessDeleteAdditionalImage()
    {
        $this->xHelperProduct->deleteImageAdditional($this->errors);

        if (empty($this->errors)) {
            die(json_encode([
                'result' => true,
                'message' => $this->l('Zdjęcie zostało usunięte'),
                'html' => $this->xHelperProduct->generateImagesAdditionalForm()
            ]));
        }
        else {
            die(json_encode([
                'result' => false,
                'message' => $this->errors
            ]));
        }
    }
}
