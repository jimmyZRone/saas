define(function (require, exports, moudles) {
    return function (jquery) {
        (function ($) {
		  //此方法适用范围：形如<label></label><input type="radio" name="123"/><label></label><input type="radio" name="123"/>
		  $.fn.Radio = function () {
		  			var name = $(this).next().attr("name"); 
					$(this).addClass("checked").siblings("label").removeClass("checked");
					$(this).children(".choose").show().siblings(".notChoose").hide();
					$(this).siblings("label").children(".choose").hide().siblings(".notChoose").show();
					$(this).siblings("input[name='"+name+"']").removeAttr("checked");
					$(this).next().attr("checked",true);
		        }
		  //此方法适用范围：形如<li><label></label><input type="radio" name="123"/></li><li><label></label><input type="radio" name="123"/></li>
		 $.fn.Radios = function(){
		 	var name = $(this).next().attr('name');
//		 	console.log($(this));
		 	$(this).addClass('checked').parent().siblings('.radio-box').find('label').removeClass('checked');
			$(this).siblings('input').attr('checked',true).removeClass("Validform_error");
			$(this).parent().siblings().children("input[name='"+name+"']").attr('checked',false).removeClass("Validform_error");
			$(this).parent().siblings(".check-error").hide();
			$(this).children('.r-default').hide().siblings('.r-select').show();
			$(this).next().parent().siblings('.radio-box').find('.r-select').hide();
			$(this).next().parent().siblings('.radio-box').find('.r-default').show();
//			$(this).next().parent().siblings().children("input[type='radio']").removeAttr("checked");
		 }
        })(jquery);
    }
	//使用方法：
	//obj.click(function(){
	//	obj.Radio();
	//})
});