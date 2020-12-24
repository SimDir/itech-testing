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
            return $this->AddReport();
        }
        return $this->View->Render('newreport.html');
    }

    private function AddReport() {
        if (!isset($this->Request['data'])) {
            return ['error' => 'не удалось получить данные от формы заявки'];
        }
        $data = $this->Request['data']; // данные которые пришли из форма черезе ajax
        $ReportData = []; // массив в который после проверки попадут данные

        if (!isset($data['name']) and $data['name'] !== '') {
            return ['error' => 'Не указано имя в формы заявки'];
        }
        $ReportData['name'] = strip_tags($data['name']);

        if (!isset($data['lastname']) and $data['lastname'] !== '') {
            return ['error' => 'Не указана фамилия в формы заявки'];
        }
        $ReportData['lastname'] = strip_tags($data['lastname']);

        if (!isset($data['email']) and $data['email'] !== '') {
            return ['error' => 'Не указана почта обратной связи в формы заявки'];
        }
        $ReportData['email'] = strip_tags($data['email']);

        if (!isset($data['phone']) and $data['phone'] !== '') {
            return ['error' => 'Не указан телефонный номер в формы заявки'];
        }
        $ReportData['phone'] = strip_tags($data['phone']);

        if (!isset($data['company']) and $data['company'] !== '') {
            return ['error' => 'Не указано имя в формы заявки'];
        }
        $ReportData['company'] = strip_tags($data['company']);

        if (!isset($data['reportThems']) and $data['reportThems'] !== '') {
            return ['error' => 'Не указано тема доклада в формы заявки'];
        }
        $ReportData['reportThems'] = strip_tags($data['reportThems']);

        if (!isset($data['reportShortDescriptions']) and $data['reportShortDescriptions'] !== '') {
            return ['error' => 'Не указано краткое описание в формы заявки'];
        }
        $ReportData['reportShortDescriptions'] = htmlspecialchars($data['reportShortDescriptions']); // если надо показать код в описании доклада. но не исполнять сам код

        if (!isset($data['reporter'])) {
            return ['error' => 'Не указан докладчик вы или слушатель в формы заявки'];
        }
        $ReportData['reporter'] = (bool) $data['reporter'];

//        dd($ReportData);
        $Report = new ReportModel();
        $mdlRet = $Report->AddReport($ReportData);
        if (!$mdlRet) {
            return ['error' => 'не удалось добавить заявку базу данных'];
        }
        $WebHookRet = $this->WebHookB24($ReportData);
        if (!$WebHookRet) {
            return ['error' => 'не удалось добавить лид в битрикс24'];
        }
        return ['success'=>true, 'Model_return_param' => $mdlRet, 'Web_hook_return_param' => $WebHookRet];
    }

    private function WebHookB24($PostData) {
        return true;
        $queryUrl = 'https://itech-testing.bitrix24.ru/rest/1/7weijsnqhivdav32/crm.lead.add.json';

        if ($PostData['reporter']) {
            $reportThems = $PostData['reportThems'];
        } else {
            $reportThems = 'Не является докладчиком';
        }

        $queryData = http_build_query(array('fields' => array(
                "TITLE" => 'Заявка от ' . $PostData['name'] . ' ' . $PostData['lastname'],
                "NAME" => $PostData['name'],
                "LAST_NAME" => $PostData['lastname'],
                "STATUS_ID" => "NEW",
                "OPENED" => "Y",
//                "ASSIGNED_BY_ID" => 1,
//                "CREATED_BY_ID" => 255,
                "COMPANY_TITLE" => $PostData['company'],
                "COMMENTS" => $reportThems,
                "SOURCE_DESCRIPTION" => "CRM-форма",
                "PHONE" => array(array("VALUE" => $PostData['phone'], "VALUE_TYPE" => "WORK")),
                "EMAIL" => array(array("VALUE" => $PostData['email'], "VALUE_TYPE" => "WORK")),
            ),
            'params' => array("REGISTER_SONET_EVENT" => "Y")));

        $curl = curl_init();
        curl_setopt_array($curl, array(CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_POST => 1, CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $queryUrl, CURLOPT_POSTFIELDS => $queryData,));
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

}
