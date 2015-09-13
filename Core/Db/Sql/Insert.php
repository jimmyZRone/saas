<?php

    namespace Core\Db\Sql;

    /**
     * 添加
     * @author lishengyou
     *
     */
    class Insert extends \Zend\Db\Sql\Insert
    {

        protected $sql = NULL;

        public function __construct($table = null , \Zend\Db\Sql\Sql $sql = NULL)
        {
            $this->sql = $sql;
            parent::__construct($table);
        }

        /**
         * 获取表名
         *  最后修改时间 2015-3-13
         *
         * @author dengshuang
         * @return Ambigous <string, multitype:, \Zend\Db\Sql\TableIdentifier>
         */
        public function getTableName()
        {
            $data = $this->table;
            if (is_array($data))
            {
                $data = each($data);
                return $data['key'];
            }
            return $data;
        }

        /**
         * 执行
         */
        public function execute(\Zend\Db\Sql\Sql $sql = NULL)
        {
            try
            {
                if (is_null($this->sql) && !$sql)
                    throw new \Exception('数据库连接不正确');
                $sql = $sql ? $sql : $this->sql;
                if (!in_array('create_time' , $this->columns) && method_exists($sql , 'metadata') && $sql->hasColumn('create_time' , $this->table))
                {
                    $this->values(array('create_time' => time()) , self::VALUES_MERGE);
                }
                if (!in_array('update_time' , $this->columns) && method_exists($sql , 'metadata') && $sql->hasColumn('update_time' , $this->table))
                {
                    $this->values(array('update_time' => time()) , self::VALUES_MERGE);
                }

                $result = $sql->prepareStatementForSqlObject($this)->execute();
                if ($result)
                {
                    //更新缓存索引
                    \Core\Db\Sql\Cache::updateTableIndex($this->table);

                    $id = $result->getGeneratedValue();
                    if ($id)
                    {
                        $result = $id;
                    }
                    else
                    {
                        $result = true;
                    }
                }
                else
                {
                    $result = false;
                }
                if ($this->getTableName() != 'log')
                {
                    logDebug('SQL:' . @$this->getSqlString() . '|RES(' . (is_bool($result) ? 'BOOL:' . intval($result) : $result) . ')');
                }
                return $result;
            } catch (\Exception $e)
            {
                logDebug('新增失败:' . $e->getMessage());
                //echo $e;
                return false;
            }
        }

        /**
         * Get SQL string for statement
         *
         * @param  null|PlatformInterface $adapterPlatform If null, defaults to Sql92
         * @return string
         */
        public function getSqlString(\Zend\Db\Adapter\Platform\PlatformInterface $adapterPlatform = null)
        {
            $display_errors = ini_get('display_errors');
            ini_set('display_errors' , 0);
            if (!$adapterPlatform && $this->sql)
            {
                $adapterPlatform = $this->sql->getAdapter()->getPlatform();
            }
            $sql = parent::getSqlString($adapterPlatform);
            ini_set('display_errors' , $display_errors);
            return $sql;
        }

    }
    