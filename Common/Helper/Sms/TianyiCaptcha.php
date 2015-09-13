<?php

    namespace Common\Helper\Sms;

    use Zend\Db\Sql\Predicate\Expression;

    /**
     * 天翼验证码
     * @author lishengyou
     * 最后修改时间 2015年3月31日 下午4:21:02
     *
     */
    class TianyiCaptcha
    {

        const EXP_TIME = 1800;//30分钟

        /**
         * 取配置
         * @author lishengyou
         * 最后修改时间 2015年4月7日 下午2:20:36
         *
         * @param unknown $key
         */

        protected static function getConfig($key)
        {
            static $config = null;
            if (!$config)
            {
                $config = \Core\Config::get('tianyicaptcha');
            }
            $data = isset($config[$key]) ? $config[$key] : null;
            if (APP_IS_DEBUG && $data && strpos($data , '@') === 0)
            {//调试
                $data = \App\Callback\Lib\Url::parse(substr($data , 1));
            }
            return $data;
        }

        /**
         * 验证验证码
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午5:29:45
         *
         * @param unknown $phone
         * @param unknown $code
         */
        public static function check($phone , $code , $del = true)
        {
            $dbCode = self::getCode($phone);
            if ($code === $dbCode)
            {
                if ($del)
                {
                    self::clear($phone);
                }
                return true;
            }
            return false;
        }

        /**
         * 清空验证码
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午8:16:25
         *
         * @param unknown $phone
         */
        public static function clear($phone)
        {
            //验证成功删除
            $sql = \Common\Model::getLink();
            $update = $sql->update('sms_captcha');
            $update->where(array('phone' => $phone));
            $update->set(array('ext_time' => 0));
            $update->execute();
            return true;
        }

        /**
         * 取得最近的有效验证码信息
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午8:15:17
         *
         * @param unknown $phone
         * @return boolean|Ambigous <unknown, NULL>
         */
        public static function getRecentlyInfo($phone)
        {
            $sql = \Common\Model::getLink();
            $select = $sql->select('sms_captcha');
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('phone' , $phone);
            $select->where($where);
            $select->order('create_time desc');

            $select->limit(1);
            $data = $select->execute();
            if (!$data)
                return false;
            if (!$data[0]['code'])
            {
                return false;
            }
            if ($data[0]['create_time'] + $data[0]['ext_time'] < time())
            {
                return false;
            }
            return $data[0];
        }

        /**
         * 取得验证码
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午8:15:57
         *
         * @param unknown $phone
         * @return boolean
         */
        public static function getCode($phone)
        {
            $info = self::getRecentlyInfo($phone);
            return $info ? $info['code'] : false;
        }

        /**
         * 保存验证码
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午5:24:51
         *
         * @param unknown $captchaId
         * @param unknown $uniqid
         * @param unknown $code
         */
        public static function save($captchaId , $uniqid , $code)
        {
            $sql = \Common\Model::getLink();
            $select = $sql->select('sms_captcha');
            $select->where(array('id' => $captchaId , 'identifier' => $uniqid));
            $data = $select->execute();
            if (!$data || $data[0]['code'])
            {
                return false;
            }
            $update = $sql->update('sms_captcha');
            $update->set(array('code' => $code));
            $update->where(array('id' => $captchaId));
            return $update->execute();
        }

        /**
         * 验证发送验证
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午5:37:52
         *
         * @param unknown $phone
         * @param unknown $ipaddress
         */
        protected static function checkSend($phone , $ipaddress)
        {
            $sql = \Common\Model::getLink();
            //手机号
            $select = $sql->select('sms_captcha');
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('phone' , $phone);
            $where->greaterThanOrEqualTo('create_time' , strtotime('-1 Hours' , time()));
            $select->where($where);
            $select->columns(array('select_count' => new Expression('count(*)')));
            $count = $select->execute();
            $count = $count[0]['select_count'];
            if ($count >= 10)
            {
                return array('status' => 0 , 'message' => '验证码发送过于频繁');
            }
            //IP地址
            $select = $sql->select('sms_captcha');
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('ipaddress' , $phone);
            $where->greaterThanOrEqualTo('create_time' , strtotime('-1 Hours' , time()));
            $select->where($where);
            $select->columns(array('select_count' => new Expression('count(*)')));
            $count = $select->execute();
            $count = $count[0]['select_count'];
            if ($count >= 20)
            {
                return array('status' => 0 , 'message' => '验证码发送过于频繁');
            }
            //验证上次发送时间
            $select = $sql->select('sms_captcha');
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('phone' , $phone);
            $select->where($where);
            $select->order('create_time desc');
            $select->limit(1);
            $data = $select->execute();
            if ($data && time() - $data[0]['create_time'] < 60)
            {//离上次发送在一分钟内
                return array('status' => 0 , 'message' => '验证码发送过于频繁');
            }
            return array('status' => 1);
        }

        /**
         * 发送验证码
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午5:55:49
         *
         * @param unknown $phone
         * @param unknown $ext_time
         * @param string $ipaddress
         * @return boolean|Ambigous <multitype:number string , multitype:number >
         */
        public static function send($phone , $ext_time = self::EXP_TIME , $ipaddress = null)
        {
            $access_token = self::getAccessToken();
            if (!$access_token)
                return array('status' => 0);
            $token = self::getToken($access_token);
            if (!$token)
                return array('status' => 0);
            //生成唯一标识
            $ipaddress = $ipaddress ? $ipaddress : \Common\Helper\Http\Request::getClientIp();
            //验证是否可以发送
            $result = self::checkSend($phone , $ipaddress);
            if (!$result['status'])
            {
                return $result;
            }
            $uniqid = "access_token:{$access_token}&token:{$token}&phone:{$phone}&ipaddress:{$ipaddress}";
            $uniqid = \Common\Helper\Encrypt::md5_16($uniqid);
            //保存
            $data = array(
                'phone' => $phone ,
                'ipaddress' => $ipaddress ,
                'identifier' => $uniqid ,
                'ext_time' => $ext_time ,
                'create_time' => time()
            );
            $sql = \Common\Model::getLink();
            //删除24小时之前的验证码
            $delete = $sql->delete('sms_captcha');
            $where = new \Zend\Db\Sql\Where();
            $where->lessThanOrEqualTo('create_time' , strtotime('-24 Hours' , time()));
            $delete->where($where);
            $delete->execute();

            $insert = $sql->insert('sms_captcha');
            $insert->values($data);
            $captchaId = $insert->execute();
            if (!$captchaId)
                return array('status' => 0);
            $dataurl = \App\Callback\Lib\Url::parse("captcha-tianyi/save/captchaid/{$captchaId}/uniqid/{$uniqid}");
            $data = self::sendSms($access_token , $token , $phone , $dataurl , $ext_time / 60);
            if (!$data)
                return array('status' => 0);
            return array('status' => 1);
        }

        /**
         * 发送验证码
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午4:34:11
         *
         * @param unknown $access_token
         * @param unknown $token
         * @param unknown $phone
         * @return mixed
         */
        protected static function sendSms($access_token , $token , $phone , $dataurl , $exp_time)
        {
            $timestamp = date('Y-m-d H:i:s');
            $app_id = self::getConfig('app_id');
            $app_secret = self::getConfig('app_secret');
            $url = self::getConfig('send_url');
            $param['app_id'] = "app_id=" . $app_id;
            $param['access_token'] = "access_token=" . $access_token;
            $param['timestamp'] = "timestamp=" . $timestamp;
            $param['token'] = "token=" . $token;
            $sendPhone = self::getConfig('phone');
            $param['phone'] = "phone=" . ($sendPhone ? $sendPhone : $phone);
            $param['url'] = "url=" . $dataurl;
            if (isset($exp_time))
                $param['exp_time'] = "exp_time=" . $exp_time;
            ksort($param);
            $plaintext = implode("&" , $param);
            $param['sign'] = "sign=" . rawurlencode(base64_encode(hash_hmac("sha1" , $plaintext , $app_secret , $raw_output = True)));
            $param['url'] = "url=" . rawurlencode($dataurl);
            ksort($param);
            $str = implode("&" , $param);
            $result = self::curl_post($url , $str);
            $resultArray = json_decode($result , true);
            return $resultArray && isset($resultArray['identifier']) ? $resultArray['identifier'] : false;
        }

        /**
         * 取得access_token
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午2:38:13
         *
         */
        protected static function getAccessToken($reload = false)
        {
            try
            {
                $storage = new \Core\Cache\File();
                $cache = new \Core\Cache($storage);
                $cacheAccessToken = $cache->get('tianyi_captcha_accesstoken');
                if (!$reload || !$cacheAccessToken || $cacheAccessToken['expires_in'] < time())
                {
                    $ch = curl_init();
                    $data = array(
                        'grant_type' => 'client_credentials' ,
                        'app_id' => self::getConfig('app_id') ,
                        'app_secret' => self::getConfig('app_secret')
                    );
                    $access_token_url = self::getConfig('access_token_url');
                    curl_setopt($ch , CURLOPT_URL , $access_token_url); // 抓取指定网页
                    curl_setopt($ch , CURLOPT_HEADER , 0); // 设置header
                    curl_setopt($ch , CURLOPT_RETURNTRANSFER , 1); // 要求结果为字符串且输出到屏幕上
                    curl_setopt($ch , CURLOPT_POST , 1); // post提交方式
                    curl_setopt($ch , CURLOPT_POSTFIELDS , http_build_query($data));
                    curl_setopt($ch , CURLOPT_SSL_VERIFYPEER , FALSE);
                    curl_setopt($ch , CURLOPT_SSL_VERIFYHOST , FALSE);
                    $result = curl_exec($ch); // 运行curl
                    $result = json_decode($result , true);
                    if (!$result || !isset($result['access_token']))
                    {
                        throw new \Exception('');
                    }
                    $cacheAccessToken = $result;
                    $cacheAccessToken['expires_in'] = time() + $cacheAccessToken['expires_in'];
                    $cache->save('tianyi_captcha_accesstoken' , $cacheAccessToken);
                }
                return $result && isset($result['access_token']) ? $result['access_token'] : false;
            } catch (\Exception $e)
            {
                return false;
            }
        }

        /**
         * 取得Token
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午2:24:41
         *
         */
        protected static function getToken($access_token)
        {
            $timestamp = date('Y-m-d H:i:s');
            $app_id = self::getConfig('app_id');
            $app_secret = self::getConfig('app_secret');
            $url = self::getConfig('token_url');
            $param['app_id'] = "app_id=" . $app_id;
            $param['access_token'] = "access_token=" . $access_token;
            $param['timestamp'] = "timestamp=" . $timestamp;
            ksort($param);
            $plaintext = implode("&" , $param);
            $param['sign'] = "sign=" . rawurlencode(base64_encode(hash_hmac("sha1" , $plaintext , $app_secret , $raw_output = True)));
            ksort($param);
            $url .= '&' . implode("&" , $param);
            $result = self::curl_get($url);
            $resultArray = json_decode($result , true);
            if ($resultArray && isset($resultArray['res_code']) && $resultArray['res_code'] == '110')
            {
                //刷新access_token
                static $reload_number = 0;
                $access_token = self::getAccessToken(true);
                if ($access_token && $reload_number < 3)
                {
                    $reload_number++;
                    return self::getToken($access_token);
                }
                else
                {
                    return false;
                }
            }
            return $resultArray && isset($resultArray['token']) ? $resultArray['token'] : false;
        }

        /**
         * CURL GET
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午2:23:56
         *
         * @param string $url
         * @param unknown $options
         * @return mixed
         */
        protected static function curl_get($url = '' , $options = array())
        {
            try
            {
                $ch = curl_init($url);
                curl_setopt($ch , CURLOPT_RETURNTRANSFER , 1);
                curl_setopt($ch , CURLOPT_TIMEOUT , 5);
                if (!empty($options))
                {
                    curl_setopt_array($ch , $options);
                }
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            } catch (\Exception $e)
            {
                return '';
            }
        }

        /**
         * CURL POST
         * @author lishengyou
         * 最后修改时间 2015年3月31日 下午2:24:05
         *
         * @param string $url
         * @param string $postdata
         * @param unknown $options
         * @return mixed
         */
        protected static function curl_post($url = '' , $postdata = '' , $options = array())
        {
            try
            {
                $ch = curl_init($url);
                curl_setopt($ch , CURLOPT_RETURNTRANSFER , 1);
                curl_setopt($ch , CURLOPT_POST , 1);
                curl_setopt($ch , CURLOPT_POSTFIELDS , $postdata);
                curl_setopt($ch , CURLOPT_TIMEOUT , 5);
                if (!empty($options))
                {
                    curl_setopt_array($ch , $options);
                }
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            } catch (\Exception $e)
            {
                return '';
            }
        }

    }
    