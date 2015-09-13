define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	var uplodify = require("uplodify");  //图片上传
	var urlHelper = require('url');

	var modelInit = function($$){
		//调用下拉框JS
		$(".selectByM",$$).each(function(){
			$(this).selectObjM();
		});

		//调用图片上传
		uplodify.uploadifyInits($("#house-file-upload",$$),$("#house-uploaderArea",$$), '', {width:"248",height:"148",template:'<div class="pc-shade"></div><div class="pc-use">设置封面</div>'});
		

		/**
		 * 判断是否可以发布
		 *
		 */
		$.each($(".hc-detail .check-box",$$), function(i, o) {
			$(o).off("click").on("click",function(){
				var det = $(this).siblings(".detail");
				var house_type = $(this).find(".cb-data").attr("house_type"),
					floor = $(this).find(".cb-data").attr("floor"),
					room_type = $(this).find(".cb-data").attr("room_type"),
					count = $(this).find(".cb-data").attr("count"),
					hall = $(this).find(".cb-data").attr("hall"),
					toilet = $(this).find(".cb-data").attr("toilet"),
					money = $(this).find(".cb-data").attr("money"),
					area = $(this).find(".cb-data").attr("area"),
					rental_way = $(this).find(".cb-data").attr("rental_way"),
					is_pic = $(this).find(".cb-data").attr("is_pic"),
					status = $(this).find(".cb-data").attr("status"),
					totalsy = $(this).find(".cb-data").attr("totalsy");
				var arr = [];
				for(var i = 0; i < totalsy; i++){
					var _arr = $(this).find(".cb-data").attr("syarea"+i);
					arr.push(_arr);
				}
				var obj = $(this).find("label");
				if(house_type == 2){  //集中式
					if(rental_way == 2){    //整租
						if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
							checkBoxFun(obj);
						}else{
							var d = dialog({
								title: '提示信息',
								content: '请先完善房源信息',
								okValue: '确定',
								ok: function () {
									d.close();
									detailEvent.detailShow(det);
								},
								cancelValue: '取消',
								cancel: function () {
									d.close();
								}
							});
							d.showModal();
						}
					}else if(rental_way == 1){
						if(status == 2){  //已出租
							if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0 && arr != ''){
								$.each(arr, function(k, v) {
									if(v != ''){
										checkBoxFun(obj);
									}
								});
							}else{
								var d = dialog({
									title: '提示信息',
									content: '请先完善房源信息',
									okValue: '确定',
									ok: function () {
										d.close();
										detailEvent.detailShow(det);
									},
									cancelValue: '取消',
									cancel: function () {
										d.close();
									}
								});
								d.showModal();
							}
						}else{
							if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
								checkBoxFun(obj);
							}else{
								var d = dialog({
									title: '提示信息',
									content: '请先完善房源信息',
									okValue: '确定',
									ok: function () {
										d.close();
										detailEvent.detailShow(det);
									},
									cancelValue: '取消',
									cancel: function () {
										d.close();
									}
								});
								d.showModal();
							}
						}

					}
				}else if(house_type == 1){
					if(rental_way == 2){
						if(floor != '' && count != '' && hall != '' && toilet != '' && money != '' && area != '' && is_pic != 0){
							checkBoxFun(obj);
						}else{
							var d = dialog({
								title: '提示信息',
								content: '请先完善房源信息',
								okValue: '确定',
								ok: function () {
									d.close();
									detailEvent.detailShow(det);
								},
								cancelValue: '取消',
								cancel: function () {
									d.close();
								}
							});
							d.showModal();
						}
					}else if(rental_way == 1){
						if(status == 2){
							if(floor != '' && count != '' && hall != '' && toilet != '' && money != '' && area != '' && is_pic != 0 && arr != ''){
								$.each(arr, function(k, v) {
									if(v != ''){
										checkBoxFun(obj);
									}
								});
							}else{
								var d = dialog({
									title: '提示信息',
									content: '请先完善房源信息',
									okValue: '确定',
									ok: function () {
										d.close();
										detailEvent.detailShow(det);
									},
									cancelValue: '取消',
									cancel: function () {
										d.close();
									}
								});
								d.showModal();
							}
						}else{
							if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
								checkBoxFun(obj);
							}else{
								var d = dialog({
									title: '提示信息',
									content: '请先完善房源信息',
									okValue: '确定',
									ok: function () {
										d.close();
										detailEvent.detailShow(det);
									},
									cancelValue: '取消',
									cancel: function () {
										d.close();
									}
								});
								d.showModal();
							}
						}

					}
				}
			});
		});



		var checkBoxFun = function(obj){
			obj.toggleClass("checked");
			if(obj.hasClass("checked")){
				obj.children(".choose").show();
				obj.next().attr("checked",true);
			}else{
				obj.children(".choose").hide();
				obj.next().removeAttr("checked");
			};
		};


		/**
		 * 全选
		 *
		 */
		$(".check-all",$$).off("click").on("click",function(){
			var obj = $(this).find("label");
			obj.toggleClass("checked");
			if(obj.hasClass("checked")){
				obj.children(".choose").show();
				obj.next().attr("checked",true);
				$.each($(".hc-detail .check-box",$$), function(i, o) {
					var house_type = $(o).find(".cb-data").attr("house_type"),
						floor = $(o).find(".cb-data").attr("floor"),
						room_type = $(o).find(".cb-data").attr("room_type"),
						count = $(o).find(".cb-data").attr("count"),
						hall = $(o).find(".cb-data").attr("hall"),
						toilet = $(o).find(".cb-data").attr("toilet"),
						money = $(o).find(".cb-data").attr("money"),
						area = $(o).find(".cb-data").attr("area"),
						rental_way = $(o).find(".cb-data").attr("rental_way"),
						is_pic = $(o).find(".cb-data").attr("is_pic"),
						status = $(o).find(".cb-data").attr("status"),
						totalsy = $(o).find(".cb-data").attr("totalsy");
					var arr = [];
					for(var i = 0; i < totalsy; i++){
						var _arr = $(this).find(".cb-data").attr("syarea"+i);
						arr.push(_arr);
					}
					var obj = $(o).find("label");
					if(house_type == 2){  //集中式
						if(rental_way == 2){    //整租
							if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
								obj.addClass("checked");
								obj.find(".choose").show();
								obj.parent().find("input").attr("checked",true);
							}
						}else if(rental_way == 1){
							if(status == 2){  //已出租
								if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0 && arr != ''){
									$.each(arr, function(k, v) {
										if(v != ''){
											obj.addClass("checked");
											obj.find(".choose").show();
											obj.parent().find("input").attr("checked",true);
										}
									});
								}
							}else{
								if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
									obj.addClass("checked");
									obj.find(".choose").show();
									obj.parent().find("input").attr("checked",true);
								}
							}

						}
					}else if(house_type == 1){
						if(rental_way == 2){
							if(floor != '' && count != '' && hall != '' && toilet != '' && money != '' && area != '' && is_pic != 0){
								obj.addClass("checked");
								obj.find(".choose").show();
								obj.parent().find("input").attr("checked",true);
							}
						}else if(rental_way == 1){
							if(status == 2){
								if(floor != '' && count != '' && hall != '' && toilet != '' && money != '' && area != '' && is_pic != 0 && arr != ''){
									$.each(arr, function(k, v) {
										if(v != ''){
											obj.addClass("checked");
											obj.find(".choose").show();
											obj.parent().find("input").attr("checked",true);
										}
									});
								}
							}else{
								if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
									obj.addClass("checked");
									obj.find(".choose").show();
									obj.parent().find("input").attr("checked",true);
								}
							}

						}
					}
				});
			}else{
				obj.children(".choose").hide();
				obj.next().removeAttr("checked");
				$.each($(".hc-detail .check-box",$$), function(i, o) {
					var house_type = $(o).find(".cb-data").attr("house_type"),
						floor = $(o).find(".cb-data").attr("floor"),
						room_type = $(o).find(".cb-data").attr("room_type"),
						count = $(o).find(".cb-data").attr("count"),
						hall = $(o).find(".cb-data").attr("hall"),
						toilet = $(o).find(".cb-data").attr("toilet"),
						money = $(o).find(".cb-data").attr("money"),
						area = $(o).find(".cb-data").attr("area"),
						rental_way = $(o).find(".cb-data").attr("rental_way"),
						is_pic = $(o).find(".cb-data").attr("is_pic"),
						status = $(o).find(".cb-data").attr("status"),
						totalsy = $(o).find(".cb-data").attr("totalsy");
					var arr = [];
					for(var i = 0; i < totalsy; i++){
						var _arr = $(this).find(".cb-data").attr("syarea"+i);
						arr.push(_arr);
					}
					var obj = $(o).find("label");
					if(house_type == 2){  //集中式
						if(rental_way == 2){    //整租
							if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
								obj.removeClass("checked");
								obj.find(".choose").hide();
								obj.parent().find("input").removeAttr("checked");
							}
						}else if(rental_way == 1){
							if(status == 2){  //已出租
								if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0 && arr != ''){
									$.each(arr, function(k, v) {
										if(v != ''){
											obj.removeClass("checked");
											obj.find(".choose").hide();
											obj.parent().find("input").removeAttr("checked");
										}
									});
								}
							}else{
								if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
									obj.removeClass("checked");
									obj.find(".choose").hide();
									obj.parent().find("input").removeAttr("checked");
								}
							}

						}
					}else if(house_type == 1){
						if(rental_way == 2){
							if(floor != '' && count != '' && hall != '' && toilet != '' && money != '' && area != '' && is_pic != 0){
								obj.removeClass("checked");
								obj.find(".choose").hide();
								obj.parent().find("input").removeAttr("checked");
							}
						}else if(rental_way == 1){
							if(status == 2){
								if(floor != '' && count != '' && hall != '' && toilet != '' && money != '' && area != '' && is_pic != 0 && arr != ''){
									$.each(arr, function(k, v) {
										if(v != ''){
											obj.removeClass("checked");
											obj.find(".choose").hide();
											obj.parent().find("input").removeAttr("checked");
										}
									});
								}
							}else{
								if(floor != '' && room_type != '' && money != '' && area != '' && is_pic != 0){
									obj.removeClass("checked");
									obj.find(".choose").hide();
									obj.parent().find("input").removeAttr("checked");
								}
							}

						}
					}
				});
			};
		});



		/**
		 * 全选及单选
		 * 2015-06-16
		 *
		 */
		var checkBoxEvent = function(){
			$(".check-box",$$).off("click").on("click",function(){
				var obj = $(this).find("label");
				obj.toggleClass("checked");
				if(obj.hasClass("checked")){
					obj.children(".choose").show();
					obj.next().attr("checked",true);
					if(obj.parent().hasClass("check-all")){
						obj.parents(".tool").siblings(".house-tab").find(".check-box label").addClass("checked");
						obj.parents(".tool").siblings(".house-tab").find(".check-box label .choose").show();
						obj.parents(".tool").siblings(".house-tab").find(".check-box input").attr("checked",true);
					}
				}else{
					obj.children(".choose").hide();
					obj.next().removeAttr("checked");
					if(obj.parent().hasClass("check-all")){
						obj.parents(".tool").siblings(".house-tab").find(".check-box label").removeClass("checked");
						obj.parents(".tool").siblings(".house-tab").find(".check-box label .choose").hide();
						obj.parents(".tool").siblings(".house-tab").find(".check-box input").removeAttr("checked");
					}
				};
			});
		}
//		checkBoxEvent();

		/**
		 * 公寓下详情收起、隐藏
		 *
		 */
		$(".hc-switch",$$).off("click").on("click",function(){
			if($(this).hasClass("current")){
				$(this).removeClass("current");
				$(this).css('background-position','-19px -1102px');
				$(this).parents(".hc-title").next(".hc-detail-box").fadeIn(400);
				$(this).parents(".hc-title").removeClass("current");
			}else{
				$(this).addClass("current");
				$(this).css('background-position','-19px -1127px');
				$(this).parents(".hc-title").next(".hc-detail-box").fadeOut(400);
				$(this).parents(".hc-title").addClass("current");
			}
		});

		/**
		 * 设置为封面效果 
		 *
		 */
		var pictureSet = function(){
			$(".uploader-area",$$).on("click",".pc-use",function(){
				var filename = $(this).parent().attr("filename");
				$(this).parents(".upload-imgview").addClass("cover").siblings().removeClass("cover");
				$(this).text("封面").parents(".upload-imgview").siblings().find(".pc-use").text("设置封面");
			});
		};
		pictureSet();

		/**
		 * 详情事件
		 * 2015-06-16
		 *
		 */
		var detailEvent = {
			detailClick: function(){
				$(".detail",$$).off("click").on("click",function(){
					detailEvent.detailShow(this);
				});
			},
			detailShow: function(el){
				$(".detail-check-box",$$).find("label").removeClass("checked").find(".choose").hide();
				$(".detail-check-box",$$).find("input").attr("checked",false);
				$(".uploadview-wrapper",$$).siblings(".upload-imgview").remove();
			
			
				
				var that = list_that = $(el);
				var res = that.siblings(".hide-msg").attr("hid"); //取隐藏哉的值
				if(res != undefined){
					res = JSON.parse(res);
				}
				var pic_length = that.parents("tr").find(".green.current").length;    //已设置为精品房源的数量
				
				//================保存以后需要把修改了的数据重新保存到这里=================
				var that_span = that.siblings(".check-box").find("span");
				var house_type = that_span.attr("house_type");
				var rental_way = that_span.attr("rental_way");
				var house_id = that_span.attr("house_id");
				var floor = that_span.attr("floor");
				var area = that_span.attr("area");
				if(area == '0.00') area = '--';
				var money = that_span.attr("money");
				if(money == '0.00') money = '--';
				var room_type = that_span.attr("room_type");
				if(room_type == '其他户型') room_type = '--';
				var room_id = that_span.attr("room_id");
				var count = that_span.attr("count");
				var hall = that_span.attr("hall");
				var toilet = that_span.attr("toilet");
				var house_name = that_span.attr("house_name");
				var dead_line = that_span.attr("dead_line");
				var city_id = that.siblings(".check-box").find("span").attr("city_id");
				var flat_id = $(".house-btn").attr("flat_id");
				var community_name = that_span.attr("community_name");
				var cover = that_span.attr("cover");
				var recom = that_span.attr("is_recom");
				//传参（隐藏）
				$(".introduce-tab",$$).attr("house_type",house_type).attr("house_id",house_id).attr("room_id",room_id).attr("rental_way",rental_way).attr("city_id",city_id).attr("flat_id",flat_id);
				var type = "GET";
				var data = {
					"house_type": house_type,
					"rental_way": rental_way,
					"room_id": room_id,
				};
				if(house_id != '' && house_id != undefined){
					data.house_id = house_id;
				}
				var url = that.attr("dataurl");
				ajax.doAjax(type,url,data,function(data){
					if(data.status == 1){
						var st_detail = $(".st-detail");
						st_detail.fadeIn(500);
						st_detail.siblings().hide();
						$(".st-detail-title",$$).css("width","auto");
						$(".st-detail-title a",$$).css("right","2%");
						
						var h = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
						st_detail.css({
							"height": h,
							"top": 45,
//							"left": 0,
						});
						$(window).resize(function(){
							var h = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
							st_detail.css({
								"height": h,
								"top": 45,
//								"left": 0,
							});
						});
						//详情滚动条
						var _h = $(window).height() - 200;
						$(".detail-main-box",$$).css({
							"height": _h,
							"overflow-y": "auto"
						});
						$(window).resize(function(){
							var height = $(window).height() - 200;
							$(".detail-main-box",$$).css({
								"height": height,
								"overflow-y": "auto"
							});
						});
	
						if(that.parents(".house-con").hasClass("centralize")){
							if(that.siblings(".check-box").find("span").hasClass("blue")){
								$(".main_edit",$$).hide();
							}else if(that.siblings(".check-box").find("span").hasClass("red")){
								$(".main_edit",$$).hide();
								$(".main_contract",$$).hide();
							}else if(that.siblings(".check-box").find("span").hasClass("black")){
								$(".main_detail",$$).hide();
								$(".main_contract",$$).hide();
							}
						}else if(that.parents(".house-con").hasClass("disperse")){
							if(that.siblings(".check-box").find("span").hasClass("blue")){
								$(".main_edit",$$).hide();
							}else if(that.siblings(".check-box").find("span").hasClass("red")){
								$(".main_edit",$$).hide();
								$(".main_contract").hide();
							}else if(that.siblings(".check-box").find("span").hasClass("black")){
								$(".main_detail").hide();
								$(".main_contract").hide();
							}
						}
						//判断是否发布房源
						if(that.siblings(".check-box").find("label").hasClass("checked")){
							$(".is_release .check-box label").addClass("checked").find(".choose").attr("style","display: inline");
							$(".is_release .check-box label").siblings("input").attr("checked","checked");
						}
						//判断是否为精品房源
						if(recom == 1){
							$(".is_recom .check-box label").addClass("checked").find(".choose").attr("style","display: inline");
							$(".is_recom .check-box label").siblings("input").attr("checked","checked");
						}
						//判断整租还是合租
						if(rental_way == 2){
							$(".house_type",$$).text("整租");
						}else{
							$(".house_type",$$).text("合租");
						}
						$(".floor",$$).text(floor);											
						$(".area",$$).text(area);												
						$(".money",$$).text(money);
						}

						if(dead_line != 0){
							var _dead_line = UnixToDate(dead_line);
							$(".contract_term",$$).text(_dead_line);
						}
						if(house_type == 2){
							$(".room_type",$$).text(room_type);
						}else{
							$(".room_type",$$).text(count + '室' + hall + '厅' + toilet + '卫');
						}
						//判断户型、面积、租金是否齐全
						if($(".area",$$).text() == '--' || $(".money",$$).text() == '--' || $(".room_type",$$).text() == '--'){
							$(".improve-msg",$$).show();
							$(".improve-msg a",$$).click(function(){
								//跳转页面
								var house_edit_url = data.house_edit_url;
								if(house_edit_url != '' && house_edit_url != undefined){
									window.parent.location = "/#" + house_edit_url;
								}
								
								//轮循
								var timing = null;
								timing = setInterval(function(){
									var house_id = $(".introduce-tab",$$).attr("house_id");
									if(house_id == undefined){
										house_id = '';
									}
									var room_id = $(".introduce-tab",$$).attr("room_id");
									var house_type = $(".introduce-tab",$$).attr("house_type");
									var rental_way = $(".introduce-tab",$$).attr("rental_way");
									var data = {
										"house_id": house_id,
										"room_id": room_id,
										"house_type": house_type,
										"rental_way": rental_way
									}
									var cycleUrl = $(".st-detail-title",$$).attr("dataUrl");
									var type = "POST";
									ajax.doAjax(type,cycleUrl,data,function(json){
										if(json.rent != '0.00' && json.area != '0.00' && json.room_type != '0室0厅0卫'){
											clearInterval(timing);
											$(".area",$$).text(json.area);
											$(".money",$$).text(json.rent);
											$(".room_type",$$).text(json.room_type);
											$(".improve-msg",$$).hide();
											$(".setmoney",$$).val(json.rent);
										}
									});
								},2000);
							});
						}else{
							$(".improve-msg",$$).hide();
						}
	
						//判断室友是否显示
						if(data.shiyou != '' && data.shiyou != undefined){
							$(".main_roommate",$$).show();
							$(".roommate-tab tr",$$).not(":first").remove();
							var msg = data.shiyou;
							var _html;
							$.each(msg, function(k, v){
								if(v.name == undefined) v.name = "";
								if(v.gender == 1){
									v.gender = '男';
								}else{
									v.gender = '女';
								}
								if(v.profession == ''){
									v.profession = '职员';
								}
								if(v.area == null){
									v.area = '<input type="text" class="ipt ipt-90" /><i>*</i>';
								}
								if(v.money == null){
									v.money = '<input type="text" class="ipt ipt-90" /><i>*</i>';
								}
								_html += '<tr class="roommate-tr"' + 'contract_id=' +  v.contract_id + ' house_id=' + v.house_id + ' house_type=' + v.house_type + ' room_id=' + v.room_id + ' tenant_id=' + v.tenant_id
								_html += '>'
								_html += '<td class="gender">' + v.name + '</td>',
								_html += '<td class="gender">' + v.gender + '</td>',
								_html += '<td class="profession"><input type="text" class="ipt ipt-90" value="'+ v.profession +'" /></td>',
								_html += '<td class="roommate-area">' + v.area + '</td>',
								_html += '<td class="roommate-money">' + v.money + '</td>';
								_html += '</tr>';
							});
							$(".main_roommate",$$).find(".roommate-tab").append(_html);
	
						}else{
							$(".main_roommate",$$).hide();
						}
						//图片
						if(data.picture != '' && data.picture != undefined){
							var _html = '';
							$.each(data.picture, function(k, v) {
								_html += '<div filename="'+ v.keys +'" queueid="" class="upload-imgview">';
								_html += '<img class="" style="_display: none;" src="'+ v.key +'">';
								_html += '<span class="ie7bug"></span>';
								_html += '<div class="pc-shade"></div>';
	                            _html += '<div class="pc-use">设置封面</div>';
	                            _html += '<i class="ifont deleteImg">&#xe627;</i>';
								_html += '<input type="hidden" name="images[new][room1][]" value="'+ v.key +'">';
								_html += '</div>';
							});
							$(".uploadview-wrapper",$$).siblings().remove();
							$(".uploadview-wrapper",$$).before(_html);
							//调用删除图片
							deleteImg();
							
						}
						//显示封面
						var _cover;
						$.each($(".upload-imgview",$$), function(i, v) {    
							_cover = $(v).attr("filename");
							if(cover == _cover){
								$(v).addClass("cover").find(".pc-use").text("封面");
							}
						});
	
					
				});
	
				//复选框事件
				$(".detail-check-box",$$).off("click").on("click",function(){
					var obj = $(this).find("label");
					if($(this).parents("li").hasClass("is_recom")){
						if(obj.hasClass("checked")){
							obj.removeClass("checked");
							obj.children(".choose").hide();	
							obj.next().removeAttr("checked");
						}else{
							if(pic_length < 4){
								obj.toggleClass("checked");
								if(obj.hasClass("checked")){
									obj.children(".choose").show();
									obj.parents("li").siblings().find(".check-box label").addClass("checked").find(".choose").show();
									obj.parents("li").siblings().find(".check-box input").attr("checked",true);
									obj.next().attr("checked",true);
								}else{
									obj.children(".choose").hide();	
									obj.next().removeAttr("checked");				
								};
							}else{
								var d = dialog({
									title: '提示信息',
									content: '您已经推荐了四个精品房清，不能再推荐',
									okValue: '确定',
									ok: function () {
										d.close();
									}
								});
								d.showModal();
							}
						}

					}else{
						
						obj.toggleClass("checked");
						if(obj.hasClass("checked")){
							obj.children(".choose").show();
							obj.next().attr("checked",true);
						}else{
							obj.children(".choose").hide();	
							obj.next().removeAttr("checked");				
						};
					}
				});
				
				//赋值（）
				if(res != '' && res != undefined){
					if(res.is_recom == 1){
						$(".is_recom",$$).find("label").addClass("checked").find(".choose").show();
						$(".is_recom",$$).find("input").attr("checked",true);
					}
					if(res.is_release == 1){
						$(".is_release",$$).find("label").addClass("checked").find(".choose").show();
						$(".is_release",$$).find("input").attr("checked",true);
					}
					$(".setmoney",$$).val(res.setmoney);
					$("input[name = 'name']",$$).val(res.house_name);
				}else{
					$(".setmoney",$$).val(money);
					$("input[name = 'name']",$$).val(community_name);
				}

				//调用关闭
				returnEvent();
				//详情表单提交
				houseDetailSubmit.checkUI(function(jn){
					var _jn = JSON.stringify(jn);
					var that = list_that;
					var jn_cover = jn.cover;
					var jn_money = jn.setmoney;
					var jn_len = jn.img_len;
					var jn_area = jn.area;
					var jn_room_type = jn.room_type;
					var jn_is_recom = jn.is_recom;
					that.siblings(".check-box").find("span").attr({
						"cover": jn_cover,
						"is_pic": jn_len,
						"money": jn_money,
						"area": jn_area,
						"room_type": jn_room_type,
						"is_recom": jn_is_recom
					});
					that.siblings(".hide-msg").attr("hid",_jn);
					
					//判断是否发布房源
					if(jn.is_release == 1){
						that.siblings(".check-box").find("label").addClass("checked").find(".choose").show();
						that.siblings(".check-box").find("input").attr("checked",true);
					}else{
						that.siblings(".check-box").find("label").removeClass("checked").find(".choose").hide();
						that.siblings(".check-box").find("input").attr("checked",false);
					}
					//判断是否为精品房源
					if(jn.is_recom == 1){
						that.siblings(".green").addClass("current");
					}else{
						that.siblings(".green").removeClass("current");
					}
					//判断是否是可发布房源
					if(jn_len > 0){
						that.siblings(".check-box").find("span").addClass("line");
					}
				});
			},
		};
		detailEvent.detailClick();



		/**
		 * 点击返回退出“弹窗”
		 *
		 */
		var returnEvent = function(){
			$(".return",$$).off("click").on("click",function(){
				$(this).parents(".st-detail").fadeOut(500);
				$(".st-detail",$$).siblings().not("#hide-success-dialog").show();
				$(".main-show", parent.document).css("overflow-y","auto");
			});
		};

		/**
		 * 删除图片
		 *
		 */
		var deleteImg = function(){
			$(".upload-imgview .deleteImg",$$).off("click").on("click",function(){
				var that = $(this);
				var house_type = $(".introduce-tab",$$).attr("house_type"),
					house_id = $(".introduce-tab",$$).attr("house_id"),
					room_id = $(".introduce-tab",$$).attr("room_id"),
					rental_way = $(".introduce-tab",$$).attr("rental_way"),
					key = that.parent().attr("filename");
				var data = {
					"house_type": house_type,
					"room_id": room_id,
					"rental_way": rental_way,
					"key": key
				};
				var type = "POST";
				var url = $(".picture-detail",$$).attr("dataurl");
				//判断house_id是否存在
				if(house_id != '' && house_id != undefined){
					data.house_id = house_id;
				}
				ajax.doAjax(type,url,data,function(json){
					if(json.status == 1){
						that.parent(".upload-imgview").remove();
					}else{
						var d = dialog({
							title: '提示信息',
							content: json.message,
							okValue: '确定',
							ok: function () {
								d.close();
							}
						});
						d.showModal();
					}
				});
			});
		};


		


		/**
		 * 详情弹窗提交
		 *
		 */
		var houseDetailSubmit = {
			submitFom: function(){
				var house_type = $(".introduce-tab",$$).attr("house_type");
				var house_id = $(".introduce-tab",$$).attr("house_id");
				var room_id = $(".introduce-tab",$$).attr("room_id");
				var city_id = $(".introduce-tab",$$).attr("city_id");
				var flat_id = $(".introduce-tab",$$).attr("flat_id");
				var rental_way = $(".introduce-tab",$$).attr("rental_way");
				var floor = $(".floor",$$).text();
				var img_len = $(".upload-imgview img",$$).length;
				var room_type = $(".room_type",$$).text();
				var area = $(".area",$$).text();
				var money = $(".money",$$).text();
				var setmoney = $(".setmoney",$$).val();
				var is_recom;
				if($(".is_recom .check-box label",$$).hasClass("checked")){
					is_recom = 1;
				}else{
					is_recom = 0;
				}
				var sid = [], szhiye = [];
				$.each($(".roommate-tr",$$),function(k,v){
					var _sid = $(v).attr("tenant_id");
					sid.push(_sid);
					var _szhiye = $(v).find(".profession > input").val();
					szhiye.push(_szhiye);
//					var member = {
//						"szhiye": $(v).find(".profession > input").val(),
//						"sarea": $(v).find(".roommate-area").text(),
//						"sid": $(v).attr("tenant_id"),
//						"shouse_id": $(v).attr("house_id"),
//						"sroom_id": $(v).attr("room_id"),
//						"shouse_type": $(v).attr("house_type")
//					};
//					roommate.push(member);

				});
				//图片
				var pic = [];
				var cov = '';
				$.each($(".upload-imgview",$$), function(k, v) {
					var _pic = $(v).attr("filename");
					if(_pic != '' && _pic != undefined){
						pic.push(_pic);
					}	
					if($(v).hasClass("cover")){
						cov = $(v).attr("filename");
					}
				});

				var type = "POST";
				var data = {
					"house_type": house_type,
					"room_id": room_id,
					"rental_way": rental_way,
					"floor": floor,
					"room_type": room_type,
					"city_id": city_id,
					"flat_id": flat_id,
					"area": area,
					"money": money,
					"setmoney": setmoney,
					"is_recom": is_recom,
					"sid": sid,
					"szhiye": szhiye
				};
				//判断house_id是否存在
				if(house_id != '' && house_id != undefined){
					data.house_id = house_id;
				}
				if(pic != '' && pic != undefined){
					data.pic = pic;
				}
				var url = $(".st-detail-btn",$$).attr("dataurl");
				ajax.doAjax(type,url,data,houseDetailSubmit.callback);    //提交


				//操作隐藏域
				var house_name = $("input[name = 'name']",$$).val();
				var is_recom;
				if($(".is_recom .check-box label",$$).hasClass("checked")){
					is_recom = 1;
				}else{
					is_recom = 0;
				}
				var is_release;
				if($(".is_release .check-box label",$$).hasClass("checked")){
					is_release = 1;
				}else{
					is_release = 0;
				}
				var hideValue = {
					"house_name": house_name,
					"setmoney": setmoney,
					"is_recom": is_recom,
					"is_release": is_release,
					"pic": pic,
					"cover": cov,
					"img_len": img_len
				}
//				$(".hide-msg",$$).data("hid",hideValue);
//				var res = $(".hide-msg",$$).data("hid");
				return hideValue;

			},
			callback: function(data){
				if(data.status == 1){
					$(".st-detail",$$).fadeOut(500);
					$(".st-detail",$$).siblings().not("#hide-success-dialog").show();
					$(".main-show",$$).css("overflow-y","auto");
					//刷新标签
//					WindowTag.loadTag(data.url,'get',function(){});

				}else{
					var d = dialog({
						title: '提示信息',
						content: data.message,
						okValue: '确定',
						ok: function () {
							d.close();
						}
					});
					d.showModal();
				}
			},
			checkUI: function(callfun){
				$('.st-detail',$$).Validform({
					btnSubmit : ".st-detail-btn",
					showAllError : true,
					ignoreHidden : true,
					tiptype: function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            callback: function(){
						var len = $(".upload-imgview",$$).length;
						var room_type = $(".room_type",$$).text();
						var area = $(".area",$$).text();
						var money = $(".money",$$).text(); 
		            	if(len <= 0){
		            		var d = dialog({
								title: '提示信息',
								content: '房间图片不能少于1张',
								okValue: '确定',
								ok: function () {
									d.close();
								}
							});
							d.showModal();
		            	}else if(room_type == '--' || area == '--' || money == '--'){
		            		var d = dialog({
								title: '提示信息',
								content: '请先完善信息',
								okValue: '确定',
								ok: function () {
									d.close();
								}
							});
							d.showModal();
		            	}else{
		            		var jn = houseDetailSubmit.submitFom();
		            		if(callfun != undefined){
		            			callfun(jn);
							}
		            	}
		            }
				});
			}
		};


		/**
		 * 房源表单提交
		 *
		 *
		 */
		var houseMsgSubmit = {
			submitForm: function(){
				var house_id = [], room_id = [], house_type= [], rental_way = [], house_name = [], community_id = [], community_name = [], address = [],
					rent = [], area_id = [], business_id = [], area = [], floor = [], city_id = [], status = [], is_recom = [], expire_time = [], online_time = [], flat_id = [], domain_name = [], wxxzid = [], cover = [];
				var _flat_id = $(".house-btn",$$).attr("flat_id");
				flat_id.push(_flat_id);
				var _domain_name = $(".house-btn",$$).attr("domain_name");
				domain_name.push(_domain_name);
				$.each($(".hc-detail .check-box span",$$),function(k,v){
					checkBoxEvent();
					if($(v).siblings("label").hasClass("checked")){
						var hideVal = $(v).parents(".check-box").siblings(".hide-msg").data("hid");
//						console.log(hideVal);
						var _house_name;
						var _rent;
						var _is_recom;
						var coverKey;
						var hideVal = $(v).parents(".check-box").siblings(".hide-msg").attr("hid");
						if(hideVal != undefined){
							hideVal = JSON.parse(hideVal);
							_house_name = hideVal.house_name;
							_rent = hideVal.setmoney;
							_is_recom = hideVal.is_recom;
							coverKey = hideVal.cover;
						}else{
							_house_name = $(v).attr("house_name");
							_rent = $(v).attr("money");
							if($(v).parents(".check-box").siblings("span").hasClass("current")){
								_is_recom = 1;
							}else{
								_is_recom = 0;
							}
							coverKey = '';
						}
						var _house_id = $(v).attr("house_id");
						if(_house_id == undefined){
							_house_id = 0;
						}
						house_id.push(_house_id);

						room_id.push($(v).attr("room_id"));
						house_type.push($(v).attr("house_type"));
						rental_way.push($(v).attr("rental_way"));
						house_name.push(_house_name);
						community_id.push($(v).attr("community_id"));
						community_name.push($(v).attr("community_name"));
						address.push($(v).attr("address"));
						rent.push(_rent);
						area_id.push($(v).attr("area_id"));
						business_id.push($(v).attr("business_id"));
						area.push($(v).attr("area"));
						floor.push($(v).attr("floor"));
						city_id.push($(v).attr("city_id"));
						status.push($(v).attr("status"));
						is_recom.push(_is_recom);
						expire_time.push($(v).attr("dead_line"));
						online_time.push($(v).attr("online_time"));
						cover.push(coverKey);
					}
				});
				$.each($(".hc-detail .check-box input",$$), function(i, o) {
					if($(o).siblings("label").hasClass("checked")){
						var xz_id = $(o).attr("wxxzid");
						if(xz_id != '' && xz_id != undefined){
							wxxzid.push(xz_id);
						}
					}

				});
				var type = "POST";
				var url = $(".house-btn",$$).attr("dataurl");
				var data = {
					"house_id": house_id,
					"room_id": room_id,
					"house_type": house_type,
					"rental_way": rental_way,
					"house_name": house_name,
					"community_id": community_id,
					"community_name": community_name,
					"address": address,
					"rent": rent,
					"area_id": area_id,
					"business_id": business_id,
					"area": area,
					"floor": floor,
					"city_id": city_id,
					"status": status,
					"is_recom": is_recom,
					"expire_time": expire_time,
					"online_time": online_time,
					"flat_id": flat_id,
					"domain_name": domain_name,
					"wxxzid": wxxzid,
					"cover": cover
				};
				ajax.doAjax(type,url,data,houseMsgSubmit.callback);

			},
			callback: function(data){
				if(data.status == 1){
					var tag = WindowTag.getCurrentTag();
					//关闭当前标签
					WindowTag.closeTag(tag.find('>a:first').attr('url'));
					var dialogHtml = $("#hide-success-dialog",$$).html();
					var d = dialog({
						title: '<i class="ifont1">&#xf0075;</i><span>提交房源信息</span>',
						content: dialogHtml
					});
					d.showModal();
					//关闭按钮
					$(".cancel-btn, .fun-btn").off("click").on("click",function(){
						d.close();
					});
					//刷新标签
					var plugIndexTag = WindowTag.getTagByUrlHash(urlHelper.make('plugins-index/index'));
					var editsetbasicinfoTag = WindowTag.getTagByUrlHash(urlHelper.make('plugins-subsite/editsetbasicinfo'));
					if(plugIndexTag){
						if(plugIndexTag.hasClass('current')){
							WindowTag.loadTag(plugIndexTag.find('a:first').attr('href'));
						}else{
							plugIndexTag.attr('reload',1);
						}
					}
					if(editsetbasicinfoTag){
						if(editsetbasicinfoTag.hasClass('current')){
							WindowTag.loadTag(editsetbasicinfoTag.find('a:first').attr('href'));
						}else{
							editsetbasicinfoTag.attr('reload',1);
						}
					}
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
			},
			checkUI: function(){
				$('.open-house-set',$$).Validform({
					btnSubmit : ".house-btn",
					showAllError : true,
					ignoreHidden : true,
					tiptype: function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            callback: function(){
//		            	if($(".house-con").find("input[type='checkbox']:checked").length == 0){
//		            		var d = dialog({
//								title: '提示信息',
//								content: '请至少选择一个房源',
//								okValue: '确定',
//								ok: function(){
//									d.close();
//								}
//							});
//							d.showModal();
//		            	}else{
		            		houseMsgSubmit.submitForm();
//		            	}

		            }
				});
			}
		}
		houseMsgSubmit.checkUI();

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


	}

	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);

	};

});