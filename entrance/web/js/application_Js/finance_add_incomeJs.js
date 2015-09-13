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
	var hash = require("child_data_hash");
	
	
	
	
	var modelInit = function($$){
		var receivable_val = parseInt($.trim($(".receivable",$$).html()));
		
		
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
//				var has_collect = $(".has-collect").val();
//				var receivable = $(".receivable").text();
//				if(has_collect == '' || has_collect == NaN){
//					var d = dialog({
//						title: '提示信息',
//						content: '请填写已收金额',
//						okValue: '确定',
//						ok: function(){
//							d.close();
//						}
//					});
//					d.showModal();
//				}else if(has_collect != '' && parseInt(has_collect) < parseInt(receivable)){
					$(this).Radios();
					if($(this).parents('.radio-box').hasClass('radio-type')){
						$(this).parents('.radio-box').siblings('.pay-time').show();
					}else{
						$(this).parents('.radio-box').siblings('.pay-time').hide();
					};
//				}else{
//					var d = dialog({
//						title: '提示信息',
//						content: '点错了吧，钱已经收够了啊！',
//						okValue: '确定',
//						ok: function(){
//							d.close();
//						}
//					});
//					d.showModal();
//				}
			});
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
		 * 根据已填金额判断是否有差额减免或加入欠费清单
		 * 2015-06-04
		 * 
		 */
		$(".has-collect").keyup(function(){
			var that = $(this);
			var receivable = that.siblings(".receivable").text();
			if(parseFloat(that.val()) < parseFloat(receivable)){
				$(".operate-box",$$).fadeIn(300);
			}else{
				$(".operate-box",$$).fadeOut(300);
			}
		});
		
		
		
		/**
		 * 编辑时，根据冲账金额判断差额减免或加入欠费清单是否显示
		 * 2015-8-7
		 * 
		 */
//		$(".fee_offset").change(function(){
//			alert(1);
//		});
		
		
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
	            
	            $(this).keyup(function(){
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
                    				"sub_type": obj.sub_type
                    			});
								input.removeClass("Validform_error").siblings(".check-error").removeClass("Validform_wrong").addClass("Validform_right");
                    			
                    			var url = $(".view-head",$$).attr("dataUrl") + "&house_name=" + obj.house_name + "&house_id=" + obj.house_id + "&record_id=" + obj.record_id + "&house_type=" + obj.house_type + "&rental_way=" + obj.rental_way;
                    			var type = "GET";
                    			ajax.doAjax(type,url,"",function(json){
                    				if(json.status == 1){
                    					var data = json.data;
	                    				var finance_data = {
											"data": data
										};
										var html = template("cost_mode_income", finance_data);
										document.getElementById('get_cost_income').innerHTML = html;
										//调用动态计算金额
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
		               	 	$(obj).removeAttr("area_id").removeAttr("house_type").removeAttr("style");
		                }
	                }
	            });
	        });
		};
		houseSelectEvt();
		
		/**
		 * 冲账功能弹窗
		 * 
		 */
		var currentCheckout = null;
		var finance_cost_checkout = function(){
	    	$('.checkout').off('click').on('click', function () {
	    		currentCheckout = this;
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
		 * 判断冲账数据是否存在
		 * 2015-06-04
		 * 
		 */
		if($(".fee_offset") && $(".fee_offset").parent().attr('flag','edit')){
			$(".fee_offset").parent().addClass('cd-bb');
		}
		
		/**
		 * 冲账功能弹窗表单提交
		 * 
		 */
		var checkoutSubmitEvt = {
			dialog:null,
			submitForm: function(){
				var sn_id = $(".checkout-con").attr("sn_id"),
					detail_id = $(".checkout-con").attr("detail_id"), 
					auth_pwd = $(".auth_pwd",checkoutSubmitEvt.dialog._popup).val(),
					cur_num = $(".cur_num",checkoutSubmitEvt.dialog._popup).val(),
					cost_num = $(".checkout-num",checkoutSubmitEvt.dialog._popup).val(),
					mark = $(".checkout-mark",checkoutSubmitEvt.dialog._popup).val();
				var type = "POST",data;
				data = {
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
							d.close();
							$(currentCheckout).addClass("none").siblings(".yellow").addClass("none");
							$(currentCheckout).parents(".cd-t").siblings(".cd-b").fadeIn(300);
							$(currentCheckout).parents(".cd-t").siblings(".cd-b").children(".fee_offset").val(msg.cost_num);
							var tag = WindowTag.getCurrentTag();
							//刷新标签
							WindowTag.loadTag(tag.find('>a:first').attr("href"),'get',function(){});
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
		 * 动态计算共计应收金额
		 * 2015-05-08
		 * 
		 */
		var getReceivableNum = function(){
			var li = $(".add_income .view-ul .cost-detail",$$);
			var cost_num_con = li.find('.cost_num',$$);
			var fee_offset_obj = li.find('.chz_record', $$);
			var flag = $(".has-collect",$$).attr("flag");
			var total = 0;
			var costs = 0;
			var mon = 0;
			fee_offset_obj.each(function(i,obj) {
				var fee_offset = $(obj).val();
				if(fee_offset == '' || fee_offset == undefined){
					fee_offset = 0;
				}
				mon += parseFloat(fee_offset);
			});
			cost_num_con.each(function(i,obj){
				var cost_num = $(obj).val();
				if(cost_num == ''){
					cost_num = 0;
				};
				costs += parseFloat(cost_num);
			});
			total = parseFloat(costs) - parseFloat(mon);
			$(".receivable",$$).text(total);
			if(flag == "" || flag == undefined){
				$(".has-collect").val(total);
			}
			//添加费用时，重新计算
			if(li.length >= 2){
				fee_offset_obj.blur(function() {
					var mon = 0;
					fee_offset_obj.each(function(i, obj) {
						var record = $(obj).val();
						if (record == '') {
							record = 0;
						}
						mon += parseFloat(record);
					});
					total = parseFloat(costs) - mon;
					$(".receivable",$$).text(total);
				});
				cost_num_con.blur(function(){
					var costs = 0;
					$(".cost_num",$$).each(function(i, obj){
						var cost_num = $(obj).val();
						if(cost_num == ''){
							cost_num = 0;
						};
						costs += parseFloat(cost_num);
					});
					total = parseFloat(costs) - mon;
					$(".receivable",$$).text(total);
					if(flag == "" || flag == undefined){
						$(".has-collect").val(total);
					}
				});
			};
			//判断是否冲账
			var _receivable = parseInt($.trim($(".receivable",$$).html()));
			var has_collect = $(".has-collect",$$).val();
			if(receivable_val != _receivable){
				if(has_collect >= _receivable){
					$(".operate-box").hide();
				}
			}
			
			
		};
		getReceivableNum();
		
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
		 * 点击删除按钮删除整条费用
		 * 2015.04.25
		 */
		var deleteColByBtn = function(){
			$('.cd-btn',$$).off('click').on('click',function(){
				var that = $(this);
				var sn_id = that.parents(".cost-detail").attr("sn_id");
				var fee_id = that.siblings(".selectByM").find(".cost_type").attr("selectval");
				var fee_money = that.siblings(".cost_num").val();
				var total_money = $(".has-collect",$$).val();

				if(sn_id == '' || sn_id == undefined){
					that.parents(".cost-detail").remove();
					getReceivableNum();
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
								"fee_id": fee_id,
								"fee_money": fee_money,
								"total_money": total_money
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
											getReceivableNum();
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
//			$(this).removeAttr("style");
			if($(this).hasClass("Validform_error")){
				$(this).siblings(".check-error").show();	
			}
		});
		
		
		/**
		 * 添加收入流水表单提交
		 * 2015.04.25
		 * 
		 */
		var addIncome = {
			submitForm: function(){
				var room = $(".cost-room",$$).val(),                              //房间
					time = $(".cost-time",$$).val(),                              //支付时间 
					pay_ways = $(".cost-ways",$$).val(),                          //支付方式 
					mark = $(".i-txt",$$).val(),								  //备注
					collect = $(".has-collect",$$).val(),                         //已收费用
					dispose_ways = $(".radio-box .checked",$$).next("input",$$).attr("data-type"),   //处理方式
					receivable = $(".receivable",$$).text(),
					sub_type = $(".add-income-btn",$$).attr("sub_type"),
					sn_id = $(".cost-detail",$$).eq(1).attr("sn_id"),
					detail_id = $(".cost-detail",$$).eq(1).attr("detail_id");
					if($("#li-spe .cost-room",$$).attr("sub_type") != '' && $("#li-spe .cost-room",$$).attr("sub_type") != undefined){
						sub_type = $("#li-spe .cost-room",$$).attr("sub_type");
					}
//					reserve_id = $(".cost-detail").eq(1).attr("reserve_id"),
//					room_id = $(".cost-detail").eq(1).attr("room_id"),
//					house_type = $(".cost-detail").eq(1).attr("house_type"),
//					house_id = $(".cost-detail").eq(1).attr("house_id"),
//					house_category = $(".cost-room").attr("house_type"),    //房源类型
//					house_cate_id = $(".cost-room").attr("house_id"),    //房源ID
//					record_id = $(".cost-room").attr("record_id");
//					contract_id = $(".cost-detail").eq(1).attr("contract_id"),
//					con_house_type = $(".cost-detail").eq(1).attr("con_house_type");
				var	destine;
				if(dispose_ways == 1){
					destine == 0;
				}else{
					destine = $(".destine-time",$$).val();   //预约缴费
				}
				var costs = [];
				$(".cost_type",$$).each(function(){
					var cost_type = $(this).attr("selectVal"),
						cost_num = $(this).parents("li").find(".cost_num",$$).val(),
						type_name = $(this).val(),
						sn_id = $(this).parents(".cost-detail",$$).attr("sn_id"),
						detail_id = $(this).parents(".cost-detail-con").attr("detail_id"),
						new_record = $(this).parents("li").find(".chz_record",$$).val();
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
					if (detail_id !== undefined) {
						cost.detail_id = detail_id;
					}
					cost.new_record = new_record;
					costs.push(cost);
				});
				var type = "POST";
				var url = $(".add-income-btn",$$).attr("url");
				var data = {
					"time": time,
					"pay_ways": pay_ways,
					"collect": collect,
					"dispose_ways": dispose_ways,
					"destine": destine,
					"cost": costs,
					"receivable": receivable,
					"mark": mark,
					"sub_type": sub_type
				};
				if($("#li-spe .check-box label",$$).hasClass('checked')){
					data.not_room_serial = 1;
				}else{
					data.not_room_serial = 0;
					data.room = room;
				}				
				if(sn_id != ''){
					data.sn_id = sn_id;
					data.detail_id = detail_id;
				}
				if(sub_type == 'debts'){
					data.debts_id = $(".add-income-btn",$$).attr("debts_id");
				}else if(sub_type == 'meter'){
					data.house_id = $(".add-income-btn",$$).attr("house_id");
					data.room_id = $(".add-income-btn",$$).attr("room_id");
					data.me_house_type = $(".add-income-btn",$$).attr("me_house_type");
				}else if(sub_type == 'collect_rents'){
					data.house_type = $(".add-income-btn",$$).attr("house_type");
					data.house_id = $(".add-income-btn",$$).attr("house_id");
					data.room_id = $(".add-income-btn",$$).attr("room_id");
					data.index_tenant_con_id = $(".add-income-btn",$$).attr("index_tenant_con_id");
				}else if(sub_type == 'room_reserve'){
					data.reserve_id = $(".add-income-btn",$$).attr("reserve_id");
					data.reserve_source = $(".add-income-btn",$$).attr("reserve_source");
					data.room_id = $(".add-income-btn",$$).attr("room_id");
					data.house_type = $(".add-income-btn",$$).attr("house_type");
					data.house_id = $(".add-income-btn",$$).attr("house_id");
				}else if(sub_type == 'tenant_contract'){
					data.contract_id = $(".add-income-btn",$$).attr("contract_id");
					data.con_house_type = $(".add-income-btn",$$).attr("con_house_type");
				}else if(sub_type == 'editIncome'){
					data.sn_id = $(".add-income-btn",$$).attr("sn_id");
					data.room_id = $(".add-income-btn",$$).attr("room_id");
					data.house_type = $(".add-income-btn",$$).attr("house_type");
					data.house_id = $(".add-income-btn",$$).attr("house_id");
				}else if(sub_type == 'add'){
					data.record_id = $(".cost-room",$$).attr("record_id");
					data.house_type = $(".cost-room",$$).attr("house_type");
					data.house_id = $(".cost-room",$$).attr("house_id");
				}else if(sub_type == 'end_lc_contract'){
					data.house_type = $(".add-income-btn",$$).attr("house_type");
					data.lc_contract_id = $(".add-income-btn",$$).attr("lc_contract_id");
				}else if(sub_type == 'room_charge'){
					data.house_type = $(".add-income-btn",$$).attr("house_type");
					data.tc_contract_id = $(".add-income-btn",$$).attr("tc_contract_id");
					data.house_id = $(".add-income-btn",$$).attr("house_id");
					data.room_id = $(".add-income-btn",$$).attr("room_id");
					data.con_detail = $(".add-income-btn",$$).attr("con_detail");
				}
				
				ajax.doAjax(type,url,data,addIncome.callback);
				
			},
			callback: function(msg){
				if(msg.status == 1){
					$(".btn2",$$).removeClass("clicked");
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
			    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
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
					$(".add-income-btn",$$).attr('md','md');
					var d = dialog({
						title: '提示信息',
						content: msg.data,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
//					setTimeout(function(){
//						d.close().remove();
//					},1200);
				}
			},
			checkUI: function(){
				$(".add_income",$$).Validform({
					btnSubmit : ".add-income-btn",
					showAllError : true,
					ignoreHidden : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		           },
		           datatype: {
		           		"cost_room":function(gets,obj,curform,regxp) {
	              			if(obj.siblings(".check-box").find("label").hasClass("checked")){
		                    	return true;
		                    }else{
		                    	if($.trim(gets) == ""){
			           				return false;
			           			}else if(typeof obj.attr("record_id") == "undefined" && $.trim(gets) != ""){
		                    		return "请选择房源，若房源不存在，请先添加";
		                    	}
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
		           			var reg1 = /^(0|[1-9][0-9]{0,7})$/,
		                   	    reg2 = /^(0|[1-9][0-9]{0,7})(\.[0-9]{1,2})?$/;
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
			                    }else if(obj.val() >= 10000000){
		                    		return '金额太大，无法保存';
		                   		 }else{
			                   		return false;
			                   	}
	                		}
		                },
		                "costWays":function(gets,obj,curform,regxp) {
	                		if(obj.attr("selectVal") == '' || obj.attr("selectVal") == 0){
		                    	return '请选择支付方式';
		                    }
		                },
		                
		           },
		           callback: function(){
		           		if($(".btn2",$$).hasClass("clicked")) return false;
		           		$(".btn2",$$).addClass("clicked");
		           		var receivable = parseInt($.trim($(".receivable",$$).html()));
		           		var res = hash.hash.ischange("add_income",$(":first",$$));
		           		var has_collect = $(".has-collect",$$).val();
		           		var dispose_ways = $(".radio-box .checked",$$).next("input",$$).attr("data-type");
		           		if(has_collect >= receivable){
		           			$(".operate-box").hide();
		           		}
		           		if(receivable == 0 && $(".cost-detail",$$).length == 2){
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
						}else if(receivable == 0 && $(".cost-detail",$$).length > 2){
		           			var d = dialog({
								title: '提示信息',
								content: '费用为0，无法新增',
								okValue: '确定',
								ok: function(){
									d.close();
									var tag = WindowTag.getCurrentTag();
									WindowTag.closeTag(tag.find('>a:first').attr('url'));
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
		hash.hash.savehash("add_income",$(":first",$$));
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