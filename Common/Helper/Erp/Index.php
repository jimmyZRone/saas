<?php

    namespace Common\Helper\Erp;

    /**
     * 财务类型
     * @author lishengyou
     * 最后修改时间 2015年3月26日 下午1:31:28
     *
     */
    class Index extends \Core\Object
    {

        /**
         * 获取当月的所有待办事项
         * 修改时间 2015年5月21日11:26:32
         * 
         * @author ft
         */
        public function getCurrentMonthBacklog($current_first_day , $current_last_day , $cid , $user = array())
        {


            $index_modle = new \Common\Model\Erp\Index();
            $sql = $index_modle->getSqlObject();
            $select = $sql->select(array('td' => 'todo'));
            $select->columns(array('deal_time' => 'deal_time' , 'title' => 'title' , 'module' , 'content' => 'content' , 'url' => 'url' , 'entity_id' => 'entity_id' , 'todo_id'));
            $where = new \Zend\Db\Sql\Where();
            $where->greaterThanOrEqualTo('td.deal_time' , $current_first_day);
            $where->lessThanOrEqualTo('td.deal_time' , $current_last_day);
            $where->equalTo('td.company_id' , $cid);
            
            $select->join(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('td.house_id > 0 and td.house_id = h.house_id') , array('community_id') , $select::JOIN_LEFT);
            $select->join(array('community' => 'community') , new \Zend\Db\Sql\Predicate\Expression('td.house_id > 0 and h.community_id = community.community_id') , array('house_city_id'=>'city_id') , $select::JOIN_LEFT);
            $select->join(array('f' => 'flat') , new \Zend\Db\Sql\Predicate\Expression('td.flat_id > 0 and td.flat_id = f.flat_id') , array('flat_city_id'=>'city_id') , $select::JOIN_LEFT);
            $select->join(array('lc' => 'landlord_contract') , new \Zend\Db\Sql\Predicate\Expression("td.entity_id = lc.contract_id and td.`module` in ('landlord_contract','landlord_contract_jiaozu')") , array('lc_city_id'=>'city_id') , $select::JOIN_LEFT);
            
        //权限=============
            if (!empty($user) && $user['is_manager'] == 0)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $tables = array();
                $houseJoin = function($table) use ($select , &$tables) {
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=h.house_id and td.house_id > 0");
                    return array($join , $select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select , $houseJoin , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select , &$tables) {
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=td.flat_id and td.flat_id > 0");
                    return array($join , $select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select , $flatJoin , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);

                $and = new \Zend\Db\Sql\Where();

                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $or->greaterThan('td.house_id' , 0);
                $and->orPredicate($or);

                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $or->greaterThan('td.flat_id' , 0);
                $and->orPredicate($or);

                $or = new \Zend\Db\Sql\Where();
                $or->equalTo('td.flat_id' , 0);
                $or->equalTo('td.house_id' , 0);
                $or->equalTo('td.create_uid' , $user['user_id']);
                $and->orPredicate($or);

                $where->andPredicate($and);
            }
            
            $swhere = new \Zend\Db\Sql\Where();
            $swhere->equalTo('td.status' , 0);
            $swhere2 = new \Zend\Db\Sql\Where();
            $swhere2->equalTo('td.status' , 1);
            $swhere->addPredicate($swhere2 , $swhere::OP_OR);
            $where->addPredicate($swhere , $where::OP_AND);

            //LMS 修改与2015年9月1日 09:56:42 
            $city_id = $user['city_id'];
            
            $and = new \Zend\Db\Sql\Where();
            $and->equalTo('f.city_id', $city_id);
            $or = new \Zend\Db\Sql\Where();
            $or->equalTo('community.city_id', $city_id);
            $and->orPredicate($or);
            $or = new \Zend\Db\Sql\Where();
            $or->equalTo('lc.city_id', $city_id);
            $and->orPredicate($or);
            $where->andPredicate($and);
            $select->where($where);
            
//             //筛选分散式房源的城市
//             $sql1 = "(house_id >0  AND  house_id IN (SELECT  `house_id` FROM  `house` AS h LEFT JOIN `community` AS c ON c.community_id=h.community_id WHERE c.city_id='$city_id') )";
//             //筛选公寓的城市
//             $sql2 = "(flat_id >0  AND  flat_id IN (SELECT  `flat_id` FROM  flat WHERE city_id='$city_id') )";
//             //筛选业主合同的城市
//             $sql3 = "(house_id =0  AND  flat_id=0 AND entity_id  IN (SELECT  `contract_id` FROM  `landlord_contract` WHERE city_id='$city_id') )";
//             $where->expression("($sql1 OR $sql2 OR $sql3)" , array());
            $select->where($where);
            return $select->execute();
        }

    }
    