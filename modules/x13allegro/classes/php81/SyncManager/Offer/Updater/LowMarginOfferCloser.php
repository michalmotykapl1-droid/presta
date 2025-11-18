<?php
namespace x13allegro\SyncManager\Offer\Updater;

use Configuration;
use Db;
use DbQuery;
use Exception;
use PrestaShopCollection;
use Product;
use XAllegroAccount;
use XAllegroAuction;
use x13allegro\Api\Model\Offers\OfferUpdate;
use x13allegro\Api\Model\Offers\Publication;
use x13allegro\Api\Model\Offers\Enum\PublicationStatus;
use x13allegro\Api\XAllegroApi;
use x13allegro\Component\Logger\Log;
use x13allegro\Component\Logger\LogType;

class LowMarginOfferCloser
{
    public function run()
    {
        if (!Configuration::get('X13_ALLEGRO_AUTO_END_LOW_MARGIN')) {
            return;
        }

        $log = Log::instance();
        $accounts = (new PrestaShopCollection(\XAllegroAccount::class))
            ->where('active', '=', 1)
            ->getResults();

        foreach ($accounts as $account) {
            /** @var XAllegroAccount $account */
            $log->account($account->id)->info(LogType::CRON_BOOTSTRAP(), ['message' => 'Low Margin Closer: START']);
            $this->processAccount($account);
            $log->account($account->id)->info(LogType::CRON_BOOTSTRAP(), ['message' => 'Low Margin Closer: END']);
        }
    }

    private function processAccount(XAllegroAccount $account)
    {
        @set_time_limit(900);
        $log = Log::instance();
        
        $marginThreshold = (float)Configuration::get('X13_ALLEGRO_LOW_MARGIN_THRESHOLD', null, null, null, 20);

        $offersToProcess = [];
        $limit = 100;
        $offset = 0;
        $apiFilters = [
            'publication.status' => 'ACTIVE',
            'sellingMode.format' => 'BUY_NOW'
        ];

        try {
            $api = new XAllegroApi($account);
            if (!$api->isLogged()) {
                return;
            }

            do {
                $result = $api->sale()->offers()->getList($apiFilters, $limit, $offset);
                if (!empty($result->offers)) {
                    $offersToProcess = array_merge($offersToProcess, $result->offers);
                }
                $offset += $limit;
            } while (!empty($result->offers) && count($result->offers) === $limit);
        } catch (Exception $e) {
            $log->account($account->id)->exception($e);
            return;
        }
        
        if (empty($offersToProcess)) {
            $log->account($account->id)->info(LogType::CRON_BOOTSTRAP(), ['message' => 'Low Margin Closer: No active offers found.']);
            return;
        }

        $offersIds = array_map(function ($object) { return $object->id; }, $offersToProcess);
        $allAuctionsList = XAllegroAuction::getAuctionAssociationsForList($offersIds);
        
        $allAuctions = array_column($allAuctionsList, null, 'id_auction');

        foreach ($offersToProcess as $offer) {
            if (!isset($allAuctions[$offer->id])) {
                continue;
            }
            $binded = $allAuctions[$offer->id];

            $sku = $binded['reference'];
            if (strpos($sku, 'A_MAG') === 0) {
                continue;
            }

            $priceBuyNow = (is_object($offer->sellingMode) && is_object($offer->sellingMode->price)) ? (float)$offer->sellingMode->price->amount : 0;
            if ($priceBuyNow == 0) {
                continue;
            }
            
            $wholesale_price_unit = 0;
            $id_shop = (int)$binded['id_shop'];

            if (!empty($binded['id_product_attribute'])) {
                $sql = new DbQuery();
                $sql->select('pas.wholesale_price');
                $sql->from('product_attribute_shop', 'pas');
                $sql->where('pas.id_product_attribute = ' . (int)$binded['id_product_attribute']);
                $sql->where('pas.id_shop = ' . $id_shop);
                $wholesale_price_unit = (float)Db::getInstance()->getValue($sql);
            }
            if ($wholesale_price_unit == 0 && !empty($binded['id_product'])) {
                $sql = new DbQuery();
                $sql->select('ps.wholesale_price');
                $sql->from('product_shop', 'ps');
                $sql->where('ps.id_product = ' . (int)$binded['id_product']);
                $sql->where('ps.id_shop = ' . $id_shop);
                $wholesale_price_unit = (float)Db::getInstance()->getValue($sql);
            }
            
            if ($wholesale_price_unit > 0) {
                $product_name = Product::getProductName($binded['id_product'], $binded['id_product_attribute'], $account->id_language, $id_shop);
                
                $package_quantity = 1;
                if (preg_match('/\((\d+[\.,]?\d*)\s?([kK][gG]|[lL])\)/', $product_name, $matches)) {
                    $package_quantity = (float)str_replace(',', '.', $matches[1]);
                }

                $total_wholesale_price = $wholesale_price_unit * $package_quantity;
                $product_for_tax = new Product($binded['id_product'], false, null, $id_shop);
                $tax_rate = $product_for_tax->getTaxesRate();
                $wholesale_price_gross = $total_wholesale_price * (1 + ($tax_rate / 100));
                $margin_percentage = ($wholesale_price_gross > 0) ? (($priceBuyNow - $wholesale_price_gross) / $wholesale_price_gross) * 100 : 0;

                if ($margin_percentage < $marginThreshold) {
                    try {
                        $offerUpdate = new OfferUpdate($offer->id);
                        $offerUpdate->publication = new Publication();
                        $offerUpdate->publication->status = PublicationStatus::ENDED;
                        $api->sale()->productOffers()->update($offerUpdate);
                        $log->account($account->id)->offer((int)$offer->id)->info(LogType::OFFER_PUBLICATION_STATUS_ENDED(), [
                            'message' => 'Offer ended automatically due to low margin.', 
                            'margin' => round($margin_percentage, 2) . '%',
                            'threshold' => $marginThreshold . '%'
                        ]);
                    } catch (Exception $e) {
                        $log->account($account->id)->offer((int)$offer->id)->exception($e);
                    }
                }
            }
        }
    }
}