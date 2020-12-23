<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

/**
 * Description of PageModel
 *
 * @author Ivan Kolotilkin
 */
class ReportModel extends Model {


    public function GetAll($Data = null) {

        $tempbean = $this->getAll('SELECT name,title,createdatetime,type FROM ' .$this->TableName . ' ORDER BY createdatetime' );
        
//        dd($tempbean);
        if ($tempbean) {
            return $tempbean;//$this->exportAll($tempbean, TRUE);

        }
        return FALSE;
    }
    public function Add($Data = null) {
        if (is_null($Data))
            return false;
        $Table = $this->Dispense($this->TableName);
        $Table->import($Data);
        $Table->createdatetime = date('Y-m-d H:i:s');
        $Table->editdatetime = date('Y-m-d H:i:s');
        return $this->store($Table);
    }
    public function Del($id = null) {
        if (is_null($id))
            return false;
        $Page = $this->load($this->TableName, $id);
        return $this->trash($Page);
    }
    public function Edit($Data = null, $id) {
        if (is_null($Data))
            return false;
//        $Table = $this->Dispense($this->TableName);
        $Table = $this->findOne($this->TableName, 'id = ?', array($id));
        $Table->import($Data);
        $Table->editdatetime = date('Y-m-d H:i:s');
        return $this->store($Table);
    }

    public function GetByName($name = '') {
        $Ret = $this->findOne($this->TableName, '(name = :name)', [':name' => $name]);
        if ($Ret) {
            return $Ret->export();
        }
        return FALSE;
    }

}
