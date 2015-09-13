<?php

    namespace Common\Helper\Erp;

    use Zend\Db\Sql\Where;

    /**
     * 小区
     * @author lishengyou
     * 最后修改时间 2015年4月1日 下午4:42:52
     *
     */
    class Community extends \Core\Object
    {

        /**
         * 通过小区名字搜索
         * 修改时间2015年4月28日 09:38:16
         * 
         * @author yzx
         * @param string $communityName
         * @return Ambigous <multitype:Ambigous <number, unknown> number , Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >>
         */
        public function getAddressByName($user , $communityName = '' , $limit = array(1 , 10) , $columns = array('*'))
        {
            $where = new Where();
            $where_and = new Where();
            $communityModel = new \Common\Model\Erp\Community();
            if (strlen($communityName) > 0)
                $where->like("community_name" , '%' . $communityName . '%');
            $where_and->equalTo("city_id" , $user["city_id"]);
            $where_and->equalTo('is_verify' , '1');
            $where->addPredicate($where_and);
            $sql = $communityModel->getSqlObject();
            $select = $sql->select($communityModel->getTableName());

            $select->where($where)->columns($columns)->order('first_letter asc');
            $result = \Core\Db\Sql\Select::pageSelect($select , null , $limit[0] , $limit[1]);

            return $result['data'];
        }
        
        /**
         * 添加小区
         * 修改时间2015年5月5日 14:35:16
         * 
         * @author yzx
         * @param unknown $data
         * @param unknown $user
         * @return number
         */
        public function addCommunit($data , $user)
        {
            $communityModel = new \Common\Model\Erp\Community();
            $first_letter = \Common\Helper\String::getFirstCharter($data['community_name']);
            
            $data['user_id'] = $user['user_id'];
            $data['company_id'] = $user['company_id'];
            if (emptys($data['city_id']))
                $data['city_id'] = $user['city_id'];
            $data['first_letter'] = $first_letter;
            $data['periphery'] = '';
            $data['traffic_condition'] = '';
            $data['introduction'] = '';
            return $communityModel->insert($data);
        }

    }
    