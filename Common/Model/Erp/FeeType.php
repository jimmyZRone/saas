<?php
namespace Common\Model\Erp;

use Zend\Db\Sql\Expression;
class FeeType extends \Common\Model\Erp
{
    /**
     * 费用类型管理  添加 /修改
     * 修改时间 2015年4月22日16:18:55
     * 
     * @author ft
     * @param array $data
     * @return boolean
     */
	public function addFeeType($data) {
	    $this->Transaction();
	    foreach ($data as $fee_info) {
	        if (!isset($fee_info['fee_type_id'])) {  //新增
        	    $new_fee_type_id = $this->insert($fee_info);
        	    if (!$new_fee_type_id) {
        	        $this->rollback();
        	        return false;
        	    }
	        } else {    //修改
	            $fee_type_id = array_shift($fee_info);
                $where = array(
                        'fee_type_id' => $fee_type_id,
                        'sys_type_id' => 0,
                );
	            $res = $this->edit($where, $fee_info);
	            if (!$res) {
	                $this->rollback();
	                return false;
	            }
	        }
	    }
	    if (isset($new_fee_type_id) && $new_fee_type_id) {  //新增
	        $this->commit();
	        return true;
	    }
	    if (isset($res) && $res) {    //修改
	        $this->commit();
	        return true;
	    }
	}
	
	/**
	 * 费用类型管理  删除
	 * 修改时间 2015年5月4日14:33:06
	 * 
	 * @author ft
	 */
	public function deleteFeeType($fee_type_id, $cid) {
	    $this->Transaction();
	    if ($fee_type_id && $cid) {
	        $where = array('fee_type_id' => $fee_type_id, 'company_id' => $cid,'sys_type_id'=>0);
	        $data = array('is_delete' => 1);
	       $res = $this->edit($where, $data);
	       if (!$res) {
	           $this->rollback();
	           return false;
	       }
	       $this->commit();
	       return true;
	    }
	}
	
    /**
     * 取多条信息
     * @param type $id
     */
    public function getManyFeeType($id)
    {
        return $this->getData(array('fee_type_id'=>$id));
    }
    /**
     * 根据费用类型id和公司id获取费用类型信息
     * 修改时间  2015年6月15日16:29:27
     * 
     * @author  ft
     */
    public function getFeeTypeById($ftype_where) {
        $sql = $this->getSqlObject();
        $select = $sql->select(array('ft' => 'fee_type'));
        $select->columns(array('fee_type_id' => 'fee_type_id', 'type_name' => 'type_name'));
        $where = new \Zend\Db\Sql\Where();
        $where->in('fee_type_id', $ftype_where['fee_type_id']);
        $where->equalTo('company_id', $ftype_where['company_id']);
        $select->where($where);
        return $select->execute();
    }
}