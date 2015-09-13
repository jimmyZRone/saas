define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	var uplodify = require("uplodify");   //上传图片插件
	

	/**
	 * 供iframe页面js调用
	 *
	 */
	var mask=window.mask = {
		//弹窗
		dialogShowModle:function(title,con){
			var d = dialog({
				title: title,
				content: con,
				okValue: '确定',
				ok: function(){
					d.close();
					var tag = WindowTag.getCurrentTag();
					//刷新标签
					WindowTag.loadTag(tag.find('>a:first').attr('url'),'get',function(){});
				},
				cancelValue: '取消',
				cancel: function () {
					d.close();
				}
			});
			d.showModal();
		},
		noDialogShowModle:function(title,con,detail,list,add){
			var d = dialog({
				title: title,
				content: con,
				okValue: '确定',
				ok: function(){
					d.close();
					if(detail){
						$("#opened_iframe").get(0).contentWindow.detailEvent.detailShow(detail);
					}
					if(!!list && !!add){
						add.fadeOut(400);
						list.fadeIn(400);
					}
				},
				cancelValue: '取消',
				cancel: function () {
					d.close();
				}
			});
			d.showModal();
		},
		noBtnDialogShowModle:function(title,con,list,add){
			var d = dialog({
				title: title,
				content: con,
			});
			d.showModal();
		},
		//刷新标签
		refresh: function(){
			var tag = WindowTag.getCurrentTag();
			WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
		},
		//返回iframe地址
		returnUrl: function(){
			var src = $('#opened_iframe').attr('src');
			return src;
		},
		basicUrl: function(){
			$(".menu-basic").addClass("current").siblings().removeClass("current");
			var src = $(".menu-basic a").attr("dataurl");
			return src;
		},
		houseUrl: function(){
			$(".menu-house").addClass("current").siblings().removeClass("current");
			var src = $(".menu-house a").attr("dataurl");
			return src;
		}
		
	};


	var modelInit = function($$){
		// 初始化iframe高度
		$('#opened_iframe').load(function(){
			$(this).height($('body', $(this).get(0).contentWindow.document).height() + 30);
		});
		
		$(".appen-btn",$$).off("click").on("click",function(){
			var url = $(this).attr("dataurl");
			var tag = WindowTag.getCurrentTag();
			//关闭当前标签
			WindowTag.closeTag(tag.find('>a:first').attr('url'));
			if(url != '' && url != undefined){
				window.WindowTag.openTag('#' + url);
			}
			
		});
		

		/**
		 * 判断是否直接进微信登录页面
		 *
		 */
		var isWechat = function(){
			var local_url = window.location.href;
			var vars = [], hash;
		    var q = local_url.split('#');
		    if(q[2] == 'weixin'){
		    	$(".menu-wechat").addClass("current").siblings().removeClass("current");
		    }
		}
		isWechat();
		
		/**
		 * 跳转至iframe页面
		 * 
		 */
		var defaultEvent = function(){
			var url;
			if($(".menu-basic").hasClass("current")){
				url = $(".open-list ul li a").eq(0).attr("dataurl");
			}else if($(".menu-wechat").hasClass("current")){
				url = $(".menu-wechat a").attr("dataurl");
			}
			$('#opened_iframe').attr('src', url);
		};
		defaultEvent();


		/**
		 * 已开通小站切换
		 *
		 */
		$(".open-list ul li a",$$).off("click").on("click",function(){
			var that = $(this);
			if(that.hasClass("current")){
				return true;
			}else{
				that.addClass("current");
				that.parent("li").siblings().find("a").removeClass("current");
				//更新菜单
				var flat_id = that.attr('flat_id');
				var menu = $('.menu',$$);
				menu.find('li a').each(function(){
					var that_a = $(this);
					var dataurl = that_a.attr('dataurl');
					var reg = /([\?&]flat_id\=)\d+(&.*)?$/;
					dataurl = dataurl.replace(reg,'$1'+flat_id+'$2');
					that_a.attr('dataurl',dataurl);
				});
				menu.find('li.current').removeClass('current').find('a').click();
				//menu.find('li a').first().click();
//
//				var type = "GET";
//				var url = $(this).attr("dataurl");
//				//刷新iframe
////				var if_src = $('#opened_iframe').attr('src');
////				if (if_src.match(/&/)){
////					if_src = if_src + "&r=" + Math.random();
////				}else{
////					if_src = if_src + "?r=" + Math.random();
////				}
//				$('#opened_iframe').attr('src', url);

			}
		});

		/**
		 * tab切换
		 *
		 */
		$(".menu ul li a").off("click").on("click",function(){
			if($(this).parent("li").hasClass("current")){
				return true;
			}else{
				$(this).parent("li").addClass("current").siblings().removeClass("current");
				var url = $(this).attr("dataurl");
				$('#opened_iframe').attr('src', url);
				$(".main-show", parent.document).css("overflow-y","auto");
			}

		});



	}

	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);

	};

});