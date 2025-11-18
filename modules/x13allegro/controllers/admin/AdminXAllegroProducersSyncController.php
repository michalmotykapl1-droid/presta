<?php
if (!defined('_PS_VERSION_')) { exit; }

require_once _PS_MODULE_DIR_.'x13allegro/src/Service/ProducersSyncService.php';
require_once _PS_MODULE_DIR_.'x13allegro/src/Service/AllegroApiLight.php';

/**
 * Admin controller you can drop into modules/x13allegro to mass-create Responsible Producers in Allegro
 * and write mappings into the X13 mapping table (autodetected).
 *
 * URL: index.php?controller=AdminXAllegroProducersSync&token=... (while logged in to BO)
 */
class AdminXAllegroProducersSyncController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $svc = new \PrestaShop\Modules\X13Allegro\Service\ProducersSyncService(Db::getInstance());
        $accounts = $svc->discoverAccounts();

        $this->context->smarty->assign([
            'accounts' => $accounts,
            'self_link' => $this->context->link->getAdminLink('AdminXAllegroProducersSync'),
        ]);

        if (Tools::getIsset('ajax') && Tools::getValue('action') === 'sync') {
            $this->ajaxSync($svc);
            exit;
        }

        $this->setTemplate('module:x13allegro/views/templates/admin/producers_sync.tpl');
    }

    protected function ajaxSync(\PrestaShop\Modules\X13Allegro\Service\ProducersSyncService $svc)
    {
        $accountKey = (string)Tools::getValue('account_key');
        $dryRun     = (bool)Tools::getValue('dry', false);

        try {
            $result = $svc->syncAllManufacturers($accountKey, $dryRun);
            $this->json([ 'success'=>true, 'result'=>$result ]);
        } catch (\Exception $e) {
            $this->json([ 'success'=>false, 'error'=>$e->getMessage() ]);
        }
    }

    protected function json($data){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($data); }
}
