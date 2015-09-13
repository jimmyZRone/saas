<?php

    /**
     * 获取输入参数 支持过滤和默认值
     * 使用方法:
     * <code>
     * queryString('id',0); 获取id参数 自动判断get或者post
     * queryString('post.name','','htmlspecialchars'); 获取$_POST['name']
     * queryString('get.'); 获取$_GET
     * </code>
     * @param string $name 变量的名称 支持指定类型
     * @param mixed $default 不存在的时候默认值
     * @param mixed $filter 参数过滤方法
     * @param mixed $datas 要获取的额外数据源
     * @return mixed
     * @author Lms 2015年4月27日 13:28:24
     */
    function I($name , $default = '' , $filter = null , $datas = null)
    {
        $value = \Common\Helper\Http\Request::queryString($name , $default , $filter , $datas);
        return $value;
    }

    /**
     * 方便调试少打几个字咯
     * @author too|编写注释时间 2015年5月28日 下午1:04:32
     */
    function P($param , $tag = 0)
    {
        if ($tag === 0)
        {
            return print_r($param);
        }
        return var_dump($param);
    }

    /**
     * 验证是否为正确时间戳
     * @param type $time
     */
    function is_time($time)
    {
        return strlen($time) == 10 && is_zzs($time) ? true : false;
    }

    /**
     *  获取自定义扩展SQL对象
     * @param type $sql SQL语句
     * @return \Zend\Db\Sql\Expression
     */
    function getExpSql($sql)
    {
        return new \Zend\Db\Sql\Expression($sql);
    }

    /**
     * 验证是否为正整数
     * @param type $num
     */
    function is_zzs($num)
    {

        //验证是否为数字
        if (!is_numeric($num))
            return false;
        $num = (string) $num;
        $count = strlen($num);
        //验证是否有负数或者小数
        for ($i = 0; $i < $count; $i++)
        {
            if (!is_numeric($num{$i}))
                return false;
        }
        return true;
    }

    /**
     *  得到表model对象
     * @param type $table
     * @return Common\Model\Erp
     * Lms 2015年5月18日 10:01:44
     */
    function M($table)
    {
        $table = explode('_' , $table);
        $table = implode('' , array_map('ucfirst' , $table));

        $db_drive = "\Common\Model\Erp\\$table";

        if (!class_exists($db_drive))
            return false;
        $model = new $db_drive();
        return $model;
    }

    /**

     *  得到表Helper对象
     * @param type $class
     * @return Common\Helper
     * Lms 2015年5月18日 09:58:02
     */
    function H($class)
    {
        $db_drive = "\Common\Helper\Erp\\$class";
        if (!class_exists($db_drive))
            return false;
        $model = new $db_drive();
        return $model;
    }

    function mkdirs($dir , $model = "0777")
    {
        return is_dir($dir) or ( mkdirs(dirname($dir) , $model) and mkdir($dir , $model));
    }

    /**
     *  批量验证变量的长度是否为0
     * @return boolean
     */
    function emptys()
    {
        $fun = func_get_args();
        foreach ($fun as $v)
        {

            if (is_array($v) && count($v) == 0)
            {
                return true;
            }

            if (!is_array($v) && strlen($v) <= 0)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * API返回成功结果
     * @param type $data array() 结果集
     * @param type true|true 是否需要压缩返回数据 默认false
     * @author Lms 2015年4月27日 14:39:36
     */
    function return_success($data = array() , $is_gzip = false)
    {

        return result($data , '1' , 'OK' , $is_gzip);
    }

    /**

     * API返回失败结果
     * @param type $code 错误码至少为三维数字组成
     * @param type $info  错误描述信息
     * @return type null
     * @author Lms 2015年4月27日 14:39:36
     */
    function return_error($code , $ext_info = '')
    {

        $error_msg = \App\Api\Mvc\Controller\ErrorController::getErrorMsg($code);
        $code = $error_msg['code'];
        $info = $error_msg['info'];
        $debug = debug_backtrace(null , 0);
        $text = "错误位置:{$debug[0]['file']}第{$debug[0]['line']}行";
        logDebug("接口返回错误[$code]:$info($ext_info)\r\n$text");
        return result(array() , $code , $info . $ext_info);
    }

    /**
     *  输出JSON格式最终结果
     * @param type $result 成功的结果集
     * @param type $code 结果代码 0为成功
     * @param type $info 描述
     */
    function result($result = array() , $code = '1' , $info = '' , $is_gzip = false)
    {
        $data = array();
        $data['error']['code'] = $code;
        $data['error']['info'] = $info;
        $data['data'] = $result;
        //zip输出
        if ($is_gzip && extension_loaded('zlib') && !headers_sent() && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'] , 'gzip') !== FALSE)
        {
            ob_start('ob_gzhandler');
            echo(json_encode($data));
            ob_end_flush();
            exit;
        }
        exit(json_encode($data));
    }

    /**
     *  打印调试格式化好的HTML内容 传参个数不限
     *  @author Lms 2015年4月27日 15:33:38
     */
    function dump()
    {
        @header("Content-type : text/html; charset=UTF-8");
        $funlist = func_get_args();
        foreach ($funlist as $key => $val)
        {
            if (is_object($val))
            {
                echo "<pre>";
                var_dump($val);
                echo "</pre>";
            }
            else
            {
                do_dump($val , $key + 1);
            }
            echo "<hr/>";
        }
        $debug = debug_backtrace(null , 0);
        echo "打印调用位置:{$debug[0]['file']}第{$debug[0]['line']}行";
        echo "<hr/>";
    }

    /**
     *  选择数据库和数据表
     * @param type $table
     * @return Common\Model\Model
     */
    function T($table)
    {
        // \Common\Helper\
        $model = new \Common\Helper\Model($table);
        return $model;
    }

    /**
     *  缓存增删改
     * @param type $key 储存键
     * @param type $val   储存值 传递null为删除缓存
     * @param type $time 缓存时间 （单位秒）
     * @return boolean|string
     * @author Lms 2015年4月27日 15:45:01
     */
    function S($key , $val = '' , $time = '60')
    {
        if (!is_string($time) || strlen($key) <= 0)
            return false;
        if ($val === null)
        {
//删除缓存
            $cache = true;
        }
        elseif (strlen($val) > 0)
        {
//设置缓存
            $cache = true;
        }
        else
        {
//读取缓存
            $cache = false;
        }
        return $cache;
    }

    /**
     * 记录DEBUG类型日志
     * @param type $text
     * @author Lms 2015年4月27日 15:52:11
     */
    function logDebug($text)
    {
        \Core\Log::logDebug($text);
    }

    /**
     * 记录Info类型日志
     * @param type $text
     * @author Lms 2015年4月27日 15:52:11
     */
    function logInfo($text)
    {
        \Core\Log::logInfo($text);
    }

    function logError($text)
    {
        \Core\Log::logError($text);
    }

    /**
     * 加密算法之加密
     * @param String $string 需要加密的字串
     * @param String $skey 加密EKY
     * @author Lms
     * @date 2015年4月28日 09:34:42
     * @update 2014-10-10 10:10
     * @return String
     */
    function encode($string = '' , $skey = 'JoooZoInLouDi')
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_=@.$*";
        $length = strlen($chars);
        $nh = rand(0 , $length - 1);
        $ch = $chars[$nh];
        $mdKey = md5($skey . $ch);
        $strLocation = $nh % 8;
        $mdKey = substr($mdKey , $strLocation , $strLocation + 7);
        $txt = base64_encode($string);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++)
        {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = ($nh + strpos($chars , $txt[$i]) + ord($mdKey[$k++])) % $length;
            $tmp .= $chars[$j];
        }
        $str = urlencode($ch . $tmp);
        return $str;
    }

    /**
     * 加密算法之解密
     * @param String $string 需要解密的字串
     * @param String $skey 解密KEY
     * @author Lms
     * @date 2015年4月28日 09:34:28
     * @return String
     */
    function decode($string = '' , $skey = 'JoooZoInLouDi')
    {
        $txt = urldecode($string);
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_=@.$*";
        $length = strlen($chars);
        $ch = $txt[0];
        $nh = strpos($chars , $ch);
        $mdKey = md5($skey . $ch);
        $mdKey = substr($mdKey , $nh % 8 , $nh % 8 + 7);
        $txt = substr($txt , 1);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++)
        {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = strpos($chars , $txt[$i]) - $nh - ord($mdKey[$k++]);
            while ($j < 0)
                $j+=$length;
            $tmp .= $chars[$j];
        }
        return base64_decode($tmp);
    }

    /**
     * 调试用,得到上次执行的SQL语句
     * @param type $obj
     * @return type
     */
    function SQL($obj)
    {
        return str_replace('"' , '`' , @$obj->getSqlString());
    }

    /**
     *  取出数组里的指定key 返回一个新数组
     * @param type $arr 原始数组
     * @param type $key 数组里的key
     * @return array
     */
    function getArrayValue($arr , $key)
    {
        $newArr = array();
        if (!is_array($arr))
            return $newArr;

        foreach ($arr as $val)
        {

            if (!isset($val[$key]))
                continue;

            $newArr[] = $val[$key];
        }

        return $newArr;
    }

    /**
     *  按照指定的key将数组归类,返回一个新的数组
     * @param type $arr 原始数组
     * @param type $key 数组里的key
     * @param type $value  原数组的key，新数组的值
     * @return array
     *  LMS 2015年5月13日 14:39:33
     */
    function getArrayKeyClassification($arr , $key , $value = '')
    {
        $newArr = array();
        if (!is_array($arr))
            return $newArr;
        foreach ($arr as $val)
        {
            if (!isset($val[$key]))
                continue;
            if (strlen($value) == 0)
                $newArr[$val[$key]][] = $val;
            elseif (isset($val[$value]))
                $newArr[$val[$key]] = $val[$value];
            else
                $newArr[$val[$key]] = $val;
        }
        return $newArr;
    }

    /**
     * Param verification缩写
     * 验证指定参数 是否传递
     * @param type $params 参数列表数组 支持格式 get.key或者post.key或者key
     * @param type $request 在该数组里查找参数验证，不传默认为自动验证 get或者post
     */
    function PV($params , $request = null)
    {

        $error_params = array();

        $params = is_array($params) ? $params : array($params);

        foreach ($params as $key)
        {

            if (!isset($key{0}))
                continue;

            if (is_array($request))
            {
                if (!isset($request[$key]) || !isset($request[$key]{0}))
                    $error_params[] = $key;
            }else
            {
                if (emptys(I($key)))
                    $error_params[] = $key;
            }
        }
        if (count($error_params) == 0)
        {
            return true;
        }
        $result = implode('] [' , $error_params);
        return_error('100' , "[$result]");
    }

    /**
     *  打印调试，HTML标准信息 已被dump封装 可直接使用dump 函数
     * @param type $var
     * @param type $var_name
     * @param type $indent
     * @param string $reference
     * @author Lms 2015年4月27日 15:33:50
     */
    function do_dump(&$var , $var_name = NULL , $indent = NULL , $reference = NULL)
    {
        $codetype = 'UTF-8';
        $do_dump_indent = "<span style='color:#666666;'>|</span> &nbsp;&nbsp; ";
        $reference = $reference . $var_name;
        $keyvar = 'the_do_dump_recursion_protection_scheme';
        $keyname = 'referenced_object_name';

// So this is always visible and always left justified and readable
        echo "<div style='text-align:left; background-color:white; font: 100% monospace; color:black;'>";

        if (is_array($var) && isset($var[$keyvar]))
        {
            $real_var = &$var[$keyvar];
            $real_name = &$var[$keyname];
            $type = ucfirst(gettype($real_var));
            echo "$indent$var_name <span style='color:#666666'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
        }
        else
        {
            $var = array($keyvar => $var , $keyname => $reference);
            $avar = &$var[$keyvar];

            $type = ucfirst(gettype($avar));
            if ($type == "String")
                $type_color = "<span style='color:green'>";
            elseif ($type == "Integer")
                $type_color = "<span style='color:red'>";
            elseif ($type == "Double")
            {
                $type_color = "<span style='color:#0099c5'>";
                $type = "Float";
            }
            elseif ($type == "Boolean")
                $type_color = "<span style='color:#92008d'>";
            elseif ($type == "NULL")
                $type_color = "<span style='color:black'>";

            if (is_array($avar))
            {
                $count = count($avar);
                echo "$indent" . ($var_name ? "$var_name => " : "") . "<span style='color:#666666'>$type ($count)</span><br>$indent(<br>";
                $keys = array_keys($avar);
                foreach ($keys as $name)
                {
                    $value = &$avar[$name];
                    do_dump($value , "['$name']" , $indent . $do_dump_indent , $reference);
                }
                echo "$indent)<br>";
            }
            elseif (is_object($avar))
            {
                echo "$indent$var_name <span style='color:#666666'>$type</span><br>$indent(<br>";
                foreach ($avar as $name => $value)
                    do_dump($value , "$name" , $indent . $do_dump_indent , $reference);
                echo "$indent)<br>";
            }
            elseif (is_int($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . mb_strlen($avar , $codetype) . ")</span> $type_color" . htmlspecialchars($avar) . "</span><br>";
            elseif (is_string($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . mb_strlen($avar , $codetype) . ")</span> $type_color\"" . htmlspecialchars($avar) . "\"</span><br>";
            elseif (is_float($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . mb_strlen($avar , $codetype) . ")</span> $type_color" . htmlspecialchars($avar) . "</span><br>";
            elseif (is_bool($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . mb_strlen($avar , $codetype) . ")</span> $type_color" . ($avar == 1 ? "TRUE" : "FALSE") . "</span><br>";
            elseif (is_null($avar))
                echo "$indent$var_name = <span style='color:#666666'>$type(" . mb_strlen($avar , $codetype) . ")</span> {$type_color}NULL</span><br>";
            else
                echo "$indent$var_name = <span style='color:#666666'>$type(" . mb_strlen($avar , $codetype) . ")</span> " . htmlspecialchars($avar) . "<br>";

            $var = $var[$keyvar];
        }
        echo "</div>";
    }

    /**
     * 验证是否是数字
     * @author yusj | 最后修改时间 2015年5月8日下午4:05:26
     */
    function isNumber($data)
    {
        if (empty($data))
        {
            return false;
        }
        if (is_array($data))
        {
            foreach ($data as $k => $v)
            {
                if (!is_numeric($v))
                {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * GET请求方法
     * @param type $url 请求的URL
     * @param type $request_timeout 请求超时时间
     * @return type
     */
    function get($url , $request_timeout = 10)
    {
        $ch = curl_init($url);
        //  $ip = get_client_ip();
        //curl_setopt($ch, CURLOPT_HEADER, true); //抓取头信息
        //curl_setopt($ch, CURLOPT_NOBODY, 1);
        //HTTPS连接
        if (stripos($url , 'https') === 0)
        {
            curl_setopt($ch , CURLOPT_SSL_VERIFYPEER , 0);
            curl_setopt($ch , CURLOPT_SSL_VERIFYHOST , 0);
        }
        curl_setopt($ch , CURLOPT_FOLLOWLOCATION , 1); //是否抓取跳转后的页面
        curl_setopt($ch , CURLOPT_RETURNTRANSFER , true);
        curl_setopt($ch , CURLOPT_BINARYTRANSFER , true);
        curl_setopt($ch , CURLOPT_TIMEOUT , $request_timeout);
        //curl_setopt($ch , CURLOPT_HTTPHEADER , array('X-FORWARDED-FOR:' . $ip , 'CLIENT-IP:' . $ip));


        $output = curl_exec($ch);
        $code = curl_getinfo($ch , CURLINFO_HTTP_CODE);

// $header = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        $data = array('data' => $output , 'code' => $code);
        return $data;
    }

    /**
     * POST请求方法
     * @param type $url 请求的URL
     * @param type $data 请求的数据包  如果为数组会转义发送，字符串则直接发送
     * @param type $request_timeout 请求超时时间
     * @param type $header 需要发送的头信息
     * @return type array('data'=>'POST返回的数据','code'=>'网页状态码')
     */
    function post($url , $data , $request_timeout = 10 , $header = '')
    {
        $ch = curl_init();
//curl_setopt($ch, CURLOPT_HEADER, true); //抓取头信息
//curl_setopt($ch, CURLOPT_NOBODY, 1);

        if (is_array($header))
        {
            curl_setopt($ch , CURLOPT_HTTPHEADER , $header);
        }
        $data = is_array($data) ? http_build_query($data) : $data;
//HTTPS连接
        if (stripos($url , 'https') === 0)
        {
            curl_setopt($ch , CURLOPT_SSL_VERIFYPEER , 0);
            curl_setopt($ch , CURLOPT_SSL_VERIFYHOST , 0);
        }
        curl_setopt($ch , CURLOPT_URL , $url); //设置链接
        curl_setopt($ch , CURLOPT_POST , 1);
        curl_setopt($ch , CURLOPT_POSTFIELDS , $data);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //是否抓取跳转后的页面
        curl_setopt($ch , CURLOPT_RETURNTRANSFER , true);
        curl_setopt($ch , CURLOPT_TIMEOUT , $request_timeout);
        $output = curl_exec($ch);
        $code = curl_getinfo($ch , CURLINFO_HTTP_CODE);
#$header = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        $result = array('data' => $output , 'code' => $code);
        return $result;
    }

    /**
     * 验证身份证号码
     * @author yusj | 最后修改时间 2015年5月9日下午2:42:56
     */
    function isCreditNo($vStr)
    {
        $vCity = array(
            '11' , '12' , '13' , '14' , '15' , '21' , '22' ,
            '23' , '31' , '32' , '33' , '34' , '35' , '36' ,
            '37' , '41' , '42' , '43' , '44' , '45' , '46' ,
            '50' , '51' , '52' , '53' , '54' , '61' , '62' ,
            '63' , '64' , '65' , '71' , '81' , '82' , '91'
        );

        if (!preg_match('/^([\\d]{17}[xX\\d]|[\\d]{15})$/' , $vStr))
            return false;

        if (!in_array(substr($vStr , 0 , 2) , $vCity))
            return false;

        $vStr = preg_replace('/[xX]$/i' , 'a' , $vStr);
        $vLength = strlen($vStr);

        if ($vLength == 18)
        {
            $vBirthday = substr($vStr , 6 , 4) . '-' . substr($vStr , 10 , 2) . '-' . substr($vStr , 12 , 2);
        }
        else
        {
            $vBirthday = '19' . substr($vStr , 6 , 2) . '-' . substr($vStr , 8 , 2) . '-' . substr($vStr , 10 , 2);
        }

        if (date('Y-m-d' , strtotime($vBirthday)) != $vBirthday)
            return false;
        if ($vLength == 18)
        {
            $vSum = 0;

            for ($i = 17; $i >= 0; $i--)
            {
                $vSubStr = substr($vStr , 17 - $i , 1);
                $vSum += (pow(2 , $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
            }

            if ($vSum % 11 != 1)
                return false;
        }

        return true;
    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function get_client_ip($type = 0 , $adv = false)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL)
            return $ip[$type];
        if ($adv)
        {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $arr = explode(',' , $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown' , $arr);
                if (false !== $pos)
                    unset($arr[$pos]);
                $ip = trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP']))
            {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif (isset($_SERVER['REMOTE_ADDR']))
            {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        elseif (isset($_SERVER['REMOTE_ADDR']))
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u" , ip2long($ip));
        $ip = $long ? array($ip , $long) : array('0.0.0.0' , 0);
        return $ip[$type];
    }

    function getXzUrl($flat_id)
    {
        $url = C('subsite:subsite.SITE_URL');
        if (is_numeric($flat_id))
        {
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $WX = T($DB_NAME . '.wxxz_flat');
            $info = $WX->where(array('id' => $flat_id))->find();
            if (!emptys($info))
                $url .= $info['domain_name'];
        }
        return $url;
    }

    function C($path)
    {
        $configs = \Core\Config::get($path);
        return $configs;
    }

    /**
     * 删除待办事项
     * @param string $module
     * @param int $entity_id
     * @return boolean
     */
    function delBackLog($module , $entity_id)
    {
        $where = array('module' => $module , 'entity_id' => $entity_id);
        $res = M('Todo')->delete($where);
        return $res;
    }

    function verifyDataLinePermissions($permissions_auth , $authenticatee_id , $extended , $module_name = 'sys_housing_management' , $user_id , $is_manager)
    {
        if ($is_manager && $module_name != 'sys_housing_management')
        {
            return true;
        }
        $module_name = $module_name ? $module_name : '';
        if (!$module_name)
        {
            return false;
        }
        $permissions = \Common\Helper\Permissions::Factory($module_name);
        return $permissions->VerifyDataLinePermissions($permissions_auth , $permissions::USER_AUTHENTICATOR , $user_id , $authenticatee_id , $extended);
    }
    