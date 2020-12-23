<?php

declare(strict_types=1);

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

/**
 * Класс для работы с HTML шаблонами
 * 
 * view
 * 
 */
class View {

    private $vars = array();
    private static $instance;
    public $TplDir = '';

    /**
     * входной параметр устанавливает спецефическую дирикторию с шаблонами
     * если не задать то установится дириктория по умолчанию
     * 
     * TEMPLATE_DIR смотрите обявление в index.php в корен сайта
     *
     * @param string $TplDir Строка дириктории с шаблонами
     */
    public static function getInstance(string $TplDir = ''): View {
//        dd($TplDir);
        if (self::$instance === null) {
            self::$instance = new self($TplDir);
        }
        self::$instance->SetWivePath(TEMPLATE_DIR . $TplDir .DS);
//        dd(self::$instance->TplDir);
        return self::$instance;
    }

    public function __construct($TplDir) {
        $this->vars['headcssjs'] = '';
        $this->vars['bodycssjs'] = '';
//        $this->TplDir = TEMPLATE_DIR.$TplDir.DS;
//        return $this;
    }
    /**
     * магический метод аналог метода execute()
     * 
     * @param string $val Строка шаблона который подключится
     * @param string $TplDir Строка дириктории с шаблонами
     */
    public function __invoke(string $val, $TplDir = false): string {
        return $this->execute($val, $TplDir);
    }

    public function __set(string $name, $value) {
        $this->vars[$name] = $value;
    }

    public function __get(string $name) {
        if (isset($this->vars[$name])) {

            return $this->vars[$name];
        }
        return FALSE;
    }

    public function __isset(string $name): bool {
        if (isset($this->vars[$name]) && !empty($this->vars[$name])) {
            return true;
        }
        return false;
    }

    /**
     * Добавляет CSS к странице
     * 
     * @param string $stylesheet полный относительный сайта путь к стилю либо путь до стороннего сервера
     */
    public function AddCss(string $stylesheet) {
        $this->vars['headcssjs'] .= "<link rel=\"stylesheet\" href=\"$stylesheet\">" . PHP_EOL;
    }

    /**
     * Добавляет JavaScript к странице
     * 
     * @param string $stylesheet полный путь к подсклюяаемому скрипту
     * @param boolean $OnTop по умолчанию true определяет в каком месте подключить скрипт. 
     * 
     * либо в начале странице в хедаре либо в низу странице
     */
    public function AddJs(string $stylesheet, bool $OnTop = true) {
        if ($OnTop) {
            $this->vars['headcssjs'] .= "<script src=\"$stylesheet\"></script>" . PHP_EOL;
        } else {
            $this->vars['bodycssjs'] .= "<script src=\"$stylesheet\"></script>" . PHP_EOL;
        }
    }

    /**
     * получаем значение переменно которую мы добавили шаблонизатору
     * 
     * @param string $name имя необходимой переменной
     */
    public function VarGet(string$name) {
        if (isset($this->vars[$name])) {

            return $this->vars[$name];
        }
        return FALSE;
    }

    /**
     * Устанавливаем массив переменных шаблонизатору
     * дальее шаблонизатор будет с ними работать
     * 
     * @param array $Array имя необходимой переменной
     */
    public function VarSetArray(array $Array) {
        if (is_array($Array)) {
            foreach ($Array as $key => $value) {
                $this->vars[$key] = $value;
            }
        }
    }

    public function assign(string $name, string $value) {
        if (isset($this->vars[$name]) && is_array($this->vars[$name])) {
            $this->vars[$name] = array_merge($this->vars[$name], (array) $value);
        } else {
            $this->vars[$name] = $value;
        }
    }

    /**
     * дописать регулярок!!!!!
     */
    private function compress(&$code): string {
        //,'#/\*(?:[^*]*(?:\*(?!/))*)*\*/#','/[\s]+/' ,'/\/\/(.*)[\r\n]/'
        return preg_replace(array('/<!--(.*)-->/Uis', '#/\*(?:[^*]*(?:\*(?!/))*)*\*/#'), '', $code); // '/<!--(.*)-->/Uis','\<![ \r\n\t]*(--([^\-]|[\r\n]|-[^\-])*--[ \r\n\t]*)\>','/[\s]+/'  |,'#/\*(?:[^*]*(?:\*(?!/))*)*\*/#'|
    }
    public function Render(string $template='index.html', $TplDir = false) {
        return $this->execute($template, $TplDir);
    }
    /**
     * подключает необходимый шаблон к шаблонизатору
     * после шаблонизатор будет этот шаблон обрабатывать
     * 
     * TEMPLATE_DIR смотрите обявление в index.php в корен сайта
     * 
     * @param string $template имя необходимого шаблона
     * @param string $TplDir каталог в котором будет искатся сам шаблон. по умелчанию каталог для поиска TEMPLATE_DIR
     */
    public function execute(string $template='index.html', $TplDir = false): string {
        $OldTpl=$this->TplDir;
        if ($TplDir) {
            $this->TplDir = $TplDir;
        }
        if (!file_exists($this->TplDir . $template) or is_dir($this->TplDir . $template)) {
            $code = '<p><b>Error: </b>' . __METHOD__ . "('$template')</p>Нет файла <strong>$template</strong> для подключения в <b>$this->TplDir</b>";
            return $code;
        }
        ob_start();
        include $this->TplDir . $template;
        $code = ob_get_contents();
        ob_end_clean();
//        return $code;
        $code = $this->Code($code);
        $code = $this->compress($code);
//        echo $code;
        if ($TplDir) {
            $this->TplDir = $OldTpl;
        }
        return $code;
    }

    /**
     * основная функция шаблонизатора он и занимается всей магией шаблонизации и обработки шаблонов
     * на вход подается HTML текст в функциями шаблонизации
     * на выходе обработанный HTML
     * @param string $code HTML текст который подлежит обработке
     */
    public function Code(&$code): string {
        
        if (preg_match("/<style[^>]*?>(.*?)<\/style>/si", $code, $matchescss)) {
            $code = str_replace(trim($matchescss[0]), '', $code);
                /* удалить комментарии */
            $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $matchescss[0]);
            /* удалить табуляции, пробелы, символы новой строки и т.д. */
            $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
            $this->vars['headcssjs'] .= $buffer . PHP_EOL;
//            die($code);
        }
        
        preg_match_all('/<{(.*?)}>/', $code, $varibles, PREG_SET_ORDER);

        foreach ($varibles as $value) {

            if (preg_match("/Controller(\(.*\))/i", $value[1], $matches)) {
//                dd($matches);
                $ControllerAction = trim($matches[1], '()');
                $ArrCtrlAct = explode(':', $ControllerAction);
                $code = str_replace($value[0], mvcrb::Exec(ucfirst($ArrCtrlAct[0]) . 'Controller', ucfirst($ArrCtrlAct[1]) . 'Action'), $code);
            } elseif (preg_match("/view(\(.*\))/i", $value[1], $matches)) {
                $ViewHtml = trim($matches[1], '()');
                $ViewHtml = trim($ViewHtml);
                $tmpDirView = $this->TplDir;
                $this->TplDir = TEMPLATE_DIR;
                $code = str_replace($value[0], $this->execute($ViewHtml), $code);
                $this->TplDir = $tmpDirView;
            } elseif (preg_match("/Addjs(\(.*\))/i", $value[1], $matches)) {
                $code = str_replace($value[0], '', $code);
                $this->AddJs(trim($matches[1], '()'), false);
            } elseif (preg_match("/Addcss(\(.*\))/i", $value[1], $matches)) {
                $code = str_replace($value[0], '', $code);
                $this->AddCss(trim($matches[1], '()'));
            } elseif (preg_match("/Title(\(.*\))/i", $value[1], $matches)) {
                $code = str_replace($value[0], '', $code);
                $this->title = trim($matches[1], '()');
            } else {
                $tplVar = $this->VarGet(trim($value[1], ' '));
                $code = preg_replace("/<{($value[1])}>/", $tplVar, $code);
            }
        }
        


        return $code;
    }

    public function SetWivePath($path) {
        $this->TplDir = $path;
    }

}
