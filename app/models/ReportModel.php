<?php

namespace mocrm;

defined('ROOT') OR die('No direct script access.');

/**
 * Description of PageModel
 *
 * @author Ivan Kolotilkin
 */
class ReportModel extends Model {

    public function GetAllReport($Data = null) {

        $tempbean = $this->GetAll('SELECT alias,name,lastname,thems,company FROM ' . $this->TableName . ' WHERE `reporter`!=0 ORDER BY createdatetime LIMIT 25');

//        dd($tempbean);
        if ($tempbean) {
            return $tempbean; //$this->exportAll($tempbean, TRUE);
        }
        return FALSE;
    }

    public function GetList($data = null) {
        if (!$data)
            return FALSE;
        $start = $data['start'] ? intval($data['start']) : 0;
        $limit = $data['limit'] ? intval($data['limit']) : 10;
        $List['TableName'] = $this->TableName;
        $List['count'] = $this->count($this->TableName);
        if (isset($data['orderby']) and $data['orderby'] != '') {
            $order['orderby'] = $data['orderby'];
            if ($data['dir'] != '') {
                $order['dir'] = $data['dir'];
            } else {
                $order['dir'] = 'ASC';
            }
        } else {
            $order = null;
        }

        if (is_array($order)) {
            $tempbean = $this->findAll($this->TableName, 'ORDER BY ' . $order['orderby'] . ' ' . $order['dir'] . ' LIMIT ' . $start . ', ' . $limit);
        } else {
            $tempbean = $this->findAll($this->TableName, 'LIMIT ' . $start . ', ' . $limit);
        }
        if ($tempbean) {
            $List['data'] = $tempbean;
            return $List;
        }
        return FALSE;
    }

    public function AddReport($Data = null) {
        if (is_null($Data))
            return false;
        $Table = $this->Dispense($this->TableName);
        $Table->import($Data);
        $Table->createdatetime = date('Y-m-d H:i:s');
        $Table->editdatetime = date('Y-m-d H:i:s');
        return $this->store($Table);
    }

    public function DelReport($id = null) {
        if (is_null($id))
            return false;
        $Page = $this->load($this->TableName, $id);
        return $this->trash($Page);
    }

    public function EditReport($Data = null, $id = null) {
        if (is_null($Data))
            return false;
//        $Table = $this->Dispense($this->TableName);
        $Table = $this->findOne($this->TableName, 'id = ?', array($id));
        $Table->import($Data);
        $Table->editdatetime = date('Y-m-d H:i:s');
        return $this->store($Table);
    }

    public function GetReportByName($name = '') {
        $Ret = $this->findOne($this->TableName, '(alias = :alias)', [':alias' => $name]);
        if ($Ret) {
            return $Ret->export();
        }
        return FALSE;
    }

}
