define(function(require,exports){
	var $ = require('jquery');
	var child_data_hash = (new function(){
		var obj = null;
		var objfun = function(){
			var hashchild = 'input,select,button,textarea,div,span,p,li';
			var attrs = ['accesskey','class','contenteditable','contextmenu','data-*',
			             'dir','draggable','dropzone','hidden','id','lang','spellcheck',
			             'style','tabindex','title','translate','on*','type','value',
			             'placeholder','nullmsg','name','choosenull','errormsg','datatype',
			             'hasevent','business-url','prev-*','pre-*','href','src','checked','selected','readonly'];//所有标签属性
			var hashs = {};
			//取得hash
			this.gethash = function(child){
				var hash_text = '';
				hashchilds = hashchild.split(',');
				for(var i = 0;i<hashchilds.length;i++){
					var childs = $(hashchilds[i],child);
					for(var j=0;j<childs.length;j++){
						var self = $(childs[j]);
						var name = self.attr('name');
						var tagname = childs[j].tagName.toLowerCase();
						if(typeof name != 'string' || name == ''){//没有名字都不知道怎么搞了啊~~
							name = '__'+tagname+'__';
						}
						if(tagname == 'input'){//判断是否需要计算
							var type = self.attr('type');
							if((type == 'radio' || type == 'checkbox') && (!self[0].checked && self[0].getAttribute('checked') != 'checked')){//不符合要求
								continue;
							}
						}
						var value = self.val();
						//判断自定义属性，大坑
						var attributes = childs[j].attributes;
						for(var k = 0;k<attributes.length;k++){
							var attr = attributes[k];
							var istrue = true;
							for(var kk=0;kk<attrs.length;kk++){
								var attrname = attr.name+'';
								if((attrs[kk].indexOf('*') > 0 && attrname.indexOf(attrs[kk].split('*')[0]) >= 0) || attrs[kk] == attrname){
									istrue = false;
									break;
								}
							}
							if(istrue && attr.value != ''){//不属于正常属性
								value += '|'+attr.name+':'+attr.value;
							}
						}
						if(value == ''){
							continue;
						}
						hash_text += name + '=' + value + '&';
					}
				}
				hash_text = hash_text.replace(/^&*(.*?)&*$/gi,'$1');
				return hash_text;
			};
			//保存hash
			this.savehash = function(key,child){
				hashs[key] = this.gethash(child);
				return hashs[key];
			};
			//判断hash是否改变
			this.ischange = function(key,child){
				if(typeof hashs[key] == 'undefined'){
					return false;
				}
				var hash = this.gethash(child);
				return hash !== hashs[key];
			};
		};
		this.get = function(){
			if(obj === null){
				obj = new objfun();
			}
			return obj;
		}
	}());
	exports.hash = child_data_hash.get();
});