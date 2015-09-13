<?php

    namespace Common\Helper\Erp;

    class City extends \Core\Object
    {

        /**
         * 获取城市列表
         * 修改时间2015年5月30日 13:45:08
         *
         * @author yzx
         * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
         */
        public function getCityList($param = 0)
        {
            $cityModel = new \Common\Model\Erp\City();
            $sql = $cityModel->getSqlObject();
            $select = $sql->select("city");
            $select->columns(array('city_id' , 'shorthand' , 'name'));
            if(!empty($param))
            {//echo 'ss';
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('province_id', $param);
                $select->where($where);
            }
            //print_r(str_replace('"', '', $select->getSqlString()));
            $result = $select->execute();
            return $result;
        }


    }
