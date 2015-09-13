<?php
namespace Common\Helper;
/**
 * 字符串操作
 * @author lishengyou
 * 最后修改时间 2014年11月5日 下午1:22:00
 *
 */
class String{
	const RAND_TYPE_NUMBER = 1;//数字
	const RAND_TYPE_LETTER = 2;//字母
	const RAND_TYPE_ZH = 3;//中文
	const RAND_TYPE_NUMBER_LETTER = 4;//字母和数字
	/**
	 * 生成随机字符
	 * @author lishengyou
	 * 最后修改时间 2014年11月5日 下午1:24:08
	 *
	 * @param unknown $length
	 * @param unknown $type
	 */
	public static function rand($length=4,$type = self::RAND_TYPE_LETTER){
		$library = Null;
		switch ($type){
			case self::RAND_TYPE_NUMBER:
				$library = range(0, 9);
				break;
			case self::RAND_TYPE_LETTER:
				$library = range('a', 'z');
				$library = array_merge($library,range('A', 'Z'));
				break;
			case self::RAND_TYPE_NUMBER_LETTER:
				$library = range('a', 'z');
				$library = array_merge($library,range('A', 'Z'));
				$library = array_merge($library,range(0, 9));
				break;
			case self::RAND_TYPE_ZH:
				$library = self::getZhFont();
				break;
		}
		$len = count($library)-1;
		$string = '';
		for ($i = 0;$i<$length;$i++){
			$rand = mt_rand(0, $len);
			$string .= $library[$rand];
		}
		return $string;
	}
	/**
	 * 取得中文字库
	 * @author lishengyou
	 * 最后修改时间 2014年11月5日 下午1:29:04
	 *
	 */
	public static function getZhFont(){
		$string = '无题李商隐相见时难别亦难东风无力百花残春蚕到死丝方尽蜡炬成灰泪始干
				晓镜但愁云鬓改夜吟应觉月光寒蓬山此去无多路青鸟殷勤为探看江淮汽车祝大家
				龙年大发身体健康的一是在了不和有大这主中人上为们地个用工时要动国产以我
				到他会作来分生对于学下级就年阶义发成部民可出能方进同行面说种过命度革而
				多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政
				三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队
				批如应形想制心样干都向变关点育重其思与间内去因件日利相由压员气业代全组
				数果期导平各基或月毛然问比展那它最及外没看治提五解系林者米群头意只明四
				道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验
				活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观
				调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示
				复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即
				毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止
				边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁
				需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌
				始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底
				床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削
				信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约
				巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却
				充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师
				举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫
				字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈
				伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株
				亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁
				雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹
				吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌
				氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀
				鲜吧卡粗介钻逐弱脚怕盐末阴丰编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异
				序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演
				威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖
				牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉
				壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横
				掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲
				龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥
				奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾
				胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂
				灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚
				丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔
				啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬
				涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫康遵牧遭幅
				园腔订香肉弟屋敏恢忘衣孙龄岭骗休借丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘
				吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯
				荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴
				秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙
				趋彼届墨碍启逆卸航雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟
				蚕亿矩三于干亏士工土才寸下大丈与万上小口巾山千乞川亿个';
		$arr = array();
		$string = str_replace("\n",'',$string);
		$string = str_replace("\r",'',$string);
		$string = str_replace("\t",'',$string);
		preg_match_all("/./u", $string, $arr);
		return $arr[0];
	}
	/**
	 * 取得首字母
	 * @author lishengyou
	 * 最后修改时间 2014年12月4日 下午2:52:23
	 *
	 * @param unknown $str
	 * @return string|NULL
	 */
	public static function getFirstCharter($str){
		$str = mb_substr($str,0,1,'utf-8');
		if(is_numeric($str)){
			return $str;
		}
		$strArray = \Pinyin::parse(mb_substr($str,0,1,'utf-8'),array('accent'=>false,'uppercase'=>true));
		return trim($strArray['letter']);
	}

	/**
	 * 计算字符串长度 (一个中文顶两个字符时)
	 * @author too|编写注释时间 2015年6月15日 下午6:57:25
	 */
	public static function countStrLength($param)
	{
        return (strlen($param) + mb_strlen($param))/2;
	}
}