window.onload = function(){
	milliseconds = (new Date()).getTime() - beforeload;
	alert((milliseconds / 1000) + ' seconds load time');
}
Cufon.replace('h2', { fontFamily: 'Aller' });
Cufon.replace('h3', { fontFamily: 'Aller' });
Cufon.replace('p.greentext', { fontFamily: 'Aller' });
Cufon.replace('p.services', { fontFamily: 'Aller' });
Cufon.replace('p.browntext', { fontFamily: 'Notethis' });
Cufon.now();
