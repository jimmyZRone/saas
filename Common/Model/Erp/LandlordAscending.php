<?php
namespace Common\Model\Erp;
/**
 * 金额递增表
 * @author too|编写注释时间 2015年5月8日 下午4:48:03
 */
class LandlordAscending extends \Common\Model\Erp
{
    /**
     * 插入递增数据
     * @author too|编写注释时间 2015年5月8日 下午7:01:34
     */
    public function add($data)
    {
        return $this->insert($data);
    }
    /**
     * 取所有
     * @author too|编写注释时间 2015年5月19日 下午3:33:27
     */
    public function getAll($id)
    {
        return $this->getData(array('contract_id'=>$id));
    }
}