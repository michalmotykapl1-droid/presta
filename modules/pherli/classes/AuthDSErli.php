<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    PrestaHelp.com
 *  @copyright 2019 PrestaHelp
 *  @license   LICENSE.txt
 */

class AuthDSErli
{

    private $url_hash = 'aHR0cHM6Ly9tb2R1bGVzLnByZXN0YWhlbHAuY29tL3dzLw==';

    private $url_hash_second = 'aHR0cDovL2xpY2VuY2plLmRldi1lZ3p5bC5wbC93cy8='; // no ssl

    private $name;

    private $domain;

    private $module_name = 'Integracja z erli.pl';

    public function __construct($name = false, $domain = false)
    {
        if ($name) {
            $this->name = $name;
        }
        if ($domain) {
            $this->domain = $domain;
        }
    }

    public function checkLicence()
    {
        $licenceSX = Configuration::get('ERLI_LICENCE_VALID');
        $date = date('Y-m-d', strtotime('-1 day'));
        if (empty($licenceSX) || $licenceSX < $date) {
            $this->getLicenceInfo();
        }
        return (int)Configuration::get('ERLI_LICENCE_INFO');
    }

    public function renewLicence()
    {
        $this->getLicenceInfo();
    }

    private function getLicenceInfo()
    {
        $licence = $this->isActive($this->domain);
        if (empty($licence)) {
            $domain = AuthDS::clearDomain($this->domain);
            $licence = $this->getStaticLicence($domain, $this->module_name, Configuration::get('ERLI_LICENCE'));
        }
        Configuration::updateValue('ERLI_LICENCE_VALID', date('Y-m-d'));
        Configuration::updateValue('ERLI_LICENCE_INFO', (int)$licence['licence']->licence);
    }

    public function isActive()
    {
        $url = base64_decode($this->url_hash).'getLicence';
        $data = array(
            'moduleName' => $this->name,
            'domain' => $this->domain
        );
        $request = $this->connect($url, $data);
        return  (array)json_decode($request['response']);
    }

    public function getLicence()
    {
        $licence = $this->isActive();
        if (empty($licence)) {
            $domain = AuthDS::clearDomain($this->domain);
            $licence = $this->getStaticLicence($domain, $this->module_name, Configuration::get('ERLI_LICENCE'));
        }
        return $licence;
    }

    public function isActive_($domain)
    {
        $url = base64_decode($this->url_hash).'getLicence';
        $data = array(
            'moduleName' => $this->name,
            'domain' => $domain
        );
        $request = $this->connect($url, $data);
        return  (array)json_decode($request['response']);
    }

    /**
     * @param $module_name
     * @param $module_version
     * @param $domain
     * @return bool
     */
    public function makeTools($module_name, $module_version, $domain)
    {
        $url = base64_decode($this->url_hash).'set';
        $data = array(
            'moduleName' => $module_name,
            'moduleVersion' => $module_version,
            'domain' => $domain,
            'date_add' => date('Y-m-d H:i:s')
        );
        $response = $this->connect($url, $data);
        if (!$response) {
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function getBaners()
    {
        $url = base64_decode($this->url_hash).'getproducts';
        try {
            $response = array();//$this->connect($url, '');
        } catch (Exception $e) {
            print_r($e);
        }
        if (empty($response['response'])) {
            return false;
        }
        $resp = (array)json_decode($response['response']);
        return $resp;
    }

    /**
     * @return array
     */
    public function getAuthor()
    {
        $url = base64_decode($this->url_hash).'getAuthor';
        try {
            $response = array();//$this->connect($url, '');
        } catch (Exception $e) {
            print_r($e);
        }
        if (empty($response['response'])) {
            return false;
        }
        $resp = (array)json_decode($response['response']);
        $respons = array();
        if (!empty($resp)) {
            foreach ($resp as $r) {
                $respons[$r->name] = $r->value;
            }
        }
        return $respons;
    }

    /**
     * @return array|bool
     */
    public function getChangelog($domain = '')
    {
        $url = base64_decode($this->url_hash).'getchangelog';
        $data = array(
            'module' => $this->name,
            'moduleName' => $this->name,
            'domain' => $domain,
        );
        try {
            $response = $this->connect($url, $data);
//            print_r($response);
        } catch (Exception $e) {
            print_r($e);
        }
        return (array)json_decode($response['response']);
    }

    /**
     * @return array|bool
     */
    public function getChangelogOther($domain = '')
    {
        $url = base64_decode($this->url_hash).'getchangelogother';
        $data = array(
            'module' => $this->name,
            'moduleName' => $this->name,
            'domain' => $domain,
        );
        try {
            $response = $this->connect($url, $data);
        } catch (Exception $e) {
            print_r($e);
        }
        return (array)json_decode($response['response']);
    }

    /**
     * @return array|bool
     */
    public function getCurrentModuleVersion($domain = '')
    {
        $url = base64_decode($this->url_hash).'getCurrentModuleVersion';
        $data = array(
            'module' => $this->name,
            'moduleName' => $this->name,
            'domain' => $domain,
        );
        $request = $this->connect($url, $data);
        return (array)json_decode($request['response']);
    }

    private function connectCurl($url, $data, $time = 10000)
    {
        $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';
//        $url = str_replace('modules.', 'modules3.', $url);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch,CURLOPT_TIMEOUT,$time);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $time);
        $return['response'] = curl_exec($ch);
        $return['info'] = curl_getinfo($ch);
        curl_close($ch);
        return $return;
    }

    private function connect($url, $data, $time = 10000)
    {
        $return = $this->connectCurl($url, $data, $time);
        if ($return['info']['http_code'] == 200 && $return['response'] != 'Link to database cannot be established: SQLSTATE[HY000] [2002] No such file or directory') {
            return $return;
        } else {
            $url2 = base64_decode($this->url_hash_second).'getLicence';
            $return = $this->connectCurl($url2, $data, $time*10);
            if ($return['info']['http_code'] == 200) {
                return $return;
            }
        }
        return $return;
    }

    /**
     * @return bool|string|string[]
     */
    public static function getBanersHtml()
    {
        $url = "https://update.prestahelp.com/produkty.html";
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $html = curl_exec($ch);
        curl_close($ch);
        $html = str_replace('src="app/', 'src="https://update.prestahelp.com/app/', $html);
        return $html;
    }

    public function getStaticLicence($domain, $module_name, $licenceUser, $showLicence = null)
    {
        $time = 10;
        $key = 'fd2g9Blh8Y';
        $module_number = 50;
        $licence = hash('sha512', $domain.(int)$time.$key.$module_name.(int)$module_number);
        Configuration::updateValue('ERLI_LICENCE_GEN', $licence);
        Configuration::updateValue('ERLI_LICENCE_GEN_DATA', $domain.'|'.$time.'|'.$key.'|'.$module_name.'|'.$module_number);
        $licences = array();
        if ($showLicence == 1) {
            echo $licenceUser.'<br />'.$licence.'<br />';
        }
        if ($licenceUser != $licence) {
            $licences['licence'] = new stdClass();
            $licences['licence']->licence = 1;
            $licences['licence']->date_expire_update = date('Y-m-d H:i:s', strtotime('+365 days'));
            $licences['licence']->date_expire_support = date('Y-m-d H:i:s', strtotime('+90 days'));
            $licences['licence']->date_expire = date('Y-m-d H:i:s', strtotime('+9999 days'));
            $licences['licence']->licence_update = 1;
            $licences['licence']->licence_support = 0;
            $licences['licence']->time = 10;
            $licences['licence']->time_upd = 7;
            $licences['licence']->date_expire_update_left = new stdClass();
            $licences['licence']->date_expire_update_left->left = '';
        }
        if ($showLicence == 1) {
            print_r($licences);
        }
        return $licences;
    }

    public static function clearDomain($domain) {
        $domain = str_replace('http://', '', $domain);
        $domain = str_replace('https://', '', $domain);
        $domain = str_replace('www.', '', $domain);
        $domain = str_replace('/', '', $domain);
        return $domain;
    }

    public function getMessageInfo($id_key)
    {
        $url = base64_decode($this->url_hash).'getMessages';
        $data = array(
            'id_module' => 50,
            'id_key' => $id_key
        );
        $response = $this->connect($url, $data);
        if (!$response) {
            return false;
        }
        if (empty($response['response'])) {
            return false;
        }
        return (array)json_decode($response['response']);
    }

}
