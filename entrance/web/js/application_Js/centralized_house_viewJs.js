define(function(require,exports){
	var $ = require('jquery');
		require("selectByM")($);
	var navigators = require("navigatortest");  //浏览器版本检测

	
	exports.inite = function(__html__){
		var $$ = __html__;
		//调用下拉框JS
		$(".selectByM",$$).selectObjM();
		
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".view",$$).placeholder();
		};
		
		//复选框选择及全选、反选
		$(".centralized_house_view",$$).find(".check-box").children("label").off("click").on("click",function(){
			if(!$(this).hasClass("checkAll")){
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children(".choose").show();
					$(this).next().attr("checked",true);
				}else{
					$(this).children(".choose").hide();
					$(this).next().removeAttr("checked");
				}
			}else{
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children(".choose").show();
					$(this).next().attr("checked",true);
					$(this).parent().siblings().children("label").addClass("checked").children(".choose").show();
					$(this).parent().siblings().children("input").attr("checked",true);
				}else{
					$(this).children(".choose").hide();
					$(this).next().removeAttr("checked");
					$(this).parent().siblings().children("label").addClass("checked").children(".choose").hide();
					$(this).parent().siblings().children("input").removeAttr("checked");
				}
			}
		});		
	}
});