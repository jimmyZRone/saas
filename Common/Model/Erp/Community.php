<?php

    namespace Common\Model\Erp;

    /**
     * 小区
     * @author lishengyou
     * 最后修改时间 2015年4月1日 下午4:42:52
     *
     */
    class Community extends \Common\Model\Erp
    {

        /**
         * 获取小区信息
         * @param $comname int 小区id
         * @author too|编写注释时间 2015年5月9日 下午5:42:15
         */
        public function getinfo($comname)
        {
            return $this->getOne(array('community_name' => $comname));
        }

        public function getCommunityHouse($company_id , $community_id)
        {
            $H = new \Common\Model\Erp\House();
            $result = $H->getData(array('community_id' => $community_id , 'company_id' => $company_id , 'is_delete' => 0));
            $result = is_array($result) ? $result : array();
           
            return $result;
        }

    }
    