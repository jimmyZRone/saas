define('url',function(require, exports) {
	exports.make = function(param){//生成地址
		var baseurl = document.URL.split('?')[0];
		baseurl = baseurl.indexOf('#') >= 0 ? baseurl.split('#')[0] : baseurl;
		if(typeof param == 'string'){
			param = param.split('/');
			var data = {};
			data['c'] = param.length > 0 ? param[0].toLowerCase() : 'index';
			data['a'] = param.length > 1 ? param[1].toLowerCase() : 'index';
			if(param.length > 2){
				for(var i=2;i<param.length;i=i+2){
					data[param[i]] = i<param.length-2 ? parem[i+1] : '';
				}
			}
			param = data;
		}
		var url = '';
		for(var key in param){
			url = url + '&' + key + '=' + param[key];
		}
		url = url.replace(/^&*(.*)&*$/,'$1');
		baseurl = baseurl.replace(/^https?:\/\/[^\/]+(\/.*)$/,'$1');
		if(baseurl == '/'){
			baseurl = '/index.php';
		}
		return baseurl+'?'+url;
	};
	var get_args = null;
	var args_url = null;
	exports.get = function(key){//取得URL地址参数
		var args = document.URL.split('#');
		delete args[0];
		var temp = '';
		for(var k in args){
			temp += args[k]+'#';
		}
		temp = temp.replace(/^(.*)#$/,'$1');
		if(get_args == null || args_url != temp){//解析参数
			args_url = temp;
			args = temp;
			get_args = {};
			args = args.split('?');
			temp = args.length > 1 ? args[1] : '';
			if(temp != ''){
				args = temp.split('&');
				for(var k in args){
					var str = args[k].split('=');
					temp = '';
					for(var i=1;i<str.length;i++){
						temp += str[i]+'&';
					}
					temp = temp.replace(/^(.*)&$/,'$1');
					get_args[str[0]] = temp;
				}
			}
		}
		return typeof get_args[key] == 'string' ? get_args[key] : '';
	};
});