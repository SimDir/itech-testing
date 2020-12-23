<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

/**
 * Description of Model
 *
 * @author Ivan Kolotilkin
 * 
 * https://youtu.be/iU8zlbkpwyo
 */
use RedBeanPHP\Facade as R;

class Model extends R {

    public $TableName = '';

    public function __construct($cfgFile = 'DataBase.php') {
        $a = explode('\\', get_class($this));
        $this->TableName = mb_strtolower(str_replace('Model', '', end($a))); // задает имя таблице БД по умолчанию. "UserModel" таблица по умолчанию "user"  особенность бибилотеки ОРМ RedBeanPHP
        $IncFile = CONFIG_DIR . $cfgFile;

        if (file_exists($IncFile)) {
            $Config = include_once($IncFile);

            $host = $Config['db_host'];
            $port = $Config['db_port'];
            $dbname = $Config['db_name'];
            $login = $Config['db_login'];
            $pass = $Config['db_pass'];
        } else {
            $Config['db_driver'] = 'SQLite';
        }

        switch ($Config['db_driver']) {
            case "MariaDB":
                $this->setup("mysql:host=$host:$port;dbname=$dbname", $login, $pass);
                break;
            case "PostgreSQL":
                $this->setup("pgsql:host=$host:$port;dbname=$dbname", $login, $pass);
                break;
            case "SQLite":
                $this->setup('sqlite:' . APP . 'database.db');
                break;
            case "CUBRID":
                $this->setup("cubrid:host=$host;port=$port;dbname=$dbname", $login, $pass);
                break;
        }
        //for version 5.3 and higher
        //optional but recommended
        $this->useFeatureSet('novice/latest');
        $this->useJSONFeatures(true);

        if (!$this->testConnection()) {
//            $this->fancyDebug(TRUE);
            die("ошибка бaзы данных $host:$port. неудалось установить соединение c БД $dbname");
        }


        return $this;
    }

    public function __destruct() {
        $this->close();
    }

}
