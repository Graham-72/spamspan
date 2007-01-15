/*
	--------------------------------------------------------------------------
	Version: 1.01
	Release date: 13/05/2006
	Last update: 13/07/2006

	(c) 2006 SpamSpan (www.spamspan.com)
	Modified for drupal by Lawrence Akka 2006

	This program is distributed under the terms of the GNU General Public
	Licence version 2, available at http://www.gnu.org/licenses/gpl.txt
	--------------------------------------------------------------------------
*/



/*
	--------------------------------------------------------------------------
	Do not edit past this point (unless you know what you are doing).
	--------------------------------------------------------------------------
*/

// load SpamSpan
if (isJsEnabled()) {
addLoadEvent(spamSpan);
 }

function spamSpan() {
	var allSpamSpans = spamSpanGetElementsByClass(spamSpanMainClass, document, 'span');
	for (var i=0; i<allSpamSpans.length; i++) {
		// get data
		var user = getSpanValue(spamSpanUserClass, allSpamSpans[i]);
		var domain = getSpanValue(spamSpanDomainClass, allSpamSpans[i]);
		var anchorText = getSpanValue(spamSpanAnchorTextClass, allSpamSpans[i]);
		// create new anchor tag
		var email = cleanSpan(user) + String.fromCharCode(32*2) + cleanSpan(domain);
		var anchorTagText = document.createTextNode(anchorText ? anchorText : email);
		var anchorTag = document.createElement('a');
			anchorTag.className = spamSpanMainClass;
			anchorTag.setAttribute('href', String.fromCharCode(109,97,105,108,116,111,58) + email);
			anchorTag.appendChild(anchorTagText);
		// replace the span with anchor
		allSpamSpans[i].parentNode.replaceChild(anchorTag, allSpamSpans[i]);
	}
}

function spamSpanGetElementsByClass(searchClass, scope, tag) {
	var classElements = new Array();
	if (scope == null) node = document;
	if (tag == null) tag = '*';
	var els = scope.getElementsByTagName(tag);
	var elsLen = els.length;
	var pattern = new RegExp("(^|\s)"+searchClass+"(\s|$)");
	for (var i = 0, j = 0; i < elsLen; i++) {
		if ( pattern.test(els[i].className) ) {
			classElements[j] = els[i];
			j++;
		}
	}
	return classElements;
}

function getSpanValue(searchClass, scope) {
	var span = getElementsByClass(searchClass, scope, 'span');
	if (span[0]) return span[0].firstChild.nodeValue;
	else return false;
}

function cleanSpan(string) {
	// string = string.replace(//g, '');
	// replace variations of [dot] with .
	string = string.replace(/[\[\(\{]?[dD][oO0][tT][\}\)\]]?/g, '.');
	// replace spaces with nothing
	string = string.replace(/\s+/g, '');
	return string;
}

