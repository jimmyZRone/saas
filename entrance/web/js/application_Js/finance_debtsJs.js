define(function(require,exports,module){
	var $ = require('jquery'),
		ajax=require("Ajax");
		require("selectByM")($);
		require("radio")($);
		require("placeholder")($);
		require("pagination");
	var navigators = require("navigatortest");  //浏览器版本检测
	
	
	
	
	
	var modelInit = function($$){
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".list").placeholder();
		};
		
		
		/**
		 * 点击删除按钮删除整条费用
		 * 2015-05-14
		 * 
		 */
		var deleteColByBtn = function(){
			$('.spr-del-trig',$$).off('click').on('click',function(){
				var that = $(this);
				var serial_id = that.parent(".edit-col").attr("serial_id");
				var father_id = that.parent(".edit-col").attr("father_id");
				if(serial_id == '' || father_id == ''){
					return false;
				}else{
					var dd = dialog({
						title: '<i class="ifont">&#xe77d;</i><span>删除费用</span>',
						content: '删除的信息将无法得到恢复，确定删除？',
						okValue: '确 定', drag: true,
						ok: function () {
							that.parents("tr").remove();
							var type = "post";
							var url = that.attr("url");
							var data = {
								"serial_id": serial_id,
								"father_id": father_id
							};
							ajax.doAjax(type,url,data,function(data){
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
		
		/**
		 * 分页
		 * 2015-05-14
		 * 
		 */
		var pageByNum = function(page){
			if(!!!page) page=0;
			page += 1;
			var type = "GET";
			var data = "&page="+page;
			var url = $(".jzf-pagination",$$).attr("page_url") + data;
			ajax.doAjax(type,url,"",function(msg){
				if(msg.page_info == null){
					$(".jzf-pagination").hide();
				}else{
					var len = msg.page_info.count;   //总共条数
					var size = msg.page_info.size;   //每页条数
					if(msg.status == 1){
	 					ajax.iniPagination(len,"#list_debts",pageByNum,size,page - 1);
						
		             	$(".jzf-pagination").show();
						$(".lst_tb",$$).find("tr").not("tr:eq(0)").remove();
						$(".lst_tb",$$).append(msg.data);
						
						//调用点击单行删除按钮删除
						deleteColByBtn();
						
					}else{
		             	 $(".jzf-pagination").hide();
					}
				};
				
			});
		};
		pageByNum();
		
		
		//页码跳转
		$(".p-btn").off("click").on("click",function(){
			var p = parseInt($(".page",$$).val()) - 1;
			var num =  $(".pagination",$$).children().length - 2;
			var cpage = $(".pagination",$$).children().eq(num).text();
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
				pageByNum(p);
			}
			
		});
		
		
		
	};
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);

	}; 
	
	
});