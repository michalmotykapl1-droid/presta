<?php
if (!defined('_PS_VERSION_')) { exit; }

require_once _PS_MODULE_DIR_.'x13allegro/x13allegro.php';
require_once _PS_MODULE_DIR_.'x13allegro/classes/php81/Service/ResponsibleProducerResolver.php';

class AdminXAllegroGpsrAutoController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = false;
        parent::__construct();
    }

    public function initContent()
    {
        // tylko AJAX
        if (!Tools::getIsset('ajax')) { die('Forbidden'); }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $action = Tools::getValue('action');
            switch ($action) {
                case 'manufacturerName':
                    $this->ajaxManufacturerName();
                    break;
                case 'resolveProducerId':
                    $this->ajaxResolveProducerId();
                    break;
                default:
                    die(json_encode(['success'=>false, 'error'=>'Unknown action']));
            }
        } catch (Throwable $e) {
            http_response_code(500);
            die(json_encode(['success'=>false, 'error'=>$e->getMessage()]));
        }
    }

    /** Zwróć nazwę producenta z PS dla podanego id_product (lub null) */
    protected function ajaxManufacturerName(): void
    {
        $idProduct = (int)Tools::getValue('id_product');
        if (!$idProduct) { die(json_encode(['success'=>true,'name'=>null])); }

        $row = Db::getInstance()->getRow('
            SELECT m.name AS manufacturer_name
            FROM '._DB_PREFIX_.'product p
            LEFT JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer = p.id_manufacturer)
            WHERE p.id_product='.(int)$idProduct.' LIMIT 1
        ');
        $name = $row && !empty($row['manufacturer_name']) ? (string)$row['manufacturer_name'] : null;
        die(json_encode(['success'=>true, 'name'=>$name]));
    }

    /** Zwróć UUID Responsible Producer z Allegro dla podanej nazwy (dopasowanie po nazwie) */
    protected function ajaxResolveProducerId(): void
    {
        $name = (string)Tools::getValue('name');
        if ($name === '') { die(json_encode(['success'=>true,'id'=>null])); }

        /** @var x13allegro $mod */
        $mod = Module::getInstanceByName('x13allegro');
        // wyciągamy klienta i token z tego samego źródła co ekrany kategorii
        $allegroApi = property_exists($mod, 'allegroApi') ? $mod->allegroApi : null;
        if (!$allegroApi && method_exists($mod,'getApi')) { $allegroApi = $mod->getApi(); }
        if (!$allegroApi || !method_exists($allegroApi,'getAccessToken')) {
            die(json_encode(['success'=>false,'error'=>'No Allegro API client']));
        }

        $resolver = new \x13allegro\Service\ResponsibleProducerResolver(
            $allegroApi,                           // ten sam klient co kategorie
            $allegroApi->getAccessToken(),        // token
            (bool)$allegroApi->isSandbox()
        );
        $id = $resolver->resolveIdByName($name);
        die(json_encode(['success'=>true, 'id'=>$id]));
    }
}
