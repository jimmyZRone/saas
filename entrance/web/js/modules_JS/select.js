define(function (require, exports, moudles) {
	function close(obj){
//		var time;
//		clearTimeout(time);
//		time = setTimeout(function(){
//			obj.slideUp(300);
//		},50);
		obj.hide();
	}
	function selects(obj,inp,func){
		$(obj).find(".selectByMO > ul > li").die().live('click', function(event){
			event.stopPropagation();
			var _this = $(this);
			var val = _this.attr("selectVal");
			var texts = _this.text();
			if(_this.hasClass("charts-auto")) return false;
			inp.val(texts);
			inp.attr("selectVal",val);
			_this.addClass("selectedLi").siblings().removeClass("selectedLi");
			close(_this.parent().parent());
			inp.focus().blur();
			if(typeof(func) == 'undefined') return true;
			else if(inp.attr("selectVal") == inp.attr("prev-value")) return true;
			inp.attr("prev-value",inp.attr("selectVal"));
			func(val, inp);
		})
	}
	function open(obj){
//		obj.slideDown(100);
		obj.show();
	}
	return function (jquery) {
		 (function ($) {
		 	$.fn.selectObjM = function(e,func){
		 		if(typeof(e) == 'undefined'){
		 			e = 1;
		 		}
		 			var obj = $(this).children(".selectByMT");   //获得新生成下拉列表的输入框
					selects(this,obj,func);
			 		if(e == 1){
			 			$(obj.siblings(".jt_Ctr"),obj.siblings(".jt_Ctr").children()).click(function(event){
			 				event.stopPropagation();
			 				close($(".selectByM").children("div"));
			 				open(obj.siblings(".selectByMO"));
			 			});
			 			$(obj).click(function(event){
			 				event.stopPropagation();
			 				close($(".selectByM").children("div"));
			 				open(obj.siblings(".selectByMO"));
			 			});
			 		}else{
			 			$(obj.siblings(".jt_Ctr"),obj.siblings(".jt_Ctr").children()).click(function(event){
			 				event.stopPropagation();
			 				close($(".selectByM").children("div"));
			 				open(obj.siblings(".selectByMO"));
			 			});
			 			$(obj).keyup(function(){
			 				var VAL = $(this).val();
			 				close($(".selectByM").children("div"));
			 				$(this).attr("selectVal",VAL)
			 			});
			 		}
			 		$("body,html").click(function(){
			 			close(obj.siblings(".selectByMO"));
			 		});
		 	}
		 })(jquery);
	}
//	使用方法：
//	obj.selectObjM(e);   e为1或者2  1代表正常的下拉列表   2代表可输入的下拉列表
});