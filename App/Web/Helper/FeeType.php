<?php
namespace App\Web\Helper;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
class FeeType{
    /**
     *
     * @param int $cid 公司id
     * @return 一维or多维数组
     *
     * @author too
     * 最后修改时间 2015年4月17日 上午10:40:07
     */
    public function getFeeType($cid){
        $model = new \Common\Model\Erp\FeeType();
        $sql = $model->getSqlObject();
        $select = $sql->select(array('rf'=>$model->getTableName('fee_type')));
        $where = new \Zend\Db\Sql\Where();//造where条件对象
        $where->equalTo('company_id', $cid);//
        $select->where($where);//把条件传进对象
        $data = $select->execute();//执行咯
        return $data;
    }
}