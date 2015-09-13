<?php

    namespace Common\Model\Erp;

    use Zend\Db\Sql\Where;

    class User extends \Common\Model\Erp
    {

        /**
         * 是管理
         * @var unknown
         */
        const IS_MANAGER = 1;

        /**
         * 不是管理
         * @var unknown
         */
        const NOT_MANAGER = 0;

        /**
         * 根据用户名获取用户
         *  最后修改时间 2015-3-19
         *
         * @author dengshuang
         * @param unknown $phone
         */
        public function getByPhone($phone)
        {
            return $this->getOne(array('username' => $phone , 'is_manager' => self::IS_MANAGER));
        }

        /**
         * 根据用户名称取管家ID
         * @author lishengyou
         * 最后修改时间 2015年5月11日 上午9:42:40
         *
         * @param unknown $username
         * @param unknown $companyId
         */
        public function getKeeperByName($username , $companyId)
        {
            return $this->getOne(array('username' => $username , 'is_manager' => self::NOT_MANAGER , 'company_id' => $companyId));
        }

        public function getUserInfo($user_id)
        {
            $SQL = $this->getSqlObject();
            $S = $SQL->select();
            $result = $S->from(array('u' => 'user'))->columns(array('username' , 'user_id'))->join(array('e' => 'user_extend') , 'u.user_id = e.user_id' , array('name' , 'contact' , 'gender' , 'city_id' , 'birthday') , $S::JOIN_LEFT)->join(array('c' => 'company') , 'u.company_id = c.company_id' , array('company_name' , 'pattern' , 'company_id') , $S::JOIN_LEFT)->where(array('u.user_id' => $user_id))->limit(1)->execute();
            return is_array($result) ? $result[0] : $result;
        }

    }
    