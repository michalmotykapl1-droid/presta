<?php

use GeminiContent\Repository\GeminiProductRepository;

class AdminGeminiContentLogController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->toolbar_title = $this->l('Zmienione produkty');
    }

    public function initContent()
    {
        parent::initContent();

        $productRepo = new GeminiProductRepository(Db::getInstance());
        $loggedProducts = $productRepo->fetchLoggedProducts();

        $this->context->smarty->assign([
            'logs' => $loggedProducts,
            'link' => $this->context->link, // Dodajemy link do szablonu
        ]);

        $this->setTemplate('log_view.tpl');
    }
    
    /**
     * Rejestracja skryptów JS dla tej strony.
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->context->controller->addJs($this->module->getPathUri() . 'views/js/admin_manager.js');
        
        Media::addJsDef([
            // Wszystkie zapytania AJAX kierujemy do kontrolera, który ma logikę (Manager)
            'gemini_ajax_url' => $this->context->link->getAdminLink('AdminGeminiContentManager'),
        ]);
    }
}
