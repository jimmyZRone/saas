<?php
namespace Common\Helper;
/**
 * 合法性验证
 * @author lishengyou
 * 最后修改时间 2015年4月7日 下午4:57:02
 *
 */
class ValidityVerification{
	/**
	 * 是否是手机号
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午4:57:31
	 *
	 * @param unknown $phone
	 */
	public static function IsPhone($phone){
		return !!preg_match('#1[123456789]\d{9}#', $phone);
	}
	/**
	 * 密码的正确性
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午5:39:33
	 *
	 * @param unknown $passwd
	 */
	public static function IsPasswd($passwd){
		if(strlen($passwd) < 6){
			return array('status'=>0,'message'=>'密码长度不能小于6位');
		}
		if(is_numeric($passwd)){
			return array('status'=>0,'message'=>'密码不能为纯数字');
		}
		return array('status'=>1);
	}
	/**
	 * 验证是否是银行卡
	 * @author too|编写注释时间 2015年6月1日 下午4:53:16
	 */
	public static function IsBankNo($param)
	{
	    return !!preg_match('#^(\d{16}|\d{19})$#', $param);
	}
    /**
     * 是否是邮箱
     * @param  $email
     * @return boolean 1 or 0
     *
     * @author too
     * 最后修改时间 2015年4月15日 下午1:22:12
     */
	public static function IsEmail($email){
	    return !!preg_match('#\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*#', $email);
	}
	/**
	 * 是否是身份证号码
	 * @param unknown $param
	 * @return boolean
	 *
	 * @author too
	 * 最后修改时间 2015年4月15日 下午1:30:39
	 */
	public static function IsId($param){
	    return !!preg_match('#^[0-9a-zA-Z]{15}+$|^[0-9a-zA-Z]{18}+$#',$param);
	}

	/**
	 * 通过身份证号码,提取生日和性别1男2女
	 * @param  $IDCard
	 * @return $birthday 时间戳
	 *
	 * @author too
	 * 最后修改时间 2015年4月16日 上午11:22:44
	 */
    static function getIDCardInfo($IDCard){
	    if(!self::IsId($IDCard)){
	        return false;
	    }
        if(strlen($IDCard)==18){
            $data = array(
                'birthday'=>strtotime(intval(substr($IDCard,6,8))),
                'gender'=>substr($IDCard,-2,1)%2 ==0?2:1
            );
            return $data;
        }
        if(strlen($IDCard)==15){
            $data = array(
                'birthday'=>strtotime(intval('19'.substr($IDCard,6,6))),
                'gender'=>substr($IDCard,-1)%2 ==0?2:1
            );
            return $data;
        }
	}
	/**
	 * 验证手机号码格式包括147
	 * 修改时间  2015年7月2日15:42:25
	 * 
	 * @author ft
	 */
	public static function checkPhoneFormate($phone) {
	    $patter = '/^0?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/';
	    return preg_match($patter, $phone);
	}
	/**
	 * 18位省份证验证
	 * @param unknown $id_card
	 * @return boolean
	 * 
	 * @author  ft
	 */
	public static function validation_filter_id_card($id_card) {
	    if(strlen($id_card) == 18) {
	        return self::idcard_checksum18($id_card);
	    } elseif((strlen($id_card) == 15)) {
	        $id_card = self::idcard_15to18($id_card);
	        return self::idcard_checksum18($id_card);
	    } else {
	        return false;
	    }
	}
	
	public function idcard_verify_number($idcard_base) {
	    if(strlen($idcard_base) != 17) {
	        return false;
	    }
	    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
	    $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
	    $checksum = 0;
	    for ($i = 0; $i < strlen($idcard_base); $i++) {
	        $checksum += substr($idcard_base, $i, 1) * $factor[$i];
	    }
	    $mod = $checksum % 11;
	    $verify_number = $verify_number_list[$mod];
	    return $verify_number;
	}
	
	public function idcard_15to18($idcard){
	    if (strlen($idcard) != 15){
	        return false;
	    }else{
	        if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false){
	            $idcard = substr($idcard, 0, 6) . '18'. substr($idcard, 6, 9);
	        }else{
	            $idcard = substr($idcard, 0, 6) . '19'. substr($idcard, 6, 9);
	        }
	    }
	    $idcard = $idcard . self::idcard_verify_number($idcard);
	    return $idcard;
	}
	
	public function idcard_checksum18($idcard){
	    if (strlen($idcard) != 18) {
	        return false;
	    }
	    $idcard_base = substr($idcard, 0, 17);
	    if (self::idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
	        return false;
	    } else {
	        return true;
	    }
	}
}