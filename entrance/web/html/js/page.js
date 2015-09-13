
$(document).ready(function(){
	
	
	/**
	 * TAB切换
	 * 
	 */
	$(".smart-msg-nav > ul > li").click(function(){
		if(!$(this).hasClass("current")){
			$(this).addClass("current").siblings().removeClass("current");
		}else{
			return true;
		}
		if($(".nav-mode").hasClass("current")){
			$(".msg-mode-box").show().siblings(".box").hide();
		}else if($(".nav-condetion").hasClass("current")){
			$(".msg-condetion-box").show().siblings(".box").hide();
		}else if($(".nav-send").hasClass("current")){
			$(".msg-send-box").show().siblings(".box").hide();
		}else if($(".nav-send-record").hasClass("current")){
			$(".send-record-box").show().siblings(".box").hide();
		}else if($(".nav-recharge-record").hasClass("current")){
			$(".recharge-record-box").show().siblings(".box").hide();
		}
	});
	
	/**
	 * 单选框功能
	 * 
	 */
	$(".recharge-bank > ul > li").click(function(){
		var that = $(this).find("label");
		that.addClass("checked");
		that.find(".r-default").hide().siblings(".r-select").show();
		that.siblings("input").attr("checked",true);
		that.parent().siblings().find("label").removeClass("checked");
		that.parent().siblings().find(".r-default").show().siblings(".r-select").hide();
		that.parent().siblings().find("input").attr("checked",false);
		that.parents(".recharge-bank").siblings(".recharge-bank").find("li label").removeClass("checked");
		that.parents(".recharge-bank").siblings(".recharge-bank").find("li label .r-default").show().siblings(".r-select").hide();
		that.parents(".recharge-bank").siblings(".recharge-bank").find("li input").attr("checked",false);
		if(that.parent().hasClass("radio-box")){
			if(that.hasClass("checked")){
				that.parent().siblings().find("label").addClass("checked");
				that.parent().siblings().find("label .r-default").hide().siblings(".r-select").show();
				that.parent().siblings().find("input").attr("checked",true);
			}else{
				that.parent().siblings().find("label").removeClass("checked");
				that.parent().siblings().find("label .r-default").show().siblings(".r-select").hide();
				that.parent().siblings().find("input").attr("checked",false);
			}
		}
	});
	
	
	
	
	
	
	
	
	
	
});
