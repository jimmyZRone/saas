<?php
namespace Common\Model\Erp;
use Zend\Db\Sql\Expression;
class SerialStrikeBalance extends \Common\Model\Erp
{
    /**
     * 查询出所有的冲账数据
     * 修改时间 2015年5月13日19:31:47
     * 
     * @author ft
     */
    public function getAllStrikeData($serial_id_arr, $list_info = 0) {
        $sql = $this->getSqlObject();
        $select = $sql->select(array('ssb' => 'serial_strike_balance'));
        if ($list_info == 1) {
            $select->columns(array('serial_id', 'ssb_money' => 'money'));
            $where = new \Zend\Db\Sql\Where();
            $where->in('serial_id', $serial_id_arr);
        } else {
            $select->columns(array('serial_detail_id' => 'serial_detail_id', 'money' => 'money'));
            $where = new \Zend\Db\Sql\Where();
            $where->in('serial_detail_id', $serial_id_arr);
        }
        $select->where($where);
        return $select->execute();
    }
}