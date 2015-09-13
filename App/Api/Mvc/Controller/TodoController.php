<?php

    namespace App\Api\Mvc\Controller;

    use App\Web\Helper;
    use Zend\Db\Sql\Expression;

    class TodoController extends \App\Api\Lib\Controller
    {

        function getListAction()
        {
            PV('day');
            $index_helper = new \Common\Helper\Erp\Index();
            $mlist = new \App\Web\Helper\Memo();
            $year_month = I('day');
            $company_id = $this->getCompanyId();
            //当月第一天
            $s_day = strtotime($year_month);

            //当月最后一天
            $e_day = strtotime(date('Y-m-d 23:59:59' , $s_day));

            $date_info = $index_helper->getCurrentMonthBacklog($s_day , $e_day , $company_id , $this->getUserInfo());

            foreach ($date_info as $key => $date)
            {
                $date_info[$key]['deal_time'] = date('Y-n-j' , $date['deal_time']);
            }

            return_success($date_info);
        }

        function getOverListAction()
        {
            $page = I('page');
            $size = I('size');
            $title = I('title');
            $index_modle = new \Common\Model\Erp\Index();
            $sql = $index_modle->getSqlObject();
            $company_id = $this->getCompanyId();
            $select = $sql->select(array('td' => 'todo'));
            $select->columns(array('deal_time' => 'deal_time' , 'module' , 'title' => 'title' , 'content' => 'content' , 'entity_id' => 'entity_id' , 'todo_id'));
            $where = new \Zend\Db\Sql\Where();
            $where->greaterThan('td.deal_time' , 0);
            if (!emptys($title))
                $where->equalTo('td.title' , $title);
            $where->lessThan('td.deal_time' , strtotime(date("Y-m-d 00:00:00")));
            $where->equalTo('td.company_id' , $company_id);
            $swhere = new \Zend\Db\Sql\Where();
            $swhere->equalTo('td.status' , 0);
            $select->where($where);
            $select->order('td.deal_time asc');

            $data = \Core\Db\Sql\Select::pageSelect($select , null , $page , $size);

            return_success($data);
        }

        function FinanceStatisticsAction()
        {

            $TC = M('TenantContract');
            $where = new \Zend\Db\Sql\Where();
            $company_id = $this->getCompanyId();
            $where->equalTo('tc.company_id' , $company_id);
            $where->equalTo('tc.is_delete' , 0);
            $year = I('year');
            $year = emptys($year) ? 'Y-01-01' : $year . '-01-01';
            $stime = strtotime(date($year));
            $etime = strtotime('+1 year' , strtotime(date($year)));
            $where->greaterThanOrEqualTo('tc.next_pay_time' , $stime);
            $where->lessThanOrEqualTo('tc.next_pay_time' , $etime);

            //LMS 修改与2015年9月1日 09:56:42 
            $user = $this->getUserInfo();
            $city_id = $user['city_id'];
            //筛选分散式房源的城市
            $sql1 = "(house_id >0  AND  house_id IN (SELECT  `house_id` FROM  `house` AS h LEFT JOIN `community` AS c ON c.community_id=h.community_id WHERE c.city_id='$city_id') )";
            //筛选公寓的城市
            $sql2 = "(house_id = 0  AND  room_id IN (SELECT  `room_focus_id`  FROM  flat AS f LEFT JOIN room_focus as rf ON f.flat_id=rf.flat_id WHERE f.city_id='$city_id') )";
            $where->expression("($sql1 OR $sql2)" , array());
            $select = $TC->getSqlObject()->select();
            $yingshou = $select->from(array('tc' => 'tenant_contract'))->leftjoin(array('r' => 'rental') , 'tc.contract_id=r.contract_id')->columns(array('next_pay_time' , 'rent' , 'pay'))->where($where)->execute();

            $money_array = array();

            $keys = array('yingshou' , 'yingzhi' , 'yishou' , 'yizhi' , 'qianfei' , 'ssb_money');

            foreach ($keys as $key)
            {
                for ($i = 1; $i <= 12; $i++)
                    $money_array[$i][$key] = 0;
            }
            foreach ($yingshou as $info)
            {
                $money = $info['rent'];
                if ($info['pay'] > 1)
                    $money = $money * $info['pay'];
                $month = date('n' , $info['next_pay_time']);
                $money_array[$month]['yingshou'] += $money;
            }
            $LC = M('LandlordContract');

            $where = new \Zend\Db\Sql\Where();

            $where->equalTo('company_id' , $company_id);
            $where->equalTo('is_delete' , 0);
            $where->equalTo('city_id' , $city_id);
            $year = I('year');
            $year = emptys($year) ? 'Y-01-01' : $year . '-01-01';
            $stime = strtotime(date($year));
            $etime = strtotime('+1 year' , strtotime(date($year)));
            $where->greaterThanOrEqualTo('next_pay_time' , $stime);
            $where->lessThanOrEqualTo('next_pay_time' , $etime);
            $yingzhi = $LC->getData($where , array('next_pay_time' , 'rent' , 'pay'));
            foreach ($yingzhi as $info)
            {
                $money = $info['rent'];
                if ($info['pay'] > 1)
                    $money = $money * $info['pay'];
                $month = date('n' , $info['next_pay_time']);
                $money_array[$month]['yingzhi'] += $money;
            }

            $SN = M('SerialNumber');
            //$current_month_first_day = strtotime(date('Y-m-01 H:i:s' , strtotime(date("Y-m-d"))));
            //$current_month_last_day = strtotime(date('Y-m-t H:i:s' , time()));
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $sql = $serial_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array('serial_id' => 'serial_id' , 'pay_time' , 'money' , 'final_money' , 'type' , 'status' , 'father_id' , 'receivable'));
            $select->leftjoin(array('ssb' => 'serial_strike_balance') , 'sn.serial_id = ssb.serial_id' , array('ssb_money' => new Expression('IF(ssb.money, ssb.money, 0)')));

            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('company_id' , $company_id);
            //$where->greaterThanOrEqualTo('pay_time' , $current_month_first_day);
            //$where->lessThanOrEqualTo('pay_time' , $current_month_last_day);
            $where->greaterThanOrEqualTo('pay_time' , $stime);
            $where->lessThanOrEqualTo('pay_time' , $etime);
            $where->equalTo('is_delete' , 0);
            $where->equalTo('city_id' , $city_id);
            $select->where($where);
            $yishouzhi = $select->execute();
            //$yishouzhi = $SN->getData($where , array('pay_time' , 'money', 'final_money' , 'type' , 'status' , 'father_id' , 'receivable'));
            foreach ($yishouzhi as $info)
            {
                $money = $info['final_money'];
                $ssb_money = (Float) $info['ssb_money'];
                $month = date('n' , $info['pay_time']);
                //欠费
                if ($info['status'] == 2 && $info['father_id'] > 0)
                {
                    //if ($info['father_id'] == 0)
                    //    continue;
                    $money_array[$month]['qianfei'] += $info['receivable'];
                    //continue;
                }
                if ($info['type'] == 1 && $info['father_id'] == 0 && ($info['status'] == 0 || $info['status'] == 1))
                {
                    $money_array[$month]['yishou'] += $money;
                }
                elseif ($info['status'] != 1 && $info['status'] != 2 && $info['type'] == 2)
                {
                    $money_array[$month]['yizhi'] += $money;
                }

                if ($ssb_money !== 0 && $info['type'] != 2)
                {
                    $money_array[$month]['ssb_money'] += $ssb_money;
                }
            }

            $result = array();
            foreach ($money_array as $month => $info)
            {
                $result[] = array(
                    'month' => $month ,
                    'data' => $info ,
                );
            }

            return_success($result);
        }

        function deleteAction()
        {
            PV('todo_id');
            $todo_id = I('todo_id');
            $del = M('Todo')->delete(array('todo_id' => $todo_id , 'company_id' => $this->getCompanyId()));
            if (!$del)
                return_error(142);

            return_success();
        }

    }
    