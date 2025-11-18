<?php

use x13allegro\Api\XAllegroApi;

abstract class XAllegroSync
{
    /** @var int */
    protected static $current_allegro_account = null;

    /** @var XAllegroApi */
    protected static $api;

    /** @var XAllegroAccount */
    protected static $account;

    /**
     * @param int $id_xallegro_account
     * @return bool
     */
    protected static function changeAccount($id_xallegro_account)
    {
        // @todo Refactoring from static!!!
        //if (self::$api instanceof XAllegroApi && self::$current_allegro_account == (int)$id_xallegro_account) {
        //    return true;
        //}

        self::$current_allegro_account = (int)$id_xallegro_account;

        try {
            self::$account = new XAllegroAccount(self::$current_allegro_account);
            self::$api = new XAllegroApi(self::$account);

            if (!self::$account->active) {
                return false;
            }
        }
        catch (Exception $ex) {
            return false;
        }

        return true;
    }
}
