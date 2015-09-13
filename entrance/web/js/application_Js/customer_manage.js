define(function(require,exports,module){
	var $ = require("jquery"),
	loading=require("loading"),
	template=require("artTemp"),
	sms=require("sms"),
	dpk=require("calendar"),
	 	 ajax=require("Ajax"),ajaxLoading;
	 	 dialog = require("dialog"); //弹窗插件
	 	 require("selectByM")($);
	 	 require("radio")($);
	 	 require("validForm")($);
	 	 require("raty");

	var path=baseUri+"Raty/images/",ajaxLoading,
	    solarMonth=new Array(31,28,31,30,31,30,31,31,30,31,30,31),
	    EVTINITED=0,
	    isInAllDel=0;


		//默认评分文字提示
		var ratyHints=[];
		ratyHints[undefined]="";
		ratyHints[1]="租客经常做出格的事情（例如不爱惜房屋，不注意邻里和谐，常带狐朋狗友乱整等)";
		ratyHints[2]='租客有少数较恶劣的行为（例如损坏家具、不爱惜房屋等）';
		ratyHints[3]='中规中矩，租客有些小毛病，但还能忍受。（例如不爱清洁等）';
		ratyHints[4]='素质不错，一切OK';
		ratyHints[5]='中国好租客，合作很愉快';

	var pageFun=function($$,data){
		 function CustomerList(){
		 	this.init($$,data);
		 }
		 CustomerList.prototype={
		 	init:function($$,data){
		 		var that=this;
		 		that.bind();
				that.iniDatepicker();
				that.delCustom();
				that.doSearchAct();
				that.genList(0);
		 	},
		 	/*
		 	 *@func 事件绑定
		 	 * */
		 	bind:function(){
	 			var  that=this;
				// 	$(".send_sms",$$).click(function(){
	 		// 		sms.showTemp();
				// 	});
	 			$(".printCustlist",$$).off().on("click",function(){
					var cur=$(this);
					var _inn=$("#tb-agmt-lst",$$).clone();
					_inn.find(".ck-status").remove();
					_inn.find(".edit-col").parent().remove();
					var _len=
					_inn.find("tr th:last-child").remove();
					var _el=$(_inn).html();
				    document.getElementById('printListWrap').contentWindow.document.getElementById('custm-list-wrap').innerHTML=_el;
					try{
						if(!!$("#printListWrap").get(0).contentWindow.printUnity){
							$("#printListWrap").get(0).contentWindow.printUnity._printOption();
						}
					}catch(e){

					}
//					that.printCustmInfo("tb-agmt-lst");//打印列表
	 			});
				//状态
				$(".dpt-sort",$$).click(function(){
					var  cur=$(this);
					if(!cur.hasClass("current")){
						cur.parent().find(".current").removeClass("current");
						cur.addClass("current");
						$("#goSearchTrig",$$).trigger("click");
					}
				});
				/*限定日期选择*/
				$(".date_ops",$$).click(function(){
					var  cur=$(this);
					if(!cur.hasClass("current")){
						cur.parent().find(".current").removeClass("current");
						cur.addClass("current");
						var type=cur.attr("data-type"),_date=new Date(),_dtime,_speriod;
						var  curYear=_date.getFullYear(),
							 curMonth=(_date.getMonth()+1) < 10 ?"0"+(_date.getMonth()+1):(_date.getMonth()+1),
							 curDay=_date.getDate() < 10 ? "0" + _date.getDate():_date.getDate(),endTime;
						_dtime=curYear+"-"+curMonth+"-"+curDay;//当前日期
						switch(type){
							case "1":
								_speriod=0;
								break;
							case "2":
								_speriod=7;
								break;
							case "3":
								_speriod=that.solarDays(curYear,curMonth-1);
								break;
							case "4":
								_speriod=3;//间隔3天
								break;
							default:
								break;
						}
						var tempYear,teamMon,temDay,totalDays,
							finNalYear,finNalMon,finNalDay;
						temDay=parseInt(curDay)+parseInt(_speriod);
						totalDays=that.solarDays(curYear,curMonth-1);

						// console.log(temDay);
						// console.log(totalDays);
						if(temDay<=totalDays){
							endTime=curYear+"-"+curMonth+"-"+(temDay<10?"0"+temDay:temDay);
						}else{
							if(curMonth<12){
								teamMon=parseInt(curMonth)+1;
								finNalDay=temDay-totalDays;
								endTime=curYear+"-"+(teamMon<10?"0"+teamMon:teamMon)+"-"+(finNalDay<10?"0"+finNalDay:finNalDay);
							}else{
								curYear=parseInt(curYear)+1;
								curMonth=1;
								totalDays=that.solarDays(curYear,curMonth-1);
								finNalDay=temDay-parseInt(curDay)-1;
								endTime=curYear+"-"+(curMonth<10?"0"+curMonth:curMonth)+"-"+(finNalDay<10?"0"+finNalDay:finNalDay);
							}
						}
						cur.parent().find(".wbdate").val(_dtime);
						cur.parent().find(".wedate").val(endTime);
						$("#goSearchTrig",$$).trigger("click");
//						cur.parent().find(".clear_filter").show();
					}else{
						cur.removeClass("current");
						var _par=cur.parent();
						_par.find(".current").removeClass("current");
						_par.find("input").val("");
						$("#goSearchTrig",$$).trigger("click");
					}
				});
				$(".clear_filter",$$).off("click").on("click",function(){
					var cur=$(this),_par=cur.parent();
					_par.find(".current").removeClass("current");
					_par.find("input").val("");
					cur.hide();
					$("#goSearchTrig",$$).trigger("click");
				});
			},/*
			*@func 页码跳转事件绑定跳转
			*/
			jumpPage:function(totlaPage){
				var that=this;
				$(".get-cstm-trigger",$$).off().on("click",function(){
					var cur=$(this),c=$.trim(cur.prev().val());
					that.getRecords(c,totlaPage);
				});
				$(".get-cstm-trigger",$$).prev().off().on("keyup",function(e){
					if(e.keyCode=="13"){
						var cv=$(this).val();
						that.getRecords(cv,totlaPage);
					}
				});

			},/*
			*@func 页码跳转事件处理
			*/
			getRecords:function(c,totlaPage){
				var that=this;
				var reg=/^[1-9]\d*$/;
				if(c!="" && reg.test(c)==true && c<=totlaPage){
					that.genList(c-1);
				}else{
					var da=dialog({
						title:"提示",
						content:"请输入正确的页码"
					});
					da.showModal();
					setTimeout(function(){
						da.close().remove();
						$(".get-cstm-trigger",$$).prev().val("");
					},1200);
				}
			},
			/*
			 *@func 绑定数据栏目操作
			 * */
			delTbitem:function(){
				var that=this;
				$(".spr-del-trig",$$).off().on("click",function(){
					if(isInAllDel==1) return;
					var cur=$(this),_par=cur.parents("tr");
					if(!_par.hasClass("delItem")){
						_par.parent().find(".delItem").removeClass("delItem");
						_par.addClass("delItem");
					}
					var d = dialog({
						title: '<i class="ifont">&#xf0077;</i><span>删除租客</span>',
						content:"<p>确认删除该租客?</p>",
						okValue: '确定',
						ok: function () {
							if(!cur.hasClass("clicked")){
								cur.parent().find(".clicked").removeClass("clicked");
								cur.addClass("clicked");
								ajax.doAjax("get",$("#goSearchTrig",$$).attr("delurl")+"&contract_id="+cur.attr("data-id"),"",function(json){
									d.close();
									if(json.status==1){
										cur.parents("tr").remove();
									}
									var  da=dialog({
										title: '提示',
										content:json.message,
										okValue: '确定',
										ok: function () {
											da.close();
										},
									});
									da.showModal();
									$(".edit-cancle-trig",$$).trigger("click");
								});
							}
						},
						cancelValue: '取消',
						cancel: function () {
						}
					});
					d.showModal();
				});
				$(".spr-cmt-trig",$$).off().on("click",function(){
					$(".raty",$$).html("");//清空评分内容
					var cur=$(this),
						_edtItem=cur.parents("tr"),
						data_url = cur.attr("alltenantactionurl"),
						str_html = '';
						if(cur.hasClass("stop-click")) return false;
						cur.addClass("stop-click");
						ajax.doAjax("get",data_url,"",function(json){
							if(json.status==1){
								var data = json.data;
								for(var i in data){
									var datas={
										username:data[i].name,
										sex:data[i].gender,
										idCard:data[i].idcard,
										phone:data[i].phone,
										id:data[i].rental_id
									};
									var temp=template('dpt-user-info', datas);
									str_html += temp;
								}
									document.getElementById("dpt-user-temp").innerHTML=str_html;
									var cTemp=$("#cmt-ove-temp-hideTemp",$$).html();
								if(!_edtItem.hasClass("isEditing")){
									_edtItem.parent().find(".isEditing").removeClass("isEditing");
									_edtItem.addClass("isEditing");
									var d = dialog({
										title: '<i class="ifont">&#xf0077;</i><span>评价租客</span>',
										content: cTemp,
										onclose:function(){
											_edtItem.parent().find(".isEditing").removeClass("isEditing");
										}
									});
									d.showModal();
									that.iniRaty();//初始化遮罩事件
									that.checkRatyForm();
									that.bindBtnEvt(d);
								}
							}else{
								var d = dialog({
								title: '提示信息',
								content:json.data,
								okValue: '确 定',
									ok : function(){
										d.close();
									}
							});
							 d.showModal();
							}
							cur.removeClass("stop-click");
					});
			  });

 				//查看评价
				$(".spr-cmt-trig-look",$$).off("click").on("click",function(){
					var url = $(this).attr("data-url");
					ajax.doAjax("get",url,"",function(json){
						if(json.status==1){
							var data = json.data,
								  score = data.Score.score,
								  remark = data.Score.remark,
								  tanent_list = data.tenant;
							var content = $("#custm-hideTemp",$$).children(".crm-custm-wrap").clone();
							content.find(".lin-custm-basic").remove();
							content.find(".score-tenant-auto").text(score);
							content.find(".remark-tenant-auto").text(remark);
							for(var i in tanent_list){
								var tanentlist = $("#custm-hideTemp",$$).find(".lin-custm-basic").clone(),
									  name = tanent_list[i].name,
								  	  phone = tanent_list[i].phone,
								      idcard = tanent_list[i].idcard;
   									 tanentlist.find(".name-tenant-auto").text(name);
									 tanentlist.find(".phone-tenant-auto").text(phone);
							         tanentlist.find(".idcard-tenant-auto").text(idcard);
							    	 content.find(".col-custm").prepend(tanentlist);
							}
							var  da=dialog({
								title: '<i class="ifont">&#xf0077;</i><span>评价记录</span>',
								content:content
							});
							da.showModal();
							return false;
						}
						var  da=dialog({
									title: '提示',
									content:json.data,
									okValue: '确定',
									ok: function () {
										da.close();
									}
								});
								da.showModal();
					});
				});
			},
		 	/*
		 	 * @func：取消事件绑定
		 	 */
		 	bindBtnEvt:function(d){
		 		$(".close-raty-mask").off().on("click",function(){
		 		   $(".isEditing").removeClass("isEditing");
		 		   $(".clicked").removeClass("clicked");
		 		   $(".jraty-msg").html("");
		 			d.close();
		 		});
		 	},
		 	/*
		 	 * @func：提交表单数据
		 	 */
		 	sendForm:function(form){
		 		var trig=$(form).find("#save-raty-info"),
		 			_target=$("#tb-agmt-lst").find(".isEditing"),
		 			rental_id=[],
		 			score=$(form).find("input[name='score']").val(),
		 			remark=$(form).find("#remark").val(),
		 			url=form.find(".btn2").attr("url");
					if(score>3 && remark.replace(/ /g,"")==""){
						$(form).find("#remark").val(ratyHints[score]);
					}
				$(form).find(".jz_tb").each(function(){
					var tenant_id = $(this).find("input[type='hidden']").val();
					rental_id.push(tenant_id);
				});
		 		if(!trig.hasClass("clicked")){
		 			trig.parent().find(".clicked").removeClass("clicked");
		 			trig.addClass("clicked").text("提交中...");
		 			var data={
		 				rental_id:rental_id,
		 				score:score,
		 				contract_id:_target.find(".spr-cmt-trig").attr("data-id"),
		 				remark:$(form).find("#remark").val()
		 			};
		 			ajaxLoading=ajax.doAjax("post",url,data,function(json){
		 				trig.removeClass("clicked").text("保存");
		 				var msg;
		 				if(json.status==1){
		 					_target.find(".spr-cmt-trig").next().remove();
		 					_target.find(".spr-cmt-trig").remove();
		 					msg="评价成功";
		 				}else{
		 					msg=json.message;
		 				}
	 					var  da=dialog({
							title: '提示',
							content:msg
						});
						da.showModal();
						setTimeout(function () {
						    da.close().remove();
							if(json.status==1){
						    		$(".close-raty-mask").trigger("click");
						    		var tag = WindowTag.getCurrentTag();
						    		window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
							}
						}, 1500);
		 			});
		 		}
		 	},
		 	/*
		 	 * @func 检查评分提交表单
		 	 */
		 	checkRatyForm:function(){
		 		var that=this;
				$(".ratyForm").Validform({
					btnSubmit : "#save-raty-info",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
						var objtip=o.obj;
	               		objtip=objtip.parent(".jzf-col-r").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
						if(objtip.hasClass("Validform_wrong")){
							objtip.parent().show();
						}else{
							objtip.parent().hide();
						}
					},
					callback : function(form){
						$(".ratyForm").find(".check-error").parent().hide();
						var _score=$(".raty").find("input[name='score']").val();
						if(_score=="" ||_score==undefined)
							$(".raty").find("input[name='score']").val("0");
			            	that.sendForm(form);
		            }
				});
				var _inputs=$(".ratyForm").find("textarea");
				$.each(_inputs,function(i,o){
					$(o).focus(function(){
							$(this).css("background","none");
							$(this).parent().find(".check-error").parent().hide();
					}).blur(function(){
						var sco=$(".raty").find("input[name='score']").val();
						if(sco<=3 && $(this).val().replace(/ /g,"")==""){
							$(this).removeAttr("style");
							$(this).parent().find(".check-error").parent().show();
						}
					});
				});
		 	},
		 	/*
		 	 * @func 初始化日期选择插件
		 	 */
		 	iniDatepicker:function(){
				$(".wdate",$$).click(function(){
					var cur=$(this),cv=cur.val();
					if(cv!=""){
						cur.parent().find(".current").removeClass("current");
					}
					dpk.inite();
				});
		 	},
		 	/*
		 	 * @func 初始化评分插件
		 	 */
		 	iniRaty:function(){
				$(".raty",$(".ratyForm")).raty({
		  			 starHalf:path+'star-half-mono.png',
		  			 starOff: path+'star-off.png',
		             starOn: path+'star-on.png',
		             score:0,
		             hints:["","","","",""],
					 mouseover: function(score, evt) {
					 	$(".jraty-msg").html("["+ratyHints[score]+"]");
					 	$(".raty").find("input[name='score']").val(score);
						var _par=$(".ratyForm").find("#remark"),
							_isNecery=$(".ratyForm").find(".isRequired");
						if(score<=3){
							_isNecery.removeClass("none");
							_par.removeAttr("ignore");
								_par.val("");
						}else{
							$(".ratyForm").find(".check-error").parent().hide();
							_isNecery.addClass("none");
							_par.attr("ignore","ignore").removeClass("Validform_error");
							// var _el=$(".ratyForm").find("#remark").val().replace(/ /g,"");
							_par.val(ratyHints[score]);
						}
					 },
					 mouseout:function(score,evt){
//					 	console.log(score);
					 	if(score==undefined){
					 		$(".raty").find("input[name='score']").val("0");
					 		$(".jraty-msg").html("");
					 	}
					 }
				 });
		 	},
		 	/*
		 	 * @func 获取后台返回数据
		 	 */
		 	genList:function(page){
		        $(".tenant_List",$$).addClass("none");
		 		var tp=loading.genLoading("tr","8",1);
		 		$("#tb-agmt-lst",$$).find("tr").not("tr:eq(0)").remove();
		 		$("#tb-agmt-lst",$$).append(tp);
				var that=CustomerList.prototype;
				if(!!!page) page=0;
				page+=1;
				var _exprBeg=$("#exp_bdate",$$).val(),
					_exprEnd=$("#exp_edate",$$).val(),
					_debtBeg=$("#debt_bdate",$$).val(),
					_debtEnd=$("#debt_edate",$$).val(),
					_rentStatus=$(".dpt-rent-status",$$).find(".current").attr("data-type"),
					_roomType=($(".dpt-room-type",$$).find(".current").attr("data-type") !== undefined) ? $(".dpt-room-type",$$).find(".current").attr("data-type") : '',
					_keyWord=$("#keywords",$$).val();
		 		var data="&start_dead_line="+_exprBeg+"&end_dead_line="+_exprEnd+"&start_next_pay_time="+_debtBeg;
		 			data+="&end_next_pay_time="+_debtEnd+"&house_type="+_roomType+"&status="+_rentStatus+"&keywords="+_keyWord+"&page="+page;

				var setting={
				  type:"GET",
				  url:$("#goSearchTrig",$$).attr("actionurl")+data
				};
				ajaxLoading=ajax.doAjax(setting.type,setting.url,"",function(json){
					var len=json.page.count;
					if(json.status==1 && json.data!="" && json.data!=null){
						$(".tenant_List",$$).addClass("none");
		 				ajax.iniPagination(len,"#pagination",that.genList,20,page-1);
		             	$(".jzf-pagination",$$).show();
						$(".lst_tb",$$).find("tr").not("tr:eq(0)").remove();
						$(".lst_tb",$$).append(json.data);
						that.choseAllItems();//绑定全部删除单个选中事件
						that.delTbitem();
						var totlaPage=1;
						if(len>20){
							if(len%10==0){
								totlaPage=parseInt(len/20);
							}else{
								totlaPage=parseInt(len/20)+1;
							}
						}
						if(EVTINITED==0){
							EVTINITED=1;
							that.jumpPage(totlaPage);
						}
					}else{
						$(".lst_tb",$$).find("tr").not("tr:eq(0)").remove();
						var errorTemp=loading.genLoading("tr","8",2);
						//$(".lst_tb",$$).append(errorTemp);
		                $(".jzf-pagination",$$).hide();
		                $(".tenant_List",$$).removeClass("none");
					}
				},"customer_list");
			},
			/*
			 *@func 删除合同
			 * */
			 delCustom:function(){
			 	var  that=this;
				$(".dpt-toolbar-delete",$$).off("click").on("click",function(){
					$(this).parent().slideUp(300);
					$(".dpt-toolbar-active",$$).slideDown(300);
					$(".crm-manage",$$).find(".lst_tb").find(".checkBox").fadeIn(500);
				});
				$(".edit-cancle-trig",$$).off("click").on("click",function(){
					$(this).parent().slideUp(300);
					$(".dpt-toolbar-inactive",$$).children(".dpt-toolbar").slideDown(300);
					$(".crm-manage",$$).find("table").find(".checkBox").fadeOut(500);
				});
				$(".dpt-toolbar-active-delete",$$).off("click").on("click",function(){
						var url = $("#goSearchTrig",$$).attr("delurl");
			   			var type = "get";
			   			var deletelist = [];
			   			$(".lst_tb tr:gt(0)",$$).each(function(){
			   				if($(this).find("input:checked").size() > 0){
			   					var reserve_id = $(this).find("input:checked").val();
			   					var house_name = $(this).find("td:first").children("a").text();
			   					var _eleAuto = {
			   						contract_id : reserve_id,
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
						title: '<i class="ifont">&#xe675;</i><span>删除租客</span>',
						content:cloneStr,
						okValue: '确定',
						ok: function () {
							dd.close();
							if($(".lst_tb",$$).find("tr").length <= 1){
								//刷新当前标签
								var tag = WindowTag.getCurrentTag();
								var url = tag.find('>a:first').attr('url');
								WindowTag.loadTag(url,'get',function(){

								});
							}
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
		   			 var trstr = '<tr><td class="zb">'+objsauto.find("td:first").find("a").text()+'</td><td class="yb">正在删除</td></tr>';
		   			 tableauto.append(trstr);
		   			 if(num_cur > 5){
						tableauto.find("tr:eq("+(num_cur-5)+")").fadeOut(300);
					}
		   			 var trcur = tableauto.find("tr:last");
		   			 var idauto = objsauto.find("input:checked").val();
		   			 var data = {
						contract_id : idauto
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
		  },
			/*
			 *@func:全选/取消全选
			 * */
			choseAllItems:function(){
				$(".dpt-toolbar-active .checkBox",$$).off("click").on("click",function(){
					$(this).toggleClass("checked");
					if($(this).hasClass("checked")){
						$(this).children(".choose").show();
						$(".crm-manage",$$).find("table").find(".checkBox").addClass("checked").children().show();
						$(".crm-manage",$$).find("table").find(".checkBox").next().attr("checked",true);
					}else{
						$(this).children(".choose").hide();
						$(".crm-manage",$$).find("table").find("td .checkBox").removeClass("checked").children().hide();
						$(".crm-manage",$$).find("table").find("td .checkBox").next().removeAttr("checked");
					}
				});
				$(".dpt-toolbar-active a:first",$$).off("click").on("click",function(){
					var obj = $(this).siblings(".checkBox");
					obj.toggleClass("checked");
					if(obj.hasClass("checked")){
						obj.children(".choose").show();
						$(".crm-manage",$$).find("table").find(".checkBox").addClass("checked").children().show();
						$(".crm-manage",$$).find("table").find(".checkBox").next().attr("checked",true);
					}else{
						obj.children(".choose").hide();
						$(".crm-manage",$$).find("table").find("td .checkBox").removeClass("checked").children().hide();
						$(".crm-manage",$$).find("table").find("td .checkBox").next().removeAttr("checked");
					}
				});
				$(".crm-manage",$$).find(".lst_tb").find(".checkBox").off("click").on("click",function(){
					$(this).toggleClass("checked");
					if($(this).hasClass("checked")){
						$(this).children().show();
						$(this).next().attr("checked",true);
					}else{
						$(this).children().hide();
						$(this).next().removeAttr("checked");
					}
				});
			},
			/*
			 *@func 点击搜索
			 * */
			doSearchAct:function(){
				var that=this;
				$("#goSearchTrig",$$).off().on("click",function(){
					$(".jzf-pagination",$$).hide();
					EVTINITED=0;
					that.genList();
				});
			},
			/*
			 *@func 返回对应月份天数
			 * @param y 当期年份 m 月份索引 起始为0
			 * */
			solarDays:function(y,m){
				 if(m==1)
		      		return(((y%4 == 0) && (y%100 != 0) || (y%400 == 0))? 29: 28)
		   		else
		      		return(solarMonth[m])
			},

            /**
             * 日期 转换为 Unix时间戳
             * @param <string> 2014-01-01 20:20:20  日期格式
             * @return <int>        unix时间戳(秒)
             */
			DateToUnix:function(date){
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
			},
			 /**
             * 时间戳转换日期
             * @param <int> unixTime    待时间戳(秒)
             * @param <bool> isFull    返回完整时间(Y-m-d 或者 Y-m-d H:i:s)
             * @param <int>  timeZone   时区
             */
			  UnixToDate: function(unixTime, isFull, timeZone) {
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
		 }
		}
		 new CustomerList();
	}
	/*
	 *@func:页面入口
	 */
	$(function(){
		 //模块方法初始化
		  exports.inite=function($$,data){
		  		pageFun($$,data);
		  };
	});
});
