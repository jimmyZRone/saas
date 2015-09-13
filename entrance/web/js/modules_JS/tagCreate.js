(function(){
	//标签组件
	window.WindowTag = new (function(){
		var htis = this;
		var $ = null;
		var ajax = null;
		var guid_index = 100000;
		var htisrequire = null;
		//刷新GUID编号
		this.reloadguidindex = function(guid){
			//设置最大的GUID
			var last = $(".tag > ul li:last");
			guid_index = parseInt(last.attr('guid').replace(/^.*?-(\d+)$/,'$1'))+1;
		}
		//描点转地址
		var urlhash2url = function(urlhash){
			urlhash = urlhash.indexOf('#') === 0 ? urlhash.substr(1) : urlhash;
			return urlhash;
		};
		//地址转描点
		var url2urlhash = function(url){
			url = url.indexOf('#') === 0 ? url : '#'+url;
			return url;
		};
		//刷新头位置
		window.reloadtagheadsize = function(){
			var left = 0;
			var li_width = $('.tag ul li').width()+8;
			var li_width_total = 0;
			$('.tag ul li').each(function(){
				var self = $(this);
				self.css({left:left});
				left += self.width()+8;
				li_width_total += li_width;
			});
			//控制标签左右滚动
			var tag_ul_width = $(".tag").width()-55-80;
			if(li_width_total > tag_ul_width){
				$(".tag-auto-conter").fadeIn(300);
			}else{
				$(".tag-auto-conter").hide();
				$(".tag ul").css("margin-left","55px");
			}
			$(".tag-auto-conter-next").off("click").on("click",function(){
				var left = parseFloat($(".tag ul").css("margin-left"));
				if($(".tag ul").is(":animated") || left <= -li_width_total + li_width*2) return false;
				$(".tag ul").animate({"margin-left" : left-li_width+"px"},300);
			});
			$(".tag-auto-conter-prev").off("click").on("click",function(){
				if($(".tag ul").is(":animated")) return false;
				var left = parseFloat($(".tag ul").css("margin-left"));
				if(left >=55) return false;
				$(".tag ul").animate({"margin-left" : left+li_width+"px"},300);
			});
			$(".tag li a.a_Tags").off("click").on("click",function(){
				var urls = "#"+$(this).attr("url");
				leftnavctr(urls);
			});
			//存储标签栏到本地记录
			if(window.sessionStorage){
				window.sessionStorage.setItem('nav_tags','<ul>'+$('.tag ul').html()+'</ul>');
			}
		};
		window.leftnavctr = function(urls){
			//检测被点标签是否是当前选中标签的同级标签，是则不收缩菜单
				var autooos = false;
				$(".nav-box li.current").siblings("li").each(function(){
					if($(this).children("a").attr("href") == urls){
						autooos = true;
						return false;
					}
				});
				if(urls == $(".nav-box li.current a").attr("href")) autooos = true;
				if(autooos == false) $(".two-nav").slideUp(500);
				$(".nav-box a").each(function(){
					$(".nav-box a").removeClass("current").parent().removeClass("current");
					if(urls == $(this).attr("href") && $(this).parents("ul").hasClass("two-nav")){
						$(this).parent().addClass("current").parent().siblings(".one-nav").addClass("current");
						$(this).parents(".two-nav").slideDown(500);
						return false;
					}if(urls == $(this).attr("href") && $(this).hasClass("one-nav")){
						$(this).addClass("current");
						$(this).siblings(".two-nav").slideDown(500);
						return false;
					}
				});
		};
		
		//根据URL取得URI
		var getUri = function(url){
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
		};
		//创建一个标签
		var createTag = function(url){
			//判断多开
			var tagsize = parseInt($(".tag ul > li > a:first[url='"+(getUri(url))+"']").eq(0).attr('tagsize'));
			tagsize = isNaN(tagsize) ? 0 : tagsize;
			if(tagsize > 1 && $(".tag ul > li > a[url='"+(getUri(url))+"']").length >= tagsize){//已经超过最大能开启的标签
				dialog({
					title:'提示消息',
					content:($(".tag ul > li > a:first[url='"+(getUri(url))+"']").eq(0).text())+'最多只能同时打开'+tagsize+'个',
					okValue:'确定',
					ok:function(){}
				}).showModal();
				return false;
			}
			var parent = htis.getCurrentTag();
			var head = '<li guid="GUID-[:GUID]" parent="[:PARENT]"><a url="[:URI]" class="a_Tags" href="#[:URL]" tagsize="1">新标签-[:GUID]</a><a class="a_close ifont" href="javascript:;"></a></li>';
			head = head.replace(/\[:URI\]/g,getUri(url));
			head = head.replace(/\[:GUID\]/g,guid_index);
			head = head.replace(/\[:URL\]/g,url);
			head = head.replace(/\[:PARENT\]/g,parent.length > 0 ? parent.find('> a:first').attr('href') : '');
			head = $(head);
			//关闭
			head.find('a:last').click(function(){
				htis.closeTag($(this).parent().find('a:first').attr('href'));
			});
			//双击刷新
			head[0].ondblclick = function(){
				htis.loadTag(url);
			};
			$(".tag > ul")[0].appendChild(head[0]);
			var body = "<div class='jooozo_Page' guid='GUID-[:GUID]'></div>";
			body = body.replace(/\[:GUID\]/g,guid_index);
			body = $(body);
			$('.main-show').append(body);
			guid_index++;
			reloadtagheadsize();
			return true;
		};
		//取得所有标签
		this.getAllTag = function(){
			return $(".tag > ul > li");
		};
		//取得当前标签
		this.getCurrentTag = function(){
			return $(".tag ul > li.current");
		};
		//设置每个标签可以打开的数量
		this.setTagSize = function(url,size){
			size = size || 1;
			url = urlhash2url(url);
			uri = getUri(url);
			$(".tag ul > li > a:first[url='"+uri+"']").attr('tagsize',size);
		};
		//设置父亲
		this.setTagParent = function(url,parent){
			var tag = this.getTagByUrlHash(url);
			if(tag){
				tag.attr('parent',parent);
			}
		};
		//标签url取得标签
		this.getTagByUrlHash = function(url){
			url = urlhash2url(url);
			uri = getUri(url);
			//判断当前标签是否是多开
			var tagsize = parseInt($(".tag ul > li > a:first[url='"+uri+"']").eq(0).attr('tagsize'));
			if(isNaN(tagsize) || tagsize <= 1){
				var tag = $(".tag ul > li > a:first[url='"+uri+"']").parent();
				if(tag.length == 0){
					return false;
				}
				return tag;
			}else{//多开
				var tag = $(".tag ul > li > a:first[href='#"+url+"']").parent();
				if(tag.length == 0){
					return false;
				}
				return tag;
			}
		};
		//选择标签
		this.selectTag = function(url){
			url = urlhash2url(url);
			window.location = url2urlhash(url);
			var tag = this.getTagByUrlHash(url);
			if(tag === false){return false;}
			var ctag = this.getCurrentTag();
			if(ctag.length >0 && tag == ctag){return false;}
			var allTag = this.getAllTag();
			allTag.removeClass('current');
			tag.addClass('current');
			if(getUri(url) == 'index'){
				$(".chart").show();
			}else{
				$(".chart").hide();
			}
			if(url != tag.find('a:first').attr('href').substr(1)){//同一标签，不同地址
				this.loadTag(url);
			}
			$('.jooozo_Page').hide();
			$('.main-show .jooozo_Page[guid="'+tag.attr('guid')+'"]').show();
			document.title = '九猪SAAS系统-'+tag.find('a:first').text();
			//判断是否需要刷新
			if(tag.attr('reload') == '1' || tag.children("a:first").attr("url") == "/index.php?c=tenant-index&a=edit"){
				this.loadTag(url);
				//兼容单击刷新-s			
//				var guid = tag.attr("guid");
//				var cur_page = $(".jooozo_Page[guid="+guid+"]");
//				if(cur_page.children().attr("hasform")){
//					this.loadTag(url);
//				}else{
//					this.loadTag(url,'get',function(){
//						tag.attr('reload',1);
//					});
//				}
				//兼容单击刷新-e
			}
			//存储标签栏到本地记录
			if(window.sessionStorage){
				window.sessionStorage.setItem('nav_tags','<ul>'+$('.tag ul').html()+'</ul>');
			}
			return true;
		};
		//加载一个标签
		this.loadTag = function(url,type,callback){
			type = type || 'get';
			callback = callback || function(){};
			url = urlhash2url(url);
			if(url == 'index'){
				return false;
			}
			var tag = this.getTagByUrlHash(url);
			if(!tag){
				//已经关闭
				return false;
			}
			tag.removeAttr('reload');
//			//兼容单击刷新-s
////			tag.attr('reload',"1");
//			//兼容单击刷新-e
			tag.find('a:first').text('加载中...');
			ajax.doAjax(type,url,'',function(json){
				var tag = WindowTag.getTagByUrlHash(url);
				if(!tag){//已经关闭
					return false;
				}
				var tagName = '';   //存储标签名
				var LocationUrl = '';
				var jsName = false;
				var html = '';
				var parent = tag.attr('parent');
				var tagsize = 1;
				if(json.status == 0){
					//加载失败
					tagName = '加载失败';   //存储标签名
					LocationUrl = url;
					jsName = false;
					html = typeof json['request'] == 'object' && json.request != null && typeof json.request['responseText'] == 'string' ? json.request.responseText : '';
					//定时关闭
//					setTimeout(function(){
//						WindowTag.closeTag(urlHash);
//					},1000);
				}else{
					tagName = json.tag_name;   //存储标签名
					LocationUrl = json.model_href;
					jsName = json.model_js;
					html = json.data;
					parent = json.parent || parent;
					tagsize = json.tagsize || tagsize;
				}
				if(tag.hasClass('current')){
					document.title = '九猪SAAS系统-'+tagName;
				}
				tag.find('a:first').text(tagName).attr('href','#'+url);
				htis.setTagSize(url,tagsize);
				var index = htis.getAllTag().index(tag);
				html = $('.main-show .jooozo_Page[guid="'+tag.attr('guid')+'"]').html(html);
				if(jsName){
					//异步加载js并调用开启方法
					htisrequire.async(jsName, function(jsName) {
						if(jsName != null && typeof jsName['inite'] == 'function'){
							jsName.inite(html,json);
						}
			        })
				}
				callback(json);
			});
		};
		//关闭一个标签
		this.closeTag = function(url){
			url = urlhash2url(url);
			var tag = this.getTagByUrlHash(url);
			if(tag === false){
				return false;
			}
			tag.remove();
			$('.main-show .jooozo_Page[guid="'+tag.attr('guid')+'"]').remove();
			//判断删除的标签是否是当前标签
			if(tag.hasClass('current')){
				var parent = tag.attr('parent');
				var parentTag = this.getTagByUrlHash(parent);
				if(parentTag === false){
					parentTag = this.getTagByUrlHash('index');
				}
				this.selectTag(parentTag.find('a:first').attr('href'));
			}
			//存储标签栏到本地记录
			if(window.sessionStorage){
				window.sessionStorage.setItem('nav_tags','<ul>'+$('.tag ul').html()+'</ul>');
			}
			reloadtagheadsize();
		};
		//打开一个标签
		this.openTag = function(url){
			url = urlhash2url(url);
			var ctag = this.getCurrentTag();
			if(this.getTagByUrlHash(url) === false){//标签还不存在
				if(!createTag(url)){
					this.selectTag(ctag.find('a:first').attr('href').substr(1));			
					return false;
				}
				var tag = this.getTagByUrlHash(url);
				tag.attr('reload',1);
				//this.loadTag(url);
			}
			this.selectTag(url);
			if(ctag.length > 0){
				this.setTagParent(url,ctag.find('a:first').attr('href').substr(1));
			}
		};
		//标签地址改变事件
		this.urlonchange = function(hash){
			hash = hash == '' ? 'index' : hash;
			this.openTag(hash);
		};
		//初始化
		this.init = function(require){
			htisrequire = require;
			$ = htisrequire("jquery");
			ajax = htisrequire("Ajax");
			var head = $(".tag > ul li:first");
			head.find('>a:first').attr('url','index').attr('tagsize',1);
			head.attr('guid','GUID-'+guid_index);
			var body = $('.main-show .jooozo_Page');
			body.attr('guid','GUID-'+guid_index);
			guid_index++;
		};
	})();
})();
define(function (require, exports, moudles) {
	window.WindowTag.init(require);
	window.onhashchange = function(){
		$("div[lang='zh-cn']").hide();//隐藏日期选择框
		var urlhash = window.location.hash.indexOf('#') === 0 ? window.location.hash.substr(1) : window.location.hash;
		urlhash = urlhash.length > 1 && urlhash != 'index' && urlhash.indexOf('/') !== 0 && urlhash ? '/'+urlhash : urlhash;
		window.WindowTag.urlonchange(urlhash);
	};
	if(sys.ie && sys.ie < 8){//IE8以下的onhashchange事件
		var Backbone = require('backbone');
		var autoRouter = Backbone.Router.extend({
			routes: {
				"*actions" : "defaultRoute"
			},
			defaultRoute : function(actions){
				if(typeof window['onhashchange'] == 'function'){
					window.onhashchange();
				}
			}
		});
		new autoRouter();
		Backbone.history.start();
	}else{
		if(window.sessionStorage && window.sessionStorage.getItem('nav_tags')){//支持HTML5，尝试本地记录恢复
			var nav_tags = window.sessionStorage.getItem('nav_tags');
			nav_tags = $(nav_tags);
			nav_tags.find('li').attr('reload',1).removeClass('current');
			nav_tags.find('li:first').addClass('current');
			nav_tags.find('li:gt(0)').each(function(){
				//关闭
				var self = $(this);
				var body = "<div class='jooozo_Page' guid='[:GUID]'></div>";
				body = body.replace(/\[:GUID\]/g,self.attr('guid'));
				body = $(body);
				$('.main-show').append(body);
			});
			$('.tag ul').html(nav_tags.html());
			reloadtagheadsize();
			$('.tag ul >li:gt(0)').each(function(){//恢复事件
				//关闭
				var self = $(this);
				self.find('a:last').click(function(){
					window.WindowTag.closeTag($(this).parent().find('a:first').attr('href').substr(1));
				});
				//双击刷新
				this.ondblclick = function(){
					window.WindowTag.loadTag($(this).find('a:first').attr('href').substr(1));
				};
			});
			window.WindowTag.reloadguidindex();
		};
		var hash = window.location.hash.indexOf('#') === 0 ? window.location.hash.substr(1) : window.location.hash;
		if(hash.length > 1 && hash != 'index'){
			if(typeof window['onhashchange'] == 'function'){
				window.onhashchange();
			}
		}
	}
	//初始化
	exports.inite = function(){
		
	}
});