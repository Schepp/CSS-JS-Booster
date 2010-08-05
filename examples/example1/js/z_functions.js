window.onload = function(){
	milliseconds = (new Date()).getTime() - beforeload;
	alert((milliseconds / 1000) + ' seconds load time');
}
Cufon.replace('h2', { fontFamily: 'Sansation' });
Cufon.replace('h3', { fontFamily: 'Sansation' });
Cufon.replace('p.greentext', { fontFamily: 'Sansation' });
Cufon.replace('p.services', { fontFamily: 'Sansation' });
Cufon.replace('p.browntext', { fontFamily: 'Sansation' });
Cufon.now();
