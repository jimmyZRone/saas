<?php

    namespace App\Api\Helper\User;

    use Common\Helper\Permissions;

    class UserController extends \Common\Helper\Erp\User
    {

        private static $guid = "";

        /**
         * 取得SESSION ID
         * @author lishengyou
         * 最后修改时间 2014年12月29日 下午5:06:21
         *
         */
        public static function getSessionId()
        {
            $guid = self::$guid;
            return $guid ? $guid : false;
        }

        /**
         * 设置SESSION ID
         * @author lishengyou
         * 最后修改时间 2014年12月29日 下午5:11:11
         *
         * @param unknown $session_id
         */
        public static function setSessionId($guid)
        {
            self::$guid = $guid;
        }

        /**
         * 登录用户
         * @author yusj | 最后修改时间 2015年4月29日上午10:19:30
         */
        public static function loginUser(array &$user , $app_type = 'default')
        {
            if (!$user)
                return false;
            $model = new \Common\Model\Erp\ErpinterfaceSession();
            $guid = self::getSessionId();
            if (!$guid)
            {
                $guid = uniqid('guid_');
                self::setSessionId($guid);
            }
            //删除当前用户其他的登录点
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('user_id' , $user['user_id']);
            //$where->equalTo('app_type',$app_type);
            $where->like('app_type' , '%app_%');
            $where->notEqualTo('session_id' , $guid);
            $model->delete($where);
            $time = time() + 86400 * 7;
            if ($model->getOne(array('session_id' => $guid)))
            {
                $data = array();
                $data['user_id'] = $user['user_id'];
                $data['value'] = serialize($user);
                $data['app_type'] = $app_type;
                $data['deadline'] = $time;//7天过期
                $reslut = $model->edit(array('session_id' => $guid) , $data);
            }
            else
            {
                $data = array();
                $data['session_id'] = $guid;
                $data['user_id'] = $user['user_id'];
                $data['value'] = serialize($user);
                $data['app_type'] = $app_type;
                $data['deadline'] = $time;//7天过期
                $reslut = $model->insert($data);
            }

            return $reslut ? $guid : false;
        }

        static function getUserAuth($user_id , $company_id)
        {

            $groupModel = new \Common\Model\Erp\Group();
            $modularModel = new \Common\Model\Erp\Modular();
            $user_group = M('UserGroup')->getOne(array('user_id' => $user_id));
            $groupData = $groupModel->getOne(array('group_id' => $user_group['group_id'] , 'company_id' => $company_id));
            if (!$groupData)
            {
                return array();
            }

            //整理权限数据
            $modularData = $modularModel->getData(array() , array() , 0 , 0 , 'parent_id asc,order asc');
            $authData = $modularModel->setTableName('module_permissions')->getData(array('authenticator_type' => Permissions::GROUP_AUTHENTICATOR , 'authenticator_id' => $groupData['group_id'] , 'block_access_id' => Permissions::MODULE_BLOCK_ACCESS));

            $temp = array();
            $authps = array(Permissions::INSERT_AUTH_ACTION , Permissions::UPDATE_AUTH_ACTION , Permissions::DELETE_AUTH_ACTION , Permissions::SELECT_AUTH_ACTION);
            $authps = array_flip($authps);

            foreach ($authData as $value)
            {
                if (!isset($temp[$value['authenticatee_id']]))
                {
                    $temp[$value['authenticatee_id']] = array();
                }
                $temp[$value['authenticatee_id']][$authps[$value['permissions_auth']]] = $value['permissions_value'];
            }
            $authData = $temp;
            unset($temp);
            //整理模块菜单
            $temp = array();
            foreach ($modularData as $value)
            {
                if (!isset($temp[$value['parent_id']]))
                {
                    $temp[$value['parent_id']] = array();
                }
                $temp[$value['parent_id']][$value['modular_id']] = $value;
            }
            $modularData = $temp;
            unset($temp);
            //统计第一级菜单是否需要全选
            foreach ($modularData['0'] as $key => $value)
            {
                $value['selected'] = true;
                if (isset($modularData[$value['modular_id']]))
                {//检测下级菜单
                    foreach ($modularData[$value['modular_id']] as $k => $val)
                    {
                        //把权限回归到每个模块
                        if (!$val['functional_module'] || !isset($authData[$val['functional_module']]))
                        {
                            $val['auth'] = array_fill_keys(array_values($authps) , false);
                        }
                        else
                        {
                            foreach ($authps as $auth)
                            {
                                $val['auth'][$auth] = isset($authData[$val['functional_module']][$auth]) && $authData[$val['functional_module']][$auth];
                            }
                        }
                        $val['selected'] = count(array_filter($val['auth'])) >= 4;
                        $modularData[$value['modular_id']][$k] = $val;
                        $value['selected'] = !$value['selected'] ? $value['selected'] : $val['selected'];
                    }
                }
                else if (!$value['functional_module'] || !isset($authData[$value['functional_module']]))
                {
                    $value['auth'] = array_fill_keys(array_values($authps) , false);
                }
                else
                {
                    foreach ($authps as $auth)
                    {
                        $value['auth'][$auth] = isset($authData[$value['functional_module']][$auth]) && $authData[$value['functional_module']][$auth];
                    }
                }
                $value['selected'] = $value['functional_module'] ? (count(array_filter($value['auth'])) >= 4) : $value['selected'];
                $modularData['0'][$key] = $value;
            }
            foreach ($modularData as $key)
            {
                foreach ($key as $value)
                {
                    if (emptys($value['auth']))
                    {
                        continue;
                    }
                    $auth = array();

                    foreach ($value['auth'] as $type => $val)
                    {
                        if ($val == false)
                            continue;

                        $auth[] = array_search($type , $authps);
                    }

                    $auth_arr[] = array(
                        'name' => $value['mark'] ,
                        'auth' => $auth ,
                    );
                }
            }
            return $auth_arr;
        }

    }
    