<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

class ReportController extends Controller {

    use ReportHelperClientData; // трейт помошник контроллера по проверке данных с формы заявки участника

    public function IndexAction($param = null) {
        return $this->View->Render('home.html');
    }

    public function GetAction($ReportName = null) {
        if ($ReportName === 'api') {
            return $this->GetReportList();
        }

        $Report = new ReportModel();
        $ReportData = $Report->GetReportByName($ReportName);
        if (!$ReportData) {
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
        
        if(!$this->ChekClientData($this->Request['data'])){
            return ['error' => $this->reportDataErrorString];
        }
        $ReportData = $this->reportDataArray; // массив в который после проверки попадут данные

        $ReportData['alias'] = $this->ThemsTranslitToUrl($ReportData['thems']);

//        dd($ReportData);
        $Report = new ReportModel();
        $MdlRet = $Report->AddReport($ReportData);
        if (!$MdlRet) {
            return ['error' => 'не удалось добавить заявку базу данных'];
        }
        if ($ReportData['reporter']) {
            $ReportThems = $ReportData['thems'];
        } else {
            $ReportThems = 'Не является докладчиком';
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
                "COMMENTS" => $ReportThems,
                'EMAIL' => [
                    ["VALUE" => $ReportData['email'], "VALUE_TYPE" => "WORK"],
                ],
                'PHONE' => [
                    ['VALUE' => $ReportData['phone'], 'VALUE_TYPE' => 'WORK']
                ]
            ],
            'params' => ["REGISTER_SONET_EVENT" => "Y"],
        ]);


        if (!$WebHookRet) {
            return ['error' => 'не удалось добавить лид в битрикс24'];
        }
        return ['success' => true, 'model_return_param' => $MdlRet, 'web_hook_return_param' => $WebHookRet];
    }

    // для ЧПУ адреса незахотел использовать русский текст. хотя и кирилица работать будет в адресной строке браузера
    private function ThemsTranslitToUrl($strs) {
        $str = trim($strs, "  \n\r\t\v\0");
        $rus = array(' ', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
        $lat = array('-', 'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
        return str_replace($rus, $lat, $str);
    }

}
