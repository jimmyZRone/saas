<?php

    namespace App\Web\Helper;

    use Zend\Db\Sql\Expression;

    class DataCount
    {

        public function gettotaldata($flat_id , $month , $year)
        {



            $all = array();
            // 计算访客数
            $fkmodel = new \Common\Model\Jzuser\UserVisitor(); //这里取访客数
            $datatemp = $fkmodel->getData(array('year' => $year , 'month' => $month , 'flat_id' => $flat_id));
            $totalMonthfk = count($datatemp); // 当月总数
            $datafk['totalmonth'] = $totalMonthfk;

            // 计算从月初到当日 , 每日的访客总数
            $day = $month == date('n') ? date('j' , $_SERVER['REQUEST_TIME']) : date('t' , strtotime($year . $month));

            foreach ($datatemp as $k => $v)
            {
                $data1['everyday'][$v['day']][] = $v;
            }
            foreach ($data1['everyday'] as $k1 => $v1)
            {
                $datafk['everyday'][$k1] = count($v1);
            }
            // 计算收藏数

            $like_model = new \Common\Model\Jzuser\UserLike();// 收藏数Subscribe
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('flat_id' , $flat_id);
            $new_month = $month < 10 ? "0$month" : $month;
            $time = $year . $new_month . '01';
            $where->greaterThanOrEqualTo('create_time' , $time);
            $where->lessThan('create_time' , date("Ymd" , strtotime('+1 month' , strtotime($time))));


//            //生成假数据
//            $new_flat_id = 118;
//            $ip = "1684300901";
//            $time = "1420041600";
//            for ($i = 3; $i <= 8; $i++)
//            {
//                $rand_num = rand(100, 500);
//                $month = date('Ymd' , strtotime("+$i month" , $time));
//        
//                for ($j = 0; $j < $rand_num; $j++)
//                {
//                    $ip +=1;
//                    $save_ip = long2ip($ip);
//                    $like_model->insert(array('ip' => $save_ip , 'create_time' => $month , 'flat_id' => $new_flat_id));
//                }
//            }
            $like_list = $like_model->getData($where);
            $data3 = array();
            foreach ($like_list as $info)
            {
                $today = (int) substr($info['create_time'] , -2);
                if (!isset($data3[$today]))
                    $data3[$today] = 1;
                else
                    $data3[$today] ++;
            }

            // 计算预约数
            $yymodel = new \Common\Model\Jzuser\Subscribe(); // 预约数
            $sql = $yymodel->getSqlObject();
            $select = $sql->select(array('s' => 'subscribe'));

            $DB_NAME = C('subsite:subsite.DB_NAME');
          
            $select->leftjoin(array('pwh' => new Expression($DB_NAME.'.wxxz_house')) , 'pwh.id = s.source_id');
            $select->leftjoin(array('pwf' => new Expression($DB_NAME.'.wxxz_flat')) , 'pwf.id = pwh.flat_id');
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('pwf.id' , $flat_id);
            $where->equalTo('s.source' , 1);
            $select->where($where);
            $datayytemp = $select->execute(); // 取出来公寓下的所有预约 , 没有时间筛选


            foreach ($datayytemp as $yk => $yv)
            {
                if (date('Yn' , $yv['look_time']) == $year . $month) //今年当月
                {
                    $yv['look_time'] = date('j' , $yv['look_time']);
                    $datayy1['total'][] = $yv;
                }
            }
            $datayy['totalMonth'] = count($datayy1['total']); // 当月总预约数
            foreach ($datayy1['total'] as $k1 => $v1)
            {
                $datayyevery['everyday'][$v1['look_time']][] = $v1;
            }
            foreach ($datayyevery['everyday'] as $k2 => $v2)
            {
                $datayy['everyday'][$v2['look_time']] = count($v2); // 月内每天的总预约
            }
            // 最后组装数组 访客数  收藏数  预约数
            $all['fk'] = $totalMonthfk;
            $all['sc'] = count($like_list);
            $all['yy'] = $datayy['totalMonth'];

            for ($num = 1; $num <= $day; $num++)
            {
                $all['everyday'][$num][0] = count($data1['everyday'][$num]);
                $all['everyday'][$num][1] = (int) $data3[$num];
                $all['everyday'][$num][2] = count($datayyevery['everyday'][$num]);
            } // print_r($all);


            return $all;
        }

    }
    