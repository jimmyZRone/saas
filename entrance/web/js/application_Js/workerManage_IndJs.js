define(function(require,exports){
	/*
	 * @desc:员工管理首页
	 * @date:2015-3-31
	 */
	var $ = require("jquery");
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax"),ajaxLoading;
	var loading=require("loading");
	var $$ = null;
	var dialog = require("dialog");  //弹窗插件
	/*
	 * @desc:表格奇数行变色
	 * @date:2015-3-31
	 */
	function table_FisrtTr(){
		$(".workerManage_Ind_b > table").find("tr:even").css("background-color","#f6f6f6");
		if(sys.ie && sys.ie < 7){
			$(".workerManage_Ind_b > table > tbody >tr").hover(function(){
				$(this).addClass("ie6Hover");
			},function(){
				$(this).removeClass("ie6Hover");
			});
		}
	}

	/*
	 * @func:changeToDelete
	 * @desc:点击删除按钮切换到删除页面，再次点击删除选择项并提交
	 * @date:2015-3-31
	 */
	function changeToDelete(){
		$(".delete_workerManage_Ind",$$).off("click").on("click",function(){
//			var uid = [];
			$(".workerManage_Ind_a > .b").fadeOut(500);
			$(".workerManage_Ind_a > .a > ul > li").hide();
			$(".workerManage_Ind_a > .a > ul > li.checkAllBox,.workerManage_Ind_a > .a > ul > li.giveUpCheck,.workerManage_Ind_a > .a > ul > li.a_delete").show();
			$(".workerManage_Ind_b .checkBox").fadeIn(500);
			if($(this).hasClass("deleteStyle")){	//拥有删除状态，点击执行删除操作
					var url = $(this).attr("deurl");
		   			var type = "post";
		   			var deletelist = [];
		   			$(".workerManage_Ind_b table tr:gt(0)",$$).each(function(){
		   				if($(this).find("input:checked").size() > 0){
		   					var worker_id = $(this).attr("uid");
		   					var worker_name = $(this).find(".workername").text();
		   					var _eleAuto = {
		   						worker_id : worker_id,
		   						worker_name : worker_name
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

	}
	var deleteauto = function(deletedata){
			var cloneStr = $(".deletemoreauto",$$).clone();
		   	  cloneStr.removeClass("none");
		   	  var deleteTptal = deletedata.deletelist.length;
		   	  cloneStr.find(".num_total").text(deleteTptal);
		   	  var dd = dialog({
						title: '<i class="ifont">&#xe675;</i><span>删除员工</span>',
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
					$(".workerManage_Ind_b table tr:gt(0)",$$).each(function(){
		   				if($(this).find("input:checked").size() > 0){
		   					objsauto = $(this);
		   					num_cur++
		   					return false;
		   				}
		   			 });
		   			 objAuto.find(".num_cur").text(num_cur);
		   			 var trstr = '<tr><td class="zb">'+objsauto.find(".workername").text()+'</td><td class="yb">正在删除</td></tr>';
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
							var num_worker = $(".num_worker_auto",$$).text()/1;
							num_worker = num_worker-1;
							$(".num_worker_auto",$$).text(num_worker);
							if($(".workerManage_Ind_b table",$$).find("tr").length <= 1){
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
	/*
	 * @func:deleteLineTab
	 * @desc:点击单行删除按钮删除并提交
	 * @date:2015-4-14
	 */
	function deleteLineTab(){
		$(".delete_LineTab",$$).off("click").on("click",function(){
			var that = $(this);
			var uid = that.parents("tr").attr("uid");
			var dd = dialog({
				title: '<i class="ifont">&#xe77d;</i><span>删除员工</span>',
				content: '确认删除该员工？',
				okValue: '确 定', drag: true,
				ok: function () {
					var type = "post";
					var urlso = that.attr("deurl");
					var data = {
						"uid" : uid
					};
					ajax.doAjax(type,urlso,data,function(data){
						if(data.status == 1){
							that.parents("tr").remove();
							var num_worker = $(".num_worker_auto",$$).text()/1;
							num_worker = num_worker-1;
							$(".num_worker_auto",$$).text(num_worker);
							if($(".workerManage_Ind_b table",$$).find("tr").length <= 1){
								//刷新当前标签
								var tag = WindowTag.getCurrentTag();
								var url = tag.find('>a:first').attr('url');
								WindowTag.loadTag(url,'get',function(){});
							}
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
	}

	/*
	 * @func:delete_CheckBox
	 * @desc:删除状态下全选及多选效果
	 * @date:2015-3-31
	 */
	function delete_CheckBox(){
		//全选
		$(".workerManage_Ind_a > .a > ul > li.checkAllBox > .checkAll").off("click").on("click",function(){
			$(this).toggleClass("checked");
			if($(this).hasClass("checked")){
				$(this).children(".ifont").show();
				$(".workerManage_Ind_b .checkBox").addClass("checked");
				$(".workerManage_Ind_b .checkBox > .gou").show().next().attr("checked",true);
			}else{
				$(this).children(".ifont").hide();
				$(".workerManage_Ind_b .checkBox").removeClass("checked");
				$(".workerManage_Ind_b .checkBox > .gou").hide().next().removeAttr("checked");
			}
		});
		$(".workerManage_Ind_a > .a > ul > li.checkAllBox > a").off("click").on("click",function(){
			var obj = $(this).siblings(".checkAll");
			obj.toggleClass("checked");
			if(obj.hasClass("checked")){
				obj.children(".ifont").show();
				$(".workerManage_Ind_b .checkBox").addClass("checked");
				$(".workerManage_Ind_b .checkBox > .gou").show().next().attr("checked",true);
			}else{
				obj.children(".ifont").hide();
				$(".workerManage_Ind_b .checkBox").removeClass("checked");
				$(".workerManage_Ind_b .checkBox > .gou").hide().next().removeAttr("checked");
			}
		});
		//单选
		$(".workerManage_Ind_checkBox").off("click").on("click",function(event){
			event.preventDefault();
			$(this).toggleClass("checked");
			if($(this).hasClass("checked")){
				$(this).children(".gou").show().next().attr("checked",true);
			}else{
				$(this).children(".gou").hide().next().removeAttr("checked");
			}
		});
	}
	/*
	 * @func:list_CallBack
	 * @desc:翻页
	 * @date:2015-4-14
	 */
	function list_CallBack(index){
		var search = $(".search_Txt",$$).val();
		if($(".search_Txt",$$).val() == $(".search_Txt",$$).attr("placeholder") || !$(".search_Txt",$$).hasClass("searchStyle")) search = '';
		if(!!!index) index=0;
		var current_Page = index+1;
		var urll = $(".jzf-pagination",$$).attr("url");
		var data = {
			"view" : search,
			"page" : current_Page
		}
		var trpe = "get";
		var tp=loading.genLoading("tr","5",1);
		$('.workerManage_Ind_b table tr:gt(0)',$$).remove();
		$('.workerManage_Ind_b table',$$).append(tp);
		ajax.doAjax(trpe,urll,data,function(data){
			var pages_Total = data.page.count;   //每页条数
			var pages_Count = data.page.size;   //总共条数		
			if(data.status == 1){
				var nums_pages = data.page.cpage;
				if( data.page.page == 1){
					$(".staff_List", $$).addClass("none");
					ajax.iniPagination(pages_Total,"#workerManage_Ind_b_fy",list_CallBack,pages_Count);
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
								list_CallBack(page_jump-1);
							}
						});
				}
				$(".jzf-pagination",$$).show();
				dataUpdate(data);
			}else{
				$(".staff_List", $$).removeClass("none");
				$('.workerManage_Ind_b table tr:gt(0)',$$).remove();
				var errorTemp=loading.genLoading("tr","5",2);
				//$('.workerManage_Ind_b table',$$).append(errorTemp);
				$(".jzf-pagination",$$).hide();
			}
			});
	}
	
	//数据更新及事件添加
	function dataUpdate(data){
		$('.workerManage_Ind_b table tr:gt(0)',$$).remove();
		$('.workerManage_Ind_b table',$$).append(data.data);
		//表格奇数行变色
		table_FisrtTr();
		//点击删除按钮，切换到删除页面
		changeToDelete();

		//删除单行
		deleteLineTab();

		//删除状态下全选及多选
		delete_CheckBox();
		
		//翻页时恢复非删除状态
		$(".workerManage_Ind_a > .a > ul > li.giveUpCheck > a").click(function(){
			$(".delete_workerManage_Ind").removeClass("deleteStyle");
			$(".workerManage_Ind_a > .b").fadeIn(500);
			$(".workerManage_Ind_a > .a > ul > li").hide();
			$(".workerManage_Ind_a > .a > ul > li.fzManage,.workerManage_Ind_a > .a > ul > li.addWorkers,.workerManage_Ind_a > .a > ul > li.a_delete,.workerManage_Ind_a > .a > ul > li.print").show();
			$(".workerManage_Ind_b .checkBox").removeClass("checked");
			$(".workerManage_Ind_b .checkBox > .gou").hide().next().removeAttr("checked");
			$(".workerManage_Ind_b .checkBox").hide();
		}).trigger("click");
	}	
	
	exports.inite = function(__html__){
		$$ = __html__;
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			require("placeholder")($);
			$(".workerManage_Ind_a > .b").placeholder();
		};
		
		//翻页
		list_CallBack(0);
		
		$(".search_workerManage_Ind",$$).off("click").on("click",function(){
			$(this).prev().addClass("searchStyle");
			list_CallBack(0);
		});
	}
});
