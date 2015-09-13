define(function(require,exports,module){
	var $ = require('jquery');
		require("placeholder")($);
		require("pagination");
	var navigators = require("navigatortest");  //浏览器版本检测
	var	ajax = require("Ajax");
	var	dt = require("calendar");
	
	
	
	var modelInit = function($$){
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".list",$$).placeholder();
		};
		
		//初始化日历
		$(".date",$$).click(function(){
			dt.inite();
		});
		
		/**
		 * 删除状态下全选及单选
		 * 
		 */
		var deleteCheckBox = function(){
			//全选
			$(".message_list_CheckAll",$$).off("click").on("click",function(){
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).find(".choose").show();
					$(".message_list .lst_tb .checkBox",$$).addClass("checked").children(".choose").show();
					$(".message_list .lst_tb .checkBox",$$).next().attr("checked",true);
				}else{
					$(this).find(".choose").hide();
					$(".message_list .lst_tb .checkBox",$$).removeClass("checked").children(".choose").hide();
					$(".message_list .lst_tb .checkBox",$$).next().removeAttr("checked");
				}
			});
			//单选
			$(".message_list .lst_tb .checkBox",$$).off("click").on("click",function(){
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children(".choose").show();
					$(this).next().attr("checked",true);
				}else{
					$(this).children(".choose").hide();
					$(this).next().removeAttr("checked");
				}
			});
		};
		
		/**
		 * 开启删除模式并执行删除操作
		 * 
		 */
		var chanceToDelete = function(){
			$(".message_list_Delete",$$).off("click").on("click",function(){
				$(this).siblings(".message_list_CheckAll,.message_list_OFF").show();
				$(".message_list .lst_tb .checkBox",$$).fadeIn(300);
				if($(this).hasClass("deleteStyle")){
					var url = $(this).attr("del_url");
		   			var type = "post";
		   			var uid = [];
		   			$(".lst_tb tr:gt(0)",$$).each(function(){
	   					if($(this).find("input:checked").size() > 0){
		   					var mess_id = $(this).attr("mess_id");
	   						var _eleAuto = {
		   						mess_id : mess_id
		   					};
		   					uid.push(_eleAuto);
		   				}
		   			});
		   			if(uid.length == 0){
		   				var d = dialog({
							title: '提示信息',
							content:"没有选中任何内容",
							okValue: '确定',
							ok: function () {
								d.close();
							}
						});
						d.showModal();
		   				return false;
		   			}
		   			var deletedata = {
		   				"url" : url,
		   				"type" : type,
		   				"uid" : uid
		   			};
		   			deleteauto(deletedata);
				};
				$(this).addClass("deleteStyle");
			});
		};
		var deleteauto = function(deletedata){
			var cloneStr = $(".deletemoreauto",$$).clone();
	   	    cloneStr.removeClass("none");
	   	    var deleteTptal = deletedata.uid.length;
	   	    cloneStr.find(".num_total").text(deleteTptal);
	   	    
	   	    var dd = dialog({
				title: '<i class="ifont">&#xe675;</i><span>删除消息</span>',
				content:cloneStr,
				okValue: '确定',
				ok: function () {
					dd.close();
				},
				cancelValue: '取消',
				cancel: function () {
					$(".ui-dialog-content",$$).children(".deletemoreauto").addClass("stop");
					$(".ui-dialog-content",$$).children(".deletemoreauto").find(".top1 .fl").text("删除终止");
					return false;
				}
			});
			dd.showModal();
			var objAuto =  $(".ui-dialog-content").find(".deletemoreauto"); 
			var tableauto = objAuto.find("table");
			var scrollbar = objAuto.find(".top2a");
			var scrollbalscroll = parseInt(100/deleteTptal);
			var url = deletedata.url;
			var type = deletedata.type;
			var uid = deletedata.uid;
			var num_cur = 0;  //计数器
			function autodeleteus(){
				if(objAuto.hasClass("stop")) return false;
				var objsauto = null;
				$(".lst_tb tr:gt(0)",$$).each(function(){
	   				if($(this).find("input:checked").size() > 0){
	   					objsauto = $(this);
	   					num_cur++
	   					return false;
	   				}
	   			 });
	   			 objAuto.find(".num_cur").text(num_cur);
	   			 var trstr = '<tr><td class="zb">'+objsauto.find("td").eq(0).children("span").text()+'</td><td class="yb">正在删除</td></tr>';
	   			 tableauto.append(trstr);
	   			 if(num_cur > 5){
					tableauto.find("tr:eq("+(num_cur-5)+")").fadeOut(300);
				}
	   			 var trcur = tableauto.find("tr:last");
	   			 var mess_id = objsauto.attr("mess_id");
	   			 var uid = {
	   			 	mess_id : mess_id,
	   			 }
	   			 var data = {
					uid : uid
				};
				ajax.doAjax(type,url,data,function(data){
					if(data.status == 0){
						trcur.find(".yb").addClass("red").removeClass("blue").text(data.message);
						objsauto.find(".checkBox").removeClass("checked").children().hide();
						objsauto.find(".checkBox").next("input").removeAttr("checked");
						if(num_cur == deleteTptal){
							scrollbar.animate({"left":0},300);
							return false;
						}
						scrollbar.animate({"left":-(100-scrollbalscroll*num_cur)+"%"},300);
						var timer = null;
						clearTimeout(timer);
						timer = setTimeout(autodeleteus,200);
					}else{
						trcur.find(".yb").addClass("blue").removeClass("red").text(data.message);
						objsauto.remove();
						if($(".lst_tb",$$).find("tr").length <= 1){
							//刷新当前标签
							var tag = WindowTag.getCurrentTag();
							var url = tag.find('>a:first').attr('url');
							WindowTag.loadTag(url,'get',function(){});
						}
						if(num_cur == deleteTptal){
							scrollbar.animate({"left":0},300);
							objAuto.find(".top1 .fl").text("删除完成");
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
		};
		
		/**
		 * 通过删除按钮删除消息
		 * 
		 */
		var deleteByLineBtn = function(){
			$(".spr-del-trig",$$).off("click").on("click",function(){
				var that = $(this);
				var mess_id = that.parents("tr").attr("mess_id");
				var dd = dialog({
					title: '<i class="ifont">&#xe77d;</i><span>删除流水</span>',
					content: '删除的信息将无法得到恢复，确定删除？',
					okValue: '确 定', drag: true,
					ok: function () {
						var type = "post";
						var url = that.attr('del_url');
						var data = {
							"uid": mess_id 
						};
						ajax.doAjax(type,url,data,function(data){
							if(data.status == 1){
								that.parents("tr").remove();
								var d = dialog({
									title: '提示信息',
									content: '删除成功',
									okValue: '确定',
									ok: function () {
										d.close();
									}
								});
								d.showModal();
							}else{
								var d = dialog({
									title: '提示信息',
									content:data.message,
									okValue: '确定',
									ok: function () {
										d.close();
									}
								});
								d.showModal();
							}
						});
					},
					cancelValue: '取消',
					cancel: function () {
	
					}
				});
				dd.showModal();
			});
		};
		
		/**
		 * 取消删除状态
		 * 
		 */
		var chanceToCancel = function(){
			$(".message_list_OFF",$$).off("click").on("click",function(){
				$(this).hide().siblings(".message_list_CheckAll").hide();
				$(".message_list .lst_tb .checkBox",$$).fadeOut(300);
			});
		};
		
		/**
		 * 条件筛选
		 * 2014-05-18
		 * 
		 */
		var selectConditionEvt = function(){
			$('.list-col-cdn',$$).click(function(){
				var cur = $(this);
				if(!cur.hasClass('current')){
					cur.parent().children('.current').removeClass('current');
					cur.addClass('current');
					var type = cur.attr("data-type"),
						_date = new Date(),
						_dtime,
						_speriod,
						_mon;
					var  curYear = _date.getFullYear(),
						 curMonth = (_date.getMonth()+1) < 10 ?"0"+(_date.getMonth()+1):(_date.getMonth()+1),
						 curDay = _date.getDate() < 10 ? "0" + _date.getDate():_date.getDate();
					_dtime = curYear+"-"+curMonth+"-"+curDay;//当前日期
					switch(type){
						case "1":
							_speriod=0;
							_mon=0;
							break;
						case "2":
							_speriod=solarDays(curYear,curMonth-2);//前一个月天数
							_mon=1;
							break;
						case "3":
							_speriod = solarDays(curYear,curMonth-2) + solarDays(curYear,curMonth-3) + solarDays(curYear,curMonth-4);//前三个月天数
							_mon=3;	
							break;
						default:
							break;
					}
					var tempYear,teamMon,temDay,totalDays,
						finNalYear,finNalMon,finNalDay;
					temDay=parseInt(curDay)+parseInt(_speriod);
					totalDays=solarDays(curYear,curMonth-1);
					
					teamMon = parseInt(curMonth) - parseInt(_mon);
					tempYear = curYear;

					if(teamMon <= 0){
						teamMon = 12 + teamMon;
						tempYear = parseInt(curYear) - 1;
					}
					endTime=tempYear+"-"+(teamMon<10?"0"+teamMon:teamMon)+"-"+curDay;
					
//					var timeStamp=DateToUnix(_dtime)-(_speriod*24*60*60),
//						endTime=UnixToDate(timeStamp);
						
					cur.parent().find(".deal-time").val(endTime);
					cur.parent().find(".deal-times").val(_dtime);
					//调用搜索
					pageByNum();
				}else{
					cur.removeClass('current');
					cur.parent().find(".deal-time").val('');
					cur.parent().find(".deal-times").val('');
					//调用搜索
					pageByNum();
				};
			});
		};
		selectConditionEvt();
		
		/**
		 * 分页
		 * 2014-05-18
		 * 
		 */
		var pageByNum = function(page){
			if(!!!page) page=0;
			page += 1;
			var send_start_time = $("input[name = 'send_start_time']",$$).val(),
				send_end_time = $("input[name = 'send_end_time']",$$).val();
			var type = "GET";
			var data = "&send_start_time="+send_start_time+"&send_end_time="+send_end_time+"&page="+page;
			var url = $(".p-btn",$$).attr("urls") + data;
			ajax.doAjax(type,url,"",function(msg){
				if(msg.page_info.count == 0){
					$(".jzf-pagination",$$).hide();
					$(".landlord_List",$$).removeClass("none");
					$(".lst_tb",$$).find("tr").not("tr:eq(0)").remove();
				}else{
					var len = msg.page_info.count;  //总共条数
					var size = msg.page_info.size;  //每页条数
					if(msg.status == 1){
		 				ajax.iniPagination(len,"#message-page",pageByNum,size,page - 1);
					
		             	$(".jzf-pagination",$$).show();
						$(".lst_tb",$$).find("tr").not("tr:eq(0)").remove();
						$(".lst_tb",$$).append(msg.data);
						
						//删除状态下的全选及单选
						deleteCheckBox();
						
						//调用开启删除界面并实现删除操作
						chanceToDelete();
						
						//调用点击单行删除按钮删除
						deleteByLineBtn();
						
						//调用取消删除
						chanceToCancel();
						
						//翻页时恢复非删除状态
						$(".list-fun .message_list_OFF",$$).click(function(){
							$(".message_list_Delete",$$).removeClass("deleteStyle");
							$(".list-fun a",$$).hide();
							$(".list-fun .message_list_Delete",$$).show();
							$(".lst_tb .checkBox",$$).removeClass("checked");
							$(".lst_tb .checkBox .choose",$$).hide().parent('.chechBox').next('input').removeAttr('checked');
							$(".lst_tb .checkBox",$$).hide();
						}).trigger("click");
					}else{
		             	 $(".jzf-pagination",$$).hide();
					}
				};
			});
		};
		pageByNum();
		
		//页码跳转
		$(".p-btn",$$).off("click").on("click",function(){
			var p = parseInt($(".page",$$).val()) - 1;
			var num =  $(".pagination",$$).children().length - 2;
			var cpage = $(".pagination",$$).children().eq(num).text();
			if(p + 1 > cpage){
				var d = dialog({
					title: '提示信息',
					content: '页码无效',
					okValue: '确定',
					ok: function(){
						d.close();
					}
				});
				d.showModal();
			}else{
				pageByNum(p);
			}
			
		});
		
		/*
		 *@func 返回对应月份天数
		 * @param y 当期年份 m 月份索引 起始为0
		 * */
		var solarDays = function(y,m){
			var solarMonth = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
			 if(m==1)
	      		return(((y%4 == 0) && (y%100 != 0) || (y%400 == 0))? 29: 28)
	   		else
	      		return(solarMonth[m])
		};
		
        /**              
         * 日期 转换为 Unix时间戳
         * @param <string> 2014-01-01 20:20:20  日期格式              
         * @return <int>        unix时间戳(秒)              
         */
		var DateToUnix = function(date){
			 var f = date.split(' ', 2);
            var d = (f[0] ? f[0] : '').split('-', 3);
            var t = (f[1] ? f[1] : '').split(':', 3);
            return (new Date(
                    parseInt(d[0], 10) || null,
                    (parseInt(d[1], 10) || 1) - 1,
                    parseInt(d[2], 10) || null,
                    parseInt(t[0], 10) || null,
                    parseInt(t[1], 10) || null,
                    parseInt(t[2], 10) || null
                    )).getTime() / 1000;
		};
		
		 /**              
         * 时间戳转换日期              
         * @param <int> unixTime    待时间戳(秒)              
         * @param <bool> isFull    返回完整时间(Y-m-d 或者 Y-m-d H:i:s)              
         * @param <int>  timeZone   时区              
         */
		var UnixToDate = function(unixTime, isFull, timeZone) {
			if (typeof (timeZone) == 'number')
                {
                    unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
                }
                var time = new Date(unixTime * 1000);
                var ymdhis = "";
                ymdhis += time.getUTCFullYear() + "-";
                ymdhis += (time.getUTCMonth()+1) < 10 ? "0"+(time.getUTCMonth()+1) + "-" : (time.getUTCMonth()+1) + "-";
                ymdhis += (time.getUTCDate()+1) < 10 ? "0"+(time.getUTCDate()+1) : (time.getUTCDate()+1);
                if (isFull === true)
                {
                    ymdhis += " " + time.getUTCHours() + ":";
                    ymdhis += time.getUTCMinutes() + ":";
                    ymdhis += time.getUTCSeconds();
                }
                return ymdhis;
		};
		
		
	};
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);
	};
	
	
	
	
});