<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');
/**
 * для отладки приложжеия.
 * покажи и умри
 * 
 */
function dd($str) {
    dump($str);
//    var_dump($str);
    die();
}

/**
 * Главный класс всего приложения
 * 
 */
final class core {

    Private static $globalConfig = [];
    private static $ExecRetVal; // суюа идут данные выполненные контроллером. 
    /*
     * Переменные роутинга 
     */
    public static $URI = ''; // Строка УРЛ запроса  site.com/Controller/Action/Param1/Param2/Param3/... и так далее
    Private static $ControllerName; // Имя выполняемого контроллера <Controller>
    Private static $ActionName; // Имя выполняемого метода <Action>
    Private static $ControllerFile; // подключаемый фаил контроллера <...\ControllerPath\*Name*Controller.php>
    Private static $ParametersArray; // массив параметров которые пришли в УРЛ строке

    /**
     * Включает отладчик Whoops
     * https://github.com/filp/whoops
     * 
     * https://phpprofi.ru/blogs/post/77
     * 
     */
    private static function initWhoops() {
        $whoops = new \Whoops\Run;
        $whoops_pretty_page_handler = new \Whoops\Handler\PrettyPageHandler();
        $whoops_pretty_page_handler->setEditor('vscode');
        $whoops->pushHandler($whoops_pretty_page_handler);
//        Свой обработчик исключения
//        $whoops->pushHandler(function($e) {
//            
//        });
        $monolog_multiline_formatter = new \Monolog\Formatter\LineFormatter(null, null, true);
        $monolog_error_log_handler = new \Monolog\Handler\ErrorLogHandler();
        $monolog_error_log_handler->setFormatter($monolog_multiline_formatter);
        $monolog_logger_error_log = new \Monolog\Logger('whoops_logger', [$monolog_error_log_handler]);
        $monolog_logger_error_log->pushHandler(new \Monolog\Handler\StreamHandler(SITE_DIR . 'error.log'));


        $whoops_plain_text_handler = new \Whoops\Handler\PlainTextHandler();
        $whoops_plain_text_handler->loggerOnly(true);
        $whoops_plain_text_handler->setLogger($monolog_logger_error_log);
        $whoops->pushHandler($whoops_plain_text_handler);
        $monolog_browser_console_handler = new \Monolog\Handler\BrowserConsoleHandler();
        $monolog_browser_console_handler->setFormatter($monolog_multiline_formatter);
        $monolog_browser_console_logger = new \Monolog\Logger('whoops_browser_console_logger', [$monolog_browser_console_handler]);
        $whoops_plain_text_handler2 = new \Whoops\Handler\PlainTextHandler();
        $whoops_plain_text_handler2->loggerOnly(true);
        $whoops_plain_text_handler2->setLogger($monolog_browser_console_logger);
        $whoops->pushHandler($whoops_plain_text_handler2);
        $whoops->register();
    }

    /**
     * Основной метод запускает все приложение. так называемая точка входа
     * 
     */
    public static function Run() {
        self::SetupConfig();
        self::InitAutoload();

        if (SHOW_ERROR)
            self::initWhoops();
        Session::init();

        self::GetControllerAndAction();
        self::$ExecRetVal = self::Exec(self::$ControllerName, self::$ActionName, self::$ParametersArray);
//                header("x-powered-by: PHP/9.6.9");  // шутка юмора
        if (is_string(self::$ExecRetVal)) {
            $viw = View::getInstance();
            $viw->mainbody = self::$ExecRetVal;
            echo $viw->execute();
        } elseif (is_array(self::$ExecRetVal)) {
            if (!headers_sent()) {
                header("Access-Control-Allow-Origin: *");
                header("Content-Type: application/json; charset=UTF-8");
            }
            echo json_encode(self::$ExecRetVal, JSON_UNESCAPED_UNICODE);
        } elseif (self::$ExecRetVal === false) {
            exit();
        } else {
            if (!headers_sent()) {
                header("HTTP/1.1 400 Bad Request");
                header("Status: 400 Bad Request");
            }
            if (SHOW_ERROR)
                dump(self::$ExecRetVal);
        }
    }

    /**
     * Автозагрузчик класов относящихся к пространству имени данного приложения
     * автозагрузкой сторонних библиотек занимается Composer
     * 
     * https://habr.com/ru/post/439200/
     */
    public static function InitAutoload() {
        spl_autoload_register(__CLASS__ . '::AutoLoadClassFile');
    }

    /**
     * Выполняет контроллер!
     * @return String
     */
    public static function Exec($Controller = '', $Action = '', $Param = []) {

        $ctrl = 'mocrm\\' . $Controller;

        if (class_exists($ctrl)) {
            $objectCtrl = new $ctrl();
            if (method_exists($objectCtrl, $Action)) {

                if (count($Param)) {
                    return call_user_func_array(array($objectCtrl, $Action), $Param);
                } else {
                    return call_user_func(array($objectCtrl, $Action));
                }
            }
            if (!headers_sent()) {
                header("HTTP/1.1 405 Method Not Allowed");
                header("Status: 405 Method Not Allowed");
            }
            if (SHOW_ERROR) {
                return "<h1>405 Method Not Allowed</h1>" . __METHOD__ . "<h5> Контроллер <b style=\"color: red;\">" . $Controller . "</b> Не имеет метода <b style=\"color: red;\">$Action()</b></h5>";
            } else {
                return '';
            }
        }
        if (!headers_sent()) {
            header("HTTP/1.1 523 Origin Is Unreachable");
            header("Status: 523 Origin Is Unreachable");
        }
        if (SHOW_ERROR) {
            return "<h1>523 Origin Is Unreachable</h1>" . __METHOD__ . "<h5> Нет исполнительного контроллера <b style=\"color: red;\">$Controller</b></h5>";
        } else {
            return '';
        }
    }

    /**
     * функция получения запроса который пришел от пользователя приложением
     * @return String
     */
    private static function GetURI() {
        if (self::$URI)
            return self::$URI;
        $pathInfo = filter_input(INPUT_SERVER, 'PATH_INFO');
        if ($pathInfo) {
            $path = $pathInfo;
        } else {
            $requestURI = filter_input(INPUT_SERVER, 'REQUEST_URI');
            if (strpos($requestURI, '?')) {
                $requestURI = substr($requestURI, 0, strpos($requestURI, '?'));
            } elseif (strpos($requestURI, '&')) {
                $requestURI = substr($requestURI, 0, strpos($requestURI, '&'));
            }
            $path = trim($requestURI);
        }
//        dd($path);
        if (!$path) {
            $path = '/';
        }
        $path = parse_url($path);
        self::$URI = trim($path['path'], '/');
        self::$URI = str_replace('index.php', '', urldecode(self::$URI));
//        dd(self::$URI);
        return self::$URI;
    }

    /**
     * Получаем контроллер и метод.
     * данная функция находит в УРЛ тот контроллер и метот на который пршол запрос
     * заполныет переменные роутинга
     * и если необходимо настраевает пришедший в УРЛ запрос
     * @return Boolean
     */
    private static function GetControllerAndAction() {
        $access = false;
        self::GetURI();
        $cfg = self::$globalConfig['App_Config_Dir'] . self::$globalConfig['App_Router_Config_File'];
        if (file_exists($cfg)) {
            $routes = include($cfg);
        } else {
//            throw new \Exception(__METHOD__ . " Конфигурационный фаил роутинга $cfg не найден. продолжить невозможно");
//            return false;
            $routes = [];
        }

        // проверяю запрос на соответствие регулярному выражению
        foreach ($routes as $uriPattern => $path) {
            if (!preg_match("~$uriPattern~", self::$URI)) {
                continue;
            }
            // получаем внутренний путь из внешнего согласно правилам маршрутизации
            $access = preg_replace("~$uriPattern~", $path, self::$URI);
        }
        if (!$access) {
            if (empty(self::$URI)) {
                $access = self::$globalConfig['Router_Default_Controller'] . "/" . self::$globalConfig['Router_Default_Action']; //
            } else {
                $access = self::$URI;
            }
        }
        $segments = explode('/', $access);
        $dirForControllers = self::$globalConfig['App_Controllers_Dir'];
        $controlerName = ucfirst(array_shift($segments)) . 'Controller';
        self::$ControllerName = $controlerName;
        $controllerFile = $dirForControllers . $controlerName . '.php';
        $action = ucfirst(array_shift($segments));
        if (empty($action)) {
            $action = ucfirst(self::$globalConfig['Router_Default_Action']);
//            var_dump($this->globalConfig['Router_Default_Action']);
        }
        $actionName = $action . 'Action';
        self::$ActionName = $actionName;
        self::$ParametersArray = $segments;
        if (!file_exists($controllerFile)) {
//            if (file_exists($dirForControllers) && is_dir($dirForControllers)) {
//                die('Директория с контроллерами не найдена');
//            }
            $dirArray = scandir($dirForControllers);
            foreach ($dirArray as $da) {
                if (is_dir($dirForControllers . $da)) {
                    $controllerFile = $dirForControllers . $da . DIRECTORY_SEPARATOR . $controlerName . '.php';
                    if (file_exists($controllerFile)) { // если в текущей подпапке есть контроллер
                        return self::$ControllerFile = $controllerFile;
                    }
                }
            }

//            throw new \Exception(__METHOD__ . ' [ERROR:404] фаил Контроллера ' . $controlerName . '.php не найден');
            return FALSE;
        } else {
            self::$ControllerFile = $controllerFile;
            return $controllerFile;
        }
//        throw new \Exception(__METHOD__ . ' [ERROR:404] фаил Контроллера ' . $controlerName . '.php не найден');
        return FALSE;
    }

    public static function Config() {
        return self::$globalConfig;
    }

    /**
     * настраиваем основную конфигурацию ядра системы
     */
    private static function SetupConfig() {
        self::$globalConfig['App_Name'] = __NAMESPACE__;
        self::$globalConfig['App_Dir'] = APP;
        self::$globalConfig['App_lib_Dir'] = self::$globalConfig['App_Dir'] . 'classes' . DIRECTORY_SEPARATOR;
        self::$globalConfig['App_Config_Dir'] = CONFIG_DIR;
        self::$globalConfig['App_Controllers_Dir'] = self::$globalConfig['App_Dir'] . 'controllers' . DIRECTORY_SEPARATOR;
        self::$globalConfig['App_Models_Dir'] = self::$globalConfig['App_Dir'] . 'models' . DIRECTORY_SEPARATOR;
        self::$globalConfig['App_User_locale'] = filter_input(INPUT_SERVER, "HTTP_ACCEPT_LANGUAGE", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        self::$globalConfig['App_Templates_Dir'] = TEMPLATE_DIR; // . 'default' . DIRECTORY_SEPARATOR;

        date_default_timezone_set(TIMEZONE);

        if (!file_exists(self::$globalConfig['App_Config_Dir'] . 'AutoLoader.php')) {
            self::$globalConfig['App_Clas_Loader_Dir_Array'] = ['classes', 'models', 'controllers'];
        } else {
            self::$globalConfig['App_Clas_Loader_Dir_Array'] = include_once self::$globalConfig['App_Config_Dir'] . 'AutoLoader.php';
        }

        self::$globalConfig['App_Router_Config_File'] = 'Routes.php';
        self::$globalConfig['Router_Default_Controller'] = 'index';
        self::$globalConfig['Router_Default_Action'] = 'index';
    }

    /**
     * Автоматическая загрузка классов
     * @param type $className
     * @return boolean
     */
    private static function AutoLoadClassFile($className) {
        $parts = explode('\\', $className);
        $class = end($parts);
//        file_put_contents(SITE_DIR.'alog.txt', $class.PHP_EOL,FILE_APPEND);
        $classFile = self::$globalConfig['App_lib_Dir'] . $class . '.php';
        if (file_exists($classFile)) {
            include_once $classFile;
            return $classFile;
        }
        return self::LoadClassFileForAllDir($class);
    }

    private static function LoadClassFileForAllDir($className) {
//        echo $className;
//        self::$loadClassArray[] = $className;
        $dirArr = self::$globalConfig['App_Clas_Loader_Dir_Array'];
        $appDir = self::$globalConfig['App_Dir'];
        foreach ($dirArr as $value) {
            $classFile = self::SearchFile($className . '.php', $appDir . $value);
            if (file_exists($classFile)) {
                include_once $classFile;
                return $classFile;
            }
        }
        return FALSE;
    }

    /**
     * Поиск файла по имени во всех папках и подпапках
     * @param string $fileName - искомый файл
     * @param string $folderName - пусть до папки
     */
    public static function SearchFile($fileName, $folderName) {
        // перебираем пока есть файлы
        if (!is_dir($folderName)) {
            return false;
        }
        $dirArray = scandir($folderName);
        foreach ($dirArray as $file) {
            if ($file != "." && $file != "..") {
                // если файл проверяем имя
                if (is_file($folderName . DIRECTORY_SEPARATOR . $file)) {
                    // если имя файла искомое,
                    // то вернем путь до него
                    if ($file == $fileName) {
                        return $folderName . DIRECTORY_SEPARATOR . $file;
                    }
//                    echo $folderName.'\\'.$file.'<br>';
                }
                // если папка, то рекурсивно
                // вызываем SearchFile
                if (is_dir($folderName . DIRECTORY_SEPARATOR . $file)) {
                    $retVal = self::SearchFile($fileName, $folderName . DIRECTORY_SEPARATOR . $file);
                    if ($retVal) { // если фуекция что-то вернула то выходим
                        return $retVal;
                    }
                }
            }
        }
    }

    public static function Redirect($url, $permanent = false) {
        if (headers_sent() === false) {
            header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
        }
        return '<script type="text/javascript">window.location = "' . $url . '"</script>';
    }

// Encrypt Function
    public static function StrEncrypt($encrypt, $key) {
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($encrypt, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

// Decrypt Function
    public static function StrDecrypt($decrypt, $key) {
        $c = base64_decode($decrypt);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        if (hash_equals($hmac, $calcmac)) {
            return $plaintext;
        }
        return false;
    }

    public static function BrouserHash() {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $ua = implode(" - ", $_SERVER);
        }

        return md5($_SERVER['REMOTE_ADDR'] . $ua);
    }


}
