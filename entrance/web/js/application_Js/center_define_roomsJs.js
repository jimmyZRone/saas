define(function(require,exports){
	var $ = require('jquery');
	var navigators = require("navigatortest");  //浏览器版本检测
	var loading=require("loading"),
	 	 ajax=require("Ajax"),ajaxLoading;
	 	require("validForm")($);
	 var hash = require('child_data_hash');
	var $$ = null;
	//计算并设置楼层层高度
	function h_center_dpt_txt(){
		var H = $(window).height();	//获取窗体大小
		if(H<768){
			H = 768;
		}
		var a = $(".center-dpt-top",$$).height()+1;
		var b = $(".center-dpt-nav",$$).height()+30;
		var c = $(".dpt-bot-grp",$$).height()+40;
		if(sys.ie && sys.ie < 8){
			b = $(".center-dpt-nav",$$).height()+40;
		}
		$(".center-dpt-txt",$$).css({"height":H-a-b-c-60-125+"px"});
	}
	
	//ie6 7 window.resize兼容
	function debounce(callback, delay, context){  
	    if (typeof(callback) !== "function") {  
	        return;  
	    }  
	    delay = delay || 150;  
	    context = context || null;  
	    var timeout;  
	    var runIt = function(){  
	            callback.apply(context);  
	        };  
	    return (function(){  
	        window.clearTimeout(timeout);  
	        timeout = window.setTimeout(runIt, delay);  
	    });  
	}  
	
	//获取楼层距离楼层列表层顶部位移
	function getFloorTop(){
		var num_Position = [];
		var top = $(".center-dpt-txt",$$).scrollTop();
		$(".center-dpt-txt",$$).children(".clearfix").each(function(){
			num_Position.push($(this).position().top+top);
		});
		
		return num_Position;
	}
	
	//获取楼层列表被选中楼层的第一层楼层数
	function getFloorNumFirst(){
		return $(".center-dpt-nav",$$).find("a.current").find(".floorNum:first").text();
	}
	
	//楼层电梯快速到
	function getFloor(num_Position){
		var current_Floor = getFloorNumFirst();
		getFloorNum(current_Floor,num_Position);
	}
	
	//匹配楼层列表并快速到达
	function getFloorNum(current_Floor,num_Position){
		var isExist = false;		//点击楼层列表中楼层存在状态
		$(".center-dpt-txt > .clearfix",$$).each(function(){
			var floor = $(this).attr("data-floornum");
			var floor_Top;  //获取目标楼层距离父级容器顶部的高度
			if(floor == current_Floor){
				var num = $(this).index();
				floor_Top = num_Position[num];
				if(sys.ie && sys.ie < 8){
					$(".center-dpt-txt",$$).scrollTop(floor_Top);
				}else{
					$(".center-dpt-txt",$$).animate({"scrollTop":floor_Top+"px"},500);	
				}
				isExist = true;
				return true;
			}
		});
		//楼 层不存在，请求数据
		if(isExist == false){
			alert("楼层不存在；请求数据咯！");
		}
	}
	
	//计算房态层内部内容总高度
	function centralized_Ind_D_b_H(){
		var obj = $(".center-dpt-txt",$$);  //获取房态列表层对象
		var H = 0;   //房态层内容总高度
		obj.children(".clearfix").each(function(){
			H += $(this).height();
		});
		return H;
	}
	
	//房态层滚动条滑动触发事件
	function centralized_Ind_D_b(){
		var obj = $(".center-dpt-txt",$$);  //获取房态列表层对象
		var obj_ScrollTop;									//房态层滑动高度
		var obj_TH = centralized_Ind_D_b_H();               //房态层内容总高度
		var obj_H = obj.height();
		
		var timer = null;
		obj.scroll(function(){
			obj_ScrollTop = $(this)[0].scrollTop;
			if(sys.ie && sys.ie <8){
				if(timer != null){
					clearTimeout(timer);
					timer = null;
				}
				timer =	setTimeout(function(){
					scroller_Thing();
				},500);				
			}else{
				scroller_Thing();	
			}
		});
		
		var scrollerFlag = true;
		
		//滚轮触发事件
		function scroller_Thing(){
			if(obj_ScrollTop >= obj_TH - obj_H){
	//				console.log("我到底了，要加载新内容了噢！！！");
			}
		}
	}
	
	var center_define_roomsJs = {
		submitForm : function(){
			var url = $(".btn2",$$).attr("url");
			var type = "post";
			var rooms_info = [];
			$(".clearfix",$$).each(function(){
				var floor_num = $(this).attr("data-floornum");
				$(this).find(".jzf-col").each(function(){
					var room_num = $(this).find(".house_Num").text();
					var rooms_count = $(this).find("input[name='rooms_Count']").val();
					var rooms = {
						floor_num : floor_num,
						house_num : room_num,
						rooms_count : rooms_count
					};
					rooms_info.push(rooms);
				});
			});
			var data = {
				"houses" : rooms_info
			}
			var check_result = hash.hash.ischange("center-dpt-body",$(":first",$$));
			if(check_result === false){
				var d = dialog({
						title: '提示信息',
						content:'数据没有发生修改，无法提交！',
						okValue: '确定',
						ok: function () {
							$(".btn2",$$).removeClass("none-click");
							d.close();
						}
					});
					d.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
			}else{
				ajax.doAjax(type,url,data,center_define_roomsJs.callback);	
			}
		},
		callback : function(data){
			if(data.status == 1){
				var d = dialog({
					title: '提示信息',
					content:'保存成功',
					okValue: '确定',
					ok: function () {
						$(".btn2",$$).removeClass("none-click");
						d.close();
						//关闭当前标签
						var tag = WindowTag.getCurrentTag();
						WindowTag.closeTag(tag.find('>a:first').attr('url'));
						window.location.href = "#"+data.p_url;
						var parent_page = $(".jooozo_Page:visible");
						parent_page.find("input[name='room_number']").attr("isedit",1);
					}
				});
				d.showModal();
			}else{
				var d = dialog({
					title: '提示信息',
					content:data.data,
					okValue: '确定',
					ok: function () {
						$(".btn2",$$).removeClass("none-click");
						d.close();
					}
				});
				d.showModal();
			}
			$(".ui-dialog-close",".ui-dialog-header").hide();
		},
		checkUI:function(){
			var that = this;
			$(".center-dpt-body",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parent().find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            datatype : {
		            	"chooseGroup":function(gets,obj,curform,regxp) {
		                   if(obj.attr("selectVal") == ''){
		                   	 return obj.attr("nullmsg");
		                   }
	
		               },
		               "float":function(gets,obj,curform,regxp) {
		                    var reg=/^\d+(\.\d+)?$/;
		                    if(reg.test(gets)){return true;}
		                    return false;
		               },
		               "nauto":function(gets,obj,curform,regxp){
		               	 if(gets/1 == 0) return false;
		               }
		            },
		            callback : function(form){
		            	if($(".btn2",form).hasClass("none-click")) return false;
	           			$(".btn2",form).addClass("none-click");
		            	that.submitForm();
		            	return false;
		            }
				});
				$(":input",$$).focus(function(){
					if($(this).hasClass("Validform_error")){
						$(this).css("background","none");
						$(this).siblings(".check-error").hide();
					}
				}).blur(function(){
					$(this).removeAttr("style");
					$(this).siblings(".check-error").show();
				});
		}
	}
	
	exports.inite = function(__html__){
		$$ = __html__;
		var that = center_define_roomsJs;
		that.checkUI();
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			require("placeholder")($);
			$(".center-dpt-txt",$$).placeholder();
		}
	
		//窗体更改重新计算房态层高度 
		if(sys.ie && sys.ie < 8){  
			window.onresize= debounce(h_center_dpt_txt, 300);
		}else{
			$(window).resize(h_center_dpt_txt);	
		}
		h_center_dpt_txt();
		
		//获取楼层距离楼层列表层顶部位移
		 var sss = getFloorTop();
		//快速滑动到指定楼层
		getFloor(sss);
		$(".center-dpt-nav",$$).find("a").click(function(){
			$(this).addClass("current").siblings().removeClass("current");
			var current_Floor = getFloorNumFirst();
			getFloorNum(current_Floor,sss);
		});
		//初始化hash
		hash.hash.savehash("center-dpt-body",$(":first",$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('center-dpt-body',$(':first',$$)) === true){
				var d = dialog({
							title: '提示信息',
							content:'数据已发生修改，确认取消？',
							okValue: '确定',
							ok: function () {
								d.close();
								//关闭当前标签
								var tag = WindowTag.getCurrentTag();
								WindowTag.closeTag(tag.find('>a:first').attr('url'));
							},
							cancelValue: '取消',
							cancel: function () {
								
							}
						});
						d.showModal();
			}else{
				//关闭当前标签
				var tag = WindowTag.getCurrentTag();
				WindowTag.closeTag(tag.find('>a:first').attr('url'));
			}
		});
		//楼层滑动事件
		centralized_Ind_D_b();
	}
});