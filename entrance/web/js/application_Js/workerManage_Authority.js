define(function(require,exports){
	/**
	 * 员工管理-新增权限组
	 * 
	 */
	var $ = require("jquery");
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax=require("Ajax");
	var	dialog = require("dialog");  //弹窗插件
	
	
	var modelInit = function($$){
		/**
		 * 权限组表单提交
		 * 
		 */
		var formEvent = {
			submitForm : function(form){
				var url = form.attr('action');
				var groupName = $("input[name = 'group_name']",$$).val(); //分组名
				var data = {
					group_name:groupName,
				};
				var type = "POST";
				var form_checkbox = $('.workerManage_Authority_B',$$).find(".form_checkbox");
				$.each(form_checkbox,function(i,o){
					var name = o.getAttribute("fm-name"),   //参数提交名称
				 		sdt = [];
				 		cboxUnits = $(o).find("input[type='checkbox']:checked").not('.exce_item');
				 	$.each(cboxUnits,function(j,item){
			 			sdt.push(item.value);
				 	});
				 	data[name] = sdt;
				});
				
				console.log(data);
	
				ajax.doAjax(type,url,data,formEvent.callback);
			},
			callback : function(json){
				if(json.status == 1){
					var d = dialog({
						title: '提示信息',
						content:'保存成功',
						okValue: '确定',
						ok: function () {
							d.close();
							//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							if(typeof json['url'] == 'string'){
			    				window.WindowTag.openTag('#'+json.url);
			    			}else if(typeof json['tag'] == 'string'){
			    				var ctag = WindowTag.getTagByUrlHash(json['tag']);
			    				if(ctag){
			    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('url'));
			    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
			    				}
			    			}
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
					}
					});
				}else{
					var d = dialog({
						title: '提示信息',
						content:json.message,
						okValue: '确定',
						ok: function () {
							d.close();
						}
					});
				}
				d.showModal();
			},
			checkUI: function(){
				$(".authority_form",$$).Validform({
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parent().find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            callback : function(form){
		            	if($(".workerManage_Authority_B",$$).find("input[type='checkbox']:checked").length==0){
		            			var d = dialog({
									title: '提示信息',
									content: '请至少选择一项权限',
									okValue: '确定',
									ok: function () {
										d.close();
									}
								});
							d.showModal();
			            }else{
			            	formEvent.submitForm(form);	
			            }
			        }
				});
			}
		};
		formEvent.checkUI();
		$(".chooseAll,.chooseAllAuto",$$).off("click").on("click",function(){
			$(".chooseAll",$$).children().toggle();
			if($(".chooseAll",$$).children().is(":visible")){
				$(".chooseAllAuto",$$).text("取消全选");
				$(".workerManage_Authority_B > .b > ul > li label",$$).addClass("checked").children().show();
				$(".workerManage_Authority_B > .b > ul > li label",$$).siblings("input").attr("checked",true);
			}else{
				$(".chooseAllAuto",$$).text("全选");
				$(".workerManage_Authority_B > .b > ul > li label",$$).removeClass("checked").children().hide();
				$(".workerManage_Authority_B > .b > ul > li label",$$).siblings("input").removeAttr("checked");
			}
		});
		/**
		 * 表单checkbox交互
		 * 
		 */
		var iniChekbox = function(){
			//针对IE10以下的input提示语兼容
			if(sys.ie && sys.ie < 10){
				require("placeholder")($);
				$(".workerManage_Authority_B > .a",$$).placeholder();
			}
			var SelectVerification = function(){//验证全选
				if($('.workerManage_Authority_B > .b > ul > li > dl > dd > label.checked',$$).length == $('.workerManage_Authority_B > .b > ul > li > dl > dd > label',$$).length){
					$('.workerManage_Authority_B .chooseAll i',$$).show();
					$('.workerManage_Authority_B .chooseAllAuto',$$).text('取消全选');
				}else{
					$('.workerManage_Authority_B .chooseAll i',$$).hide();
					$('.workerManage_Authority_B .chooseAllAuto',$$).text('全选');
				}
			};
			//顶级多选择
			$(".workerManage_Authority_B > .b > ul > li > .li_Head > label",$$).off("click").on("click",function(event){
				event.preventDefault();
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){//选中
					$(this).children(".choose").show();
					var ipt = $(this).parents('.li_Head').parent().find('dt input');
					var dt = ipt.parent();
					var lb = dt.find('label');
					ipt[0].checked = false;
					lb.removeClass('checked');
					lb.find('span').hide();
					lb.click();
				}else{//取消
					$(this).children(".choose").hide();
					var ipt = $(this).parents('.li_Head').parent().find('dt input');
					var dt = ipt.parent();
					var lb = dt.find('label');
					ipt[0].checked = true;
					lb.addClass('checked');
					lb.find('span').show();
					lb.click();
				}
			});
			//一级多选择
			$(".workerManage_Authority_B > .b > ul > li > dl > dt > label",$$).off("click").on("click",function(event){
				event.preventDefault();
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){//选中
					$(this).children(".choose").show();
					$(this).parents('.form_checkbox').find('dd input').each(function(){
						if(!this.checked){
							$(this).parent().find('label').click();
						}
					});
				}else{//取消
					$(this).children(".choose").hide();
					$(this).parents('.form_checkbox').find('dd input').each(function(){
						if(this.checked){
							$(this).parent().find('label').click();
						}
					});
				}
			});
			var Relationalgraph = {//关系图
				tenant:{
					0:{serial_number:0}
				},
				reserve:{
					0:{serial_number:0}
				},
				owner:{
					0:{serial_number:0}
				}
			};
			//单个选择
			$(".workerManage_Authority_B > .b > ul > li > dl > dd > label",$$).off("click").on("click",function(event){
				event.preventDefault();
				//用于处理关系图的公共变量
				var dl = $(this).parents('dl[fm-name]');
				var fmName = dl.attr('fm-name');
				var ipt = $(this).next();
				var val = parseInt(ipt.val());
				
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){//选中
					$(this).children(".choose").show();
					$(this).next().attr("checked",true);
					//正向查找
					if(Relationalgraph[fmName] && Relationalgraph[fmName][val]){
						for(var k in Relationalgraph[fmName][val]){
							var vo = Relationalgraph[fmName][val][k];
							var voIpt = $(".workerManage_Authority_B > .b > ul > li > dl[fm-name="+k+"] dd input[value="+vo+"]");
							if(!voIpt[0].checked){
								voIpt.parent().find('label').click();
							}
						}
					}
				}else{//取消
					$(this).children(".choose").hide();
					$(this).next().removeAttr("checked");
					//逆向查找
					for(var af in Relationalgraph){
						var afVo = Relationalgraph[af];
						for(var afval in afVo){
							var afvalVo = afVo[afval];
							for(var maf in afvalVo){
								if(maf == fmName && afvalVo[maf] == val){//匹配
									var voIpt = $(".workerManage_Authority_B > .b > ul > li > dl[fm-name="+af+"] dd input[value="+afval+"]");
									if(voIpt[0].checked){
										voIpt.parent().find('label').click();
									}
								}
							}
						}
					}
				}
				//查看是否需要全选
				var exceItemIpt = dl.find('.exce_item');
				var li_Head = dl.parent().find('div.li_Head');
				if($('.workerManage_Authority_B input[name='+fmName+']:checked',$$).length >= 4){
					exceItemIpt[0].checked = true;
					exceItemIpt.parent().find('label').addClass('checked').find('.choose').show();
					if(li_Head.length > 0){
						if(dl.parent().find('dl dt input').length == dl.parent().find('dl dt input:checked').length){
							li_Head.find('input')[0].checked = true;
							li_Head.find('label').addClass('checked').find('.choose').show();
						}else{
							li_Head.find('input')[0].checked = false;
							li_Head.find('label').removeClass('checked').find('.choose').hide();
						}
					}
				}else{
					exceItemIpt[0].checked = false;
					exceItemIpt.parent().find('label').removeClass('checked').find('.choose').hide();
					if(li_Head.length > 0){
						li_Head.find('input')[0].checked = false;
						li_Head.find('label').removeClass('checked').find('.choose').hide();
					}
				}
				//查看是否选中最大的全选
				SelectVerification();
			});
			SelectVerification();
		};
		iniChekbox();
		
		
		
	};
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);
	};
	
});