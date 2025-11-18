<?php
namespace x13allegro\SyncManager\Offer\Updater;

use x13allegro\Api\Model\Offers\OfferUpdate;
use x13allegro\Api\Model\Offers\Stock;
use x13allegro\Component\Logger\Log;
use x13allegro\Component\Logger\LogType;
use \Db;
use \Tools;
use \XAllegroAccount;
use \PrestaShopCollection;
use \x13allegro\Api\XAllegroApi;
use \StockAvailable;

class PriorityQuantityUpdater
{
    public function run()
    {
        $log = Log::instance();
        $accounts = (new PrestaShopCollection(\XAllegroAccount::class))
            ->where('active', '=', 1)
            ->getResults();

        foreach ($accounts as $account) {
            /** @var XAllegroAccount $account */
            $log->account($account->id)->info(LogType::CRON_STOCK_UPDATE(), ['message' => 'Priority Stock Update: START']);

            $priorityOffers = $this->getPriorityOffers($account);

            if (empty($priorityOffers)) {
                $log->account($account->id)->info(LogType::CRON_STOCK_UPDATE(), ['message' => 'Priority Stock Update: No offers found to check.']);
                continue;
            }

            $log->account($account->id)->info(LogType::CRON_STOCK_UPDATE(), ['message' => 'Priority Stock Update: Found ' . count($priorityOffers) . ' offers to check.']);

            $commands = [];
            foreach ($priorityOffers as $offer) {
                $shop_quantity = StockAvailable::getQuantityAvailableByProduct($offer['id_product'], $offer['id_product_attribute'], $account->id_shop);

                if ($shop_quantity < (int)$offer['allegro_quantity']) {
                    $offerUpdate = new OfferUpdate($offer['id_auction']);
                    $offerUpdate->stock = new Stock();
                    $offerUpdate->stock->available = (int)$shop_quantity;
                    $commands[] = $offerUpdate;
                }
            }
            
            if(empty($commands)) {
                $log->account($account->id)->info(LogType::CRON_STOCK_UPDATE(), ['message' => 'Priority Stock Update: No offers require an update after re-check.']);
                continue;
            }

            $log->account($account->id)->info(LogType::CRON_STOCK_UPDATE(), ['message' => 'Priority Stock Update: Preparing batch for ' . count($commands) . ' offers.']);
            
            $api = new XAllegroApi($account);
            $batches = array_chunk($commands, 1000);

            foreach ($batches as $batch) {
                $commandId = Tools::passwdGen(36);
                try {
                    $resource = $api->sale()->offerQuantityChangeCommands();
                    $resource->update($commandId, ['offers' => $batch]);
                    $log->account($account->id)->info(LogType::CRON_STOCK_UPDATE(), ['message' => 'Priority Stock Update: Batch sent.', 'commandId' => $commandId, 'count' => count($batch)]);

                    for ($i = 0; $i < 5; $i++) {
                        sleep(2);
                        $task = $resource->get($commandId);
                        if (in_array($task->task->status, ['DONE', 'FAIL'])) {
                            break;
                        }
                    }
                    $log->account($account->id)->info(LogType::CRON_STOCK_UPDATE(), ['message' => 'Priority Stock Update: Batch finished.', 'commandId' => $commandId, 'status' => $task->task->status]);

                } catch (\Exception $e) {
                    $log->account($account->id)->exception($e);
                }
            }
        }
    }

    private function getPriorityOffers(XAllegroAccount $account)
    {
        // Zapytanie jest teraz uproszczone, ponieważ dokładną weryfikację robimy w pętli
        $query = 'SELECT xa.id_auction, xa.quantity as allegro_quantity, xa.id_product, xa.id_product_attribute
                  FROM `' . _DB_PREFIX_ . 'xallegro_auction` xa
                  WHERE xa.id_xallegro_account = ' . (int)$account->id . '
                    AND xa.closed = 0
                    AND xa.archived = 0
                    AND xa.selling_mode = "BUY_NOW"';

        return Db::getInstance()->executeS($query);
    }
}