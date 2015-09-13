define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("radio")($);
		require("placeholder")($);
		require("validForm")($);
		require("combobox")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var	dialog = require("dialog");    //弹窗插件
	var ajax = require("Ajax"); 
	var	dt = require("calendar");
	var hash = require('child_data_hash');
	
	var modelInit = function($$){
		//调用下拉框JS
		$(".selectByM",$$).each(function(){
			if($(this).attr("hasevent")){
					$(this).selectObjM(1,function(val,inp){
						$(".cost-detail-con .selectByM[hasevent='true'] .selectByMO li",$$).show();
						var keys_array = [];
						$(".cost-detail-con",$$).each(function(){
							var _this_key = $(this).find(".selectByM[hasevent='true']",$$).children(".selectByMO").find("li.selectedLi").attr("selectval");
							keys_array.push(_this_key);
						});
						$(".cost-detail-con .selectByM[hasevent='true'] .selectByMO li",$$).each(function(){
							for(var n in keys_array){
								if($(this).attr("selectval") == keys_array[n] && !$(this).hasClass(".selectedLi")){
									$(this).hide();
									break;
								}
							}
						});
					});
				}else{
					$(this).selectObjM();	
				}
		});
		
		//调用单选框
		$('.radio').each(function(){
			$(this).click(function(){
				$(this).Radios();
				if($(this).parents('.radio-box').hasClass('radio-type')){
					$(this).parents('.radio-box').siblings('.pay-time').show();
				}else{
					$(this).parents('.radio-box').siblings('.pay-time').hide();
				};
			})
		});
		
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".view").placeholder();
		};
		
		//初始化日历插件
		$('.date').click(function(){
			dt.inite();
		});

		/**
		 * 动态计算共计应收金额
		 * 2015-05-08
		 * 
		 */
		var getReceivableNum = function(){
			var li = $(".add_expense .view-ul .cost-detail",$$);
			var cost_num_con = li.find('.cost_num',$$);
			var costs = 0;
			cost_num_con.each(function(i,obj){
				var cost_num = $(obj).val();
				if(cost_num == ''){
					cost_num = 0;
				};
				costs += parseFloat(cost_num);
			});
			$(".receivable",$$).text(costs);
			//添加费用时，重新计算
			var key = li.length - 1; 
			if(li.length >= 2){
				var dom = li.eq(key).find('.cost_num',$$);
				dom.blur(function(){
					var costs = 0;
					$(".cost_num",$$).each(function(i, obj){
						var cost_num = $(obj).val();
						if(cost_num == ''){
							cost_num = 0;
						};
						costs += parseFloat(cost_num);
							
					});
					$(".receivable",$$).text(costs);
				});
			};
		};
		getReceivableNum();
		
		/**
		 * 复选框事件
		 * 2015.04.25
		 * 
		 */
		var checkBoxEvt = function(){
			$('.check-box').off('click').on('click',function(){
				var obj = $(this).find("label");
				var city = $(".evt-trig-ele .evt-trig-ele").text();
				obj.toggleClass("checked");
				if(!obj.parents(".check-box").hasClass("li-spe-checkbox")){
					if(obj.hasClass("checked")){
						obj.children(".choose").show();
						obj.next().attr("checked",true);
						obj.parents('.check-box').siblings('input').attr('readonly','readonly').val('');
						obj.parents('.check-box').siblings(".title-room").hide().siblings(".title-city").show();
						obj.parents('.check-box').siblings(".title-room").hide().siblings(".title-city").show();
						obj.parents('.check-box').siblings(".cost-room").hide().siblings(".city-name").show().val(city);
						obj.parents('.check-box').siblings('input').removeClass('Validform_error').siblings('span').hide();
					}else{
						obj.children(".choose").hide();
						obj.next().removeAttr("checked");
						obj.parents('.check-box').siblings('input').removeAttr('readonly');
						obj.parents('.check-box').siblings(".title-room").show().siblings(".title-city").hide();
						obj.parents('.check-box').siblings(".cost-room").show().siblings(".city-name").hide();
						obj.parents('.check-box').siblings('input').addClass('Validform_error').siblings('span').show();
					}
				}else{
					return false;
				}
				
			});
		};
		checkBoxEvt();
		
		/**
		 * 房间搜索
		 * 
		 */
		var houseSelectEvt = function(){
			$('.cost-room',$$).each(function(){
	            var input = $(this);
	            var dom = input.parent('li');
	            input.keyup(function(){
	            	$(this).removeAttr("record_id");
	            });
	            
            	input.combobox({
	                url:input.attr("url"),
	                param_name: 'search',
	                title_name: 'name',
	                commbox_selector: '.commbox',
	                width: 302,
	                result:"data",
	                item_template:'<span>:house_name</span>',
	                //height: 200,
	                min_char:1,
	                padding: 10,
	                offset: { top: 32},
	            callback: {
	                    init:function(obj){},
	                    select: function(obj) {
	                    },
	                    choose: function(obj) {
                    		if(obj && obj!=undefined){
                    			input.val(obj.house_name);
								input.attr({
                    				"house_id": obj.house_id,
                    				"record_id": obj.record_id,
                    				"house_type": obj.house_type,
                    				"sub_tenancy": obj.sub_tenancy
                    			});
                    			input.removeClass("Validform_error").siblings(".check-error").removeClass("Validform_wrong").addClass("Validform_right");
                    			
                    			var url = $(".view-head",$$).attr("dataUrl") + "&house_name=" + obj.house_name;
                    			var type = "GET";
                    			ajax.doAjax(type,url,"",function(json){
                    				if(json.status == 1){
                    					var data = json.data;
	                    				var expense_data = {
											"data": data
										};
										var html = template("cost_mode_expense", expense_data);
										document.getElementById('get_cost_expense').innerHTML = html;
                    					//调用动态计算应收金额
                    					getReceivableNum();
                    					//调用删除费用
										deleteColByBtn();
                    				}
                    				
                    			});
                    		}
                    		input.focus().blur();
	                    },
		                notchoose: function(obj){
		                },
		                notdata: function(obj){
		                	//没有数据清空对应字段
		               	 	$(obj).removeAttr("area_id").removeAttr("house_type",obj.house_type).removeAttr("style");
		                }
	                }
	            });
	        });
		};
		houseSelectEvt();
		
		/**
		 * 添加费用
		 * 
		 */
		$(".add-cost",$$).off('click').on('click',function(){
//			var cost = $('#cost',$$).html();
//			$('.view-ul',$$).append(cost);

			var totalchoice = $("#cost",$$).find(".cost-col-detail .selectByM[hasevent=true]").children(".selectByMO").find("li").size();
			var clone = $("#cost",$$).find(".cost-col-detail").clone();
			clone.removeClass("cost-col-detail").addClass("cost-detail-con").show();
			var choosekey = [];
			$(".cost-detail-con .selectByM[hasevent = 'true']",$$).each(function(){
				var val_auto = $(this).find(".selectByMT").attr("selectVal");
				if(val_auto != "") choosekey.push(val_auto);
			});
			var length_choosekey = choosekey.length;
			clone.find(".selectByM[hasevent = 'true'] .selectByMO li").each(function(){
				var selectval = $(this).attr("selectVal");
				for(var i =0;i<length_choosekey;i++){
					if(selectval == choosekey[i]){$(this).hide(); break;}
				}
			});
			clone.find(".selectByM").each(function(){
				if($(this).attr("hasevent")){
					$(this).selectObjM(1,function(val,inp){
						$(".cost-detail-con .selectByM[hasevent='true'] .selectByMO li",$$).show();
						var keys_array = [];
						$(".cost-detail-con",$$).each(function(){
							var _this_key = $(this).find(".selectByM[hasevent='true']",$$).children(".selectByMO").find("li.selectedLi").attr("selectval");
							keys_array.push(_this_key);
						});
//						console.log(keys_array);
						$(".cost-detail-con .selectByM[hasevent='true'] .selectByMO li",$$).each(function(){
							for(var n in keys_array){
								if($(this).attr("selectval") == keys_array[n] && !$(this).hasClass(".selectedLi")){
									$(this).hide();
									break;
								}
							}
						});
					});
				}else{
					$(this).selectObjM();	
				}
			});
//			clone.find(".forclonedelete").off("click").on("click",function(){
//				$(this).parent().remove();
//				currentchoice = $("#cost",$$).find(".cost-detail-con").size();
//				if(totalchoice > currentchoice) $(".dpt-add-btn-auto",$$).parent().show();
//			});
//			$(this).parent().before(clone);
			$('.view-ul',$$).append(clone);
			var currentchoice = $(".cost-detail-con").size();
			if(totalchoice == currentchoice) $(this).parent().hide();


			//冲账功能
			finance_cost_checkout();
			//下拉
			$(".selectByM",$$).each(function(){
				$(this).selectObjM();
			});
			//调用删除费用
			deleteColByBtn();
			//调用动态计算应收金额
			getReceivableNum();
		});
		//编辑页面
		var auto_checkfee = function (){
			var choosekey = [];
			$(".view-ul",$$).find(".cost-detail-con .selectByM[hasevent=true]").each(function(){
				var val_auto = $(this).find(".selectByMT").attr("selectVal");
				if(val_auto != "") choosekey.push(val_auto);
			});
			$(".view-ul",$$).find(".cost-detail-con .selectByM[hasevent=true]").each(function(){
				$(this).find("li").each(function(i,o){
					if(!$(this).hasClass("selectedLi")){
						var val_auto = $(o).attr("selectVal");
						var length = choosekey.length;
						for(var i=0; i<length; i++){
							if(choosekey[i] == val_auto) {$(o).hide(); break;}
						}	
					}
				});
			});
		}
		auto_checkfee();
		
		/**
		 * 冲账功能弹窗
		 * 
		 */
		var finance_cost_checkout = function(){
	    	$('.checkout').off('click').on('click', function () {
	    		var dialogHtml = $('#hide-checkout').html();
				var d = dialog({
					title: '<i class="ifont1">&#xe778;</i><span>冲账</span>',
					content: dialogHtml
				});
				checkoutSubmitEvt.dialog = d;
				d.showModal();
				var cost_num = $(this).siblings(".cost_num").val();
	    		if(cost_num != ''){
	    			$(".cur_num").val(cost_num);
	    		}
	    		var sn_id = $(this).parents(".cost-detail").attr("sn_id");
	    		var detail_id = $(this).parents(".cost-detail").attr("detail_id");
	    		$(".checkout-con").attr({
	    			"sn_id": sn_id,
	    			"detail_id": detail_id
	    		});
				
				//冲账弹窗表单提交
				checkoutSubmitEvt.checkUI(function(){
					d.close();
				});
				
				//取消按钮
				$(".cancel-btn").off("click").on("click",function(){
					d.close();
				});
			});
		}
		finance_cost_checkout();
		
		/**
		 * 冲账功能弹窗表单提交
		 * 
		 */
		var checkoutSubmitEvt = {
			dialog:null,
			submitForm: function(){
				var sn_id = $(".checkout-con",checkoutSubmitEvt.dialog._popup).attr("sn_id"),
					detail_id = $(".checkout-con",checkoutSubmitEvt.dialog._popup).attr("detail_id"), 
					auth_pwd = $(".auth_pwd",checkoutSubmitEvt.dialog._popup).val(),
					cur_num = $(".cur_num",checkoutSubmitEvt.dialog._popup).val(),
					cost_num = $(".checkout-num",checkoutSubmitEvt.dialog._popup).val(),
					mark = $(".checkout-mark",checkoutSubmitEvt.dialog._popup).val();
				var type = "POST";
				var data = {
					"sn_id": sn_id,
					"detail_id": detail_id,
					"auth_pwd": auth_pwd,
					"cur_num": cur_num,
					"cost_num": cost_num,
					"mark": mark
				};
				var url = $(".checkout").attr("url");
				ajax.doAjax(type,url,data,checkoutSubmitEvt.callback);
			},
			callback: function(msg){
				if(msg.status == 1){
					var d = dialog({
						title: '提示信息',
						content: '保存成功',
						okValue: '确定',
						ok: function(){
							$(".cd-b").hide();
							d.close();
						}
					});
				}else{
					var d = dialog({
						title: '提示信息',
						content: msg.message,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
				}
				d.showModal();
			},
			checkUI: function(callfun){
				$(".checkout-con").Validform({
					btnSubmit : ".checkout-btn",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            datatype : {
		                "checkout_num":function(gets,obj,curform,regxp) {
	                		var reg1=/^(0|[1-9][0-9]*)$/,     //匹配正整数
		                   	    reg2=/^[0-9]+(.[0-9]{1,3})?$/;  //匹配正负浮点数
	                		if(obj.val() == '' || obj.val() == '0'){
		                    	return false;
		                    }else if(reg1.test(obj.val()) == true && reg2.test(obj.val()) == true){
		                    	return true;
		                    };
	                	}
		            },
		            callback: function(){
	            		checkoutSubmitEvt.submitForm();
	            		if(callfun != undefined) callfun();
		            }
				});
			}
		};
		
		/**
		 * 点击删除按钮删除整条费用
		 * 2015.04.25
		 */
		var deleteColByBtn = function(){
			$('.cd-btn',$$).off('click').on('click',function(){
				var that = $(this);
				var sn_id = that.parents(".cost-detail").attr("sn_id");
				var fee_id = that.siblings(".selectByM").find(".cost_type").attr("selectval");
				if(sn_id == '' || sn_id == undefined){
					that.parents(".cost-detail").remove();
				}else{
					var dd = dialog({
						title: '<i class="ifont">&#xe77d;</i><span>删除费用</span>',
						content: '删除的信息将无法得到恢复，确定删除？',
						okValue: '确 定', drag: true,
						ok: function () {
							var type = "post";
							var urlso = $(".cd-btn").attr("url");
							var data = {
								"sn_id": sn_id,
								"fee_id": fee_id
							};
							that.parents(".cost-detail").remove();
							ajax.doAjax(type,urlso,data,function(data){
								if(data.status == 1){
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
							getReceivableNum();
							dd.close();
						},
						cancelValue: '取消',
						cancel: function () {
							dd.close();
						}
					});
					dd.showModal();
				}
			});
		};
		deleteColByBtn();
		
		//录入框获得焦点与失去焦点状态
		$(".cost-time").off("focus").on("focus",function(){
			if($(this).hasClass("Validform_error")){
				$(this).css("background","none");
				$(".check-error").hide();
			}
		});
		$(".cost-time").off("blur").on("blur",function(){
			$(this).removeAttr("style");
			if($(this).hasClass("Validform_error")){
				$(this).siblings(".check-error").show();	
			}
		});
		
		/**
		 * 添加支出流水表单提交
		 * 2015.04.25
		 * 
		 */
		var addIncome = {
			submitForm: function(){
				var room = $(".cost-room",$$).val(),                              //房间
					time = $(".cost-time",$$).val(),                              //支付时间 
					pay_ways = $(".cost-ways",$$).val(),                          //支付方式 
					mark = $(".i-txt",$$).val(),								  //备注
					
					receivable = $(".receivable",$$).text(),
					sn_id = $(".cost-detail",$$).eq(1).attr("sn_id"),
//					reserve_id = $(".cost-detail",$$).eq(1).attr("reserve_id"),
//					room_id = $(".cost-detail",$$).eq(1).attr("room_id"),
//					house_type = $(".cost-detail",$$).eq(1).attr("house_type"),
//					house_id = $(".cost-detail",$$).eq(1).attr("house_id"),
//					house_category = $(".cost-room",$$).attr("house_type"),    //房源类型
//					house_cate_id = $(".cost-room",$$).attr("house_id"),    //房源ID
//					record_id = $(".cost-room",$$).attr("record_id"),
					sub_tenancy = $(".add-expense-btn",$$).attr("sub_tenancy");
					if($("#li-spe .cost-room",$$).attr("sub_tenancy") != '' && $("#li-spe .cost-room",$$).attr("sub_tenancy") != undefined){
						sub_tenancy = $("#li-spe .cost-room",$$).attr("sub_tenancy");
					}
				var costs = [];
				$(".cost_type",$$).each(function(){
					var cost_type = $(this).attr("selectVal"),
						cost_num = $(this).parents("li").find(".cost_num").val(),
						type_name = $(this).val(),
						sn_id = $(this).parents(".cost-detail").attr("sn_id");
					var	cost = {};
					if(cost_type != ''){
						cost.cost_type = cost_type;
					}
					if(cost_num != ''){
						cost.cost_num = cost_num;
					}
					if(type_name != ''){
						cost.type_name = type_name;
					}
					if(sn_id != ''){
						cost.sn_id = sn_id;
					}
					costs.push(cost);
				});
				var type = "POST";
				var url = $(".add-expense-btn",$$).attr("url");
				var data = {
					"time": time,
					"pay_ways": pay_ways,
					"cost": costs,
					"receivable": receivable,
					"mark": mark,
					"sub_tenancy": sub_tenancy
//					"house_category": house_category,
//					"house_cate_id": house_cate_id,
//					"record_id": record_id
				};
				if(sn_id != ''){
					data.sn_id = sn_id;
				}	
//				if(reserve_id != ''){
//					data.reserve_id = reserve_id;
//					data.room_id = room_id;
//					data.house_type = house_type;
//					data.house_id = house_id;
//				}
//				if(sub_tenancy != ''){
//					data.sub_tenancy = sub_tenancy;
//					data.house_type = $(".add-expense-btn",$$).attr("house_type");
//					data.house_id = $(".add-expense-btn",$$).attr("house_id");
//					data.room_id = $(".add-expense-btn",$$).attr("room_id");
//					data.room_focus_id = $(".add-expense-btn",$$).attr("room_focus_id");
//					data.contract_id = $(".add-expense-btn",$$).attr("contract_id");
//				}
				if($("#li-spe .check-box label").hasClass('checked')){
					data.not_room_serial = 1;
				}else{
					data.not_room_serial = 0;
					data.room = room;
				}
				
				if(sub_tenancy == 'owner_contract'){
					data.contract_id = $(".add-expense-btn",$$).attr("contract_id");
					data.house_type = $(".add-expense-btn",$$).attr("house_type");
					data.house_id = $(".add-expense-btn",$$).attr("house_id");
				}else if(sub_tenancy == 'out_tenancy'){
					data.house_id = $(".add-expense-btn",$$).attr("house_id");
					data.room_id = $(".add-expense-btn",$$).attr("room_id");
					data.room_focus_id = $(".add-expense-btn",$$).attr("room_focus_id");
					data.house_type = $(".add-expense-btn",$$).attr("house_type");
				}else if(sub_tenancy == 'landlord_con'){
					data.contract_id = $(".add-expense-btn",$$).attr("contract_id");
					data.house_id = $(".add-expense-btn",$$).attr("house_id");
					data.house_type = $(".add-expense-btn",$$).attr("house_type");
				}else if(sub_tenancy == 'lc_pay'){
					data.contract_id = $(".add-expense-btn",$$).attr("contract_id");
					data.house_type = $(".add-expense-btn",$$).attr("house_type");
				}else if(sub_tenancy == 'unsubscribe'){
					data.house_id = $(".add-expense-btn",$$).attr("house_id");
					data.room_id = $(".add-expense-btn",$$).attr("room_id");
					data.house_type = $(".add-expense-btn",$$).attr("house_type");
					data.reserve_id = $(".add-expense-btn",$$).attr("reserve_id");
				}else if(sub_tenancy == 'add'){
					data.record_id = $(".cost-room",$$).attr("record_id");
					data.house_type = $(".cost-room",$$).attr("house_type");
					data.house_id = $(".cost-room",$$).attr("house_id");
				}else if(sub_tenancy == 'tc_stop_contract'){
					data.tc_contract_id = $(".add-expense-btn",$$).attr("tc_contract_id");
					data.house_id = $(".add-expense-btn",$$).attr("house_id");
					data.room_id = $(".add-expense-btn",$$).attr("room_id");
					data.house_type = $(".add-expense-btn",$$).attr("house_type");
				}else if(sub_tenancy == 'editExpense'){
					data.sn_id = $(".add-expense-btn",$$).attr("sn_id");
				}
				
				
				ajax.doAjax(type,url,data,addIncome.callback);
				
			},
			callback: function(msg){
				if(msg.status == 1){
					var d = dialog({
						title: '提示信息',
						content: '保存成功',
						okValue: '确定',
						ok: function(){
							d.close();
							//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							if(typeof msg['url'] == 'string'){
			    				window.WindowTag.openTag('#'+msg.url);
			    			}else if(typeof msg['tag'] == 'string'){
			    				var ctag = WindowTag.getTagByUrlHash(msg['tag']);
			    				if(ctag){
			    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('url'));
			    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('url'),'get',function(){});
			    				}else{
			    					window.WindowTag.openTag('#'+msg.tag);
			    				}
			    			}
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
						}
					});
					d.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
				}else{
					$(".add-expense-btn",$$).attr('md','md');
					var d = dialog({
						title: '提示信息',
						content: msg.message,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
				}
			},
			checkUI: function(){
				$(".add_expense",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					ignoreHidden : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		           },
		           datatype: {
		           		"cost_room":function(gets,obj,curform,regxp) {
		           			if($.trim(gets) == ""){
		           				return false;
		           			}else if(typeof obj.attr("record_id") == "undefined" && $.trim(gets) != ""){
	                    		return "请选择房源，若房源不存在，请先添加";
	                    	}
		                },
			           	"destine-time":function(gets,obj,curform,regxp) {
			           		if($(".radio-box .checked").next("input").attr("data-type") == 2){
			           			if(obj.val() == '' || obj.val() == obj.attr("placeholder")){
			                    	return '请填写预约缴费时间';
			                    }
			           		}
		                },
		                "cost_type":function(gets,obj,curform,regxp) {
			           		if($(".cost_type").length >= 2){
			           			if(obj.attr("selectval") == ''){
			           				return '请选择费用类型';
			           			}
			           		}
		                },
		                "cost_num":function(gets,obj,curform,regxp) {
		           			var reg1 = /^(0|[1-9][0-9]*)$/,
		                   	    reg2 = /^[0-9]+(.[0-9]{1,3})?$/;
	                		if(obj.val() == '' || obj.val() == '0'){
		                    	return false;
		                    }else if(reg1.test(obj.val()) == false && reg2.test(obj.val())==false){
		                    	return false;
		                    }else if(obj.val() >= 10000000){
		                    	return '金额太大，无法保存';
		                    }else{
		                    	return true;
		                    }
		                },
		                "has_collect":function(gets,obj,curform,regxp) {
		                	if($.trim(gets) == ""){
	                			return false;
	                		}else{
	                			var reg1 = /^([1-9][0-9]*)$/;          //匹配正整数
		                   	   	var	reg2 = /^[0-9]+(.[0-9]{1,5})?$/;   //匹配正负浮点数
		                		if(reg1.test(gets)==true){
			                   		return true;
			                    }else if(gets.indexOf(".")!='-1'){
			                   		if(reg2.test(gets)== true){
			                   			return true;
			                   		}else{
			                   			return false;
			                   		} 
			                    }else{
			                   		return false;
			                   	}
	                		}
		                },
		                "costWays":function(gets,obj,curform,regxp) {
	                		if(obj.attr("selectVal") == '' || obj.attr("selectVal") == 0){
		                    	return '请选择支付方式';
		                    }
		                }
		           },
		           callback: function(){
		           		if($(".btn2",$$).hasClass("clicked")) return false;
		           		$(".btn2",$$).addClass("clicked");
		           		var receivable = parseInt($.trim($(".receivable",$$).html()));
		           		var res = hash.hash.ischange("add_expense",$(":first",$$));
		           		if(receivable == 0){
		           			var d = dialog({
								title: '提示信息',
								content: '请添加费用',
								okValue: '确定',
								ok: function(){
									d.close();
								}
							});
							d.showModal();
							$(".btn2",$$).removeClass("clicked");
						}else if(receivable >= 10000000){
							var d = dialog({
								title: '提示信息',
								content: '金额太大，无法保存',
								okValue: '确定',
								ok: function(){
									d.close();
								}
							});
							d.showModal();
							$(".btn2",$$).removeClass("clicked");
		           		}
//						else if(res === false){
//							var d = dialog({
//								title: '提示信息',
//								content: '当前数据没有任何修改',
//								okValue: '确定',
//								ok: function(){
//									d.close();
//								}
//							});
//							d.showModal();
//							$(".btn2",$$).removeClass("clicked");
//		           		}
						else{
		           			addIncome.submitForm();
		           		}
		           }
				});
			}
		};
		addIncome.checkUI();
		
		//取消
		hash.hash.savehash("add_expense",$(":first",$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('add_income',$(':first',$$)) == true){
				var d = dialog({
							title: '提示信息',
							content:'数据已发生修改，确认取消？',
							okValue: '确定',
							ok: function () {
								d.close();
								//关闭当前标签
								var tag = WindowTag.getCurrentTag();
								WindowTag.closeTag(tag.find('>a:first').attr('href'));
							},
							cancelValue: '取消',
							cancel: function () {
								
							}
						});
						d.showModal();
			}else{
				//关闭当前标签
				var tag = WindowTag.getCurrentTag();
				WindowTag.closeTag(tag.find('>a:first').attr('href'));
			}
		});
		
	};
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);

	}; 
	 
	 
});