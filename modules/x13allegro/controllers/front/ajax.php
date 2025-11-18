<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

class x13allegroAjaxModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (Tools::isSubmit('getAllegroAuctionLink'))
        {
            if ($auction = XAllegroAuction::getAuctionByProduct(Tools::getValue('id_product'), Tools::getValue('id_product_attribute'))) {
                die(json_encode(array(
                    'result' => true,
                    'href' => 'https://allegro.pl/show_item.php?item=' . $auction['id_auction']
                )));
            }
            else {
                die(json_encode(array('result' => false)));
            }
        }
    }
}
