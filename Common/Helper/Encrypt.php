<?php
namespace Common\Helper;
/**
 * 加密
 * @author lishengyou
 *
 */
class Encrypt{
	/**
	 * MD5加密
	 * @param unknown $str
	 * @return string
	 */
	public static function md5($str){
		return md5($str);
	}
	/**
	 * SHA1加密
	 * @param unknown $str
	 * @return string
	 */
	public static function sha1($str){
		$str = self::md5($str);
		$str = ~$str;
		return sha1($str);
	}
	/**
	 * MD5 16位加密
	 * @param unknown $str
	 * @return string
	 */
	public static function md5_16($str){
		$str = self::md5($str);
		return substr($str,8,16);
	}
	/**
	 * RSA 加密
	 * @author lishengyou
	 * 最后修改时间 2014年11月5日 下午8:01:48
	 *
	 * @param unknown $sourcestr
	 * @return string|boolean
	 */
	public static function rsaEncodeing($sourcestr){
		$key_content = '-----BEGIN RSA PRIVATE KEY-----
						MIICXQIBAAKBgQDDXghh58EP7gRlrDz0FAufnvmGg/3XC7WUmN2XMOjups2+0yDP
						Pfa7xoDr6hw0cVV+278n074UJ+736d4XebbxCNkxVH9GH3dRasWWTc5Ymx7+a748
						XAkWjWk6316dyj0QFnw5TLhf1SR6LxNxvaNQzW6D+F9nu3gJJR5BwTFCXQIDAQAB
						AoGBAL5ON74fAaohwXjEyW88o4HuWsQUiMzUZCGGsruW8h+ermZGxPv7MQACwgyM
						NMNE7vIu3krOcKazq40k66lUb8Ut1S8taYcisuT0vloeG8qbQoiuoVGRVWAioazL
						cg12SRqi+V05fUMMLuqrGkspqJtGzqM0D4RZNd/fTZE3ENdlAkEA/v3oG23vO41K
						41QPiFXNZjIOTVSQKeChdxQPTeTVXBHJAj6eUeQ6v9IeAI37FLgzYMx/XNmQPQpa
						bZcH22Jz9wJBAMQjxr6Kae7r9rkG5xykfMpPC4Th7zOunfMzecjX9td82/QJHt05
						adizjJZg9m73LnpDdcgLP2ueyBuql9LMv0sCQQCuUOFVrwe2jFa/pX2g1BdAX8PL
						NZ4AIuH+x6XWuDLrZ/UkJa6RiRZof7mm42jbtzjYWbRPwyOJtwQumuryHRHtAkA/
						OeEpLukzEXF495asjwGDHbPy4/n9yP41lZRef++cSy2EHySJ36YVKtvY5ezKnHep
						BfIDyExrXsXW1UkXtNS9AkBGrNhr7cWZGwmWb12l1tvDL58UqQuI0y+mlhGV8zhX
						8BMBQA+bYJzNcw91cQtxK7ZWgz3WXVaL2MdxVMAYza0N
						-----END RSA PRIVATE KEY-----';
		$key_content = str_replace(array("\t"), '', $key_content);
		$pi_key = openssl_pkey_get_private ( $key_content );
		$crypttext = '';
		if (openssl_private_encrypt ( $sourcestr, $crypttext, $pi_key )) {
			return base64_encode ( "" . $crypttext );
		}
		return false;
	}
	/**
	 * RSA 解密
	 * @author lishengyou
	 * 最后修改时间 2014年11月5日 下午8:04:42
	 *
	 * @param unknown $crypttext
	 * @return string|boolean
	 */
	public static function rsaDecodeing($crypttext){
		$key_content = '-----BEGIN PUBLIC KEY-----
						MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDXghh58EP7gRlrDz0FAufnvmG
						g/3XC7WUmN2XMOjups2+0yDPPfa7xoDr6hw0cVV+278n074UJ+736d4XebbxCNkx
						VH9GH3dRasWWTc5Ymx7+a748XAkWjWk6316dyj0QFnw5TLhf1SR6LxNxvaNQzW6D
						+F9nu3gJJR5BwTFCXQIDAQAB
						-----END PUBLIC KEY-----';
		$key_content = str_replace(array("\t"), '', $key_content);
		$pu_key = openssl_pkey_get_public ( $key_content );
		$crypttext = base64_decode ( $crypttext );
		$sourcestr = '';
		openssl_public_decrypt( $crypttext, $sourcestr, $pu_key );
		return $sourcestr;
	}
}