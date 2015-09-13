window.APP_WEB = window.APP_WEB || {};
window.APP_WEB.CONST = window.APP_WEB.CONST || {};
window.APP_WEB.CONST.VERSION = window.APP_WEB.CONST.VERSION || '2.0';
var baseUri = '';
var v_node = APP_WEB.CONST.VERSION; //版本号
(function(){//动态取得JS路径
	var jsFiles = document.scripts;
	var currentPath = jsFiles[jsFiles.length-1].src;
	currentPath = currentPath.replace(/\/+/,'/');
	if(currentPath.indexOf(document.location.protocol) !== 0 && currentPath.indexOf('/') !== 0){
		//使用的相对目录
		baseUri = document.URL.replace(/\/+/,'/').split('/');
		baseUri.splice(baseUri.length-1,1);
		var currentPathSplit = currentPath.split('/');
		currentPathSplit.splice(currentPathSplit.length-1,1);
		var i=0;
		for(;i<currentPathSplit.length;i++){
			if(currentPathSplit[i] != '..'){
				break;
			}
			baseUri.splice(baseUri.length-1,1);
		}
		currentPathSplit.splice(0,i);
		currentPath = baseUri.join('/');
		if(currentPathSplit.length>0){
			currentPath = currentPath + '/' + currentPathSplit.join('/');
		}
	}else if(currentPath.indexOf('/') === 0){
		currentPath = currentPath.split('/');
		currentPath.splice(currentPath.length-1,1);
		currentPath = (document.location.protocol+'//'+document.location.host)+currentPath.join('/');
	}
	baseUri = currentPath.substring((document.location.protocol+'//'+document.location.host).length);
	baseUri = baseUri.replace(/\/+/,'/');
	baseUri = baseUri.split('/');
	if(baseUri[baseUri.length-1].indexOf('.') > -1){
		baseUri.splice(baseUri.length-1,1);
	}
	baseUri = '/'+baseUri.join('/')+'/';
	baseUri = baseUri.replace(/\/\//g,'/');
})();
seajs.config({
	base:baseUri,
	alias: {
		'jquery': 'jquery/jquery/1.10.1/jquery.min.js',
		'validForm':'validform/validform.js',                           //表单验证插件
		'Ajax':'modules_JS/html_Ajax.js',								//ajax
		'calendar':'calendar/WdatePicker.js',							//时间录入控件
		'calendar_Ind':'calendar/calendar_Ind.js',                      //首页时间控件
		'mousewheel':'mousewheel/jquery.mousewheel.js',                 //滚轮滚动侦测插件
		'combobox':'combobox/jquery.combobox.js',                       //信息检索
		'navigatortest':'modules_JS/navigatorTest.js',                  //检测浏览器类型及版本
		'uplodify':'uplodify/jquery.uploadify.min.js',                  //图片上传基础文件
		'uplodify_xz':'uplodify/uploadify.min.js',                  //图片上传基础文件-小站
		'uplodify_dt':'uplodify/uploadify_dt.min.js',                  //图片上传基础文件-小站详情
		'json2':'uplodify/json2.js',                                    //解决IE8以下关于json的Bug
		'highcharts':'highcharts/highcharts.js',						//图表绘制插件
		'selectByM':'modules_JS/select.js',							    //自定义下拉列表
		'placeholder':'modules_JS/placeholder.js',						//UI占位符兼容
		'tagCreate':'modules_JS/tagCreate.js',							//模板加载
		'loading':'loading/loading.js',									//页面加载等待
		'backbone':'backbone/backbone.js',								//backbone插件（浏览器回退兼容）
		'underscore':'backbone/underscore.js',							//浏览器回退兼容
		'backbone_Inite':'backbone/backbone_inite.js',					//启动backbone
		"dialog":'dialog/dialog-plus.js',								//页面弹出框
		'radio':'modules_JS/radio.js',                                   //自定义单选框
		'mod_statics':'modules_JS/mod_statics.js',						//首页统计模块
		"raty":'Raty/jquery.raty.js',									//评分插件
		"pagination":'pagination/jquery.paging.js',						//分页插件
		"artTemp":'artTemplate/template-native.js',					//js模板渲染
		"indexCalander":'modules_JS/mod_calendar.js',				//首页日历模块
		"url":'modules_JS/url.js',									//URL地址处理模块
		"child_data_hash":"modules_JS/child_data_hash.js",		//取得借点的数据HASH
		"colorpicker":"bigcolorpicker/jquery.bigcolorpicker.js", //颜色选择
		"ZeroClipboard":"ZeroClipboard/ZeroClipboard.js",         //复制文本到剪切板
		"dragsort":"jquery-list-dragsort/jquery-list-dragsort.js",         //复制文本到剪切板
		"cropper":"cropper/cropper.js",										//	图片裁剪
		"sha1":"modules_JS/sha1.js",									//加密
		"sms":"plugin/temp-sms.js",									//短信发送模板
		/*
		 * 各模板对应Js
		 */
		"spread_house":'application_Js/spread_house.js',					//分散式首页
		'account_chance_pwdJs':'application_Js/account_chance_pwdJs.js',	//修改登录密码
		'account_setJs':'application_Js/account_setJs.js',					//用户中心
		'account_chang_safe_pwd':'application_Js/account_chang_safe_pwd.js', //修改冲账密码
		'city_choose':'application_Js/city_choose.js',  //选择城市
		/*
		 * 员工管理
		 */
		'workerManage_IndJs':'application_Js/workerManage_IndJs.js',		 //首页
		'workerManage_AddJs':'application_Js/workerManage_AddJs.js',		//添加员工
		'workerManage_AuthorityManageJs':'application_Js/workerManage_AuthorityManageJs.js',//权限管理
		'workerManage_Authority':'application_Js/workerManage_Authority.js',				//权限分配
		'workerManage_RoomManageJs':'application_Js/workerManage_RoomManageJs.js',			//房源分配
		'workerManage_AuthorityManageJs':'application_Js/workerManage_AuthorityManageJs.js',//分组管理
		/*
		 * 集中式公寓
		 */
		'centralized_ManageJs':'application_Js/centralized_ManageJs.js',     //公寓管理列表
		'centralized_IndJs':'application_Js/centralized_IndJs.js',		     //公寓房源列表
		'centralized_add_house_bulkJs':'application_Js/centralized_add_house_bulkJs.js', //批量添加公寓房间
		'centralized_add_houseJs':'application_Js/centralized_add_houseJs.js',	//添加房源
		'centralized_add_modeJs':'application_Js/centralized_add_modeJs.js',					//添加房源模板
		'centralized_Depart_InfoJs':'application_Js/centralized_Depart_InfoJs.js',				//公寓信息编辑
		'centralized_house_viewJs':'application_Js/centralized_house_viewJs.js',		//公寓房间信息详细编辑
		'centralized_RoomsConfigJs':'application_Js/centralized_RoomsConfigJs.js',		//公寓房间配置
		'center_define_houseNumerJs':'application_Js/center_define_houseNumerJs.js',	//自定义房源编号
		'center_define_roomsJs':'application_Js/center_define_roomsJs.js',			//自定义每层房间数
		'centralized_add_modeJs':'application_Js/centralized_add_modeJs.js',		//添加模板
		'meterJs':'application_Js/meterJs.js',																	//抄表

		/*
		 * 分散式公寓
		 */
		'distributed_add_round_houseJs':'application_Js/distributed_add_round_houseJs.js',		//添加房间
		'distributed_room_viewJs':'application_Js/distributed_room_viewJs.js',		//合租添加房间

		/*
		 * 客户管理
		 */
		'customer_manage':'application_Js/customer_manage.js',						//租客管理
		'customer_detail':'application_Js/customer_detail.js',						//租客详情
		'agrmt_detail':'application_Js/agrmt_detail.js',								//合同详情
		'customer_reserve_list':'application_Js/customer_reserve_list.js',			//预订管理
		/*
		 * 业主管理
		 */
		'manager_landlord_listJs':'application_Js/manager_landlord_listJs.js',     //业主列表
		'manager_landlord_msgJs':'application_Js/manager_landlord_msgJs.js',      //业主信息

		/*
		 * 财务管理
		 */
		'finance_indexJs':'application_Js/finance_indexJs.js',            //财务列表
		'finance_debtsJs':'application_Js/finance_debtsJs.js',            //欠费清单
		'finance_add_incomeJs':'application_Js/finance_add_incomeJs.js',  //新增收入
		'finance_add_dexpenseJs':'application_Js/finance_add_dexpenseJs.js',  //新增支出
		'finance_viewJs':'application_Js/finance_viewJs.js',               //详情
		'finance_typeJs':'application_Js/finance_typeJs.js',               //财务类型

		/*
		 * 消息列表
		 */
		'message_listJs':'application_Js/message_listJs.js',        //消息列表

		/**
		 * 微信小站
		 */
		'opening_basic_setJs':'plug-station_Js/opening_basic_setJs.js',      //开通中-基本设置
		'opening_picture_setJs':'plug-station_Js/opening_picture_setJs.js',      //开通中-图片设置
		'opening_house_setJs':'plug-station_Js/opening_house_setJs.js',      //开通中-房源设置
		'wechat_loginJs':'plug-station_Js/wechat_loginJs.js',      //微信登录
		'data_countJs':'plug-station_Js/data_countJs.js',      //数据统计
		'opened_basic_setJs':'plug-station_Js/opened_basic_setJs.js',       //已开通-基本设置
		'openedJs':'plug-station_Js/openedJs.js',      //已开通
		'apply_openJs':'plug-station_Js/apply_openJs.js' ,//申请开通小站

		/**
		 * 智能短信
		 */
		 "apply_sms" :'sms/apply_sms.js' ,//申请开通智能短信
		 "apply_sms_set" :'sms/apply_sms_set.js' //智能短信模板配置




	},
	map: [
    [ /^(.*\/application_Js\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/modules_JS\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/autoComplete\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/uplodify\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/combobox\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/plug-station_Js\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/artTemplate\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/backbone\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/bigcolorpicker\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/calendar\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/cropper\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/dialog\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/formparam\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/highcharts\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/jquery\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/jquery-list-dragsort\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/loading\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/mousewheel\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/pagination\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/Raty\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/sms\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/validform\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ],
    [ /^(.*\/ZeroClipboard\/.*\.(?:css|js))(?:.*)$/i, '$1?'+'v='+v_node ]
  ]
});
