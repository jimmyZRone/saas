<?php

    namespace Common;

    class Model
    {

        protected static $_link_guid = null;

        /**
         * sql对象
         * @var object
         */
        protected $_sql_object = null;

        /**
         * 表字段
         * @var array
         */
        protected $_table_field = array();

        /**
         * 表名
         * @var string
         */
        protected $_table_name = '';
        protected $_last_sql = '';

        /**
         * 初始化方法
         * 	最后修改时间 2014-10-31
         * 	成员函数是用来初始化对象获取sql对象
         *
         * @author dengshuang
         * @return void
         */
        public function __construct($tableName = '' , $guid = NULL)
        {//P(static::$_link_guid);
            static::$_link_guid = $guid ? $guid : static::$_link_guid;//logDebug(static::$_link_guid);
            $this->_sql_object = self::getLink(static::$_link_guid);
            if (!$tableName && !$this->_table_name)
            {
                //根据当前类名称取信息
                $classname = get_class($this);
                $classname = explode('\\' , $classname);
                $classname = str_split(end($classname));
                $tableName = '';
                foreach ($classname as $key => $value)
                {
                    if (is_numeric($value))
                    {
                        $tableName .= $value;
                    }
                    else
                    {
                        $ascii = ord($value);
                        if ($key > 0 && $ascii < 91)
                        {//大写
                            $tableName .= '_';
                        }
                        $tableName .= strtolower($value);
                    }
                }
            }
            $this->_table_name = $tableName ? $tableName : $this->_table_name;
            if (empty($this->_table_field))
            {
                $this->_table_field = $this->getFieldByName($tableName);
            }
        }

        /**
         * 取得表名
         * @author lishengyou
         * 最后修改时间 2014年12月29日 下午4:29:12
         *
         * @return string
         */
        public function getTableName()
        {
            return $this->_table_name;
        }

        /**
         * 设置表名
         * @author lishengyou
         * 最后修改时间 2014年12月29日 下午4:29:12
         *
         * @return string
         */
        public function setTableName($tablename)
        {
            $this->_table_name = $tablename;
            return $this;
        }

        /**
         * 取得连接标识
         * @author lishengyou
         * 最后修改时间 2015年3月26日 上午10:39:09
         *
         */
        public static function getGuid()
        {
            return static::$_link_guid;
        }

        protected $transaction_status = false;

        /**
         * 开启事务
         * @author lishengyou
         * 最后修改时间 2014年12月29日 下午2:33:06
         *
         */
        public function Transaction()
        {
            if (!$this->transaction_status)
            {
                $this->transaction_status = true;
                $this->_sql_object->Transaction();
            }
        }

        protected static $_static_transaction = array();

        /**
         * 开启事务
         * @author lishengyou
         * 最后修改时间 2015年3月26日 上午10:21:16
         *
         * @param unknown $guid
         */
        public static function TransactionByGuid($guid)
        {
            $sql_object = static::getLink($guid);
            if ($sql_object)
            {
                $sql_object->Transaction();
            }
            else
            {
                static::$_static_transaction[$guid] = true;
            }
        }

        /**
         * 会滚事务
         */
        public function rollback()
        {
            if ($this->transaction_status)
            {
                $this->transaction_status = false;
                $this->_sql_object->rollback();
            }
        }

        /**
         * 回滚事务
         * @author lishengyou
         * 最后修改时间 2015年3月26日 上午10:21:51
         *
         * @param unknown $guid
         */
        public static function RollbackByGuid($guid)
        {
            $sql_object = static::getLink($guid);
            if ($sql_object)
            {
                $sql_object->rollback();
            }
        }

        /**
         * 提交事务
         */
        public function commit()
        {
            if ($this->transaction_status)
            {
                $this->transaction_status = false;
                $this->_sql_object->commit();
            }
        }

        /**
         * 提交事务
         * @author lishengyou
         * 最后修改时间 2015年3月26日 上午10:22:17
         *
         * @param unknown $guid
         */
        public static function CommitByGuid($guid)
        {
            $sql_object = static::getLink($guid);
            if ($sql_object)
            {
                $sql_object->commit();
            }
        }

        /**
         * 取得Select对象
         * @author lishengyou
         * 最后修改时间 2015年3月19日 下午3:28:39
         *
         * @return \Core\Db\Sql\Sql
         */
        public function getSqlObject()
        {
            return $this->_sql_object;
        }

        /**
         * 设置字段
         * 	最后修改时间 2014-11-04
         * 	设置字段
         *
         * @param array $data
         * @author dengshuang
         * @return int
         */
        protected function setTableFiled($tableField = array())
        {
            if ($tableField)
            {
                $this->_table_field = $tableField;
            }
        }

        /**
         * 获取数量
         * 	最后修改时间 2014-10-31
         * 	获取数量
         *
         * @param array $data
         * @author dengshuang
         * @return int
         */
        public function getCount($where)
        {
            $select = $this->_sql_object->select($this->_table_name);
            $select->columns(array('count' => new \Zend\Db\Sql\Expression('count(*)')));

            $select->where($where);
            $count = $select->execute();
            $this->_last_sql = SQL($select); // P($this->_last_sql);
            if ($count)
            {
                $count = isset($count[0]['count']) ? $count[0]['count'] : 0;
                return $count;
            }
            else
            {
                return false;
            }
        }

        /**
         * 插入数据
         * 	最后修改时间 2014-10-31
         * 	插入数据
         *
         * @param array $data
         * @author dengshuang
         * @return int
         */
        public function insert($data)
        {
            if (is_int(current(array_keys($data))))
            {//插入多条
                $insert = $this->_sql_object->insert($this->_table_name);
                $sqlstr = '';
                foreach ($data as $key => $value)
                {
                    $value = $this->_prepare($value);
                    $insert->values($value);
                    $sqlstr .= $insert->getSqlString() . ';';
                }
                $sqlstr = trim($sqlstr , ';');
                $pdo = $this->_sql_object->getAdapter()->getDriver()->getConnection()->getResource();
                $results = $pdo->exec($sqlstr); // P($sqlstr);
                if (!$results)
                {
                    $this->rollback();
                }
                else
                {
                    $this->commit();
                }
                $lastid = $pdo->lastInsertId();
                return $results ? ($lastid ? $lastid : true) : false;
            }
            else
            {
                $data = $this->_prepare($data);
                $insert = $this->_sql_object->insert($this->_table_name);
                $insert->values($data);
                $this->_last_sql = str_replace('"' , "" , @$insert->getSqlString()); // P($this->_last_sql);
                $results = $insert->execute(); //print_r(str_replace('"' , "" , $insert->getSqlString()));
                return $results;
            }
        }

        /**
         * 修改数据
         * 	最后修改时间 2014-10-31
         * 	修改数据
         *
         * @param array $data
         * @author dengshuang
         * @return int
         */
        public function edit($where , $data)
        {

            $update = $this->_sql_object->update($this->_table_name);
            $data = $this->_prepare($data); //print_r($data);
            $update->set($data);
            $update->where($where);
            $this->_last_sql = str_replace('"' , "" , @$update->getSqlString());
            $results = $update->execute();
            //print_r(str_replace('"' , "" , $this->_last_sql));//die();
            if ($results !== false)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        /**
         * 删除
         * 	最后修改时间 2014-11-05
         * 	删除
         *
         * @param array $house_id
         * @author dengshuang
         * @return bool
         */
        public function delete($condition , $is_delete = false)
        {
            $delete = $this->_sql_object->delete($this->_table_name);
            $delete->where($condition);
            $this->_last_sql = str_replace('"' , "" , @$delete->getSqlString());
            //print_r($this->_last_sql);//die();
            return $delete->execute(null , $is_delete);
        }

        /**
         * 获取单条数据
         * 	最后修改时间 2014-11-05
         * 	获取单条数据
         *
         * @param array $house_id
         * @author dengshuang
         * @return bool
         */
        public function getOne($where , $columns = array() , $iscache = false)
        {
            $result = $this->getData($where , $columns , 0 , 0 , '' , false , '' , $iscache);

            if (is_array($result))
            {
                if (!empty($result['0']))
                {
                    return $result['0'];
                }
                else
                {
                    return array();
                }
            }
            else
            {
                return false;
            }
        }

        /**
         * 获取多条数据
         * 	最后修改时间 2014-11-05
         * 	获取多条数据
         *
         * @param array $house_id
         * @author dengshuang
         * @return bool
         */
        public function getData($where = array() , $columns = array() , $limit = 0 , $offset = 0 , $order = '' , $count = false , $group = '' , $iscache = false)
        {
            $offset = $offset < 0 ? 0 : $offset;
            $select = $this->_sql_object->select($this->_table_name); //var_dump($select);
            if ($columns)
            {
                $select->columns($columns);
            }
            if ($where)
            {
                $select->where($where);
            }
            if ($order)
            {
                $select->order($order);
            }

            if ($group)
            {
                $select->group($group);
            }

            if ($count === false)
            {
                if ($limit)
                {
                    $select->limit($limit);
                }
                if ($limit)
                {
                    $select->offset($offset);
                }
                $this->_last_sql = str_replace('"' , "" , @$select->getSqlString());
                //print_r(str_replace('"' , "" , $select->getSqlString()));
                //print_r($this->_last_sql);
                return $select->execute(null , true , $iscache);
            }
            else
            {
                $limit = $limit ? $limit : 10;
                return \Core\Db\Sql\Select::pageSelect($select , $count , intval($offset / $limit) + 1 , $limit);
            }
        }

        /**
         * 根据表名获取字段数组
         * 	最后修改时间 2014-11-04
         * 	根据表名获取字段数组
         *
         * @param array $data
         * @author dengshuang
         * @return int
         */
        private function getFieldByName($tableName)
        {
            $cache_key = "web/table_field/" . static::$_link_guid . "_{$tableName}";
            $cache = new \Core\Cache(new \Core\Cache\File());
            $cacheData = $cache->get($cache_key);
            if (!$cacheData)
            {
                if (!method_exists($this->_sql_object , 'metadata'))
                    return false;
                $columns = $this->_sql_object->metadata()->getColumns($this->_table_name);
                $_columns = array();
                foreach ($columns as $value)
                {
                    $_columns[] = $value->getName();
                }
                $cacheData = $_columns;
                $cache->save($cache_key , $cacheData);
            }
            return $cacheData;
        }

        /**
         * 预处理数据
         * 	最后修改时间 2014-11-04
         * 	预处理数据,筛选出存在的字段去除无用数据
         *
         * @param array $data
         * @author dengshuang
         * @return int
         */
        private function _prepare($data = array())
        {
            if (empty($data) || empty($this->_table_field))
            {
                return $data;
            }
            else
            {
                $return_array = array();
                foreach ($data as $key => $value)
                {
                    if (in_array($key , $this->_table_field))
                    {
                        $return_array[$key] = $value;
                    }
                }//print_r($return_array);
                return $return_array;
            }
        }

        /**
         * 获取链接
         *  最后修改时间 2015-3-13
         *  获取数据库链接,其实是获取的是sql对象
         *
         * @param string $uid
         * @return \Core\Db\Sql\Sql
         */
        public static function getLink($uid = 'default')
        {
            static $links = array();
            if (!isset($links[$uid]))
            {
                $config = \Core\Config::get('db:' . $uid);
                if (!$config)
                {
                    $links[$uid] = null;
                }
                else
                {
                    $links[$uid] = \Core\Db\Sql\Sql::getInstance($uid , $config);
                }
            }
            if ($links[$uid] && isset(static::$_static_transaction[$uid]) && static::$_static_transaction[$uid])
            {
                $links[$uid]->Transaction();
                unset(static::$_static_transaction[$uid]);
            }
            return $links[$uid];
        }

        public function getLastSql()
        {
            return $this->_last_sql;
        }

    }
    