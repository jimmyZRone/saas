define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("radio")($);
		require("validForm")($);
		require("combobox")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	var uplodify = require("uplodify");   //上传图片插件
	var dt = require("calendar");
	var hash = require("child_data_hash");
	
	var modelInit = function($$){
		//付款发生变化时，清空银行卡号
		var bankChance={
			cb: function(){
				$("input[name = 'landlord_card_num']").val('');
			}
			
		};
		
		//调用下拉框JS
		$(".selectByM",$$).each(function(){
			$(this).selectObjM();
			if($(this).find(".selectedLi").size() > 0){
				var text_val = $(this).find(".selectedLi").text();
				var val = $(this).find(".selectedLi").attr("selectval");
				$(this).children(".selectByMT").val(text_val).attr("selectval",val);
			}
		});
		
		
		$(".slt-200",$$).each(function(){
			if($(this).attr("hasevent")){
				$(this).selectObjM(2,bankChance.cb);
			}else{
				$(this).selectObjM(2);
			}
			
		});
		//调用单选框
		$(".radio-box").off("click").on("click",function(){
			var obj = $(this).find("label");
			obj.addClass("checked").parent().siblings('.radio-box').find('label').removeClass('checked');
			obj.find(".r-default").hide().parents(".radio-box").siblings(".radio-box").find(".r-default").show();
			obj.find(".r-select").show().parents(".radio-box").siblings(".radio-box").find(".r-select").hide();
			obj.siblings("input").attr("checked",true).parents(".radio-box").siblings(".radio-box").find("input").attr("checked",false);
			if(obj.parents('.radio-box').hasClass('radio-type')){
				obj.parents('.radio-box').siblings('.word').show().siblings('.sign').hide();
				obj.parents('.house-type').siblings('.house-centralized').hide().siblings('.house-distributed').show();
				obj.parents('.radio-box').siblings('.auto-money').show();
			}else{
				obj.parents('.radio-box').siblings('.sign').show().siblings('.word').hide();
				obj.parents('.house-type').siblings('.house-centralized').show().siblings('.house-distributed').hide();
				obj.parents('.radio-box').siblings('.auto-money').hide();
			};
			//消息搜索
			houseSelectEvt();
		});
		
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".view").placeholder();
		};
		
		//初始化日历插件
		$('.date',$$).on("click",function(){
			dt.inite();
		});
		var minDateCache=null;
		$(".maxWdate",$$).click(function(){
			var cur=$(this),minDate=cur.val(),maxDate=cur.attr("max_date");
			if(minDateCache==null) minDateCache=minDate;
			dt.inite({
			 	dateFmt:'yyyy-MM-dd',
				minDate:minDateCache,
				maxDate:maxDate
			});
		});
		
		/**
		 * 计算两个时间相差年数
		 * 
		 */
		var yearDifference = function(time1,time2){
			var oDate1;
			var oDate2;
			var days;
			var strSeparator = "-";
			oDate1 = time1.split(strSeparator);
	   		oDate2 = time2.split(strSeparator);
	   		var year1 = parseInt(oDate1[0]),
	   			month1 = parseInt(oDate1[1]),
	   			day1 = parseInt(oDate1[2]),
	   			year2 = parseInt(oDate2[0]),
	   			month2 = parseInt(oDate2[1]),
	   			day2 = parseInt(oDate2[2]);
	   		var year = year2 - year1;
	   		if(year > 0){
	   			if(month1 > month2 || month1 == month2 && day1 > day2){
	   				year = year;
	   			}else if(month1 < month2 || month1 == month2 && day1 < day2){
	   				year = year + 1;
	   			}
	   		}
	   		return year;
		};
		
		/**
		 * 根据合同起始判断是否有租金递增功能
		 * 2015-05-08
		 * 
		 */
		$(".contract_start, .contract_end").blur(function(){
			var start_time = $("input[name = 'landlord_start_time']",$$);
			var end_time = $("input[name = 'landlord_end_time']",$$);
			if(start_time.val() != '' && start_time.val() != start_time.attr('placeholder') && end_time.val() != '' && end_time.val() != end_time.attr('placeholder')){
				var year = yearDifference(start_time.val(),end_time.val());
				if(year > 1){
					$(".landlord-rent .check-box").fadeIn(300);
				}else{
					$(".landlord-rent .check-box").fadeOut(300);
					$(".landlord-rent .rent-con").fadeOut(300);
				}
			};
		});
		
		/**
		 * 判断提前付款天数是否错误
		 * 2015-05-08
		 * 
		 */
		$(".advance-pay").blur(function(){
			var days = $(this);
			var landlord_pay_num = $("input[name = 'landlord_pay_num']").attr("selectval");
			if(landlord_pay_num != '' && days.val() != '' && days.val() != days.attr('placeholder')){
				var days_num = parseInt(landlord_pay_num)*30;
				if(days_num < days.val()){
					var d = dialog({
						title: '提示信息',
						content: '提前付款天数无效',
						okValue: '确定',
						ok: function(){
							d.close();
							days.val('');
						}
					});
					d.showModal();
				};
			};
		});
		
		//日期转换成时间戳
		function datetime_to_unix(datetime){ 
		    var tmp_datetime = datetime.replace(/:/g,'-'); 
		    tmp_datetime = tmp_datetime.replace(/ /g,'-'); 
		    var arr = tmp_datetime.split("-"); 
		    var now = new Date(Date.UTC(arr[0],arr[1]-1,arr[2])); 
		    return parseInt(now.getTime()/1000); 
		}
		/**
		 * 判断免房租期是否有效
		 * 2015-06-01
		 * 
		 */
		$(".reduce-term, input[name = 'landlord_start_time'], input[name = 'landlord_end_time']").blur(function(){
			var reduce_term = $(".reduce-term").val();
			var start_time = $("input[name = 'landlord_start_time']",$$).val();
			var end_time = $("input[name = 'landlord_end_time']",$$).val();
			if(start_time != '' && end_time != '' && reduce_term != ''){
				var m1 = parseInt(start_time.split("-")[1].replace(/^0+/, "")) + parseInt(start_time.split("-")[0]) * 12;
                var m2 = parseInt(end_time.split("-")[1].replace(/^0+/, "")) + parseInt(end_time.split("-")[0]) * 12;
				var month = m2 - m1;
				var dm1= datetime_to_unix(start_time);
				var dm2 = datetime_to_unix(end_time);
				var day = (dm2 - dm1)/86400;
				if(month < reduce_term && $(".mzq-dw",$$).attr("selectval") == 'month'){
					var d = dialog({
						title: '提示信息',
						content: '免房租期月数无效',
						okValue: '确定',
						ok: function(){
							d.close();
							$(".reduce-term").val('');
						}
					});
					d.showModal();
				}else if(day < reduce_term && $(".mzq-dw",$$).attr("selectval") == "day"){
					var d = dialog({
						title: '提示信息',
						content: '免房租期日数无效',
						okValue: '确定',
						ok: function(){
							d.close();
							$(".reduce-term").val('');
						}
					});
					d.showModal();
				}
			}
		});
		
		/**
		 * 自动填写收款人
		 * 2015-06-03
		 * 
		 */
		$("input[name = 'landlord_name']",$$).blur(function(){
			if($(this).val() != ''){
				$("input[name = 'landlord_payee']").val($(this).val());
			}
		});
		
		
		/**
		 * 自动计算押金
		 * 
		 */
		$("input[name = 'landlord_rent'], input[name = 'landlord_bet_num']",$$).blur(function(){
			var landlord_rent = $("input[name = 'landlord_rent']",$$).val();
			var landlord_bet_num = $("input[name = 'landlord_bet_num']",$$).attr('selectval');
			var landlord_deposit = $("input[name = 'landlord_deposit']",$$);
			if(landlord_deposit.hasClass('cl-spe')){
				if(landlord_rent != '' && landlord_bet_num != ''){
					landlord_deposit.val(landlord_rent*landlord_bet_num);
				}
			}
			
		});
		
		/**
		 * 复选框事件
		 * 
		 */
		var checkBoxEvt = function(){
			$('.check-box',$$).off('click').on('click',function(){
				var obj = $(this).find("label");
				var start_time = $("input[name = 'landlord_start_time']",$$).val();
				var end_time = $("input[name = 'landlord_end_time']",$$).val();
				if(start_time == '' || end_time == ''){
					var d = dialog({
						title: '提示信息',
						content: '请填写合同起始时间',
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();				
				}else{
					obj.toggleClass("checked");
					if(obj.hasClass("checked")){
						obj.children(".choose").show();
						obj.next().attr("checked",true);
						obj.parent().next().fadeIn(300);
					}else{
						obj.children(".choose").hide();
						obj.next().removeAttr("checked");
						obj.parent().next().fadeOut(300);
					};
				};
			});
		};
		checkBoxEvt();
		
		/**
		 * 将阿拉伯数字转换为大写
		 * 
		 */
		var _change = {
           ary0:["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"],
           ary1:["", "十", "百", "千"],
           ary2:["", "万", "亿", "兆"],
           init:function (name) {
               this.name = name;
           },
           strrev:function () {
               var ary = []
               for (var i = this.name.length; i >= 0; i--) {
                   ary.push(this.name[i])
               }
               return ary.join("");
           }, //倒转字符串。
           pri_ary:function () {
               var $this = this
               var ary = this.strrev();
               var zero = ""
               var newary = ""
               var i4 = -1
               for (var i = 0; i < ary.length; i++) {
                   if (i % 4 == 0) { //首先判断万级单位，每隔四个字符就让万级单位数组索引号递增
                       i4++;
                       newary = this.ary2[i4] + newary; //将万级单位存入该字符的读法中去，它肯定是放在当前字符读法的末尾，所以首先将它叠加入$r中，
                       zero = ""; //在万级单位位置的“0”肯定是不用的读的，所以设置零的读法为空
 
                   }
                   //关于0的处理与判断。
                   if (ary[i] == '0') { //如果读出的字符是“0”，执行如下判断这个“0”是否读作“零”
                       switch (i % 4) {
                           case 0:
                               break;
                           //如果位置索引能被4整除，表示它所处位置是万级单位位置，这个位置的0的读法在前面就已经设置好了，所以这里直接跳过
                           case 1:
                           case 2:
                           case 3:
                               if (ary[i - 1] != '0') {
                                   zero = "零"
                               }
                               ; //如果不被4整除，那么都执行这段判断代码：如果它的下一位数字（针对当前字符串来说是上一个字符，因为之前执行了反转）也是0，那么跳过，否则读作“零”
                               break;
 
                       }
 
                       newary = zero + newary;
                       zero = '';
                   }
                   else { //如果不是“0”
                       newary = this.ary0[parseInt(ary[i])] + this.ary1[i % 4] + newary; //就将该当字符转换成数值型,并作为数组ary0的索引号,以得到与之对应的中文读法，其后再跟上它的的一级单位（空、十、百还是千）最后再加上前面已存入的读法内容。
                   }
 
               }
               if (newary.indexOf("零") == 0) {
                   newary = newary.substr(1)
               }//处理前面的0
               return newary;
           }
       }
 
       //创建class类
       function change() {
           this.init.apply(this, arguments);
       }
       change.prototype = _change
		
		/**
		 * 自定义金额弹窗事件
		 * 
		 */
		var autoMoneyEvt = function(){
			$('.auto-money',$$).off("click").on("click",function(){
				var landlord_rent = $("input[name = 'landlord_rent']").val();
				if($.trim(landlord_rent) != ''){
					var start_time = $("input[name = 'landlord_start_time']",$$).val();
					var end_time = $("input[name = 'landlord_end_time']",$$).val();
					var rent = $("input[name = 'landlord_rent']",$$).val();
					var rent_money = $(":input[name = 'rent_money']").val().split(",");
					if(start_time == '' || end_time == ''){
						var d = dialog({
							title: '提示信息',
							content: '请填写合同起始时间',
							okValue: '确定',
							ok: function(){
								d.close();
							}
						});
						d.showModal();	
					}else{
						var year = yearDifference(start_time,end_time) - 1;
							var dialogHtml = $($('#hide-auto-money').html());
							var loopTag = $(dialogHtml).find('.loop');
							var yearHtml = loopTag.get(0).outerHTML;
							var yearsHtml = [];
							
							for (var i = 0; i < year; i++) {
								var k = new change((i + 2) + '');
								yearsHtml.push(yearHtml.replace('$\{year\}', k.pri_ary()));
							}
							
							$(yearsHtml.join("\n")).insertAfter(loopTag);
							loopTag.remove();
						
						var d = dialog({
							title: '<i class="ifont1">&#xf0075;</i><span>递增金额自定义</span>',
							content: dialogHtml.get(0).outerHTML
						});
						AutoMoneySubmit.dialog = d;
						d.showModal();
						//第一年租金赋值
						$(".rent-first",d._popup).val(rent);
						if(rent_money != ''){
							for(var i = 0; i < rent_money.length; i++){
								(function(i){
									$.each($(".year-money-con",d._popup,$$),function(i,o){
										(function(o){
											$(o).val(rent_money[i + 1]);
										})(o);
									});
								})(i);
							}
						}
	//					
						AutoMoneySubmit.checkUI(function(year_data){
							$(':input[name=rent_money]',$$).val(year_data);
							d.close();
						});
						
						
		
						
						//取消按钮
						$(".cancel-btn").off("click").on("click",function(){
							d.close();
						});
					}
				}else{
					var d = dialog({
						title: '提示信息',
						content: '请输入租金',
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
				}
			});
		};
		autoMoneyEvt();
		//编辑时弹窗
		$(".edit-auto-money",$$).off("click").on("click",function(){
			var editDialogHtml = $('#hide-auto-moneys').html();
			var d = dialog({
				title: '<i class="ifont1">&#xf0075;</i><span>递增金额自定义</span>',
				content: editDialogHtml,
				okValue: '确定',
				ok: function(){
					d.close();
				},
				cancelValue: '取消',
				cancel: function () {
					d.close();
				}
			});
			d.showModal();
		});
		
		
		
		/**
		 * 自定义金额弹出框提交
		 * 
		 */
		var AutoMoneySubmit = {
			dialog:null,
			
			submitForm: function(){
				var cm = "";
				$(".year-money",AutoMoneySubmit.dialog._popup).each(function(){
					var auto_msg = $(this).val();
					cm += ','+auto_msg;
				});

				var d = dialog({
					title: '提示信息',
					content: '保存成功',
					okValue: '确定',
					ok: function () {
						$(".btn2",".ui-dialog-grid").removeClass("none-click");
						d.close();
						$(".rent-con-num",$$).attr('placeholder','已自定义');
						$(".auto-money",$$).addClass("blue");
					}
				});
				d.showModal();
				$(".ui-dialog-close",".ui-dialog-header").hide();
				cm = cm.replace(cm.substr(0,1),"");
				return cm;
			},
			checkUI: function(callfun){
				$('.auto-money-con').Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            callback: function(form){
		            	if($(".btn2",".ui-dialog-grid").hasClass("none-click")) return false;
	           			$(".btn2",".ui-dialog-grid").addClass("none-click");
		            	var year_data = AutoMoneySubmit.submitForm();
		            	if (callfun != undefined) callfun(year_data);
		            }
				});
			}
		};
		
		/**
		 * 银行卡号特效
		 * 
		 */
		var bankCard = function() {
			var bankInput = null;
			$(':input[name=landlord_card_num]').keyup(function(){
				if (bankInput == null) {
					bankInput = $("<span class=\"landlord_card_num_tips\"></span>");
					bankInput.insertBefore(this);
				}
				if ($.trim($(this).val()) == '') {
					bankInput.hide();
					return;
				}
				bankInput.show();
				
				var spaceTextArr = (' ' + $(this).val()).split('');
				var spaceTextArrTmp = [];
				for (var i = 0; i < spaceTextArr.length; i++) {
					spaceTextArrTmp.push(spaceTextArr[i]);
					if ((i % 4) == 0) spaceTextArrTmp.push('　');
				}
				
				bankInput.text($.trim(spaceTextArrTmp.join('')));
				
			}).blur(function(){
				if (bankInput != null) bankInput.hide();
			}).focus(function(){
				if (bankInput != null) bankInput.show();
			});
		}
		bankCard();
		
		/**
		 * 房源名字匹配
		 * 2015-09-01
		 * 
		 */
		$(".centralized_house",$$).blur(function(){
			var that = $(this);
			var house_name = that.val();
			var type = "GET";
			var url = that.attr("dataurl") + "&house_name=" + house_name;
			ajax.doAjax(type,url,"",function(json){
				if(json.status == 0){
					var d = dialog({
						title: '提示信息',
						content: json.message,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
				}
			});	
		});
		$($$).on("blur",".build, .unit, .floor, .num",function(){
			var build = $(".build",$$).val(),
				unit = $(".unit",$$).val(),
				floor = $(".floor",$$).val(),
				num = $(".num",$$).val(),
				house_name = $(".distributed_house",$$).val();
			var type = "GET";
			var url = $(".distributed_house",$$).attr("dataurl") + "&house_name=" + house_name + "-"+ build + "栋" + unit + "单元" + floor + "楼" + num + "号" ;
			ajax.doAjax(type,url,"",function(json){
				if(json.status == 0){
					var d = dialog({
						title: '提示信息',
						content: json.message,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
				}
			});	
		})

        
        /**
		 * 身份证搜索
		 * 2015-05-08
		 * 
		 */
		$(".landlord_card").blur(function(){
			var search = $(this).val();
			if(search != ''){
				var url = $(this).attr('actionurl') + "&search=" + search;
				var ways = "GET";
				ajax.doAjax(ways,url,'',function(msg){
					if(msg.status == 1){
						var d = dialog({
							title: '提示信息',
							content: '身份证已存在',
							okValue: '确定',
							ok: function(){
								$("input[name = 'landlord_name']").val(msg.data.name);
								$("input[name = 'landlord_phone']").val(msg.data.phone);
								d.close();
							}
						});
						d.showModal();
					}else if(msg.status == 0){
						return true;
					}
				});
			}
		});
		
		/**
		 * 小区搜索
		 * 
		 */
		var houseSelectEvt = function(){
			$('#houses',$$).each(function(){
	            var input = $(this);
	            var dom = input.parent('li');
	            var type = $(".house-type > .radio-box > .checked").next('input').attr('data-type');
	            $(this).keyup(function(){
	            	$(this).removeAttr("community_id");
	            });
	        	input.combobox({
	                url:input.attr("actionurl") + '&type=' + type,
	                param_name: 'search',
	                title_name: 'name',
	                commbox_selector: '.commbox',
	                width: 682,
	                result:"data",
	                item_template:':community_name',
//	                height: 200,
	                min_char:1,
	                prompt: '<p>若没有查询到小区<span class="add-compounds">请点此添加</span></p>',
	                padding: 10,
	                offset: { top: 32},
	                callback: {
	                    init:function(obj){},
	                    select: function(obj) {
	                    },
	                    choose: function(obj) {
	                		if(obj && obj != undefined){
								input.val(obj.community_name).attr("community_id",obj.community_id);
	                		}
//	                		$("#houses").siblings(".add-compounds").hide();
	                		$("#houses").siblings(".check-error").hide();
	                		$("#houses").removeClass("Validform_error");
	                		var url = input.attr("dataurl") + "&house_name=" + obj.community_name;
	                		var type = "GET";
	                		ajax.doAjax(type,url,"",function(json){
	                			if(json.status == 0){
	                				var d = dialog({
										title: '提示信息',
										content: json.message,
										okValue: '确定',
										ok: function(){
											d.close();
										}
									});
									d.showModal();
	                			}
	                		});
	                    },
		                notchoose: function(obj){
		                },
		                notdata: function(obj){
		                	if(obj.val() != ''){
		                		$("#houses").siblings(".check-error").show();
		                	}
		                }
	            	}
	        	});        
	        });
		};
		
		
		/**
		 * 分散式搜索
		 * 
		 */
//		$("#houses, .build, .unit, .floor, .num").blur(function(){
//			var search = $("#houses"),
//				search_val = search.val();
//				build = $(".build").val(),
//				unit = $(".unit").val(),
//				floor = $(".floor").val(),
//				num = $(".num").val();
//			if(search.val() != '' && search.val() != search.attr("placeholder") && build != '' && unit != '' && floor != '' && num != ''){
//				var type = $(".house-type > .radio-box > .checked").next('input').attr('data-type');
//				var url = $("#houses").attr('actionurl') + "&type=" + type + "&search=" + search_val + "&build=" + build + "&unit=" + unit + "&floor=" + floor + "&num=" + num;
//				var ways = "GET";
//				ajax.doAjax(ways,url,'',function(msg){
//					if(msg.status == 0){
//						var d = dialog({
//							title: '提示信息',
//							content: '小区名称已存在,无法新增业主',
//							okValue: '确定',
//							ok: function(){
//								d.close();
//							}
//						});
//						d.showModal();
//					}
//				});
//			};
//		});
		
		/**
		 * 业主表单提交
		 * 
		 */
		var addLandlord = {
			submitForm: function(){
				var house_type = $(".radio-box .checked",$$).next('input').attr('data-type'),                                  //房源类型
				    centralized_house = $("input[name = 'centralized_house']",$$).val(),                 //集中式房源
				    distributed_house = $("input[name = 'distributed_house']",$$).val(),                    //分散式房源
				    community_id = $("#houses",$$).attr("community_id"),									 //小区ID
				    build = $("input[name = 'build']",$$).val(),                                            //栋
				    unit = $("input[name = 'unit']",$$).val(),                                              //单元
				    floor = $("input[name = 'floor']",$$).val(),                                            //楼层
				    num = $("input[name = 'number']",$$).val(),                                          //号数
					landlord_name = $("input[name = 'landlord_name']",$$).val(),                         //业主姓名
					landlord_phone = $("input[name = 'landlord_phone']",$$).val(),                       //业主电话
					landlord_card = $("input[name = 'landlord_card']",$$).val(),                         //身份证号
					landlord_contract_num = $("input[name = 'landlord_contract_num']",$$).val(),         //合同编号
					landlord_start_time = $("input[name = 'landlord_start_time']",$$).val(),             //租房开始时间
					landlord_end_time = $("input[name = 'landlord_end_time']",$$).val(),                 //租房结束时间
					landlord_term = $("input[name = 'landlord_term']",$$).val(),                         //免房租期
					landlord_advance_pay = $("input[name = 'landlord_advance_pay']",$$).val(),           //提前付款
					landlord_rent = $("input[name = 'landlord_rent']",$$).val(),                         //租金
					landlord_bet_num = $("input[name = 'landlord_bet_num']",$$).attr("selectVal"),       //押几
					landlord_pay_num = $("input[name = 'landlord_pay_num']",$$).attr("selectVal"),		  //付几		
					landlord_deposit = $("input[name = 'landlord_deposit']",$$).val(),                   //押金
					landlord_pay_bank = $("input[name = 'landlord_pay_bank']",$$).attr("selectVal"),    //付款银行
					landlord_open_bank = $("input[name = 'landlord_open_bank']",$$).val(),               //开户支行
					landlord_card_num = $("input[name = 'landlord_card_num']",$$).val(),                 //银行卡号
					landlord_payee = $("input[name = 'landlord_payee']",$$).val(),						  //收款人
					landlord_mark = $("textarea[name = 'landlord_mark']",$$).val(),                     //备注
					landlord_id = $("#landlord-msg",$$).attr("landlord_id"),
					contract_id = $("#landlord-msg",$$).attr("contract_id");
				var	pay_rent_ways = $(".rent-con .checked",$$).next('input').attr('data-type');
				var local_url = window.location.href;
				
				//取url里的参数
				var vars = [], hash;
			    var q = local_url.split('?')[1];
			    if(q != undefined){
			       q = q.split('&');
			       for(var i = 0; i < q.length; i++){
			           hash = q[i].split('=');
			           vars.push(hash[1]);
			           vars[hash[0]] = hash[1];
			       }
				}
				var house_id = vars['house_id'];
				if(house_id == '' || house_id == undefined){
					house_id = '';
				}
				
				
				if($("input[name = 'rent_money']").val() != ''){
					pay_rent_ways = 3;
				}
				var	rent_con_num = $(".rent-con-num",$$).val();
				var cycle = yearDifference(landlord_start_time,landlord_end_time);
				
				
				//图片
				var room_images = [];
				if($(".upload-imgview",$$).size() > 0){
					var check_imgs = true;
					$(".upload-imgview",$$).each(function(){
						if($(this).find('input').val() == ""){
							check_imgs = false;
							return false
						}
						room_images.push($(this).find('input').val());
					});
					if(check_imgs == false){
						var d = dialog({
							title: '提示信息',
							content:'请等待图片上传'
						});
						d.showModal();
						  setTimeout(function(){
							d.close().remove();
						},1500);
						return false;
					}
				}
				var data = {
					"house_type": house_type, 
					"landlord_name": landlord_name,
					"landlord_phone": landlord_phone,
					"landlord_card": landlord_card,
					"landlord_contract_num": landlord_contract_num,
					"landlord_start_time": landlord_start_time,
					"landlord_end_time": landlord_end_time,
					"landlord_month": '',
					'landlord_day':'',
					"landlord_advance_pay": landlord_advance_pay,
					"landlord_rent": landlord_rent,
					"landlord_bet_num": landlord_bet_num,
					"landlord_pay_num": landlord_pay_num,
					"landlord_deposit": landlord_deposit,
					"landlord_pay_bank": landlord_pay_bank,
					"landlord_open_bank": landlord_open_bank,
					"landlord_card_num": landlord_card_num,
					"landlord_payee": landlord_payee,
					"landlord_mark": landlord_mark,
					"room_images": room_images,
					"house_id": house_id 
				};
				if($(".mzq-dw",$$).attr("selectval") == "month"){
					data.landlord_month = landlord_term;
					data.landlord_day = '';
				}else{
					data.landlord_month = '';
					data.landlord_day = landlord_term;
				}
				if(house_type == 1){
					data.landlord_house = distributed_house;
					data.build = build;
					data.unit = unit;
					data.floor = floor;
					data.num = num;
					data.community_id = community_id;
				}else{
					data.landlord_house = centralized_house;
				};
				if($(".landlord-rent .check-box label",$$).hasClass("checked")){
					data.pay_rent_ways = pay_rent_ways;
					var rent_money;
					if(rent_con_num != '' && rent_con_num != $(".rent-con-num",$$).attr('placeholder')){
						rent_money = rent_con_num;
					}else{
						rent_money = $("input[name = 'rent_money']").val().split(",");
					}
					data.rent_con_num = rent_money;
				}
				//编辑页面传ID
				if(landlord_id != '' && landlord_id != undefined){
					data.landlord_id = landlord_id;
				};
				if(contract_id != '' && contract_id != undefined){
					data.contract_id = contract_id;
				};
				//租金递增传年份
				if(cycle != '' && !isNaN(cycle)){
					data.cycle = cycle;
				}
				var type = "POST";
				var url = $('.btn2',$$).attr('url');
				ajax.doAjax(type,url,data,addLandlord.callback);
			},
			callback: function(data){
				if(data.status == 1){
					var d = dialog({
						title: '提示信息',
						content: data.message,
						okValue: '确定',
						ok: function(){
							$(".btn2",$$).removeClass("none-click");
							d.close();
							
							var tag = WindowTag.getCurrentTag();
							//关闭当前标签
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
							//刷新标签
							WindowTag.loadTag(data.tag,'get',function(){});
							//新开财务页面
							if(data.url != '' && data.url != undefined){
								window.WindowTag.openTag('#' + data.url);
							}
							if(data.newtag != '' && data.newtag != undefined){
								var da = dialog({
									title: '提示信息',
									content: '是否新增公寓或房源？',
									okValue: '确定',
									ok: function(){
										da.close();
										window.WindowTag.openTag('#' + data.newtag);
									},
									cancelValue: '取消',
									cancel: function () {
										da.close();
									}
								});
								da.showModal();
							}
						}
					});
					d.showModal();
				}else{
					$(".add-landlord-btn",$$).attr('md','md');
					var d = dialog({
						title: '提示信息',
						content: data.message,
						okValue: '确定',
						ok: function(){
							$(".btn2",$$).removeClass("none-click");
							d.close();
						}
					});
					d.showModal();
				}
				$(".ui-dialog-close",".ui-dialog-header").hide();
			},
			checkUI: function(){
				$(".manager_landlord_msg",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					ignoreHidden : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            datatype : {
		                "landlordRent":function(gets,obj,curform,regxp) {
		                	var start_time = $("input[name = 'landlord_start_time']",$$);
							var end_time = $("input[name = 'landlord_end_time']",$$);
		                	if(start_time.val() != '' && start_time.val() != start_time.attr('placeholder') && end_time.val() != '' && end_time.val() != end_time.attr('placeholder')  || gets != ''){
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
		                	}
		                },
		                "rentIncrease":function(gets,obj,curform,regxp) {
		                	var start_time = $("input[name = 'landlord_start_time']",$$);
							var end_time = $("input[name = 'landlord_end_time']",$$);
							var rent_con_num = $(".rent-con-num").attr("placeholder");
		                	if(start_time.val() != '' && start_time.val() != start_time.attr('placeholder') && end_time.val() != '' && end_time.val() != end_time.attr('placeholder')  || gets != ''){
		                		if(rent_con_num == '' && rent_con_num == undefined){
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
		                		}
		                	}
		                },
		                "floor":function(gets,obj,curform,regxp) {
	                		if($.trim(gets) == ""){
	                			return false;
	                		}else{
		                   	   	var	reg2 = /^([\-0-9]*)([0-9a-zA-Z])$/;   //匹配正负浮点数
		                		if(reg2.test(gets)==true){
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
		                "landlordDeposit":function(gets,obj,curform,regxp) {    //验证押金
		                	if(gets != '' && gets != obj.attr('placeholder')){
	                			var reg1 = /^([1-9][0-9]*|[0])$/;          //匹配正整数（包含0）
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
		                "chooseNumBet":function(gets,obj,curform,regxp) {
		                	var start_time = $("input[name = 'landlord_start_time']",$$);
							var end_time = $("input[name = 'landlord_end_time']",$$);
		                	if(start_time.val() != '' && start_time.val() != start_time.attr('placeholder') && end_time.val() != '' && end_time.val() != end_time.attr('placeholder')){
		                		if(obj.attr("selectVal") == ''){
			                    	return '请选择押几';
			                    }
		                	}
		                },
		                "chooseNumPay":function(gets,obj,curform,regxp) {
		                	var start_time = $("input[name = 'landlord_start_time']",$$);
							var end_time = $("input[name = 'landlord_end_time']",$$);
		                	if(start_time.val() != '' && start_time.val() != start_time.attr('placeholder') && end_time.val() != '' && end_time.val() != end_time.attr('placeholder')){
		                		if(obj.attr("selectVal") == ''){
			                    	return '请选择付几';
			                    }
		                	}
		                },
		                "compoundsName":function(gets,obj,curform,regxp) {
		                		if(typeof obj.attr("community_id") == "undefined"){
//			                		$(".add-compounds").show();
			                		return '小区不存在，请新增小区';
		                		}else{
		                			$(".add-compounds").hide();
		                		}
		                },
		                "landlord_phone":function(gets,obj,curform,regxp) {
		                	if($("input[name = 'landlord_phone']").val() != ''){
		                		var reg = /^13[0-9]{9}$|14[0-9]{9}|15[0-9]{9}$|18[0-9]{9}$/;
			                	if(reg.test(obj.val()) == false){
			                		return false;
			                	}else{
			                		return true;
		                		}
		                	}
		                },
		                "landlord_card":function(gets,obj,curform,regxp) {
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
		                "landlord_term":function(gets,obj,curform,regxp) {
		                	if($("input[name = 'landlord_term']").val() != ''){
		                		var reg = /^(0|[1-9][0-9]*)$/;
		                		if(reg.test(obj.val()) == true){
		                			return true;
		                		}else{
		                			return false;
		                		}
		                	}
		                },
		                "contract-time":function(gets,obj,curform,regxp) {
		                	if($.trim(gets) == ''){
		                		return false;
		                	}
		                },
		                "landlord_card_num":function(gets,obj,curform,regxp) {
		                	if($("input[name = 'landlord_card_num']").val() != ''){
		                		var reg = /^(\d{16}|\d{17}|\d{18}|\d{19})$/;   //银行卡号16位或者19位
		                		if(reg.test(obj.val()) == true){
		                			return true;
		                		}else{
		                			return false;
		                		}
		                	}
		                },
		                "landlord_mark":function(gets,obj,curform,regxp) {
		                	if($("input[name = 'landlord_mark']").val() != ''){
		                		var reg = /^\S{0,400}$/;
		                		if(reg.test(obj.val()) == true){
		                			return true;
		                		}else{
		                			return false;
		                		}
		                	}
		                }
		            },
		            callback : function(form){
		            	var res = hash.hash.ischange("manager_landlord_msg",$(":first",$$));
		            	var start_time = $("input[name = 'landlord_start_time']",$$);
						var end_time = $("input[name = 'landlord_end_time']",$$);
						var start = new Date(start_time.val().replace("-", "/"));
						var end = new Date(end_time.val().replace("-", "/"));
						if(start_time.val() != '' && start_time.val() != start_time.attr('placeholder') && end_time.val() != '' && end_time.val() != end_time.attr('placeholder') && start > end){
							var d = dialog({
								title: '提示信息',
								content: '合同起始日期填写错误',
								okValue: '确定',
								ok: function(){
									d.close();
								}
							});
							d.showModal();
						}else if(res === true){
							if($(".btn2",form).hasClass("none-click")) return false;
	           				$(".btn2",form).addClass("none-click");
	            			addLandlord.submitForm();
	            		}else if((res === false)){
		            		var d = dialog({
								title: '提示信息',
								content: '当前数据没有任何修改',
								okValue: '确定',
								ok: function(){
									$(".btn2",form).removeClass("none-click");
									d.close();
								}
							});
							d.showModal();
							$(".ui-dialog-close",".ui-dialog-header").hide();
						}
	            	}
				});
			}
		};
		addLandlord.checkUI();
		
		
		
		
		/**
		 * 新增小区弹窗
		 * 
		 */
		
		$('.manager_landlord_msg',$$).on('click', '.add-compounds', function(){
			var dialogHtml = $('#hide-add-compounds').html();
			var d = dialog({
				title: '<i class="ifont1">&#xf0075;</i><span>提交小区信息</span>',
				content: dialogHtml
			});
			d.showModal();
			AddCompoundsSubmit.dialog = d;
			
			var areaContainer = {};
			//
			$.each($(".city-selectByMO ul li",$$), function(i, o) {    
				if($(o).hasClass("selectedLi")){
					var li_val = $(o).text();
					$(".city").val(li_val);
					var url = $(".selectByMT.city").attr('addressurl');
					var type = "GET";
					ajax.doAjax(type,url,"",function(msg){
						if(msg.status == 1 && msg.data != undefined && msg.data.length != 0){
							areaContainer = {};
							
							for (var i = 0; i < msg.data.length; i++) {
								if (areaContainer[msg.data[i]['area_id']] == undefined || 
									areaContainer[msg.data[i]['area_id']].children == undefined) {
									areaContainer[msg.data[i]['area_id']] = {
										'name': msg.data[i]['aname'],
										'children': {}
									}
								}
								
								areaContainer[msg.data[i]['area_id']].children[msg.data[i]['business_id']] = msg.data[i]['name'];
							}
							
							$(".area").siblings(".selectByMO").find('li:not(:first)').remove();
							
							var areaHtml = [];
							for (var key in areaContainer) {
								var liHtml = '<li selectVal="' + key + '">' + areaContainer[key].name + '</li>';
								areaHtml.push(liHtml);
							}
							
							$(areaHtml.join('')).insertAfter($(".area").siblings(".selectByMO").find('li:last'));
							
						}
					});
				}
			});
			
			
			
			
			$(".selectByM",d._popup).each(function(){
				var that = $(this);
				
				$(this).selectObjM(1, function(val, input){
					
					if (input.get(0) == $('.selectByMT.city', d._popup).get(0)) {
						var add_compounds = {
							submitForm: function(){
								var	city_id = val;
								var type = "POST";
								var url = $(".selectByMT.city").attr('addressurl');
								var data = {
									"city_id": city_id
								};
								ajax.doAjax(type,url,data,add_compounds.callback);
							},
							callback: function(msg){
								if(msg.status == 1 && msg.data != undefined && msg.data.length != 0){
									areaContainer = {};
									
									for (var i = 0; i < msg.data.length; i++) {
										if (areaContainer[msg.data[i]['area_id']] == undefined || 
											areaContainer[msg.data[i]['area_id']].children == undefined) {
											areaContainer[msg.data[i]['area_id']] = {
												'name': msg.data[i]['aname'],
												'children': {}
											}
										}
										
										areaContainer[msg.data[i]['area_id']].children[msg.data[i]['business_id']] = msg.data[i]['name'];
									}
									
									$(".area").siblings(".selectByMO").find('li:not(:first)').remove();
									
									var areaHtml = [];
									for (var key in areaContainer) {
										var liHtml = '<li selectVal="' + key + '">' + areaContainer[key].name + '</li>';
										areaHtml.push(liHtml);
									}
									
									$(areaHtml.join('')).insertAfter($(".area").siblings(".selectByMO").find('li:last'));
									
								}
							}
							
						}
						add_compounds.submitForm();
					}
					else if (input.get(0) == $('.selectByMT.area', d._popup).get(0) && areaContainer.length != 0) {
						$(".business").siblings(".selectByMO").find('li:not(:first)').remove();
						var businessContainer = {};
						if (areaContainer[val] != undefined) businessContainer = areaContainer[val]['children'];
						
						var businessHtml = [];
						for (var key in businessContainer) {
							var liHtml = '<li selectVal="' + key + '">' + businessContainer[key] + '</li>';
							businessHtml.push(liHtml);
						}
						
						$(businessHtml.join('')).insertAfter($(".business").siblings(".selectByMO").find('li:last'));
						
						
					} else {
						//console.log(input.get(0));
					}
					
					
				});
			});
//			
			//弹窗表单提交
			AddCompoundsSubmit.checkUI(function(){
				d.close();
			});
			//取消按钮
			$(".cancel-btn").off("click").on("click",function(){
				d.close();
			});
		});
		
		/**
		 * 新增小区弹窗提交
		 * 
		 */
		var AddCompoundsSubmit = {
			dialog: null,
			submitForm: function(){
				var community_name = $(".compounds-name", AddCompoundsSubmit.dialog._popup).val(),
					city_id = $(".city", AddCompoundsSubmit.dialog._popup).attr('selectVal'),
					area_id = $(".area",AddCompoundsSubmit.dialog._popup).attr("selectVal"),
					area_string = $(".area",AddCompoundsSubmit.dialog._popup).val(),
					business_id = $(".business",AddCompoundsSubmit.dialog._popup).attr("selectVal"),
					business_string = $(".business",AddCompoundsSubmit.dialog._popup).val(),
					address = $(".i-txt",AddCompoundsSubmit.dialog._popup).val();
				var type = "POST";
				var data = {
					"community_name": community_name,
					"city_id": city_id,
					"area_id": area_id,
					"area_string": area_string,
					"business_id": business_id,
					"business_string": business_string,
					"address": address
				};
				var url = $(".add_compounds_btn").attr("actionurl");
				ajax.doAjax(type,url,data,AddCompoundsSubmit.callback);
			},
			callback: function(info){
				if(info.status == 1){
					var d = dialog({
						title: '提示信息',
						content: '小区提交成功，为了保证数据准确，我们将会在2小时内进行审核<br />请耐心等待。 如有需要请联系028-83375579',
						okValue: '确定',
						ok: function(){
							$(".add_compounds_btn",".ui-dialog-grid").removeClass("none-click");
							d.close();
						}
					});
					d.showModal();
				}else{
					var d = dialog({
						title: '提示信息',
						content: info.message,
						okValue: '确定',
						ok: function(){
							$(".add_compounds_btn",".ui-dialog-grid").removeClass("none-click");
							d.close();
						}
					});
					d.showModal();
				}
				$(".ui-dialog-close",".ui-dialog-header").hide();
			},
			checkUI: function(callfun){
				$('.add-compounds-con').Validform({
					btnSubmit : ".add_compounds_btn",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		             datatype : {
		                "areaName":function(gets,obj,curform,regxp) {
		                	if(obj.attr("selectVal") == ''){
		                    	return '请选择区域';
		                    }
		                },
		                "businessName":function(gets,obj,curform,regxp) {
		                	if(obj.attr("selectVal") == ''){
		                    	return '请选择区域';
		                   } 
		                },
		            },   
		            callback: function(form){
//		            	if($(".add_compounds_btn",".ui-dialog-grid").hasClass("none-click")) return false;
//	           			$(".add_compounds_btn",".ui-dialog-grid").addClass("none-click");
	           			var area_id = $(".area",AddCompoundsSubmit.dialog._popup).attr("selectVal");
	           			var business_id = $(".business",AddCompoundsSubmit.dialog._popup).attr("selectVal");
	           			
	           			if(area_id == 0){
	           				var d = dialog({
								title: '提示信息',
								content: '请选择区域',
								okValue: '确定',
								ok: function(){
									d.close();
								}
							});
							d.showModal();
	           			}else if(business_id == 0){
	           				var d = dialog({
								title: '提示信息',
								content: '请选择商圈',
								okValue: '确定',
								ok: function(){
									d.close();
								}
							});
							d.showModal();
	           			}else{
	           				AddCompoundsSubmit.submitForm();
		            		if (callfun != undefined) callfun();
	           			}
	           			
		            	
		            }
				});
			}
		};
		
		//调用图片上传
		uplodify.uploadifyInits($('#landlord-file-upload',$$),$("#landlord-uploaderArea",$$),'*.gif; *.jpg; *.jpeg; *.png; *.doc; *.docx; *.pdf');
		uplodify.uploadifyInits($('#relet-file-upload',$$),$("#relet-uploaderArea",$$),'*.gif; *.jpg; *.jpeg; *.png; *.doc; *.docx; *.pdf');
		uplodify.uploadifyInits($('#landlord-edit-file-upload',$$),$("#landlord-edit-uploaderArea",$$),'*.gif; *.jpg; *.jpeg; *.png; *.doc; *.docx; *.pdf');
		
		/**
		 * 合同终止
		 * 2015-05-19
		 * 
		 */
		$(".contract-stop").off("click").on("click",function(){
			var that = $(this);
			var url = that.attr("action");
			var type = "GET";
			var d = dialog({
				title: '提示信息',
				content: '确定终止该合同？',
				okValue: '确定',
				ok: function () {
					ajax.doAjax(type,url,"",function(data){
				 		var dd = dialog({
							title: '提示',
							content: data.message
						});
						dd.showModal();
						setTimeout(function(){
							dd.close();
						},1200);
						if(data.status == 1){
							d.close();
							window.location.href="#"+data.url;
							//刷新标签
							var tag = WindowTag.getCurrentTag();
							WindowTag.loadTag(data.tag,'get',function(){});
							
						}
					});
				},
				cancelValue: '取消',
				cancel: function () {
					d.close();
				}
			});
			d.showModal();
			
		});
		
		/**
		 * 合同支付
		 * 2015-06-01
		 * 
		 */
		$(".contract-pay").off("click").on("click",function(){
			var url = $(this).attr("payaction");
			var type = "GET";
			ajax.doAjax(type,url,"",function(data){
				if(data.status == 1){
					window.location.href="#"+data.url;
				}else{
					var d = dialog({
						title: '提示信息',
						content: data.message,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
				}
			});
		});
		
		//取消
		hash.hash.savehash("manager_landlord_msg",$(":first",$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('manager_landlord_msg',$(':first',$$)) == true){
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