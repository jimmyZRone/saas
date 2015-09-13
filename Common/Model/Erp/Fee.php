<?php
namespace Common\Model\Erp;
class Fee extends \Common\Model\Erp
{
	//财务项
	/**
	 * 集中式房源  house_type=2 rental
	 * @var unknown
	 */
	const SOURCE_FOCUS = "SOURCE_FOCUS";
	/**
	 * 集中式公寓
	 * @var unknown
	 */
	const SOURCE_FLAT = "SOURCE_FLAT";
	/**
	 * 分散式房源
	 *
	 * house_type=1
	 * rental_way=2
	 * @var unknown
	 */
	const SOURCE_DISPERSE = "SOURCE_DISPERSE";
	/**
	 * 分散式房间 house_type=1 rental_way=1
	 * @var unknown
	 */
	const SOURCE_DISPERSE_ROOM = "SOURCE_DISPERSE_ROOM";
        /**
         * 取一条费用信息 ,带费用名
         * @param type $id
         * @return type
         */
        public function getFeeMany($type,$id)
        {
            $sql = $this->getSqlObject();
            $select = $sql->select(array('f'=>$this->getTableName('fee')));
            $select->columns(
                    array(
                            'fee_id' => 'fee_id', 
                            'fee_type_id' => 'fee_type_id', 
                            'payment_mode' => 'payment_mode', 
                            'money' => 'money',
                    ));
            $select->join(array('ft'=>'fee_type'),'f.fee_type_id = ft.fee_type_id',array('type_name'=>'type_name'),'left');
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('f.source_id', $id);
            $where->equalTo('f.source', $type);
            $where->equalTo('f.is_delete', 0);
            $select->order('fee_type_id ASC');
            $select->where($where);
	        //print_r(str_replace('"', '', $select->getSqlString()));
            $data = $select->execute();
            return $data;
        }
}