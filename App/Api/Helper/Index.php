<?php

    namespace App\Api\Helper;

    class Index extends \Common\Helper\Erp\LandlordContract
    {

        /**
         * 首页数据
         * 
         *  @author yusj | 最后修改时间 2015年5月8日上午11:32:13
         */
        public function index($user)
        {
            $day = I('day');
            $payrent = $this->payrent($user , $day);
            $rent = $this->rent($user , $day);

            $expire = $this->expire($user , $day);
            $total = $this->total($user);
            $disable = $this->disable($user);
            $notRent = $this->notRent($user);
            $hasRent = $this->hasRent($user);
            $reserve = $this->reserve($user);
            $rentReservation = $this->rentReservation($user);

            $data ['payrent'] = $payrent[0]['count'];
            $data ['rent'] = $rent[0]['count'];
            $data ['expire'] = $expire[0]['count'];

            $data ['total'] = $total;
            $data ['disable'] = $disable;
            $data ['not_rent'] = $notRent;
            $data ['has_rent'] = $hasRent;
            $data ['reserve'] = $reserve;
            $data ['rent_reservation'] = $rentReservation;

            return $data;
        }

        /**
         * 交租
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function payrent($user , $day = '')
        {
            $company_id = $user ['company_id'];
            $todo_model = new \Common\Model\Erp\Todo();
            $sql = $todo_model->getSqlObject();
            $select = $sql->select(array('todo' => 'todo'));
            $select->columns(array('count' => new \Zend\Db\Sql\Expression('count(*)')));
            
            $where = new \Zend\Db\Sql\Where();
            //权限=============
            if (!empty($user) && $user['is_manager'] == 0) {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $select->join(array('h'=>'house'),new \Zend\Db\Sql\Predicate\Expression('todo.house_id > 0 and todo.house_id = h.house_id'),array('community_id'),$select::JOIN_LEFT);
                $tables = array();
                $houseJoin = function($table) use ($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=h.house_id and todo.house_id > 0");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=todo.flat_id and todo.flat_id > 0");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            
                $and = new \Zend\Db\Sql\Where();
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $or->greaterThan('todo.house_id', 0);
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $or->greaterThan('todo.flat_id', 0);
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->equalTo('todo.flat_id', 0);
                $or->equalTo('todo.house_id', 0);
                $or->equalTo('todo.create_uid', $user['user_id']);
                $and->orPredicate($or);
                 
                $where->andPredicate($and);
            }
            
            if (empty($day))
                $today_start = strtotime('today');
            else
                $today_start = strtotime($day);
            $today_end = $today_start + 86400;
            $where->greaterThanOrEqualTo('todo.deal_time' , $today_start);
            $where->lessThan('todo.deal_time' , $today_end);
            $where->equalTo('todo.title' , '交租');
            $where->equalTo('todo.company_id' , $company_id);
            $where->in('todo.status' , array(0 , 1));
            if (!$user['is_manager'])
            {
                $where->equalTo("todo.create_uid" , $user['user_id']);
            }
            //$count = M('Todo')->getCount($where);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 收租
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function rent($user , $day = '')
        {
            $company_id = $user ['company_id'];
            $todo_model = new \Common\Model\Erp\Todo();
            $sql = $todo_model->getSqlObject();
            $select = $sql->select(array('todo' => 'todo'));
            $select->columns(array('count' => new \Zend\Db\Sql\Expression('count(*)')));
            $where = new \Zend\Db\Sql\Where();
            
            $where = new \Zend\Db\Sql\Where();
            //权限=============
            if (!empty($user) && $user['is_manager'] == 0) {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $select->join(array('h'=>'house'),new \Zend\Db\Sql\Predicate\Expression('todo.house_id > 0 and todo.house_id = h.house_id'),array('community_id'),$select::JOIN_LEFT);
                $tables = array();
                $houseJoin = function($table) use ($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=h.house_id and todo.house_id > 0");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=todo.flat_id and todo.flat_id > 0");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            
                $and = new \Zend\Db\Sql\Where();
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $or->greaterThan('todo.house_id', 0);
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $or->greaterThan('todo.flat_id', 0);
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->equalTo('todo.flat_id', 0);
                $or->equalTo('todo.house_id', 0);
                $or->equalTo('todo.create_uid', $user['user_id']);
                $and->orPredicate($or);
                 
                $where->andPredicate($and);
            }
            
            if (empty($day))
                $today_start = strtotime('today');
            else
                $today_start = strtotime($day);
            $today_end = $today_start + 86400;
            $where->greaterThanOrEqualTo('todo.deal_time' , $today_start);
            $where->lessThan('todo.deal_time' , $today_end);
            $where->equalTo('todo.title' , '收租');
            $where->equalTo('todo.company_id' , $company_id);
            $where->in('todo.status' , array(0 , 1));
            if (!$user['is_manager'])
            {
                $where->equalTo("todo.create_uid" , $user['user_id']);
            }
            $select->where($where);
            return $select->execute();
            //$count = M('Todo')->getCount($where);
        }

        /**
         * 到期
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function expire($user , $day = '')
        {
            $company_id = $user ['company_id'];
            $todo_model = new \Common\Model\Erp\Todo();
            $sql = $todo_model->getSqlObject();
            $select = $sql->select(array('todo' => 'todo'));
            $select->columns(array('count' => new \Zend\Db\Sql\Expression('count(*)')));
            $where = new \Zend\Db\Sql\Where();
            //权限=============
            if (!empty($user) && $user['is_manager'] == 0) {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $select->join(array('h'=>'house'),new \Zend\Db\Sql\Predicate\Expression('todo.house_id > 0 and todo.house_id = h.house_id'),array('community_id'),$select::JOIN_LEFT);
                $tables = array();
                $houseJoin = function($table) use ($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=h.house_id and todo.house_id > 0");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("`{$table}`.authenticatee_id=todo.flat_id and todo.flat_id > 0");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            
                $and = new \Zend\Db\Sql\Where();
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $or->greaterThan('todo.house_id', 0);
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $or->greaterThan('todo.flat_id', 0);
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->equalTo('todo.flat_id', 0);
                $or->equalTo('todo.house_id', 0);
                $or->equalTo('todo.create_uid', $user['user_id']);
                $and->orPredicate($or);
                 
                $where->andPredicate($and);
            }
            
            if (empty($day))
                $today_start = strtotime('today');
            else
                $today_start = strtotime($day);
            $today_end = $today_start + 86400;
            $where->greaterThanOrEqualTo('todo.deal_time' , $today_start);
            $where->lessThan('todo.deal_time' , $today_end);
            $where->equalTo('todo.title' , '到期');
            $where->equalTo('todo.company_id' , $company_id);
            $where->in('todo.status' , array(0 , 1));
            if (!$user['is_manager'])
            {
                $where->equalTo("todo.create_uid" , $user['user_id']);
            }
            $select->where($where);
            return $select->execute();
            //$count = M('Todo')->getCount($where);
        }

        /**
         * 总计
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function total($user)
        {
            $company_id = $user ['company_id'];
            //分散式
            $model = new \Common\Model\Erp\House ();
            $sql = $model->getSqlObject();
            $select = $sql->select(array("h" => "house"));
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限=============
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('h.is_delete' , 0);//是否删除
            $where->equalTo('h.company_id' , $company_id);//当前公司
            $where->equalTo('c.city_id' , $user['city_id']);//当前公司
            $select->where($where);
            $data = $select->count();

            //集中式
            $model2 = new \Common\Model\Erp\RoomFocus ();
            $sql2 = $model2->getSqlObject();
            $select2 = $sql2->select(array("rf" => "room_focus"));
            
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select2 , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            
            $where2 = new \Zend\Db\Sql\Where();//造where条件对象
            $where2->equalTo('rf.is_delete' , 0);//是否删除
            $where2->equalTo('rf.company_id' , $company_id);//当前公司
            $where2->equalTo('f.city_id' , $user['city_id']);//城市筛选
            $select2->where($where2)->leftjoin(array('f' => "flat") , getExpSql("f.flat_id=rf.flat_id"));
            $data2 = $select2->count();

            $data3 = array('1' => $data , '2' => $data2);
            return $data3;
        }

        /**
         * 停用
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function disable($user)
        {
            $company_id = $user ['company_id'];
            //分散式
            $model = new \Common\Model\Erp\House ();
            $sql = $model->getSqlObject();
            $select = $sql->select(array("h" => "house_view"));
            
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限=============
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('h.is_delete' , 0);//是否删除
            $where->equalTo('h.company_id' , $company_id);//当前公司
            $where->equalTo('h.status' , 3);//
            $where->equalTo('h.is_yd' , 0);//
            $where->equalTo('h.city_id' , $user['city_id']);//当前公司

            $data = $select->where($where)->columns(array('select_count' => getExpSql('count(*)')))->execute();

            //集中式
            $model2 = new \Common\Model\Erp\RoomFocus ();
            $sql2 = $model2->getSqlObject();
            $select2 = $sql2->select(array("rf" => "room_focus"));
            
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select2 , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            
            $where2 = new \Zend\Db\Sql\Where();//造where条件对象
            $where2->equalTo('rf.is_delete' , 0);//是否删除
            $where2->equalTo('rf.company_id' , $company_id);//当前公司
            $where2->equalTo('rf.status' , 3);//
            $where->equalTo('rf.is_yd' , 0);//
            $select2->where($where2);
            $where2->equalTo('f.city_id' , $user['city_id']);//城市筛选
            $data2 = $select2->where($where2)->leftjoin(array('f' => "flat") , getExpSql("f.flat_id=rf.flat_id"))->columns(array('select_count' => getExpSql('count(*)')))->execute();
            $count = array('1' => $data[0]['select_count'] , '2' => $data2[0]['select_count']);
            return $count;
        }

        /**
         * 未租
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function notRent($user)
        {
            $company_id = $user ['company_id'];
            //分散式
            $model = new \Common\Model\Erp\House ();
            $sql = $model->getSqlObject();
            $select = $sql->select(array("h" => "house_view"));
            
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限=============
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('h.is_delete' , 0);//是否删除
            $where->equalTo('h.company_id' , $company_id);//当前公司
            $where->equalTo('h.status' , 1);//
            $where->equalTo('h.is_yd' , 0);//
            $where->equalTo('h.city_id' , $user['city_id']);//当前公司
            $select->where($where);
            $data = $select->count();
            //集中式
            $model2 = new \Common\Model\Erp\RoomFocus ();
            $sql2 = $model2->getSqlObject();
            $select2 = $sql2->select(array("rf" => "room_focus"));
            
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select2 , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            
            $where2 = new \Zend\Db\Sql\Where();//造where条件对象
            $where2->equalTo('rf.is_delete' , 0);//是否删除
            $where2->equalTo('rf.company_id' , $company_id);//当前公司
            $where2->equalTo('rf.status' , 1);//
            $where->equalTo('rf.is_yd' , 0);//
            $where2->equalTo('f.city_id' , $user['city_id']);//城市筛选
            $select2->where($where2)->leftjoin(array('f' => "flat") , getExpSql("f.flat_id=rf.flat_id"));
            $data2 = $select2->count();
            $data3 = array('1' => $data , '2' => $data2);
            return $data3;
        }

        /**
         * 已租
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function hasRent($user)
        {
            $company_id = $user ['company_id'];
            //分散式
            $model = new \Common\Model\Erp\House ();
            $sql = $model->getSqlObject();
            $select = $sql->select(array("r" => "room"));
            
            $select->leftjoin(array('h' => 'house') , 'r.house_id=h.house_id');
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限=============
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'r.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('r.is_delete' , 0);//是否删除
            $where->equalTo('h.company_id' , $company_id);//当前公司
            $where->equalTo('r.status' , 2);
            //$where->equalTo('r.is_yd' , 0);
            $where->equalTo('r.is_yytz' , 0);
            $where->equalTo('c.city_id' , $user['city_id']);//当前公司
            $select->where($where);
            $data = $select->count();
            //集中式
            $model2 = new \Common\Model\Erp\RoomFocus ();
            $sql2 = $model2->getSqlObject();
            $select2 = $sql2->select(array("rf" => "room_focus"));
            
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select2 , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            
            $where2 = new \Zend\Db\Sql\Where();//造where条件对象
            $where2->equalTo('rf.is_delete' , 0);//是否删除
            $where2->equalTo('rf.company_id' , $company_id);//当前公司
            $where->equalTo('rf.is_yd' , 0);//
            $where2->equalTo('rf.status' , 2);//
            $where2->equalTo('rf.is_yytz' , 0);//
            $where2->equalTo('f.city_id' , $user['city_id']);//城市筛选
            $select2->where($where2)->leftjoin(array('f' => "flat") , getExpSql("f.flat_id=rf.flat_id"));
            $data2 = $select2->count();


            $data3 = array('1' => $data , '2' => $data2);
            return $data3;
        }

        /**
         * 预定
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function reserve($user)
        {
            $company_id = $user ['company_id'];
            //分散式
            $model = new \Common\Model\Erp\House ();
            $sql = $model->getSqlObject();
            $select = $sql->select(array("h" => "house_view"));
            
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限=============
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('h.is_delete' , 0);//是否删除
            $where->equalTo('h.company_id' , $company_id);//当前公司
            $where->equalTo('h.is_yd' , 1);//
            $where->equalTo('h.city_id' , $user['city_id']);//当前公司
            $select->where($where);
            $data = $select->count();
            //集中式
            $model2 = new \Common\Model\Erp\RoomFocus ();
            $sql2 = $model2->getSqlObject();
            $select2 = $sql2->select(array("rf" => "room_focus"));
            
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select2 , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            
            $where2 = new \Zend\Db\Sql\Where();//造where条件对象
            $where2->equalTo('rf.is_delete' , 0);//是否删除
            $where2->equalTo('rf.company_id' , $company_id);//当前公司
            $where2->equalTo('rf.is_yd' , 1);//
            $where2->equalTo('f.city_id' , $user['city_id']);//城市筛选
            $select2->where($where2)->leftjoin(array('f' => "flat") , getExpSql("f.flat_id=rf.flat_id"));
            $data2 = $select2->count();
            $data3 = array('1' => $data , '2' => $data2);
            return $data3;
        }

        /**
         * 预约退租
         *
         * @author yusj | 最后修改时间 2015年5月8日上午10:42:37
         */
        public function rentReservation($user)
        {

            $company_id = $user ['company_id'];
            //分散式
            $model = new \Common\Model\Erp\House ();
            $sql = $model->getSqlObject();
            $select = $sql->select(array("h" => "house_view"));
            
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限=============
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('h.is_delete' , 0);//是否删除
            $where->equalTo('h.company_id' , $company_id);//当前公司
            $where->equalTo('h.status' , 2);
            $where->equalTo('h.is_yytz' , 1);
            $where->equalTo('h.city_id' , $user['city_id']);//当前公司
            $select->where($where);
            $data = $select->count();
            //集中式
            $model2 = new \Common\Model\Erp\RoomFocus ();
            $sql2 = $model2->getSqlObject();
            $select2 = $sql2->select(array("rf" => "room_focus"));
            
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select2 , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            
            $where2 = new \Zend\Db\Sql\Where();//造where条件对象
            $where2->equalTo('rf.is_delete' , 0);//是否删除
            $where2->equalTo('rf.company_id' , $company_id);//当前公司
            $where2->equalTo('rf.status' , 2);
            $where2->equalTo('rf.is_yytz' , 1);
            $where2->equalTo('f.city_id' , $user['city_id']);//城市筛选
            $select2->where($where2)->leftjoin(array('f' => "flat") , getExpSql("f.flat_id=rf.flat_id"));
            $data2 = $select2->count();
            $data3 = array('1' => $data , '2' => $data2);
            return $data3;
        }

    }
    