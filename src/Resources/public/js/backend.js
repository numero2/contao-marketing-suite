/* jshint esversion:6 */
/* jshint ignore:start */
/*!***************************************************
* mark.js v8.11.1
* https://markjs.io/
* Copyright (c) 2014–2018, Julian Kühnel
* Released under the MIT license https://git.io/vwTVl
*****************************************************/
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):e.Mark=t()}(this,function(){"use strict";var e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},t=function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")},n=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),r=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},i=function(){function e(n){var r=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:[],o=arguments.length>3&&void 0!==arguments[3]?arguments[3]:5e3;t(this,e),this.ctx=n,this.iframes=r,this.exclude=i,this.iframesTimeout=o}return n(e,[{key:"getContexts",value:function(){var e=void 0,t=[];return e=void 0!==this.ctx&&this.ctx?NodeList.prototype.isPrototypeOf(this.ctx)?Array.prototype.slice.call(this.ctx):Array.isArray(this.ctx)?this.ctx:"string"==typeof this.ctx?Array.prototype.slice.call(document.querySelectorAll(this.ctx)):[this.ctx]:[],e.forEach(function(e){var n=t.filter(function(t){return t.contains(e)}).length>0;-1!==t.indexOf(e)||n||t.push(e)}),t}},{key:"getIframeContents",value:function(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:function(){},r=void 0;try{var i=e.contentWindow;if(r=i.document,!i||!r)throw new Error("iframe inaccessible")}catch(e){n()}r&&t(r)}},{key:"isIframeBlank",value:function(e){var t="about:blank",n=e.getAttribute("src").trim();return e.contentWindow.location.href===t&&n!==t&&n}},{key:"observeIframeLoad",value:function(e,t,n){var r=this,i=!1,o=null,a=function a(){if(!i){i=!0,clearTimeout(o);try{r.isIframeBlank(e)||(e.removeEventListener("load",a),r.getIframeContents(e,t,n))}catch(e){n()}}};e.addEventListener("load",a),o=setTimeout(a,this.iframesTimeout)}},{key:"onIframeReady",value:function(e,t,n){try{"complete"===e.contentWindow.document.readyState?this.isIframeBlank(e)?this.observeIframeLoad(e,t,n):this.getIframeContents(e,t,n):this.observeIframeLoad(e,t,n)}catch(e){n()}}},{key:"waitForIframes",value:function(e,t){var n=this,r=0;this.forEachIframe(e,function(){return!0},function(e){r++,n.waitForIframes(e.querySelector("html"),function(){--r||t()})},function(e){e||t()})}},{key:"forEachIframe",value:function(t,n,r){var i=this,o=arguments.length>3&&void 0!==arguments[3]?arguments[3]:function(){},a=t.querySelectorAll("iframe"),s=a.length,c=0;a=Array.prototype.slice.call(a);var u=function(){--s<=0&&o(c)};s||u(),a.forEach(function(t){e.matches(t,i.exclude)?u():i.onIframeReady(t,function(e){n(t)&&(c++,r(e)),u()},u)})}},{key:"createIterator",value:function(e,t,n){return document.createNodeIterator(e,t,n,!1)}},{key:"createInstanceOnIframe",value:function(t){return new e(t.querySelector("html"),this.iframes)}},{key:"compareNodeIframe",value:function(e,t,n){if(e.compareDocumentPosition(n)&Node.DOCUMENT_POSITION_PRECEDING){if(null===t)return!0;if(t.compareDocumentPosition(n)&Node.DOCUMENT_POSITION_FOLLOWING)return!0}return!1}},{key:"getIteratorNode",value:function(e){var t=e.previousNode(),n=void 0;return n=null===t?e.nextNode():e.nextNode()&&e.nextNode(),{prevNode:t,node:n}}},{key:"checkIframeFilter",value:function(e,t,n,r){var i=!1,o=!1;return r.forEach(function(e,t){e.val===n&&(i=t,o=e.handled)}),this.compareNodeIframe(e,t,n)?(!1!==i||o?!1===i||o||(r[i].handled=!0):r.push({val:n,handled:!0}),!0):(!1===i&&r.push({val:n,handled:!1}),!1)}},{key:"handleOpenIframes",value:function(e,t,n,r){var i=this;e.forEach(function(e){e.handled||i.getIframeContents(e.val,function(e){i.createInstanceOnIframe(e).forEachNode(t,n,r)})})}},{key:"iterateThroughNodes",value:function(e,t,n,r,i){for(var o=this,a=this.createIterator(t,e,r),s=[],c=[],u=void 0,l=void 0;function(){var e=o.getIteratorNode(a);return l=e.prevNode,u=e.node}();)this.iframes&&this.forEachIframe(t,function(e){return o.checkIframeFilter(u,l,e,s)},function(t){o.createInstanceOnIframe(t).forEachNode(e,function(e){return c.push(e)},r)}),c.push(u);c.forEach(function(e){n(e)}),this.iframes&&this.handleOpenIframes(s,e,n,r),i()}},{key:"forEachNode",value:function(e,t,n){var r=this,i=arguments.length>3&&void 0!==arguments[3]?arguments[3]:function(){},o=this.getContexts(),a=o.length;a||i(),o.forEach(function(o){var s=function(){r.iterateThroughNodes(e,o,t,n,function(){--a<=0&&i()})};r.iframes?r.waitForIframes(o,s):s()})}}],[{key:"matches",value:function(e,t){var n="string"==typeof t?[t]:t,r=e.matches||e.matchesSelector||e.msMatchesSelector||e.mozMatchesSelector||e.oMatchesSelector||e.webkitMatchesSelector;if(r){var i=!1;return n.every(function(t){return!r.call(e,t)||(i=!0,!1)}),i}return!1}}]),e}(),o=function(){function e(n){t(this,e),this.opt=r({},{diacritics:!0,synonyms:{},accuracy:"partially",caseSensitive:!1,ignoreJoiners:!1,ignorePunctuation:[],wildcards:"disabled"},n)}return n(e,[{key:"create",value:function(e){return"disabled"!==this.opt.wildcards&&(e=this.setupWildcardsRegExp(e)),e=this.escapeStr(e),Object.keys(this.opt.synonyms).length&&(e=this.createSynonymsRegExp(e)),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(e=this.setupIgnoreJoinersRegExp(e)),this.opt.diacritics&&(e=this.createDiacriticsRegExp(e)),e=this.createMergedBlanksRegExp(e),(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(e=this.createJoinersRegExp(e)),"disabled"!==this.opt.wildcards&&(e=this.createWildcardsRegExp(e)),e=this.createAccuracyRegExp(e),new RegExp(e,"gm"+(this.opt.caseSensitive?"":"i"))}},{key:"escapeStr",value:function(e){return e.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")}},{key:"createSynonymsRegExp",value:function(e){var t=this.opt.synonyms,n=this.opt.caseSensitive?"":"i",r=this.opt.ignoreJoiners||this.opt.ignorePunctuation.length?"\0":"";for(var i in t)if(t.hasOwnProperty(i)){var o=t[i],a="disabled"!==this.opt.wildcards?this.setupWildcardsRegExp(i):this.escapeStr(i),s="disabled"!==this.opt.wildcards?this.setupWildcardsRegExp(o):this.escapeStr(o);""!==a&&""!==s&&(e=e.replace(new RegExp("("+this.escapeStr(a)+"|"+this.escapeStr(s)+")","gm"+n),r+"("+this.processSynonyms(a)+"|"+this.processSynonyms(s)+")"+r))}return e}},{key:"processSynonyms",value:function(e){return(this.opt.ignoreJoiners||this.opt.ignorePunctuation.length)&&(e=this.setupIgnoreJoinersRegExp(e)),e}},{key:"setupWildcardsRegExp",value:function(e){return e=e.replace(/(?:\\)*\?/g,function(e){return"\\"===e.charAt(0)?"?":""}),e.replace(/(?:\\)*\*/g,function(e){return"\\"===e.charAt(0)?"*":""})}},{key:"createWildcardsRegExp",value:function(e){var t="withSpaces"===this.opt.wildcards;return e.replace(/\u0001/g,t?"[\\S\\s]?":"\\S?").replace(/\u0002/g,t?"[\\S\\s]*?":"\\S*")}},{key:"setupIgnoreJoinersRegExp",value:function(e){return e.replace(/[^(|)\\]/g,function(e,t,n){var r=n.charAt(t+1);return/[(|)\\]/.test(r)||""===r?e:e+"\0"})}},{key:"createJoinersRegExp",value:function(e){var t=[],n=this.opt.ignorePunctuation;return Array.isArray(n)&&n.length&&t.push(this.escapeStr(n.join(""))),this.opt.ignoreJoiners&&t.push("\\u00ad\\u200b\\u200c\\u200d"),t.length?e.split(/\u0000+/).join("["+t.join("")+"]*"):e}},{key:"createDiacriticsRegExp",value:function(e){var t=this.opt.caseSensitive?"":"i",n=this.opt.caseSensitive?["aàáảãạăằắẳẵặâầấẩẫậäåāą","AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćč","CÇĆČ","dđď","DĐĎ","eèéẻẽẹêềếểễệëěēę","EÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïī","IÌÍỈĨỊÎÏĪ","lł","LŁ","nñňń","NÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøō","OÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rř","RŘ","sšśșş","SŠŚȘŞ","tťțţ","TŤȚŢ","uùúủũụưừứửữựûüůū","UÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿ","YÝỲỶỸỴŸ","zžżź","ZŽŻŹ"]:["aàáảãạăằắẳẵặâầấẩẫậäåāąAÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ","cçćčCÇĆČ","dđďDĐĎ","eèéẻẽẹêềếểễệëěēęEÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ","iìíỉĩịîïīIÌÍỈĨỊÎÏĪ","lłLŁ","nñňńNÑŇŃ","oòóỏõọôồốổỗộơởỡớờợöøōOÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ","rřRŘ","sšśșşSŠŚȘŞ","tťțţTŤȚŢ","uùúủũụưừứửữựûüůūUÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ","yýỳỷỹỵÿYÝỲỶỸỴŸ","zžżźZŽŻŹ"],r=[];return e.split("").forEach(function(i){n.every(function(n){if(-1!==n.indexOf(i)){if(r.indexOf(n)>-1)return!1;e=e.replace(new RegExp("["+n+"]","gm"+t),"["+n+"]"),r.push(n)}return!0})}),e}},{key:"createMergedBlanksRegExp",value:function(e){return e.replace(/[\s]+/gim,"[\\s]+")}},{key:"createAccuracyRegExp",value:function(e){var t=this,n=this.opt.accuracy,r="string"==typeof n?n:n.value,i="string"==typeof n?[]:n.limiters,o="";switch(i.forEach(function(e){o+="|"+t.escapeStr(e)}),r){case"partially":default:return"()("+e+")";case"complementary":return"()([^"+(o="\\s"+(o||this.escapeStr("!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~¡¿")))+"]*"+e+"[^"+o+"]*)";case"exactly":return"(^|\\s"+o+")("+e+")(?=$|\\s"+o+")"}}}]),e}();return function(){function a(e){t(this,a),this.ctx=e,this.ie=!1;var n=window.navigator.userAgent;(n.indexOf("MSIE")>-1||n.indexOf("Trident")>-1)&&(this.ie=!0)}return n(a,[{key:"log",value:function(t){var n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"debug",r=this.opt.log;this.opt.debug&&"object"===(void 0===r?"undefined":e(r))&&"function"==typeof r[n]&&r[n]("mark.js: "+t)}},{key:"getSeparatedKeywords",value:function(e){var t=this,n=[];return e.forEach(function(e){t.opt.separateWordSearch?e.split(" ").forEach(function(e){e.trim()&&-1===n.indexOf(e)&&n.push(e)}):e.trim()&&-1===n.indexOf(e)&&n.push(e)}),{keywords:n.sort(function(e,t){return t.length-e.length}),length:n.length}}},{key:"isNumeric",value:function(e){return Number(parseFloat(e))==e}},{key:"checkRanges",value:function(e){var t=this;if(!Array.isArray(e)||"[object Object]"!==Object.prototype.toString.call(e[0]))return this.log("markRanges() will only accept an array of objects"),this.opt.noMatch(e),[];var n=[],r=0;return e.sort(function(e,t){return e.start-t.start}).forEach(function(e){var i=t.callNoMatchOnInvalidRanges(e,r),o=i.start,a=i.end;i.valid&&(e.start=o,e.length=a-o,n.push(e),r=a)}),n}},{key:"callNoMatchOnInvalidRanges",value:function(e,t){var n=void 0,r=void 0,i=!1;return e&&void 0!==e.start?(n=parseInt(e.start,10),r=n+parseInt(e.length,10),this.isNumeric(e.start)&&this.isNumeric(e.length)&&r-t>0&&r-n>0?i=!0:(this.log("Ignoring invalid or overlapping range: "+JSON.stringify(e)),this.opt.noMatch(e))):(this.log("Ignoring invalid range: "+JSON.stringify(e)),this.opt.noMatch(e)),{start:n,end:r,valid:i}}},{key:"checkWhitespaceRanges",value:function(e,t,n){var r=void 0,i=!0,o=n.length,a=t-o,s=parseInt(e.start,10)-a;return s=s>o?o:s,r=s+parseInt(e.length,10),r>o&&(r=o,this.log("End range automatically set to the max value of "+o)),s<0||r-s<0||s>o||r>o?(i=!1,this.log("Invalid range: "+JSON.stringify(e)),this.opt.noMatch(e)):""===n.substring(s,r).replace(/\s+/g,"")&&(i=!1,this.log("Skipping whitespace only range: "+JSON.stringify(e)),this.opt.noMatch(e)),{start:s,end:r,valid:i}}},{key:"getTextNodes",value:function(e){var t=this,n="",r=[];this.iterator.forEachNode(NodeFilter.SHOW_TEXT,function(e){r.push({start:n.length,end:(n+=e.textContent).length,node:e})},function(e){return t.matchesExclude(e.parentNode)?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT},function(){e({value:n,nodes:r})})}},{key:"matchesExclude",value:function(e){return i.matches(e,this.opt.exclude.concat(["script","style","title","head","html"]))}},{key:"wrapRangeInTextNode",value:function(e,t,n){var r=this.opt.element?this.opt.element:"mark",i=e.splitText(t),o=i.splitText(n-t),a=document.createElement(r);return a.setAttribute("data-markjs","true"),this.opt.className&&a.setAttribute("class",this.opt.className),a.textContent=i.textContent,i.parentNode.replaceChild(a,i),o}},{key:"wrapRangeInMappedTextNode",value:function(e,t,n,r,i){var o=this;e.nodes.every(function(a,s){var c=e.nodes[s+1];if(void 0===c||c.start>t){if(!r(a.node))return!1;var u=t-a.start,l=(n>a.end?a.end:n)-a.start,h=e.value.substr(0,a.start),f=e.value.substr(l+a.start);if(a.node=o.wrapRangeInTextNode(a.node,u,l),e.value=h+f,e.nodes.forEach(function(t,n){n>=s&&(e.nodes[n].start>0&&n!==s&&(e.nodes[n].start-=l),e.nodes[n].end-=l)}),n-=l,i(a.node.previousSibling,a.start),!(n>a.end))return!1;t=a.end}return!0})}},{key:"wrapGroups",value:function(e,t,n,r){return e=this.wrapRangeInTextNode(e,t,t+n),r(e.previousSibling),e}},{key:"separateGroups",value:function(e,t,n,r,i){for(var o=t.length,a=1;a<o;a++){var s=e.textContent.indexOf(t[a]);t[a]&&s>-1&&r(t[a],e)&&(e=this.wrapGroups(e,s,t[a].length,i))}return e}},{key:"wrapMatches",value:function(e,t,n,r,i){var o=this,a=0===t?0:t+1;this.getTextNodes(function(t){t.nodes.forEach(function(t){t=t.node;for(var i=void 0;null!==(i=e.exec(t.textContent))&&""!==i[a];){if(o.opt.separateGroups)t=o.separateGroups(t,i,a,n,r);else{if(!n(i[a],t))continue;var s=i.index;if(0!==a)for(var c=1;c<a;c++)s+=i[c].length;t=o.wrapGroups(t,s,i[a].length,r)}e.lastIndex=0}}),i()})}},{key:"wrapMatchesAcrossElements",value:function(e,t,n,r,i){var o=this,a=0===t?0:t+1;this.getTextNodes(function(t){for(var s=void 0;null!==(s=e.exec(t.value))&&""!==s[a];){var c=s.index;if(0!==a)for(var u=1;u<a;u++)c+=s[u].length;var l=c+s[a].length;o.wrapRangeInMappedTextNode(t,c,l,function(e){return n(s[a],e)},function(t,n){e.lastIndex=n,r(t)})}i()})}},{key:"wrapRangeFromIndex",value:function(e,t,n,r){var i=this;this.getTextNodes(function(o){var a=o.value.length;e.forEach(function(e,r){var s=i.checkWhitespaceRanges(e,a,o.value),c=s.start,u=s.end;s.valid&&i.wrapRangeInMappedTextNode(o,c,u,function(n){return t(n,e,o.value.substring(c,u),r)},function(t){n(t,e)})}),r()})}},{key:"unwrapMatches",value:function(e){for(var t=e.parentNode,n=document.createDocumentFragment();e.firstChild;)n.appendChild(e.removeChild(e.firstChild));t.replaceChild(n,e),this.ie?this.normalizeTextNode(t):t.normalize()}},{key:"normalizeTextNode",value:function(e){if(e){if(3===e.nodeType)for(;e.nextSibling&&3===e.nextSibling.nodeType;)e.nodeValue+=e.nextSibling.nodeValue,e.parentNode.removeChild(e.nextSibling);else this.normalizeTextNode(e.firstChild);this.normalizeTextNode(e.nextSibling)}}},{key:"markRegExp",value:function(e,t){var n=this;this.opt=t,this.log('Searching with expression "'+e+'"');var r=0,i="wrapMatches",o=function(e){r++,n.opt.each(e)};this.opt.acrossElements&&(i="wrapMatchesAcrossElements"),this[i](e,this.opt.ignoreGroups,function(e,t){return n.opt.filter(t,e,r)},o,function(){0===r&&n.opt.noMatch(e),n.opt.done(r)})}},{key:"mark",value:function(e,t){var n=this;this.opt=t;var r=0,i="wrapMatches",a=this.getSeparatedKeywords("string"==typeof e?[e]:e),s=a.keywords,c=a.length;this.opt.acrossElements&&(i="wrapMatchesAcrossElements"),0===c?this.opt.done(r):function e(t){var a=new o(n.opt).create(t),u=0;n.log('Searching with expression "'+a+'"'),n[i](a,1,function(e,i){return n.opt.filter(i,t,r,u)},function(e){u++,r++,n.opt.each(e)},function(){0===u&&n.opt.noMatch(t),s[c-1]===t?n.opt.done(r):e(s[s.indexOf(t)+1])})}(s[0])}},{key:"markRanges",value:function(e,t){var n=this;this.opt=t;var r=0,i=this.checkRanges(e);i&&i.length?(this.log("Starting to mark with the following ranges: "+JSON.stringify(i)),this.wrapRangeFromIndex(i,function(e,t,r,i){return n.opt.filter(e,t,r,i)},function(e,t){r++,n.opt.each(e,t)},function(){n.opt.done(r)})):this.opt.done(r)}},{key:"unmark",value:function(e){var t=this;this.opt=e;var n=this.opt.element?this.opt.element:"*";n+="[data-markjs]",this.opt.className&&(n+="."+this.opt.className),this.log('Removal selector "'+n+'"'),this.iterator.forEachNode(NodeFilter.SHOW_ELEMENT,function(e){t.unwrapMatches(e)},function(e){var r=i.matches(e,n),o=t.matchesExclude(e);return!r||o?NodeFilter.FILTER_REJECT:NodeFilter.FILTER_ACCEPT},this.opt.done)}},{key:"opt",set:function(e){this._opt=r({},{element:"",className:"",exclude:[],iframes:!1,iframesTimeout:5e3,separateWordSearch:!0,acrossElements:!1,ignoreGroups:0,each:function(){},noMatch:function(){},filter:function(){return!0},done:function(){},debug:!1,log:window.console},e)},get:function(){return this._opt}},{key:"iterator",get:function(){return new i(this.ctx,this.opt.iframes,this.opt.exclude,this.opt.iframesTimeout)}}]),a}()});
/* jshint ignore:end */


// text analysis widget
document.addEventListener('tinyMCEInitialized', function (e) {

    var widget = document.querySelector('.widget.text-analysis');
    var tinyMCE = document.querySelector("#ctrl_text_cms_ifr");

    if( !widget || !tinyMCE ) {
        return;
    }

    tinyMCE = tinyMCE.contentDocument.body;

    var markInstance = new Mark(tinyMCE);

    markAnalysisGroup = function( type, group ) {

        markInstance.unmark();

        if( type == 'sentences' ) {

            markInstance.getTextNodes(function(nodes) {

                var str_reverse = function(str) { return str.split("").reverse().join(""); };

                var text = str_reverse(nodes.value);
                var textLength = text.length;
                var lastFound = 0;

                var ranges = [];
                var start = 0;
                var end = 0;

                for( var i = analysisContents[type][group].length-1; i >= 0; i--) {

                    // find last word
                    var word = str_reverse(analysisContents[type][group][i][1]);
                    lastFound = text.indexOf(word, lastFound);
                    if( lastFound === -1 ){
                        lastFound = !ranges.length?0:textLength-ranges[ranges.length-1].start;
                        continue;
                    }
                    end = textLength-lastFound;
                    start = end;

                    // find first word
                    word = str_reverse(analysisContents[type][group][i][0]);

                    var skip = false;

                    // one word sentences no need to search
                    if( group == 0 && word.length === analysisContents[type][group][i][2] ) {
                        start = lastFound;
                        start = textLength-lastFound-word.length-1;
                        ranges.push({start: start, length: end-start});
                        continue;
                    }

                    // try to find first word from reverse
                    while( (end-start) < analysisContents[type][group][i][2] ) {

                        lastFound = text.indexOf(word, lastFound+2);
                        if( lastFound === -1 ){
                            lastFound = !ranges.length?0:textLength-ranges[ranges.length-1].start;
                            skip = true;
                            break;
                        }
                        start = textLength-lastFound-word.length;
                    }

                    if( !skip ) {
                        ranges.push({start: start, length: end-start});
                    }
                }
                markInstance.markRanges(ranges);
            });

        } else {

            // TODO as we use exact matching a word with ':' '.' ',' '?' '!' won't match
            // but partial matching is no solution
            markInstance.mark(
                analysisContents[type][group],
                { "accuracy": "exactly" }
            );
        }
    };

    widget.querySelectorAll('.group li a').forEach(function(button){

        button.addEventListener('click', function(e) {

            e.preventDefault();

            if( this.classList.contains('active') ) {
                this.classList.remove('active');
                markInstance.unmark();
                return;
            }

            // set all other buttons inactive
            widget.querySelectorAll('.group li a').forEach(function(button){
                button.classList.remove('active');
            });

            // set current button active
            this.classList.add('active');

            markAnalysisGroup( this.getAttribute('data-type'), this.getAttribute('data-group') );

        }, false);
    });

    // cleanup all markings before submitting
    document.querySelector('form.tl_form.tl_edit_form').addEventListener('submit', function(e) {

        if( markInstance ) {
            e.preventDefault();
            markInstance.unmark({ done: this.submit });
        }

    }, false);

}, false);

document.addEventListener('DOMContentLoaded', function(){

    // schedule form interaction
    (function(){

        var schedule = document.querySelector('.cms_schedule .title #toggleQuickNav');

        if( schedule ) {

            var yearRadio = document.querySelectorAll('.cms_schedule .title .toggleQuickNav .year input[type="radio"]');

            var yearToggle = document.querySelector('.cms_schedule .title #toggleQuickNavYear');
            for( var i = 0; i < yearRadio.length; i++ ) {

                if( yearToggle ) {
                    yearRadio[i].addEventListener('click', function(e){
                        yearToggle.checked = false;
                        var activeMonth = document.querySelector('.cms_schedule .title .toggleQuickNav .month input[type="radio"]:checked');
                        if( activeMonth ) {
                            activeMonth.checked = false;
                        }
                    });
                }
            }

            var monthRadio = document.querySelectorAll('.cms_schedule .title .toggleQuickNav .month input[type="radio"]');

            var form = document.querySelector('.cms_schedule .title form');
            for( i = 0; i < monthRadio.length; i++ ) {

                if( form ) {
                    monthRadio[i].addEventListener('click', function(e){
                        form.submit();
                    });
                }
            }
        }
    })();


    // link preview
    (function(){

        var linkPreview = document.querySelectorAll('.link-preview .preview a');
        var domain = document.querySelectorAll('input[name="domain"]');

        if( linkPreview ) {

            for( var i = 0; i < linkPreview.length; i++ ) {

                linkPreview[i].addEventListener('click', function(e) {

                    e.preventDefault();

                    var selection = window.getSelection();
                    var range = document.createRange();
                    range.selectNodeContents(e.target);
                    selection.removeAllRanges();
                    selection.addRange(range);

                    var message = "";
                    try {
                        document.execCommand("copy");
                    } catch(err) {
                    }
                    selection.removeAllRanges();
                });

                var field = linkPreview[i].getAttribute('data-field');

                if( field ) {

                    var input = document.querySelector('input[name="'+field+'"]');

                    if( input ) {

                        if( domain ) {
                            for( var j = 0; j < domain.length; j++ ) {

                                if( typeof domain[j].ref == "undefined" ) {
                                    domain[j].ref = [];
                                }

                                domain[j].ref.push(input);
                                domain[j].addEventListener('change', function(e) {

                                    var event;
                                    if(typeof Event === 'function') {
                                        event = new Event('keyup');
                                    } else {
                                        event = document.createEvent('Event');
                                        event.initEvent('keyup', true, true);
                                    }

                                    for( var k = 0; k < e.target.ref.length; k++ ) {
                                        e.target.ref[k].dispatchEvent(event);
                                    }
                                });
                            }
                        }

                        input.refField = linkPreview[i];
                        input.addEventListener('keyup', function(e) {

                            var domain = document.querySelector('input[name="domain"]:checked');

                            if( domain ) {
                                var link = 'https://' + domain.value + '/' + e.target.value;
                                if( e.target.name == 'prefix' ) {
                                    link += "/abc123";
                                }

                                link = link.replace(/\/+/g, "/")
                                anchor = e.target.refField;
                                anchor.href = link;
                                anchor.innerHTML = link;
                            }
                        });
                    }
                }
            }
        }
    })();


    // suggest wizard
    (function(){

        var suggestInput = document.querySelectorAll('fieldset .suggest.wizard input');

        if( suggestInput ) {

            function filter(e) {

                var wizard = e.target ? e.target : e.relatedTarget;
                wizard = wizard.nextElementSibling;

                var val = e.target.value;
                var list = wizard.querySelectorAll('ul li');
                if( list ) {
                    for( var j=0; j < list.length; j++) {
                        if( list[j].innerHTML.toLowerCase().indexOf(val.toLowerCase()) >= 0 ) {
                            list[j].style.display = 'block';
                        } else {
                            list[j].style.display = 'none';
                        }
                    }
                }
            }

            for( var i = 0; i < suggestInput.length; i++ ) {

                // filter on input
                suggestInput[i].addEventListener('focus', filter);
                suggestInput[i].addEventListener('keyup', filter);
            }
        }

        var suggestEntries = document.querySelectorAll('fieldset .suggest.wizard .suggest_wizard ul[data-field] li');

        if( suggestEntries ) {

            for( var i = 0; i < suggestEntries.length; i++ ) {

                // use selected value
                suggestEntries[i].addEventListener('click', function(e) {
                    var entry = e.target;
                    var list = entry.parentNode;
                    var field = list.getAttribute('data-field')

                    var input = document.querySelector('input[name="'+field+'"]');
                    if( input ){
                        input.value = entry.innerHTML;
                    }
                });
            }
        }
    })();

});

var CMSBackend = {

    themePath: 'bundles/marketingsuite/img/backend/icons/',

    override: function(selector, html) {
        var element = document.querySelector(selector);
        if( element ) {
            element.outerHTML = html;
        }
    },
    append: function(selector, html) {
        var element = document.querySelector(selector);
        if( element ) {
            element.innerHTML += html;
        }
    },
    prepend: function(selector, html) {
        var element = document.querySelector(selector);
        if( element ) {
            element.innerHTML = html + element.innerHTML;
        }
    },

    toggleField: function(el, id, table) {

        el.blur();

        var image = $(el).getFirst('img'),
            active = (image.get('data-state') == 1),
            div = el.getParent('div'),
            next, icon, icond;
        // Backwards compatibility
        if( image.get('data-state') === null ) {
            console.warn('Using a field toggle without a "data-state" attribute is deprecated.');
        }
        if( image.get('data-icon') === null || image.get('data-icon-disabled') === null ) {
            console.warn('Using a field toggle without a "data-icon" or a "data-icon-disabled" attribute is deprecated.');
        }

        // Find the icon depending on the view (tree view, list view, parent view)
        next = div.getNext('div');
        if( next.hasClass('cte_type') ) {
        }

        icon = image.get('data-icon');
        icond = image.get('data-icon-disabled');

        var request = new Request.Contao({'url':window.location.href, 'followRedirects':false}).get({'tid':id, 'state':!active ? 1 : 0, 'rt':Contao.request_token});

        request.xhr.addEventListener('load', function(e) {

            if( request.status == 302 ) {
                var path = (!active ? icon : icond);

                if( path.indexOf('/') < 0 ) {
                    path = CMSBackend.themePath + path;
                }

                image.src = path;
                image.set('data-state', !active ? 1 : 0);
            }
        });

        return false;
    },

    toggleFieldReload: function(el, id, table) {

        el.blur();

        var image = $(el).getFirst('img'),
            active = (image.get('data-state') == 1);
        // Backwards compatibility
        if( image.get('data-state') === null ) {
            console.warn('Using a field toggle without a "data-state" attribute is deprecated.');
        }

        var request = new Request.Contao({'url':window.location.href, 'followRedirects':false}).get({'tid':id, 'state':!active ? 1 : 0, 'rt':Contao.request_token});

        request.xhr.addEventListener('load', function(e) {

            if( request.status == 302 ) {
                location.reload(true);
            }
        });

        return false;
    },

    SnippetPreview: class SnippetPreview {

        /**
         * Constructor
         */
        constructor(opts) {

            this.options = Object.assign({
                id: 0,
                baseUrl: null,
                urlSuffix: null,
                titleTag: null,
                titleField: null,
                titleFieldFallback: null,
                aliasField: null,
                descriptionField: null,
                descriptionFieldFallback: null,
                titleMinLength: 0,
                titleMaxLength: 0,
                descriptionMinLength: 0,
                descriptionMaxLength: 0,
                lengthLabel: null,
                labelTooShort: null,
                labelTooLong: null,
                labelOptimal: null
            }, opts);

            this.initFields();
        }


        /**
         * Initializes all the fields
         */
        initFields() {

            [
                { type: 'title', id: this.options.titleField, proxy: true },
                { type: 'title', id: this.options.titleFieldFallback },
                { type: 'alias', id: this.options.aliasField },
                { type: 'desc', id: this.options.descriptionField, proxy: true },
                { type: 'desc', id: this.options.descriptionFieldFallback },

            ].map((field)=>{

                const input = document.getElementById(field.id);

                if( !input ) {
                    return;
                }

                const widget = input.parentNode;
                input.originWidget = widget;

                if( field.proxy ) {

                    // mark widget as "snippet"
                    widget.classList.add('snippet');

                    // attach min/max lengths
                    input.setAttribute('data-snippet-minlength',(field.type=='title'?this.options.titleMinLength:this.options.descriptionMinLength));
                    input.setAttribute('data-snippet-maxlength',(field.type=='title'?this.options.titleMaxLength:this.options.descriptionMaxLength));

                    // add length label
                    if( this.options.lengthLabel ) {

                        const label = document.querySelector('label[for="'+field.id+'"]');

                        if( label ) {
                            label.innerHTML += '<span class="snippet-count" data-template="'+this.options.lengthLabel+'"></span>';
                        }
                    }

                    // add proxy element
                    const proxy = document.createElement('div');

                    proxy.className = 'tl_text';
                    proxy.contentEditable = true;
                    proxy.innerHTML = input.value;

                    proxy.originInput = input;
                    proxy.originWidget = widget;
                    proxy.options = this.options;

                    ['blur', 'keyup', 'keydown'].map((e)=>{
                        proxy.addEventListener(e, this.handleProxy);
                    });

                    this.handleProxy.bind(proxy)();
                    input.parentNode.insertBefore(proxy, input.nextSibling);
                    input.proxy = proxy;

                    this.updateCounter(input);
                }

                // update snippet preview on change
                ['change', 'input'].map((evt)=>{

                    input.addEventListener(evt,()=>{
                        this.updateCounter(input);
                        this.updatePreview(field.type);
                    });
                });

                // force tiny to update everytime
                if( field.type == 'desc' ) {

                    if( window.tinyMCE ) {

                        setTimeout(()=>{

                            const tiny = window.tinyMCE.get(field.id);

                            if( tiny ) {

                                tiny.on('Paste Change keyup input Undo Redo', ()=>{
                                    this.updatePreview(field.type);
                                    window.tinyMCE.triggerSave();
                                });
                            }

                        }, 10);
                    }
                }
            });

            // force update of title on init
            this.updatePreview('title')
        }


        /**
         * Updates the counter on the labels
         *
         * @param HTMLElement input
         */
        updateCounter( input ) {

            if( !input.originWidget ) {
                return;
            }

            const counter = input.originWidget.querySelector('.snippet-count');

            if( counter ) {

                let text = counter.getAttribute('data-template');

                const val = input.value;
                const maxLength = input.getAttribute('data-snippet-maxlength');

                text = text.replace('{1}',val.length).replace('{2}',maxLength);

                counter.textContent = text;
            }
        }


        /**
         * Handles everything related to our fake input "proxy"
         *
         * @param Event|null e
         */
        handleProxy( e ) {

            // dont do anything when navigating
            if( e && ((e.keyCode >= 35 && e.keyCode <= 40) || e.keyCode == 9) ) {
                return;
            }

            const content = (this.innerText || this.textContent);
            const minLength = this.originInput.getAttribute('data-snippet-minlength');
            const maxLength = this.originInput.getAttribute('data-snippet-maxlength');

            // init highlighting
            const markInstance = new Mark(this);
            markInstance.unmark();

            // highlight text thats too long
            if( content.length > maxLength && (!e || e.type == "blur") ) {

                markInstance.markRanges([{
                    start: maxLength,
                    length:(content.length-maxLength)
                }]);
            }

            // add tooltip depending on the text length
            if( content.length ) {

                if( !this.tips ) {

                    this.tips = new Tips.Contao(
                        this,
                        {
                            offset: {x:9, y:42},
                            hideEmpty: true,
                            showDelay: 1
                        }
                    );
                }

                if( content.length < minLength ) {

                    if( this.options.labelTooShort ) {

                        this.tips.setText(this.options.labelTooShort);

                        this.classList.remove('length_long');
                        this.classList.remove('length_optimal');
                        this.classList.add('length_short');
                    }

                } else if( content.length > maxLength ) {

                    if( this.options.labelTooLong ) {

                        this.tips.setText(this.options.labelTooLong);

                        this.classList.remove('length_short');
                        this.classList.remove('length_optimal');
                        this.classList.add('length_long');
                    }

                } else {

                    if( this.options.labelOptimal ) {

                        this.tips.setText(this.options.labelOptimal);

                        this.classList.remove('length_short');
                        this.classList.remove('length_long');
                        this.classList.add('length_optimal');
                    }
                }

                this.tips.show();

            } else {

                this.classList.remove('length_short');
                this.classList.remove('length_long');
                this.classList.remove('length_optimal');

                if( this.tips ) {
                    this.tips.setText('');
                    this.tips.hide();
                }
            }

            // hide tooltip on blur
            if( !e || e.type == 'blur' ) {

                if( this.tips ) {
                    this.tips.hide();
                }
            }

            // set new value in original input
            this.originInput.value = content;

            // dispatch input event for original input
            var event = new Event('input');
            this.originInput.dispatchEvent(event);
        }


        /**
         * Updates the snippet preview
         *
         * @param string type Type of field that has been updated
         */
        updatePreview( type ) {

            const preview = document.getElementById('snippet_preview_'+this.options.id);

            if( !preview ) {
                return;
            }

            // update title
            if( type == 'title' ) {

                let title = '';
                const titleFields = [this.options.titleField,this.options.titleFieldFallback];

                for( let i=0; i < titleFields.length; i++ ) {

                    const input = document.getElementById(titleFields[i]);

                    if( input && input.value ) {
                        title = input.value;
                        break;
                    }
                }

                title = title.trim();

                // highlight parts of the title
                if( this.options.titleTag ) {

                    let newTitle = this.options.titleTag.replace('##TITLE##','</span>'+title+'<span>');
                    let rawTitle = new DOMParser().parseFromString(newTitle, 'text/html').body.textContent;

                    if( rawTitle.length > this.options.titleMaxLength ) {

                        const ot = '<span>';
                        const ct = '</span>';

                        let x = this.options.titleMaxLength;

                        if( newTitle.indexOf(ct) !== -1 && newTitle.indexOf(ct) < x ) {
                            x += ct.length;
                        }

                        if( newTitle.indexOf(ot) !== -1 && newTitle.indexOf(ot) < x ) {
                            x += ot.length;
                        }

                        newTitle = newTitle.substr(0,x);

                        if( newTitle.indexOf(ct) === -1 ) {
                            newTitle = ct + newTitle;
                        }

                        if( newTitle.indexOf(ot) !== -1 ) {
                            newTitle = newTitle + ct;
                        }

                        newTitle += '...';
                    }

                    title = '<span>' + newTitle;

                    // hide additional explanation texts
                    const previews = document.querySelectorAll('.widget.snippet-preview');

                    if( previews && previews.length > 1 ) {

                        if( preview != previews[0] ) {
                            preview.querySelector('.explanation').hide();
                        }
                    }

                } else {

                    if( title.length > this.options.titleMaxLength ) {
                        title = title.substr(0,maxLength) + '...';
                    }
                }

                preview.querySelector('div.title').innerHTML = title;

            // update description
            } else if( type == 'desc') {

                let description;
                const descriptionFields = [this.options.descriptionField,this.options.descriptionFieldFallback];

                for( let i=0; i < descriptionFields.length; i++ ) {

                    var input = document.getElementById(descriptionFields[i]);

                    if( input && input.value ) {

                        description = input.value;

                        if( description.length > this.options.descriptionMaxLength ) {
                            description = description.substr(0,this.options.descriptionMaxLength) + '...';
                        }

                        break;
                    }
                }

                // strip any html
                description = new DOMParser().parseFromString(description, 'text/html').body.textContent;

                preview.querySelector('div.description').textContent = (description !== "undefined"?description.trim():'');

            // update alias / url
            } else if( type == 'alias') {

                const input = document.getElementById(this.options.aliasField);
                let url;

                const anchor = new Element('a', { 'href': this.options.baseUrl }),
                indexEmpty = (anchor.pathname == '/' || anchor.pathname.match(/^\/[a-z]{2}(-[A-Z]{2})?\/$/));

                if( input.value == 'index' && indexEmpty ) {
                    url = this.options.baseUrl;
                } else {
                    url = this.options.baseUrl + (input.value || this.options.id) + this.options.urlSuffix;
                }

                preview.querySelector('div.url').textContent = url.trim();
            }
        }
    }
};