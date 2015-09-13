<?php
namespace App\Web\Helper;
/**
 * 房源
 * @author lishengyou
 * 最后修改时间 2015年4月1日 下午4:48:56
 *
 */
class House{
    /**
     * 查房源信息
     * @author too|编写注释时间 2015年5月11日 上午10:03:09
     */
    public function getInfo($param)
    {
        $model = new \Common\Model\Erp\House();
        return $model->getOne(array('community_id'=>$param['0'],
                                    'cost'=>$param['1'],
                                    'unit'=>$param['2'],
                                    'floor'=>$param['3'],
                                    'number'=>$param['4']
        ));
    }

}