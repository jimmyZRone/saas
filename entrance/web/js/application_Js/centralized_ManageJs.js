define(function(require,exports){
	var $ = require('jquery'),
		navigators = require("navigatortest");
	var dialog = require("dialog");  //弹窗插件
	var ajax = require("Ajax");
	var $$ = null;
	
	var centralized_ManageJs = {
		setHeight : function(){
			var A = $(".head");
			var B = $(".tag");
			var C = $(".centralized_Add",$$);
			$(".centralized_List",$$).height($(window).height()-A.height()-B.height()-C.height()-5-158);
			$(".centralized_List_Date",$$).height($(window).height()-A.height()-B.height()-C.height()-5-10);
			$(".centralized_List_Date",$$).children(".empty").css("padding",($(".centralized_List_Date",$$).height()-126)/2+"px,0");
			if($(".centralized_List ul li").size() == 0){
				$(".centralized_List",$$).hide();
				$(".centralized_List_Date",$$).show();
			}else{
				$(".centralized_List",$$).show();
				$(".centralized_List_Date",$$).hide();
			}
		},
		deleteFlat : function(){
			$(".flat-delete",$$).off("click").on("click",function(){
				var that = $(this);
				var url =that.parents("ul").attr("url");
				var flat_id = that.parents("li").attr("flat-id");
				var type = "get";
				var data = {
					flat_id : flat_id
				};
				var d = dialog({
					title: '<i class="ifont">&#xe77d;</i><span>删除公寓</span>',
					content:'删除的信息将无法得到恢复，确定删除？',
					okValue: '确定',
					ok: function () {
						ajax.doAjax(type,url,data,centralized_ManageJs.callback);
						that.parents("li").addClass("deleteStyle");
						d.close();
					},
					cancelValue: '取消',
					cancel: function () {
						d.close();
					}
				});
				d.showModal();
			});
		},
		callback : function(data){
			if(data.status == 1){
				var d = dialog({
					title: '提示信息',
					content:'删除成功',
					okValue: '确定',
					ok: function () {
						var flat_id = $(".centralized_List li.deleteStyle",$$).attr("flat-id");
						$(".roomTypeOps",".total-house").find(".selectByMO ul li").each(function(){
							if($(this).attr("selectval") == flat_id){
								$(this).remove();
							}
						});
						$(".centralized_List li.deleteStyle",$$).remove();
						d.close();
						if($(".centralized_List ul li").size() == 0){
							$(".centralized_List",$$).hide();
							$(".centralized_List_Date",$$).show();
						}else{
							$(".centralized_List",$$).show();
							$(".centralized_List_Date",$$).hide();
						}
					}
				});
			}else{
				var d = dialog({
					title: '提示信息',
					content:data.data,
					okValue: '确定',
					ok: function () {
						$(".centralized_List li.deleteStyle",$$).removeClass("deleteStyle");
						d.close();
					}
				});
			}
			d.showModal();
		}
	}
	
		exports.inite = function(__html__){
			$$ = __html__;
			var that = centralized_ManageJs;
			$(".centralized_List",$$).find("li").children(".name").hover(function(){
				$(this).parent().addClass("changeColor");
				$(this).siblings(".list_SomeDetail").show();
			},function(){
				$(this).parent().removeClass("changeColor");
				$(this).siblings(".list_SomeDetail").hide();
			});
			$(".centralized_List",$$).find("li").children(".name").off("click").on("click",function(){
				var url = $(this).siblings(".jt_Box").find(".manage_auto").attr("href");
				window.location.href  = url;
			});
			$(".centralized_List",$$).find(".jt_Box").hover(function(event){
				event.stopPropagation();
				$(this).children(".list_Menu").show();
				$(this).siblings(".list_SomeDetail").hide();
			},function(event){
				event.stopPropagation();
				$(this).children(".list_Menu").hide();
			});
	
			var length_Centralized_List = $(".centralized_List",$$).find("li").length;
			$(".centralized_List",$$).find("li").each(function(){
				var num = $(this).index();
				$(this).css("z-index",length_Centralized_List-num);
			});	
			that.deleteFlat();
			that.setHeight();
		}
});