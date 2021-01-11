<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

class ReportController extends Controller {

    public function IndexAction($param = null) {

        return $this->View->Render('home.html');
    }

    public function GetAction($ReportName = null) {
        if ($ReportName === 'api') {
            return $this->GetReportList();
        }

        $Report = new ReportModel();
        $ReportData = $Report->GetReportByName($ReportName);
        if(!$ReportData){
            return '<h1>Доклад не найден</h1>';
        }
        $this->View->VarSetArray($ReportData);
        return $this->View->Render('report.html');
        
    }

    public function JointAction($param = null) {
        if ($param === 'api') {
            return $this->AddReport();
        }
        return $this->View->Render('newreport.html');
    }

    private function GetReportList() {
        $Report = new ReportModel();
        return $Report->GetAllReport();
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
        $ReportData['thems'] = strip_tags($data['reportThems']);
        $ReportData['alias'] = $this->ThemsTranslitToUrl($ReportData['thems']);
        if (!isset($data['reportShortDescriptions']) and $data['reportShortDescriptions'] !== '') {
            return ['error' => 'Не указано краткое описание в формы заявки'];
        }
        $ReportData['descriptions'] = htmlspecialchars($data['reportShortDescriptions']); // если надо показать код в описании доклада. но не исполнять сам код

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
        if ($ReportData['reporter']) {
            $reportThems = $PostData['reportThems'];
        } else {
            $reportThems = 'Не является докладчиком';
        }
        // значения добавил по хардкору. по идеи все это надо выносить либо в фаил конфигов а лучше в админ раздел приложения чтоб можно менять эти параметры через админку сайта.
        $b24 = new \Fomvasss\Bitrix24ApiHook\Bitrix24('https://itech-testing.bitrix24.ru', 1, '7weijsnqhivdav32');
        // see "crm.lead.add"
        $WebHookRet = $b24->crmLeadAdd([
            "fields" => [
                'TITLE' => 'Заявка от ' . $ReportData['name'] . ' ' . $ReportData['lastname'],
                'NAME' => $ReportData['name'],
                "LAST_NAME" => $ReportData['lastname'],
                "STATUS_ID" => "NEW",
                "OPENED" => "Y",
                "COMPANY_TITLE" => $ReportData['company'],
                "COMMENTS" => $reportThems,
                'EMAIL' => [
                    ["VALUE" => $ReportData['email'], "VALUE_TYPE" => "WORK" ],
                ],
                'PHONE' => [
                    ['VALUE' => $ReportData['phone'],'VALUE_TYPE' => 'WORK']
                ]
            ],
            'params' => ["REGISTER_SONET_EVENT" => "Y"],
        ]);
        
        
        if (!$WebHookRet) {
            return ['error' => 'не удалось добавить лид в битрикс24'];
        }
        return ['success' => true, 'Model_return_param' => $mdlRet, 'Web_hook_return_param' => $WebHookRet];
    }
    // для ЧПУ адреса незахотел использовать русский текст. хотя у кирилица работать будет в адресной строке браузера
    private function ThemsTranslitToUrl($strs) {
        $str = trim($strs, "  \n\r\t\v\0");
        $rus = array(' ', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
        $lat = array('-', 'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
        return str_replace($rus, $lat, $str);
    }

}
