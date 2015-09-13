<?php

    namespace Core\Db\Sql;

    use Zend\Db\ResultSet\ResultSet;
    use Zend\Db\Sql\Predicate\Expression;
    use Zend\Cache\StorageFactory;
    use Loudi\Cache;
    use Zend\Db\Sql\Where;

    /**
     * 查询
     * @author lishengyou
     *
     */
    class Select extends \Zend\Db\Sql\Select
    {

        protected $sql = NULL;
        protected static $_columns = null;

        public function __construct($table = null , \Zend\Db\Sql\Sql $sql = NULL)
        {
            $this->sql = $sql;
            parent::__construct($table);
            $this->tableReadOnly = false;
        }

        /**
         * 执行
         */
        public function execute(\Zend\Db\Sql\Sql $sql = NULL , $delete = TRUE,$isCache = FALSE)
        {
            try
            {
                if (is_null($this->sql) && !$sql)
                    throw new \Exception('数据库连接不正确');

                $sqlstring = '';
                if($isCache){//使用缓存
	                $sqlstring = $this->getSqlString();
	                $cache = \Core\Db\Sql\Cache::getCacheData($this->table,$this->joins, $sqlstring);
	                if($cache !== false){//有缓存
	                	return $cache;
	                }
                }
                $sql = $sql ? $sql : $this->sql;
                $start_time = microtime();
                $result = $sql->prepareStatementForSqlObject($this)->execute();

	            if(!$result && $this->getTableName() != 'log'){
	            	$end_time = explode(' ',microtime());
	            	$start_time = explode(' ', $start_time);
	            	$end_time = $end_time[1].$end_time[0];
	            	$start_time = $start_time[1].$start_time[0];
					logDebug('SQL:'.@$this->getSqlString().'|RES('.strval(!!$result).')'.($end_time-$start_time));
				}

                if (!$result)
                    throw new \Exception('');
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $data = $resultSet->toArray();

                //保存缓存
                if($isCache){
               		\Core\Db\Sql\Cache::updateCacheData($sqlstring, $data);
                }

                return $data;
            } catch (\Exception $e)
            {
                return false;
            }
        }

        /**
         * 取得主键
         * @author lishengyou
         * 最后修改时间 2015年1月30日 上午10:56:34
         *
         * @param unknown $table
         */
        public function getPk($table = null)
        {
            $table = $table ? $table : $this->table;
            $constraints = $this->sql->metadata()->getConstraints($table);
            foreach ($constraints as $constraint)
            {
                if ($constraint->isPrimaryKey())
                {
                    return $constraint->getColumns();
                }
            }
            return false;
        }

        /**
         * 左连接
         * @author lishengyou
         * 最后修改时间 2015年4月2日 下午2:08:43
         *
         * @param unknown $name
         * @param unknown $on
         * @param unknown $columns
         * @return \Core\Db\Sql\Select
         */
        public function leftjoin($name , $on , $columns = self::SQL_STAR)
        {
            parent::join($name , $on , $columns , 'LEFT');
            return $this;
        }

        /**
         * 分页查询
         * @author lishengyou
         * 最后修改时间 2014年11月13日 上午10:36:06
         *
         * @param Select $select
         * @param unknown $count
         * @param unknown $page
         * @param unknown $size
         */
        public static function pageSelect(Select $select , $count = null , $page , $size,$iscache = false ,$offset=0)
        {
            if (is_null($count))
            {
                $countSelect = clone $select;
                $count = $countSelect->count();
            }
            $count = intval($count);
            $page = intval($page);
            $size = intval($size);
            $size = $size < 1 ? 1 : $size;
            $count = $count < 0 ? 0 : $count;
            $cpage = ceil($count / $size);
            $page = $page > $cpage ? $cpage : $page;
            $page = $page < 1 ? 1 : $page;
            $select->limit($size);
            $select->offset(($page - 1) * $size + $offset);
            $data = $select->execute(null,true,$iscache);
 		//echo str_replace('"', '', $select->getSqlString());die;
            return array('page' => array('page' => $page , 'size' => $size , 'count' => $count , 'cpage' => $cpage) , 'data' => $data);
        }

        /**
         * 统计
         * @author lishengyou
         * 最后修改时间 2015年4月2日 下午2:09:48
         *
         * @return Ambigous <number, unknown>
         */
        public function count()
        {
        	$cs = clone $this;
        	if(!$this->group){
	            $cs->columns(array('select_count' => new Expression('count(*)')));
	            $count = $cs->execute();
	            $count = $count[0]['select_count'];
	            return $count ? $count : 0;
        	}else{
        		$cs->columns(array(new Expression('0 as id')));
        		$select = $this->sql->select(array(uniqid()=>new \Zend\Db\Sql\TableIdentifier($cs)));
        		return $select->count();
        	}
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
         * 添加where条件
         *  最后修改时间 2015-3-13
         *  zend自带的where方法如果传入where类的对象就会覆盖之前的,这个方法只添加,不覆盖
         *
         * @author dengshuang
         * @param unknown $predicate
         * @param unknown $combination
         * @return \Core\Db\Sql\Select
         */
        public function andWhere($predicate , $combination = \zend\db\sql\Predicate\PredicateSet::OP_AND)
        {
            if ($predicate instanceof Where)
            {
                $this->where->addPredicates($predicate , $combination);
            }
            return $this;
        }
        /**
         * Get SQL string for statement
         *
         * @param  null|PlatformInterface $adapterPlatform If null, defaults to Sql92
         * @return string
         */
        public function getSqlString(\Zend\Db\Adapter\Platform\PlatformInterface $adapterPlatform = null){
        	$display_errors = ini_get('display_errors');
        	ini_set('display_errors', 0);
        	if(!$adapterPlatform && $this->sql){
        		$adapterPlatform = $this->sql->getAdapter()->getPlatform();
        	}
        	$sql = parent::getSqlString($adapterPlatform);
        	ini_set('display_errors', $display_errors);
        	return $sql;
        }
    }
