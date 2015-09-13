define(function(require,exports){
	/*
	 * 员工管理-权限组列表
	 * 
	 */
	var $ = require("jquery");
	var navigators = require("navigatortest");  //浏览器版本检测
		require("pagination");					//翻页
	var ajax = require("Ajax");
	var $$ = null;
	var dialog = require("dialog");  //弹窗插件

	var modelInit = function($$){
		/**
		 * 表格奇数行变色
		 * 
		 */
		$(".workerManage_AuthorityManage_b > table").find("tr:even").css("background-color","#f6f6f6");
		var tr_len = $(".workerManage_AuthorityManage_b > table > tbody > tr");
		if (tr_len.length < 2) {
			$(".group_List", $$).removeClass("none");
		}
		if(sys.ie && sys.ie < 7){
			$(".workerManage_AuthorityManage_b > table > tbody >tr").hover(function(){
				$(this).addClass("ie6Hover");
			},function(){
				$(this).removeClass("ie6Hover");
			});	
		}
		
		/**
		 * 删除状态下全选及多选
		 * 
		 */
		var delete_CheckBox = function(){
			//全选
			$(".workerManage_AuthorityManage_a > .a > ul > li.checkAllBox > .checkAll").off("click").on("click",function(){
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children(".ifont").show();
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox").addClass("checked");
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox > .gou").show().next().attr("checked",true);
				}else{
					$(this).children(".ifont").hide();
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox").removeClass("checked");
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox > .gou").hide().next().removeAttr("checked");
				}
			});
			$(".workerManage_AuthorityManage_a > .a > ul > li.checkAllBox > a").off("click").on("click",function(){
				var obj = $(this).siblings(".checkAll");
				obj.toggleClass("checked");
				if(obj.hasClass("checked")){
					obj.children(".ifont").show();
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox").addClass("checked");
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox > .gou").show().next().attr("checked",true);
				}else{
					obj.children(".ifont").hide();
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox").removeClass("checked");
					$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox > .gou").hide().next().removeAttr("checked");
				}
			});
			//单选
			$(".workerManage_AuthorityManage_checkBox").off("click").on("click",function(event){
				event.preventDefault();
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children(".gou").show().next().attr("checked",true);	
				}else{
					$(this).children(".gou").hide().next().removeAttr("checked");
				}
			});
		};
		delete_CheckBox();
		
		
		/**
		 * 开启删除界面并实现删除操作
		 * 
		 */
		var changeToDelete = function(){
			$(".delete_workerManage_AuthorityManage").off("click").on("click",function(){
				$(".workerManage_AuthorityManage_a > .a > ul > li").hide();
				$(".workerManage_AuthorityManage_a > .a > ul > li.checkAllBox,.workerManage_AuthorityManage_a > .a > ul > li.giveUpCheck,.workerManage_AuthorityManage_a > .a > ul > li.a_delete").show();
				$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox").fadeIn(500);
				if($(this).hasClass("deleteStyle")){	//拥有删除状态，点击执行删除操作
					var url = $(this).attr("data-url");
		   			var type = "post";
		   			var deletelist = [];
		   			$(".workerManage_AuthorityManage_b table tr:gt(0)",$$).each(function(){
		   				if($(this).find("input:checked").size() > 0){
		   					var group_id = $(this).attr("uid");
		   					var group_name = $(this).find(".group_name").text();
		   					var _eleAuto = {
		   						group_id : group_id,
		   						group_name : group_name
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
		   			deleteauto(deletedata);
				}
				$(this).addClass("deleteStyle");
			});
		};
		changeToDelete();
		var deleteauto = function(deletedata){
			var cloneStr = $(".deletemoreauto",$$).clone();
		   	  cloneStr.removeClass("none");
		   	  var deleteTptal = deletedata.deletelist.length;
		   	  cloneStr.find(".num_total").text(deleteTptal);
		   	  var dd = dialog({
						title: '<i class="ifont">&#xe675;</i><span>删除权限组</span>',
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
				var num_cur = 0;  //计数器
				function autodeleteus(){
					if(objAuto.hasClass("stop")) return false;
					var objsauto = null;
					$(".workerManage_AuthorityManage_b table tr:gt(0)",$$).each(function(){
		   				if($(this).find("input:checked").size() > 0){
		   					objsauto = $(this);
		   					num_cur++
		   					return false;
		   				}
		   			 });
		   			 objAuto.find(".num_cur").text(num_cur);
		   			 var trstr = '<tr><td class="zb">'+objsauto.find(".group_name").text()+'</td><td class="yb">正在删除</td></tr>';
		   			 tableauto.append(trstr);
		   			 if(num_cur > 5){
						tableauto.find("tr:eq("+(num_cur-5)+")").fadeOut(300);
					}
		   			 var trcur = tableauto.find("tr:last");
		   			 var idauto = objsauto.attr("uid");
		   			 var data = {
						uid : idauto
					};
					ajax.doAjax(type,url,data,function(data){
						if(data.status == 0){
							trcur.find(".yb").addClass("red").removeClass("blue").text(data.message);
							objsauto.find(".checkBox").removeClass("checked").children(".gou").hide();
							objsauto.find(".checkBox").children("input").removeAttr("checked");
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
							if($(".workerManage_AuthorityManage_b table",$$).find("tr").length <= 1){
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
		}
		/**
		 * 点击单行删除按钮删除权限组
		 * 
		 */
		var deleteLineTab = function(){
			$(".delete_LineTab",$$).off("click").on("click",function(){
				var that = $(this);
				
				var uid = that.parents("tr").attr("uid");
				var dd = dialog({
					title: '<i class="ifont">&#xe77d;</i><span>删除员工</span>',
					content: '删除的信息将无法得到恢复，确定删除？',
					okValue: '确 定', drag: true,
					ok: function () {
						var type = "post";
						var urlso = that.attr("data-url");
						var data = {
							"uid" : uid
						};
						ajax.doAjax(type,urlso,data,function(info){
							if(info.status == 1){
								that.parents("tr").remove();
								var d = dialog({
									title: '提示信息',
									content: '删除成功',
									okValue: '确定',
									ok: function(){
										d.close();
										if($(".workerManage_AuthorityManage_b table",$$).find("tr").length <= 1){
											//刷新当前标签
											var tag = WindowTag.getCurrentTag();
											var url = tag.find('>a:first').attr('url');
											WindowTag.loadTag(url,'get',function(){});
										}
									}
								});
								d.showModal();
							}else{
								var d = dialog({
									title: '提示信息',
									content:info.message,
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
		}
		deleteLineTab();
		
		/**
		 * 取消删除
		 * 
		 */
		var chanceToCancel = function(){
			$(".workerManage_AuthorityManage_a > .a > ul > li.giveUpCheck > a").click(function(){
				$(".delete_workerManage_AuthorityManage").removeClass("deleteStyle");
				$(".workerManage_AuthorityManage_a > .b").fadeIn(500);
				$(".workerManage_AuthorityManage_a > .a > ul > li").hide();
				$(".workerManage_AuthorityManage_a > .a > ul > li.fzManage,.workerManage_AuthorityManage_a > .a > ul > li.addWorkers,.workerManage_AuthorityManage_a > .a > ul > li.a_delete,.workerManage_AuthorityManage_a > .a > ul > li.print").show();
				$(".workerManage_AuthorityManage_b > table > tbody > tr > td.first_T > .checkBox").hide();
			});	
		};
		chanceToCancel();
		
		
		
		
	};
		
	exports.inite = function(__html__){
		modelInit(__html__);
	};
	
	
	
});