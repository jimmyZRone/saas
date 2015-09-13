<?php
namespace App\Web\Helper;
use Common\Model\Erp\Tenant;
class Evaluation extends Tenant{
    /**
     * 用身份证号码获取该用户的平均平均得分
     * @author too|最后修改时间 2015年4月30日 上午10:14:11
     */
    public function getAvgScores($idcard,$cid=0){
        $evaluate = new \Common\Model\Erp\Evaluate();
        $sql = $evaluate->getSqlObject();
        $select = $sql->select(array('e'=>$evaluate->getTableName('evaluate')));
        $select->columns(array('score'));
        $select->join(array('r'=>'rental'),'r.rental_id = e.rental_id',array(),'left');
        $select->join(array('cr'=>'contract_rental'),'cr.contract_id = r.contract_id',array(),'left');
        $select->join(array('t'=>'tenant'),'t.tenant_id = cr.tenant_id',array('name','phone'),'left');
        $where = new \Zend\Db\Sql\Where();//造where条件对象
        //if ($cid>0){
        //	$where->equalTo('t.company_id', $cid);
        //}
        $where->equalTo('t.is_delete', 0);
        $where->equalTo('t.idcard', $idcard);
        $select->where($where);
        $data = $select->execute();
        $sum = 0;
        $dataa = array();
        if (!empty($data)){
        	foreach ($data as $v){
        		$sum += $v['score'];
        	}
        	$dataa['name'] = $data[0]['name'];
        	$dataa['phone'] = $data[0]['phone'];
        	$dataa['allComment'] = count($data);
        	$dataa['avgscore'] = (double)number_format($sum/$dataa['allComment'],2);
        }
        return $dataa;
    }
    /**
     * 身份证号 取所有评论 带分页
     * @param type $idcard
     * @param type $cid
     * @return type
     * @author tuhong
     */
    public function getComment($idcard,$cid,$page,$size){
        $evaluate = new \Common\Model\Erp\Evaluate();
        $sql = $evaluate->getSqlObject();
        $select = $sql->select(array('e'=>$evaluate->getTableName('evaluate')));
        //$select->columns(array('score'));
        $select->join(array('r'=>'rental'),'r.rental_id = e.rental_id',array(),'left');
        $select->join(array('cr' => 'contract_rental'), 'cr.contract_id = r.contract_id', array(), 'left');
        $select->join(array('t'=>'tenant'),'t.tenant_id = cr.tenant_id',array('name','phone','idcard'),'left');
        $select->join(array('u'=>'user'), 'u.user_id = e.user_id',array('username'),'left');
        $where = new \Zend\Db\Sql\Where();//造where条件对象
        //$where->equalTo('t.company_id', $cid);
        $where->equalTo('t.is_delete', 0);
        $where->equalTo('t.idcard', $idcard);
        $select->where($where)->order("e.create_time desc");
        $data = $select::pageSelect($select,null, $page, $size);
        /* foreach($data['data'] as $v){
            $data[$v]['create_time'] = 'aa';//date('Y-m-d',$v['create_time']);
        } */
        return $data;
    }
}