<?php

    namespace Common\Helper\Qiniu;

    /**
     * 图片
     * @author lishengyou
     * 最后修改时间 2014年11月27日 下午2:38:32
     *
     */
    class Image
    {

        /**
         * 取得域名
         * @author lishengyou
         * 最后修改时间 2015年8月24日 上午10:04:46
         *
         */
        public static function getDomain()
        {
            return 'http://7qn9zw.com1.z0.glb.clouddn.com/';
        }

        /**
         * 取得图片地址
         * @author lishengyou
         * 最后修改时间 2014年11月27日 下午2:39:07
         *
         * @param unknown $key
         * @param unknown $width
         * @param unknown $height
         */
        public static function getUrl($key , $bucket = '' , array $options = array())
        {
            $key = explode('?' , $key);
            $url = '';
            $ext = explode('.' , $key[0]);
            if (count($ext) > 1)
            {
                $ext = strtolower(end($ext));
                if ($ext == 'doc' || $ext == 'docx')
                {
                    $key[0] = 'word-thumbnail.png';
                }
                else if ($ext == 'pdf')
                {
                    $key[0] = 'pdf-thumbnail.png';
                }
            }
            if (count($key) == 1)
            {
                $url = str_replace(array(':BUCKET' , ':KEY') , array($bucket , $key[0]) , 'http://7qn9zw.com1.z0.glb.clouddn.com/:KEY');
            }
            else
            {
                //高级样式
                $url = str_replace(array(':BUCKET' , ':KEY') , array($bucket , $key[0]) , 'http://7qn9zw.com1.z0.glb.clouddn.com/:KEY') . '?' . $key[1];
            }
            if (empty($options))
                return $url;
            //归类样式处理
            $image = array();
            $order = false;
            if (isset($options['order']))
            {
                $order = $options['order'];
                unset($options['order']);
            }
            else
            {
                $order = array('view2' , 'mogr2');
            }
            if (isset($options['mogr2']))
            {
                $image['mogr2'] = $options['mogr2'];
                unset($options['mogr2']);
            }
            if (!empty($options))
            {
                $image['view2'] = $options;
            }
            $funcs = array('view2' => 'imageView2' , 'mogr2' => 'imageMogr2');
            foreach ($order as $value)
            {
                if (isset($image[$value]) && isset($funcs[$value]))
                    $url = self::{$funcs[$value]}($image[$value] , $url , $bucket);
            }
            return $url;
        }

        /**
         * 解析参数到数组
         * @author lishengyou
         * 最后修改时间 2015年6月1日 下午7:02:21
         *
         * @param unknown $param
         * @return unknown|multitype:string
         */
        protected static function parseparam2array($param)
        {
            $param = trim($param , '/');
            $param = preg_replace('#/+#' , '/' , $param);
            $param = explode('/' , $param);
            $data = array();
            $length = count($param);
            for ($i = 0; $i < $length; $i+=2)
            {
                $data[$param[$i]] = isset($param[$i + 1]) ? $param[$i + 1] : '';
            }
            return $data;
        }

        /**
         * imageView2样式
         * @author lishengyou
         * 最后修改时间 2014年11月27日 下午2:52:02
         *
         * @param unknown $options
         * @param unknown $key
         */
        public static function imageView2(array $op = array() , $key = false , $bucket = false)
        {
            if (isset($op['op']) && $key instanceof \Smarty_Internal_Template)
            {
                $key = isset($op['key']) ? $op['key'] : $key;
                $bucket = isset($op['bucket']) ? $op['bucket'] : $bucket;
                $op = self::parseparam2array($op['op']);
            }
            $mode = isset($op['mode']) ? $op['mode'] : 2;
            $w = isset($op['w']) ? $op['w'] : '';
            $h = isset($op['h']) ? $op['h'] : '';
            $q = isset($op['q']) ? $op['q'] : 100;
            $format = isset($op['format']) ? $op['format'] : '';
            if (!$mode)
            {
                return false;
            }
            if (!$w && !$h)
            {
                return false;
            }
            $imageUrl = 'imageView2/' . $mode;
            $imageUrl .= $w ? '/w/' . $w : '';
            $imageUrl .= $h ? '/h/' . $h : '';
            $imageUrl .= $q ? '/q/' . $q : '';
            $imageUrl .= $format ? '/format/' + $format : '';

            if ($key)
            {
                $imageUrl = strpos($key , '?') ? ($key . '|' . $imageUrl) : (strpos($key , 'http://') === 0 ? $key . '?' . $imageUrl : self::getUrl($key , $bucket) . '?' . $imageUrl);
            }


            if (strpos($imageUrl , 'http://') === false)
            {

                $imageUrl = self::getDomain()  . $imageUrl;
    
            }

            return $imageUrl;
        }

        /**
         * 高级样式
         * @author lishengyou
         * 最后修改时间 2014年11月27日 下午3:01:27
         *
         * @param array $op
         * @param unknown $key
         * @return Ambigous <number, string>
         */
        public static function imageMogr2(array $op = array() , $key = false , $bucket = false)
        {
            if (isset($op['op']) && $key instanceof \Smarty_Internal_Template)
            {
                $key = isset($op['key']) ? $op['key'] : $key;
                $bucket = isset($op['bucket']) ? $op['bucket'] : $bucket;
                $op = self::parseparam2array($op['op']);
            }
            $auto_orient = isset($op['auto_orient']) ? $op['auto_orient'] : '';
            $thumbnail = isset($op['thumbnail']) ? $op['thumbnail'] : '';
            $strip = isset($op['strip']) ? $op['strip'] : '';
            $gravity = isset($op['gravity']) ? $op['gravity'] : '';
            $crop = isset($op['crop']) ? $op['crop'] : '';
            $quality = isset($op['quality']) ? $op['quality'] : '';
            $rotate = isset($op['rotate']) ? $op['rotate'] : '';
            $format = isset($op['format']) ? $op['format'] : '';
            $blur = isset($op['blur']) ? $op['blur'] : '';

            $imageUrl = 'imageMogr2';

            $imageUrl .= $auto_orient ? '/auto-orient' : '';
            $imageUrl .= $thumbnail ? '/thumbnail/' . $thumbnail : '';
            $imageUrl .= $strip ? '/strip' : '';
            $imageUrl .= $gravity ? '/gravity/' . $gravity : '';
            $imageUrl .= $quality ? '/quality/' . $quality : '';
            $imageUrl .= $crop ? '/crop/' . $crop : '';
            $imageUrl .= $rotate ? '/rotate/' . $rotate : '';
            $imageUrl .= $format ? '/format/' . $format : '';
            $imageUrl .= $blur ? '/blur/' . $blur : '';
            if ($key)
            {
                $imageUrl = strpos($key , '?') ? ($key . '|' . $imageUrl) : (strpos($key , 'http://') === 0 ? $key . '?' . $imageUrl : self::getUrl($key , $bucket) . '?' . $imageUrl);
            }
            return $imageUrl;
        }

    }
    