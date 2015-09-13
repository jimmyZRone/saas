<?php
namespace App\Web\Helper;
class PublicTools{
    /**
     * 多维数组转一维数组
     * @author too|最后修改时间 2015年4月21日 下午3:13:50
     */
    public function arrtostr($param){
        $data = array();
        foreach ($param as $k=>$v){
            if(is_array($v)){
                $data[$k] = $this->arrtostr($v);
            }
            $data = $v;
        }
        return $data;
    }
}