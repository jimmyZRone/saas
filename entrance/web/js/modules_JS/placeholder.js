define(function (require, exports, moudles) {
    return function (jquery) {
      		(function($){
				$.fn.placeholder = function(){
					function valueIsPlaceholder(input){
						return ($(input).val == $(input).attr("placeholder"));
					}
					return this.each(function(){
						
						$(this).find(":input").each(function(){
							if($(this).attr("type") == "password"){
								
								var new_field = $("<input type = 'text' class='placeholdTxt'>");
								new_field.attr("rel",$(this).attr("id"));
								new_field.attr("value",$(this).attr("placeholder"));
								$(this).parent().append(new_field);
								new_field.hide();
								
								function showPasswordPlaceholder(input){
									if($(input).val() == "" || valueIsPlaceholder(input)){
										$(input).hide();
										$('input[rel=' + $(input).attr("id") + ']').show();
									}
								}
								
								new_field.focus(function(){
									$(this).hide();
									$('input#' + $(this).attr("rel")).show().focus();
								});
								$(this).blur(function(){
									showPasswordPlaceholder(this,false);
								});
								
								showPasswordPlaceholder(this);
							}else{
								//用占位文本替换其值
								//可选的reload参数用来解决Firefox和
								//IE缓存域值的问题
								function showPlaceholder(input,reload){
									if($(input).val() == "" || (reload && valueIsPlaceholder(input))){
										$(input).val($(input).attr("placeholder"));
										$(input).addClass("placeholdTxt");
									}
								}
								
								$(this).focus(function(){
									if($(this).val() == $(this).attr("placeholder")){
										$(this).val("");
										$(this).removeClass("placeholdTxt");
									}
								});
								
								$(this).blur(function(){
									showPlaceholder($(this),false);
								});
								
								showPlaceholder(this,true);
							}
						});
						
						//禁止表单提交默认值
						$(this).submit(function(){
							$(this).find(":input").each(function(){
								if($(this).val() == $(this).attr("placeholder")){
									$(this).val("");
								}
							});
						})
					});
				}
			})(jquery);	
	}
});