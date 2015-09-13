<?php

    namespace Common\Helper\Erp;

    class Area extends \Core\Object
    {

        /**
          获取城市地区列表
         * 
         * @author yzx
         * @param string $phone
         * @param string $idcard
         * @return unknown
         */
        public function getAreaList($city_id)
        {

            $area = new \Common\Model\Erp\Area();
            $result = $area->getData(array('city_id' => $city_id) , array('area_id' , 'name'));
            return $result;
        }
        /**
         * 取当前公司有发布过房源的区域
         * @author lishengyou
         * 最后修改时间 2015年6月15日 下午5:14:23
         *
         * @param int $city_id
         * @param int $company_id
         */
		public function getCompanyList($city_id,$company_id){
			$area = new \Common\Model\Erp\Area();
			$sql = $area->getSqlObject();
			$select = $sql->select(array('a'=>'area'));
			$select->where(array('a.city_id'=>$city_id,'h.company_id'=>$company_id,'h.is_delete'=>0));
			$select->join(array('c'=>'community'), 'c.area_id=a.area_id','community_id');
			$select->join(array('h'=>'house'), 'h.community_id=c.community_id','house_id');
			$select->group('a.area_id');
			return $select->execute();
		}
    }
    