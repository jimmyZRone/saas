<?php

    namespace Common\Model\Erp;

    use Zend\Db\Sql\Expression;

    class Index extends \Common\Model\Erp
    {

        /**
         * 获取总的待办提醒
         * 修改时间 2015年5月21日17:31:46
         *
         * @author ft
         */
        public function getTotalBacklog($cid , $user = array())
        {
            //所有todo
            $sql = $this->getSqlObject();
            $select = $sql->select(array('td' => 'todo'));
            $select->columns(array('deal_time' => 'deal_time' , 'title' => 'title' , 'content' => 'content' , 'url' => 'url' , 'entity_id' => 'entity_id'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('td.company_id' , $cid);
            $swhere = new \Zend\Db\Sql\Where();
            $swhere->equalTo('td.status' , 0);
            $swhere2 = new \Zend\Db\Sql\Where();
            $swhere2->equalTo('td.status' , 1);
            $swhere->addPredicate($swhere2 , $swhere::OP_OR);
            $where->addPredicate($swhere , $where::OP_AND);
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

            //LMS 修改与2015年9月1日 09:56:42 
            $city_id = $user['city_id'];
//             //筛选分散式房源的城市
//             $sql1 = "(house_id >0  AND  house_id IN (SELECT  `house_id` FROM  `house` AS h LEFT JOIN `community` AS c ON c.community_id=h.community_id WHERE c.city_id='$city_id') )";
//             //筛选公寓的城市
//             $sql2 = "(flat_id >0  AND  flat_id IN (SELECT  `flat_id` FROM  flat WHERE city_id='$city_id') )";
//             //筛选业主合同的城市
//             $sql3 = "(house_id =0  AND  flat_id=0 AND entity_id  IN (SELECT  `contract_id` FROM  `landlord_contract` WHERE city_id='$city_id') )";
//             $where->expression("($sql1 OR $sql2 OR $sql3)");
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
            $select->order(
                    [new \Zend\Db\Sql\Predicate\Expression("td.title='交租',td.title='收租',td.title='到期',td.deal_time ASC")]
            );


//        echo $select->getSqlString();die();
            return $select->execute();
        }

        /**
         * 获取代办提醒总数
         * 修改时间 2015年5月25日09:55:53
         *
         * @author ft
         */
        public function backlogTotal($company_id , $user = array())
        {
            //todo总数
            $sql = $this->getSqlObject();
            $total_select = $sql->select(array('td' => 'todo'));
            $total_select->columns(array('total_todo' => new Expression('count(todo_id)')));
            $total_where = new \Zend\Db\Sql\Where();
            $total_where->equalTo('td.company_id' , $company_id);
            $stotal_where = new \Zend\Db\Sql\Where();
            $stotal_where->equalTo('td.status' , 0);
            $stotal_where2 = new \Zend\Db\Sql\Where();
            $stotal_where2->equalTo('td.status' , 1);
            
            $total_select->join(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('td.house_id > 0 and td.house_id = h.house_id') , array('community_id') , $total_select::JOIN_LEFT);
            $total_select->join(array('community' => 'community') , new \Zend\Db\Sql\Predicate\Expression('td.house_id > 0 and h.community_id = community.community_id') , array('house_city_id'=>'city_id') , $total_select::JOIN_LEFT);
            $total_select->join(array('f' => 'flat') , new \Zend\Db\Sql\Predicate\Expression('td.flat_id > 0 and td.flat_id = f.flat_id') , array('flat_city_id'=>'city_id') , $total_select::JOIN_LEFT);
            $total_select->join(array('lc' => 'landlord_contract') , new \Zend\Db\Sql\Predicate\Expression("td.entity_id = lc.contract_id and td.`module` in ('landlord_contract','landlord_contract_jiaozu')") , array('lc_city_id'=>'city_id') , $total_select::JOIN_LEFT);
            
            if ($user['is_manager'] == 0)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $tables = array();
                $houseJoin = function($table) use ($total_select , &$tables) {
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=h.house_id and td.house_id > 0");
                    return array($join , $total_select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($total_select , $houseJoin , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($total_select , &$tables) {
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=td.flat_id and td.flat_id > 0");
                    return array($join , $total_select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($total_select , $flatJoin , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);

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

                $stotal_where->andPredicate($and);
            }
            
            //LMS 修改与2015年9月1日 09:56:42
            $city_id = $user['city_id'];
            //             //筛选分散式房源的城市
            //             $sql1 = "(house_id >0  AND  house_id IN (SELECT  `house_id` FROM  `house` AS h LEFT JOIN `community` AS c ON c.community_id=h.community_id WHERE c.city_id='$city_id') )";
            //             //筛选公寓的城市
            //             $sql2 = "(flat_id >0  AND  flat_id IN (SELECT  `flat_id` FROM  flat WHERE city_id='$city_id') )";
            //             //筛选业主合同的城市
            //             $sql3 = "(house_id =0  AND  flat_id=0 AND entity_id  IN (SELECT  `contract_id` FROM  `landlord_contract` WHERE city_id='$city_id') )";
            //             $where->expression("($sql1 OR $sql2 OR $sql3)");
            $and = new \Zend\Db\Sql\Where();
            $and->equalTo('f.city_id', $city_id);
            $or = new \Zend\Db\Sql\Where();
            $or->equalTo('community.city_id', $city_id);
            $and->orPredicate($or);
            $or = new \Zend\Db\Sql\Where();
            $or->equalTo('lc.city_id', $city_id);
            $and->orPredicate($or);
            $stotal_where->andPredicate($and);
            
            $stotal_where->addPredicate($stotal_where2 , $stotal_where::OP_OR);
            $total_where->addPredicate($stotal_where , $total_where::OP_AND);
            $total_select->where($total_where);
            $total_backlog = $total_select->execute();

            //备忘录总数
            $memo_select = $sql->select(array('m' => 'memo'));
            $memo_select->columns(array('totla_memo' => new Expression('count(memo_id)')));
            $memo_where = new \Zend\Db\Sql\Where();
            $memo_where->equalTo('m.is_notice' , 1);
            $memo_where->equalTo('m.create_uid' , $user['user_id']);
            $memo_select->where($memo_where);
            $total_memo = $memo_select->execute();
            $total_data = $total_backlog[0]['total_todo'] + $total_memo[0]['totla_memo'];
            return $total_data;
        }

    }
    