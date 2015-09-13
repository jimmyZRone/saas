define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var uplodify_xz = require("uplodify_xz");   //上传图片插件
	var ajax = require("Ajax");

	
	var modelInit = function($$){
		
		/**
		 * 获取图片的KEY
		 * 
		 */
		var getImgKey = function(){
			var type = "GET";
			var url = $(".main-picture").attr("dataUrl");
			ajax.doAjax(type,url,"",function(json){
				var filename1 = $(".picture-flat .picture-left .picture-big .upload-pic").attr("filename");
				var filename2 = $(".picture-brand .picture-left .picture-big .upload-pic").attr("filename");
				if(filename1 == '' || filename1 == undefined){
					$(".picture-flat .picture-left .picture-big .upload-pic").attr("filename",json[0]);
				}
				if(filename2 == '' || filename2 == undefined){
					$(".picture-brand .picture-left .picture-big .upload-pic").attr("filename",json[4]);
				}
				$(".picture-flat .picture-center ul li").eq(0).attr("key",json[0]);
				$(".picture-flat .picture-center ul li").eq(1).attr("key",json[1]);
				$(".picture-flat .picture-center ul li").eq(2).attr("key",json[2]);
				$(".picture-flat .picture-center ul li").eq(3).attr("key",json[3]);
				$(".picture-brand .picture-center ul li").eq(0).attr("key",json[4]);
				$(".picture-brand .picture-center ul li").eq(1).attr("key",json[5]);
				$(".picture-brand .picture-center ul li").eq(2).attr("key",json[6]);
				$(".picture-brand .picture-center ul li").eq(3).attr("key",json[7]);
			});
		};
		getImgKey();
		
		

	
		/**
		 * 图片上传
		 * 
		 */
//		$(".picture-big").hover(function(){
//			$(this).children(".pl-shade").fadeIn(300);
//
//		});
		uplodify_xz.uploadifyInitsEvent($('#open-file-upload',$$),$("#open-uploaderArea",$$));
		uplodify_xz.uploadifyInitsEvent($('#opens-file-upload',$$),$("#opens-uploaderArea",$$));
		
		/**
		 * 图片点击使用
		 * 
		 */
		$(".pc-use").off("click").on("click",function(){
			var that = $(this);
			that.parents("li").addClass("current").siblings().removeClass("current");
			var pic = that.siblings("img").attr("src");
			var filename = that.parents("li").attr("key");
			that.find("span").hide().parents("li").siblings().find(".pc-use > span").show();
			that.find("i").show().parents("li").siblings().find(".pc-use > i").hide();
			that.parents(".picture-center").siblings(".picture-left").find(".upload-pic > img").attr("src",pic);
			that.parents(".picture-center").siblings(".picture-left").find(".upload-pic").attr("filename",filename);
			
		});
	
		
		
		/**
		 * 图片信息提交
		 * 
		 */
		$(".picture-btn",$$).off("click").on("click",function(){
			var pic = [];
			$.each($(".upload-pic"), function(k, v) {
				var filename = $(v).attr("filename");
				var type = $(v).attr("datatype");
				var _pic = {
					"filename": filename,
					"type": type
				}
				pic.push(_pic);
			});
			var type = "POST";
			var data = {
				"pic": pic
			};
			var url = $(".picture-btn",$$).attr("url");
			ajax.doAjax(type,url,data,function(json){
				if(json.status == 1){
					var tag = WindowTag.getCurrentTag();
					//关闭当前标签
					WindowTag.closeTag(tag.find('>a:first').attr('url'));
					//跳页面
					if(json.url != '' && json.url != undefined){
						window.location.hash = '#' + json.url;
//						window.WindowTag.openTag('#' + json.url);
					}
				}else{
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

	
	
	
	
	}
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);

	};
	
});