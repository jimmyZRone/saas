define(function(require,exports,module){
	var $ = require('jquery');
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	var uplodify = require("uplodify");   //上传图片插件

	
	var modelInit = function($$){
		
		$(".apply-btn").off("click").on("click",function(){
			var type = "POST";
			var url = $(this).attr("url");
			ajax.doAjax(type,url,"",function(json){
				if(json.status == 1){
					var tag = WindowTag.getCurrentTag();
					//关闭当前标签
					WindowTag.closeTag(tag.find('>a:first').attr('url'));
					//跳页面
					if(json.url != '' && json.url != undefined){
						window.location.hash = '#' + json.url;
//						window.WindowTag.openTag('#' + json.url);
					}
				}
			});
			
		});
		

	};
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);

	};
	
});