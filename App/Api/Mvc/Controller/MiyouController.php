<?php

    namespace App\Api\Mvc\Controller;

    class MiyouController extends \App\Api\Lib\Controller
    {

        const COMPANY_ID = 32;

        function dataAction()
        {
            $table = I('table');
            if (!method_exists($this , $table))
                return return_error('-1');
            $data = $this->$table();
            return_success($data);
        }

        function company()
        {
            $table = __FUNCTION__;
            $where = array('company_id' => self::COMPANY_ID);
            $list = M($table)->getData($where);
            return $list;
        }

        function house()
        {
            $table = __FUNCTION__;
            $where = array('company_id' => self::COMPANY_ID);
            $list = M($table)->getData($where);
            return $list;
        }

        function house_entirel()
        {
            $table = __FUNCTION__;
            $where = array('h.company_id' => self::COMPANY_ID);
            $select = M($table)->getSqlObject()->select();
            $select->from(array('r' => $table))->columns(array('*'))->leftjoin(array('h' => 'house') , 'r.house_id=h.house_id' , array())->where($where);
            $list = $select->execute();
            return $list;
        }

        function room()
        {
            $table = __FUNCTION__;
            $where = array('h.company_id' => self::COMPANY_ID);
            $select = M($table)->getSqlObject()->select();
            $select->from(array('r' => $table))->columns(array('*'))->leftjoin(array('h' => 'house') , 'r.house_id=h.house_id' , array())->where($where);
            $list = $select->execute();
            return $list;
        }

        function landlord()
        {
            $table = __FUNCTION__;
            $where = array('company_id' => self::COMPANY_ID);
            $list = M($table)->getData($where);
            return $list;
        }

        function landlord_contract()
        {
            $table = __FUNCTION__;
            $where = array('company_id' => self::COMPANY_ID);
            $list = M($table)->getData($where);
            return $list;
        }

        function rental()
        {
            $table = __FUNCTION__;
            $where = array('t.company_id' => self::COMPANY_ID);
            $select = M($table)->getSqlObject()->select();
            $select->from(array('r' => $table))->columns(array('*'))->leftjoin(array('t' => 'tenant') , 'r.tenant_id=t.tenant_id' , array())->where($where);
            $list = $select->execute();
            return $list;
        }

        function tenant()
        {
            $table = __FUNCTION__;
            $where = array('company_id' => self::COMPANY_ID);
            $list = M($table)->getData($where);
            return $list;
        }

        function tenant_contract()
        {
            $table = __FUNCTION__;
            $where = array('company_id' => self::COMPANY_ID);
            $list = M($table)->getData($where);
            return $list;
        }

        function system_config()
        {
            $table = __FUNCTION__;
            $list = M($table)->getData(array());
            return $list;
        }

        function attachments()
        {
            $table = __FUNCTION__;
            $where = array('hfv.company_id' => self::COMPANY_ID);
            $select = @M($table)->getSqlObject()->select();
            $ex_sql = "(a.module='house' AND hfv.house_id>0 AND hfv.house_id=a.entity_id) OR (a.module='room' AND hfv.house_id>0 AND hfv.record_id=a.entity_id)";
            $select->from(array('a' => $table))->columns(array('*'))->leftjoin(array('hfv' => 'house_focus_view') , getExpSql($ex_sql) , array())->where($where)->group('a.attachments_id');
            $list = $select->execute();
            return $list;
        }

        function user()
        {
            $table = __FUNCTION__;
            $where = array('u.company_id' => self::COMPANY_ID);
            $select = @M($table)->getSqlObject()->select();
            $ex_sql = "ue.user_id=u.user_id";
            $select->from(array('u' => $table))->columns(array('*'))->leftjoin(array('ue' => 'user_extend') , getExpSql($ex_sql) , array('*'))->leftjoin(array('ug' => 'user_group') , 'ug.user_id=u.user_id' , array())->leftjoin(array('g' => 'group') , 'g.group_id=ug.group_id' , array('group_name' => 'name' , 'group_id'))->where($where)->group('u.user_id');
            $list = $select->execute();
            return $list;
        }

    }
    