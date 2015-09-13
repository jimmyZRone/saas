define(function(require,exports){
	var $ = require('jquery');
		require("selectByM")($);
		require("radio")($);
		require("placeholder")($);
		require("validForm")($);
	var  ajax=require("Ajax"),ajaxLoading;
	var  dialog = require("dialog"); //弹窗插件
	var navigators = require("navigatortest");  //浏览器版本检测
	var hash = require('child_data_hash');
	var $$ = null;
		
	var centralized_add_house_bulkJs = {
		others : function(){
			//针对IE10以下的input提示语兼容
			if(sys.ie && sys.ie < 10){
				$(".view",$$).placeholder();
			};
		
			//调用复选框	
			$(".selectByM",$$).each(function(){
				$(this).selectObjM();
			})
			
			//调用单选框
			$('.radio',$$).each(function(){
				$(this).click(function(){
					$(this).Radios();
					if($(this).parents('.radio-box').hasClass('radio-type')){
						$(this).parents('.radio-box').siblings('.rent-msg').show();
					}else{
						$(this).parents('.radio-box').siblings('.rent-msg').hide();
					};
				})
			});
		},
		//自定义房源编号
		diyhousenum : function(){
			$(".auto-house-num",$$).off("click").on("click",function(){
				$(".housenum-start",$$).focus().blur();
				$(".housenum-end",$$).focus().blur();
				var start = $(".housenum-start",$$).val()/1;
				var end = $(".housenum-end",$$).val()/1;
				if($(".housenum-start",$$).hasClass("Validform_error") || $(".housenum-end",$$ || start > end).hasClass("Validform_error")) return false;
				if(!!!$(".housenum-start",$$).attr("prev")) {$(".housenum-start",$$).attr("prev",$(".housenum-start",$$).val());}
				if(!!!$(".housenum-end",$$).attr("prev")) {$(".housenum-end",$$).attr("prev",$(".housenum-end",$$).val());}
				var length = end - start + 1;
				var obj = $("#hideTemp",$$).find(".lr-tb");
				if($(".housenum-start",$$).attr("prev") != $(".housenum-start",$$).val()){
					obj.removeClass("hassaved");
					$(".housenum-start",$$).attr("prev",$(".housenum-start",$$).val());
				}
				if($(".housenum-end",$$).attr("prev") != $(".housenum-end",$$).val()){
					obj.removeClass("hassaved");
					$(".housenum-end",$$).attr("prev",$(".housenum-end",$$).val());
				}
				if(!obj.hasClass("hassaved")){
					obj.find("tr:gt(0)").remove();
					var str_auto = "";
					for(var i = 0; i < length; i++){
						str_auto += '<tr><td>'+(i+start)+'</td><td><input type="text" class="ipt centralized_add_house_bulk_diyhousenum" value="'+(i+start)+'"/></td></tr>';
					}
					obj.append(str_auto);
				}
				var  cTemp=$('#hideTemp',$$).html();	 
				var d = dialog({
				title: '<i class="ifont">&#xf0077;</i><span>自定义房源编号</span>',
				content: cTemp,
				okValue: '确 定',
				ok: function () {
					    obj.find("tr:gt(0)").remove();
					    var checkresult = true;
					    var names_Floor = [];
					    var str_auto = "";
						$(".ui-dialog-content .centralized_add_house_bulk_diyhousenum").each(function(i,o){
							var vals = $.trim($(o).val());
							names_Floor.push(vals);
							if(vals == ""){
								checkresult = false;
							}
							str_auto += '<tr><td>'+(i+start)+'</td><td><input type="text" class="ipt centralized_add_house_bulk_diyhousenum" value="'+vals+'"/></td></tr>';
						});
						obj.append(str_auto);
						if(checkresult == false){
							var dd_auto = dialog({
									title:'提示信息',
									content:'自定义房源编号不能为空！',
									okValue:'确 定',
									ok:function(){
										dd_auto.close();
										obj.find("tr:gt(0)").remove();
									}
								});
								dd_auto.showModal();
								return false;
						}
						var names_Floor_auto = names_Floor.slice(0);
						var nary=names_Floor_auto.sort(); //检测数组中是否有重复元素
						for(var i=0;i<names_Floor_auto.length;i++){ 
	
							if (nary[i]==nary[i+1]){ 
								var dd = dialog({
									title: '提示信息',
									content:'自定义房源编号不能相同！',
									okValue: '确定',
									ok: function () {
										dd.close();
										obj.find("tr:gt(0)").remove();
									}
								});
								dd.showModal();
								return false;
						    } 
						}
						obj.addClass("hassaved");
						d.close();
					}
				});
				d.showModal();
			});
		},
		//自定义套内间数
		diyroomnum : function(){
			$(".auto-roomnum",$$).off("click").on("click",function(){
				$(".housenum-start",$$).focus().blur();
				$(".housenum-end",$$).focus().blur();
				$(this).siblings("input").focus().blur();
				if($(".housenum-start",$$).hasClass("Validform_error")|$(".housenum-end",$$).hasClass("Validform_error")|$(this).siblings("input").hasClass("Validform_error")) return false;
				var start = $(".housenum-start",$$).val()/1;
				var end = $(".housenum-end",$$).val()/1;
				var length = end - start + 1;
				var obj = $("#hideTemp1",$$).find(".lr-tb");
				var housenum;
				var roomsnum = $(this).siblings("input").val();
				if(!!!$(this).siblings("input").attr("prev")) $(this).siblings("input").attr("prev",roomsnum);
				if(roomsnum != $(this).siblings("input").attr("prev")) {$(this).siblings("input").attr("prev",roomsnum);obj.removeClass("hassaved");}
				if(!obj.hasClass("hassaved")){
					obj.find("tr:gt(0)").remove();
					if($("#hideTemp tr",$$).length == 1){
						var str_auto = "";
						for(var i = 0; i < length; i++){
							str_auto += '<tr><td>'+(start+i)+'</td><td><input type="text" class="ipt centralized_add_house_bulk_diyroomnum" value="'+roomsnum+'"/></td></tr>';
						}
						obj.append(str_auto);
					}else{
						var str_auto = "";
						for(var i = 0; i < length; i++){
							housenum = $(".centralized_add_house_bulk_diyhousenum:eq("+i+")",$$).val();
							str_auto += '<tr><td>'+housenum+'</td><td><input type="text" class="ipt centralized_add_house_bulk_diyroomnum" value="'+roomsnum+'"/></td></tr>';
						}
						obj.append(str_auto);
					}	
				}
				var  cTemp=$('#hideTemp1',$$).html();	 
				var d = dialog({
				title: '<i class="ifont">&#xf0077;</i><span>自定义套内间数</span>',
				content: cTemp,
				okValue: '确 定',
				ok: function () {
						obj.find("tr:gt(0)").remove();
						 var checkresult = true;
						 var checkresult_auto = true;
						 var str_auto = "";
						 var housenum_start = parseInt($('.housenum-start',$$).val());
						 housenum_start = isNaN(housenum_start) ? 1: housenum_start;
						$(".ui-dialog-content .centralized_add_house_bulk_diyroomnum").each(function(i,o){
							var vals = $.trim($(o).val());
							housenum = $(".centralized_add_house_bulk_diyhousenum:eq("+i+")",$$).val();
							housenum = typeof housenum == 'undefined' ? i+housenum_start : housenum;
							str_auto += '<tr><td>'+housenum+'</td><td><input type="text" class="ipt centralized_add_house_bulk_diyroomnum" value="'+vals+'"/></td></tr>';
							if(vals == "") checkresult=false;
							if(isNaN(vals/1)|| vals/1 < 1 || vals/1 > 99) checkresult_auto = false;
						});
						obj.append(str_auto);
						if(checkresult == false){
							var dd_auto = dialog({
									title:'提示信息',
									content:'自定义套内间数不能为空！',
									okValue:'确 定',
									ok:function(){
										dd_auto.close();
									}
								});
								dd_auto.showModal();
								return false;
						}
						if(checkresult_auto == false){
							var dd_auto = dialog({
									title:'提示信息',
									content:'自定义套内间数不合规则（套内间数只能是1到99整数）！',
									okValue:'确 定',
									ok:function(){
										dd_auto.close();
									}
								});
								dd_auto.showModal();
								return false;
						}
						obj.addClass("hassaved");
						d.close();
					}
				});
				d.showModal();
			});
		},
		submitForm : function(){
			var url = $(".btn2",$$).attr("url");
			var type = "post";
			var floornum = $(".floornum",$$).val();
			var cutetype = $("input[name='type']:checked",$$).val();
			var houses = [];   //房源号及房源内房间数
			var roomnum_default = $(".roomnum-default",$$).val();
			var start = $(".housenum-start",$$).val()/1;
			var end = $(".housenum-end",$$).val()/1;
			var length = end - start + 1;
			var house = {};
			var house_numtotal = [];  //房源编号数组
			var room_numtotal = [];	  //套内房间数数组
			var flat_id = document.URL.split("flat_id=")[1];
			if($("#hideTemp tr",$$).length == 1){
				for(var i = 0; i < length; i++){
					house_numtotal.push(start+i);
				}
			}else{
				$(".centralized_add_house_bulk_diyhousenum",$$).each(function(){
					house_numtotal.push($(this).val());
				});
			}
			if($("#hideTemp1 tr",$$).length == 1){
				for(var i = 0; i < length; i++){
					room_numtotal.push(roomnum_default);
				}
			}else{
					$(".centralized_add_house_bulk_diyroomnum",$$).each(function(){
						room_numtotal.push($(this).val());
					});
			}
			for(var n in house_numtotal){
				house = {
					house_num : house_numtotal[n],
					room_num : room_numtotal[n]
				}
				houses.push(house);
			}
			var data = {
				floornum : floornum,
				cutetype : cutetype,
				houses : houses,
				flat_id : flat_id
			}
			if(hash.hash.ischange('centralized_add_house_bulk',$(':first',$$)) == true) {ajax.doAjax(type,url,data,centralized_add_house_bulkJs.callback);}
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
		},
		callback : function(data){
			if(data.status == 1){
				var d = dialog({
					title: '提示信息',
					content:'保存成功',
					okValue: '确定',
					ok: function () {
						d.close();
						$(".btn2",$$).removeClass("none-click");
						//关闭当前标签
						var tag = WindowTag.getCurrentTag();
						if(typeof data['url'] == 'string'){
		    				window.WindowTag.openTag('#'+data.url);
		    			}else if(typeof data['tag'] == 'string'){
		    				var ctag = WindowTag.getTagByUrlHash(data['tag']);
		    				if(ctag){
		    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
		    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
		    				}
		    			}
						WindowTag.closeTag(tag.find('>a:first').attr('href'));
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
			$(".ui-dialog-close",".ui-dialog-header").hide();
		},
		checkForm : function(){
			$(".centralized_add_house_bulk",$$).Validform({
				btnSubmit : ".btn2",
				showAllError : true,
				ignoreHidden : true,
				tiptype : function(msg,o,cssctl){
	                var objtip=o.obj.parents("li").find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	           },
	           datatype : {
	           		"recheckend":function(gets,obj,curform,regxp) {
		                   var start =  $(".housenum-start",$$).val()/1;
						   var end = gets/1;
						   if($.trim($(".housenum-start",$$).val()) == "") $(".housenum-start",$$).val(1);
						   if(end < start){
						   	return "结束号码应大于等于开始号码";
						   }
						   if(end-start > 100){
						   	return "房间号段区间不能超过100";
						   }
		               },
		               "house-num":function(gets,obj,curform,regxp){
		               	if(gets/1 == 0) return "房间编号不能为0";
		               	if(gets/1 > 99999999) return "房间编号不能超过8位";
		               },
		               "floor-num":function(gets,obj,curform,regxp){
		               	 if(gets/1 == 0){
		               	 	return "所在楼层不能为0";
		               	 }
		               	 if(isNaN(gets/1) || gets.indexOf(".")>=0 || Math.abs(gets/1) > 999) return false;
		               },
		               "num-rooms":function(gets,obj,curform,regxp){
		               	   if(gets/1 == 0){
		               	   	 return "套内间数不能为0";
		               	   }
		               	   if(gets/1 > 99){
		               	   	 return "套内间数不能超过99";
		               	   }
		               }
	           },
	           callback : function(form){
	           		if($(".btn2",form).hasClass("none-click")) return false;
	           		$(".btn2",form).addClass("none-click");
	           		centralized_add_house_bulkJs.submitForm();
	           		return false;
	           }
			});
			$(":input",$$).focus(function(){
				if($(this).hasClass("Validform_error")){
					$(this).css("background","none");
					$(this).parents("li").find(".check-error").hide();
				}
			}).blur(function(){
				$(this).removeAttr("style");
				$(this).parents("li").find(".check-error").show();
			});
		}
	}
	
	//入口函数
	exports.inite = function(__html__){
		$$ = __html__;
		var that = centralized_add_house_bulkJs;
		that.others();
		that.diyhousenum();
		that.diyroomnum();
		that.checkForm();
		hash.hash.savehash('centralized_add_house_bulk',$(':first',$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('centralized_add_house_bulk',$(':first',$$)) == true){
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
});