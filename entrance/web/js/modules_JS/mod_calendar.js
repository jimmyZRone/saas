define(function(require,exports,module){ 
	 var $ = require("jquery"),
		 
	 	 loading=require("loading"),
	 	 ajax=require("Ajax"),ajaxLoading;
	 	 calendar_Ind = require("calendar_Ind"),
	 	 calendar_auto = require("calendar"),
	 	 dialog = require("dialog"); //弹窗插件
	 	 require("selectByM")($);
	/*
	*@func:日程管理页面方法初始化
	*@date 2015-3-20
	*/
	exports.iniPageFun=function(){
		calendar_Ind.inite($("#show_Calendar"));
		var  that=this;
		that.iniDateSwitch();
		that.bindEvent();
		that.renderBoxH();
//		that.iniDialog();
		that.gotoDayEvent();//跳转到具体某天的代办事项去
		that.bindAddRemind();
	}
	/*
	*@func:初始化对话框
	* @desc :type - 1-确认 取消 带数据操作 2-错误提示弹窗
	*@date 2015-3-20
	*/
	exports.iniDialog=function(type,obj){
		var  d,that=this;
		if(type==1){
			d = dialog({
				title: '消息',
				content: '确认删除',
				okValue: '确认', 
				ok: function () {
					that.iniDialog(2,"");
				},
				cancelValue: '取消',
				cancel: function () {}
			});
		}else{
			d = dialog({
				title: '错误提示',
				content: obj,
				cancelValue: '确定',
				cancel: function () {}
			});
		}
		d.showModal();
	}
	/*
	*@func:顶部日历切换事件绑定
	*@date 2015-3-20
	*/
	exports.bindEvent=function(){
		var  that=this;
		$(".eventTriger").click(function(){
			var cur=$(this),
				index=cur.attr("data-index"),
				type=cur.attr("tab-type");
				if(!cur.hasClass("current_tab")){
					if(cur.hasClass("btn-b")){
						$(".current_tab").removeClass("current_tab");
						$(".cld-col").addClass("current_tab");
						that.iniTabSwitch(2,"calendar",cur);
					}else{
						cur.parent().find(".current_tab").removeClass("current_tab");
						cur.addClass("current_tab");
						that.iniTabSwitch(index,type,cur);
					}
				}else{
					if(cur.hasClass("btn-b")){
						$(".current_tab").removeClass("current_tab");
						$(".cld-col").addClass("current_tab");
						that.iniTabSwitch(2,"calendar",cur);
					}
				}
		});
	}
	/*
	*@func:查看指定日期日历显示
	*@date 2015-3-20
	*/
	exports.iniDateSwitch=function(){
		$(".txt").click(function(){
			var cur=$(this),par=cur.parent();
			if(!par.hasClass("active")){
				$(".active").removeClass("active");
				par.addClass("active");
			}else{
				par.removeClass("active");
			}
		});
		$(document).bind("click",function(e){
			if($(e.target).is($(".txt"))) return;
			else{
				if($(".zone-sel").hasClass("active")){
					$(".zone-sel").removeClass("active")
				}
			}
		});
	}
	
	/*
	*@func:代办事项和备忘录高度渲染
	*@date 2015-3-20
	*/
	exports.renderBoxH=function(){
	 var sh=window.innerHeight ? window.innerHeight:document.documentElement.clientHeight;
		 sh=sh-265;
      $(".cm-list .tlist-list").css({
      	"height":sh-10-61+"px"
      });
//     var resizeTimer = null;
//		$(window).unbind().bind('resize', function () {
//	        if (resizeTimer) {
//	            clearTimeout(resizeTimer)
//	        }
//	        resizeTimer = setTimeout(function(){
//	           $(".main").removeAttr("style");
//				calendar_Ind.renderClaenBox();
//				exports.renderBoxH();
//	        }, 400);
//		    }
//		);
	}
	/*
	 * @func 加载模板|错误提示模板定义
	 */
	var src=$(".layout-loading-zone").find("img").attr("src"),TIMEOUT=[],
			loadingTemp='<li class="loading-status isLoading"><img src="'+src+'" class="loading-img"/></li>',
			errorTemp='<li class="loading-status error-tip"><p>今天没有任务噢~</p><p>休息一下咯~<i class="ifont">&#xe604;</i></p></li>';
	/*
	*@func:切换日历/备忘录
	* @param: i 需要展示的容器的索引 type-获取数据类型 备忘录还是待办事项
	*@date 2015-3-20
	*/
	exports.iniTabSwitch=function(i,type,cur){
//		console.log(i+","+type+","+cur);
		var that=this;
		$(".tab-col").hide();
		$(".isLoading").remove();
		if(type!="calendar"){
			if($(".isLoading").length>0 && ajaxLoading && $(cur).hasClass("current_tab")){
//				$(cur).removeClass("current_tab");
				return;
			} 
			var isloadData=$(".current_tab").attr("iscached");
			//还没请求过数据
			if(isloadData==0){
				if(ajaxLoading){
					ajaxLoading.abort();
				}
				if(type=="remind"){
					loading.pageLoading("#rlist ul",loadingTemp);
					that.getRemindList();//请求备忘录数据
				}else{
					that.gotoModTodo();//请求代办事项
				}
				clearTimeout(TIMEOUT);
				TIMEOUT=setTimeout(function(){
					if($(".isLoading").length>0 && ajaxLoading){
						ajaxLoading.abort();
						that.iniDialog(2,"请求超时");
						$(".current_tab").removeClass("current_tab");
						$(".isLoading").remove();
						if(type=="remind"){
							loading.pageLoading("#rlist ul",errorTemp);
						}else{
							loading.pageLoading("#tlist ul",errorTemp);
						}
					}
				},30000);
			}else{
				if(type == "todo"){
					that.gotoModTodo();//请求代办事项
				}
			}
			$(".calendar").find(".tab-col[tab-index="+i+"]").show();
		}else{
			if(ajaxLoading) ajaxLoading.abort();
			if($(".isLoading").length>0) $(".isLoading").remove();
//			$("#currentM").trigger("click");
			$(".calendar").find(".tab-col[tab-index="+i+"]").show();
		}
	}

	/*
	*@func: 待办事项模块展示判断
	* @param type--为空：展示全部 其他展示指定天数的代办事项
	*@date 2015-3-20
	*/
	exports.gotoModTodo=function(type){
		var  that=this,cur=type;
		if(type){
			$(".tab-col").hide();
			$(".c-needs").show();
			$("#todo-all-ops").addClass("none");
			$("#todo-day-ops").removeClass("none");
			var yy=$.trim($("#curr-year-txt").text()),
			mm=$.trim($("#curr-mon-txt").text()),
			dd=$.trim(cur.find(".per-day").text());
			that.handlerModTodo(yy,mm,dd);
		}else{
			$("#todo-all-ops").removeClass("none");
			$("#todo-day-ops").addClass("none");
			that.getTodoList();
		}
	}
	
	/*
	*@func: 备忘录数据获取
	*@date 2015-3-20
	*/
	exports.getRemindList=function(){
		var url = $(".rmd-col").attr("listURL");
		var setting={
			type:"POST",
			url:url,
			data:""
		},that=this;
		ajaxLoading=ajax.doAjax(setting.type,setting.url,setting.data,function(json){
				$(".isLoading").remove();
//			setTimeout(function(){
//				$(".isLoading").remove();
//				loading.pageLoading("#tlist ul",errorTemp);
//				$(".todo-col").removeClass("current_tab");
//			},2000);
			if(json.status==1){
				$(".rmd-col").attr("isCached",1);
				filldata_bwllist(json.datawgq,json.datagq,$("#rlist ul"));
			}else{
				$(".rmd-col").attr("isCached",0);
				loading.pageLoading("#rlist ul",errorTemp);
				$(".rmd-col").removeClass("current_tab");
			}
		});
	}
	//备忘录列表数据填充
	function filldata_bwllist(datawgq,datagq,obj){
		obj.html("");
		var str = "";
		for(var n in datawgq){
			 str += '<li memo_id = "'+datawgq[n].memo_id+'">'
							+'<span class="cml-hour">'+datawgq[n].memo_moment+'</span>'
							+'<span class="cml-con">'+datawgq[n].notice_content+'</span>'
							+'<div class="cml-r">'
							+'<span class="cml-year">'+datawgq[n].memo_time+'</span>'
							+'<span class="cml-del"><a href="javascript:;" class="ifont">&#xe675;</a></span>'
							+'</div>'
							+'</li>';
		}
		obj.append(str);
		var strs = "";
		for(var n in datagq){
			 strs += '<li memo_id = "'+datagq[n].memo_id+'">'
							+'<span class="cml-hour">不提醒</span>'
							+'<span class="cml-con">'+datagq[n].notice_content+'</span>'
							+'<div class="cml-r">'
							+'<span class="cml-year">'+datagq[n].memo_time+'</span>'
							+'<span class="cml-del"><a href="javascript:;" class="ifont">&#xe675;</a></span>'
							+'</div>'
							+'</li>';
		}
		obj.append(strs);
		//添加事件
		bindevents_bwllist();
	}
	//备忘录事件
	function bindevents_bwllist(){
		var infourl = $(".add-Memorandum").attr("infourl");
		var delurl = $(".add-Memorandum").attr("delurl");
		//备忘录查看修改
		$(".cml-con").off("click").on("click",function(){
			var that = $(this);
			var memo_id = $(this).parents("li").attr("memo_id");
			var data = {
				memo_id : memo_id
			};
			var type = "get";
			$(this).addClass("current");
			ajax.doAjax(type,infourl,data,function(json){
				var slectstr = $(".slt-bwl-autoss").html();
				if(json.status == 1){
					var memo_id = json.data.memo_id;
					var memo_moment = json.data.memo_moment;
					var memo_time = json.data.memo_time;
					var notice_content = json.data.notice_content;
					var notice_time = json.data.notice_time;
					var d = dialog({
						title: '<i class="ifont ifont-yytz" memo_id = "'+memo_id+'">&#xf0077;</i><span>查看/修改备忘录</span>',
						content: '<div class="dg-option"><i class="dg-star">*</i><span class="dg-date">日期:</span><input type="text" class="ipt auto-bwl-time" placeholder="2015-5-28"  value="'+memo_time+" "+memo_moment+'"/>'
								+ slectstr+'</div>'
								+ '<div class="dg-title"><i class="dg-star">*</i><span>事件:</span></div>'
								+ '<textarea class="dg-con">'+notice_content+'</textarea>',
						okValue: '保 存', drag: true,
						ok: function () {
							submitBwl(infourl,d,that);
							return false;
						},
						cancelValue: '取消',
						cancel: function () {
							_isClicked=false;
						}
					});
					d.showModal();
					$(".auto-bwl-time").click(function(){
						calendar_auto.inite({dateFmt:'yyyy-MM-dd  HH:mm:ss'});
					});
					$(".slt-bwl-autos",".ui-dialog-content").each(function(){
						$(this).find(".selectByMO").hide();
						$(this).selectObjM()
						$(this).find("li").each(function(){
							var that_auto = $(".slt-bwl-autos");
							if($(this).attr("selectval") == notice_time){
								$(this).addClass("selectedLi");
								that_auto.find(".selectByMT").val($(this).text()).attr("selectval",notice_time);
								return false;
							}
						});
					});
				}else{
					var d = dialog({
						title: '提示信息',
						content: json.data,
						okValue: '确 定', drag: true,
						ok: function () {
							d.close();
						}
					});
					d.showModal();
				}
			});
		});
		//备忘录删除
		$(".cml-del").off("click").on("click",function(){
			var li_obj = $(this).parents("li");
			var d = dialog({
					title: '<i class="ifont ifont-yytz">&#xe675;</i><span>删除备忘录</span>',
					content: '确定删除该条备忘录？',
					okValue: '确 定', drag: true,
					ok: function () {
						var memo_id = li_obj.attr("memo_id");
						var type = "get";
						var data ={
							memo_id : memo_id
						};
						ajax.doAjax(type,delurl,data,function(json){
							if(json.status == 1){
								var dd = dialog({
									title:'提示信息',
									content:'删除成功！',
									okValue:'确 定',drag:true,
									ok:function(){
										li_obj.remove();
										dd.close();
									}
								});
								dd.showModal();
							}else{
								var dd = dialog({
									title:'提示信息',
									content:json.message,
									okValue:'确 定',drag:true,
									ok:function(){
										dd.close();
									}
								});
								dd.showModal();
							}
						});
						d.close();
					},
					cancelValue: '取消',
					cancel: function () {

					}
				});
				d.showModal();
		});
	}
	/*
	*@func: 待办事项数据获取
	*@date 2015-3-20
	*/
	exports.getTodoList=function(yy,mm,dd){
		if(ajaxLoading && $(".isLoading").length>0){
			 ajaxLoading.abort();
		}
		loading.pageLoading("#tlist ul.tlist-list",loadingTemp);
		var url = $(".calendar .c-head h1").attr("url");
		var data ={
			month_day : yy+"-"+mm+"-"+dd
		};
		if(typeof(yy) == "undefined" || typeof(mm) == "undefined" || typeof(dd) == "undefined"){
			data = {
				month_day : ""
			};
		}
		var setting={
			type:"POST",
			url:url,
			data:data
		},that=this;
		ajaxLoading=ajax.doAjax(setting.type,setting.url,setting.data,function(json){
			$(".isLoading").remove();
//			setTimeout(function(){
//				$(".isLoading").remove();
//				loading.pageLoading("#tlist ul",errorTemp);
//				$(".todo-col").removeClass("current_tab");
//			},2000);
			if(json.status==1){
				$("#tlist .tlist-ctr").show().find("li:first").addClass("current").siblings().removeClass("current");
				$(".todo-col").attr("isCached",1);
				$("#todo-all-ops") .find(".cmh-l span").find("i").text(json.data.length);
//				console.log(json);
				filldaydate(json.data);
			}else{
				$(".todo-col").attr("isCached",0);
				loading.pageLoading("#tlist ul.tlist-list",errorTemp);
				$(".todo-col").removeClass("current_tab");
				$("#tlist .tlist-ctr").hide();
			}
		});
	}
	function filldaydate(data){
		var obj = $("#tlist ul.tlist-list");
		obj.html("");
		var str = '<li style="height:20px;border-bottom:none"></li>';
		for(var n in data){
			data[n].content = data[n].content.replace(/,/, '，').replace(/[\.|。]$/, '') + '。';
			if($.trim(data[n].title)=="到期"){
				str += '<li><span class="cn-red ctype">到期</span><span class="cml-con"><a href="#'+data[n].url+'" title="'+data[n].content+'">'+data[n].content+'</a></span></li>';
			}else if($.trim(data[n].title)=="收租"){
				str += '<li><span class="cn-blue ctype">收租</span><span class="cml-con"><a href="#'+data[n].url+'" title="'+data[n].content+'">'+data[n].content+'</a></span></li>';
			}else if($.trim(data[n].title)=="恢复"){
				str += '<li><span class="cn-greens ctype">恢复</span><span class="cml-con"><a href="#'+data[n].url+'" title="'+data[n].content+'">'+data[n].content+'</a></span></li>';
			}else if($.trim(data[n].title)=="备忘录"){
				str += '<li memo_id = '+data[n].entity_id+'><span class="cn-green ctype">备忘</span><span class="cml-con"><a href="javascript:;" url="'+data[n].url+'" title="'+data[n].content+'">'+data[n].deal_time+"应处理"+data[n].content+'</a></span></li>';
			}else{
				str += '<li><span class="cn-yellow ctype">交租</span><span class="cml-con"><a href="#'+data[n].url+'" title="'+data[n].content+'">'+data[n].content+'</a></span></li>';
			}
		}
		obj.append(str);
		$("#tlist ul li a").off("click").on("click",function(){
			$(this).parent().addClass("current");
			if(typeof $(this).attr("url") != "undefined"){
				var infourl = $(this).attr("url");
				var that = $(this);
				var memo_id = $(this).parents("li").attr("memo_id");
				var data = {
					memo_id : memo_id
				};
				var type = "get";
				ajax.doAjax(type,infourl,data,function(json){
					var slectstr = $(".slt-bwl-autoss").html();
					if(json.status == 1){
						var memo_id = json.data.memo_id;
						var memo_moment = json.data.memo_moment;
						var memo_time = json.data.memo_time;
						var notice_content = json.data.notice_content;
						var notice_time = json.data.notice_time;
						var d = dialog({
							title: '<i class="ifont ifont-yytz" memo_id = "'+memo_id+'">&#xf0077;</i><span>查看/修改备忘录</span>',
							content: '<div class="dg-option"><i class="dg-star">*</i><span class="dg-date">日期:</span><input type="text" class="ipt auto-bwl-time" placeholder="2015-5-28"  value="'+memo_time+" "+memo_moment+'"/>'
									+ slectstr+'</div>'
									+ '<div class="dg-title"><i class="dg-star">*</i><span>事件:</span></div>'
									+ '<textarea class="dg-con">'+notice_content+'</textarea>',
							okValue: '保 存', drag: true,
							ok: function () {
								submitBwl(infourl,d,that);
								return false;
							},
							cancelValue: '取消',
							cancel: function () {
								_isClicked=false;
							}
						});
						d.showModal();
						$(".auto-bwl-time").click(function(){
							calendar_auto.inite({dateFmt:'yyyy-MM-dd  HH:mm:ss'});
						});
						$(".slt-bwl-autos",".ui-dialog-content").each(function(){
							$(this).find(".selectByMO").hide();
							$(this).selectObjM()
							$(this).find("li").each(function(){
								var that_auto = $(".slt-bwl-autos");
								if($(this).attr("selectval") == notice_time){
									$(this).addClass("selectedLi");
									that_auto.find(".selectByMT").val($(this).text()).attr("selectval",notice_time);
									return false;
								}
							});
						});
					}
				});
			}
		});
		$("#tlist .tlist-ctr a").off("click").on("click",function(){
			$(this).parent().addClass("current").siblings().removeClass("current");
			var ctr_text = $(this).text();
			switch(ctr_text){
			 	case "收租":
			 					  $("#tlist .tlist-list li:gt(0)").each(function(){
			 					  	 if($(this).find(".cn-blue").size() == 0){
			 					  	 	$(this).hide();
			 					  	 }else{
			 					  	 	$(this).show();
			 					  	 }
			 					  });
			 					  break;
			 	case "交租":
			 					$("#tlist .tlist-list li:gt(0)").each(function(){
			 					  	 if($(this).find(".cn-yellow").size() == 0){
			 					  	 	$(this).hide();
			 					  	 }else{
			 					  	 	$(this).show();
			 					  	 }
			 					  });
			 					 break;
			 	case "到期":
			 					 $("#tlist .tlist-list li:gt(0)").each(function(){
			 					  	 if($(this).find(".cn-red").size() == 0){
			 					  	 	$(this).hide();
			 					  	 }else{
			 					  	 	$(this).show();
			 					  	 }
			 					  });
			 					 break;
			 	case "备忘":
			 					 $("#tlist .tlist-list li:gt(0)").each(function(){
			 					  	 if($(this).find(".cn-green").size() == 0){
			 					  	 	$(this).hide();
			 					  	 }else{
			 					  	 	$(this).show();
			 					  	 }
			 					  });
			 					 break;
			 	default :
			 				$("#tlist .tlist-list li").show();
			}
		});
	}
	/*
	*@func: 跳转代办事项模块进行处理
	*@date 2015-3-20
	*/
	exports.gotoDayEvent=function(){
		var that=this;
		$(".event-day-marker").live("click",function(){
			var cur=$(this);
			that.gotoModTodo(cur);
//			$(".tab-col").hide();
//			that.getTodoList();
//			setTimeout(function(){
//				if(!$("#calendar_Ind_box").hasClass("none") && ajaxLoading){
//					that.iniDialog(2,"请求超时");
//					$(".current_tab").removeClass("current_tab");
//					ajaxLoading.abort();
//				}
//			},30000);
		});
	}
	
	//默认对应月份天数
	var solarMonth=new Array(31,28,31,30,31,30,31,31,30,31,30,31),
		tempDay,tempMon,curentYear,maxDay,tempYear;
		
	/*
	*@func: 跳转代办事项处理
	*@date 2015-3-25
	*/
	exports.handlerModTodo=function(yy,mm,dd){
		var that=this;
		$("#todo-mon").text(mm);
		$("#todo-day").text(dd);
		maxDay=that.solarDays(yy,mm-1);//当前月份天数
		that.todoDateSwitch(yy,mm,dd);
		that.getTodoList(yy,mm,dd);
	}
	/*
	*@func: 代办事项日期切换
	* @desc:默认展示当前一年的代办事项
	*@date 2015-3-25
	*/
	exports.todoDateSwitch=function(yy,mm,dd){
		var  that=this,
			 cMon=$.trim($("#todo-mon").text()),
			 cDay=$.trim($("#todo-day").text());
			 tempDay=cDay;
			 tempMon=cMon;
			 tempYear=yy;
		//上一天
		$("#prevDay").unbind().bind("click",function(){
			if(tempDay==1){
				//跳转至上一年
				if(tempMon==1){
					tempYear--;
			 		tempMon=12;
			 		maxDay=that.solarDays(tempYear,tempMon-1);
			 		tempDay=maxDay;
					$("#todo-mon").text(tempMon);
					$("#todo-day").text(tempDay);
				}else{
					tempMon--;	
					maxDay=that.solarDays(yy,tempMon-1);//重置当前月份天数
					$("#todo-mon").text(tempMon);
					$("#todo-day").text(maxDay);
					tempDay=maxDay;
				}
			}else{
				tempDay--;	
				$("#todo-day").text(tempDay);
			}
			that.getTodoList(tempYear,tempMon,tempDay);

		});
		//下一天
		$("#nextDay").unbind().bind("click",function(){
			if(tempDay==maxDay) {
				//跳转至下一年
				if(tempMon==12){
					tempYear++;
			 		tempMon=1;
			 		maxDay=that.solarDays(tempYear,tempMon-1);
			 		tempDay=1;
					$("#todo-mon").text(tempMon);
					$("#todo-day").text(tempDay);
				}else{
					tempMon++;
					maxDay=that.solarDays(yy,tempMon-1);//重置当前月份天数
					$("#todo-mon").text(tempMon);
					tempDay=1;
					$("#todo-day").text(tempDay);
				}
			}else{
				tempDay++;
				$("#todo-day").text(tempDay);
			}
			that.getTodoList(tempYear,tempMon,tempDay);
		});
	}

	/*
	*@func: 获取月份天数
	* @y-年份 @m-月份索引 初始值为0
	*@date 2015-3-25
	*/
	exports.solarDays=function(y,m){
		 if(m==1)
      		return(((y%4 == 0) && (y%100 != 0) || (y%400 == 0))? 29: 28)
   		else
      		return(solarMonth[m])
	}
	/*
	*@func: 添加备忘录事件绑定
	*@date 2015-3-25
	*/
	exports.bindAddRemind=function(){
	    $('.add-Memorandum').on('click', function () {
	    	var url = $(this).attr("addurl");
	    	var slectstr = $(".slt-bwl-autoss").html();
				var d = dialog({
					title: '<i class="ifont ifont-yytz">&#xf0077;</i><span>添加备忘录</span>',
					content: '<div class="dg-option"><i class="dg-star">*</i><span class="dg-date">日期:</span><input type="text" class="ipt auto-bwl-time" placeholder="2015-5-28" />'
							+ slectstr+'</div>'
							+ '<div class="dg-title"><i class="dg-star">*</i><span>事件:</span></div>'
							+ '<textarea class="dg-con"></textarea>',
					okValue: '保 存', drag: true,
					ok: function () {
						submitBwl(url,d);
						return false;
					},
					cancelValue: '取消',
					cancel: function () {
						_isClicked=false;
					}
				});
				d.showModal();
				$(".auto-bwl-time").click(function(){
					calendar_auto.inite({dateFmt:'yyyy-MM-dd  HH:mm:ss'});
				});
				$(".slt-bwl-autos").selectObjM();
		});
	}
	/*
	 * @func:提交备忘录  url提交地址   d弹窗对象  dick专为编辑设置的备忘录列表被点击对象
	 */
	var submitBwl = function(url,d,dick){
		var type = "post";
		var memo_time = $(".auto-bwl-time",".ui-dialog-content").val();  //提醒时间
		var notice_time = $("input[name='slt-bwl-auto']",".ui-dialog-content").attr("selectval");   //提醒类型
		var content  = $(".dg-con",".ui-dialog-content").val();   //提醒事件
		var memo_id = $(".ifont-yytz").attr("memo_id");
		if(typeof(memo_id) == "undefined") memo_id = "";
		var data = {
			memo_id : memo_id,
			memo_time : memo_time,
			notice_time : notice_time,
			content : content
		};
		ajax.doAjax(type,url,data,function(data){
			if(data.status == 1){
				var dd = dialog({
					title: '提示信息',
					content: '保存成功',
					okValue: '确 定', drag: true,
					ok: function () {
						if(typeof(dick) == "undefined"){
							//新增回调
							var str = '<li memo_id = "'+data.data.memo_id+'">'
							+'<span class="cml-hour">'+data.data.memo_moment+'</span>'
							+'<span class="cml-con">'+data.data.notice_content+'</span>'
							+'<div class="cml-r">'
							+'<span class="cml-year">'+data.data.memo_time+'</span>'
							+'<span class="cml-del"><a href="javascript:;" class="ifont">&#xe675;</a></span>'
							+'</div>'
							+'</li>';
							$("#rlist ul").prepend(str);
								var surl = $(".calendar .c-head h1").attr("surl");
							   var settings={
							      type:"post",
							      surl:surl
							   },sD;
						   //取总数
						   ajax.doAjax(settings.type,settings.surl,"",function(json){
						       $(".todo-col").find("span").text(json.data);
						   }); 
						}else{
							//编辑回调
							var get_memo_moment = data.data.memo_moment;
							var get_memo_time = data.data.memo_time;
							var get_notice_content = data.data.notice_content;
							dick.text(get_notice_content).siblings(".cml-hour").text(get_memo_moment).siblings(".cml-r").children(".cml-year").text(get_memo_time);
						}
						//添加事件
						bindevents_bwllist();
						dd.close();
						d.close().remove();
					}
				});
				dd.showModal();
			}else{
				var dd = dialog({
					title: '提示信息',
					content: data.message,
					okValue: '确 定', drag: true,
					ok: function () {
						dd.close();
					}
				});
				dd.showModal();
			}
		});
	
	}
	
	$(function(){
	  exports.iniPageFun();//模块方法初始化
	});
});
	