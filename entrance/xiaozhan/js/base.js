window.IS_ONLOAD = false;
window.onload = function(){window.IS_ONLOAD = true;};
var L = {};
/**
 * 获取一列元素的最后一个的offsetTop
 *传入一个jquery对象, $('ul li');
 */
L.getLastH = function(ele){
	var obj = ele;
	var lastPinH = obj.last().offset().top + Math.floor(obj.last().height()/2);
	return lastPinH;
}

/**
 * 检测是否滚动到某个位置了
 *根据 L.getLastH方法执行的结果来判断
 */
L.checkScrollSite = function(ele){
	var srcollTop = document.documentElement.scrollTop || document.body.scrollTop;
	var documentH = document.documentElement.clientHeight;
	if(L.getLastH(ele) < (srcollTop + documentH)){
		return true;
	}else{
		return false;
	}	
}


/**
 * 弹窗对象
 */
L.dialog = {
	/*
	*msg	:提示信息 msg和conFirm二选一
	*conFirm :确定框(外部调用后面需要加一个fmCallBack函数.把确定和取消的结果返回到外部调用方)
	*fmCallBack: conFirm参数处罚后的回调，接收一个函数，函数的形参是一个bool值
	*callback: 当页面load进来之后，回调函数( 可以对load进来的页面进行操作)
	*/
	_create:function(obj){
		//内容html
		var dialog_html = '<div id="dialog-bg"></div>';
		dialog_html+= '<div id="dialog-main">';
		dialog_html+='<div id="dialog-box"><div id="loading-box"><span></span></div></div>';
		dialog_html+='</div>';
		$('body').append(dialog_html);
		var d_bg = $('#dialog-bg'),
		 	d_main = $('#dialog-main'),
		 	d_close = $('#dialog-close'),
		 	d_box = $('#dialog-box');
		
		if(obj.msg){
			var ts = '<div class="diag-confirm"><p>'+obj.msg+'</p><div class="btn-div">';
                ts+='	<a href="javascript:;" class="js_fm_btn js_fm_btn_ok"> 朕知道了 </a>';
                ts+='</div>';
             ts+='</div>';
			d_box.html(ts);
			
		}else if(obj.conFirm){
			var ts = '<div class="diag-confirm"><p>'+obj.conFirm+'</p><div class="btn-div">';
				ts+='<a href="javascript:;" setType="ok" class="js_fm_btn js_fm_btn_sld"> 确定 </a><a href="javascript:;" setType="reset" class="js_fm_btn"> 取消 </a>';
                ts+='</div>';
             ts+='</div>';
			d_box.html(ts);
		}
		//确定和取消按钮...
		$('.js_fm_btn').die().live('click',function(){
			var type = $(this).attr('setType');
			if(type == 'ok'){
				if(obj.fmCallBack) obj.fmCallBack(true);
				L.dialog._close();
			}else if(type == 'reset'){
				if(obj.fmCallBack) obj.fmCallBack(false);
				L.dialog._close();				
			}else{
				L.dialog._close();
				if(obj.callback){
					obj.callback();
				}
			}
		});
		
		//设置位置
		var set_center = function(obj){
			obj.css({
				left:($(window).width() - obj.width())/2+'px',
				top: ($(window).height()/2) - (obj.height()/2) + 'px'
			});
		};
		set_center(d_main);
		if(obj.top){
			d_main.css({
				top : obj.top + 'px'
			});
			
		}
		$(window).resize(function(){
			set_center(d_main);
			if(obj.top){
				d_main.css('top',obj.top+'px');
			}
		});
		
		d_bg.show().css({
			opacity:0,
			filter:'alpha(opacity=0)'
		}).css({
			opacity:0.5
		});
		d_main.show().css({
			opacity:0,
			filter:'alpha(opacity=0)'
		}).css({
			opacity:1
		});
		
		//关闭方法调用
		d_close.click(function(event){
			L.dialog._close();
			if(obj.callback){
				obj.callback();
			}
			event.stopPropagation();
		});
	},
	//关闭方法
	_close:function(){
		$('#dialog-bg').css({
			opacity:0,
			filter:'alpha(opacity=0)'
		});
		$('#dialog-bg').remove();
		$('#dialog-main').remove();
	}
};



/**
 * ajax加载数据列表函数
 *pager: 第一次开始请求第几页数据
 *obj:创建模板之后放入的容器
 *urls: 请求的ajax地址
 *callback :当数据返回成功之后，在该函数中调用各种模板创建方法
 *该方法ajax请求返回给前端的是json数据
 */
L.scrollGetLister = function(pager,obj,urls,data,callback){
	var page = pager;
	var ajaxState = true;
	$(window).scroll(function(){
		var isAjax = L.checkScrollSite(obj.children());
		if(isAjax && ajaxState){
			ajaxState = false;
			pager++;
			$.ajax({
				type:'GET',
				url:urls,
				data:data+'page='+pager,
				dataType:'json',
				beforeSend:function(){
					$('.room-list-notice').remove();
					var lod = '<div class="room-list-notice" id="js_loding">^主人，请骚等...</div>';
					obj.append(lod);
					$('#js_loding').show(300);
				},
				success:function(data){
					if(data.status == 'n'){ //当没有数据返回
						ajaxState = false;
						$('#js_loding').html('哦，弹尽粮绝了.');
						$('#js_loding').animate({
							opacity:0
						},300,function(){
							$(this).remove();
						});
					}else{
						$('#js_loding').animate({
							opacity:0
						},300,function(){
							$(this).remove();
						});
						callback(data); //根据返回的数据，从外部将该函数传入，做各种逻辑..
						ajaxState = true;
					}
				},
				error:function(){
					L.dialog._create({
						msg:'出错了，请刷新页面重试.'	
					});
					return false;
				}
			});
		}
	});
}


/*
*
* 详细页面焦点图
*/
L.slder = function(){
	var curBox = $('#js_cur_num'), //当前张数
		countBox = $('#js_tot_num');
		countBox.text( $('.swipe-wrap').find('div').length );//设置图片总数
	 	slider = new Swipe(document.getElementById('js_slider') , {
			startSlide: 0,
			speed: 300,
			auto: 3000,
			continuous: true,
			disableScroll: true,
			stopPropagation: false,
			callback: function(pos) {
				curBox.text(pos+1);
			},
			transitionEnd: function(index) {}
		});
}
