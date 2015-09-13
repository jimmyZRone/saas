define(function(require,exports){
	var $ = require('jquery');
	var navigators = require("navigatortest");  //浏览器版本检测
	var dialog = require("dialog");  //弹窗插件
	var $$ = null;

	//设置房间列表层高度
	function h_centralized_RoomsConfig_D_b(){
		var c = $(".centralized_RoomsConfig_C").height();
		var d = $(".centralized_RoomsConfig_D .a").height();
		var H = $(window).height();//当前窗口高度
		if(H<768){
			H = 768;
		}
		$(".centralized_RoomsConfig_D").children(".b").height(H-c-d-70-105);
	}
	//设置房态楼层列表、房间列表层级
	function num_centralized_RoomsConfig_DLi_Z(){
		var num_centralized_RoomsConfig_DLi = $(".centralized_RoomsConfig_D > .b > ul > li").length;
		$(".centralized_RoomsConfig_D .b").children("ul").children("li").each(function(){
			var num_Li = $(this).index();
			$(this).css("z-index",num_centralized_RoomsConfig_DLi-num_Li);
			var num_Dd = $(this).find("dd").length;
			$(this).children("dl").children("dd").each(function(){
				var nums = $(this).index();
				$(this).css("z-index",num_Dd-nums);
				//房间列表各种详细情况弹出层
				$(this).children(".romm_NUM,.icon_Style,.rent_Style").hover(function(){
					$(this).siblings(".tc_Detail").show();
				},function(){
					$(this).siblings(".tc_Detail").hide();
				});
				//ie6鼠标掠过变色
				if(sys.ie && sys.ie < 7){
					$(this).hover(function(){
						$(this).addClass("ie6Hover");
					},function(){
						$(this).removeClass("ie6Hover");
					});
				}
			})
		});
	}

	
	//获取楼层列表被选中楼层的第一层楼层数
	function getFloorNumFirst(){
		return $(".centralized_RoomsConfig_D > .a > .a_2 > ul > li.current").children(".floorNum:first").text();
	}
	
	//匹配楼层列表并快速到达
	function getFloorNum(current_Floor,num_Position){
		var isExist = false;		//点击楼层列表中楼层房态详情存在状态
		$(".centralized_RoomsConfig_D > .b > ul > li").each(function(){
			var floor = $(this).children(".floorNum").children(".floor_Num").text();
			var floor_Top;  //获取目标楼层距离父级容器顶部的高度
			if(floor == current_Floor){
				var num = $(this).index();
				floor_Top = num_Position[num];
				if(num > 0){
					if(sys.ie && sys.ie < 8){
						$(".centralized_RoomsConfig_B").hide();
						h_centralized_RoomsConfig_D_b();
					}else{
						$(".centralized_RoomsConfig_B").slideUp(500,function(){
							h_centralized_RoomsConfig_D_b();
						});	
					}
				}
				if(sys.ie && sys.ie < 8){
					$(".centralized_RoomsConfig_D").children(".b").scrollTop(floor_Top);
				}else{
					$(".centralized_RoomsConfig_D").children(".b").animate({"scrollTop":floor_Top+"px"},500);	
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
	
	//楼层电梯快速到
	function getFloor(){
		var current_Floor = getFloorNumFirst();
		var num_Position = [];
		$(".centralized_RoomsConfig_D").children(".b").children("ul").children("li").each(function(){
			num_Position.push($(this).position().top);
		});
		getFloorNum(current_Floor,num_Position);
		$(".centralized_RoomsConfig_D").find(".a_2").find("li").click(function(){
			$(this).addClass("current").siblings().removeClass("current");
			current_Floor = getFloorNumFirst();
			getFloorNum(current_Floor,num_Position);
		});
	}
	//房态层滚动条滑动触发事件
	function centralized_RoomsConfig_D_b(){
		var obj = $(".centralized_RoomsConfig_D").children(".b");  //获取房态列表层对象
		var obj_ScrollTop;									//房态层滑动高度
		var obj_TH = centralized_RoomsConfig_D_b_H();               //房态层内容总高度
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
			if(obj_ScrollTop == 0){			//如果滚动条滑动到房态层顶部,展示房态数据统计
				scrollerFlag = true;
				if(sys.ie && sys.ie <8){
					$(".centralized_RoomsConfig_B").show();
					h_centralized_RoomsConfig_D_b();
					obj_H = obj.height();
				}else{
					$(".centralized_RoomsConfig_B").slideDown(300,function(){
						h_centralized_RoomsConfig_D_b();
						obj_H = obj.height();
					});
				}
			}else if (scrollerFlag == true) {		//其他状态收起房态数据统计
				scrollerFlag = false;
				if(sys.ie && sys.ie <8){
					
					$(".centralized_RoomsConfig_B").hide();
					h_centralized_RoomsConfig_D_b();
					obj_H = obj.height();
				}else{
					$(".centralized_RoomsConfig_B").slideUp(300,function(){
						h_centralized_RoomsConfig_D_b();
						obj_H = obj.height();
					});	
				}
			}
			if(obj_ScrollTop >= obj_TH - obj_H){
	//				console.log("我到底了，要加载新内容了噢！！！");
			}
		}
	}
	
	//计算房态层内部内容总高度
	function centralized_RoomsConfig_D_b_H(){
		var obj = $(".centralized_RoomsConfig_D").children(".b");  //获取房态列表层对象
		var H = 0;   //房态层内容总高度
		obj.children("ul").children("li").each(function(){
			H += $(this).height();
		});
		H += 72;   //加上父级UL的padding-bottom
		return H;
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
	
	//勾选复选框
	function delete_CheckBox(){
		$(".centralized_RoomsConfig_D > .b > ul > li > dl > dd > .checkBox > label",$$).click(function(){
			$(this).toggleClass("checked");
			if($(this).hasClass("checked")){
				$(this).children(".gou").show();
				$(this).next().attr("checked",true);
				$(this).parent().parent().addClass("choosed");
			}else{
				$(this).children(".gou").hide();
				$(this).next().removeAttr("checked");
				$(this).parent().parent().removeClass("choosed");
			}
		});
		$(".romm_NUM",$$).off("click").on("click",function(){
			var obj = $(this).siblings(".checkBox").children("label");
			obj.toggleClass("checked");
			if(obj.hasClass("checked")){
				obj.children(".gou").show();
				obj.next().attr("checked",true);
				obj.parent().parent().addClass("choosed");
			}else{
				obj.children(".gou").hide();
				obj.next().removeAttr("checked");
				obj.parent().parent().removeClass("choosed");
			}
		});
	}
	
	var centralized_RoomsConfigJs = {
		submitForm : function($$){
			var data = [];
			$("input[type=checkbox]:checked",$$).each(function(i,o){
				var room_id = $(this).val();
				var room_num = $(this).parents(".checkBox").siblings(".romm_NUM").text();
				var data_room ={
					room_id : room_id,
					room_num : room_num
				}
				data.push(data_room);
			});
			var parent = $(".btn2",$$).attr("parent_url");
			var aotostyle = 0;
			$(".main-box").children(".tag").find("li").each(function(i,o){
				var urls = $(this).children(".a_Tags").attr("url");
				if(urls == parent){
					var parent_index = i;
					var parent_obj = $(".jooozo_Page:eq("+parent_index+")").find(".house-list");
					parent_obj.data("room-config",data);
					var d = dialog({
						title: '提示信息',
						content:'保存成功',
						okValue: '确定',
						ok: function () {
							d.close();
							//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
							$(".jooozo_Page:eq("+parent_index+")").show().siblings().hide();
							$(".tag ul li:eq("+parent_index+")").addClass("current").siblings().removeClass("current");
							var length_data_room = data.length;
							var obj_auto = parent_obj.find(".exchangeTenplateConfigRooms").parent();
							parent_obj.find(".template_roomsconfig").remove();
							for(var i=0; i<length_data_room; i++){
								var str = "<li room-id='"+data[i].room_id+"' class='template_roomsconfig'><a href='javascript:;'>"+data[i].room_num+"</a> <a href='javascript:;' class='ifont'>&#xe627;</a></li>";
								if(i > 9) str = "<li room-id='"+data[i].room_id+"' class='template_roomsconfig' style='display:none;'><a href='javascript:;'>"+data[i].room_num+"</a> <a href='javascript:;' class='ifont'>&#xe627;</a></li>";
								obj_auto.before(str);
							}
							if(length_data_room<11){
								parent_obj.find(".exchangeTenplateConfigRooms").parent().hide();
							}else{
								parent_obj.find(".exchangeTenplateConfigRooms").parent().show();
							}
							parent_obj.find(".template_roomsconfig .ifont").off("click").on("click",function(){
								var _this = $(this).parent();
								var dd = dialog({
									title:'<i class="ifont">&#xe675;</i><span>删除配置房间</span>',
									content:'确定删除配置房间？',
									okValue:'确 定',
									ok:function(){
										dd.close();
										var data_length = data.length;
										var this_room_id = _this.attr("room-id");
										var num_deletei = null;
										for(var i = 0; i< data_length;i++){
											if(data[i].room_id == this_room_id){ num_deletei = i;break;}
										}
										data.splice(num_deletei,1);
										var next_start = 0;
										if(_this.index(parent_obj.find(".template_roomsconfig")) == parent_obj.find(".template_roomsconfig").length-1){
											next_start = 0;
										}else{
											if(_this.next().is(":hidden")){
												next_start = _this.index(parent_obj.find(".template_roomsconfig"));
											}
										}
										_this.remove();
										parent_obj.data("room-config",data);
										if(parent_obj.find(".template_roomsconfig:visible").size()==0){
											if(parent_obj.find(".template_roomsconfig:hidden").size()>10){
												var length = parent_obj.find(".template_roomsconfig").length;
												for(var i=next_start; i< next_start+10; i++){
													parent_obj.find(".template_roomsconfig:eq("+i+")").show();
													if(i>= length) break;
												}
											}else{
												parent_obj.find(".template_roomsconfig:hidden").show();
												parent_obj.find(".exchangeTenplateConfigRooms").parent().hide();
											}
										}
									},
									cancelValue: '取 消',
									cancel: function () {
									}
								});
								dd.showModal();
							});
						}
					});
					d.showModal();
					aotostyle = 1;
					return;
				}
			});
			if(aotostyle == 1) return false;
			var d = dialog({
				title: '提示信息',
				content:'模板已不存在，保存失败',
				okValue: '确定',
				ok: function () {
					d.close();
				}
			});
			d.showModal();
		},
		ook : function(obj){
			if(obj.hasClass("checked")){
				obj.children().show();
				$(".centralized_RoomsConfig_D > .b > ul > li > dl > dd.choosed",$$).each(function(){
					if($(this).find("input[type='checkbox']:checked").size() == 0){
						$(this).hide();
					}
				});
				$(".centralized_RoomsConfig_D > .b > ul > li",$$).each(function(){
					if($(this).find("dd:visible").size() == 0) $(this).hide();
				});
			}else{
				obj.children().hide();
				$(".centralized_RoomsConfig_D > .b > ul > li > dl > dd.choosed",$$).each(function(){
					if($(this).find("input[type='checkbox']:checked").size() == 0){
						$(this).show();
					}
				});
				$(".centralized_RoomsConfig_D > .b > ul > li",$$).each(function(){
					if($(this).find("dd:visible").size() == 0) $(this).show();
				});
			}
		},
		update_data:function(){
			var parent = $(".btn2",$$).attr("parent_url");
			var parent_obj = null;
			$(".main-box").children(".tag").find("li").each(function(i,o){
				var urls = $(this).children(".a_Tags").attr("url");
				if(urls == parent){
					var parent_index = i;
					parent_obj = $(".jooozo_Page:eq("+parent_index+")").find(".house-list:first");
				}
			});
			var data = parent_obj.data("room-config");
			console.log(data);
			if(typeof data == "undefined" || data == "") return false;
			$(".centralized_RoomsConfig_D>.b>ul dd",$$).each(function(){
				var _this = $(this);
				_this.removeClass("choosed").find(".checkBox").children("label").removeClass("checked").children().hide();
				_this.find("input").removeAttr("checked");
				var room_id = $(this).find("input").val();
				var checkresult = false;
				for(var n in data){
					if(room_id == data[n].room_id){
						checkresult = true;
						break;
					}
				}
				if(checkresult == true){
					_this.addClass("choosed").find(".checkBox").children("label").addClass("checked").children().show();
					_this.find("input").attr("checked",true);
				}
			});
		}
	}
	
	
	exports.inite = function(__html__){
		$$ = __html__;
		var that = centralized_RoomsConfigJs;
		//头部楼层列表层级设置
		var num = $(".centralized_RoomsConfig_D").find(".a_2").find("li").length;
		$(".centralized_RoomsConfig_D").find(".a_2").find("li").each(function(){
			var nums = $(this).index()
			$(this).css("z-index",num-nums);
		});
		
		//针对IE6鼠标划过变色
		if(sys.ie && sys.ie < 7){
			$(".centralized_RoomsConfig_D > .a > .a_2 > ul > li").hover(function(){
				$(this).addClass("ie6Hover");
				$(this).children(".floor_Detail").show();
			},function(){
				$(this).removeClass("ie6Hover");
				$(this).children(".floor_Detail").hide();
			});
			$(".centralized_RoomsConfig_D .b > ul > li > dl > dd > .jtBox").hover(function(){
				$(this).children(".detail_Choices").show();
			},function(){
				$(this).children(".detail_Choices").hide();
			})
		}
		
		//设置房态楼层列表、房间列表层级
		num_centralized_RoomsConfig_DLi_Z();
		
		//窗体更改重新计算房态层高度 
		if(sys.ie && sys.ie < 8){  
			window.onresize= debounce(h_centralized_RoomsConfig_D_b, 300);
		}else{
			$(window).resize(h_centralized_RoomsConfig_D_b);	
		}
		h_centralized_RoomsConfig_D_b();
		
		//快速滑动到指定楼层
		getFloor();

		//房态层滑动事件
		centralized_RoomsConfig_D_b();

		//勾选复选框
		delete_CheckBox();
		
		//勾选已配置隐藏
		that.ook($(".centralized_RoomsConfig_C > .b > ul > li > .checkBox",$$));
		$(".centralized_RoomsConfig_C > .b > ul > li > .checkBox",$$).off("click").on("click",function(){
			$(this).toggleClass("checked");
			that.ook($(this));
		});
		$(".centralized_RoomsConfig_C > .b > ul > li > .checkBox",$$).next().off("click").on("click",function(){
			var obj = $(this).prev();
			obj.toggleClass("checked");
			that.ook(obj);
		});
		//保存提交
		$(".btn2",$$).off("click").on("click",function(){
			that.submitForm($$);
		});
		//已配置数据更新
		that.update_data();
		$("dd",$$).hover(function(){
			$(this).children(".room-configed").show();
		},function(){
			$(this).children(".room-configed").hide();
		});
	}
});