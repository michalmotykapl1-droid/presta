<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\XAllegroApi;
use x13allegro\Api\Model\Tag;
use x13allegro\Json\JsonMapBuilder;

final class AdminXAllegroTagManagerController extends XAllegroController
{
    protected $allegroAutoLogin = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function ajaxProcessTagNew()
    {
        /** @var Tag $tag */
        $tag = (new JsonMapBuilder('Tag'))->map(new Tag());
        $tag->name = trim(substr(Tools::getValue('tagName'), 0, XAllegroApi::TAG_MAX_CHARS));

        try {
            $this->allegroApi->sale()->tags()->create($tag);
        }
        catch (Exception $ex) {
            die(json_encode(array(
                'result' => false,
                'message' => (string)$ex
            )));
        }

        switch (Tools::getValue('tagMapType'))
        {
            case XAllegroTagManager::MAP_PRODUCT:
                $object = new XAllegroProduct(null, Tools::getValue('tagMapId'));
                break;

            case XAllegroTagManager::MAP_CATEGORY:
                $object = new XAllegroCategory(Tools::getValue('tagMapId'));
                break;

            case XAllegroTagManager::MAP_MANUFACTURER:
                $object = new XAllegroManufacturer(Tools::getValue('tagMapId'));
                break;

            case XAllegroTagManager::MAP_AUCTION:
                $object = new XAllegroCategory(XAllegroCategory::getIdByAllegroCategory(Tools::getValue('tagMapId')));
                break;

            default:
                die(json_encode(array(
                    'result' => false,
                    'message' => $this->l('Nieprawidłowy typ mapowania')
                )));
        }

        die(json_encode(array(
            'result' => true,
            'message' => $this->l('Nowy tag został dodany'),
            'html' => (new XAllegroHelperTagManager())->renderTagsTable($this->allegroApi, $object->tags)
        )));
    }

    public function ajaxProcessTagSave()
    {
        /** @var Tag $tag */
        $tag = (new JsonMapBuilder('Tag'))->map(new Tag());
        $tag->id = Tools::getValue('tagId');
        $tag->name = trim(substr(Tools::getValue('tagName'), 0, XAllegroApi::TAG_MAX_CHARS));

        try {
            $this->allegroApi->sale()->tags()->update($tag);
        }
        catch (Exception $ex) {
            die(json_encode(array(
                'result' => false,
                'message' => (string)$ex
            )));
        }

        die(json_encode(array(
            'result' => true,
            'message' => $this->l('Tag został zapisany')
        )));
    }

    public function ajaxProcessTagDelete()
    {
        try {
            $this->allegroApi->sale()->tags()->deleteTag(Tools::getValue('tagId'));
        }
        catch (Exception $ex) {
            die(json_encode(array(
                'result' => false,
                'message' => (string)$ex
            )));
        }

        die(json_encode(array(
            'result' => true,
            'message' => $this->l('Tag został usunięty')
        )));
    }
}
