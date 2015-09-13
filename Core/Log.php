<?php

    namespace Core;

    /**
     * 回调函数
     * @author lishengyou
     * 最后修改时间 2015年2月11日 下午4:29:35
     *
     */
    class Log
    {

        /**
         * 记录DEBUG类型日志
         * @param type $text
         * @author Lms 2015年4月27日 15:52:11
         */
        public static function logDebug($text)
        {

            return self::setLog($text , 'debug');
        }

        /**
         * 记录Info类型日志
         * @param type $text
         * @author Lms 2015年4月27日 15:53:12
         */
        public static function logInfo($text)
        {

            return self::setLog($text , 'info');
        }

        /**
         * 记录日志类型的日志
         * @author lishengyou
         * 最后修改时间 2015年6月3日 上午10:10:02
         * @param type $text
         * @return Ambigous <boolean, number>
         */
        public static function logError($text)
        {
            return self::setLog($text , 'error');
        }

        /**
         * 根据条件获取日志
         * @param type $id  数据库唯一ID 传递后将获取它之后的日志,传空则为获取最后20条日志
         * @param type $unique_id 筛选客户端唯一ID 
         * @return array('count' => int , 'data' => array());
         */
        public static function getLog($id = '' , $unique_id = '')
        {
            $LogDb = new \Common\Model\Erp\Log();
            $where = new \Zend\Db\Sql\Where();
            //是否筛选客户端
            if (!empty($unique_id))
                $where->equalTo('unique_id' , $unique_id);
            $order = '';
            //将获取大于该的的日志
            if (isset($id) && is_numeric($id))
            {
                $where->greaterThan('id' , $id);
                $limit = 100;
            }
            else
            {
                //获取最新20条
                $limit = 100;
                $order = 'id desc';
            }
            $count = $LogDb->getCount(array());
            $result = $LogDb->getData($where , array() , $limit , 0 , $order);
            $result = is_array($result) ? $result : array();
            //在获取数据为DESC时 内容是倒序的，需要更正
            if (!empty($order))
                asort($result);
            $new_id = end($result)['id'];

            if (is_numeric($new_id))
                $id = $new_id;


            foreach ($result as &$info)
            {
                $info['text'] = htmlspecialchars($info['text']);
            }

            return array('count' => $count , 'list' => $result , 'id' => $id);
        }
		private static $_logDatas = array();
        private static function setLog($text , $type = 'debug')
        {

            $text = is_array($text)?var_export($text , true):$text;
            //获取该类型的日志是否允许记录
            $log_type = "log_$type";//配置文件格式 log_debug
            $log_state = \Core\Config::get('log:' . $log_type);
            //该类型的配置未被开启 不记录日志
            if (!$log_state)
                return false;
            //排除重复
            foreach (self::$_logDatas as $value){
            	if($value['type'] == $type && $value['text'] == $text){
            		return true;
            		break;
            	}
            }
            $data['unique_id'] = self::getIp();
            $data['text'] = $text;
            $data['type'] = $type;
            $data['create_time'] = date('Y-m-d H:i:s');
            self::$_logDatas[] = $data;
            return true;
        }
		public static function saveAllLog(){
			if(!empty(self::$_logDatas)){
				$LogDb = new \Common\Model\Erp\Log();
				$result = $LogDb->insert(self::$_logDatas);//写入日志
			}
		}
        public static function getIp()
        {
            $ip = \Common\Helper\Http\Request::getClientIp(0 , true);

            if ($ip == '127.0.0.1' || $ip == '0.0.0.0')
            {
                $ip = gethostbyname('');//获取局域网IP
            }
            return $ip;
        }

    }
    /**
     * 注册一个脚本结束回调来写日志
     */
    register_shutdown_function(function(){
    	Log::saveAllLog();
    });