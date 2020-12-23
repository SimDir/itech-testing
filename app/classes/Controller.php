<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

/**
 * Description of Controller
 *
 * @author Ivan P Kolotilkin
 */
abstract class Controller {

    public $GET = FALSE;
    public $POST = FALSE;
    public $REQUEST_METHOD = FALSE;

    public function __construct($param=null) {
        $this->REQUEST_METHOD = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_ENCODED);
        switch ($this->REQUEST_METHOD) {
            case 'GET':
                $this->GET = TRUE;
                break;
            case 'POST':
                $this->POST = TRUE;
                break;
        }
        return $this;
    }

    public function __get(string $name) {
        switch ($name) {
            case 'VIEW':
            case 'View':
            case 'view':
                return View::getInstance(end(explode('\\', get_class($this))));
            case 'REQUEST':
            case 'Request':
            case 'request':
                return json_decode(file_get_contents('php://input'), true);
            default:
                return null;
        }
    }
    public function __call($name, $arguments) {
        // Замечание: значение $name регистрозависимо.
        echo "Вызов метода '$name' "
        . implode(', ', $arguments) . "\n";
    }

    public function IndexAction(){return end(explode('\\', get_class($this)));}

}
