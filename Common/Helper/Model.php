<?php

    /**
     * @author    Lmssky  智能拼装SQL 2015年6月16日 20:54:16
     */
    namespace Common\Helper;

    class Model
    {

        // 主键名称
        protected $pk = 'id';
        // 数据表前缀
        protected $tablePrefix = '';
        // 数据表名（不包含表前缀）
        protected $tableName = '';
        // 最近错误信息
        protected $error = '';
        protected $link;
        protected $sql_obj;
        // 字段信息
        protected $fields = array();
        // 数据信息
        protected $data = array();
        // 查询表达式参数
        protected $options = array();
        // 数据库表达式
        protected $comparison = array('eq' => '=' , 'neq' => '<>' , 'gt' => '>' , 'egt' => '>=' , 'lt' => '<' , 'elt' => '<=' , 'notlike' => 'NOT LIKE' , 'like' => 'LIKE' , 'in' => 'IN' , 'notin' => 'NOT IN');
        protected $methods = array('table' , 'order' , 'alias' , 'having' , 'group' , 'lock' , 'distinct' , 'auto' , 'filter' , 'validate' , 'result' , 'bind');
        protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';
        protected $mysqlfunc = array('NOW()');
        protected $lastsql = '';

        /**
         * 架构函数
         * 取得DB类的实例对象 字段检查
         * @access public
         * @param string $name 模型名称
         * @param string $tablePrefix 表前缀
         * @param mixed $connection 数据库连接信息
         */
        public function __construct($name = '' , $tablePrefix = '' , $config = '')
        {
            // 模型初始化
            $this->connect($config);
            $this->_initialize();
            // 获取模型名称
            if (!empty($name))
            {
                if (strpos($name , '.'))
                { // 支持 数据库名.模型名的 定义
                    list($this->dbName , $this->name) = explode('.' , $name);
                }
                else
                {
                    $this->name = $name;
                }
            }
            elseif (empty($this->name))
            {
                $this->name = $this->getModelName();
            }

            // 设置表前缀
            if (is_null($tablePrefix))
            {// 前缀为Null表示没有前缀
                $this->tablePrefix = '';
            }
            elseif ('' != $tablePrefix)
            {
                $this->tablePrefix = $tablePrefix;
            }
            return $this;
        }

        private function connect($_config)
        {
            //已连接则不再连接

            if (!empty($this->link))
                return;

            $model = new \Common\Model();
            $this->link = $model->getLink()->getAdapter()->getDriver()->getConnection()->getResource();

            return;
//            $db = empty($this->dbName) ? $_config['DB_NAME'] : $this->dbName;
//            $link = new mysqli($_config['DB_HOST'] , $_config['DB_USER'] , $_config['DB_PWD'] , $db);
//            // $link = mysqli::__construct($_config['DB_HOST'] , $_config['DB_USER'] , $_config['DB_PWD']) or die(mysql_error());
//            if (mysqli_connect_errno())
//            {
//                echo "连接失败" . mysqli_connect_error();
//                exit();
//            }
//            $this->link = $link;
        }

        function query($sql)
        {
            $this->lastsql = $sql;
            logDebug($sql);
            $this->sql_obj = $this->link->prepare($sql);
            $result = $this->sql_obj->execute();
            return $result;
        }

        /**
         * 得到当前的数据对象名称
         * @access public
         * @return string
         */
        public function getModelName()
        {
            if (empty($this->name))
                $this->name = substr(get_class($this) , 0 , -5);
            return $this->name;
        }

        public function table($table)
        {
            $this->name = $table;
            $this->trueTableName = $table;
            return $this;
        }

        /**
         * 设置数据对象的值
         * @access public
         * @param string $name 名称
         * @param mixed $value 值
         * @return void
         */
        public function __set($name , $value)
        {
            // 设置数据对象属性
            $this->data[$name] = $value;
        }

        /**
         * 获取数据对象的值
         * @access public
         * @param string $name 名称
         * @return mixed
         */
        public function __get($name)
        {
            return isset($this->data[$name]) ? $this->data[$name] : null;
        }

        /**
         * 检测数据对象的值
         * @access public
         * @param string $name 名称
         * @return boolean
         */
        public function __isset($name)
        {
            return isset($this->data[$name]);
        }

        /**
         * 销毁数据对象的值
         * @access public
         * @param string $name 名称
         * @return void
         */
        public function __unset($name)
        {
            unset($this->data[$name]);
        }

        /**
         * 利用__call方法实现一些特殊的Model方法
         * @access public
         * @param string $method 方法名称
         * @param array $args 调用参数
         * @return mixed
         */
        public function __call($method , $args)
        {
            if (in_array(strtolower($method) , $this->methods , true))
            {
                // 连贯操作的实现
                $this->options[strtolower($method)] = $args[0];
                return $this;
            }
            elseif (in_array(strtolower($method) , array('count' , 'sum' , 'min' , 'max' , 'avg') , true))
            {
                // 统计查询的实现
                $field = isset($args[0]) ? $args[0] : '*';

                return $this->getField(strtoupper($method) . '(' . $field . ') AS ' . $method);
            }
            elseif (strtolower(substr($method , 0 , 5)) == 'getby')
            {
                // 根据某个字段获取记录
                $field = parse_name(substr($method , 5));
                $where[$field] = $args[0];
                return $this->where($where)->find();
            }
            elseif (strtolower(substr($method , 0 , 10)) == 'getfieldby')
            {
                // 根据某个字段获取记录的某个值
                $name = parse_name(substr($method , 10));
                $where[$name] = $args[0];
                return $this->where($where)->getField($args[1]);
            }
            elseif (isset($this->_scope[$method]))
            {// 命名范围的单独调用支持
                return $this->scope($method , $args[0]);
            }
            else
            {
                //throw_exception(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
                return;
            }
        }

        // 回调方法 初始化模型
        protected function _initialize()
        {
            
        }

        /**
         * 对保存到数据库的数据进行处理
         * @access protected
         * @param mixed $data 要操作的数据
         * @return boolean
         */
        protected function _facade($data)
        {
            // 检查非数据字段
            if (!empty($this->fields))
            {
                foreach ($data as $key => $val)
                {
                    if (!in_array($key , $this->fields , true))
                    {
                        unset($data[$key]);
                    }
                    elseif (is_scalar($val))
                    {
                        // 字段类型检查
                        $this->_parseType($data , $key);
                    }
                }
            }
            // 安全过滤
            if (!empty($this->options['filter']))
            {
                $data = array_map($this->options['filter'] , $data);
                unset($this->options['filter']);
            }
            $this->_before_write($data);
            return $data;
        }

        // 写入数据前的回调方法 包括新增和更新
        protected function _before_write(&$data)
        {
            
        }

        /**
         * 新增数据
         * @access public
         * @param mixed $data 数据
         * @param array $options 表达式
         * @param boolean $replace 是否replace
         * @return mixed
         */
        public function add($data = '' , $options = array() , $replace = false)
        {
            if (empty($data))
            {
                // 没有传递数据，获取当前数据对象的值
                if (!empty($this->data))
                {
                    $data = $this->data;
                    // 重置数据
                    $this->data = array();
                }
                else
                {
                    $this->error = L('_DATA_TYPE_INVALID_');
                    return false;
                }
            }

            // 分析表达式
            $options = $this->_parseOptions($options);

            // 数据处理
            $data = $this->_facade($data);

            if (false === $this->_before_insert($data , $options))
            {
                return false;
            }
            // 生成SQL
            $sql = $this->insert($data , $options , $replace);
            $result = $this->query($sql);
            if ($result)
            {
                $pk_id = $this->link->lastInsertId();
                return $pk_id > 0 ? $pk_id : true;
            }
            return $result;
        }

        /**
         * 插入记录
         * @access public
         * @param mixed $data 数据
         * @param array $options 参数表达式
         * @param boolean $replace 是否replace
         * @return false | integer
         */
        public function insert($data , $options = array() , $replace = false)
        {

            $values = $fields = array();
            foreach ($data as $key => $val)
            {
                if (is_array($val) && 'exp' == $val[0])
                {
                    $fields[] = $this->parseKey($key);
                    $values[] = $this->escapeString($val[1]);
                }
                elseif (is_scalar($val))
                { // 过滤非标量数据
                    $new_key = $this->parseKey($key);
                    $fields[] = "`$new_key`";
                    $values[] = $this->parseValue($val);
                }
            }
            $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',' , $fields) . ') VALUES (' . implode(',' , $values) . ')';

            $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
            $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');

            return $sql;
        }

        /**
         * table分析
         * @access protected
         * @param mixed $table
         * @return string
         */
        protected function parseTable($tables)
        {

            if (is_array($tables))
            {// 支持别名定义
                $array = array();
                foreach ($tables as $table => $alias)
                {
                    if (!is_numeric($table))
                        $array[] = $table . ' ' . $this->parseKey($alias);
                    else
                        $array[] = $this->parseKey($table);
                }
                $tables = $array;
            }elseif (is_string($tables))
            {
                $tables = explode(',' , $tables);
                array_walk($tables , array(&$this , 'parseKey'));
            }
            return implode(',' , $tables);
        }

        protected function parseKey(&$key)
        {
            return $key;
        }

        // 插入数据前的回调方法
        protected function _before_insert(&$data , $options)
        {
            
        }

        // 插入成功后的回调方法
        protected function _after_insert($data , $options)
        {
            
        }

        public function addAll($dataList , $options = array() , $replace = false)
        {
            if (empty($dataList))
            {
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
            // 分析表达式
            $options = $this->_parseOptions($options);
            // 数据处理
            foreach ($dataList as $key => $data)
            {
                $dataList[$key] = $this->_facade($data);
            }

            // 写入数据到数据库
            $result = $this->db->insertAll($dataList , $options , $replace);
            if (false !== $result)
            {
                $insertId = $this->getLastInsID();
                if ($insertId)
                {
                    return $insertId;
                }
            }
            return $result;
        }

        /**
         * 通过Select方式添加记录
         * @access public
         * @param string $fields 要插入的数据表字段名
         * @param string $table 要插入的数据表名
         * @param array $options 表达式
         * @return boolean
         */
        public function selectAdd($fields = '' , $table = '' , $options = array())
        {
            // 分析表达式
            $options = $this->_parseOptions($options);
            // 写入数据到数据库
            if (false === $result = $this->db->selectInsert($fields ? $fields : $options['field'] , $table ? $table : $this->getTableName() , $options))
            {
                // 数据库插入操作失败
                $this->error = $this->L('_OPERATION_WRONG_');
                return false;
            }
            else
            {
                // 插入成功
                return $result;
            }
        }

        /**
         * 保存数据
         * @access public
         * @param mixed $data 数据
         * @param array $options 表达式
         * @return boolean
         */
        public function save($data = '' , $options = array())
        {

            if (empty($data))
            {
                $this->error = '保存数据为空';
                return false;
            }
            // 数据处理

            $data = $this->_facade($data);
            // 分析表达式
            $options = $this->_parseOptions($options);
            if (false === $this->_before_update($data , $options))
            {
                return false;
            }
            if (!isset($options['where']))
            {
                // 如果没有任何更新条件则不执行
                $this->error = $this->L('_OPERATION_WRONG_');
                return false;
            }
            $result = $this->update($data , $options);
            return $result;
        }

        /**
         * 更新记录
         * @access public
         * @param mixed $data 数据
         * @param array $options 表达式
         * @return false | integer
         */
        public function update($data , $options)
        {

            $sql = 'UPDATE '
                    . $this->parseTable($options['table'])
                    . $this->parseSet($data)
                    . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
                    . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
                    . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
                    . $this->parseLock(isset($options['lock']) ? $options['lock'] : false)
                    . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');

            $result = $this->query($sql);
            return $result;
        }

        /**
         * 特殊条件分析
         * @access protected
         * @param string $key
         * @param mixed $val
         * @return string
         */
        protected function parseThinkWhere($key , $val)
        {

            $whereStr = '';
            switch ($key)
            {
                case '_string':
                    // 字符串模式查询条件
                    $whereStr = $val;
                    break;
                case '_complex':
                    // 复合查询条件
                    $whereStr = substr($this->parseWhere($val) , 6);

                    break;
                case '_query':
                    // 字符串模式查询条件
                    parse_str($val , $where);
                    if (isset($where['_logic']))
                    {
                        $op = ' ' . strtoupper($where['_logic']) . ' ';
                        unset($where['_logic']);
                    }
                    else
                    {
                        $op = ' AND ';
                    }
                    $array = array();
                    foreach ($where as $field => $data)
                        $array[] = $this->parseKey($field) . ' = ' . $this->parseValue($data);
                    $whereStr = implode($op , $array);
                    break;
            }
            return $whereStr;
        }

        /**
         * limit分析
         * @access protected
         * @param mixed $lmit
         * @return string
         */
        protected function parseLimit($limit)
        {
            return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
        }

        /**
         * join分析
         * @access protected
         * @param mixed $join
         * @return string
         */
        protected function parseJoin($join)
        {
            $joinStr = '';
            if (!empty($join))
            {
                if (is_array($join))
                {
                    foreach ($join as $key => $_join)
                    {
                        if (false !== stripos($_join , 'JOIN'))
                            $joinStr .= ' ' . $_join;
                        else
                            $joinStr .= ' LEFT JOIN ' . $_join;
                    }
                }else
                {
                    $joinStr .= ' LEFT JOIN ' . $join;
                }
            }
            //将__TABLE_NAME__这样的字符串替换成正规的表名,并且带上前缀和后缀
            $joinStr = preg_replace("/__([A-Z_-]+)__/sU" , $this->C("DB_PREFIX") . ".strtolower('$1')" , $joinStr);
            return $joinStr;
        }

        /**
         * order分析
         * @access protected
         * @param mixed $order
         * @return string
         */
        protected function parseOrder($order)
        {
            if (is_array($order))
            {
                $array = array();
                foreach ($order as $key => $val)
                {
                    if (is_numeric($key))
                    {
                        $array[] = $this->parseKey($val);
                    }
                    else
                    {
                        $array[] = $this->parseKey($key) . ' ' . $val;
                    }
                }
                $order = implode(',' , $array);
            }
            return !empty($order) ? ' ORDER BY ' . $order : '';
        }

        /**
         * group分析
         * @access protected
         * @param mixed $group
         * @return string
         */
        protected function parseGroup($group)
        {
            return !empty($group) ? ' GROUP BY ' . $group : '';
        }

        /**
         * where分析
         * @access protected
         * @param mixed $where
         * @return string
         */
        protected function parseWhere($where)
        {
            $whereStr = '';
            if (is_string($where))
            {
                // 直接使用字符串条件
                $whereStr = $where;
            }
            else
            { // 使用数组表达式
                $operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
                if (in_array($operate , array('AND' , 'OR' , 'XOR')))
                {
                    // 定义逻辑运算规则 例如 OR XOR AND NOT
                    $operate = ' ' . $operate . ' ';
                    unset($where['_logic']);
                }
                else
                {
                    // 默认进行 AND 运算
                    $operate = ' AND ';
                }

                foreach ($where as $key => $val)
                {
                    $whereStr .= '( ';
                    if (0 === strpos($key , '_'))
                    {
                        // 解析特殊条件表达式
                        $whereStr .= $this->parseThinkWhere($key , $val);
                    }
                    else
                    {
                        // 查询字段的安全过滤
                        if (!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/' , trim($key)))
                        {
                            throw_exception(L('_EXPRESS_ERROR_') . ':' . $key);
                        }
                        // 多条件支持
                        $multi = is_array($val) && isset($val['_multi']);
                        $key = trim($key);
                        if (strpos($key , '|'))
                        { // 支持 name|title|nickname 方式定义查询字段
                            $array = explode('|' , $key);
                            $str = array();
                            foreach ($array as $m => $k)
                            {
                                $v = $multi ? $val[$m] : $val;
                                $str[] = '(' . $this->parseWhereItem($this->parseKey($k) , $v) . ')';
                            }
                            $whereStr .= implode(' OR ' , $str);
                        }
                        elseif (strpos($key , '&'))
                        {
                            $array = explode('&' , $key);
                            $str = array();
                            foreach ($array as $m => $k)
                            {
                                $v = $multi ? $val[$m] : $val;
                                $str[] = '(' . $this->parseWhereItem($this->parseKey($k) , $v) . ')';
                            }
                            $whereStr .= implode(' AND ' , $str);
                        }
                        else
                        {
                            $whereStr .= $this->parseWhereItem($this->parseKey($key) , $val);
                        }
                    }
                    $whereStr .= ' )' . $operate;
                }
                $whereStr = substr($whereStr , 0 , -strlen($operate));
            }

            return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        }

        // where子单元分析
        protected function parseWhereItem($key , $val)
        {
            $whereStr = '';
            if (is_array($val))
            {
                if (is_string($val[0]))
                {
                    if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i' , $val[0]))
                    { // 比较运算
                        $whereStr .= $key . ' ' . $this->comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
                    }
                    elseif (preg_match('/^(NOTLIKE|LIKE)$/i' , $val[0]))
                    {// 模糊查找
                        if (is_array($val[1]))
                        {
                            $likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
                            if (in_array($likeLogic , array('AND' , 'OR' , 'XOR')))
                            {
                                $likeStr = $this->comparison[strtolower($val[0])];
                                $like = array();
                                foreach ($val[1] as $item)
                                {
                                    $like[] = $key . ' ' . $likeStr . ' ' . $this->parseValue($item);
                                }
                                $whereStr .= '(' . implode(' ' . $likeLogic . ' ' , $like) . ')';
                            }
                        }
                        else
                        {
                            $whereStr .= $key . ' ' . $this->comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
                        }
                    }
                    elseif ('exp' == strtolower($val[0]))
                    { // 使用表达式
                        $whereStr .= ' (' . $key . ' ' . $val[1] . ') ';
                    }
                    elseif (preg_match('/IN/i' , $val[0]))
                    { // IN 运算
                        if (isset($val[2]) && 'exp' == $val[2])
                        {
                            $whereStr .= $key . ' ' . strtoupper($val[0]) . ' ' . $val[1];
                        }
                        else
                        {
                            if (is_string($val[1]))
                            {
                                $val[1] = explode(',' , $val[1]);
                            }
                            $zone = implode(',' , $this->parseValue($val[1]));
                            $whereStr .= $key . ' ' . strtoupper($val[0]) . ' (' . $zone . ')';
                        }
                    }
                    elseif (preg_match('/BETWEEN/i' , $val[0]))
                    { // BETWEEN运算
                        $data = is_string($val[1]) ? explode(',' , $val[1]) : $val[1];
                        $whereStr .= ' (' . $key . ' ' . strtoupper($val[0]) . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]) . ' )';
                    }
                    else
                    {
                        throw_exception(L('_EXPRESS_ERROR_') . ':' . $val[0]);
                    }
                }
                else
                {
                    $count = count($val);
                    $rule = isset($val[$count - 1]) ? strtoupper($val[$count - 1]) : '';
                    if (in_array($rule , array('AND' , 'OR' , 'XOR')))
                    {
                        $count = $count - 1;
                    }
                    else
                    {
                        $rule = 'AND';
                    }
                    for ($i = 0; $i < $count; $i++)
                    {
                        $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                        if ('exp' == strtolower($val[$i][0]))
                        {
                            $whereStr .= '(' . $key . ' ' . $data . ') ' . $rule . ' ';
                        }
                        else
                        {
                            $op = is_array($val[$i]) ? $this->comparison[strtolower($val[$i][0])] : '=';
                            $whereStr .= '(' . $key . ' ' . $op . ' ' . $this->parseValue($data) . ') ' . $rule . ' ';
                        }
                    }
                    $whereStr = substr($whereStr , 0 , -4);
                }
            }
            else
            {
                //对字符串类型字段采用模糊匹配
                $whereStr .= $key . ' = ' . $this->parseValue($val);
            }
            return $whereStr;
        }

        /**
         * set分析
         * @access protected
         * @param array $data
         * @return string
         */
        protected function parseSet($data)
        {
            foreach ($data as $key => $val)
            {
                if (is_array($val) && 'exp' == $val[0])
                {
                    $set[] = $this->parseKey($key) . '=' . $this->escapeString($val[1]);
                }
                else
                {
                    $set[] = $this->parseKey($key) . '=' . $this->parseValue($val);
                }
            }

            return ' SET ' . implode(',' , $set);
        }

        /**
         * distinct分析
         * @access protected
         * @param mixed $distinct
         * @return string
         */
        protected function parseDistinct($distinct)
        {
            return !empty($distinct) ? ' DISTINCT ' : '';
        }

        /**
         * value分析
         * @access protected
         * @param mixed $value
         * @return string
         */
        protected function parseValue($value)
        {

            if (in_array($value , $this->mysqlfunc))
            {
                $value = $value;
            }
            elseif (is_string($value))
            {
                $value = '\'' . $this->escapeString($value) . '\'';
            }
            elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp')
            {
                $value = $this->escapeString($value[1]);
            }
            elseif (is_array($value))
            {
                $value = array_map(array($this , 'parseValue') , $value);
            }
            elseif (is_bool($value))
            {
                $value = $value ? '1' : '0';
            }
            elseif (is_null($value))
            {
                $value = 'null';
            }

            return $value;
        }

        /**
         * field分析
         * @access protected
         * @param mixed $fields
         * @return string
         */
        protected function parseField($fields)
        {
            if (is_string($fields) && strpos($fields , ','))
            {
                $fields = explode(',' , $fields);
            }
            if (is_array($fields))
            {
                // 完善数组方式传字段名的支持
                // 支持 'field1'=>'field2' 这样的字段别名定义
                $array = array();
                foreach ($fields as $key => $field)
                {
                    if (!is_numeric($key))
                        $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($field);
                    else
                        $array[] = $this->parseKey($field);
                }
                $fieldsStr = implode(',' , $array);
            }elseif (is_string($fields) && !empty($fields))
            {
                $fieldsStr = $this->parseKey($fields);
            }
            else
            {
                $fieldsStr = '*';
            }
            //TODO 如果是查询全部字段，并且是join的方式，那么就把要查的表加个别名，以免字段被覆盖
            return $fieldsStr;
        }

        /**
         * 删除数据
         * @access public
         * @param mixed $options 表达式
         * @return mixed
         */
        public function delete($options = array())
        {
            if (empty($options) && empty($this->options['where']))
            {
                // 如果删除条件为空 则删除当前数据对象所对应的记录
                if (!empty($this->data) && isset($this->data[$this->getPk()]))
                    return $this->delete($this->data[$this->getPk()]);
                else
                    return false;
            }
            if (is_numeric($options) || is_string($options))
            {
                // 根据主键删除记录
                $pk = $this->getPk();
                if (strpos($options , ','))
                {
                    $where[$pk] = array('IN' , $options);
                }
                else
                {
                    $where[$pk] = $options;
                }
                $pkValue = $where[$pk];
                $options = array();
                $options['where'] = $where;
            }
            // 分析表达式
            $options = $this->_parseOptions($options);
            $result = $this->dbdelete($options);

            if (false !== $result)
            {
                $data = array();
                if (isset($pkValue))
                    $data[$pk] = $pkValue;
                $this->_after_delete($data , $options);
            }
            // 返回删除记录个数
            return $result;
        }

        /**
         * 删除记录
         * @access public
         * @param array $options 表达式
         * @return false | integer
         */
        public function dbdelete($options = array())
        {
            $sql = 'DELETE FROM '
                    . $this->parseTable($options['table'])
                    . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
                    . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
                    . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
                    . $this->parseLock(isset($options['lock']) ? $options['lock'] : false)
                    . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
            return $this->query($sql);
        }

        /**
         * comment分析
         * @access protected
         * @param string $comment
         * @return string
         */
        protected function parseComment($comment)
        {
            return !empty($comment) ? ' /* ' . $comment . ' */' : '';
        }

        /**
         * 设置锁机制
         * @access protected
         * @return string
         */
        protected function parseLock($lock = false)
        {
            if (!$lock)
                return '';
            if ('ORACLE' == $this->dbType)
            {
                return ' FOR UPDATE NOWAIT ';
            }
            return ' FOR UPDATE ';
        }

        // 删除成功后的回调方法
        protected function _after_delete($data , $options)
        {
            
        }

        /**
         * 查询数据集
         * @access public
         * @param array $options 表达式参数
         * @return mixed
         */
        public function select($options = array())
        {

            if (is_string($options) || is_numeric($options))
            {
                // 根据主键查询
                $pk = $this->getPk();
                if (strpos($options , ','))
                {
                    $where[$pk] = array('IN' , $options);
                }
                else
                {
                    $where[$pk] = $options;
                }
                $options = array();
                $options['where'] = $where;
            }
            elseif (false === $options)
            { // 用于子查询 不查询只返回SQL
                $options = array();
                // 分析表达式
                $options = $this->_parseOptions($options);
                return '( ' . $this->db->buildSelectSql($options) . ' )';
            }
            // 分析表达式
            $options = $this->_parseOptions($options);

            $resultSet = $this->dbselect($options);

            return $resultSet;
        }

        /**
         * 生成查询SQL
         * @access public
         * @param array $options 表达式
         * @return string
         */
        public function buildSelectSql($options = array())
        {

            if (isset($options['page']))
            {
                // 根据页数计算limit
                if (strpos($options['page'] , ','))
                {
                    list($page , $listRows) = explode(',' , $options['page']);
                }
                else
                {
                    $page = $options['page'];
                }
                $page = $page ? $page : 1;
                $listRows = isset($listRows) ? $listRows : (is_numeric($options['limit']) ? $options['limit'] : 20);
                $offset = $listRows * ((int) $page - 1);
                $options['limit'] = $offset . ',' . $listRows;
            }

            $sql = $this->dbparseSql($this->selectSql , $options);
            $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);

            return $sql;
        }

        /**
         * 查找记录
         * @access public
         * @param array $options 表达式
         * @return mixed
         */
        public function dbselect($options = array())
        {
            $sql = $this->buildSelectSql($options);
            $result = $this->query($sql);
            if (!$result)
            {
                return $result;
            }
            $data = $this->sql_obj->fetchAll(2);
            return $data;
        }

        // 查询成功后的回调方法
        protected function _after_select(&$resultSet , $options)
        {
            
        }

        /**
         * 生成查询SQL 可用于子查询
         * @access public
         * @param array $options 表达式参数
         * @return string
         */
        public function buildSql($options = array())
        {
            // 分析表达式
            $options = $this->_parseOptions($options);
            return '( ' . $this->db->buildSelectSql($options) . ' )';
        }

        /**
         * 分析表达式
         * @access protected
         * @param array $options 表达式参数
         * @return array
         */
        protected function _parseOptions($options = array())
        {

            if (is_array($options))
                $options = array_merge($this->options , $options);
            // 查询过后清空sql表达式组装 避免影响下次查询
            $this->options = array();
            if (!isset($options['table']))
            {
                // 自动获取表名
                $options['table'] = $this->getTableName();

                $fields = $this->fields;
            }
            else
            {
                // 指定数据表 则重新获取字段列表 但不支持类型检测
                $fields = $this->getDbFields();
            }
            if (!empty($options['alias']))
            {
                $options['table'] .= ' ' . $options['alias'];
            }

            // 字段类型验证
            if (isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join']))
            {
                // 对数组查询条件进行字段类型检查
                foreach ($options['where'] as $key => $val)
                {
                    $key = trim($key);
                    if (in_array($key , $fields , true))
                    {
                        if (is_scalar($val))
                        {
                            $this->_parseType($options['where'] , $key);
                        }
                    }
                    elseif ('_' != substr($key , 0 , 1) && false === strpos($key , '.') && false === strpos($key , '(') && false === strpos($key , '|') && false === strpos($key , '&'))
                    {
                        unset($options['where'][$key]);
                    }
                }
            }
            return $options;
        }

        /**
         * 数据类型检测
         * @access protected
         * @param mixed $data 数据
         * @param string $key 字段名
         * @return void
         */
        protected function _parseType(&$data , $key)
        {
            if (empty($this->options['bind'][':' . $key]))
            {
                $fieldType = strtolower($this->fields['_type'][$key]);
                if (false === strpos($fieldType , 'bigint') && false !== strpos($fieldType , 'int'))
                {
                    $data[$key] = intval($data[$key]);
                }
                elseif (false !== strpos($fieldType , 'float') || false !== strpos($fieldType , 'double'))
                {
                    $data[$key] = floatval($data[$key]);
                }
                elseif (false !== strpos($fieldType , 'bool'))
                {
                    $data[$key] = (bool) $data[$key];
                }
            }
        }

        /**
         * 查询数据
         * @access public
         * @param mixed $options 表达式参数
         * @return mixed
         */
        public function find($options = array())
        {
            if (is_numeric($options) || is_string($options))
            {
                $where[$this->getPk()] = $options;
                $options = array();
                $options['where'] = $where;
            }
            // 总是查找一条记录
            $options['limit'] = 1;
            // 分析表达式
            $options = $this->_parseOptions($options);
            $resultSet = $this->dbselect($options);
            if (is_array($resultSet) && count($resultSet) > 0)
                return $resultSet[0];
            return $resultSet;
        }

        // 查询成功的回调方法
        protected function _after_find(&$result , $options)
        {
            
        }

        protected function returnResult($data , $type = '')
        {
            if ($type)
            {
                if (is_callable($type))
                {
                    return call_user_func($type , $data);
                }
                switch (strtolower($type))
                {
                    case 'json':
                        return json_encode($data);
                    case 'xml':
                        return xml_encode($data);
                }
            }
            return $data;
        }

        /**
         * 处理字段映射
         * @access public
         * @param array $data 当前数据
         * @param integer $type 类型 0 写入 1 读取
         * @return array
         */
        public function parseFieldsMap($data , $type = 1)
        {
            // 检查字段映射
            if (!empty($this->_map))
            {
                foreach ($this->_map as $key => $val)
                {
                    if ($type == 1)
                    { // 读取
                        if (isset($data[$val]))
                        {
                            $data[$key] = $data[$val];
                            unset($data[$val]);
                        }
                    }
                    else
                    {
                        if (isset($data[$key]))
                        {
                            $data[$val] = $data[$key];
                            unset($data[$key]);
                        }
                    }
                }
            }
            return $data;
        }

        /**
         * 设置记录的某个字段值
         * 支持使用数据库字段和方法
         * @access public
         * @param string|array $field  字段名
         * @param string $value  字段值
         * @return boolean
         */
        public function setField($field , $value = '')
        {
            if (is_array($field))
            {
                $data = $field;
            }
            else
            {
                $data[$field] = $value;
            }
            return $this->save($data);
        }

        /**
         * 字段值增长
         * @access public
         * @param string $field  字段名
         * @param integer $step  增长值
         * @return boolean
         */
        public function setInc($field , $step = 1)
        {
            return $this->setField($field , array('exp' , $field . '+' . $step));
        }

        /**
         * 字段值减少
         * @access public
         * @param string $field  字段名
         * @param integer $step  减少值
         * @return boolean
         */
        public function setDec($field , $step = 1)
        {
            return $this->setField($field , array('exp' , $field . '-' . $step));
        }

        /**
         * 获取一条记录的某个字段值
         * @access public
         * @param string $field  字段名
         * @param string $spea  字段数据间隔符号 NULL返回数组
         * @return mixed
         */
        public function getField($field , $sepa = null)
        {
            $options['field'] = $field;
            $options = $this->_parseOptions($options);
            $field = trim($field);
            if (strpos($field , ','))
            { // 多字段
                if (!isset($options['limit']))
                {
                    $options['limit'] = is_numeric($sepa) ? $sepa : '';
                }
                $resultSet = $this->db->select($options);
                if (!empty($resultSet))
                {
                    $_field = explode(',' , $field);
                    $field = array_keys($resultSet[0]);
                    $key = array_shift($field);
                    $key2 = array_shift($field);
                    $cols = array();
                    $count = count($_field);
                    foreach ($resultSet as $result)
                    {
                        $name = $result[$key];
                        if (2 == $count)
                        {
                            $cols[$name] = $result[$key2];
                        }
                        else
                        {
                            $cols[$name] = is_string($sepa) ? implode($sepa , $result) : $result;
                        }
                    }
                    return $cols;
                }
            }
            else
            { // 查找一条记录
                // 返回数据个数
                if (true !== $sepa)
                {// 当sepa指定为true的时候 返回所有数据
                    $options['limit'] = is_numeric($sepa) ? $sepa : 1;
                }
                $result = $this->dbselect($options);
            }
            return $result;
        }

        /**
         * 创建数据对象 但不保存到数据库
         * @access public
         * @param mixed $data 创建数据
         * @param string $type 状态
         * @return mixed
         */
        public function create($data = '' , $type = '')
        {
            // 如果没有传值默认取POST数据
            if (empty($data))
            {
                $data = $_POST;
            }
            elseif (is_object($data))
            {
                $data = get_object_vars($data);
            }
            // 验证数据
            if (empty($data) || !is_array($data))
            {
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }

            // 检查字段映射
            $data = $this->parseFieldsMap($data , 0);

            // 状态
            $type = $type ? $type : (!empty($data[$this->getPk()]) ? self::MODEL_UPDATE : self::MODEL_INSERT);

            // 检测提交字段的合法性
            if (isset($this->options['field']))
            { // $this->field('field1,field2...')->create()
                $fields = $this->options['field'];
                unset($this->options['field']);
            }
            elseif ($type == self::MODEL_INSERT && isset($this->insertFields))
            {
                $fields = $this->insertFields;
            }
            elseif ($type == self::MODEL_UPDATE && isset($this->updateFields))
            {
                $fields = $this->updateFields;
            }
            if (isset($fields))
            {
                if (is_string($fields))
                {
                    $fields = explode(',' , $fields);
                }
                // 判断令牌验证字段
                if ($this->C('TOKEN_ON'))
                    $fields[] = $this->C('TOKEN_NAME');
                foreach ($data as $key => $val)
                {
                    if (!in_array($key , $fields))
                    {
                        unset($data[$key]);
                    }
                }
            }

            // 数据自动验证
            if (!$this->autoValidation($data , $type))
                return false;

            // 表单令牌验证
            if ($this->C('TOKEN_ON') && !$this->autoCheckToken($data))
            {
                $this->error = L('_TOKEN_ERROR_');
                return false;
            }

            // 验证完成生成数据对象
            if ($this->autoCheckFields)
            { // 开启字段检测 则过滤非法字段数据
                $fields = $this->getDbFields();
                foreach ($data as $key => $val)
                {
                    if (!in_array($key , $fields))
                    {
                        unset($data[$key]);
                    }
                    elseif (MAGIC_QUOTES_GPC && is_string($val))
                    {
                        $data[$key] = stripslashes($val);
                    }
                }
            }

            // 创建完成对数据进行自动处理
            $this->autoOperation($data , $type);
            // 赋值当前数据对象
            $this->data = $data;
            // 返回创建的数据以供其他调用
            return $data;
        }

        // 自动表单令牌验证
        // TODO  ajax无刷新多次提交暂不能满足
        public function autoCheckToken($data)
        {
            if ($this->C('TOKEN_ON'))
            {
                $name = $this->C('TOKEN_NAME');
                if (!isset($data[$name]) || !isset($_SESSION[$name]))
                { // 令牌数据无效
                    return false;
                }

                // 令牌验证
                list($key , $value) = explode('_' , $data[$name]);
                if ($value && $_SESSION[$name][$key] === $value)
                { // 防止重复提交
                    unset($_SESSION[$name][$key]); // 验证完成销毁session
                    return true;
                }
                // 开启TOKEN重置
                if ($this->C('TOKEN_RESET'))
                    unset($_SESSION[$name][$key]);
                return false;
            }
            return true;
        }

        /**
         * 使用正则验证数据
         * @access public
         * @param string $value  要验证的数据
         * @param string $rule 验证规则
         * @return boolean
         */
        public function regex($value , $rule)
        {
            $validate = array(
                'require' => '/.+/' ,
                'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/' ,
                'url' => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/' ,
                'currency' => '/^\d+(\.\d+)?$/' ,
                'number' => '/^\d+$/' ,
                'zip' => '/^\d{6}$/' ,
                'integer' => '/^[-\+]?\d+$/' ,
                'double' => '/^[-\+]?\d+(\.\d+)?$/' ,
                'english' => '/^[A-Za-z]+$/' ,
            );
            // 检查是否有内置的正则表达式
            if (isset($validate[strtolower($rule)]))
                $rule = $validate[strtolower($rule)];
            return preg_match($rule , $value) === 1;
        }

        /**
         * 解析SQL语句
         * @access public
         * @param string $sql  SQL指令
         * @param boolean $parse  是否需要解析SQL
         * @return string
         */
        protected function parseSql($sql , $parse)
        {
            if (true === $parse)
            {
                $options = $this->_parseOptions();
                $sql = $this->dbparseSql($sql , $options);
            }
            elseif (is_array($parse))
            { // SQL预处理
                $sql = vsprintf($sql , $parse);
            }
            else
            {
                $sql = strtr($sql , array('__TABLE__' => $this->getTableName() , '__PREFIX__' => $this->C('DB_PREFIX')));
            }
            return $sql;
        }

        /**
         * 替换SQL语句中表达式
         * @access public
         * @param array $options 表达式
         * @return string
         */
        public function dbparseSql($sql , $options = array())
        {
            $sql = str_replace(
                    array('%TABLE%' , '%DISTINCT%' , '%FIELD%' , '%JOIN%' , '%WHERE%' , '%GROUP%' , '%HAVING%' , '%ORDER%' , '%LIMIT%' , '%UNION%' , '%COMMENT%') , array(
                $this->parseTable($options['table']) ,
                $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false) ,
                $this->parseField(!empty($options['field']) ? $options['field'] : '*') ,
                $this->parseJoin(!empty($options['join']) ? $options['join'] : '') ,
                $this->parseWhere(!empty($options['where']) ? $options['where'] : '') ,
                $this->parseGroup(!empty($options['group']) ? $options['group'] : '') ,
                $this->parseHaving(!empty($options['having']) ? $options['having'] : '') ,
                $this->parseOrder(!empty($options['order']) ? $options['order'] : '') ,
                $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '') ,
                $this->parseUnion(!empty($options['union']) ? $options['union'] : '') ,
                $this->parseComment(!empty($options['comment']) ? $options['comment'] : '')
                    ) , $sql);

            return $sql;
        }

        /**
         * union分析
         * @access protected
         * @param mixed $union
         * @return string
         */
        protected function parseUnion($union)
        {
            if (empty($union))
                return '';
            if (isset($union['_all']))
            {
                $str = 'UNION ALL ';
                unset($union['_all']);
            }
            else
            {
                $str = 'UNION ';
            }
            foreach ($union as $u)
            {
                $sql[] = $str . (is_array($u) ? $this->buildSelectSql($u) : $u);
            }
            return implode(' ' , $sql);
        }

        /**
         * having分析
         * @access protected
         * @param string $having
         * @return string
         */
        protected function parseHaving($having)
        {
            return !empty($having) ? ' HAVING ' . $having : '';
        }

        /**
         * 得到完整的数据表名
         * @access public
         * @return string
         */
        public function getTableName()
        {
            if (empty($this->trueTableName))
            {
                $tableName = !empty($this->tablePrefix) ? $this->tablePrefix : '';
                if (!empty($this->tableName))
                {
                    $tableName .= $this->tableName;
                }
                else
                {
                    $tableName .= $this->parse_name($this->name);
                }
                $this->trueTableName = strtolower($tableName);
            }
            return (!empty($this->dbName) ? $this->dbName . '.' : '') . $this->trueTableName;
        }

        /**
         * 字符串命名风格转换
         * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
         * @param string $name 字符串
         * @param integer $type 转换类型
         * @return string
         */
        function parse_name($name , $type = 0)
        {
            if ($type)
            {
                return ucfirst(preg_replace("/_([a-zA-Z])/e" , "strtoupper('\\1')" , $name));
            }
            else
            {
                return strtolower(trim(preg_replace("/[A-Z]/" , "_\\0" , $name) , "_"));
            }
        }

        /**
         * 返回模型的错误信息
         * @access public
         * @return string
         */
        public function getError()
        {
            return $this->error;
        }

        // 鉴于getLastSql比较常用 增加_sql 别名
        public function _sql()
        {
            return $this->lastsql;
        }

        /**
         * 获取主键名称
         * @access public
         * @return string
         */
        public function getPk()
        {
            return isset($this->fields['_pk']) ? $this->fields['_pk'] : $this->pk;
        }

        /**
         * 获取数据表字段信息
         * @access public
         * @return array
         */
        public function getDbFields()
        {
            if (isset($this->options['table']))
            {// 动态指定表名
                $fields = $this->db->getFields($this->options['table']);
                return $fields ? array_keys($fields) : false;
            }

            if ($this->fields)
            {
                $fields = $this->fields;
                unset($fields['_autoinc'] , $fields['_pk'] , $fields['_type'] , $fields['_version']);
                return $fields;
            }
            return false;
        }

        /**
         * 设置数据对象值
         * @access public
         * @param mixed $data 数据
         * @return Model
         */
        public function data($data = '')
        {
            if ('' === $data && !empty($this->data))
            {
                return $this->data;
            }
            if (is_object($data))
            {
                $data = get_object_vars($data);
            }
            elseif (is_string($data))
            {
                parse_str($data , $data);
            }
            elseif (!is_array($data))
            {
                throw_exception(L('_DATA_TYPE_INVALID_'));
            }
            $this->data = $data;
            return $this;
        }

        /**
         * 查询SQL组装 join
         * @access public
         * @param mixed $join
         * @return Model
         */
        public function join($join)
        {
            if (is_array($join))
            {
                $this->options['join'] = $join;
            }
            elseif (!empty($join))
            {
                $this->options['join'][] = $join;
            }
            return $this;
        }

        /**
         * 查询SQL组装 union
         * @access public
         * @param mixed $union
         * @param boolean $all
         * @return Model
         */
        public function union($union , $all = false)
        {
            if (empty($union))
                return $this;
            if ($all)
            {
                $this->options['union']['_all'] = true;
            }
            if (is_object($union))
            {
                $union = get_object_vars($union);
            }
            // 转换union表达式
            if (is_string($union))
            {
                $options = $union;
            }
            elseif (is_array($union))
            {
                if (isset($union[0]))
                {
                    $this->options['union'] = array_merge($this->options['union'] , $union);
                    return $this;
                }
                else
                {
                    $options = $union;
                }
            }
            else
            {
                throw_exception(L('_DATA_TYPE_INVALID_'));
            }
            $this->options['union'][] = $options;
            return $this;
        }

        /**
         * 查询缓存
         * @access public
         * @param mixed $key
         * @param integer $expire
         * @param string $type
         * @return Model
         */
        public function cache($key = true , $expire = null , $type = '')
        {
            if (false !== $key)
                $this->options['cache'] = array('key' => $key , 'expire' => $expire , 'type' => $type);
            return $this;
        }

        /**
         * 指定查询字段 支持字段排除
         * @access public
         * @param mixed $field
         * @param boolean $except 是否排除
         * @return Model
         */
        public function field($field , $except = false)
        {
            if (true === $field)
            {// 获取全部字段
                $fields = $this->getDbFields();
                $field = $fields ? $fields : '*';
            }
            elseif ($except)
            {// 字段排除
                if (is_string($field))
                {
                    $field = explode(',' , $field);
                }
                $fields = $this->getDbFields();
                $field = $fields ? array_diff($fields , $field) : $field;
            }
            $this->options['field'] = $field;
            return $this;
        }

        /**
         * 调用命名范围
         * @access public
         * @param mixed $scope 命名范围名称 支持多个 和直接定义
         * @param array $args 参数
         * @return Model
         */
        public function scope($scope = '' , $args = NULL)
        {
            if ('' === $scope)
            {
                if (isset($this->_scope['default']))
                {
                    // 默认的命名范围
                    $options = $this->_scope['default'];
                }
                else
                {
                    return $this;
                }
            }
            elseif (is_string($scope))
            { // 支持多个命名范围调用 用逗号分割
                $scopes = explode(',' , $scope);
                $options = array();
                foreach ($scopes as $name)
                {
                    if (!isset($this->_scope[$name]))
                        continue;
                    $options = array_merge($options , $this->_scope[$name]);
                }
                if (!empty($args) && is_array($args))
                {
                    $options = array_merge($options , $args);
                }
            }
            elseif (is_array($scope))
            { // 直接传入命名范围定义
                $options = $scope;
            }

            if (is_array($options) && !empty($options))
            {
                $this->options = array_merge($this->options , array_change_key_case($options));
            }
            return $this;
        }

        /**
         * 指定查询条件 支持安全过滤
         * @access public
         * @param mixed $where 条件表达式
         * @param mixed $parse 预处理参数
         * @return Model
         */
        public function where($where , $parse = null)
        {
            if (!is_null($parse) && is_string($where))
            {
                if (!is_array($parse))
                {
                    $parse = func_get_args();
                    array_shift($parse);
                }
                $parse = array_map(array($this->db , 'escapeString') , $parse);
                $where = vsprintf($where , $parse);
            }
            elseif (is_object($where))
            {
                $where = get_object_vars($where);
            }
            if (is_string($where) && '' != $where)
            {
                $map = array();
                $map['_string'] = $where;
                $where = $map;
            }
            if (isset($this->options['where']))
            {
                $this->options['where'] = array_merge($this->options['where'] , $where);
            }
            else
            {
                $this->options['where'] = $where;
            }
            return $this;
        }

        /**
         * 指定查询数量
         * @access public
         * @param mixed $offset 起始位置
         * @param mixed $length 查询数量
         * @return Model
         */
        public function limit($offset , $length = null)
        {
            $this->options['limit'] = is_null($length) ? $offset : $offset . ',' . $length;
            return $this;
        }

        /**
         * 指定分页
         * @access public
         * @param mixed $page 页数
         * @param mixed $listRows 每页数量
         * @return Model
         */
        public function page($page , $listRows = null)
        {
            $this->options['page'] = is_null($listRows) ? $page : $page . ',' . $listRows;
            return $this;
        }

        /**
         * 查询注释
         * @access public
         * @param string $comment 注释
         * @return Model
         */
        public function comment($comment)
        {
            $this->options['comment'] = $comment;
            return $this;
        }

        /**
         * 设置模型的属性值
         * @access public
         * @param string $name 名称
         * @param mixed $value 值
         * @return Model
         */
        public function setProperty($name , $value)
        {
            if (property_exists($this , $name))
                $this->$name = $value;
            return $this;
        }

        /**
         * SQL指令安全过滤
         * @access public
         * @param string $str  SQL字符串
         * @return string
         */
        public function escapeString($str)
        {
            return addslashes($str);
        }

        /**
         *
          /* *
         *  临时未实现方法 读取语言
         */
        private function L($lang)
        {
            return $lang;
        }

        /*         *
         *  临时未实现方法 读取配置
         */

        function C($key)
        {
            return '';
        }

    }
    