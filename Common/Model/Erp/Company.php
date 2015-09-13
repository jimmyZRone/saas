<?php
namespace Common\Model\Erp;
use Zend\Db\Sql\Where;
class Company extends \Common\Model\Erp
{
	/**
	 * 读取公司自定义配置
	 * 修改时间2015年5月5日 16:50:12
	 * 
	 * @author yzx
	 * @param string or array $options  'list/key[;list/key]'  array('list/key'[,'list/key'])
	 * @return array('list'=>array('key'=>'value','key2'=>'value'),'list2'=>array('key'=>'value','key2'=>'value'));
	 */
	public function get($options=FALSE,$user = array()){
		if (empty($user))
		{
			$user = \Common\Helper\Erp\User::getCurrentUser();
		}
		$list = array();
		$retVal = array();
		if($options){
			if(is_string($options)){
				$options = explode(';', $options);
			}
			$tmp = array();
			//归类list
			foreach ($options as $option){
				$option = explode('/', $option);
				if(!isset($list[$option[0]])){
					$list[$option[0]] = array();
				}
				if(isset($option[1])){
					$list[$option[0]][] = $option[1];
				}
			}
			foreach ($list as $key => $value){
				$rs = NULL;
				if(empty($value)){
					//取得list下所有配置
					$rs = $this->getData(array('diy_config_list'=>$key,"company_id"=>$user['company_id']));
				}else{
					//取得list下key列表的值
					if(count($value) == 1){
						$rs = $this->getData(array('diy_config_list'=>$key,'diy_config_key'=>$value[0],"company_id"=>$user['company_id']),'',1);
					}else{
						$where = new Where();
						$where->in('diy_config_key',$value);
						$where->equalTo('diy_config_list', $key);
						$where->equalTo("company_id", $user['company_id']);
						$rs = $this->getData($where);
					}
				}
				if(is_array($rs)){
					$retVal = array_merge($retVal,$rs);
				}
			}
		}else{
			//取得所有配置
			$retVal = $this->getData(array("company_id"=>$user['company_id']));
		}
		//整理最后的返回格式
		$_retVal = array();
		foreach ($retVal as $value){
			if(!isset($_retVal[$value['diy_config_list']])){
				$_retVal[$value['diy_config_list']] = array();
			}
	
			if ('object' == $value['diy_config_type'] || 'array' == $value['diy_config_type'] || 'resource' == $value['diy_config_type']){
				$value['company_diy_config'] = unserialize($value['company_diy_config']);
			}
			//查询是否已经存在
			$_retVal[$value['diy_config_list']][$value['diy_config_key']] = $value['company_diy_config'] ? $value['company_diy_config'] : '';
		}
	
		//print_r($_retVal);
		//处理没有查询到的值
		foreach ($list as $key => $value){
			if(!empty($value)){
				$value = array_fill_keys($value,NULL);
			}
			if(!isset($_retVal[$key])){
				$_retVal[$key] = $value;
			}else{
				$_retVal[$key] = array_merge($value,$_retVal[$key]);
			}
		}
		return $_retVal;
	}
	/**
	 * 设置配置
	 * 修改时间2015年5月12日 10:35:08
	 * 
	 * @author yzx
	 * @param array $data  array('key'=>'list/key','value'=>'value') or array(array('key'=>'list/key','value'=>'value'),array('key'=>'list/key','value'=>'value'));
	 * @return bool
	 */
	public function set($data=array(),$user=array()){
		$retVal = TRUE;
		if(isset($data[0])){
			//更新插入多条
			// 			$this->sql->Transaction();
			$this->_sql_object->Transaction();
			foreach ($data as $value){
				if(!$this->set($value)){
					// 					$this->sql->rollback();
					$this->_sql_object->rollback();
					$retVal = FALSE;
					break;
				}
			}
			if($retVal){
				//全部更新成功，提交事务
				//$this->sql->commit();
				$this->_sql_object->rollback();
			}
		}else{
			//更新插入一条
			$where = array('diy_config_list'=>explode('/', $data['key']));
			if(!isset($where['diy_config_list'][1])){
				$retVal = FALSE;
			}else{
				$where['diy_config_key'] = $where['diy_config_list'][1];
				$where['diy_config_list'] = $where['diy_config_list'][0];
				$where['company_id'] = $user['company_id'];
				
				$type = gettype($data['value']);
				//查询是否已经存在
				if ('object' == $type || 'array' == $type || 'resource' == $type) {
					$value = serialize($data['value']);
				}else{
					$value = $data['value'];
				}
				// 				$data = $this->sql->select($this->table)->where($where)->limit(1)->execute();
				$data = $this->getOne($where);
				if(!!$data){
					//已经存在更新
					$data = $data[0];
					if($data['company_diy_config'] != $value){
						//内容不相同，需要更新
						$retVal = $this->edit($where, array('company_diy_config'=>$value,'diy_config_type'=>$type));
					}
				}else{
					//不存在创建
					$data = array(
							'diy_config_list'=>$where['diy_config_list'],
							'diy_config_key'=>$where['diy_config_key'],
							'diy_config_type'=> $type,
							'company_diy_config'=>$value,
							'description'=>''
					);
					$retVal = $this->edit(array("company_id"=>$user['company_id']), $data);
				}
			}
		}
		return !!$retVal;
	}
	/**
	 * 通过key值查找配置
	 * 修改时间2015年5月5日 16:56:51
	 * 
	 * @author yzx
	 * @param unknown $key
	 * @return Ambigous <multitype:multitype: , string, mixed>
	 */
	public function getDataByKey($key){
		$list = array();
		$retVal = $this->getData(array('diy_config_key'=>$key));
		//整理最后的返回格式
		$_retVal = array();
		foreach ($retVal as $value){
			if(!isset($_retVal[$value['diy_config_list']])){
				$_retVal[$value['diy_config_list']] = array();
			}
	
			if ('object' == $value['diy_config_type'] || 'array' == $value['diy_config_type'] || 'resource' == $value['diy_config_type']){
				$value['value'] = unserialize($value['value']);
			}
			//查询是否已经存在
			$_retVal[$value['diy_config_list']][$value['diy_config_key']] = $value['company_diy_config'] ? $value['company_diy_config'] : '';
		}
	
		//print_r($_retVal);
		//处理没有查询到的值
		foreach ($list as $key => $value){
			if(!empty($value)){
				$value = array_fill_keys($value,NULL);
			}
			if(!isset($_retVal[$key])){
				$_retVal[$key] = $value;
			}else{
				$_retVal[$key] = array_merge($value,$_retVal[$key]);
			}
		}
		return $_retVal;
	
	}
}