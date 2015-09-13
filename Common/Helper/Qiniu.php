<?php

    namespace Common\Helper;
include dirname(dirname(dirname(__FILE__))) . '/vendor/Qiniu/autoload.php';

    use Qiniu\Auth;
    use Qiniu\Storage\BucketManager;
    use Qiniu\Storage\UploadManager;

    /**
     * 验证码类
     * @author lishengyou
     * 最后修改时间 2014年11月10日 下午3:36:16
     *
     */
    class Qiniu
    {

        private $ak = "NBYkQWUdKz38TEM_g1TRNyC8Bhcp4g75HMyPab_L";
        private $sk = "VtoD9vz8V9VTPWgXDuslaaBhydBwpTpGMWjuysuH";
        private $bucket = 'jooozo-erp';
        private $host = 'qiniudn.com';
        private $auth;

        function __construct()
        {
            $this->auth = new Auth($this->ak , $this->sk);
        }

        function getUrl()
        {
            return "http://" . $this->getBucket() . '.' . $this->host . '/';
        }

        function getBucket()
        {
            return $this->bucket;
        }

        function getUploadManager()
        {
            return new UploadManager($this->getAuth());
        }

        function getBucketManager()
        {
            return new BucketManager($this->getAuth());
        }

        function getAuth()
        {
            return $this->auth;
        }

        function getDownloadUrl($file_name)
        {
            $bucketMgr = $this->getBucketManager();
            $bucket = $this->getBucket();
            list($info , $err) = $bucketMgr->stat($bucket , $file_name);
            if (count($info) == 0)
                return false;
            $info['url'] =  \Common\Helper\Qiniu\Image::getUrl($file_name);
            return $info;
        }

        /**
         *  七牛云下载 消息列队调用
         */
        public function Upload($file_dir , $file_name)
        {
            if (empty($file_dir) || empty($file_name))
                return false;
            $auth = $this->getAuth();
            $bucket = $this->getBucket();
            $token = $auth->uploadToken($bucket);
            $uploadMgr = $this->getUploadManager();
            $save_dir = $file_dir . $file_name;
            $file_save_dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $save_dir;
            if (!is_file($file_save_dir))
                return false;
            list($result) = $uploadMgr->putFile($token , $save_dir , $file_save_dir);

            if (!isset($result['key']) || empty($result['key']))
            {
                logDebug('上传七牛云离线文件失败,文件名[' . $save_dir . ']');
                return false;
            }
            return $result;
        }

    }
    