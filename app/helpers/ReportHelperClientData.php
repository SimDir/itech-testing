<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

/**
 * Проверяет на коректность полученных от клиента(браузера) данных
 * доклада
 * @author user
 */
trait ReportHelperClientData {

    private $reportDataErrorString = '';
    private $reportDataArray = [];

    private function ChekClientData($data) {
        if (!isset($data['name']) and $data['name'] !== '') {
            $this->reportDataErrorString = 'Не указано имя в форме заявки';
            return false;
        }
        $this->reportDataArray['name'] = strip_tags($data['name']);

        if (!isset($data['lastname']) and $data['lastname'] !== '') {
            $this->reportDataErrorString = 'Не указана фамилия в форме заявки';
            return false;
        }
        $this->reportDataArray['lastname'] = strip_tags($data['lastname']);

        if (!isset($data['email']) and $data['email'] !== '') {
            $this->reportDataErrorString = 'Не указана почта обратной связи в форме заявки';
            return false;
        }
        $this->reportDataArray['email'] = strip_tags($data['email']);

        if (!isset($data['phone']) and $data['phone'] !== '') {
            $this->reportDataErrorString = 'Не указан телефонный номер в форме заявки';
            return false;
        }
        $this->reportDataArray['phone'] = strip_tags($data['phone']);

        if (!isset($data['company']) and $data['company'] !== '') {
            $this->reportDataErrorString = 'Не указано название вашей компании в форме заявки';
            return false;
        }
        $this->reportDataArray['company'] = strip_tags($data['company']);

        if (!isset($data['reporter'])) {
            $this->reportDataErrorString = 'Не указан докладчик вы или слушатель в форме заявки';
            return false;
        }
        $this->reportDataArray['reporter'] = (bool) $data['reporter'];
        /* 
        * если пользователь оставляющий заявку не является докладчиком 
        * то прекращаем проверку присланных данных с формы заполнив недостающие данные по умолчанию
        * иначе продолжаем проверять остальные поля формы
        */
        if(!$this->reportDataArray['reporter']){ 
            $this->reportDataArray['thems'] = 'Не заполнено пользователем';
            $this->reportDataArray['descriptions'] = 'Не заполнено пользователем';
            return true;
        }

        if (!isset($data['reportThems']) and $data['reportThems'] !== '') {
            $this->reportDataErrorString = 'Не указано тема доклада в форме заявки';
            return false;
        }
        $this->reportDataArray['thems'] = strip_tags($data['reportThems']);
        
        if (!isset($data['reportShortDescriptions']) and $data['reportShortDescriptions'] !== '') {
            $this->reportDataErrorString = 'Не указано краткое описание в форме заявки';
            return false;
        }
        $this->reportDataArray['descriptions'] = htmlspecialchars($data['reportShortDescriptions']); // если надо показать код в описании доклада. но не исполнять сам код

        return true;
    }

}
