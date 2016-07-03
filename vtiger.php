<?php

include_once('HTTP/Client.php');
include_once('Zend/Json/Json.php');

class VTiger {
    private $endpoint;
    private $httpc;

    private $sessionid;
    private $userid;

    function __construct($ep) {
        $this->endpoint = $ep;
        $this->httpc = new HTTP_Client();
    }

    function get($arg_string) {
        $this->httpc->get($this->endpoint . "?" . $arg_string);
        return $this->httpc->currentResponse();
    }

    function getJSON($arg_string) {
        $response = $this->get($arg_string);
        $dict = Zend\Json\Json::decode($response['body']);
        return $dict;
    }

    function post($data) {
        $this->httpc->post($this->endpoint, $data, true);
        return $this->httpc->currentResponse();
    }

    function postJSON($data) {
        $response = $this->post($data);
        $dict = Zend\Json\Json::decode($response['body']);
        return $dict;
    }

    function login($u, $p) {
        $response = $this->getJSON("operation=getchallenge&username=" . $u);
        if($response->success == false)
            die('getchallenge failed: ' . $response->error->errorMsg);

        $challengeToken = $response->result->token;
        $generatedKey = md5($challengeToken . $p);

        $response = $this->postJSON(array('operation'=>'login', 'username'=>$u, 'accessKey'=>$generatedKey));
        if($response->success == false)
            die('login failed: ' . $response->error->errorMsg);

        $this->sessionid = $response->result->sessionName;
        $this->userid = $response->result->userId;
    }

    function retrieve($id) {
        $response = $this->getJSON("sessionName=$this->sessionid&operation=retrieve&id=$id");
        if ($response->success == 1) {
            return $response->result;
        }
        return false;
    }

    function list_types() {
        $response = $this->getJSON("sessionName=$this->sessionid&operation=listtypes");
        print_r($response);
    }
}

function r($text) {
    $text=trim($text);
    $text = str_replace("&","%26",$text);
    return $text;
}

function misc() {

    if(isset($_POST['add'])) {
        $endpointUrl = "http://vtiger.intra.alrekry.fi/vtiger/webservice.php";
        $userName="admin";
        $userAccessKey = 'QrVFFXtdhgHEQFI';

        $httpc = new HTTP_Client();
        $httpc->get("$endpointUrl?operation=getchallenge&username=$userName");
        $response = $httpc->currentResponse();
        $jsonResponse = Zend\Json\Json::decode($response['body']);
        print_r($jsonResponse);
        if($jsonResponse->success==false)
        die('getchallenge failed:'.$jsonResponse->error->errorMsg);

        $challengeToken = $jsonResponse->result->token;
        $generatedKey = md5($challengeToken.$userAccessKey);
        $httpc->post("$endpointUrl",
             array('operation'=>'login', 'username'=>$userName,
                           'accessKey'=>$generatedKey), true);
        $response = $httpc->currentResponse();
        $jsonResponse = Zend\Json\Json::decode($response['body']);
        print_r("a");
        print_r($jsonResponse);
        print_r("b");
        if($jsonResponse->success == false)
        die('login failed:'.$jsonResponse->error->errorMsg);

        $sessionId = $jsonResponse->result->sessionName;
        $userId = $jsonResponse->result->userId;



        if(trim($_POST['PUHELIN']) != "" && trim($_POST['MATKAPUHELIN']) != "") {
        $puhelin = trim($_POST['PUHELIN']) . ", " . trim($_POST['MATKAPUHELIN']);
        } else if(trim($_POST['PUHELIN']) != "" && trim($_POST['MATKAPUHELIN']) == "") {
        $puhelin = trim($_POST['PUHELIN']);
        } else {
        $puhelin = trim($_POST['MATKAPUHELIN']);
        }

        $osoite = trim($_POST['OSOITE']) . "\n";
        if(trim($_POST['KAUPUNGINOSA______']) != "") {
        $osoite = $osoite . $_POST['KAUPUNGINOSA______'] . "\n";
        }
        $osoite = $osoite . $_POST['POSTINUMERO'] . " " . $_POST['POSTITOIMIPAIKKA__'];

        $nimi = $_POST['SUKUNIMI__________'] . " " . $_POST['ETUNIMI___________'];

        $contactData = array('discontinued'=>true,
                'productname'=>r($nimi),
                'cf_538'=>r($_POST['EMAIL']),
                'cf_539'=>r($puhelin),
                'cf_540'=>r($osoite),
                'cf_541'=>r($_POST['AMMATTI___________']),
                'productcategory'=>r($_POST['ala']),
                'description'=>r($_POST['full_form']),
                'cf_542'=>r($_POST['ERITYISTAIDOT']),
                'cf_543'=>r($_POST['HUOMIOT']),
                'assigned_user_id'=>'18x1' // ModuleID x UserID "'Users'x'admin'"
                );
        $objectJson = Zend\Json\Json::encode($contactData);
        $moduleName = 'Products';

        $params = array("sessionName"=>$sessionId, "operation"=>'create',
                "element"=>$objectJson, "elementType"=>$moduleName);
    //    print_r($params);
        $httpc->post("$endpointUrl", $params, true);
        $response = $httpc->currentResponse();
        print_r($response);
        $jsonResponse = Zend\Json\Json::decode($response['body']);

        if($jsonResponse->success==false)
        die('create failed: '.$jsonResponse->error->message);
        $savedObject = $jsonResponse->result;
        $id = $savedObject->id;

        echo "Lisätty: " . $nimi . ".\n\n";
        $cmd = "mv " . $_POST['fname'] . " " . "/var/www/html/lomake/accepted 2>&1";
        exec($cmd,$pal);
        //print_r($pal);

    } else if(isset($_POST['remove'])) {
        echo "Poistettu.";
        $cmd = "mv " . $_POST['fname'] . " " . "/var/www/html/lomake/rejected";
        shell_exec($cmd);
    } else {
        echo "Jotain outoa tapahtui.";
    }
}
