/*
===========================================================
* @func:loading效果模块方法封装
* @desc:loading加载等待效果
* @date:2015-3-9
* @author:Jack
* @version:v0.0.1
=============================================================
* */
define(function(require){
	var  $=require("jquery"),
		 src=$(".layout-loading-zone").find("img").attr("src"),
		 _isIE6 = !('minWidth' in $('html')[0].style),
		 pageTemp='<img src="'+src+'" class="loading-img"/>';
	return({
	 /*
	 *遮罩层遮罩加载效果
	 */
	  genPageLoading:function(){
	  	if(_isIE6){
	  		var _ie6Wd=window.screen.width,_ie6Ht=parseInt($(document.body).outerHeight(true));
	  		$("#overlay-loading").css({
	  			"width":_ie6Wd,
	  			"height":_ie6Ht,
	  			"position":"absolute",
	  			"top":0,
	  			"left":0,
	  			"right":0,
	  			"bottom":0,
	  			"overflow":"hidden"
	  		});
	  	}
	   	$("#overlay-loading,.layout-loading-zone").fadeIn(50);
	  },
	 /*
	 *ajax内容加载
	 * @param:obj 填充加载模板
	 */
	  pageLoading:function(obj,temp){
	  	if(temp){
	  		$(obj).html(temp).removeClass("none");
	  	}else{
	  		$(obj).html(pageTemp).removeClass("none");
	  	}
	  },
	 /*
	 *关闭遮罩
	 */
	  removeLoading:function(){
	  	var  that=this;
	  	$("#overlay-loading").click(function(){
	  		that.closeOverlay();
	  	});
	  },
	  closeOverlay:function(){
	    $("#overlay-loading,.layout-loading-zone").fadeOut(10);
	  },
	 /*
	 *@func 生成loading模板
	 * @param :type:tr | li colspan:td属性，当为tr时有效 c:1-loading 模板 2 -错误信息展示
	 * @return 生成的模板
	 */
	genLoading:function(type,colspan,c){
		var temp="";
		if(c==1){
			if(type=="tr"){
				temp='<tr><td align="center" colspan="'+colspan+'">'+pageTemp+'</td></tr>';
			}else{
				temp='<li>'+pageTemp+'</li>';
			}
		}else if(c==2){
			if(type=="tr"){
				temp='<tr><td style="color:#c03838;" align="center" colspan="'+colspan+'">未找到数据</td></tr>';
			}else if(type=="li"){
				temp='<li style="color:#c03838;text-align:center;">未找到数据</li>';
			}else{
				temp='<div style="color:#c03838;text-align:center;">未找到数据</div>';
			}
		}else{
			temp='<div class="loadingTemp">'+pageTemp+'</div>';
		}
		return temp;
	}
  })
});