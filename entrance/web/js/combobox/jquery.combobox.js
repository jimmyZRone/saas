define(function (require, exports, moudles) {
    return function (jquery) {

	var KEY_UP = 38;
	var KEY_DOWN = 40;
	var KEY_ENTER = 13;

	var OPERATION = false;

	var optionsContainer = [];

	var optionsData = [];
	var currentIndex = [];
	$.fn.combobox = function(option) {
		var hoverFlag = false;
		var url = null;
		var config = {
			url: null,
			param_name: 'keyword',
			data_name: '',
			title_name: 'title',
			item_template: null,
			min_char: 0,
			commbox_selector: '.commbox',
			width: null,
			result:"msg",//后台返回json数组名
			prompt: null,
			height: null,
			padding: null,
			offset: { top: 0, left: 0},
			callback: {
				init: null,
				select: null,
				choose: null,
				notdata: null,
				notchoose: null,
				keyup: null
			}
		};
		var keyuptime = null;
		var keyupval = '';
		var keyupcache = {};
		var keyupcachelength = 0;
		config = $.extend(config, option);

		$(this).each(function(i, item) {
			var $$ = this;
            var textBox = $($$);

            $$.index = i;
            textBox.index = i;
            textBox.attr('autocomplete', 'off');

            if (!bootstrap(textBox)) return true;
            try{
            	var a =  $('ul[data-index=' + i + ']');
            	if (a.size() > 0) a.remove();
            }catch(e){}
            optionsContainer[i] = $('<dl></dl>');
            optionsContainer[i].attr('data-index', i);

            init(textBox);

		});

		// 初始化对象
		function init(textBox) {
			textBox.old_value = false;
	    	$(textBox).on('keyup', function(event) {
	    		keyup(event, textBox);
	    	}).on('click', function(event){
	    		keyup(event, textBox);
	    	}).get(0).onpaste = function(e){
	    		e = event || e;
	    		e.keyCode = 0;
	    		setTimeout(function(e){
	    			return function(){
	    				keyup(e, textBox);
	    			}
	    		}(e),60);
	    	};

	    	if($.browser.msie) {
	    		$(textBox).get(0).attachEvent('onpropertychange', function (o){
	    			if ($.trim($(textBox).val()) == '') {
	    				keyupval = '';
	    				keyup({keyCode: KEY_UP}, textBox);
	    			}
	    		});

	    	}

		}

		// 键盘按下时
		function keyup(event, textBox) {
			try{
			if (KEY_UP == event.keyCode || KEY_DOWN == event.keyCode || KEY_ENTER == event.keyCode){
				return;
			}
			}catch(e){}

			if (typeof(config.callback.keyup) == 'function') config.callback.keyup(textBox);

			var input_val = $.trim(textBox.val());
			var callback = function(data){//回调
				optionsContainer[textBox.index].children().not('.prompt').remove();
				//optionsContainer[textBox.index].hide();
				optionsData[textBox.index] = [];
				if (data[config.result] == undefined || data[config.result].length <= 0 || data['status'] != 1) {
					if (typeof(config.callback.notdata) == 'function') config.callback.notdata(textBox);
					return;
				}

				optionsData[textBox.index] = data[config.result];

				$.each(optionsData[textBox.index], function(i, item){
					var dd = $('<dd></dd>');

					if (config.item_template != null && typeof(config.item_template) == 'string') {
						var item_html = config.item_template;
						for (var item_key in item) {
							item_html = item_html.replace(new RegExp(":" + item_key, 'gm'), item[item_key]);
						}

						dd.html(item_html);
					} else {
						dd.html(item[config.title_name]);
					}

					dd.attr('data-index', i);
					dd.hover(function(){
			    		hoverFlag = true;
			    	}, function(){
			    		hoverFlag = false;
			    	});
//			    	if(sys.ie && sys.ie < 8){
//			    		dd.css("position","relative");
//			    	dd.append('<iframe style="width:'+config.width+'px;filter:alpha(opacity=0);-moz-opacity:0; height:24px; position:absolute; border:none; left:0; z-index:-1"></iframe>');
//			    	}
					optionsContainer[textBox.index].append(dd);
				});



				optionsContainer[textBox.index].addClass(config.commbox_selector.replace('.', ''));
				optionsContainer[textBox.index].insertAfter(textBox);
				optionsContainer[textBox.index].show();

				OPERATION = true;

				handle(event, textBox);
			}


			if (config.prompt != null && typeof(config.prompt) == 'string') {
				if (!optionsContainer[textBox.index].children().hasClass('prompt')) {
					var prompt = $('<dd>' + config.prompt + '</dd>');
					prompt.addClass('prompt');
          prompt.hover(function(){
            optionsContainer[textBox.index]["currentOption"]=prompt.get(0);
          },function(){
              optionsContainer[textBox.index]["currentOption"]=undefined;
          }).click(function(){
              textBox.focus();
          });
					optionsContainer[textBox.index].append(prompt);
					optionsContainer[textBox.index].addClass(config.commbox_selector.replace('.', ''));
					optionsContainer[textBox.index].insertAfter(textBox);
				}
				optionsContainer[textBox.index].show();

				handle(event, textBox);

			}

			var result={};
			result[config.result]=[],result['status']=0;
			if (config.min_char > 0 && input_val.length < config.min_char) {
				callback(result);
				return;
			}
			if(input_val == ''){
				keyupval = input_val;
				if(input_val == '')
				callback(result);
				return;
			}
			var keyup_time = 80;
			if(input_val.length < 3 && /^[\w]+$/.test(input_val)){//2位字母
				keyup_time = 240;
			}else if(input_val.length<keyupval.length){
				keyup_time = 180;
			}else if(/^[^\w]+[\w]{1,2}$/.test(input_val)){//不确定是否还需要输入中文
				keyup_time = 180;
			}else if(/^[^\w]+[\w]{3,}$/.test(input_val)){//判断是否是中文还在输入
				keyup_time = 500;
			}else if(/^[\w]{3,}$/.test(input_val)){//不确定是否是中文输入
				keyup_time = 300;
			}
			keyupval = input_val;
			if(typeof keyupcache[input_val] != 'undefined'){
				callback(keyupcache[input_val]);
				return;
			}
			clearTimeout(keyuptime);
			keyuptime = setTimeout(function(){//颤抖
				var input_val = $.trim(textBox.val());
				if(input_val != keyupval){
					return;
				}
				var input_val = $.trim(textBox.val());
				var json_url = url.replace(/:param_name/gm, encodeURIComponent(input_val));
				$.getJSON(json_url, (function(val){
					return function(data){
						if(val != $.trim(textBox.val()))  return;
						if(data.status == 1 && data[config.result].length>0 && data[config.result].length < 50){
							//保存缓存
							if(keyupcachelength<3){
								keyupcachelength++;
							}else{
								for(var k in keyupcache){
									delete keyupcache[k];
									break;
								}
							}
							keyupcache[val] = data;
						};
						callback(data);
					}
				})(input_val));

			},keyup_time);
		}

		function choice(flag, textBox) {
			var commbox_children = optionsContainer[textBox.index].children().not('.prompt');

			if (currentIndex[textBox.index] <= -1) currentIndex[textBox.index] = commbox_children.size() - 1;
			else if (currentIndex[textBox.index] >= commbox_children.size()) currentIndex[textBox.index] = 0;

			var current_children = commbox_children.eq(currentIndex[textBox.index]);

			commbox_children.removeClass('current');
			current_children.addClass('current');

			if (!flag) $(textBox).val(optionsData[textBox.index][currentIndex[textBox.index]][config.title_name]);
			$(textBox).focus();

			if (typeof(config.callback.select) == 'function') config.callback.select(optionsData[textBox.index][current_children.attr('data-index')], textBox);
		}


		function handle(event, textBox) {
			if (typeof(config.callback.init) == 'function') config.callback.init(textBox);

			var input_position = $(textBox).position();

			optionsContainer[textBox.index].css({
				width: config.width,
				minHeight: config.height,
				position: 'absolute',
				top: input_position.top + config.offset.top,
				left: input_position.left + config.offset.left
			}).children().css({
				width: config.width - config.padding * 2,
				paddingLeft: config.padding,
				paddingRight: config.padding
			});

			currentIndex[textBox.index] = -1;
			var commbox_children = optionsContainer[textBox.index].children().not('.prompt');

			$(textBox).unbind('keydown').bind('keydown', function(event){
				if (optionsData[textBox.index].length <= 0) {
					return true;
				}

				if (OPERATION && KEY_UP == event.keyCode) {
					currentIndex[textBox.index]--;
					choice(true, textBox);
				}
				else if (OPERATION && KEY_DOWN == event.keyCode) {
					currentIndex[textBox.index]++;
					choice(true, textBox);
				}
				else if (KEY_ENTER == event.keyCode) {
					optionsContainer[textBox.index].hide();
					OPERATION = false;
					choice(false, textBox);

					if (typeof(config.callback.choose) == 'function')

						config.callback.choose(optionsData[textBox.index][commbox_children.eq(currentIndex[textBox.index]).attr('data-index')], this);
				}

			}).unbind('focus').bind('focus', function(){
				textBox.old_value = textBox.val();
//				$(textBox).parents('form').attr('onsubmit', 'return false');
			}).unbind('blur').bind('blur', function(){
          var _target=optionsContainer[textBox.index]["currentOption"];
            console.log(_target);
          if(_target && _target!=undefined && $(_target).hasClass("prompt")){
              return;
          }
        // console.log(_target.className);
				// 选择第一个
				if (!hoverFlag && !optionsContainer[textBox.index].is(":hidden")) {
					currentIndex[textBox.index] = 0;
					optionsContainer[textBox.index].hide();
					if(textBox.old_value == textBox.val()){//值没有变化
						return false;
					}
					if (typeof(config.callback.notchoose) == 'function')
						config.callback.notchoose(optionsData[textBox.index][commbox_children.eq(currentIndex[textBox.index]).attr('data-index')], this);
					$(textBox).val(optionsData[textBox.index][currentIndex[textBox.index]][config.title_name]);
				}

//				$(textBox).parents('form').attr('onsubmit', 'return true');
			});

			commbox_children.on('mouseover', function(event) {
				commbox_children.removeClass('current');
				$(this).addClass('current');
        optionsContainer[textBox.index]["currentOption"]=this;
				//if (typeof(config.callback.select) == 'function') config.callback.select(optionsData[textBox.index][$(this).attr('data-index')]);
			}).on('click', function(event){
				currentIndex[textBox.index] = commbox_children.index(this);
				$(textBox).val(optionsData[textBox.index][currentIndex[textBox.index]][config.title_name]).focus();
				optionsContainer[textBox.index].hide();
				if (typeof(config.callback.choose) == 'function')
					config.callback.choose(optionsData[textBox.index][$(this).attr('data-index')], textBox);
			}).mouseout(function(){
         optionsContainer[textBox.index]["currentOption"]=undefined;
      });


		};

		// 初始化配置信息
		function bootstrap(textBox) {
			if (typeof config.offset.top == 'undefined') config.offset.top = 0;
			if (typeof config.offset.left == 'undefined') config.offset.left = 0;

			// input、width、url选择器参数必须有
			if (!$(textBox).size()) return false;

			if (config.width == null) return false;
			if (config.url == null) return false;

			if (config.height == null) config.height = 'auto';

			if(config.width == null) config.width = $(textBox).width();
			if(config.height == null) config.height = $(textBox).height();

			var interlinkage = '?';
			if (config.url.indexOf('?') >= 0) interlinkage = '&';

			url = config.url + interlinkage + config.param_name + '=:param_name' + '&callback=?';

			return true;
		}
	};
	}
});
