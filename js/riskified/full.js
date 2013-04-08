/**
 * script for all pages
 */


(function() {
	function riskifiedBeaconLoad() {
		var url = "http://riskified-staging.herokuapp.com/assets/beacon.js";
		var s = document.createElement('script');
		s.type = 'text/javascript';
		s.async = true;
		s.src = url;
		var x = document.getElementsByTagName('script')[0];
		x.parentNode.insertBefore(s, x);
	}
	window.attachEvent ? window.attachEvent('onload', riskifiedBeaconLoad)
			: window.addEventListener('load', riskifiedBeaconLoad, false);
})();
