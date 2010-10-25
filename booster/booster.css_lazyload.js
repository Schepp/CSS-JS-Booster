if(typeof booster_add_all_stylesheets != 'function'){
	var booster_add_all_stylesheets = function(){
		if(typeof booster_stylesheets != 'undefined'){
			for(var i = booster_stylesheets.length;i--;){
				if(document.all && !window.postMessage) booster_add_stylesheet(booster_stylesheets[i].replace(/booster_css.php/,'booster_css_ie.php'));
				else booster_add_stylesheet(booster_stylesheets[i]);
			}
		}
	}
	
	var booster_add_stylesheet = function(stylesheet){
		var html_doc = document.getElementsByTagName('head').item(0);
		var css = document.createElement('link');
		css.setAttribute('rel', 'stylesheet');
		css.setAttribute('type', 'text/css');
		css.setAttribute('href', stylesheet);
	    html_doc.appendChild(css);
	}
	
	try {
		document.addEventListener('DOMContentLoaded', function (){
			booster_add_all_stylesheets();
		}, false);
	} catch(e) {
		if(typeof window.postMessage != 'undefined' && window == top) (function(){
			try {
				document.documentElement.doScroll("left");
			} catch(error) {
				setTimeout(arguments.callee, 10);
				return;
			}
			booster_add_all_stylesheets();
		})();
		else booster_add_all_stylesheets();
	}
}
