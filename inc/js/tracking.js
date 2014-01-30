
href = location.href;

var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-47645120-1']);
_gaq.push(['_setCampNameKey', 'm']);                 // campaign name
_gaq.push(['_setCampMediumKey', 'med']);             // campaign medium
_gaq.push(['_setCampSourceKey', 'pl']);              // campaign source
_gaq.push(['_setCampTermKey', 'ver']);               // campaign term/keyword
_gaq.push(['_setCampContentKey', 'cr']);             // content

_gaq.push(['_trackPageview']);

(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();


jQuery.noConflict();

jQuery(document).ready(function(){
	// Creating custom :external selector
	/*
	 jQuery.expr[':'].external = function(obj){
	 return !obj.href.match(/^mailto\:/) && !obj.href.match(/^javascript\:/) && (obj.hostname != location.hostname);
	 };

	 jQuery('a.afcorp-class:external').click(function(){
	 var href = jQuery(this).attr('href');
	 _gaq.push(['_link', href ]); return false;
	 });
	 */
	jQuery('#menu-footer-external-link-menu a').each(function () {
		var theurl = jQuery(this).attr('href');
		var protomatch = /^(http):\/\/www\./;
		var b = theurl.replace(protomatch, '');
		jQuery(this).attr("onClick", "_gaq.push(['_trackEvent', 'Outbound Link', 'Outbound Link', '"+b+"' ]);");
	});

	jQuery("a[href$='pdf']").each(function () {
		jQuery(this).attr('target', '_blank');

		var thecategory = "Material Download:Other";
		var theclick = jQuery(this).attr("onClick");

		if(theclick === undefined) { // Only do this if onClick isn't already set.
			var thefile = jQuery(this).attr('href').split('/').pop();
			if(thefile == 'AF1786.pdf') {
				thecategory = "Material Download:Candidate Form";
			}
			if (thefile.indexOf('Courses_')>-1) {
				thecategory = "Material Download:Course Sequence";
			}
			jQuery(this).attr("onClick", "_gaq.push(['_trackEvent', '"+thecategory+"', 'Download', '"+thefile+"' ]);");
		}
	});

	jQuery(".flexslider .home_slider_info a").each(function () {
		var theloc = jQuery(this).attr('href');
		jQuery(this).attr("onClick", "_gaq.push(['_trackEvent', 'Homepage Hero Image', 'Click', '"+theloc+"']);");
	});


	//.google-ajax-feed-text a
	jQuery(".google-ajax-feed-text a").each(function () {
		var theloc = jQuery(this).attr('href');
		jQuery(this).attr("onClick", "_gaq.push(['_trackSocial', 'facebook', 'Click', '"+theloc+"' ]);");
	});

	/*
	 // The following was throwing an error in jQuery core.
	 jQuery("a[href*='/eligibility/']").each(function () {
	 jQuery(this).attr("onClick", "_gaq.push(['_trackevent', 'Application Eligibility Check', 'Internal Link', 'Application Eligibility Check']);");
	 });
	 */
	// Delay external links and downloads to allow GA to recieve tracking info.
	jQuery("a").on('click',function(e){
		var url = jQuery(this).attr("href");
		var target = jQuery(this).attr("target");
		if ((e.currentTarget.host != window.location.host) || (url.indexOf('.pdf')>-1)) {
			if (e.metaKey || e.ctrlKey || target=="_blank") {
				//if (e.metaKey || e.ctrlKey) {
				var newtab = true;
			}
			if (!newtab && (!(url.indexOf('mailto:')==0))) {
				e.preventDefault();
				setTimeout('document.location = "' + url + '"', 100);
			} else if (newtab){
				e.preventDefault();
				setTimeout('window.open("' + url + '")', 100);
			}
		}
	});
	//addLinkerEvents(); // Next section
});

/****************************************************
 Author: Brian J Clifton
 Url: http://www.advanced-web-metrics.com/scripts
 This script is free to use as long as this info is left in

 Combined script for tracking external links, file downloads and mailto links

 All scripts presented have been tested and validated by the author and are believed to be correct
 as of the date of publication or posting. The Google Analytics software on which they depend is
 subject to change, however; and therefore no warranty is expressed or implied that they will
 work as described in the future. Always check the most current Google Analytics documentation.

 Thanks to Nick Mikailovski (Google) for intitial discussions & Holger Tempel from webalytics.de
 for pointing out the original flaw of doing this in IE.

 ****************************************************/
// Only links written to the page (already in the DOM) will be tagged
// This version is for ga.js (last updated Jan 15th 2009)


function addLinkerEvents() {
	var Begin = new Date();
	var Start = Begin.getTime();

	var as = document.getElementsByTagName("a");
	var extTrack = ["academyadmissions.com","www.academyadmissions.com"];
	// List of local sites that should not be treated as an outbound link. Include at least your own domain here

	var extDoc = [".doc",".xls",".exe",".zip",".pdf",".js"];
	//List of file extensions on your site. Add/edit as you require

	/*If you edit no further below this line, Top Content will report as follows:
	 /ext/url-of-external-site
	 /downloads/filename
	 /mailto/email-address-clicked
	 */

	for(var i=0; i<as.length; i++) {
		var flag = 0;
		var tmp = as[i].getAttribute("onclick");

		// IE6-IE7 fix (null values error) with thanks to Julien Bissonnette for this
		if (tmp != null) {
			tmp = String(tmp);
			if (tmp.indexOf('urchinTracker') > -1 || tmp.indexOf('_trackPageview') > -1)
				continue;
		}
		// Tracking outbound links off site - not the GATC
		for (var j=0; j<extTrack.length; j++) {
			if (as[i].href.indexOf(extTrack[j]) == -1 && as[i].href.indexOf('google-analytics.com') == -1 ) {
				flag++;
			}
		}

		//CASEY - The following 2 blocks of code - Provided by GSDM - were overwritting external links and mailto links.

//    if (flag == extTrack.length && as[i].href.indexOf("mailto:") == -1){
//      //as[i].onclick = function(){ var splitResult = this.href.split("//");pageTracker._trackPageview('/ext/' +splitResult[1]) + ";" +((tmp != null) ? tmp+";" : "");};
//      as[i].onclick = function(){
//        var End = new Date(); var Stop = End.getTime(); var timeElapse = Stop - Start; var splitResult = this.href.split("//");
//        //pageTracker._trackEvent('/ext/', 'track- '+ splitResult[1],'Time',parseInt(timeElapse)) + ";" +((tmp != null) ? tmp+";" : "");
//        _gaq.push(['_trackEvent', '/ext/', 'track- '+splitResult[1], 'Time', parseInt(timeElapse)])+";"+((tmp != null) ? tmp+";" : "");
//      };
//      //alert(as[i] +"  ext/" +splitResult[1])
//    }

		// added to track mailto links 23-Oct-2007
		// updated 31-Oct-2008 to remove break command - thanks to Victor Geerdink for spotting this
		//if (as[i].href.indexOf("mailto:") != -1) {
		//  as[i].onclick = function(){
		//    var splitResult = this.href.split(":");//pageTracker._trackPageview('/mailto/' +splitResult[1])+ ";"+((tmp != null) ? tmp+";" : "");
		//    //pageTracker._trackEvent('/mailto/' +splitResult[1],'MailTo -'+splitResult[1],'Mail',parseInt(timeElapse))+ ";"+((tmp != null) ? tmp+";" : "");}
		//    _gaq.push(['_trackEvent', '/mailto/' +splitResult[1], 'MailTo - '+ splitResult[1], 'Mail', parseInt(timeElapse)]) + ";" +((tmp != null) ? tmp+";" : "");
		//    //alert(as[i] +"  mailto/" +splitResult[1])
		//  }
		//}
	}
}

// Copyright 2012 Google Inc. All Rights Reserved.

/**
 * @fileoverview A simple script to automatically track Facebook and Twitter
 * buttons using Google Analytics social tracking feature.
 * @author api.nickm@gmail.com (Nick Mihailovski)
 * @author api.petef@gmail.com (Pete Frisella)
 */


/**
 * Namespace.
 * @type {Object}.
 */
var _ga = _ga || {};


/**
 * Ensure global _gaq Google Analytics queue has been initialized.
 * @type {Array}
 */
var _gaq = _gaq || [];


/**
 * Tracks social interactions by iterating through each tracker object
 * of the page, and calling the _trackSocial method. This function
 * should be pushed onto the _gaq queue. For details on parameters see
 * http://code.google.com/apis/analytics/docs/gaJS/gaJSApiSocialTracking.html
 * @param {string} network The network on which the action occurs.
 * @param {string} socialAction The type of action that happens.
 * @param {string} opt_target Optional text value that indicates the
 *     subject of the action.
 * @param {string} opt_pagePath Optional page (by path, not full URL)
 *     from which the action occurred.
 * @return a function that iterates over each tracker object
 *    and calls the _trackSocial method.
 * @private
 */
_ga.getSocialActionTrackers_ = function( network, socialAction, opt_target, opt_pagePath) {
	return function() {
		var trackers = _gat._getTrackers();
		for (var i = 0, tracker; tracker = trackers[i]; i++) {
			tracker._trackSocial(network, socialAction, opt_target, opt_pagePath);
		}
	};
};

/**
 * Tracks Facebook likes, unlikes and sends by suscribing to the Facebook
 * JSAPI event model. Note: This will not track facebook buttons using the
 * iframe method.
 * @param {string} opt_pagePath An optional URL to associate the social
 *     tracking with a particular page.
 */
_ga.trackFacebook = function(opt_pagePath) {
	try {
		if (FB && FB.Event && FB.Event.subscribe) {
			FB.Event.subscribe('edge.create', function(opt_target) {
				_gaq.push(_ga.getSocialActionTrackers_('facebook', 'like',
				                                       opt_target, opt_pagePath));
			});
			FB.Event.subscribe('edge.remove', function(opt_target) {
				_gaq.push(_ga.getSocialActionTrackers_('facebook', 'unlike',
				                                       opt_target, opt_pagePath));
			});
			FB.Event.subscribe('message.send', function(opt_target) {
				_gaq.push(_ga.getSocialActionTrackers_('facebook', 'send',
				                                       opt_target, opt_pagePath));
			});
		}
	} catch (e) {}
};


/**
 * Handles tracking for Twitter click and tweet Intent Events which occur
 * everytime a user Tweets using a Tweet Button, clicks a Tweet Button, or
 * clicks a Tweet Count. This method should be binded to Twitter click and
 * tweet events and used as a callback function.
 * Details here: http://dev.twitter.com/docs/intents/events
 * @param {object} intent_event An object representing the Twitter Intent Event
 *     passed from the Tweet Button.
 * @param {string} opt_pagePath An optional URL to associate the social
 *     tracking with a particular page.
 * @private
 */
_ga.trackTwitterHandler_ = function(intent_event, opt_pagePath) {
	var opt_target; //Default value is undefined
	if (intent_event && intent_event.type == 'tweet' ||
	    intent_event.type == 'click') {
		if (intent_event.target.nodeName == 'IFRAME') {
			opt_target = _ga.extractParamFromUri_(intent_event.target.src, 'url');
		}
		var socialAction = intent_event.type + ((intent_event.type == 'click') ?
		                                        '-' + intent_event.region : ''); //append the type of click to action
		_gaq.push(_ga.getSocialActionTrackers_('twitter', socialAction, opt_target,
		                                       opt_pagePath));
	}
};

/**
 * Binds Twitter Intent Events to a callback function that will handle
 * the social tracking for Google Analytics. This function should be called
 * once the Twitter widget.js file is loaded and ready.
 * @param {string} opt_pagePath An optional URL to associate the social
 *     tracking with a particular page.
 */
_ga.trackTwitter = function(opt_pagePath) {
	intent_handler = function(intent_event) {
		_ga.trackTwitterHandler_(intent_event, opt_pagePath);
	};

	//bind twitter Click and Tweet events to Twitter tracking handler
	twttr.events.bind('click', intent_handler);
	twttr.events.bind('tweet', intent_handler);
};


/**
 * Extracts a query parameter value from a URI.
 * @param {string} uri The URI from which to extract the parameter.
 * @param {string} paramName The name of the query paramater to extract.
 * @return {string} The un-encoded value of the query paramater. undefined
 *     if there is no URI parameter.
 * @private
 */
_ga.extractParamFromUri_ = function(uri, paramName) {
	if (!uri) {
		return;
	}
	var regex = new RegExp('[\\?&#]' + paramName + '=([^&#]*)');
	var params = regex.exec(uri);
	if (params != null) {
		return unescape(params[1]);
	}
	return;
};
