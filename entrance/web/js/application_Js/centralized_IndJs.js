define(function(require,exports){
	var $ = require('jquery');
	require("selectByM")($);		//自定义下拉菜单
	require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var dialog = require("dialog");  //弹窗插件
	var ajax = require("Ajax");
	var calendar = require("calendar");
	var urlMake = require("url");
	
	//获取楼层列表被选中楼层的第一层楼层数
	function getFloorNumFirst(){
		return $(".centralized_Ind_D > .a > .a_2 > ul > li.current").children(".floorNum:first").text();
	}
		
	//获取楼层距离楼层列表层顶部位移
	function getFloorTop(){
		var num_Position = [];
		var top = $(".centralized_Ind_D").children(".b").scrollTop();
		$(".centralized_Ind_D").children(".b").children("ul").children("li").each(function(){
			num_Position.push($(this).position().top+top);
		});
		
		return num_Position;
	}
	
	
	//计算房态层内部内容总高度
	function centralized_Ind_D_b_H(){
		var obj = $(".centralized_Ind_D").children(".b");  //获取房态列表层对象
		var H = 0;   //房态层内容总高度
		obj.children("ul").children("li").each(function(){
			H += $(this).height();
		});
		H += 172;   //加上父级UL的padding-bottom
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
	
	//楼层房间计数器
	function num_FloorRooms(){
		$(".centralized_Ind_D > .b > ul > li").each(function(){
			var num = $(this).children("dl").children("dd").length;
			$(this).children(".floorNum").children("p").children(".num_Rooms").text(num);
		})
	}
	
	var auto = function($$){
		var centralized_IndJs = {
			 others : function(){
			 		var that = centralized_IndJs;
			 		//头部楼层列表层级设置
					var num = $(".centralized_Ind_D",$$).find(".a_2").find("li").length;
					$(".centralized_Ind_D",$$).find(".a_2").find("li").each(function(){
						var nums = $(this).index()
						$(this).css("z-index",num-nums);
					});
					//针对IE10以下的input提示语兼容
					if(sys.ie && sys.ie < 10){
						require("placeholder")($);
						$("#centralized_Ind",$$).placeholder();
					}
					//自定义下拉菜单
					$(".selectByM",$$).each(function(){
						$(this).selectObjM();
					});
					//出租率、月空置率、年空置率解释显隐
					$(".centralized_Ind_B",$$).find("li").children(".red").hover(function(){
						$(this).siblings(".prompt").show();
					},function(){
						$(this).siblings(".prompt").hide();
					});
					//针对IE6鼠标划过变色
					if(sys.ie && sys.ie < 7){
							$(".centralized_Ind_D > .a > .a_2 > ul > li",$$).hover(function(){
								$(this).addClass("ie6Hover");
								$(this).children(".floor_Detail").show();
							},function(){
								$(this).removeClass("ie6Hover");
								$(this).children(".floor_Detail").hide();
							});
							$(".centralized_Ind_D .b > ul > li > dl > dd > .jtBox",$$).hover(function(){
								$(this).children(".detail_Choices").show();
							},function(){
								$(this).children(".detail_Choices").hide();
							})
					   }
					//页面无数据时提示
					if($(".centralized_Ind_D .b dd").size() == 0){
						$(".centralized_Ind_D .a_2").hide();
						$(".hiden-loading-temp",$$).children().text("本公寓还没有房源，请先添加房源");
						$(".hiden-loading-temp",$$).removeClass("none");
					}
					//数据展开收起
					$(".show-static",$$).off("click").on("click",function(){
						$(this).toggleClass("active");
						$(".data-statics-bar",$$).toggle();
					});
					},
			 //设置房态楼层列表、房间列表层级
			 num_centralized_Ind_DLi_Z : function(){
							var num_centralized_Ind_DLi = $(".centralized_Ind_D > .b > ul > li",$$).length;
							$(".centralized_Ind_D .b",$$).children("ul").children("li").each(function(){
								var num_Li = $(this).index();
								$(this).css("z-index",num_centralized_Ind_DLi-num_Li);
								var num_Dd = $(this).find("dd").length;
								$(this).children("dl").children("dd").each(function(){
									var nums = $(this).index();
									$(this).css("z-index",num_Dd-nums);
									//房间列表各种详细情况弹出层
									$(this).children(".romm_NUM,.icon_Style,.rent_Style").hover(function(){
										if($(this).siblings(".checkBox ").is(":hidden")){
											$(this).siblings(".tc_Detail").show();	
										}
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
					},
			 h_centralized_Ind_D_b : function(){
					var a = $(".centralized_Ind_A",$$).height();
					var b = $(".centralized_Ind_B",$$).height();
					var c = $(".centralized_Ind_C",$$).height();
					var d = $(".centralized_Ind_D .a",$$).height();
					var H = $(window).height();//当前窗口高度
					if(H<768){
						H = 768;
					}
					$(".centralized_Ind_D",$$).children(".b").height(H-a-b-c-d-70-105);
					if($(".centralized_Ind_B",$$).is(":hidden")){
						$(".centralized_Ind_D",$$).children(".b").height(H-a-c-d-70-105);
					}
				},
			 //房态筛选
			 rentStyleChoose : function(){
					var that = centralized_IndJs;
					var obj = $(".centralized_Ind_D",$$).children(".b").children("ul").children("li");
					//点击房源跳转到详细
					$(".romm_NUM",$$).off("click").on("click",function(){
						if($(".centralized_Delete",$$).hasClass("deleteStyle")){
							if($(this).siblings(".checkBox").hasClass("canNotDelete")) return false;
							var obj_label = $(this).siblings(".checkBox").children("label");
							obj_label.toggleClass("checked");
							if(obj_label.hasClass("checked")){
								obj_label.children().show();
								obj_label.next().attr("checked",true);
							}else{
								obj_label.children().hide();
								obj_label.next().removeAttr("checked");
							}
						}else{
							var href = $(this).siblings(".jtBox").find(".a_Detail").attr("url");
							var ctag = WindowTag.getTagByUrlHash(href);
							if(ctag){
								window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
								window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
							}else{
								window.WindowTag.openTag(href);	
							}
						}
					});
					//男租客状态显隐
					$(".centralized_Ind_C",$$).children(".b").find(".choice_man").click(function(){
						$(this).toggleClass("blue");
						$(this).parent().siblings().children("a.pink").removeClass("pink");
						$(this).parent().siblings().children("a.green").removeClass("green");
						$(this).parent().siblings().children("a.yellow").removeClass("yellow");
						$(this).parent().siblings().children("a.black").removeClass("black");
						if($(this).hasClass("blue")){
							obj.each(function(){
									$(this).find("dd").each(function(){
										var rentStyle = $(this).attr("data-rentStyle");   //获取房间出租状态
										$(this).hide();
										if(rentStyle == "rented" && $(this).find(".man").size() > 0){
											$(this).show();
											$(this).parents("li").show();
										}
									});
									if($(this).find("dd:visible").size() == 0){$(this).hide();}
							});
						}else{
							obj.each(function(){
									$(this).show();
									$(this).find("dd").each(function(){
										$(this).show();
									});
							});
						}
					});
					//女租客状态显隐
					$(".centralized_Ind_C",$$).children(".b").find(".choice_woman").click(function(){
						$(this).toggleClass("pink");
						$(this).parent().siblings().children("a.pink").removeClass("pink");
						$(this).parent().siblings().children("a.blue").removeClass("blue");
						$(this).parent().siblings().children("a.green").removeClass("green");
						$(this).parent().siblings().children("a.yellow").removeClass("yellow");
						$(this).parent().siblings().children("a.black").removeClass("black");
						if($(this).hasClass("pink")){
							obj.each(function(){
									$(this).find("dd").each(function(){
										var rentStyle = $(this).attr("data-rentStyle");   //获取房间出租状态
										$(this).hide();
										if(rentStyle == "rented" && $(this).find(".woman").size()>0){
											$(this).show();
											$(this).parents("li").show();
										}
									});
									if($(this).find("dd:visible").size() == 0){$(this).hide();}
							});
						}else{
							obj.each(function(){
									$(this).show();
									$(this).find("dd").each(function(){
										$(this).show();
									});
							});
						}
					});
					
					//预定状态房态显隐
					$(".centralized_Ind_C",$$).children(".b").find(".choice_booked").click(function(){
						$(this).toggleClass("green");
						$(this).parent().siblings().children("a.pink").removeClass("pink");
						$(this).parent().siblings().children("a.blue").removeClass("blue");
						$(this).parent().siblings().children("a.yellow").removeClass("yellow");
						$(this).parent().siblings().children("a.black").removeClass("black");
						if($(this).hasClass("green")){
							obj.each(function(){
									$(this).find("dd").each(function(){
										$(this).hide();
										if($(this).attr("data-booked")){
											$(this).show();
											$(this).parents("li").show();
										}
									});
									if($(this).find("dd:visible").size() == 0){$(this).hide();}
							});
						}else{
							obj.each(function(){
									$(this).show();
									$(this).find("dd").each(function(){
										$(this).show();
									});
							});
						}
					});
					//退租状态房态显隐
					$(".centralized_Ind_C",$$).children(".b").find(".choice_outRented").click(function(){
						$(this).toggleClass("yellow");
						$(this).parent().siblings().children("a.pink").removeClass("pink");
						$(this).parent().siblings().children("a.blue").removeClass("blue");
						$(this).parent().siblings().children("a.green").removeClass("green");
						$(this).parent().siblings().children("a.black").removeClass("black");
						if($(this).hasClass("yellow")){
							obj.each(function(){
									$(this).find("dd").each(function(){
										var rentStyle = $(this).attr("data-rentStyle");   //获取房间出租状态
										$(this).hide();
										if(rentStyle == "outRented"){
											$(this).show();
											$(this).parents("li").show();
										}
									});
									if($(this).find("dd:visible").size() == 0){$(this).hide();}
							});
						}else{
							obj.each(function(){
									$(this).show();
									$(this).find("dd").each(function(){
										$(this).show();
									});
							});
						}
					});
					//停用状态房态显隐
					$(".centralized_Ind_C",$$).children(".b").find(".choice_stopUse").click(function(){
						$(this).toggleClass("black");
						$(this).parent().siblings().children("a.pink").removeClass("pink");
						$(this).parent().siblings().children("a.blue").removeClass("blue");
						$(this).parent().siblings().children("a.green").removeClass("green");
						$(this).parent().siblings().children("a.yellow").removeClass("yellow");
						if($(this).hasClass("black")){
							obj.each(function(){
									$(this).find("dd").each(function(){
										var rentStyle = $(this).attr("data-rentStyle");   //获取房间出租状态
										$(this).hide();
										if(rentStyle == "stopUse"){
											$(this).show();
											$(this).parents("li").show();
										}
									});
									if($(this).find("dd:visible").size() == 0){$(this).hide();}
							});
						}else{
							obj.each(function(){
									$(this).show();
									$(this).find("dd").each(function(){
										$(this).show();
									});
							});
						}
					});
					//未出租房态显隐
					$(".centralized_Ind_C",$$).children(".b").find(".choice_waitfrs").click(function(){
						$(this).toggleClass("pink");
						$(this).parent().siblings().children("a.blue").removeClass("blue");
						$(this).parent().siblings().children("a.black").removeClass("black");
						$(this).parent().siblings().children("a.green").removeClass("green");
						$(this).parent().siblings().children("a.yellow").removeClass("yellow");
						$(this).parent().siblings().children("a.choice_woman").removeClass("pink");
						if($(this).hasClass("pink")){
							obj.each(function(){
									$(this).find("dd").each(function(){
										var rentStyle = $(this).attr("data-rentStyle");   //获取房间出租状态
										$(this).hide();
										if(rentStyle == "notRented"){
											$(this).show();
											$(this).parents("li").show();
										}
									});
									if($(this).find("dd:visible").size() == 0){$(this).hide();}
							});
						}else{
							obj.each(function(){
									$(this).show();
									$(this).find("dd").each(function(){
										$(this).show();
									});
							});
						}
					});
				},
			 //快速滑动到指定楼层
			 getFloor : function(num_Position){
					var that = centralized_IndJs;
					var current_Floor = getFloorNumFirst();
					that.getFloorNum(current_Floor,num_Position);
				},
			 //匹配楼层列表并快速到达
			 getFloorNum : function(current_Floor,num_Position){
			 		var that = centralized_IndJs;
					var isExist = false;		//点击楼层列表中楼层房态详情存在状态
					$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
						var floor = $(this).children(".floorNum").children(".floor_Num").text();
						var floor_Top;  //获取目标楼层距离父级容器顶部的高度
						if(floor == current_Floor){
							var num = $(this).index();
							floor_Top = num_Position[num];
							if(num > 0){
//								if(sys.ie && sys.ie < 8){
//									$(".centralized_Ind_B",$$).hide();
//									that.h_centralized_Ind_D_b();
//								}else{
//									$(".centralized_Ind_B",$$).slideUp(500,function(){
//										that.h_centralized_Ind_D_b();
//									});	
//								}
							}
							if(sys.ie && sys.ie < 8){
								$(".centralized_Ind_D",$$).children(".b").scrollTop(floor_Top);
							}else{
								$(".centralized_Ind_D",$$).children(".b").animate({"scrollTop":floor_Top+"px"},500);	
							}
							isExist = true;
							return true;
						}
					});
					//楼 层不存在，请求数据
					if(isExist == false){
//						alert("楼层不存在；请求数据咯！");	
//						jumpfloor();
					}
					//点击楼层加载内容
					function jumpfloor(){
						var objs = $(".centralized_Ind_D .a_2 li.current .floorNum:last",$$).text();
						var obj = $(".centralized_Ind_D",$$).children(".b");
						var url = obj.attr("url");
						var type = "get";
						var page_current = 	$(".centralized_Ind_D .b",$$).attr("currentpage"); 
						var flat_id = document.URL.split("flat_id=")[1];
						page_current++;
						var data = {
								page : page_current,
								flat_id : flat_id
							};
							ajax.doAjax(type,url,data,function(data){
								console.log(data);
								if(data.status == 1){
									var datas = data.data;
									for(var n in datas){
										that.showhouses(datas[n],n);
									}
								}
							});
					}
				},
			 //房态层滑动事件
			 centralized_Ind_D_b : function(sss){
			 		var that = this;
			 		var num_ctr = 1;  //控制页面每次滑动到底部只加载一次
					var obj = $(".centralized_Ind_D",$$).children(".b");  //获取房态列表层对象
					var obj_ScrollTop;									//房态层滑动高度
					var obj_TH = centralized_Ind_D_b_H();               //房态层内容总高度
					var scrollfloorbz = [];        //每三层楼距离顶部高度组成新数组
					for(var i = 0; i < parseInt(sss.length/3); i++){
						scrollfloorbz.push(sss[i*3+2]);
					}
					if(sss.length%3 != 0){
						scrollfloorbz.push(sss[sss.length-1]);
					}
					var obj_H = obj.height();
					var url = obj.attr("url");
					var type = "get";
					var page_current = 	$(".centralized_Ind_D .b",$$).attr("currentpage");   //获取当前加载的页码
					var page_total = obj.attr("page");
					var flat_id = document.URL.split("flat_id=")[1];
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
						for(var i = 0; i < scrollfloorbz.length; i++){
							if(obj_ScrollTop < scrollfloorbz[i]){
								$(".centralized_Ind_D .a .a_2 li:eq("+i+")").addClass("current").siblings().removeClass("current");
								break;
							}
						}
//						if(obj_ScrollTop == 0){			//如果滚动条滑动到房态层顶部,展示房态数据统计
//							scrollerFlag = true;
//							if(sys.ie && sys.ie <8){
//								$(".centralized_Ind_B").show();
//								that.h_centralized_Ind_D_b();
//								obj_H = obj.height();
//							}else{
//								$(".centralized_Ind_B").slideDown(300,function(){
//									that.h_centralized_Ind_D_b();
//									obj_H = obj.height();
//								});
//							}
//						}else if (scrollerFlag == true) {		//其他状态收起房态数据统计
//							scrollerFlag = false;
//							if(sys.ie && sys.ie <8){
//								
//								$(".centralized_Ind_B").hide();
//								that.h_centralized_Ind_D_b();
//								obj_H = obj.height();
//							}else{
//								$(".centralized_Ind_B").slideUp(300,function(){
//									that.h_centralized_Ind_D_b();
//									obj_H = obj.height();
//								});	
//							}
//						}
//						if(obj_ScrollTop >= obj_TH - obj_H && num_ctr == 1){
//							$(".centralized_Ind_D .b",$$).attr("currentpage",page_current);
//							num_ctr = 2;
//							page_current++;
//							if(page_current > page_total){
//								var d = dialog({
//											title: '提示信息',
//											content: '已经没有数据了！',
//											okValue: '确 定', drag: true,
//											ok : function(){
//												d.close();
//											}
//										});
//								d.showModal();
//								return false;
//							}
//							data = {
//								page : page_current,
//								flat_id : flat_id
//							};
//							ajax.doAjax(type,url,data,function(data){
//								if(data.status == 1){
//									var datas = data.data;
//									for(var n in datas){
//										that.showhouses(datas[n],n);
//									}
//									num_ctr = 1;
//								}
//							});
//						}
					}
				},
				//进入删除状态及删除
				deletestyle :  function(sss){
					var that = this;
					$(".centralized_Delete",$$).off("click").on("click",function(){
						var url = $(this).attr("url");
						var type= "post";
						var flat_id = document.URL.split("flat_id=")[1];
//						var room_id = [];
//						var roomdelete_list = [];
						if(sys.ie && sys.ie < 8){
							$(".centralized_Ind_C > .a > ul > li:lt(2)",$$).hide();
							$(".centralized_Ind_C > .a > ul > li:last",$$).show();
							$(".centralized_Ind_C > .b",$$).hide();
						}else{
							$(".centralized_Ind_C > .a > ul > li:lt(2)",$$).animate({"width":"0"},200,function(){
								$(this).hide();
							});
							$(".centralized_Ind_C > .a > ul > li:last",$$).fadeIn(200);
							$(".centralized_Ind_C > .b",$$).fadeOut(400);
						}
						$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
								$(this).children(".floorNum").children(".checkBoxAll").show();
								$(this).children(".floorNum").children("p").show();
								$(this).children("dl").children("dd").each(function(){
									$(this).children(".checkBox").show();
									$(this).children(".jtBox,.icon_Style,.rent_Style").hide();
									$(this).children(".romm_NUM").css({"padding-left":"27px","width":"127px"});
								});
							});
						if($(this).hasClass("deleteStyle")){	//拥有删除状态，点击执行删除操作
//							$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
//								$(this).children("dl").children("dd").each(function(){
//									if($(this).children(".checkBox").children("input:checked").length > 0){
//										room_id.push($(this).attr("house-id"));
//										roomdelete_list.push($(this));
//									}
//								});
//							});
//							var d = dialog({
//									title: '<i class="ifont">&#xe62e;</i><span>删除房源</span>',
//									content: '确认删除房源？',
//									okValue: '确 定', drag: true,
//									ok: function () {
//										var data = {
//											flat_id : flat_id,
//											room_id : room_id,
//										};
//										ajax.doAjax(type,url,data,function(data){
//											if(data.status == 1){
//												var dd = dialog({
//													title: '提示信息',
//													content: '删除成功！',
//													okValue: '确 定', drag: true,
//													ok : function(){
//														dd.close();
//														for(var n in roomdelete_list){
//															roomdelete_list[n].remove();
//														}
//														num_FloorRooms();
//														sss = getFloorTop();
//														that.centralized_Ind_D_b(sss);
//													}
//												});
//												dd.showModal();
//											}else{
//												var dd = dialog({
//													title: '提示信息',
//													content: data.data,
//													okValue: '确 定', drag: true,
//													ok : function(){
//														dd.close();
//													}
//												});
//												dd.showModal();
//											}
//										});
//									},
//									cancelValue: '取消',
//									cancel: function () {
//			//							_isClicked=false;
//									}
//								});
//								d.showModal();
								var d =dialog({
									title:'提示信息',
									content:'删除的信息将无法得到恢复，确定删除？',
									okValue: '确 定', drag: true,
									ok : function(){
										d.close();
										var deletelist = [];
							   			var deletelistobj = [];
							   			$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
							   				$(this).children("dl").children("dd").each(function(){
							   					if($(this).find("input:checked").size() > 0){
								   					var room_id = $(this).attr("house-id");
								   					var house_num = $(this).find(".romm_NUM").text();
								   					var _eleAuto = {
								   						room_id : room_id,
								   						house_num : house_num
								   					}
								   					deletelist.push(_eleAuto);
								   					deletelistobj.push($(this));
								   				}
							   				});
							   			});
							   			if(deletelist.length == 0){
							   				var dd = dialog({
													title: '提示信息',
													content:"没有选中任何内容",
													okValue: '确定',
													ok: function () {
														dd.close();
													}
												});
											dd.showModal();
							   				return false;
							   			}
							   			var deletedata =   {
							   				url : url,
							   				type : type,
							   				deletelist : deletelist,
							   				deletelistobj : deletelistobj,
							   				flat_id : flat_id
							   			}
							   			that.deleteauto(deletedata,0);
									},
									cancelValue: '取消',
									cancel : function(){
										
									}
								});
								d.showModal();
						}
						$(this).addClass("deleteStyle");
					});
				},
				deleteauto : function(deletedata,num_cur){
				   	  var that = this;
				   	  var cloneStr = $(".deletemoreauto",$$).clone();
				   	  cloneStr.removeClass("none");
				   	  var deleteTptal = deletedata.deletelist.length;
				   	  var deletelist = deletedata.deletelist;
				   	  var deletelistobj = deletedata.deletelistobj;
				   	  cloneStr.find(".num_total").text(deleteTptal);
				   	  var dd = dialog({
								title: '<i class="ifont">&#xe675;</i><span>删除房源</span>',
								content:cloneStr,
								okValue: '确定',
								ok: function () {
									dd.close();
									//刷新页面
									var tag = window.WindowTag.getCurrentTag();
									window.WindowTag.loadTag(tag.find('>a:first').attr('href'));
									window.WindowTag.loadTag(urlMake.make('centralized-flat/list'));
								},
								cancelValue: '取消',
								cancel: function () {
									$(".ui-dialog-content").children(".deletemoreauto").addClass("stop");
									$(".ui-dialog-content").children(".deletemoreauto").find(".top1 .fl").text("删除终止");
									//刷新页面
									var tag = window.WindowTag.getCurrentTag();
									window.WindowTag.loadTag(tag.find('>a:first').attr('href'));
									window.WindowTag.loadTag(urlMake.make('centralized-flat/list'));
									return false;
								}
							});
						dd.showModal();
						var objAuto =  $(".ui-dialog-content").children(".deletemoreauto"); 
						var tableauto = objAuto.find("table");
						var scrollbar = objAuto.find(".top2a");
						var scrollbalscroll = parseInt(100/deleteTptal);
						var url = deletedata.url;
						var type = deletedata.type;
						var flat_id = deletedata.flat_id;
						function autodeleteus(){
							if(objAuto.hasClass("stop")) return false;
							var objsauto = null;
//				   			 $(".centralized_Ind_D > .b > ul > li",$$).each(function(){
//					   				$(this).children("dl").children("dd").each(function(){
//					   					if($(this).find("input:checked").size() > 0){
//						   					objsauto = $(this);
//						   					num_cur++
//						   					return false;
//						   				}
//					   				});
//					   			});
							objsauto = 	deletelistobj[num_cur];
							var house_Num = deletelist[num_cur].house_num;
							 var idauto = deletelist[num_cur].room_id;
							num_cur++
				   			 objAuto.find(".num_cur").text(num_cur);
				   			 var trstr = '<tr><td class="zb">'+house_Num+'</td><td class="yb">正在删除</td></tr>';
				   			 tableauto.append(trstr);
				   			 if(num_cur > 5){
								tableauto.find("tr:eq("+(num_cur-5)+")").fadeOut(300);
							}
				   			 var trcur = tableauto.find("tr:last");
				   			 var data = {
								room_id : idauto,
								flat_id : flat_id
							};
							ajax.doAjax(type,url,data,function(data){
								if(data.status == 0){
									trcur.find(".yb").addClass("red").removeClass("blue").text(data.data);
									objsauto.find(".checkBox").removeClass("checked").children().hide();
									objsauto.find(".checkBox").next().removeAttr("checked");
									if(num_cur == deleteTptal){
										scrollbar.animate({"left":0},300);
										return false;
									}
									scrollbar.animate({"left":-(100-scrollbalscroll*num_cur)+"%"},300);
									var timer = null;
									clearTimeout(timer);
									timer = setTimeout(autodeleteus,200);
								}else{
									trcur.find(".yb").addClass("blue").removeClass("red").text(data.data);
									objsauto.remove();
									num_FloorRooms();
									sss = getFloorTop();
									that.centralized_Ind_D_b(sss);
									if(num_cur == deleteTptal){
										scrollbar.animate({"left":0},300);
										objAuto.find(".top1 .fl").text("删除完成");
										if($(".centralized_Ind_D .b dd").size() == 0){
											$(".centralized_Ind_D .a_2").hide();
											$(".hiden-loading-temp",$$).children().text("本公寓还没有房源，请先添加房源");
											$(".hiden-loading-temp",$$).removeClass("none");
										}
										$(".centralized_Ind_D > .b > ul > li").each(function(){
											if($(this).children("dl").children().size() == 0) $(this).hide();
										});
										return false;
									}
									scrollbar.animate({"left":-(100-scrollbalscroll*num_cur)+"%"},300);
									var timer = null;
									clearTimeout(timer);
									timer = setTimeout(autodeleteus,200);
								}
							});
						}
						autodeleteus();
				  },
				//取消删除状态
				deletecancel : function(){
					$(".centralized_DeleteCancel",$$).off("click").on("click",function(){
						if(sys.ie && sys.ie < 8){
							$(".centralized_Ind_C > .a > ul > li:lt(2)",$$).show();
							$(".centralized_Ind_C > .a > ul > li:last",$$).hide();
							$(".centralized_Ind_C > .b",$$).show();
						}else{
							$(".centralized_Ind_C > .a > ul > li:lt(2)",$$).show().animate({"width":"76px"},200)
							$(".centralized_Ind_C > .a > ul > li:last",$$).fadeOut(200);
							$(".centralized_Ind_C > .b",$$).fadeIn(400);
						}
						$(".centralized_Delete",$$).removeClass("deleteStyle");
						$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
								$(this).children(".floorNum").children(".checkBoxAll").hide();
								$(this).children(".floorNum").children("p").hide();
								$(this).children("dl").children("dd").each(function(){
									$(this).children(".checkBox").hide();
									$(this).children(".jtBox,.icon_Style,.rent_Style").show();
									$(this).children(".romm_NUM").css({"padding-left":"8px","width":"136px"});
								});
							});
					});
				},
				//删除状态下勾选复选框
				delete_CheckBox : function(){
					//给非未出租状态下鼠标掠过复选框添加提示框
					$(".centralized_Ind_D > .b > ul > li > dl > dd",$$).each(function(){
						if($(this).attr("data-rentstyle") != "notRented" || $(this).attr("data-booked")){
							$(this).children(".checkBox").addClass("canNotDelete");
							if($(this).children(".delete_DetailTxt").size() == 0){
								$(this).append("<div class='delete_DetailTxt'>只能删除未出租房屋，该房屋有合约！</div>");	
							}
							$(this).children(".checkBox").hover(function(){
								$(this).siblings(".delete_DetailTxt").show();
							},function(){
								$(this).siblings(".delete_DetailTxt").hide();
							});
						}
					});
					
					$(".centralized_Ind_D > .b > ul > li > dl > dd > .checkBox > label",$$).click(function(){
						if($(this).parent().hasClass("canNotDelete")){
							return false;
						}
						$(this).toggleClass("checked");
						if($(this).hasClass("checked")){
							$(this).children(".gou").show();
							$(this).next().attr("checked",true);
						}else{
							$(this).children(".gou").hide();
							$(this).next().removeAttr("checked");
						}
					});
				},
				//删除状态下整层楼房间全选
				delete_CheckBoxAll : function(){
					$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
						$(this).children(".floorNum").children(".checkBoxAll").click(function(){
							$(this).toggleClass("checked").children(".gou").toggle();
							if($(this).hasClass("checked")){
								$(this).parent().siblings("dl").children("dd").each(function(){
									if(!$(this).children(".checkBox").hasClass("canNotDelete")){
										$(this).children(".checkBox").children("label").addClass("checked").children(".gou").show();
										$(this).children(".checkBox").children("label").next().attr("checked",true);	
									}
								});
							}else{
								$(this).parent().siblings("dl").children("dd").each(function(){
									$(this).children(".checkBox").children("label").removeClass("checked").children(".gou").hide();
									$(this).children(".checkBox").children("label").next().removeAttr("checked");
								});
							}
						});
					});
				},
				//停用房间点击弹窗
				stopRentedClick : function(){
					var that = this;
					$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
						$(this).children("dl").children("dd").each(function(){
							$(this).children(".jtBox").children(".detail_Choices").children("ul").children("li").children(".a_StopUse").off("click").on("click",function(){
								var thats = $(this).parents("dd");
								var house_id = $(this).parents("dd").attr("house-id");
								var type = "get";
								var url = $(this).attr("url");
								var flat_id = document.URL.split("flat_id=")[1];
								var d = dialog({
									title: '<i class="ifont">&#xe62e;</i><span>停用房间</span>',
									content:'<div class="rsv-wrap reserveUserForm jzf-form" id="reserve_box" style="height: 175px;">'
												+'<div class="jzf-col">'
													+'<div class="col-box">'
														+'<div class="jzf-col-l float-l">'
															+'<span><i class="red">*</i>起始日期：</span>'
														+'</div>'
														+'<div class="jzf-col-r fl clearfix">'
															+'<input type="text" datatype="*,checkstart" readonly="readonly" name="begin_date" nullmsg="请选择起始日期" errormsg="起始时间不能超过终止时间" class="ipt ipt-165 rev-over-temp-wdate ipx-txt">'
															+'<span class="check-error-ts check-error-auto">'
															+'<i class="ico-arrwo-d"></i>'
															+'<span class="check-error"></span>'
														+'</span>'
														+'</div>'
												    +'</div>'
													+'<div class="col-box">'
														+'<div class="jzf-col-l float-l">'
															+'<span><i class="red">*</i>终止日期：</span>'
														+'</div>'
														+'<div class="jzf-col-r fl clearfix">'
															+'<input type="text" datatype="*,checkend" readonly="readonly"  name="end_date" nullmsg="请选择终止日期" errormsg="终止时间不能小于起始时间" class="ipt ipt-165 rev-over-temp-wdate  ipx-txt">'
															+'<span class="check-error-ts check-error-auto">'
																+'<i class="ico-arrwo-d"></i>'
																+'<span class="check-error"></span>'
															+'</span>'
														+'</div>'
												    +'</div>'
												+'</div>'
												+'<div class="jzf-col">'
													+'<div class="jzf-col-l">'
														+'<span><i class="red">&nbsp;&nbsp;</i>停用说明：</span>'
													+'</div>'
													+'<div class="jzf-col-r blo-row">'
														+'<textarea class="ipx-txt" id="remark" datatype="string255"  errormsg="备注不能超过255个字符" ignore="ignore"></textarea>'
														+'<span class="check-error-ts check-error-auto">'
															+'<i class="ico-arrwo-d ico-arrwo-d-remark"></i>'
															+'<span class="check-error check-error-textarea"></span>'
														+'</span>'
													+'</div>'
												+'</div>'
											+'</div>',
									okValue: '保 存', drag: true,
									ok: function () {
					
									},
									cancelValue: '取消',
									cancel: function () {
			//							_isClicked=false;
									}
								});
								d.showModal();
								$(".rev-over-temp-wdate",".ui-dialog").off("click").on("click",function(){
									calendar.inite();
								});
								//针对IE10以下的input提示语兼容
								if(sys.ie && sys.ie < 10){
									$(".ui-dialog").placeholder();
								}
								//表单验证
								$(".ui-dialog").Validform({
									btnSubmit : ".ui-dialog-autofocus",
									showAllError : true,
									tiptype : function(msg,o,cssctl){
										var objtip=o.obj;
					               		objtip=objtip.parents(".jzf-col-r").find(".check-error");
						                cssctl(objtip,o.type);
						                objtip.text(msg);
						                if(o.type == 3){
						                	objtip.parent().show();	
						                }
									},
						            datatype : {
						              "checkstart":function(gets,obj,curform,regxp){
						              	  var endtime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='end_date']").val();
						              	  if(endtime != ""){
						              	  	endtime = changetodate(endtime);
						              	  	gets =  changetodate(gets);
						              	  	if(gets > endtime){return false;}
						              	  	else{
						              	  		$(obj).parents(".col-box").siblings(".col-box").find("input[name='end_date']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
						              	  	}
						              	  }
						              	  function changetodate(time){
						              	  	var time_date = time.split("-");
						              	  	var date=new Date();
						              	  	date.setFullYear(time_date[0]);
						              	  	date.setMonth(time_date[1]-1);
						              	  	date.setDate(time_date[2]);
						              	  	return Date.parse(date)/1000;
						              	  }
						              },
						              "checkend":function(gets,obj,curform,regxp){
						              	 var starttime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").val();
						              	 var today = gettoday();
						              	 if(starttime == ""){
						              	 	starttime = today;
						              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").val(today);
						              	 }
						              	 starttime = changetodate(starttime);
						              	 gets = changetodate(gets);
						              	 if(starttime > gets) {return false;}
						              	 else{
						              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
						              	 }
						              	 
						              	 function changetodate(time){
						              	  	var time_date = time.split("-");
						              	  	var date=new Date();
						              	  	date.setFullYear(time_date[0]);
						              	  	date.setMonth(time_date[1]-1);
						              	  	date.setDate(time_date[2]);
						              	  	return Date.parse(date)/1000;
						              	  }
						              	 function gettoday(){
						              	 	var date = new Date();
						              	 	var year = date.getFullYear();
						              	 	var month = date.getMonth()+1;
						              	 	var day = date.getDate();
						              	 	return year+"-"+month+"-"+day;
						              	 }
						              },
						              "string255":function(gets,obj,curform,regxp){
						              	var value = gets.replace(/([^\u0000-\u00FF])/g,"***");
				                   	 	 var length = value.length;
				                   	 	 if(length>255) return false;
						              }
						            },
						            callback : function(form){
							            var endtime_start  = $("input[name='begin_date']",form).val();
										var endtime_end = $("input[name='end_date']",form).val();
										var notice = $("#remark",form).val();
										var data = {
											flat_id : flat_id,
											room_id : house_id,
											endtime_start : endtime_start,
											endtime_end : endtime_end,
											notice : notice
										};
										d.close();
										d.remove();
										ajax.doAjax(type,url,data,function(data){
											if(data.status == 1){
												var d_auto = dialog({
														title: '提示信息',
														content: '停用成功！',
														okValue: '确 定', drag: true,
														ok : function(){
//															thats.replaceWith(data.data);
//															that.others();
//															that.num_centralized_Ind_DLi_Z();
//															that.eventbind();
//															that.rentStyleChoose();
															d_auto.close();
															var tag = WindowTag.getCurrentTag();
						    								window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
														}
													});
													d_auto.showModal();
												}else{
													var d = dialog({
														title: '提示信息',
														content: data.data,
														okValue: '确 定', drag: true,
														ok : function(){
															d.close();
														}
													});
													d.showModal();
												}
											});
							            	return false;
						            }
								});
								$(":input,textarea",".ui-dialog").focus(function(){
									if($(this).hasClass("Validform_error")){
										$(this).css("background","none");
										$(this).siblings(".check-error-auto").hide();
									}
								}).blur(function(){
									$(this).removeAttr("style");
									if($(this).hasClass("Validform_error")){
										$(this).siblings(".check-error-auto").show();	
									}
								});
							});
						});
					});
				},
				//预约退租点击弹窗
				bookedOutRentedClick : function(){
					$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
						$(this).children("dl").children("dd").each(function(){
							$(this).children(".jtBox").children(".detail_Choices").children("ul").children("li").children(".a_BookOutRented").off("click").on("click",function(){
								var thats = $(this).parents("dd");
								var house_id = $(this).parents("dd").attr("house-id");
								var house_num = $(this).parents("dd").children(".romm_NUM").text();
								var flat_id = document.URL.split("flat_id=")[1];
								var type = "get";
								var url = $(this).attr("url");
								var d = dialog({
									title: '<i class="ifont ifont-yytz">&#xe663;</i><span>预约退租</span>',
									content: '<div class="dg-title">房间: '+house_num+'</div>'
											+'<div class="dg-option"><span class="dg-date">退租时间:</span><input type="text" class="ipt" placeholder = "2014-4-28"/><span class="check-error check-error-ts"></div>'
											+ '<div class="dg-title">备注说明:</div>'
											+ '<textarea class="dg-con dg-yytz"></textarea>',
									okValue: '保 存', drag: true,
									ok: function () {
										var onj = $(".dg-option").children("input");
										onj.next().text("");
										if(onj.val() == '' || onj.val() == onj.attr("placeholder")){
											onj.next().text("请选择退租时间").addClass("Validform_wrong");
											return false
										}
										var time_outrented = $(".dg-option").children("input").val();
										var notice = $(".dg-yytz").val();
										var data = {
											flat_id : flat_id,
											room_id : house_id,
											time_outrented : time_outrented,
											notice : notice
										}
										ajax.doAjax(type,url,data,function(data){
											if(data.status == 1){
														var dd = dialog({
															title: '提示信息',
															content: "预约退租成功！",
															okValue: '确 定', drag: true,
															ok: function (){
//																thats.replaceWith(data.data);
//																that.others();
//																that.num_centralized_Ind_DLi_Z();
//																that.eventbind();
																dd.close();
																var tag = WindowTag.getCurrentTag();
						    									window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
															}
														});
														dd.showModal();
													}else{
														var dd = dialog({
															title: '提示信息',
															content: data.data,
															okValue: '确 定', drag: true,
															ok: function (){
																dd.close();
															}
														});
														dd.showModal();
													}
										});
									},
									cancelValue: '取 消',
									cancel: function () {
			//							_isClicked=false;
									}
								});
								d.showModal();
								$(".dg-option input").off("click").on("click",function(){
									calendar.inite();
								});
								//针对IE10以下的input提示语兼容
								if(sys.ie && sys.ie < 10){
									$(".ui-dialog").placeholder();
								}
//								 $(".dg-option input").blur(function(){
//								 	if($(this).val() == '' || $(this).val() == $(this).attr("placeholder")){
//											$(this).next().text("请选择退租时间").addClass("Validform_wrong");
//										}else{
//											$(this).next().text("");
//										}
//								 });
							});
						});
					});		
				},
				//退定点击弹窗
				outBooked : function(){
					$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
						$(this).children("dl").children("dd").each(function(){
							$(this).children(".jtBox").children(".detail_Choices").children("ul").children("li").children(".a_OutBooked").off("click").on("click",function(){
								var thats = $(this).parents("dd");
								var url = $(this).attr("url");
								var type = "get";
								var house_id = $(this).parents("dd").attr("house-id");
								var flat_id = document.URL.split("flat_id=")[1];
								var data1 = {
									flat_id : flat_id,
									room_id : house_id
								}
								ajax.doAjax(type,url,data1,function(data){
									if(data.status == 1){
										$("#hideTemp .lr-tb",$$).empty();
										var datas = data.data;
										for(var n in datas){
											var reserve_id = datas[n].reserve_id;
											var money = datas[n].money;
											var name = datas[n].name;
											var phone = datas[n].phone;
											var str='<tr><td style="width:49px"><label class="checkbox"><span class="gou ifont1">&#xe60c;</span></label><input type="checkbox" value="'+reserve_id+'"/></td><td style="width:111px">'+name+'</td><td style="width:199px">'+phone+'</td><td>'+money+'元</td></tr>';
											$("#hideTemp .lr-tb",$$).append(str);
										}
										var hideTemp = $("#hideTemp",$$).html();
										var d = dialog({
											title: '<i class="ifont ifont-yytz">&#xe6a3;</i><span>选择退定人</span>',
											content: hideTemp,
											okValue: '保 存', drag: true,
											ok: function () {
												type="post";
												var reserve_ids = [];
												$(".centralized_Ind_tab_td input:checked").each(function(){
													reserve_ids.push($(this).val());
												});
												var data2 = {
													flat_id : flat_id,
													room_id : house_id,
													reserve_id : reserve_ids
												}
												ajax.doAjax(type,url,data2,function(data){
													if(data.status == 1){
														var dd = dialog({
															title: '提示信息',
															content: "退定成功！",
															okValue: '确 定', drag: true,
															ok: function (){
//																thats.replaceWith(data.data);
//																that.others();
//																that.num_centralized_Ind_DLi_Z();
//																that.eventbind();
																dd.close();
																var tag = WindowTag.getCurrentTag();
						    									window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
																if(typeof data['url'] == 'string'){
																	window.WindowTag.openTag(data.url);
																}
															}
														});
														dd.showModal();
													}else{
														var dd = dialog({
															title: '提示信息',
															content: data.data,
															okValue: '确 定', drag: true,
															ok: function (){
																dd.close();
															}
														});
														dd.showModal();
													}
												});
											},
											cancelValue: '取消',
											cancel: function () {
												
											}
										});
										d.showModal();
										$(".centralized_Ind_tab_td .checkbox").off("click").on("click",function(){
											$(this).toggleClass("checked");
											if($(this).hasClass("checked")){
												$(this).children(".gou").show();
												$(this).next("input").attr("checked",true);
											}else{
												$(this).children(".gou").hide();
												$(this).next("input").removeAttr("checked");
											}
										});
									}else{
										var d = dialog({
											title: '提示信息',
											content: "预定人不存在，无法退定！",
											okValue: '确 定', drag: true,
											ok: function () {
												d.close();
											}
										});
										d.showModal();
									}
								});
							});
						});
					});		
				},
				//预定点击弹窗
				booked : function(){
					var that = this;
					$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
					    $(this).children("dl").children("dd").each(function(){
							$(this).children(".jtBox").children(".detail_Choices").children("ul").children("li").children(".a_Booked").off("click").on("click",function(){
								var thats = $(this).parents("dd");
								var url = $(this).attr("url");
								var type = "get";
								var house_id = $(this).parents("dd").attr("house-id");
								var rsvTemp=$("#hide_reserve_detail",$$).html();
								var d = dialog({
									title: '<i class="ifont">&#xf0077;</i><span>预定人信息</span>',
									content: rsvTemp,
									okValue: '保 存', drag: true,
									ok : function(){
										
									},
									cancelValue: '取消',
									cancel: function (){
											
									}
								});
								d.showModal();
								//自定义下拉菜单
								$(".selectByM",".ui-dialog").each(function(){
									if($(this).attr("hasevent")){
										$(this).selectObjM(1,function(val,inp){
											if(!inp.hasClass("Validform_error")) inp.siblings(".check-error-auto").hide();
										})
									}else{
										$(this).selectObjM();	
									}
								});
								
								$(".rev-over-temp-wdate",".ui-dialog").off("click").on("click",function(){
									calendar.inite();
								});
//								$(".ui-dialog input[name='begin_date']", .ui-dialog input[name='end_date']"".ui-dialog").off("click").on("click",function(){
//									alert(1);
//									calendar.inite();
//								});
								$("input,textarea",".ui-dialog").blur(function(){
									if(!$(this).hasClass("Validform_error")) $(this).siblings(".check-error-auto").hide();
								});
								//表单验证
								$(".ui-dialog").Validform({
									btnSubmit : ".ui-dialog-autofocus",
									showAllError : true,
									tiptype : function(msg,o,cssctl){
										var objtip=o.obj;
					               		objtip=objtip.parents(".jzf-col-r").find(".check-error");
						                cssctl(objtip,o.type);
						                objtip.text(msg);
						                if(o.type == 3){
						                	objtip.parent().show();	
						                }
									},
						            datatype : {
						              "idcard":function(gets,obj,curform,regxp) {
						                   var reg1=/^(0|[1-9][0-9]*)$/;
						                   var length = gets.length;
						                   var str_last = gets.substr(length-1,length-1);
						                   if($.trim(gets)=="") return false;
						                   if(reg1.test(gets) || (str_last == "x" && reg1.test(gets.replace("x","")))){
						                   		if(length == 18 || length == 15){
						                   			return true	
						                   		}
						                   }
											return false;
						              },
						               "chooseGroup":function(gets,obj,curform,regxp) {
						                   if(obj.attr("selectVal") == ''){
						                   	 return obj.attr("choosenull");
						                   }
				
						              },
						              "checkstart":function(gets,obj,curform,regxp){
						              	  var endtime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='end_date']").val();
						              	  if(endtime != ""){
						              	  	endtime = changetodate(endtime);
						              	  	gets =  changetodate(gets);
						              	  	if(gets > endtime) {return false;}
						              	  	else{
						              	  		$(obj).parents(".col-box").siblings(".col-box").find("input[name='end_date']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
						              	  	}
						              	  }
						              	  function changetodate(time){
						              	  	var time_date = time.split("-");
						              	  	var date=new Date();
						              	  	date.setFullYear(time_date[0]);
						              	  	date.setMonth(time_date[1]-1);
						              	  	date.setDate(time_date[2]);
						              	  	return Date.parse(date)/1000;
						              	  }
						              },
						              "checkend":function(gets,obj,curform,regxp){
						              	 var starttime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").val();
						              	 var today = gettoday();
						              	 if(starttime == ""){
						              	 	starttime = today;
						              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").val(today);
						              	 }
						              	 starttime = changetodate(starttime);
						              	 gets = changetodate(gets);
						              	 if(starttime > gets) {return false;}
						              	 else{
						              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
						              	 }
						              	 
						              	 function changetodate(time){
						              	  	var time_date = time.split("-");
						              	  	var date=new Date();
						              	  	date.setFullYear(time_date[0]);
						              	  	date.setMonth(time_date[1]-1);
						              	  	date.setDate(time_date[2]);
						              	  	return Date.parse(date)/1000;
						              	  }
						              	 function gettoday(){
						              	 	var date = new Date();
						              	 	var year = date.getFullYear();
						              	 	var month = date.getMonth()+1;
						              	 	var day = date.getDate();
						              	 	return year+"-"+month+"-"+day;
						              	 }
						              },
						              "string255":function(gets,obj,curform,regxp){
						              	var value = gets.replace(/([^\u0000-\u00FF])/g,"***");
				                   	 	 var length = value.length;
				                   	 	 if(length>255) return false;
						              }
						            },
						            callback : function(form){
						            		if($(".ui-dialog-autofocus",form).hasClass("none-click")) return false;
	           								$(".ui-dialog-autofocus",form).addClass("none-click");
							            	that.bookedsubmit(url,type,house_id,thats,d);
							            	return false;
						            }
								});
								$(":input,textarea",".ui-dialog").focus(function(){
									if($(this).hasClass("Validform_error")){
										$(this).css("background","none");
										$(".check-error-auto",".ui-dialog").hide();
									}
								}).blur(function(){
									$(this).removeAttr("style");
									if($(this).hasClass("Validform_error")){
										$(this).siblings(".check-error-auto").show();	
									}
								});
								//检测预订人是否存在
								var score  = '';			//平均分数
								var counta = '';       //评价次数
								var urlsauto = '';	
								$("input[name='idcard']",".ui-dialog-body").off("blur").on("blur",function(){
									var thats = $(this);
									var name = $("input[name='name']",$(".ui-dialog-body"));
									var phone = $("input[name='phone']",$(".ui-dialog-body"));
									var url = $(this).attr("data-score-action");
									var type = "post";
									var idcard = $(this).val();
									var data = {
										idcard : idcard
									};
									if(!$(this).hasClass("Validform_error") && $.trim($(this).val())!=$(this).attr("prevval")){
										$(this).attr("prevval", $.trim($(this).val()));
										ajax.doAjax(type,url,data,function(data){
											if(data.status == 1){
												name.val(data.tdata.name).siblings(".check-error-ts").hide();
												phone.val(data.tdata.phone).siblings(".check-error-ts").hide();
												score = data.avgscore.avgscore;
												counta = data.avgscore.allComment;
												if(counta > 0){
													thats.siblings(".tip-black-list").show().find(".tip-msg .red").text(score);
													thats.siblings(".tip-black-list").find(".this-close").off("click").on("click",function(){
														$(this).parent().hide();
													});
													thats.siblings(".tip-black-list").find(".tip-msg a").off("click").on("click",function(){
														var index = 0;
														showcontent(index);
													});	
												}
											}else{
												thats.siblings(".tip-black-list").hide();
											}
										});
									}
								});
								function content_auto(index){
									var urls = urlsauto;
									var types = "get";
									var idcard = $("input[name='idcard']",$(".ui-dialog-body")).val();
									var current_page = index + 1;
									var datas = {
														idcard : idcard,
														current_page : current_page
												};
									ajax.doAjax(types,urls,datas,function(data){
										if(data.status == 1){
														var namea = data.data.data[0].name;
														var phonea = data.data.data[0].phone;
														var id_carda =  data.data.data[0].idcard;
														var pages_Total = data.data.page.count;   //总条数
														var pages_Count = data.data.page.size;    //每页条数
														var contentsauto = '';
														var stars = '';
														var num_star = 1;
														for(var i  in data.data.data){
															num_star =  parseInt(data.data.data[i].score/20);
															switch(num_star){
																case 1:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-off.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 2:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 3:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 4:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 5:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-on.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
															}
															contentsauto += '<tr>'+stars+'<td>'+data.data.data[i].remark+'</td>'+'<td>'+data.data.data[i].phone+'</td>'+'<td>'+data.data.data[i].create_time+'</td></tr>';
														}
													$(".lst_tb_auto tr:gt(0)").remove();
													$(".lst_tb_auto").append(contentsauto);
										}
									});
								}
								function showcontent(index){
									var urls = $("input[name='idcard']",$(".ui-dialog-body")).attr("data-info-action");
									urlsauto = urls;
									var types = "get";
									var idcard = $("input[name='idcard']",$(".ui-dialog-body")).val();
									if(!!!index) index=0;
									var current_page = index + 1;
									var datas = {
														idcard : idcard,
														current_page : current_page
												};
									ajax.doAjax(types,urls,datas,function(data){
										if(data.status == 1){
														var namea = data.data.data[0].name;
														var phonea = data.data.data[0].phone;
														var id_carda =  data.data.data[0].idcard;
														var pages_Total = data.data.page.count;   //总条数
														var pages_Count = data.data.page.size;    //每页条数
														var contentsauto = '';
														var stars = '';
														var num_star = 1;
														for(var i  in data.data.data){
															num_star =  parseInt(data.data.data[i].score/20);
															switch(num_star){
																case 1:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-off.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 2:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 3:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 4:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
																			break;
																case 5:
																			stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-on.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
															}
															contentsauto += '<tr>'+stars+'<td>'+data.data.data[i].remark+'</td>'+'<td>'+data.data.data[i].phone+'</td>'+'<td>'+data.data.data[i].create_time+'</td></tr>';
														}
														var dd = dialog({
															title: '<i class="ifont">&#xf0077;</i><span>评价记录</span>',
															content: '<div id="custm-hideTempAuto">'
																				+'<div class="crm-custm-wrap">'
																					+'<div class="col-custm">'
																						+'<ul class="lin-custm-basic clearfix mb10" id="temp-custm">'
																								+'<li>'
																									+'<span>租客姓名：</span>'
																									+'<span class="gray">'+namea+'</span>'
																								+'</li>'
																								+'<li>'
																									+'<span>联系电话：</span>'
																									+'<span class="gray">'+phonea+'</span>'
																								+'</li>'
																								+'<li>'
																									+'<span>证件号码：</span>'
																									+'<span class="gray">'+id_carda+'</span>'
																								+'</li>'
																						+'</ul>'
																						+'<ul class="lin-custm-score clearfix">'
																							+'<li>'
																								+'<span>评分次数：</span>'
																								+'<span class="gray"><i>'+counta+'</i>次</span>'
																							+'</li>'
																							+'<li>'
																								+'<span>评价分数：</span>'
																								+'<span class="gray"><i>'+score+'</i></span>'
																							+'</li>'
																						+'</ul>'
																					+'</div>'
																					+'<table class="lst_tb lst_tb_auto">'
																						+'<tr>'
																							+'<th class="th1">评价分数</th>'
																							+'<th class="th2">评分原因</th>'
																							+'<th>操作人</th>'
																							+'<th>操作时间</th>'
																						+'</tr>'
																						+contentsauto
																					+'</table>'
																					+'<div class="jzf-pagination clearfix">'
																						+'<div class="pagination" id="pagination-auto-yd"></div>'
																						+'<div class="jump-to-page clearfix">'
																							+'<span>跳转至</span>'
																							+'<div class="jump-edit-box">'
																					        	+'<input type="text" placeholder="页码">'
																					        	+'<a href="javascript:;" class="p-btn btn btn4">GO</a>'
																					        	+'<i class="page-unit">页</i>'
																					        +'</div>'
																						+'</div>'
																					+'</div>'
																				+'</div>'
																			+'</div>'
														});
														dd.showModal();
														if(data.data.page.page == 1){
															ajax.iniPagination(pages_Total,"#pagination-auto-yd",content_auto,pages_Count);	
														}
												}else{
													var dd = dialog({
																	title: '提示信息',
																	content: data.message,
																	okValue: '确 定', drag: true,
																	ok : function(){
																		dd.close();
																	}
																});
																dd.showModal();
										}
									});
								}
							});
						  });
					});
				},
				//出租点击弹窗
				rented : function(){
					var that = this;
					$(".a_Rental",$$).off("click").on("click",function(e){
						e.stopPropagation();
						var jup_url = $(this).attr("jup-url"),
							  reserve_url = $(this).attr("reserve-url"),
							  flat_id = document.URL.split("&flat_id=")[1],
							  room_id = $(this).parents("dd").attr("house-id");
						if(typeof reserve_url == "undefined"){
							window.location.href = jup_url;
							return false;
						}
						var data = {
							flat_id : flat_id,
							room_id : room_id
						};
						ajax.doAjax("get",reserve_url,data,function(json){
							if(json.status == 1){
								var count = json.count;
								if(count ==1){
									var reserve_id = json.data[0].reserve_id;
									jup_url += "&reserve_id="+reserve_id;
									window.location.href=jup_url;
								}else{
									$("#hideTemp .lr-tb",$$).empty();
										var datas = json.data;
										for(var n in datas){
											var reserve_id = datas[n].reserve_id;
											var money = datas[n].money;
											var name = datas[n].name;
											var phone = datas[n].phone;
											var str='<tr><td style="width:49px"><label class="radio"><span class="gou ifont">&#xe617;</span><span class="gou-no ifont">&#xe612;</span></label><input type="radio" name="renter" value="'+reserve_id+'"/></td><td style="width:111px">'+name+'</td><td style="width:199px">'+phone+'</td><td>'+money+'元</td></tr>';
											$("#hideTemp .lr-tb",$$).append(str);
										}
										var hideTemp = $("#hideTemp",$$).html();
										var d = dialog({
											title: '<i class="ifont ifont-yytz">&#xe6a3;</i><span>选择出租人</span>',
											content: hideTemp,
											okValue: '确 定', drag: true,
											ok: function () {
												var reserve_id = $(":radio:checked",".ui-dialog-content").val();
												if(typeof reserve_id == "undefined"){
													var dd = dialog({
														title: '提示信息',
														content: "没有选择预订人！",
														okValue: '确 定', drag: true,
														ok : function(){
															dd.close();
														}
													});
													dd.showModal();
													return false;
												}else{
													jup_url += "&reserve_id="+reserve_id;
													window.location.href=jup_url;
												}
											},
											cancelValue: '取消',
											cancel: function () {
												
											}
										});
										d.showModal();
										$(".centralized_Ind_tab_td .radio",".ui-dialog-content").off("click").on("click",function(){
												$(this).children(".gou").show().siblings().hide().parents("tr").siblings("tr").find(".gou-no").show().siblings().hide();
												$(this).next(":radio").attr("checked",true).parents("tr").siblings("tr").find(":radio").removeAttr("checked");
										});
								}
							}else{
									var dd = dialog({
														title: '提示信息',
														content: json.data,
														okValue: '确 定', drag: true,
														ok : function(){
															dd.close();
														}
													});
									dd.showModal();
							}
						});
					});
				},
				//预定信息提交
				bookedsubmit : function(url,type,house_id,thats,dd){
					var that = this;
					var name = $(".ui-dialog input[name='name']").val();
					var phone = $(".ui-dialog input[name='phone']").val();
					var idcard = $(".ui-dialog input[name='idcard']").val();
					var money = $(".ui-dialog input[name='money']").val();
					var begin_date = $(".ui-dialog input[name='begin_date']").val();
					var end_date = $(".ui-dialog input[name='end_date']").val();
					var paytype = $(".ui-dialog input[name='ya']").attr("selectval");
					var gettype = $(".ui-dialog input[name='fu']").attr("selectval");
					var remark = $(".ui-dialog #remark").val();
					var flat_id = document.URL.split("flat_id=")[1];
					var data = {
						flat_id : flat_id,
						room_id : house_id,
						name : name,
						phone : phone,
						idcard : idcard,
						money : money,
						begin_date : begin_date,
						end_date : end_date,
						paytype : paytype,
						gettype : gettype,
						remark : remark
					}
					dd.close();
					dd.remove();
					ajax.doAjax(type,url,data,function(data){
						if(data.status == 1){
							var d = dialog({
								title: '提示信息',
								content: '预定成功！',
								okValue: '确 定', drag: true,
								ok : function(){
									$(".ui-dialog-autofocus").removeClass("none-click");
//									thats.replaceWith(data.data);
//									that.others();
//									that.num_centralized_Ind_DLi_Z();
//									that.eventbind();
									var tag = WindowTag.getCurrentTag();
						    		window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
									var reserve_url = data.reserve_url;
									if(typeof reserve_url == "string"){
										window.WindowTag.openTag(reserve_url);
									}
								}
							});
							d.showModal();
						}else{
							var d = dialog({
								title: '提示信息',
								content: data.data,
								okValue: '确 定', drag: true,
								ok : function(){
									$(".ui-dialog-autofocus").removeClass("none-click");
									d.close();
								}
							});
							d.showModal();
						}
					});
					$(".ui-dialog-close",".ui-dialog-header").hide();
				},
				//搜索
				searchcontent : function(){
					$(".centralized_Ind_SearchButton",$$).off("click").on("click",function(){
						var thisa = $(this);
						var housetype = $(".centralized_Ind_RoomType",$$).attr("selectVal");
						var searchtxt = $("#centralized_Ind_SearchTxt",$$).val();
						var url = $(this).attr("url");
						var type = "get";
						var flat_id = document.URL.split("flat_id=")[1];
						var numindex = thisa.parents(".jooozo_Page").index();
						var tag = $(".tag li:eq("+numindex+")").children(".a_Tags");
						var tagname = tag.text();
						var data = {
							room_type : housetype,
							search_str : searchtxt,
							flat_id : flat_id
						}
						tag.text("加载中...");
						ajax.doAjax(type,url,data,function(data){
							tag.text(tagname);
							if(data.status == 1){
								var obj = thisa.parents("#centralized_Ind").parent();
								obj.empty();
								obj.append(data.data);
								auto($$);
								if($(".centralized_Ind_D .b dd").size() == 0){
									$(".centralized_Ind_D .a_2").hide();
									$(".hiden-loading-temp",$$).children().text("没有找到任何数据，请调整搜索条件再尝试！");
									$(".hiden-loading-temp",$$).removeClass("none");
								}
							}else{
								var d = dialog({
									title: '提示信息',
									content: '搜索结果不存在！',
									okValue: '确 定', drag: true,
									ok : function(){
										d.close();
									}
								});
								d.showModal();
							}
						});
					});
				},
				//启用提交
				startuse : function(){
					var that = this;
					$(".a_StartUse",$$).off("click").on("click",function(){
						var thats = $(this);
						var url = $(this).attr("url");
						var type = "get";
						var room_id = $(this).parents("dd").attr("house-id");
						var flat_id = document.URL.split("flat_id=")[1];
						var data = {
							room_id : room_id,
							flat_id : flat_id
						}
						var d = dialog({
							title: '提示信息',
							content: '您正在执行取消停用操作，确定？',
							okValue: '确 定', drag: true,
							ok : function(){
								d.close();
								ajax.doAjax(type,url,data,function(data){
									if(data.status == 1){
											var d = dialog({
											title: '提示信息',
											content: '取消停用成功！',
											okValue: '确 定', drag: true,
											ok : function(){
//												thats.parents("dd").replaceWith(data.data);
//												that.others();
//												that.num_centralized_Ind_DLi_Z();
//												that.eventbind();
												d.close();
												var tag = WindowTag.getCurrentTag();
						    					window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
											}
											});
											d.showModal();
									}else{
										var d = dialog({
												title: '提示信息',
												content: data.data,
												okValue: '确 定', drag: true,
												ok : function(){
													d.close();
												}
											});
										d.showModal();
									}
								});
							}
						});
						d.showModal();
					});
				},
				//取消预约退租
				resetOutRented : function(){
					var that = this;
					$(".a_resetOutRented",$$).off("click").on("click",function(){
						var url = $(this).attr("url");
						var type = "get";
						var flat_id = document.URL.split("flat_id=")[1];
						var thats = $(this).parents("dd");
						var room_id = thats.attr("house-id");
						var data = {
							flat_id : flat_id,
							room_id : room_id
						}
						var d = dialog({
							title: '提示信息',
							content: '您正在执行撤销预约退租操作，确定？',
							okValue: '确 定', drag: true,
							ok : function(){
								d.close();
								ajax.doAjax(type,url,data,function(data){
									if(data.status == 1){
											var d = dialog({
											title: '提示信息',
											content: '取消预约退租成功！',
											okValue: '确 定', drag: true,
											ok : function(){
//												thats.replaceWith(data.data);
//												that.others();
//												that.num_centralized_Ind_DLi_Z();
//												that.eventbind();
												d.close();
												var tag = WindowTag.getCurrentTag();
						    					window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
											}
											});
											d.showModal();
									}else{
										var d = dialog({
											title: '提示信息',
											content: data.data,
											okValue: '确 定', drag: true,
											ok : function(){
												d.close();
											}
											});
											d.showModal();
									}
								});
							}
						});
						d.showModal();
					});
				},
				//退租
				outrented : function(){
					var that = this;
					$(".a_OutRented",$$).off("click").on("click",function(){
						var thats = $(this).parents("dd");
						var flat_id = document.URL.split("flat_id=")[1];
						var room_id = thats.attr("house-id");
						var room_type = 2;
						var url = $(this).attr("c-url");
						var tz_url = $(this).attr("url");
						var type = "get";
						var data = {
							flat_id : flat_id,
							room_id : room_id
						}
						var d = dialog({
							title: '<i class="ifont">&#xf0077;</i><span>退租提醒</span>',
							content: '您正在执行退租操作，确定？',
							okValue: '确 定', drag: true,
							ok : function(){
								ajax.doAjax(type,url,data,function(data){
									if(data.status == 1){
										var dd = dialog({
											title: '提示信息',
											content: '退租成功！',
											okValue: '确 定', drag: true,
											ok : function(){
//												thats.replaceWith(data.data);
//												that.others();
//												that.num_centralized_Ind_DLi_Z();
//												that.eventbind();
												dd.close();
												var tag = WindowTag.getCurrentTag();
						    					window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
												window.location.href = "#"+tz_url+"&room_focus_id="+room_id+"&house_type="+room_type+"&source=out_tenancy";
											}
										});
										dd.showModal();
									}else{
										var dd = dialog({
											title: '提示信息',
											content: data.data,
											okValue: '确 定', drag: true,
											ok : function(){
												dd.close();
											}
										});
										dd.showModal();
									}
								});
								d.close();
							},
							cancelValue: '取消',
							cancel: function (){
									
							}
							});
							d.showModal();
					});
				},
				//查看房源
				lookDtail  : function(){
					$(".a_Detail",$$).off("click").on("click",function(){
						var url = $(this).attr("url");
						var ctag = WindowTag.getTagByUrlHash(url);
						if(ctag){
							window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
							if(url != ctag.find(' > a:first').attr('href').replace(/^#(.+)$/,'$1')){
								window.WindowTag.loadTag(url,'get',function(){});
							}
						}else{
							window.WindowTag.openTag(url);	
						}
					});
				},
				eventbind : function(){
					var that = this;
					//停用房间点击弹窗
					that.stopRentedClick();
					//预约退租点击弹窗
					that.bookedOutRentedClick();
					//楼层房间计数器
					num_FloorRooms();
					//退定点击弹窗
					that.outBooked();
					//预定点击弹窗
					that.booked();
					//搜索
					that.searchcontent();
					//启用房源
					that.startuse();
					//取消预约退租
					that.resetOutRented();
					//退租
					that.outrented();
					//出租
					that.rented();
					//查看
					that.lookDtail();
				},
				//滚动加载房源房源内容填充
				showhouses : function(data,n){
					var showsame = true; //判断是否出现相同的楼层
					//填充每一个房间
					function everhouses(){
						var obj = $(".centralized_Ind_D > .b > ul > li:last > dl",$$);
						var rental_way = $(".centralized_Ind_D > .b",$$).attr("rental-way");
						if(rental_way == 1){
							rental_way = "整租";
						}else{
							rental_way = "合租";
						}
						for(var n in data){
							var data_rentstyle = data[n].status;
							var room_id = data[n].room_focus_id;
							var room_num = data[n].custom_number;
							switch(data_rentstyle){
								//未租状态
								case "1":
											var str = '';
											//未租有预定
											if(data[n].is_yd == "1"){
												var yy_message = data[n].msg_yd[0];
												var name_yy = '';
												var phone_yy = '';
												var money_yy = '';
												var s_time_yy = '';
												var e_time_yy = '';
												if(typeof(yy_message) != 'undefined'){
													name_yy = yy_message.name;
													phone_yy = yy_message.phone;
													money_yy = yy_message.money;
													s_time_yy = yy_message.stime;
													e_time_yy = yy_message.etime;
												}
												str = '<dd house-id="'+room_id+'" data-booked="true" data-rentStyle="notRented">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'
																	+'<li><a href="#/index.php?c=centralized-roomfocus&a=edit&room_focus_id='+room_id+'" class="a_Detail">查看</a></li>'
																	+'<li><a href="javascript:;" class="a_Booked" url="/index.php?c=centralized-roomfocus&a=renewal">预定</a></li>'
																	+'<li><a href="javascript:;" class="a_OutBooked" url="/index.php?c=centralized-roomfocus&a=abolishrenewal">退定</a></li>'
																	+'<li><a href="javascript:;" class="a_StopUse" url="/index.php?c=centralized-roomfocus&a=stop">停用</a></li>'
																	+'<li><a href="#/index.php?c=tenant-index&a=adds&house_id='+room_id+'" class="a_Rental">出租</a></li>'
																+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="icon_Style ifont"><span class="book">&#xe6a3;</span></span>'
														+'<div class="rented_Detail tc_Detail">'
															+'<ol>'
																+'<li><span class="name">预定人姓名：</span><span class="text">'+name_yy+'</span></li>'
																+'<li><span class="name">预定人电话：</span><span class="text">'+phone_yy+'</span></li>'
																+'<li><span class="name">预定金额：</span><span class="text">'+money_yy+'元</span></li>'
																+'<li><span class="name">预留时间：</span><span class="text">'+s_time_yy+'至'+e_time_yy+'</span></li>'
																+'<!--[if ie 6]>'
																+'<div class="clear"></div>'
																+'<![endif]-->' 
															+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>';
											}else{//未租没有预定
												var emp_message = data[n].emp_msg[0];
												var day_emp = '';
												var money_emp = '';
												if(typeof(emp_message) != 'undefined'){
													day_emp = emp_message.day;
													money_emp = emp_message.money;
												}
												str = '<dd house-id="'+room_id+'"  data-rentStyle="notRented">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'
																	+'<li><a href="#/index.php?c=centralized-roomfocus&a=edit&room_focus_id='+room_id+'" class="a_Detail">查看</a></li>'
																	+'<li><a href="javascript:;" class="a_Booked" url="/index.php?c=centralized-roomfocus&a=renewal">预定</a></li>'
																	+'<li><a href="javascript:;" class="a_StopUse" url="/index.php?c=centralized-roomfocus&a=stop">停用</a></li>'
																	+'<li><a href="#/index.php?c=tenant-index&a=adds&house_id='+room_id+'" class="a_Rental">出租</a></li>'
																+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="rent_Style notRented">未出租</span>'
														+'<div class="rented_Detail tc_Detail">'
															+'<ol>'
																+'<li><span class="name">房间户型：</span><span class="text">'+rental_way+'</span></li>'
																+'<li><span class="name">房间租金：</span><span class="text">'+money_emp+'元/月</span></li>'
																+'<li><span class="name">空置天数：</span><span class="text">'+day_emp+'天</span></li>'
																+'<!--[if ie 6]>'
																+'<div class="clear"></div>'
																+'<![endif]-->' 
															+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>';
											}
											obj.append(str);
										   break;
								//已租状态
								case "2":
											var sj_choice = '';
											var str = '';
											//出租状态下预约退租
											if(data[n].is_yytz =="1"){
												var msg_yytz = data[n].msg_yytz[0];
												var ctime_msg_yytz = '';
												var remark_msg_yytz = '';
												if(typeof(msg_yytz) != 'undefined'){
													ctime_msg_yytz = msg_yytz.creat_time;
													remark_msg_yytz = msg_yytz.remark;
												}
												var msg = data[n].msg[0];
												var name_msg = '';
												var phone_msg = '';
												var pay_msg ='';
												var nextpay_msg = '';
												if(typeof(msg) != 'undefined'){
													name_msg = msg.name;
													phone_msg = msg.phone;
													pay_msg = msg.pay;
													nextpay_msg = msg.next_pay_time;
												}
												if(data[n].is_yd == "1"){
													var msg_yd = data[n].msg_yd[0];
													var name_yy = '';
													var phone_yy = '';
													var money_yy = '';
													var s_time_yy = '';
													var e_time_yy = '';
													if(typeof(msg_yd) != 'undefined'){
														name_yy = msg_yd.name;
														phone_yy = msg_yd.phone;
														money_yy = msg_yd.money;
														s_time_yy = msg_yd.stime;
														e_time_yy = msg_yd.etime;
													}
													sj_choice = '<li><a class="a_Detail" href="#/index.php?c=centralized-roomfocus&amp;a=edit&amp;room_focus_id='+room_id+'">查看</a></li>'
																	+'<li><a class="a_GoOnRented" href="javascript:;">续租</a></li>'
																	+'<li><a class="a_OutRented" href="javascript:;">退租</a></li>'
																	+'<li><a class="a_Booked" url="/index.php?c=centralized-roomfocus&amp;a=renewal" href="javascript:;">预定</a></li>'
																	+'<li><a class="a_OutBooked" href="javascript:;" url="/index.php?c=centralized-roomfocus&amp;a=abolishrenewal">退定</a></li>'
																	+'<li><a class="a_StopUse" url="/index.php?c=centralized-roomfocus&amp;a=stop" href="javascript:;">停用</a></li>'
																	+'<li><a class="a_resetOutRented" url="/index.php?c=centralized-roomfocus&amp;a=revocationsubscribe" href="javascript:;">取消预退</a></li>';
													str = '<dd house-id="'+room_id+'" data-booked="true"  data-rentStyle="outRented">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'+sj_choice+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="icon_Style ifont"><span class="outRent">&#xe663;</span></span>'
														+'<div class="rented_Detail tc_Detail">'
																+'<ol>'
																	+'<li><span class="name">房间户型：</span><span class="text">'+rental_way+'</span></li>'
																	+'<li><span class="name">预定人姓名：</span><span class="text">'+name_yy+'</span></li>'
																	+'<li><span class="name">预定人电话：</span><span class="text">'+phone_yy+'</span></li>'
																	+'<li><span class="name">预定金额：</span><span class="text">'+money_yy+'元</span></li>'
																	+'<li><span class="name">预留时间：</span><span class="text">'+s_time_yy+'至'+e_time_yy+'</span></li>'
																	+'<!--[if ie 6]>'
																	+'<div class="clear"></div>'
																	+'<![endif]-->' 
																+'</ol>'
																+'<ol>'
																	+'<li><span class="name">租客姓名：</span><span class="text">'+name_msg+'</span></li>'
																	+'<li><span class="name">租客电话：</span><span class="text">'+phone_msg+'</span></li>'
																	+'<li><span class="name">下次付款金额：</span><span class="text">'+pay_msg+'</span></li>'
																	+'<li><span class="name">下次付款时间：</span><span class="text">'+nextpay_msg+'</span></li>'
																	+'<!--[if ie 6]>'
																	+'<div class="clear"></div>'
																	+'<![endif]-->' 
																+'</ol>'
																+'<ol>'
																	+'<li><span class="name">退租日期：</span><span class="text">'+ctime_msg_yytz+'</span></li>'
																	+'<li class="bzText"><span class="name">备注说明：</span><p>'+remark_msg_yytz+'</p></li>'
																	+'<!--[if ie 6]>'
																	+'<div class="clear"></div>'
																	+'<![endif]-->' 
																+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>'
												}else{
													sj_choice = '<li><a class="a_Detail" href="#/index.php?c=centralized-roomfocus&amp;a=edit&amp;room_focus_id='+room_id+'">查看</a></li>'
																	+'<li><a class="a_GoOnRented" href="javascript:;">续租</a></li>'
																	+'<li><a class="a_OutRented" href="javascript:;">退租</a></li>'
																	+'<li><a class="a_Booked" url="/index.php?c=centralized-roomfocus&amp;a=renewal" href="javascript:;">预定</a></li>'
																	+'<li><a class="a_StopUse" url="/index.php?c=centralized-roomfocus&amp;a=stop" href="javascript:;">停用</a></li>'
																	+'<li><a class="a_resetOutRented" url="/index.php?c=centralized-roomfocus&amp;a=revocationsubscribe" href="javascript:;">取消预退</a></li>';
													str = '<dd house-id="'+room_id+'" data-rentStyle="outRented">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'+sj_choice+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="icon_Style ifont"><span class="outRent">&#xe663;</span></span>'
														+'<div class="rented_Detail tc_Detail">'
																+'<ol>'
																	+'<li><span class="name">租客姓名：</span><span class="text">'+name_msg+'</span></li>'
																	+'<li><span class="name">租客电话：</span><span class="text">'+phone_msg+'</span></li>'
																	+'<li><span class="name">下次付款金额：</span><span class="text">'+pay_msg+'</span></li>'
																	+'<li><span class="name">下次付款时间：</span><span class="text">'+nextpay_msg+'</span></li>'
																	+'<!--[if ie 6]>'
																	+'<div class="clear"></div>'
																	+'<![endif]-->' 
																+'</ol>'
																+'<ol>'
																	+'<li><span class="name">退租日期：</span><span class="text">'+ctime_msg_yytz+'</span></li>'
																	+'<li class="bzText"><span class="name">备注说明：</span><p>'+remark_msg_yytz+'</p></li>'
																	+'<!--[if ie 6]>'
																	+'<div class="clear"></div>'
																	+'<![endif]-->' 
																+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>'
												}
											}else{
												var msg = data[n].msg[0];
												var name_msg = '';
												var phone_msg = '';
												var pay_msg ='';
												var nextpay_msg = '';
												if(typeof(msg) != 'undefined'){
													name_msg = msg.name;
													phone_msg = msg.phone;
													pay_msg = msg.pay;
													nextpay_msg = msg.next_pay_time;
												}
												if(data[n].is_yd == "1"){
													sj_choice = '<li><a class="a_Detail" href="#/index.php?c=centralized-roomfocus&amp;a=edit&amp;room_focus_id='+room_id+'">查看</a></li>'
																	+'<li><a class="a_GoOnRented" href="javascript:;">续租</a></li>'
																	+'<li><a class="a_OutRented" href="javascript:;">退租</a></li>'
																	+'<li><a class="a_Booked" url="/index.php?c=centralized-roomfocus&amp;a=renewal" href="javascript:;">预定</a></li>'
																	+'<li><a class="a_OutBooked" href="javascript:;" url="/index.php?c=centralized-roomfocus&amp;a=abolishrenewal">退定</a></li>'
																	+'<li><a class="a_StopUse" url="/index.php?c=centralized-roomfocus&amp;a=stop" href="javascript:;">停用</a></li>'
																	+'<li><a class="a_BookOutRented" url="/index.php?c=centralized-roomfocus&a=rentalback" href="javascript:;">预约退租</a></li>';
													str = '<dd house-id="'+room_id+'" data-booked="true"  data-rentStyle="rented">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'+sj_choice+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="icon_Style ifont"><span class="man">&#xe654;</span></span>'
														+'<div class="rented_Detail tc_Detail">'
																+'<ol>'
																	+'<li><span class="name">租客姓名：</span><span class="text">'+name_msg+'</span></li>'
																	+'<li><span class="name">租客电话：</span><span class="text">'+phone_msg+'</span></li>'
																	+'<li><span class="name">下次付款金额：</span><span class="text">'+pay_msg+'</span></li>'
																	+'<li><span class="name">下次付款时间：</span><span class="text">'+nextpay_msg+'</span></li>'
																	+'<!--[if ie 6]>'
																	+'<div class="clear"></div>'
																	+'<![endif]-->' 
																+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>'
												}else{
													sj_choice = '<li><a class="a_Detail" href="#/index.php?c=centralized-roomfocus&amp;a=edit&amp;room_focus_id='+room_id+'">查看</a></li>'
																	+'<li><a class="a_GoOnRented" href="javascript:;">续租</a></li>'
																	+'<li><a class="a_OutRented" href="javascript:;">退租</a></li>'
																	+'<li><a class="a_Booked" url="/index.php?c=centralized-roomfocus&amp;a=renewal" href="javascript:;">预定</a></li>'
																	+'<li><a class="a_StopUse" url="/index.php?c=centralized-roomfocus&amp;a=stop" href="javascript:;">停用</a></li>'
																	+'<li><a class="a_BookOutRented" url="/index.php?c=centralized-roomfocus&a=rentalback" href="javascript:;">预约退租</a></li>';
													str = '<dd house-id="'+room_id+'" data-rentStyle="rented">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'+sj_choice+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="icon_Style ifont"><span class="man">&#xe654;</span></span>'
														+'<div class="rented_Detail tc_Detail">'
																+'<ol>'
																	+'<li><span class="name">租客姓名：</span><span class="text">'+name_msg+'</span></li>'
																	+'<li><span class="name">租客电话：</span><span class="text">'+phone_msg+'</span></li>'
																	+'<li><span class="name">下次付款金额：</span><span class="text">'+pay_msg+'</span></li>'
																	+'<li><span class="name">下次付款时间：</span><span class="text">'+nextpay_msg+'</span></li>'
																	+'<!--[if ie 6]>'
																	+'<div class="clear"></div>'
																	+'<![endif]-->' 
																+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>'
												}
											}
											obj.append(str);
										   break;
								case "3":
											var msg_stop = data[n].stop_msg[0];
											var reason_stop = '';
											var remark_stop = '';
											var stime_stop = '';
											var etime_stop = '';
											if(typeof(msg_stop) != "undefined"){
												reason_stop = msg_stop.stop_reason;
												remark_stop = msg_stop.remark;
												stime_stop = msg_stop.start_time;
												etime_stop = msg_stop.end_time;
											}
											if(data[n].is_yd == "1"){
												var msg_yd = data[n].msg_yd[0];
													var name_yy = '';
													var phone_yy = '';
													var money_yy = '';
													var s_time_yy = '';
													var e_time_yy = '';
													if(typeof(msg_yd) != 'undefined'){
														name_yy = msg_yd.name;
														phone_yy = msg_yd.phone;
														money_yy = msg_yd.money;
														s_time_yy = msg_yd.stime;
														e_time_yy = msg_yd.etime;
													}
												str = '<dd house-id="'+room_id+'" data-booked="true" data-rentStyle="stopUse">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'
																	+'<li><a href="#/index.php?c=centralized-roomfocus&a=edit&room_focus_id='+room_id+'" class="a_Detail">查看</a></li>'
																	+'<li><a href="javascript:;" class="a_Booked" url="/index.php?c=centralized-roomfocus&a=renewal">预定</a></li>'
																	+'<li><a href="javascript:;" class="a_OutBooked" url="/index.php?c=centralized-roomfocus&a=abolishrenewal">退定</a></li>'
																	+'<li><a class="a_StartUse" url="/index.php?c=centralized-roomfocus&amp;a=recover" href="javascript:;">启用</a></li>'
																	+'<li><a href="javascript:;" class="a_Rental">出租</a></li>'
																+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="icon_Style ifont"><span class="stop">&#xe62e;</span></span>'
														+'<div class="rented_Detail tc_Detail">'
															+'<ol>'
																+'<li><span class="name">预定人姓名：</span><span class="text">'+name_yy+'</span></li>'
																+'<li><span class="name">预定人电话：</span><span class="text">'+phone_yy+'</span></li>'
																+'<li><span class="name">预定金额：</span><span class="text">'+money_yy+'元</span></li>'
																+'<li><span class="name">预留时间：</span><span class="text">'+s_time_yy+'至'+e_time_yy+'</span></li>'
																+'<!--[if ie 6]>'
																+'<div class="clear"></div>'
																+'<![endif]-->' 
															+'</ol>'
															+'<ol>'
																+'<li><span class="name">停用原因：</span><span class="text">'+reason_stop+'</span></li>'
																+'<li class="bzText"><span class="name">停用说明：</span><span class="text">'+remark_stop+'</span></li>'
																+'<li><span class="name">起始日期：</span><span class="text">'+stime_stop+'</span></li>'
																+'<li><span class="name">结束日期：</span><span class="text">'+etime_stop+'</span></li>'
															+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>';
											}else{
												str = '<dd house-id="'+room_id+'" data-rentStyle="stopUse">'
													  +'<span class="romm_NUM fl">'+room_num+'</span>'
													  +'<!--[if ie 6]>'
													  +'<div style="height: 0; overflow: hidden;"></div>'
													  +'<![endif]-->' 
													  +'<div class="jtBox"><span class="jt"></span>'
														  +'<div class="detail_Choices">'
																+'<ul>'
																	+'<li><a href="#/index.php?c=centralized-roomfocus&a=edit&room_focus_id='+room_id+'" class="a_Detail">查看</a></li>'
																	+'<li><a href="javascript:;" class="a_Booked" url="/index.php?c=centralized-roomfocus&a=renewal">预定</a></li>'
																	+'<li><a class="a_StartUse" url="/index.php?c=centralized-roomfocus&amp;a=recover" href="javascript:;">启用</a></li>'
																	+'<li><a href="javascript:;" class="a_Rental">出租</a></li>'
																+'</ul>'
															+'</div>'
														+'</div>'
														+'<span class="icon_Style ifont"><span class="stop">&#xe62e;</span></span>'
														+'<div class="rented_Detail tc_Detail">'
															+'<ol>'
																+'<li><span class="name">停用原因：</span><span class="text">'+reason_stop+'</span></li>'
																+'<li class="bzText"><span class="name">停用说明：</span><span class="text">'+remark_stop+'</span></li>'
																+'<li><span class="name">起始日期：</span><span class="text">'+stime_stop+'</span></li>'
																+'<li><span class="name">结束日期：</span><span class="text">'+etime_stop+'</span></li>'
															+'</ol>'
														+'</div>'
														+'<span class="checkBox">'
															+'<label><span class="gou ifont ifont1">&#xe60c;</span></label>'
															+'<input type="checkbox"/>'
														+'</span>'
													+'</dd>';
											}
											obj.append(str);
							}
						}
					}
					$(".centralized_Ind_D > .b > ul > li",$$).each(function(){
						var floornum = $(this).find(".floor_Num").text();
						if(floornum == n) showsame = true;
						else{
							showsame = false;
						}
					});
					if(showsame == false){
						var str = '<li>'
									+'<span class="floorNum fl"><span class="checkBoxAll"><span class="gou ifont ifont1">&#xe60c;</span></span><span class="floor_Num">'+n+'</span> 楼<p>共<span class="num_Rooms"></span>间</p></span>'
									+'<!--[if ie 6]>'
									+'<div style="height: 0; overflow: hidden;"></div>'
									+'<![endif]-->' 
									+'<dl class="fl">'
									+'</dl>'
									+'</li>';
					    $(".centralized_Ind_D > .b > ul",$$).append(str);
					}
					everhouses();
					that.others();
					//设置房态楼层列表、房间列表层级
					that.num_centralized_Ind_DLi_Z();
					that.rentStyleChoose();
					sss = getFloorTop();
					//删除状态下勾选复选框
					that.delete_CheckBox();
					//删除状态下整层楼房间全选
					that.delete_CheckBoxAll();
					//状态切换事件绑定
					that.eventbind();
				}
		}
		var that = centralized_IndJs;
		that.others();
		//设置房态楼层列表、房间列表层级
		that.num_centralized_Ind_DLi_Z();
		//窗体更改重新计算房态层高度 
		if(sys.ie && sys.ie < 8){  
			window.onresize= debounce(that.h_centralized_Ind_D_b, 300);
		}else{
			$(window).resize(that.h_centralized_Ind_D_b);	
		}
		that.h_centralized_Ind_D_b();
		that.rentStyleChoose();
		//获取楼层距离楼层列表层顶部位移
		var sss = getFloorTop();
		//快速滑动到指定楼层
		that.getFloor(sss);
		$(".centralized_Ind_D",$$).find(".a_2").find("li").click(function(){
			sss = getFloorTop();
			$(this).addClass("current").siblings().removeClass("current");
			var current_Floor = getFloorNumFirst();
			that.getFloorNum(current_Floor,sss);
		});
		//房态层滑动事件
		that.centralized_Ind_D_b(sss);
		//进入删除状态并删除
		that.deletestyle(sss);
		//取消删除状态
		that.deletecancel();
		//删除状态下勾选复选框
		that.delete_CheckBox();
		//删除状态下整层楼房间全选
		that.delete_CheckBoxAll();
		//状态切换事件绑定
		that.eventbind();
	}
	
	exports.inite = function(__html__){
		auto(__html__);
	}
});