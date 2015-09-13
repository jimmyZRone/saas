define('backbone/backbone_inite', function(require, exports) {
	var Backbone = require('backbone');
	var $ = require("jquery");
	var ajax = require("Ajax");
	var types;
	
	/**
	 * 标签
	 */
	window.WindowTag = {
		//取得所有标签
		getAllTag:function(){
			return $(".tag > ul > li");
		},
		//转换URL到基本URL
		parseUrl2ModelUrl:function(url){
			if(url == 'index'){
				return 'index';
			}
			var param = url.split('?');
			var baseUrl = param[0];
			if(param.length == 1){
				return baseUrl+'?c=index&a=index';
			}
			param = param[1].split('&');
			var vars = {};
			for(var i=0;i<param.length;i++){
				var v = param[i].split('=');
				if(v.length == 1){
					v[1] = '';
				}
				vars[v[0]] = v[1];
			}
			vars['c'] = typeof vars['c'] == 'string' ? vars['c'] : 'index';
			vars['a'] = typeof vars['a'] == 'string' ? vars['a'] : 'index';
			url = baseUrl+'?c='+vars['c']+'&a='+vars['a'];
			return url;
		},
		//取得当前标签
		getCurrentTag:function(){
			return $(".tag ul > li.current");
		},
		//根据UrlHash取得标签
		getTagByUrlHash:function(urlHash){
			var url = urlHash.indexOf('#') == -1 ? urlHash : urlHash.split("#")[1];
			url = this.parseUrl2ModelUrl(url);
			var tag = $(".tag ul > li > a[url='#"+url+"']");
			if(tag.length == 0){
				return false;
			}
			return tag.parent();
		},
		//选择标签
		selectTag:function(urlHash){
			window.location = urlHash;
			var tag = this.getTagByUrlHash(urlHash);
			if(tag.length  == 0){return false;}
			var ctag = this.getCurrentTag();
			if(ctag.length  == 0){return false;}
			if(tag == ctag){return false;}
			var allTag = this.getAllTag();
			ctag.removeClass('current');
			tag.addClass('current');
			if(urlHash == '#index'){
				$(".chart").show();
			}
			$('.jooozo_Page:eq('+(allTag.index(ctag))+')').hide();
			$('.jooozo_Page:eq('+(allTag.index(tag))+')').show();
			return true;
		},
		//创建标签
		createTag:function(urlHash,type,callback){
			if(urlHash == '#index'){
				return false;
			}
			if(this.getTagByUrlHash(urlHash)){//已经存在标签
				return false;
			}
			var url = urlHash.split("#")[1];
			var str = $("<div class='jooozo_Page' page-config='"+url+"'></div>");
			var strTag = "<a class='a_Tags' href='#"+url+"' url='#"+(this.parseUrl2ModelUrl(url))+"'>加载中...</a><a class='a_close ifont' href='javascript:;'>&#xe627;</a>";
			var div = document.createElement("li");
			div.innerHTML=strTag;
			var tag_Parent = $(".tag > ul");
			var page_Parent = $(".main-show");
			//双击刷新
			div.ondblclick = function(){
				WindowTag.loadTag(urlHash,type,callback);
			}
			tag_Parent[0].appendChild(div);
			page_Parent.append(str);
			this.selectTag(urlHash);
			jsTagPosition();
			tag_Close();
			$(".chart").hide();
			this.loadTag(urlHash,type,callback);
		},
		//加载标签
		loadTag:function(urlHash,type,callback){
			var url = urlHash.split("#")[1];
			if(urlHash == '#index'){
				return false;
			}
			var tag = this.getTagByUrlHash(urlHash);
			if(!tag){
				//已经关闭
				return false;
			}
			tag.find('a:first').text('加载中...');
			ajax.doAjax(type,url,'',function(json){
				var page_Config = '';  //储存模板名称
				var tagName = '';   //存储标签名
				var LocationUrl = '';
				var jsName = false;
				var html = '';
				if(json.status == 0){
					//加载失败
					page_Config = url;  //储存模板名称
					tagName = '加载失败';   //存储标签名
					LocationUrl = url;
					jsName = false;
					html = typeof json['request'] == 'object' && json.request != null && typeof json.request['responseText'] == 'string' ? json.request.responseText : '';
					//定时关闭
//					setTimeout(function(){
//						WindowTag.closeTag(urlHash);
//					},1000);
				}else{
					page_Config = json.model_name;  //储存模板名称
					tagName = json.tag_name;   //存储标签名
					LocationUrl = json.model_href;
					jsName = json.model_js;
					html = json.data;
				}
				var tag = WindowTag.getTagByUrlHash(urlHash);
				if(!tag){
					//已经关闭
					return false;
				}
				tag.find('a:first').text(tagName).attr('href','#'+url);
				var index = WindowTag.getAllTag().index(tag);
				html = $(html);
				$('.jooozo_Page:eq('+(index)+')').attr('page-config',page_Config).html(html);
				if(jsName){
					//异步加载js并调用开启方法
					require.async(jsName, function(jsName) {
						if(jsName != null && typeof jsName['inite'] == 'function'){
							jsName.inite(html,json);
						}
			        })
				}
				jsTagPosition();
				callback(json);
			});
		},
		//打开标签
		openTag:function(urlHash){
			var type = arguments.length > 1 ? argument[1] : 'get';
			var tag = this.getTagByUrlHash(urlHash);
			if(tag){
				this.selectTag(urlHash);
		 		if(urlHash == "#index"){
	 				$(".chart").show();
	 			}else{
	 				$(".chart").hide();
	 			}
		 		if(tag.find('> a:first').attr('href') != urlHash){
			 		//刷新标签
			 		this.loadTag(urlHash,type,function(json){
			 			if(urlHash == "#index"){
			 				$(".chart").show();
			 			}else{
			 				$(".chart").hide();
			 			}
			 		});
		 		}
		 	}else{
		 		this.createTag(urlHash,type,function(json){
		 			if(urlHash == "#index"){
		 				$(".chart").show();
		 			}else{
		 				$(".chart").hide();
		 			}
		 		});
		 	}
		},
		//关闭标签
		closeTag:function(urlHash){
			var tag = this.getTagByUrlHash(urlHash);
			if(!tag){
				return false;
			}
			tag.find('> a:last').click();
		}
	}
	
	/*
	 * 页面请求回调
	 */
	function callback(data){
		var page_Config = data.model_name;  //储存模板名称
		var tagName = data.tag_name;   //存储标签名
		var LocationUrl = document.URL.split("#")[1];
		var jsName = data.model_js;
		var str = "<div class='jooozo_Page' page-config = '"+page_Config+"'>"+data.data+"</div>";
		var strTag = "<a class='a_Tags' href='#"+LocationUrl+"'>"+tagName+"</a><a class='a_close ifont' href='javascript:;'>&#xe627;</a>";
		var div = document.createElement("li");
		div.page_config=page_Config;
		div.innerHTML=strTag;
		var tag_Parent = $(".tag").children("ul");
		var page_Parent = $(".main-show");
//		This.attr("page-config",page_Config);
		tag_Parent[0].appendChild(div);
		page_Parent.append(str);
		//异步加载js并调用开启方法
		require.async(jsName, function  (ecs) {
            ecs.inite();
        })
  
		$(".jooozo_Page:last").show().siblings().hide();
		tag_Parent.children("li").removeClass("current");
		tag_Parent.children("li:last").addClass("current");	
//		tagClick(tag_Parent.children("li").children("a"));
		jsTagPosition();
//		tagClicks();
		tag_Close();
		$(".chart").hide();
 	}
		/*
	 * 标签定位
	 */
	function jsTagPosition(){
		var num_Tag = $(".tag").find("li").length;
		var obj_Tag = $(".tag").find("li:last");
		var w_Tag = $(".tag").children("ul").children("li").outerWidth(true)+5;
		var left_Last = w_Tag*(num_Tag-1);
		obj_Tag.css("left",left_Last+"px");
	}
	/*
	 * 标签重新定位
	 */
	function jsTagPositionAgain(num){
		var w_Tag = $(".tag").children("ul").children("li").outerWidth(true)+5;
		$(".tag").find("li:gt("+num+")").each(function(){
			var numThis = $(this).index();
			$(this).css("left",numThis*w_Tag+"px");
		})
	}
	/*
	 * 关闭标签及对应模块
	 */
	function tag_Close(){
		$(".a_close").off("click").on("click",function(){
			var num = $(this).parent().index();
			if($(this).parent().hasClass('current')){
				var href = $(this).parent().prev().children("a").attr("href");
				$(this).parent().prev().addClass("current");
	//			history.go(-1);
				window.location.href = href;
			}
			$(".jooozo_Page:eq("+num+")").remove().prev().show();
			$(this).parent().remove();
			jsTagPositionAgain(num-1);
		});
	}

	/*
	 * 标签点击获取模板
	 */
	function tagClicks(){
		$(".SCTest").off("click").on("click",function(){
			var model_Name = $(this).attr("href").split("#")[1];
			var tag_Name = $(this).attr("data-tag-name");
			var model_Parent;
			var next_model_Parent;   //下一层父级串唯一标示符
			if(typeof($(this).parent().attr("parent-config")) == 'undefined'){
				model_Parent = $(this).parent().attr("page-config");
			}else{
				model_Parent = $(this).parent().attr("parent-config");
			}
			next_model_Parent = model_Parent+"&"+model_Name;
			var flag = false;
			$(".jooozo_Page").each(function(){
				var Jparent_config = $(this).attr("parent-config");
				if(Jparent_config == next_model_Parent){
					var Jcurrent = $(this).index();
					$(this).show().siblings().hide();
					tag_Parent.children("li:eq("+Jcurrent+")").addClass("current").siblings().removeClass("current");
					flag = true;
					return true;
				}
			});
			if (!flag) createSCD(model_Name,tag_Name,model_Parent,num_FLOOR);
		});
		backoff();
	}
	//返回
	function backoff(){
		$(".backOff").off("click").on("click",function(){
			var parent_Config = $(this).parent().attr("parent-config");
			if(typeof(parent_Config) == 'undefined') return false;
			var array_Parent = parent_Config.split("&");
			var L_Array_Parent = array_Parent.length;
			var back_Step = parseInt($(this).attr("tip-back"));
			var destination;
			if(isNaN(back_Step)){
				destination = array_Parent[0];
			}else{
				if(back_Step>L_Array_Parent){
					return false;
				}else{
					destination = array_Parent[L_Array_Parent-1-back_Step];
				}
			}
			$(".jooozo_Page").each(function(){
				var page_Config = $(this).attr("page-config");
				var num_Current;
				if(destination == page_Config){
					num_Current = $(this).index();
					$(this).show().siblings().hide();
					$(".show_Top").children("ul").children("li:eq("+num_Current+")").addClass("current").siblings().removeClass("current");
					window.location.href = "#"+page_Config;
					return true;
				}
			});
		});
	}
	//配置路由
	var autoRouter = Backbone.Router.extend({
		routes: {
			"*actions" : "defaultRoute"
		},
		defaultRoute : function(actions){
			if(actions == ""){
				return false;
			}
			if(actions != 'index'){
				actions = "/"+actions;
			}
			actions = "#"+actions;
			WindowTag.openTag(actions);
			
//			var checkTagExist = false; //浏览历史对应标签及模板是否存在
//			if(actions != 'index'){
//				actions = "/"+actions;
//			}
//			$(".tag").children("ul").children("li").each(function(){
//				var HREF = $(this).children("a.a_Tags").attr("href");
//				HREF = HREF.split("#")[1];      //截取“#”号后的内容
//				if(actions == HREF){
//					checkTagExist = true;
//					$(this).addClass("current").siblings().removeClass("current");
//					var num = $(this).index();
//					$(".jooozo_Page:eq("+num+")").show().siblings().hide();
//					//数据块的显隐
//					if(actions == "index"){
//						$(".chart").show();
//					}else{
//						$(".chart").hide();
//					}
//				}
//			});
//			if(checkTagExist == false){
//			 		ajax.doAjax(types,actions,"",callback);
//			 	}
		}
	});
	//定义全局变量App
	window.App = {
		initialize: function(type) {
			types = type;
			new autoRouter();
	        Backbone.history.start();
	    }  
	};

	exports.run = App.initialize;
})