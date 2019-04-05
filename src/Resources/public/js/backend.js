/* jshint ignore:start */
/*!***************************************************
* mark.js v8.11.1
* https://markjs.io/
* Copyright (c) 2014–2018, Julian Kühnel
* Released under the MIT license https://git.io/vwTVl
*****************************************************/

(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
  typeof define === 'function' && define.amd ? define(factory) :
  (global.Mark = factory());
}(this, (function () { 'use strict';

  var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  };

  var classCallCheck = function (instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  };

  var createClass = function () {
    function defineProperties(target, props) {
      for (var i = 0; i < props.length; i++) {
        var descriptor = props[i];
        descriptor.enumerable = descriptor.enumerable || false;
        descriptor.configurable = true;
        if ("value" in descriptor) descriptor.writable = true;
        Object.defineProperty(target, descriptor.key, descriptor);
      }
    }

    return function (Constructor, protoProps, staticProps) {
      if (protoProps) defineProperties(Constructor.prototype, protoProps);
      if (staticProps) defineProperties(Constructor, staticProps);
      return Constructor;
    };
  }();

  var _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  var DOMIterator = function () {
    function DOMIterator(ctx) {
      var iframes = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      var exclude = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
      var iframesTimeout = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 5000;
      classCallCheck(this, DOMIterator);

      this.ctx = ctx;
      this.iframes = iframes;
      this.exclude = exclude;
      this.iframesTimeout = iframesTimeout;
    }

    createClass(DOMIterator, [{
      key: 'getContexts',
      value: function getContexts() {
        var ctx = void 0,
            filteredCtx = [];
        if (typeof this.ctx === 'undefined' || !this.ctx) {
          ctx = [];
        } else if (NodeList.prototype.isPrototypeOf(this.ctx)) {
          ctx = Array.prototype.slice.call(this.ctx);
        } else if (Array.isArray(this.ctx)) {
          ctx = this.ctx;
        } else if (typeof this.ctx === 'string') {
          ctx = Array.prototype.slice.call(document.querySelectorAll(this.ctx));
        } else {
          ctx = [this.ctx];
        }
        ctx.forEach(function (ctx) {
          var isDescendant = filteredCtx.filter(function (contexts) {
            return contexts.contains(ctx);
          }).length > 0;
          if (filteredCtx.indexOf(ctx) === -1 && !isDescendant) {
            filteredCtx.push(ctx);
          }
        });
        return filteredCtx;
      }
    }, {
      key: 'getIframeContents',
      value: function getIframeContents(ifr, successFn) {
        var errorFn = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : function () {};

        var doc = void 0;
        try {
          var ifrWin = ifr.contentWindow;
          doc = ifrWin.document;
          if (!ifrWin || !doc) {
            throw new Error('iframe inaccessible');
          }
        } catch (e) {
          errorFn();
        }
        if (doc) {
          successFn(doc);
        }
      }
    }, {
      key: 'isIframeBlank',
      value: function isIframeBlank(ifr) {
        var bl = 'about:blank',
            src = ifr.getAttribute('src').trim(),
            href = ifr.contentWindow.location.href;
        return href === bl && src !== bl && src;
      }
    }, {
      key: 'observeIframeLoad',
      value: function observeIframeLoad(ifr, successFn, errorFn) {
        var _this = this;

        var called = false,
            tout = null;
        var listener = function listener() {
          if (called) {
            return;
          }
          called = true;
          clearTimeout(tout);
          try {
            if (!_this.isIframeBlank(ifr)) {
              ifr.removeEventListener('load', listener);
              _this.getIframeContents(ifr, successFn, errorFn);
            }
          } catch (e) {
            errorFn();
          }
        };
        ifr.addEventListener('load', listener);
        tout = setTimeout(listener, this.iframesTimeout);
      }
    }, {
      key: 'onIframeReady',
      value: function onIframeReady(ifr, successFn, errorFn) {
        try {
          if (ifr.contentWindow.document.readyState === 'complete') {
            if (this.isIframeBlank(ifr)) {
              this.observeIframeLoad(ifr, successFn, errorFn);
            } else {
              this.getIframeContents(ifr, successFn, errorFn);
            }
          } else {
            this.observeIframeLoad(ifr, successFn, errorFn);
          }
        } catch (e) {
          errorFn();
        }
      }
    }, {
      key: 'waitForIframes',
      value: function waitForIframes(ctx, done) {
        var _this2 = this;

        var eachCalled = 0;
        this.forEachIframe(ctx, function () {
          return true;
        }, function (ifr) {
          eachCalled++;
          _this2.waitForIframes(ifr.querySelector('html'), function () {
            if (! --eachCalled) {
              done();
            }
          });
        }, function (handled) {
          if (!handled) {
            done();
          }
        });
      }
    }, {
      key: 'forEachIframe',
      value: function forEachIframe(ctx, filter, each) {
        var _this3 = this;

        var end = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : function () {};

        var ifr = ctx.querySelectorAll('iframe'),
            open = ifr.length,
            handled = 0;
        ifr = Array.prototype.slice.call(ifr);
        var checkEnd = function checkEnd() {
          if (--open <= 0) {
            end(handled);
          }
        };
        if (!open) {
          checkEnd();
        }
        ifr.forEach(function (ifr) {
          if (DOMIterator.matches(ifr, _this3.exclude)) {
            checkEnd();
          } else {
            _this3.onIframeReady(ifr, function (con) {
              if (filter(ifr)) {
                handled++;
                each(con);
              }
              checkEnd();
            }, checkEnd);
          }
        });
      }
    }, {
      key: 'createIterator',
      value: function createIterator(ctx, whatToShow, filter) {
        return document.createNodeIterator(ctx, whatToShow, filter, false);
      }
    }, {
      key: 'createInstanceOnIframe',
      value: function createInstanceOnIframe(contents) {
        return new DOMIterator(contents.querySelector('html'), this.iframes);
      }
    }, {
      key: 'compareNodeIframe',
      value: function compareNodeIframe(node, prevNode, ifr) {
        var compCurr = node.compareDocumentPosition(ifr),
            prev = Node.DOCUMENT_POSITION_PRECEDING;
        if (compCurr & prev) {
          if (prevNode !== null) {
            var compPrev = prevNode.compareDocumentPosition(ifr),
                after = Node.DOCUMENT_POSITION_FOLLOWING;
            if (compPrev & after) {
              return true;
            }
          } else {
            return true;
          }
        }
        return false;
      }
    }, {
      key: 'getIteratorNode',
      value: function getIteratorNode(itr) {
        var prevNode = itr.previousNode();
        var node = void 0;
        if (prevNode === null) {
          node = itr.nextNode();
        } else {
          node = itr.nextNode() && itr.nextNode();
        }
        return {
          prevNode: prevNode,
          node: node
        };
      }
    }, {
      key: 'checkIframeFilter',
      value: function checkIframeFilter(node, prevNode, currIfr, ifr) {
        var key = false,
            handled = false;
        ifr.forEach(function (ifrDict, i) {
          if (ifrDict.val === currIfr) {
            key = i;
            handled = ifrDict.handled;
          }
        });
        if (this.compareNodeIframe(node, prevNode, currIfr)) {
          if (key === false && !handled) {
            ifr.push({
              val: currIfr,
              handled: true
            });
          } else if (key !== false && !handled) {
            ifr[key].handled = true;
          }
          return true;
        }
        if (key === false) {
          ifr.push({
            val: currIfr,
            handled: false
          });
        }
        return false;
      }
    }, {
      key: 'handleOpenIframes',
      value: function handleOpenIframes(ifr, whatToShow, eCb, fCb) {
        var _this4 = this;

        ifr.forEach(function (ifrDict) {
          if (!ifrDict.handled) {
            _this4.getIframeContents(ifrDict.val, function (con) {
              _this4.createInstanceOnIframe(con).forEachNode(whatToShow, eCb, fCb);
            });
          }
        });
      }
    }, {
      key: 'iterateThroughNodes',
      value: function iterateThroughNodes(whatToShow, ctx, eachCb, filterCb, doneCb) {
        var _this5 = this;

        var itr = this.createIterator(ctx, whatToShow, filterCb);
        var ifr = [],
            elements = [],
            node = void 0,
            prevNode = void 0,
            retrieveNodes = function retrieveNodes() {
          var _getIteratorNode = _this5.getIteratorNode(itr);

          prevNode = _getIteratorNode.prevNode;
          node = _getIteratorNode.node;

          return node;
        };
        while (retrieveNodes()) {
          if (this.iframes) {
            this.forEachIframe(ctx, function (currIfr) {
              return _this5.checkIframeFilter(node, prevNode, currIfr, ifr);
            }, function (con) {
              _this5.createInstanceOnIframe(con).forEachNode(whatToShow, function (ifrNode) {
                return elements.push(ifrNode);
              }, filterCb);
            });
          }
          elements.push(node);
        }
        elements.forEach(function (node) {
          eachCb(node);
        });
        if (this.iframes) {
          this.handleOpenIframes(ifr, whatToShow, eachCb, filterCb);
        }
        doneCb();
      }
    }, {
      key: 'forEachNode',
      value: function forEachNode(whatToShow, each, filter) {
        var _this6 = this;

        var done = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : function () {};

        var contexts = this.getContexts();
        var open = contexts.length;
        if (!open) {
          done();
        }
        contexts.forEach(function (ctx) {
          var ready = function ready() {
            _this6.iterateThroughNodes(whatToShow, ctx, each, filter, function () {
              if (--open <= 0) {
                done();
              }
            });
          };
          if (_this6.iframes) {
            _this6.waitForIframes(ctx, ready);
          } else {
            ready();
          }
        });
      }
    }], [{
      key: 'matches',
      value: function matches(element, selector) {
        var selectors = typeof selector === 'string' ? [selector] : selector,
            fn = element.matches || element.matchesSelector || element.msMatchesSelector || element.mozMatchesSelector || element.oMatchesSelector || element.webkitMatchesSelector;
        if (fn) {
          var match = false;
          selectors.every(function (sel) {
            if (fn.call(element, sel)) {
              match = true;
              return false;
            }
            return true;
          });
          return match;
        } else {
          return false;
        }
      }
    }]);
    return DOMIterator;
  }();

  var RegExpCreator = function () {
    function RegExpCreator(options) {
      classCallCheck(this, RegExpCreator);

      this.opt = _extends({}, {
        'diacritics': true,
        'synonyms': {},
        'accuracy': 'partially',
        'caseSensitive': false,
        'ignoreJoiners': false,
        'ignorePunctuation': [],
        'wildcards': 'disabled'
      }, options);
    }

    createClass(RegExpCreator, [{
      key: 'create',
      value: function create(str) {
        if (this.opt.wildcards !== 'disabled') {
          str = this.setupWildcardsRegExp(str);
        }
        str = this.escapeStr(str);
        if (Object.keys(this.opt.synonyms).length) {
          str = this.createSynonymsRegExp(str);
        }
        if (this.opt.ignoreJoiners || this.opt.ignorePunctuation.length) {
          str = this.setupIgnoreJoinersRegExp(str);
        }
        if (this.opt.diacritics) {
          str = this.createDiacriticsRegExp(str);
        }
        str = this.createMergedBlanksRegExp(str);
        if (this.opt.ignoreJoiners || this.opt.ignorePunctuation.length) {
          str = this.createJoinersRegExp(str);
        }
        if (this.opt.wildcards !== 'disabled') {
          str = this.createWildcardsRegExp(str);
        }
        str = this.createAccuracyRegExp(str);
        return new RegExp(str, 'gm' + (this.opt.caseSensitive ? '' : 'i'));
      }
    }, {
      key: 'escapeStr',
      value: function escapeStr(str) {
        return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
      }
    }, {
      key: 'createSynonymsRegExp',
      value: function createSynonymsRegExp(str) {
        var syn = this.opt.synonyms,
            sens = this.opt.caseSensitive ? '' : 'i',
            joinerPlaceholder = this.opt.ignoreJoiners || this.opt.ignorePunctuation.length ? '\0' : '';
        for (var index in syn) {
          if (syn.hasOwnProperty(index)) {
            var value = syn[index],
                k1 = this.opt.wildcards !== 'disabled' ? this.setupWildcardsRegExp(index) : this.escapeStr(index),
                k2 = this.opt.wildcards !== 'disabled' ? this.setupWildcardsRegExp(value) : this.escapeStr(value);
            if (k1 !== '' && k2 !== '') {
              str = str.replace(new RegExp('(' + this.escapeStr(k1) + '|' + this.escapeStr(k2) + ')', 'gm' + sens), joinerPlaceholder + ('(' + this.processSynonyms(k1) + '|') + (this.processSynonyms(k2) + ')') + joinerPlaceholder);
            }
          }
        }
        return str;
      }
    }, {
      key: 'processSynonyms',
      value: function processSynonyms(str) {
        if (this.opt.ignoreJoiners || this.opt.ignorePunctuation.length) {
          str = this.setupIgnoreJoinersRegExp(str);
        }
        return str;
      }
    }, {
      key: 'setupWildcardsRegExp',
      value: function setupWildcardsRegExp(str) {
        str = str.replace(/(?:\\)*\?/g, function (val) {
          return val.charAt(0) === '\\' ? '?' : '\x01';
        });
        return str.replace(/(?:\\)*\*/g, function (val) {
          return val.charAt(0) === '\\' ? '*' : '\x02';
        });
      }
    }, {
      key: 'createWildcardsRegExp',
      value: function createWildcardsRegExp(str) {
        var spaces = this.opt.wildcards === 'withSpaces';
        return str.replace(/\u0001/g, spaces ? '[\\S\\s]?' : '\\S?').replace(/\u0002/g, spaces ? '[\\S\\s]*?' : '\\S*');
      }
    }, {
      key: 'setupIgnoreJoinersRegExp',
      value: function setupIgnoreJoinersRegExp(str) {
        return str.replace(/[^(|)\\]/g, function (val, indx, original) {
          var nextChar = original.charAt(indx + 1);
          if (/[(|)\\]/.test(nextChar) || nextChar === '') {
            return val;
          } else {
            return val + '\0';
          }
        });
      }
    }, {
      key: 'createJoinersRegExp',
      value: function createJoinersRegExp(str) {
        var joiner = [];
        var ignorePunctuation = this.opt.ignorePunctuation;
        if (Array.isArray(ignorePunctuation) && ignorePunctuation.length) {
          joiner.push(this.escapeStr(ignorePunctuation.join('')));
        }
        if (this.opt.ignoreJoiners) {
          joiner.push('\\u00ad\\u200b\\u200c\\u200d');
        }
        return joiner.length ? str.split(/\u0000+/).join('[' + joiner.join('') + ']*') : str;
      }
    }, {
      key: 'createDiacriticsRegExp',
      value: function createDiacriticsRegExp(str) {
        var sens = this.opt.caseSensitive ? '' : 'i',
            dct = this.opt.caseSensitive ? ['aàáảãạăằắẳẵặâầấẩẫậäåāą', 'AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ', 'cçćč', 'CÇĆČ', 'dđď', 'DĐĎ', 'eèéẻẽẹêềếểễệëěēę', 'EÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ', 'iìíỉĩịîïī', 'IÌÍỈĨỊÎÏĪ', 'lł', 'LŁ', 'nñňń', 'NÑŇŃ', 'oòóỏõọôồốổỗộơởỡớờợöøō', 'OÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ', 'rř', 'RŘ', 'sšśșş', 'SŠŚȘŞ', 'tťțţ', 'TŤȚŢ', 'uùúủũụưừứửữựûüůū', 'UÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ', 'yýỳỷỹỵÿ', 'YÝỲỶỸỴŸ', 'zžżź', 'ZŽŻŹ'] : ['aàáảãạăằắẳẵặâầấẩẫậäåāąAÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÄÅĀĄ', 'cçćčCÇĆČ', 'dđďDĐĎ', 'eèéẻẽẹêềếểễệëěēęEÈÉẺẼẸÊỀẾỂỄỆËĚĒĘ', 'iìíỉĩịîïīIÌÍỈĨỊÎÏĪ', 'lłLŁ', 'nñňńNÑŇŃ', 'oòóỏõọôồốổỗộơởỡớờợöøōOÒÓỎÕỌÔỒỐỔỖỘƠỞỠỚỜỢÖØŌ', 'rřRŘ', 'sšśșşSŠŚȘŞ', 'tťțţTŤȚŢ', 'uùúủũụưừứửữựûüůūUÙÚỦŨỤƯỪỨỬỮỰÛÜŮŪ', 'yýỳỷỹỵÿYÝỲỶỸỴŸ', 'zžżźZŽŻŹ'];
        var handled = [];
        str.split('').forEach(function (ch) {
          dct.every(function (dct) {
            if (dct.indexOf(ch) !== -1) {
              if (handled.indexOf(dct) > -1) {
                return false;
              }
              str = str.replace(new RegExp('[' + dct + ']', 'gm' + sens), '[' + dct + ']');
              handled.push(dct);
            }
            return true;
          });
        });
        return str;
      }
    }, {
      key: 'createMergedBlanksRegExp',
      value: function createMergedBlanksRegExp(str) {
        return str.replace(/[\s]+/gmi, '[\\s]+');
      }
    }, {
      key: 'createAccuracyRegExp',
      value: function createAccuracyRegExp(str) {
        var _this = this;

        var chars = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~¡¿';
        var acc = this.opt.accuracy,
            val = typeof acc === 'string' ? acc : acc.value,
            ls = typeof acc === 'string' ? [] : acc.limiters,
            lsJoin = '';
        ls.forEach(function (limiter) {
          lsJoin += '|' + _this.escapeStr(limiter);
        });
        switch (val) {
          case 'partially':
          default:
            return '()(' + str + ')';
          case 'complementary':
            lsJoin = '\\s' + (lsJoin ? lsJoin : this.escapeStr(chars));
            return '()([^' + lsJoin + ']*' + str + '[^' + lsJoin + ']*)';
          case 'exactly':
            return '(^|\\s' + lsJoin + ')(' + str + ')(?=$|\\s' + lsJoin + ')';
        }
      }
    }]);
    return RegExpCreator;
  }();

  var Mark = function () {
    function Mark(ctx) {
      classCallCheck(this, Mark);

      this.ctx = ctx;
      this.ie = false;
      var ua = window.navigator.userAgent;
      if (ua.indexOf('MSIE') > -1 || ua.indexOf('Trident') > -1) {
        this.ie = true;
      }
    }

    createClass(Mark, [{
      key: 'log',
      value: function log(msg) {
        var level = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'debug';

        var log = this.opt.log;
        if (!this.opt.debug) {
          return;
        }
        if ((typeof log === 'undefined' ? 'undefined' : _typeof(log)) === 'object' && typeof log[level] === 'function') {
          log[level]('mark.js: ' + msg);
        }
      }
    }, {
      key: 'getSeparatedKeywords',
      value: function getSeparatedKeywords(sv) {
        var _this = this;

        var stack = [];
        sv.forEach(function (kw) {
          if (!_this.opt.separateWordSearch) {
            if (kw.trim() && stack.indexOf(kw) === -1) {
              stack.push(kw);
            }
          } else {
            kw.split(' ').forEach(function (kwSplitted) {
              if (kwSplitted.trim() && stack.indexOf(kwSplitted) === -1) {
                stack.push(kwSplitted);
              }
            });
          }
        });
        return {
          'keywords': stack.sort(function (a, b) {
            return b.length - a.length;
          }),
          'length': stack.length
        };
      }
    }, {
      key: 'isNumeric',
      value: function isNumeric(value) {
        return Number(parseFloat(value)) == value;
      }
    }, {
      key: 'checkRanges',
      value: function checkRanges(array) {
        var _this2 = this;

        if (!Array.isArray(array) || Object.prototype.toString.call(array[0]) !== '[object Object]') {
          this.log('markRanges() will only accept an array of objects');
          this.opt.noMatch(array);
          return [];
        }
        var stack = [];
        var last = 0;
        array.sort(function (a, b) {
          return a.start - b.start;
        }).forEach(function (item) {
          var _callNoMatchOnInvalid = _this2.callNoMatchOnInvalidRanges(item, last),
              start = _callNoMatchOnInvalid.start,
              end = _callNoMatchOnInvalid.end,
              valid = _callNoMatchOnInvalid.valid;

          if (valid) {
            item.start = start;
            item.length = end - start;
            stack.push(item);
            last = end;
          }
        });
        return stack;
      }
    }, {
      key: 'callNoMatchOnInvalidRanges',
      value: function callNoMatchOnInvalidRanges(range, last) {
        var start = void 0,
            end = void 0,
            valid = false;
        if (range && typeof range.start !== 'undefined') {
          start = parseInt(range.start, 10);
          end = start + parseInt(range.length, 10);
          if (this.isNumeric(range.start) && this.isNumeric(range.length) && end - last > 0 && end - start > 0) {
            valid = true;
          } else {
            this.log('Ignoring invalid or overlapping range: ' + ('' + JSON.stringify(range)));
            this.opt.noMatch(range);
          }
        } else {
          this.log('Ignoring invalid range: ' + JSON.stringify(range));
          this.opt.noMatch(range);
        }
        return {
          start: start,
          end: end,
          valid: valid
        };
      }
    }, {
      key: 'checkWhitespaceRanges',
      value: function checkWhitespaceRanges(range, originalLength, string) {
        var end = void 0,
            valid = true,
            max = string.length,
            offset = originalLength - max,
            start = parseInt(range.start, 10) - offset;
        start = start > max ? max : start;
        end = start + parseInt(range.length, 10);
        if (end > max) {
          end = max;
          this.log('End range automatically set to the max value of ' + max);
        }
        if (start < 0 || end - start < 0 || start > max || end > max) {
          valid = false;
          this.log('Invalid range: ' + JSON.stringify(range));
          this.opt.noMatch(range);
        } else if (string.substring(start, end).replace(/\s+/g, '') === '') {
          valid = false;
          this.log('Skipping whitespace only range: ' + JSON.stringify(range));
          this.opt.noMatch(range);
        }
        return {
          start: start,
          end: end,
          valid: valid
        };
      }
    }, {
      key: 'getTextNodes',
      value: function getTextNodes(cb) {
        var _this3 = this;

        var val = '',
            nodes = [];
        this.iterator.forEachNode(NodeFilter.SHOW_TEXT, function (node) {
          nodes.push({
            start: val.length,
            end: (val += node.textContent).length,
            node: node
          });
        }, function (node) {
          if (_this3.matchesExclude(node.parentNode)) {
            return NodeFilter.FILTER_REJECT;
          } else {
            return NodeFilter.FILTER_ACCEPT;
          }
        }, function () {
          cb({
            value: val,
            nodes: nodes
          });
//console.log(nodes);
        });
      }
    }, {
      key: 'matchesExclude',
      value: function matchesExclude(el) {
        return DOMIterator.matches(el, this.opt.exclude.concat(['script', 'style', 'title', 'head', 'html']));
      }
    }, {
      key: 'wrapRangeInTextNode',
      value: function wrapRangeInTextNode(node, start, end) {
        var hEl = !this.opt.element ? 'mark' : this.opt.element,
            startNode = node.splitText(start),
            ret = startNode.splitText(end - start);
        var repl = document.createElement(hEl);
        repl.setAttribute('data-markjs', 'true');
        if (this.opt.className) {
          repl.setAttribute('class', this.opt.className);
        }
        repl.textContent = startNode.textContent;
        startNode.parentNode.replaceChild(repl, startNode);
        return ret;
      }
    }, {
      key: 'wrapRangeInMappedTextNode',
      value: function wrapRangeInMappedTextNode(dict, start, end, filterCb, eachCb) {
        var _this4 = this;

        dict.nodes.every(function (n, i) {
          var sibl = dict.nodes[i + 1];
          if (typeof sibl === 'undefined' || sibl.start > start) {
            if (!filterCb(n.node)) {
              return false;
            }
            var s = start - n.start,
                e = (end > n.end ? n.end : end) - n.start,
                startStr = dict.value.substr(0, n.start),
                endStr = dict.value.substr(e + n.start);
            n.node = _this4.wrapRangeInTextNode(n.node, s, e);
            dict.value = startStr + endStr;
            dict.nodes.forEach(function (k, j) {
              if (j >= i) {
                if (dict.nodes[j].start > 0 && j !== i) {
                  dict.nodes[j].start -= e;
                }
                dict.nodes[j].end -= e;
              }
            });
            end -= e;
            eachCb(n.node.previousSibling, n.start);
            if (end > n.end) {
              start = n.end;
            } else {
              return false;
            }
          }
          return true;
        });
      }
    }, {
      key: 'wrapGroups',
      value: function wrapGroups(node, pos, len, eachCb) {
        node = this.wrapRangeInTextNode(node, pos, pos + len);
        eachCb(node.previousSibling);
        return node;
      }
    }, {
      key: 'separateGroups',
      value: function separateGroups(node, match, matchIdx, filterCb, eachCb) {
        var matchLen = match.length;
        for (var i = 1; i < matchLen; i++) {
          var pos = node.textContent.indexOf(match[i]);
          if (match[i] && pos > -1 && filterCb(match[i], node)) {
            node = this.wrapGroups(node, pos, match[i].length, eachCb);
          }
        }
        return node;
      }
    }, {
      key: 'wrapMatches',
      value: function wrapMatches(regex, ignoreGroups, filterCb, eachCb, endCb) {
        var _this5 = this;

        var matchIdx = ignoreGroups === 0 ? 0 : ignoreGroups + 1;
        this.getTextNodes(function (dict) {
          dict.nodes.forEach(function (node) {
            node = node.node;
            var match = void 0;
            while ((match = regex.exec(node.textContent)) !== null && match[matchIdx] !== '') {
              if (_this5.opt.separateGroups) {
                node = _this5.separateGroups(node, match, matchIdx, filterCb, eachCb);
              } else {
                if (!filterCb(match[matchIdx], node)) {
                  continue;
                }
                var pos = match.index;
                if (matchIdx !== 0) {
                  for (var i = 1; i < matchIdx; i++) {
                    pos += match[i].length;
                  }
                }
                node = _this5.wrapGroups(node, pos, match[matchIdx].length, eachCb);
              }
              regex.lastIndex = 0;
            }
          });
          endCb();
        });
      }
    }, {
      key: 'wrapMatchesAcrossElements',
      value: function wrapMatchesAcrossElements(regex, ignoreGroups, filterCb, eachCb, endCb) {
        var _this6 = this;

        var matchIdx = ignoreGroups === 0 ? 0 : ignoreGroups + 1;
        this.getTextNodes(function (dict) {
          var match = void 0;
          while ((match = regex.exec(dict.value)) !== null && match[matchIdx] !== '') {
            var start = match.index;
            if (matchIdx !== 0) {
              for (var i = 1; i < matchIdx; i++) {
                start += match[i].length;
              }
            }
            var end = start + match[matchIdx].length;
            _this6.wrapRangeInMappedTextNode(dict, start, end, function (node) {
              return filterCb(match[matchIdx], node);
            }, function (node, lastIndex) {
              regex.lastIndex = lastIndex;
              eachCb(node);
            });
          }
          endCb();
        });
      }
    }, {
      key: 'wrapRangeFromIndex',
      value: function wrapRangeFromIndex(ranges, filterCb, eachCb, endCb) {
        var _this7 = this;

        this.getTextNodes(function (dict) {
          var originalLength = dict.value.length;
          ranges.forEach(function (range, counter) {
            var _checkWhitespaceRange = _this7.checkWhitespaceRanges(range, originalLength, dict.value),
                start = _checkWhitespaceRange.start,
                end = _checkWhitespaceRange.end,
                valid = _checkWhitespaceRange.valid;

            if (valid) {
              _this7.wrapRangeInMappedTextNode(dict, start, end, function (node) {
                return filterCb(node, range, dict.value.substring(start, end), counter);
              }, function (node) {
                eachCb(node, range);
              });
            }
          });
          endCb();
        });
      }
    }, {
      key: 'unwrapMatches',
      value: function unwrapMatches(node) {
        var parent = node.parentNode;
        var docFrag = document.createDocumentFragment();
        while (node.firstChild) {
          docFrag.appendChild(node.removeChild(node.firstChild));
        }
        parent.replaceChild(docFrag, node);
        if (!this.ie) {
          parent.normalize();
        } else {
          this.normalizeTextNode(parent);
        }
      }
    }, {
      key: 'normalizeTextNode',
      value: function normalizeTextNode(node) {
        if (!node) {
          return;
        }
        if (node.nodeType === 3) {
          while (node.nextSibling && node.nextSibling.nodeType === 3) {
            node.nodeValue += node.nextSibling.nodeValue;
            node.parentNode.removeChild(node.nextSibling);
          }
        } else {
          this.normalizeTextNode(node.firstChild);
        }
        this.normalizeTextNode(node.nextSibling);
      }
    }, {
      key: 'markRegExp',
      value: function markRegExp(regexp, opt) {
        var _this8 = this;

        this.opt = opt;
        this.log('Searching with expression "' + regexp + '"');
        var totalMatches = 0,
            fn = 'wrapMatches';
        var eachCb = function eachCb(element) {
          totalMatches++;
          _this8.opt.each(element);
        };
        if (this.opt.acrossElements) {
          fn = 'wrapMatchesAcrossElements';
        }
        this[fn](regexp, this.opt.ignoreGroups, function (match, node) {
          return _this8.opt.filter(node, match, totalMatches);
        }, eachCb, function () {
          if (totalMatches === 0) {
            _this8.opt.noMatch(regexp);
          }
          _this8.opt.done(totalMatches);
        });
      }
    }, {
      key: 'mark',
      value: function mark(sv, opt) {
        var _this9 = this;

        this.opt = opt;
        var totalMatches = 0,
            fn = 'wrapMatches';

        var _getSeparatedKeywords = this.getSeparatedKeywords(typeof sv === 'string' ? [sv] : sv),
            kwArr = _getSeparatedKeywords.keywords,
            kwArrLen = _getSeparatedKeywords.length,
            handler = function handler(kw) {
          var regex = new RegExpCreator(_this9.opt).create(kw);
          var matches = 0;
          _this9.log('Searching with expression "' + regex + '"');
          _this9[fn](regex, 1, function (term, node) {
            return _this9.opt.filter(node, kw, totalMatches, matches);
          }, function (element) {
            matches++;
            totalMatches++;
            _this9.opt.each(element);
          }, function () {
            if (matches === 0) {
              _this9.opt.noMatch(kw);
            }
            if (kwArr[kwArrLen - 1] === kw) {
              _this9.opt.done(totalMatches);
            } else {
              handler(kwArr[kwArr.indexOf(kw) + 1]);
            }
          });
        };

        if (this.opt.acrossElements) {
          fn = 'wrapMatchesAcrossElements';
        }
        if (kwArrLen === 0) {
          this.opt.done(totalMatches);
        } else {
          handler(kwArr[0]);
        }
      }
    }, {
      key: 'markRanges',
      value: function markRanges(rawRanges, opt) {
        var _this10 = this;

        this.opt = opt;
        var totalMatches = 0,
            ranges = this.checkRanges(rawRanges);
        if (ranges && ranges.length) {
          this.log('Starting to mark with the following ranges: ' + JSON.stringify(ranges));
          this.wrapRangeFromIndex(ranges, function (node, range, match, counter) {
            return _this10.opt.filter(node, range, match, counter);
          }, function (element, range) {
            totalMatches++;
            _this10.opt.each(element, range);
          }, function () {
            _this10.opt.done(totalMatches);
          });
        } else {
          this.opt.done(totalMatches);
        }
      }
    }, {
      key: 'unmark',
      value: function unmark(opt) {
        var _this11 = this;

        this.opt = opt;
        var sel = this.opt.element ? this.opt.element : '*';
        sel += '[data-markjs]';
        if (this.opt.className) {
          sel += '.' + this.opt.className;
        }
        this.log('Removal selector "' + sel + '"');
        this.iterator.forEachNode(NodeFilter.SHOW_ELEMENT, function (node) {
          _this11.unwrapMatches(node);
        }, function (node) {
          var matchesSel = DOMIterator.matches(node, sel),
              matchesExclude = _this11.matchesExclude(node);
          if (!matchesSel || matchesExclude) {
            return NodeFilter.FILTER_REJECT;
          } else {
            return NodeFilter.FILTER_ACCEPT;
          }
        }, this.opt.done);
      }
    }, {
      key: 'opt',
      set: function set$$1(val) {
        this._opt = _extends({}, {
          'element': '',
          'className': '',
          'exclude': [],
          'iframes': false,
          'iframesTimeout': 5000,
          'separateWordSearch': true,
          'acrossElements': false,
          'ignoreGroups': 0,
          'each': function each() {},
          'noMatch': function noMatch() {},
          'filter': function filter() {
            return true;
          },
          'done': function done() {},
          'debug': false,
          'log': window.console
        }, val);
      },
      get: function get$$1() {
        return this._opt;
      }
    }, {
      key: 'iterator',
      get: function get$$1() {
        return new DOMIterator(this.ctx, this.opt.iframes, this.opt.exclude, this.opt.iframesTimeout);
      }
    }]);
    return Mark;
  }();

  return Mark;

})));
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

    // snippet preview widget
    (function(){

        var preview = document.querySelector('.widget.snippet-preview');

        if( !preview ) {
            return;
        }

        var previewTitle = preview.querySelector('.title');
        var previewDescription = preview.querySelector('.description');

        var updateCounter = function( elem ) {

            var input = document.querySelector(elem);
            var widget = input.parentNode;
            var counter = widget.querySelector('.snippet-count');

            if( counter ) {
                var text = counter.getAttribute('data-template');

                var val = input.value;
                var maxLength = input.getAttribute('data-snippet-length');

                text = text.replace('{1}',val.length).replace('{2}',maxLength);

                counter.innerHTML = text;
            }
        };

        ['input[name="pageTitle"]', 'textarea[name="description"]'].map(function(elem) {

            if( !document.querySelector(elem) ) {
                return;
            }

            // initialize counter
            updateCounter( elem );

            // initialize fake proxy input fields
            var proxy = document.querySelector(elem).parentNode.querySelector('div[contenteditable]');

            if( proxy ) {

                var handleProxy = function( e ) {

                    // dont do anything when navigating
                    if( e && ((e.keyCode >= 35 && e.keyCode <= 39) || e.keyCode == 9) ) {
                        return;
                    }

                    var content = (this.innerText || this.textContent);
                    var input = this.parentNode.querySelector('input,textarea');
                    var maxLength = parseInt(input.getAttribute('data-snippet-length'),10);

                    // remove highlighting
                    var markInstance = new Mark(this);
                    markInstance.unmark();

                    // highlight text thats too long
                    if( content.length > maxLength && (!e || e.type == "blur") ) {

                        markInstance.markRanges([{
                            start: maxLength,
                            length:(content.length-maxLength)
                        }]);
                    }

                    // set new value in original input
                    input.value = content;

                    // dispatch change event for original input
                    var event = new Event('change');
                    input.dispatchEvent(event);
                };

                ['blur', 'keyup'].map(function(e) {
                    proxy.addEventListener(e, handleProxy);
                });

                handleProxy.bind(proxy)();
            }

            // update snippet preview on change
            ['change', 'keyup'].map(function(e) {

                document.querySelector(elem).addEventListener(e, function(){

                    var isDescription = this.tagName == "TEXTAREA";
                    var maxLength = this.getAttribute('data-snippet-length');
                    var val = this.value;

                    if( val.length > maxLength ) {
                        val = val.substr(0,maxLength);
                    }

                    if( isDescription ) {
                        previewDescription.innerHTML = val;
                        updateCounter(elem);
                    } else {
                        previewTitle.innerHTML = val;
                        updateCounter(elem);
                    }
                });
            });
        });
    })();


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
};
