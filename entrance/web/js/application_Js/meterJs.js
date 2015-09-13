define(function(require,exports){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		ajax=require("Ajax");
		require("pagination");
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var calendar = require("calendar");
	var urlHelper = require("url");
	var hash = require('child_data_hash');
	var auto = function($$){
		var meter = {
			inites : function(){
				var that = this;
				//调用下拉框JS
				$(".selectByM",$$).each(function(i,o){
					if(i == 0){
						$(this).selectObjM(1,function(val, inp){
							var val = inp.attr("selectval");
							var prev_val = inp.attr("prev-data");
							if(!!!prev_val) prev_val = "";
							if(val == prev_val) return false;
							var money = inp.siblings(".selectByMO").find("li.selectedLi").attr("money");
							$(".fee-price-auto",$$).text(money);
							gethighdata();
						});	
						function gethighdata(){
							var url = $(".meter-val",$$).attr("url");
							
							var room_id = urlHelper.get('room_id');
							var house_type = urlHelper.get('house_type');
							var house_id = urlHelper.get('house_id'); 
							var fee_type_id = $(".selectByMO:first li.selectedLi",$$).attr("selectval");
							if(typeof fee_type_id == "undefined") return false;
							var data = {
								room_id : room_id,
								house_type : house_type,
								house_id : house_id,
								fee_type_id : fee_type_id
							}
							ajax.doAjax("get",url,data,function(data){
								if(data.status == 1){
									$(".meter-val",$$).attr("high-data",data.data);
								}else{
									$(".meter-val",$$).attr("high-data",0);
								}
							});
						};
						gethighdata();
					}else{
						that.newpagecovers($(this));
						$(this).selectObjM(1,function(val,inp){
							var val = inp.attr("selectval");
							var prev_val = inp.attr("prev-data");
							if(!!!prev_val) prev_val = "";
							if(val == prev_val) return false;
							inp.attr("prev-data",val);
							var url = inp.attr("url");
							var type = "get";
							var room_id = '';
							var house_type='';
							var house_id = '';
							
							room_id = urlHelper.get('room_id');
							house_type = urlHelper.get('house_type');
							house_id = urlHelper.get('house_id');
							if(house_type == "") house_type =2;
							var obj = {
								url : url,
								type : type,
								room_id : room_id,
								house_id : house_id,
								view : val,
								house_type:house_type,
							};
							that.list_CallBack(0,obj);
						});
					}
				});
				//针对IE10以下的input提示语兼容
				if(sys.ie && sys.ie < 10){
					$(".view",$$).placeholder();
				};
				
				//页码跳转
				$(".p-btn",$$).off("click").on("click",function(){
					var p = parseInt($(this).prev().val()) - 1;
					var num =  $(".pagination",$$).children().length - 2;
					var cpage = $(".pagination",$$).children().eq(num).text();
					var url = $(".select-auto",$$).children("input").attr("url");
					var val = $(".select-auto",$$).children("input").attr("selectval");
					var type = "get";
					var room_id = '';
					var house_type='';
					var house_id = '';
					room_id = urlHelper.get('room_id');
					house_type = urlHelper.get('house_type');
					house_id = urlHelper.get('house_id');
					if(house_type == "") house_type =2;
					var obj = {
								url : url,
								type : type,
								room_id : room_id,
								house_id : house_id,
								view : val,
								house_type:house_type,
							};
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
						that.list_CallBack(p,obj);
					}
					
				});
				//调用分页插件
				ajax.iniPagination(100, "#pagination");
				
				$(".meter-time",$$).off("click").on("click",function(){
					calendar.inite();
				});
				$(".check-box label",$$).off("click").on("click",function(){
					$(this).toggleClass("checked");
					if($(this).hasClass("checked")){
						$(this).children().show();
					}else{
						$(this).children().hide();
					}
				});
			},
			//翻页初始化
			newpagecovers : function(obj){
				var that = this;
				var inp = obj.find(".selectByMT");
				var val = obj.attr("selectval");
				if(!!!val) val = "";
				var prev_val = inp.attr("prev-data");
						var url = inp.attr("url");
						var type = "get";
						var room_id = '';
						var house_type='';
						var house_id = '';
						
						room_id = urlHelper.get('room_id');
						house_type = urlHelper.get('house_type');
						house_id = urlHelper.get('house_id');
						if(house_type == "") house_type =2;
						var obj = {
							url : url,
							type : type,
							room_id : room_id,
							house_id : house_id,
							view : val,
							house_type:house_type,
						}
						that.list_CallBack(0,obj)
			},
			//翻页
			list_CallBack : function(index,obj){
				var that = this;
				var search = $(".search_Txt",$$).val();
				if(!!!index) index=0;
				var current_Page = index+1;
				if(!$("#pagination-auto-fee",$$).data("object")) $("#pagination-auto-fee",$$).data("object",obj);
				if(typeof obj.url == "undefined") obj = $("#pagination-auto-fee",$$).data("object");
				var urll = obj.url;
				var type = obj.type;
				var room_id = obj.room_id;
				var fee_type_id = obj.view;
				var house_type = obj.house_type;
				var house_id = obj.house_id;
				var data = {
					"room_id" : room_id,
					"house_type":house_type,
					"house_id" : house_id,
					"fee_type_id" : fee_type_id,
					"page" : current_Page
				};
				ajax.doAjax(type,urll,data,function(data){
					var pages_Total = data.count;   //每页条数
					var pages_Count = data.size;   //总共条数		
					if(data.status == 1){
						if( data.page == 1){
							ajax.iniPagination(pages_Total,"#pagination-auto-fee",that.list_CallBack,pages_Count);
						}
						dataUpdate(data.data);
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
					//数据更新及事件添加
					function dataUpdate(data){
						$('.lst_tb tr:gt(0)',$$).remove();
						var str = '';
						for(var n in data){
							str += '<tr><td><span>'+data[n].type_name+'</span></td><td class="tb-second"><span>'+data[n].before_meter+'</span></td><td class="tb-three"><span>'+data[n].now_meter+'</span>	</td><td><span>'+data[n].add_time+'</span></td><td class="tb-five"><span>'+data[n].name+'</span></td></tr>';
						}
						$('.lst_tb',$$).append(str);
					};	
			},
			submitForm : function(){
				var that = this;
				var url = $(".btn2",$$).attr("url");
				var type = "post";
				data = that.getData();
				var result_check = hash.hash.ischange("view_con",$(":first",$$));
					if(result_check === true){
						ajax.doAjax(type,url,data,function(data){
						if(data.status == 1){
							var d = dialog({
								title: '提示信息',
								content:'保存成功',
								okValue: '确定',
								ok: function () {
									$(".btn2",$$).removeClass("none-click");
									d.close();
									//翻页初始化
									that.newpagecovers($(".history-records .selectByM",$$));
									$(".meter-val",$$).attr("high-data",$(".meter-val",$$).val());
									if($(".check-box",$$).children().hasClass("checked")){
										chooserooms_auto();	
									}
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
					});
					$(".ui-dialog-close",".ui-dialog-header").hide();
				}
				else{
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
				}
				function chooserooms_auto(){
					var url = $("#hideTemp",$$).attr("url");
					var f_url = "#"+$("#hideTemp",$$).attr("f-url");
					var room_id = '';
					var house_type='';
					var house_id = '';

					room_id = urlHelper.get('room_id');
					house_type = urlHelper.get('house_type');
					house_id = urlHelper.get('house_id');
					var data1 = {
						room_id : room_id,
						house_id : house_id,
						house_type : house_type
					};
					ajax.doAjax("post",url,data1,function(data){
									if(data.status == 1){
										$("#hideTemp .lr-tb",$$).empty();
										var datas = data.data;
										for(var n in datas){
											var house_name = datas[n].house_name;
											house_name = encodeURI(encodeURI(house_name));
											var jump_url = f_url+"&house_id="+datas[n].house_id+"&money="+datas[n].money+"&house_type="+datas[n].house_type+"&fee_type_id="+datas[n].fee_type_id+"&sum_money="+datas[n].sum_money+"&meterid="+datas[n].meterid+"&house_name="+house_name+"&room_id="+datas[n].room_id+"&meter=meter_read";
											var str='<tr><td style="width:49px"><label class="checkbox"><span class="gou ifont1">&#xe60c;</span></label><input type="checkbox" value="'+jump_url+'"/></td><td style="width:169px">'+datas[n].house_name+"-"+datas[n].custom_number+'</td><td style="width:110px">'+datas[n].type_name+'</td><td>'+datas[n].sum_money+'</td></tr>';
											$("#hideTemp .lr-tb",$$).append(str);
										}
										var hideTemp = $("#hideTemp",$$).html();
										var d = dialog({
											title: '<i class="ifont ifont-yytz">&#xe6a3;</i><span>选择收费房间</span>',
											content: hideTemp,
											okValue: '确 定', drag: true,
											ok: function () {
												d.close();
												$(".ui-dialog-content .centralized_Ind_tab_td td").each(function(){
													if($(this).find("input:checked").size() > 0){
														var jump = $(this).find("input").val();
														//关闭当前标签
														var tag = WindowTag.getCurrentTag();
														WindowTag.closeTag(tag.find('>a:first').attr('url'));
														window.location.href = jump;
													}
												});
											},
											cancelValue: '取消',
											cancel: function () {
												
											}
										});
										d.showModal();
										$(".centralized_Ind_tab_td .checkbox",$$).off("click").on("click",function(){
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
											content: "房间数据拉取失败！",
											okValue: '确 定', drag: true,
											ok: function () {
												d.close();
											}
										});
										d.showModal();
									}
								});
				}
			},
			getData : function(){
				var meter_name = $("input[name='fee-name']",$$).attr("selectval");
				var meter_price = $(".fee-price-auto",$$).text();
				var meter_time = $(".meter-time",$$).val();
				var meter_val = $(".meter-val",$$).val();
				var room_id = '';
				var house_type='';
				var house_id = '';
				room_id = urlHelper.get('room_id');
				house_id = urlHelper.get('house_id');
				house_type = urlHelper.get('house_type');
				if(house_type == "") house_type =2;
				var data = {
					meter_name : meter_name,
					meter_price : meter_price,
					meter_time : meter_time,
					meter_val : meter_val,
					room_id : room_id,
					house_id : house_id,
					house_type : house_type
				};
				return data;
			},
			checkUI : function(){
				var that = this;
				$(".meter",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents("li").find(".check-error");
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
		                    if(!reg.test(gets)){return false;}
		                    if(gets.indexOf(".")>0){
		                    	if(gets.split(".")[1].length > 2) return "小数点后不能超过两位";
		                    }
		               },
		               "checkhigh":function(gets,obj,curform,regxp){
		               		if(!obj.hasClass(".Validform_error")){
			               		var high_data = parseFloat(obj.attr("high-data"));
			               		if(gets < high_data){
			               			return "当前记录不能小于记录最高值！";
			               		}	
			               	}
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
		};
		meter.inites();
		meter.checkUI();
		hash.hash.savehash("view_con",$(":first",$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('view_con',$(':first',$$)) === true){
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
	}
	
	exports.inite = function(__html__){
		auto(__html__)
	}
});