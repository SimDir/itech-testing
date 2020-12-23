<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

/**
 * Description of FileSessionHandler
 *
 * @author Ivan Kolotilkin
 * 
 */
class FileSessionHandler {

    private $savePath;

    function open($savePath, $sessionName) {
        $this->savePath = $savePath;
        if (!is_dir($this->savePath)) {
            $ret = mkdir($this->savePath, 0755);
        }
        return true;
    }

    function close() {
        return true;
    }

    function read($id) {
        $sesFile = "$this->savePath/sess_$id";
        if (file_exists($sesFile)) {
            $data = core::StrDecrypt(file_get_contents($sesFile), core::BrouserHash());
            if($data === false){
                unlink($sesFile);
                return '';
            }
            return $data;
        }
        return '';
    }

    function write($id, $data) {
        $DataCrypt = core::StrEncrypt($data, core::BrouserHash());
        return file_put_contents("$this->savePath/sess_$id", $DataCrypt) === false ? false : true;
    }

    function destroy($id) {
        $file = "$this->savePath/sess_$id";
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    function gc($maxlifetime) {
        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }
        return true;
    }

}
