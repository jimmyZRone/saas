<?php
namespace Common\Model\Erp;
class Landlord extends \Common\Model\Erp
{
    /**
     * 通过业主id获取业主信息
     * @author too|编写注释时间 2015年5月8日 下午3:24:12
     */
    public function getLandlord($lid,$cid)
    {
        $data = $this->getOne(array("idcard"=>$lid,'is_delete'=>0,'company_id'=>$cid));
        return $data;
    }
}