<?php

    namespace App\Api\Helper;

    class FeeType extends \Common\Helper\Erp\FeeType
    {

        /**
         * 获取公司费用列表信息
         * 
         * @author yusj | 最后修改时间 2015年5月4日下午3:47:44
         */
        public function getFeeTypeListByCompanyID($company_id)
        {
            $feeTypeModel = new \Common\Model\Erp\FeeType ();
            $sql = $feeTypeModel->getSqlObject();
            $select = $sql->select(array(
                'ft' => $feeTypeModel->getTableName()
            ));
            $select->columns(array('fee_type_id' , 'type_name'));
            $where = new \Zend\Db\Sql\Where (); // 造where条件对象
            $where->equalTo('ft.company_id' , $company_id);
            $where->equalTo('ft.is_delete' , 0); // 集中房源的流水
            $select->where($where);
            $data = $select->execute();
            return $data;
        }

        public function addFee($house_id , $source , $fee_list)
        {


            $feeTypeModel = new \App\Api\Helper\FeeType();
            $T = new \App\Api\Helper\User\TokenAuth();
            $user = $T->getTokenInfo(I('token'));
            $F = M('Fee');
            $company_id = $user['company_id'];
            $fee_type = $feeTypeModel->getFeeTypeListByCompanyID($company_id);
            $fee_type = getArrayKeyClassification($fee_type , 'fee_type_id' , 'type_name');
            //删除未传递的fee_id,费用
            $fee_id_list = getArrayValue($fee_list , 'fee_id');


            $where = new \Zend\Db\Sql\Where();

            $where->equalTo('source_id' , $house_id);
            $where->equalTo('source' , $source);
            if (count($fee_id_list) > 0)
                $where->notIn('fee_id' , $fee_id_list);

            $delete = $F->delete($where);

            if (!$delete)
            {
                return false;
            }


            foreach ($fee_list as $info)
            {
                $id = $info['fee_type_id'];
                if (!isset($fee_type[$id]))
                    return false;
                $data = array(
                    'payment_mode' => $info['payment_mode'] ,
                    'money' => $info['money'] ,
                    'source' => $source ,
                    'source_id' => $house_id ,
                );
                //修改
                if (isset($info['fee_id']))
                {
                    $result = $F->edit(array('fee_id' => $info['fee_id']) , $data);
                }
                else
                {
                    //添加
                    $data['create_time'] = time();
                    $data['fee_type_id'] = $id;
                    $data['is_delete'] = 0;

                    $result = $F->insert($data);
                }


                if (!$result)
                    return false;
            }

            return true;
        }

    }
    