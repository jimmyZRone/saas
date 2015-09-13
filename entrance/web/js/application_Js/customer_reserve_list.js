/*
 *@func 预定管理
 * */
define(function(require,exports,module){
	var $ = require("jquery"),
	loading=require("loading"),
	template=require("artTemp"),
	dpk=require("calendar"),
	 	 ajax=require("Ajax"),ajaxLoading;
	 	 dialog = require("dialog"); //弹窗插件
	 	 require("radio")($);
	 	 require("validForm")($);
		 require("combobox")($);
	 var moduleInte=function($$,data){
		function Reserve(){
			this.init();
		}
		Reserve.prototype={
			init:function(){
				var that=this;
				that.bind();
				that.addDptClient();
			},
			/*
			 *@func:事件绑定
			 * */
			bind:function(){
				var that=this;
				$.each($(".radio",$$),function(i,o){
					$(o).click(function(){
						$(this).Radios();
					})
				});
				$(".wdate",$$).on("click",function(){
					dpk.inite();
				});
				$(".dpt-sort",$$).off("click").on("click",function(){
					$(this).addClass("current").siblings().removeClass("current");
					that.pagescover();
				});
			},
			/*
			 * @func:添加预定/查看预定
			 */
			addDptClient:function(){
				var that=this;
				$(".dpt-add-reserve",$$).click(function(){
					var rsvTemp=$("#hide_reserve_detail",$$).html();
					var d = dialog({
						title: '<i class="ifont">&#xf0077;</i><span>预定人信息</span>',
						content: rsvTemp
					});
					d.showModal();
					that.bindEvt(d);
				});
				that.pagescover();
			},
			/*
			 * @func:绑定遮罩模板的相关事件
			 */
			bindEvt:function(d){
				var that=this;
				//匹配
				 $('input[name="housename"]',".ui-dialog").off('change').on('change','',function(){
		            var value = $.trim($(this).val());
		            if(value===''|| value.length<3){
		                $('.community-name,.address-update').addClass('hidden');
		                $('.address-autoComplete').removeClass('hidden');
		                return false;
		            }
		        });
       		  	$('input[name="housename"]',".ui-dialog").each(function(){
		            var input = $(this);
		            var searchurl = input.attr("searchurl");
		            var dom = input.parent('.jzf-col-r');
		             $('.address-autoComplete a',dom).unbind('click').bind('click',function(){
		                $('.address-autoComplete',dom).addClass('hidden');
		                $('.community-name,.address-update',dom).removeClass('hidden');
		                var area_id = parseInt($('input[name="community_id"]',dom).val('').attr('area_id'));
		                area_id = isNaN(area_id) ? 0 : area_id;
		                if(area_id>0){
		                    $('select[name=address-area]',dom).val(area_id).change();
		                }
		                $('input[name=address_string]',dom).val($('.address-autoComplete span',dom).text());
		            });
		            input.combobox({
		                url:searchurl,
		                param_name: 'search',
		                title_name: 'house_name',
		                result:'room',
		                commbox_selector: '.commbox',
		                width: 425,
		                item_template : ':house_name',
		                //height: 200,
		                min_char:1,
		                padding: 10,
		                offset: { top: 32},
		                callback: {
		                    init: function(obj) {
		                        //$('.address-autoComplete a').trigger('click');
		
		                    },
		                    select: function(obj) {

		                    },
		                    choose: function(obj) {
		                        if(typeof obj == 'object'){
		                            $('input[name="community_id"]',dom).val(obj.house_id).attr('record-id',obj.record_id).attr('house-type',obj.house_type).attr("rental_way",obj.rental_way);
		                            input.siblings(".check-error-auto").hide();
		                        }
		                    },
		                    notchoose: function(obj) {
		                        $('input[name="community_id"]',dom).val('').attr('record-id','').attr('house-type','').attr("rental_way",'');
		                    },
		                    notdata: function(obj) {
		                        $('input[name="community_id"]',dom).val('').attr('record-id','').attr('house-type','').attr("rental_way",'');
		                    }
		
		                }
		            });
		        });
				//下拉框
				$(".rev-over-temp-selectByM",".ui-dialog").each(function(){
					if($(this).attr("hasevent")){
						$(this).selectObjM(1,function(val,inp){
							if(!inp.hasClass("Validform_error")) inp.siblings(".check-error-auto").hide();
						})
					}else{
						$(this).selectObjM();	
					}
				});
				//日期
				$(".rev-over-temp-wdate",".ui-dialog").on("click",function(){
					dpk.inite();
				});
				//自定义按钮事件
				 $(".close-resv-mask",".ui-dialog").off("click").on("click",function(){
				 	d.close();
				 	d.remove();
				 });
				//表单验证初始
				that.checkForm(d);
				//录入框获得焦点与失去焦点状态
				$(":input,textarea",".ui-dialog").off("focus").on("focus",function(){
					if($(this).hasClass("Validform_error")){
						$(this).css("background","none");
						$(".check-error-auto,.check-error-ts",".ui-dialog").hide();
					}
				});
				$(":input,textarea",".ui-dialog").off("blur").on("blur",function(){
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
										if(typeof data.data.data[0] == "undefined") return false;
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
										if(typeof data.data.data[0] == "undefined") return false;
										var namea = data.data.data[0].name;
										var phonea = data.data.data[0].phone;
										var id_carda =  data.data.data[0].idcard;
										if(typeof data.data.data[0] == "undefined" || typeof phonea == "undefined" || typeof id_carda == "undefined") return false;
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
			},
			/*
			 *@func 获取提交参数
			 * */
			getParamData:function(form){
				var house_name = $("#houses",form).val();
				var house_id = $("input[name='community_id']",form).val();
				var record_id = $("input[name='community_id']",form).attr("record-id");
				var house_type = $("input[name='community_id']",form).attr("house-type");
				var rental_way = $("input[name='community_id']",form).attr("rental_way");
				var reserve_id = $("input[name='community_id']",form).attr("reserve-id");
				var tenant_id = $("input[name='community_id']",form).attr("tenant-id");
				if(typeof($("input[name='community_id']",form).attr("reserve-id")) == "undefined") reserve_id = '';
				if(typeof($("input[name='community_id']",form).attr("datatenant-id")) == "undefined") datatenant_id = '';
				var name = $("input[name='name']",form).val();
				var phone = $("input[name='phone']",form).val();
				var idcard = $("input[name='idcard']",form).val();
				var money = $("input[name='money']",form).val();
				var begin_date = $("input[name='begin_date']",form).val();
				var end_date = $("input[name='end_date']",form).val();
				var ya = $("input[name='ya']",form).attr("selectval");
				var fu = $("input[name='fu']",form).attr("selectval");
				var remark = $("#remark",form).val();
				var data = {
					tenant_id : tenant_id,
					reserve_id : reserve_id,
					house_name : house_name,
					house_id : house_id,
					record_id : record_id,
					house_type : house_type,
					rental_way : rental_way,
					name : name,
					phone : phone,
					idcard : idcard,
					money : money,
					begin_date : begin_date,
					end_date : end_date,
					ya : ya,
					fu : fu,
					remark : remark
				}
				return data;
			},
			/*
			 *@func：表单提交
			 * */
			submitForm : function(form,auto){
				var that=this;
				var type = "post",
					  url = $(".dpt-add-reserve",$$).attr("reserveurl");
					data=that.getParamData(form,auto);
				if($("input[name='housename']",form).attr("readonly")) url = $("input[name='housename']",form).attr("edit-url");
				ajax.doAjax(type,url,data,function(data){
					if(data.status == 1){
						var d = dialog({
							title: '提示信息',
							content:'保存成功！',
							okValue: '确定',
							ok: function () {
								d.close();
								auto.close();
								//刷新当前标签
								var tag = WindowTag.getCurrentTag();
								var url = tag.find('>a:first').attr('url');
								WindowTag.loadTag(url,'get',function(){
									
								});
								if(!!!data.url) return false;
								window.location.href = "#"+data.url;
							}
						});
					}else{
						var d = dialog({
							title: '提示信息',
							content:data.message,
							okValue: '确定',
							ok: function () {
								d.close();
							}
						});
					}
					d.showModal();
				});
			},
			/*
			 *@func:初始化表单验证
			 * */
			checkForm : function(auto){
				var that=this;
				$(".reserveUserForm",".ui-dialog-content").Validform({
					btnSubmit : "#save-resv-info",
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
		               "checkhouse":function(gets,obj,curform,regxp){
		               	     if($.trim(gets)!="" && obj.siblings("input[name='community_id']").val()==""){
		               	     	return "房源不存在，请先添加！";
		               	     }
		               },
		                "chooseGroup":function(gets,obj,curform,regxp) {
			                   if(obj.attr("selectVal") == ''){
			                   	 return obj.attr("choosenull");
			                   }
	
			              },
			              "string255":function(gets,obj,curform,regxp){
								 var value = gets.replace(/([^\u0000-\u00FF])/g,"***");
		                   	 	 var length = value.length;
		                   	 	 if(length>255) return false;
							},
							"float":function(gets,obj,curform,regxp){
								var reg=/^\d+(\.\d+)?$/;
			                    if(!reg.test(gets)){return false;}
			                    if(gets.indexOf(".")>0){
			                    	if(gets.split(".")[1].length > 2) return "小数点后不能超过两位";
			                    }
							},
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
						              	}
		            },
		            callback : function(form){
			            	that.submitForm(form,auto);
			            	return false;
		            }
				});
		},
		/*	
		 * @func:预订列表翻页
		 */
		pagescover : function(index){
			var that = Reserve.prototype;
			var search = $("#keywords",$$).val();
			var stime = $("#exp_bdate",$$).val();
			var etime = $("#exp_edate",$$).val();
			var house_type = $(".jzf-col-r-auto a.current",$$).attr("data-type");
			if($("#keywords",$$).val() == $("#keywords",$$).attr("placeholder")) search = '';
			if(!!!index) index=0;
			var current_Page = index+1;
			var urll = $(".lst_tb",$$).attr("listaction");
			var data = {
				"stime":stime,
				"etime":etime,
				"house_type":house_type,
				"keywords" : search,
				"page" : current_Page
			}
			var trpe = "get";
			var tp=loading.genLoading("tr","6",1);
			$('.lst_tb tr:gt(0)',$$).remove();
			$('.lst_tb',$$).append(tp);
			ajax.doAjax(trpe,urll,data,function(data){
				if(data.status == 1){
					$(".tenant_List",$$).addClass("none");
					var nums_pages = data.page.cpage;
					if( data.page.page == 1){
						var pages_Total = data.page.count;   //每页条数
						var pages_Count = data.page.size;   //总共条数		
						ajax.iniPagination(pages_Total,"#crm-manage-pagination",that.pagescover,pages_Count);
						$(".jump-edit-box .p-btn").off("click").on("click",function(){
							var page_jump = $(this).prev().val()/1;
							if(page_jump > nums_pages || isNaN(page_jump)){
								var da=dialog({
									title:"提示",
									content:"请输入正确的页码"
								});
								da.showModal();
								setTimeout(function(){
									da.close().remove();
									$(".get-cstm-trigger").prev().val("");
								},1200); 
							}else{
								that.pagescover(page_jump-1);
							}
						});
					}
				$(".jzf-pagination",$$).show();
				that.dataUpdate(data);
				}else{
					$('.lst_tb tr:gt(0)',$$).remove();
					var errorTemp=loading.genLoading("tr","6",2);
					//$('.lst_tb',$$).append(errorTemp);
					$(".jzf-pagination",$$).hide();
					$(".tenant_List",$$).removeClass("none");
				}
				});
		},
		//数据更新及事件添加
	   		dataUpdate : function(data){
	   			var that = this;
				$('.lst_tb tr:gt(0)',$$).remove();
				$('.lst_tb',$$).append(data.data);
				if(!$(".dpt-toolbar-active",$$).is(":hidden")){
					$(".lst_tb",$$).find("tr:gt(0)").each(function(){
		   				$(this).find("td:first").find("span:first").children(".checkBox").fadeIn(300);
		   			});
		   				if($(".checkBoxAll",$$).hasClass("checked")){
		   					$(".lst_tb .checkBox",$$).each(function(){
			   					$(this).addClass("checked").children().show();
			   					$(this).next().attr("checked",true);
			   				});
		   				}
				}
				that.bindEvt();
				that.eventblin();
		   },
		   //查看|出租|退订|删除(含多选)|按条件筛选
		   eventblin : function(){
		   		var that = this;
		   		//查看
		   		$(".dpt-reserve-preview",$$).off("click").on("click",function(){
		   			var thst= $(this);
		   			if(thst.hasClass("stop-click")) return false;
		   			thst.addClass("stop-click");
					var url = $(this).attr("data-url");
					var type= "get";
					var id = $(this).parents("tr").attr("data-id");
					var data = {
						id : id
					};
					ajax.doAjax(type,url,data,function(data){
						if(data.status == 1){
							var rsvTemp=$("#hide_reserve_detail",$$).html();
							var d = dialog({
								title: '<i class="ifont">&#xf0077;</i><span>预定人信息</span>',
								content: rsvTemp
							});
							d.showModal();
							thst.removeClass("stop-click");
							var uldialogconent = $(".ui-dialog-content");
							$("input[name='housename']",uldialogconent).val(data.data.house_name).attr('readonly',true);
							$("input[name='community_id']",uldialogconent).val(data.data.house_id).attr("record-id",data.data.room_id).attr("house-type",data.data.house_type).attr("rental_way",data.data.rental_way).attr("reserve-id",data.data.reserve_id).attr("tenant-id",data.data.tenant_id);
							$("input[name='name']",uldialogconent).val(data.data.tname);
							$("input[name='phone']",uldialogconent).val(data.data.tphone);
							$("input[name='idcard']",uldialogconent).val(data.data.tidcard);
							$("input[name='money']",uldialogconent).val(data.data.money).attr('readonly',true);
							$("input[name='begin_date']",uldialogconent).val(data.data.stime);
							$("input[name='end_date']",uldialogconent).val(data.data.etime);
							var ya = data.data.pay_type;
							var fu = data.data.source;
							// console.log(ya+","+fu);
							$("input[name='ya']",uldialogconent).siblings(".selectByMO").find("li").each(function(){
								if($(this).attr("selectval") == ya){
									$(this).addClass("selectedLi");
									$("input[name='ya']",$(".ui-dialog-content")).val($(this).text()).attr("selectval",ya);
								}
							})
							$("input[name='fu']",uldialogconent).siblings(".selectByMO").find("li").each(function(){
								if($(this).attr("selectval") == fu){
									$(this).addClass("selectedLi");
									$("input[name='fu']",$(".ui-dialog-content")).val($(this).text()).attr("selectval",fu);
								}
							});
							$("#remark",uldialogconent).val(data.data.mark);
							that.bindEvt(d);
//							var housename = $("input[name='housename']",uldialogconent);
//							var housenamep = housename.parent();
//							housename.remove();
//							housenamep.before(housename.clone());
						}else{
							var d = dialog({
									title: '提示信息',
									content:data.message,
									okValue: '确定',
									ok: function () {
										d.close();
										thst.removeClass("stop-click");
									}
								});
							d.showModal();
						}
					});
		   		});
		   		//退订
		   		$(".cancle-reserve-trig",$$).off("click").on("click",function(){
		   			var thats = $(this);
					var d = dialog({
						title: '<i class="ifont">&#xf0077;</i><span>预定人退定</span>',
						content: '确认退定？',
						okValue: '确定',
						ok: function () {
							var url = thats.attr("data-url");
							var type= "post";
							var id = thats.parents("tr").attr("data-id");
							var data = {
								reserve_id : id
							};
							ajax.doAjax(type,url,data,function(data){
								if(data.status == 1){
									var dd = dialog({
											title: '提示信息',
											content:data.message,
											okValue: '确定',
											ok: function () {
												dd.close();
												thats.parents("tr").remove();
												if($(".lst_tb",$$).find("tr").length <= 1){
													//刷新当前标签
													var tag = WindowTag.getCurrentTag();
													var url = tag.find('>a:first').attr('url');
													WindowTag.loadTag(url,'get',function(){
														
													});
												}
												window.location.href = "#"+data.url;
											}
										});
									dd.showModal();
								}else{
									var dd = dialog({
											title: '提示信息',
											content:data.message,
											okValue: '确定',
											ok: function () {
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
		   		//删除
		   		$(".spr-del-trig",$$).off("click").on("click",function(){
		   			var thats = $(this);
		   			var d = dialog({
						title: '<i class="ifont">&#xf0077;</i><span>删除预定人</span>',
						content: '删除的信息将无法得到恢复，确认删除？',
						okValue: '确定',
						ok: function () {
							var url = thats.attr("data-url");
							var type= "post";
							var id = thats.parents("tr").attr("data-id");
							var data = {
								reserve_id : id
							};
							ajax.doAjax(type,url,data,function(data){
								if(data.status == 1){
									var dd = dialog({
											title: '提示信息',
											content:data.message,
											okValue: '确定',
											ok: function () {
												dd.close();
												thats.parents("tr").remove();
												if($(".lst_tb",$$).find("tr").length <= 1){
													//刷新当前标签
													var tag = WindowTag.getCurrentTag();
													var url = tag.find('>a:first').attr('url');
													WindowTag.loadTag(url,'get',function(){
														
													});
												}
											}
										});
									dd.showModal();
								}else{
									var dd = dialog({
											title: '提示信息',
											content:data.message,
											okValue: '确定',
											ok: function () {
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
		   		//进入多选删除状态
		   		$(".dpt-toolbar-delete",$$).off("click").on("click",function(){
		   			$(this).parent().slideUp(300).next().slideDown(300);
		   			$(".lst_tb",$$).find("tr:gt(0)").each(function(){
		   				$(this).find("td:first").find("span:first").children(".checkBox").fadeIn(300);
		   			});
		   		});
		   		//退出多选删除状态
		   		$(".edit-cancle-trig",$$).off("click").on("click",function(){
		   			$(this).parent().slideUp(300).prev().slideDown(300);
		   			$(".lst_tb",$$).find("tr:gt(0)").each(function(){
		   				$(this).find("td:first").find("span:first").children(".checkBox").fadeOut(300);
		   			});
		   		});
		   		//勾选复选框
		   		$(".lst_tb .checkBox",$$).off("click").on("click",function(){
		   			$(this).toggleClass("checked");
		   			if($(this).hasClass("checked")){
		   				$(this).children().show();
		   				$(this).next().attr("checked",true);
		   			}else{
		   				$(this).children().hide();
		   				$(this).next().removeAttr("checked");
		   			}
		   		});
		   		$(".checkBoxAll",$$).off("click").on("click",function(){
		   			$(this).toggleClass("checked");
		   			if($(this).hasClass("checked")){
		   				$(this).children().show();
		   				$(".lst_tb .checkBox",$$).each(function(){
		   					$(this).addClass("checked").children().show();
		   					$(this).next().attr("checked",true);
		   				});
		   			}else{
		   				$(this).children().hide();
		   				$(".lst_tb .checkBox",$$).each(function(){
		   					$(this).removeClass("checked").children().hide();
		   					$(this).next().removeAttr("checked");
		   				});
		   			}
		   		});
		   		$(".dpt-toolbar-active a:first").off("click").on("click",function(){
					var obj = $(this).siblings(".checkBox");
					obj.toggleClass("checked");
					if(obj.hasClass("checked")){
						obj.children(".choose").show();
						$(".lst_tb .checkBox",$$).each(function(){
		   					$(this).addClass("checked").children().show();
		   					$(this).next().attr("checked",true);
		   				});
					}else{
						obj.children(".choose").hide();
						$(".lst_tb .checkBox",$$).each(function(){
		   					$(this).removeClass("checked").children().hide();
		   					$(this).next().removeAttr("checked");
		   				});
					}
				});
		   		//筛选
		   		$("#goSearchTrig",$$).off("click").on("click",function(){
		   			that.pagescover();
		   		});
		   		//多选删除
		   		$(".dpt-toolbar-active-delete",$$).off("click").on("click",function(){
		   			var url = $(this).attr("data-url");
		   			var type = "post";
		   			var deletelist = [];
		   			$(".lst_tb tr:gt(0)",$$).each(function(){
		   				if($(this).find("input:checked").size() > 0){
		   					var reserve_id = $(this).attr("data-id");
		   					var house_name = $(this).find("td:first").children("span:last").text();
		   					var _eleAuto = {
		   						reserve_id : reserve_id,
		   						house_name : house_name
		   					}
		   					deletelist.push(_eleAuto);
		   				}
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
		   				deletelist : deletelist
		   			}
		   			that.deleteauto(deletedata);
		   		});
		   },
		   deleteauto : function(deletedata){
		   	  var that = this;
		   	  var cloneStr = $(".deletemoreauto",$$).clone();
		   	  cloneStr.removeClass("none");
		   	  var deleteTptal = deletedata.deletelist.length;
		   	  cloneStr.find(".num_total").text(deleteTptal);
		   	  var dd = dialog({
						title: '<i class="ifont">&#xe675;</i><span>删除预定人</span>',
						content:cloneStr,
						okValue: '确定',
						ok: function () {
							dd.close();
						},
						cancelValue: '取消',
						cancel: function () {
							$(".ui-dialog-content").children(".deletemoreauto").addClass("stop");
							$(".ui-dialog-content").children(".deletemoreauto").find(".top1 .fl").text("删除终止");
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
				var deleteList = deletedata.deletelist;
				var num_cur = 0;  //计算器
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
		   			 var trstr = '<tr><td class="zb">'+objsauto.find("td:first").find("span:last").text()+'</td><td class="yb">正在删除</td></tr>';
		   			 tableauto.append(trstr);
		   			 if(num_cur > 5){
						tableauto.find("tr:eq("+(num_cur-5)+")").fadeOut(300);
					}
		   			 var trcur = tableauto.find("tr:last");
		   			 var idauto = objsauto.attr("data-id");
		   			 var data = {
						reserve_id : idauto
					};
					ajax.doAjax(type,url,data,function(data){
						if(data.status == 0){
							trcur.find(".yb").addClass("red").removeClass("blue").text(data.message);
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
							trcur.find(".yb").addClass("blue").removeClass("red").text(data.message);
							objsauto.remove();
							if($(".lst_tb",$$).find("tr").length <= 1){
								//刷新当前标签
								var tag = WindowTag.getCurrentTag();
								var url = tag.find('>a:first').attr('url');
								WindowTag.loadTag(url,'get',function(){
									
								});
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
		   }
		};
		new Reserve();//初始化
	 };
	 exports.inite=function(_html_,data){
		moduleInte(_html_,data);
	}
});
