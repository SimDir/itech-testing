<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

class ReportController extends Controller {

    public function IndexAction($param = null) {

        return $this->View->Render('home.html');
    }

    public function GetAction($ReportName = null) {

        return $this->View->Render('report.html');
    }

    public function JointAction($param = null) {
        if ($param === 'api') {
            return ['req'=>$this->request];
        }
        return $this->View->Render('newreport.html');
    }

    private function WebHookB24($PostData) {
        $queryUrl = 'https://itech-testing.bitrix24.ru/rest/1/7weijsnqhivdav32/crm.lead.add.json';

        $queryData = http_build_query(array('fields' => array("TITLE" => 'Новый участник доклада',
                "NAME" => $PostData['UserData']['name'],
                "LAST_NAME" => $PostData['UserData']['name'],
                "STATUS_ID" => "NEW",
                "OPENED" => "Y",
//                "ASSIGNED_BY_ID" => 1,
                "CREATED_BY_ID" => 250,
                "OPPORTUNITY" => $PostData['OrderSum'],
                "COMMENTS" => json_encode($PostData['OrderParam']),
                "PHONE" => array(array("VALUE" => $PostData['UserData']['tel'], "VALUE_TYPE" => "WORK")),
                "EMAIL" => array(array("VALUE" => $PostData['UserData']['email'], "VALUE_TYPE" => "WORK")),),
            'params' => array("REGISTER_SONET_EVENT" => "Y")));

        $curl = curl_init();
        curl_setopt_array($curl, array(CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_POST => 1, CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $queryUrl, CURLOPT_POSTFIELDS => $queryData,));
        $result = curl_exec($curl);
        curl_close($curl);
    }

}
