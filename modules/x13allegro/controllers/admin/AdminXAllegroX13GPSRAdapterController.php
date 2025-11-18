<?php

require_once (__DIR__ . '/../../x13allegro.php');

use x13allegro\Adapter\Module\x13gpsrAdapter;
use x13allegro\Api\Exception\ApiException;

final class AdminXAllegroX13GPSRAdapterController extends XAllegroController
{
    public function ajaxProcessRefreshFormModifier()
    {
        $formType = Tools::getValue('formType');
        $formObjectId = (int)Tools::getValue('formObjectId');

        $formModifier = (new x13gpsrAdapter())
            ->refreshGPSRCache($formType)
            ->getFormModifier($formType, $formObjectId);

        die(json_encode([
            'success' => true,
            'formModifier' => $formModifier
        ]));
    }

    public function ajaxProcessCreateGPRS()
    {
        $formType = Tools::getValue('formType');
        $formObjectId = (int)Tools::getValue('formObjectId');
        $allegroAccountId = (int)Tools::getValue('allegroAccountId');

        try {
            $x13gpsrAdapter = new x13gpsrAdapter();
            $x13gpsr = $x13gpsrAdapter->getInstance();

            if (!$x13gpsr) {
                throw new RuntimeException('X13GPSR module is not available');
            }

            switch ($formType) {
                case x13gpsrAdapter::RESPONSIBLE_PRODUCER:
                    $x13gpsrAdapter->assignResponsibleManufacturer($formObjectId, [
                        $allegroAccountId => $x13gpsrAdapter->createAllegroResponsibleManufacturer($allegroAccountId, $formObjectId)
                    ]);

                    $message = $this->l('Utworzono nowego producenta w Allegro');
                    break;

                case x13gpsrAdapter::RESPONSIBLE_PERSON:
                    $x13gpsrAdapter->assignResponsiblePerson($formObjectId, [
                        $allegroAccountId => $x13gpsrAdapter->createAllegroResponsiblePerson($allegroAccountId, $formObjectId)
                    ]);

                    $message = $this->l('Utworzono nową osobę odpowiedzialną w Allegro');
                    break;

                default:
                    throw new RuntimeException('Incorrect "formType"');
            }
        }
        catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e instanceof ApiException ? (string)$e : $e->getMessage()
            ]));
        }

        die(json_encode([
            'success' => true,
            'message' => $message
        ]));
    }
}
