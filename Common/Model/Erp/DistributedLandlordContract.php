<?php
namespace Common\Model\Erp;
/**
 * 分散式业主合同附加信息
 * @author too|编写注释时间 2015年5月8日 下午2:41:31
 */
class DistributedLandlordContract extends \Common\Model\Erp
{
    /**
     * 写入扩展信息
     * @author too|编写注释时间 2015年5月8日 下午4:40:56
     */
    public function add($data)
    {
        return $this->insert($data);
    }
    /**
     * 取出一条
     * @author too|编写注释时间 2015年5月19日 下午3:09:17
     */
    public function get($id)
    {
        return $this->getOne(array('contract_id'=>$id));
    }
}