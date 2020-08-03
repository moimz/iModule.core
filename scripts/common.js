/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * iModule 에서 사용하는 각종 자바스크립트 함수 라이브러리
 * jQuery 기능을 사용하는 확장기능은 jquery.extend.js 파일에 정의되어 있다.
 * 
 * @file /scripts/common.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 12. 11.
 */
var iModule = {
	isMobile:navigator.userAgent.match(/(iPhone|iPod|iPad|Android)/) !== null,
	/**
	 * 아이모듈 DOM 객체를 초기화한다.
	 */
	init:function(container) {
		var $container = container ? $(document) : container;
		
		/**
		 * 툴틉 객체 초기화
		 */
		$("*[data-tooltip]",$container).inits();
		
		/**
		 * input 객체 초기화
		 */
		$("div[data-role=input]",$container).inits();
		
		/**
		 * 태그입력기 객체 초기화
		 */
		$("div[data-role=tags]",$container).inits();
		 
		/**
		 * form 상태 초기화
		 */
		$("form",$container).status("default");
		
		/**
		 * 자동저장 form 처리
		 */
		//$("form[data-autosave]").autosave();
		
		/**
		 * tab 객체 초기화
		 */
		$("*[data-role=tab]",$container).inits();
		
		/**
		 * 테이블 객체 초기화
		 */
		$("ul[data-role=table]",$container).inits();
		
		/**
		 * 시간출력
		 */
		$("time[data-time][data-moment]",$container).each(function() {
			if ($(this).attr("data-moment") == "fromNow") {
				$(this).html(moment.unix($(this).attr("data-time")).locale($("html").attr("lang")).fromNow());
			} else if ($(this).attr("data-moment") == "toNow") {
				$(this).html(moment.unix($(this).attr("data-time")).locale($("html").attr("lang")).toNow());
			} else {
				$(this).html(moment.unix($(this).attr("data-time")).locale($("html").attr("lang")).format($(this).attr("data-moment")));
			}
		});
		
		/**
		 * 비활성링크처리
		 */
		$("a[disabled]",$container).on("click",function(e) {
			e.preventDefault();
		});
		
		/**
		 * 링크타켓처리
		 */
		$("a",$container).on("click",function(e) {
			if (!$(this).attr("href")) return;
			if ($(this).attr("href").indexOf("#IM_") == -1) return;
			var temp = $(this).attr("href").split("#IM_")
			var link = temp[0];
			var target = temp[1];
			
			if (target == "blank") {
				window.open(link);
			} else {
				location.href = link;
			}
			e.preventDefault();
		});
		
		/**
		 * 페이지이동 네비게이션 처리
		 */
		$("div[data-role=pagination] > ul > li.disabled > a",$container).on("click",function(e) {
			e.preventDefault();
		});
		
		$(document).triggerHandler("init");
	},
	/**
	 * 자바스크립트에서 모듈 언어셋이 필요할 경우, iModule 코어에 의하여 언어셋파일을 로드한다.
	 *
	 * @param string module 모듈명
	 * @param object lang 설정된 언어셋
	 * @param object oLang 모듈 기본언어셋
	 */
	addLanguage:function(type,target,lang,oLang) {
		if (type == "core") {
			iModule._LANG = lang;
			iModule._OLANG = oLang;
		} else {
			if (type == "module") {
				if (window[target] === undefined) window[target] = {};
				var targetObject = window[target];
			} else if (type == "plugin") {
				if (window.Plugin.plugins[target] === undefined) window.Plugin.plugins[target] = {};
				var targetObject = window.Plugin.plugins[target];
			}
			
			targetObject._LANG = lang;
			targetObject._OLANG = oLang;
			
			targetObject.addText = function(code,text) {
				var temp = code.split("/");
				
				var string = this._LANG;
				for (var i=0, loop=temp.length-1;i<loop;i++) {
					if (string[temp[i]]) {
						string = string[temp[i]];
					} else {
						string[temp[i]] = {};
						string = string[temp[i]];
					}
				}
				
				string[temp.pop()] = text;
			};
			
			targetObject.getText = function(code,replacement) {
				var replacement = replacement ? replacement : null;
				var returnString = null;
				var temp = code.split("/");
				
				var string = this._LANG;
				for (var i=0, loop=temp.length;i<loop;i++) {
					if (string[temp[i]]) {
						string = string[temp[i]];
					} else {
						string = null;
						break;
					}
				}
				
				if (string != null) {
					returnString = string;
				} else if (this._OLANG != null) {
					var string = this._OLANG;
					for (var i=0, loop=temp.length;i<loop;i++) {
						if (string[temp[i]]) {
							string = string[temp[i]];
						} else {
							string = null;
							break;
						}
					}
					
					if (string != null) returnString = string;
				}
				
				/**
				 * 언어셋 텍스트가 없는경우 iModule 코어에서 불러온다.
				 */
				if (returnString != null) return returnString;
				else if ($.inArray(temp[0],["text","button","action"]) > -1) return iModule.getText(code,replacement);
				else return replacement == null ? code : replacement;
			};
			
			targetObject.getErrorText = function(code) {
				var message = this.getText("error/"+code,code);
				if (message === code && typeof Admin == "object") message = Admin.getText("error/"+code,code);
				if (message === code) message = iModule.getErrorText(code);
				
				return message;
			};
		}
	},
	/**
	 * iModule 코어의 언어셋을 가져온다.
	 *
	 * @param string code
	 * @param string replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string language 실제 언어셋 텍스트
	 */
	getText:function(code,replacement) {
		var replacement = replacement ? replacement : null;
		var temp = code.split("/");
		
		var string = this._LANG;
		for (var i=0, loop=temp.length;i<loop;i++) {
			if (string[temp[i]]) {
				string = string[temp[i]];
			} else {
				string = null;
				break;
			}
		}
		
		if (string != null) {
			return string;
		} else if (this._OLANG != null) {
			var string = this._OLANG;
			for (var i=0, loop=temp.length;i<loop;i++) {
				if (string[temp[i]]) {
					string = string[temp[i]];
				} else {
					return replacement == null ? code : replacement;
				}
			}
		}
		
		return replacement == null ? code : replacement;
	},
	/**
	 * iModule 코어의 에러메세지 가져온다.
	 *
	 * @param string code 에러코드
	 * @return string message 에러메세지
	 */
	getErrorText:function(code) {
		var message = this.getText("error/"+code,code);
		if (message === code) message = iModule.getText("error/UNKNOWN")+" ("+code+")";
		
		return message;
	},
	/**
	 * 파일사이즈를 KB, MB, GB 단위로 변환한다.
	 *
	 * @param int fileSize 파일사이즈 (byte단위)
	 * @param boolean isKiB 1000 으로 나눈 값이 아닌 1024로 나눈 KiB 단위사용 여부
	 */
	getFileSize:function(fileSize,isKiB) {
		var isKiB = isKiB === true;
		var depthSize = isKiB == true ? 1024 : 1000;
		
		fileSize = parseInt(fileSize);
		return depthSize > fileSize ? fileSize+"B" : depthSize * depthSize > fileSize ? (fileSize/depthSize).toFixed(2)+(isKiB == true ? "KiB" : "KB") : depthSize * depthSize * depthSize > fileSize ? (fileSize/depthSize/depthSize).toFixed(2)+(isKiB == true ? "MiB" : "MB") : (fileSize/depthSize/depthSize/depthSize).toFixed(2)+(isKiB == true ? "GiB" : "GB");
	},
	getNumberFormat:function(number,decimals,dec_point,thousands_sep) {
		number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
		var n = !isFinite(+number) ? 0 : +number,
			prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
			sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
			dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
			s = '',
			toFixedFix = function (n, prec) {
				var k = Math.pow(10, prec);
				return '' + Math.round(n * k) / k;
			};
		s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
		}
		if ((s[1] || '').length < prec) {
			s[1] = s[1] || '';
			s[1] += new Array(prec - s[1].length + 1).join('0');
		}
		return s.join(dec);
	},
	/**
	 * 브라우져의 앞/뒤로 가기 버튼 클릭시 캐시사용을 막는다.
	 */
	preventCache:function() {
		window.onpageshow = function(event) {
			if (event.persisted) {
				location.reload();
			}
		};
	},
	/**
	 * 브라우져의 세션스토리지 데이터를 저장하거나 가져온다.
	 */
	session:function(name,value) {
		if (window.sessionStorage === undefined) return;
		
		name = "IM_" + name;
		
		if (value === undefined) {
			if (window.sessionStorage[name] !== undefined) {
				return JSON.parse(window.sessionStorage[name]);
			} else {
				return null;
			}
		} else {
			try {
				if (value === null) delete window.sessionStorage[name];
				else window.sessionStorage[name] = JSON.stringify(value);
				return true;
			} catch (e) {
				return false;
			}
		}
	},
	/**
	 * 브라우져의 로컬스토리지 데이터를 저장하거나 가져온다.
	 */
	storage:function(name,value) {
		if (window.localStorage === undefined) return;
		
		name = "IM_" + name;
		
		if (value === undefined) {
			if (window.localStorage[name] !== undefined) {
				return JSON.parse(window.localStorage[name]);
			} else {
				return null;
			}
		} else {
			try {
				if (value === null) delete window.localStorage[name];
				else window.localStorage[name] = JSON.stringify(value);
				return true;
			} catch (e) {
				return false;
			}
		}
	},
	/**
	 * 알림메세지 관련
	 */
	alert:{
		idx:0,
		/**
		 * 알림메세지를 출력한다.
		 *
		 * @param string type 알림메세지 종류 (error, success, info)
		 * @param string message 알림메세지
		 * @param int time 알림메세지가 사라지기까지 시간 (기본 5)
		 */
		show:function(type,message,timer) {
			if ($("body > div[data-role=alert]").length == 0) {
				alert(message);
			} else {
				var timer = timer ? timer : 5;
				var idx = iModule.alert.idx++;
				var $item = $("<div>").attr("data-idx",idx).addClass(type).addClass("message").css("display","none");
				$item.html(message);
				var $close = $("<div>").addClass("close").append($("<i>").addClass("fa fa-times-circle"));
				$close.on("click",function() {
					var idx = $(this).parent().attr("data-idx");
					iModule.alert.close(idx);
				});
				$item.append($close);
				
				$("body > div[data-role=alert]").append($item);
				iModule.alert.slideDown(idx);
				setTimeout(iModule.alert.close,timer * 1000,idx);
			}
		},
		/**
		 * 새로 추가된 알림메세지를 위에서 아래로 내려오게 한다.
		 *
		 * @param int idx 알림메세지 고유번호
		 */
		slideDown:function(idx) {
			$("body > div[data-role=alert] > div[data-idx="+idx+"]").slideDown();
		},
		/**
		 * 알림메세지를 닫는다.
		 *
		 * @param int idx 알림메세지 고유번호
		 */
		close:function(idx) {
			var $item = $("body > div[data-role=alert] > div[data-idx="+idx+"]");
			if ($item.length > 0) {
				$item.find(".close").css("visibility","hidden");
				$item.width($("body > div[data-role=alert]Item-"+idx).width());
				$item.animate({marginLeft:-$item.outerWidth(true),opacity:0},"fast",function() {
					$(this).remove();
				});
			}
		},
		/**
		 * 프로그래스바 알림메세지를 출력한다.
		 *
		 * @param string id 프로그래스바 고유값 (이 값을 이용하여 프로그래스바 진행율을 업데이트 할 수 있다.)
		 * @param int loaded 프로그래스바 로딩값
		 * @param int total 프로그래바 전체값
		 */
		progress:function(id,loaded,total) {
			if ($("body > div[data-role=alert]").length == 0) return;
			
			if (total > 0 && loaded < total) {
				if ($("body > div[data-role=alert] > div[data-progress="+id+"]").length == 0) {
					$("body > div[data-role=alert]").append($("<div>").addClass("progress").attr("data-progress",id).append($("<span>")));
				}
				$("body > div[data-role=alert] > div[data-progress="+id+"] > span").css("width",(loaded/total*100)+"%");
			} else {
				if ($("body > div[data-role=alert] > div[data-progress="+id+"]").length == 0) return;
				
				$("body > div[data-role=alert] > div[data-progress="+id+"] > span").css("width","100%");
				$("body > div[data-role=alert] > div[data-progress="+id+"]").fadeOut(3000,function() {
					$(this).remove();
				});
			}
		}
	},
	/**
	 * 아이모듈 전체 컨텍스트를 활성화한다.
	 */
	enable:function() {
		var $disabled = $("body > div[data-role=disabled]");
		if ($disabled.length == 1) {
			if ($("body").hasClass("mobile") == true) {
				$("body").removeClass("disabled");
				$("body").scrollTop($("body").data("position"));
			}
			$("body").removeClass("disabled").removeClass("mobile");
			$disabled.remove();
		}
	},
	/**
	 * 아이모듈 전체 컨텍스트를 비활성화한다.
	 *
	 * @param boolean is_loading 로딩 인디케이터를 보일지 여부 (기본값 false)
	 */
	disable:function(is_loading) {
		var $disabled = $("body > div[data-role=disabled]");
		var $box = $("body > div[data-role=disabled] > div[data-role=box]");
		$box.removeClass("loading modal");
		
		if ($disabled.length == 0) {
			var $disabled = $("<div>").attr("data-role","disabled");
			$("body").append($disabled);
			
			var position = $("body").scrollTop();
			$("body").addClass("disabled");
			
			var $box = $("<div>").attr("data-role","box");
			$disabled.data("position",position);
			$disabled.append($box);
		}
		
		$box.empty();
		if (is_loading == true) {
			$disabled.addClass("loading");
			$box.append($("<i>").addClass("mi mi-loading"));
		} else {
			$disabled.removeClass("loading");
		}
	},
	/**
	 * 툴팁관련
	 */
	tooltip:{
		show:function($object,html,width) {
			iModule.tooltip.hide();
			
			var $tooltip = $("<div>").attr("data-role","tooltip");
			var html = html ? html : $object.attr("data-tooltip");
			var maxWidth = width ? width : $object.attr("data-tooltip-width");
			
			if (maxWidth) {
				$tooltip.css("maxWidth",maxWidth+"px");
			}
			$tooltip.css("opacity",0).append($("<div>").html(html));
			$("div[data-role=wrapper]").append($tooltip);
			
			if ($(window).height() > $object.offset().top + $object.outerHeight() + $tooltip.outerHeight() + 10) {
				$tooltip.addClass("top").css("top",$object.offset().top + $object.outerHeight());
			} else {
				$tooltip.addClass("bottom").css("top",$object.offset().top - $tooltip.outerHeight());
			}
			$tooltip.css("left",$object.offset().left).css("opacity",1);
		},
		hide:function(timer) {
			if (timer) {
				setTimeout(iModule.tooltip.hide,timer * 1000);
				return;
			}
			
			$("div[data-role=wrapper] > div[data-role=tooltip]").remove();
		}
	},
	/**
	 * 모달창 관련
	 */
	modal:{
		init:function() {
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			var $modal = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal]");
			if ($disabled.length == 0 || $box.length == 0 || $modal.length == 0) return;
			
			$disabled.addClass("modal");
			
			if ($modal.data("isInit") == true) {
				iModule.modal.set();
				return;
			}
			
			iModule.init($modal);
			iModule.modal.set();
			
			if ($("div[data-role=input]",$modal).length > 0) {
				var $input = $("div[data-role=input]",$modal).first();
				if ($input.attr("data-type") == "select") {
					$("button",$input).focus();
				} else if ($input.attr("data-type") == "textarea") {
					$("textarea",$input).focus();
				} else {
					$("input",$input).focus();
				}
			} else if ($("button[type=submit]",$modal).length > 0) {
				$("button[type=submit]",$modal).last().focus();
			}
			
			$("button[data-action]",$modal).on("click",function() {
				if ($(this).attr("data-action") == "close") {
					iModule.modal.close();
				}
			});
			
			$modal.on("click",function(e) {
				$("div[data-role=input]").removeClass("extend");
				$("div[data-role=picker]").remove();
				e.stopPropagation();
			});
			
			if ($modal.attr("data-closable") == "TRUE") {
				$box.on("click",function() {
					iModule.modal.close();
				});
			}
			
			$modal.data("isInit",true);
		},
		/**
		 * 모달창의 크기와 위치를 정의한다.
		 */
		set:function() {
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			var $modal = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal]");
			var $form = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal] > form");
			if ($disabled.length == 0 || $box.length == 0 || $modal.length == 0) return;
			
			var width = parseInt($modal.attr("data-width"));
			var height = parseInt($modal.attr("data-height"));
			var maxWidth = parseInt($modal.attr("data-max-width"));
			var maxHeight = parseInt($modal.attr("data-max-height"));
			var is_fullsize = $modal.attr("data-fullsize") == "TRUE";
			
			var $content = $("form > main",$modal).children(":visible").eq(0);
			if ($content.attr("data-fullsize") == "TRUE") {
				$("form > main",$modal).attr("data-fullsize","TRUE");
			}
			
			$modal.css("minWidth","").css("width","").css("minHeight","").css("height","");
			
			if ($modal.width() < maxWidth) {
				if (maxWidth < $(window).width()) {
					$modal.css("minWidth",maxWidth+"px").css("width",maxWidth+"px");
					$form.css("minWidth",maxWidth+"px").css("width",maxWidth+"px");
				} else {
					$modal.css("minWidth",($(window).width() - 20)+"px").css("width",($(window).width() - 20)+"px");
					$form.css("minWidth",($(window).width() - 20)+"px").css("width",($(window).width() - 20)+"px");
				}
			} else {
				if (width > 0) $modal.css("minWidth",maxWidth+"px").css("width",maxWidth+"px");
			}
			
			if ($modal.height() < maxHeight) {
				if (maxHeight < $(window).height()) {
					$modal.css("minHeight",maxHeight+"px").css("height",maxHeight+"px");
					$form.css("minHeight",maxHeight+"px").css("height",maxHeight+"px");
				} else {
					$modal.css("minHeight",($(window).height() - 20)+"px").css("height",($(window).height() - 20)+"px");
					$form.css("minHeight",($(window).height() - 20)+"px").css("height",($(window).height() - 20)+"px");
				}
			} else {
				if (height > 0) $modal.css("minHeight",maxHeight+"px").css("height",maxHeight+"px");
			}
			
			if (is_fullsize == true && (iModule.isMobile == true || ($modal.width() < width && $modal.height() < height))) {
				$modal.css("minWidth","100%").css("width","100%").css("minHeight","100%").css("height","100%");
				$("body").attr("data-scroll",$("body").scrollTop());
			} else {
				if ($(window).height() > $modal.height()) {
					$modal.css("margin",(($(window).height() - $modal.height()) / 2)+"px auto");
				} else {
					$modal.css("margin","10px auto");
				}
			}
		},
		/**
		 * 모달창을 서버로 부터 가져온다.
		 *
		 * @param string url 모달창을 가져올 주소
		 * @param object data 전달할 데이터
		 */
		get:function(url,data,callback,error) {
			iModule.disable(true);
			var data = data && typeof data == "object" ? data : {};
			
			$.send(url,data,function(result) {
				if (result.success == true) {
					if (result.modalHtml) {
						iModule.modal.showHtml(result.modalHtml,callback);
					} else if (result.open) {
						window.open(result.open);
						iModule.enable();
					} else {
						iModule.enable();
					}
				} else {
					iModule.enable();
					if (typeof error == "function") {
						return error(result);
					}
				}
			});
		},
		show:function(title,content,options,buttons,callback) {
			var options = options ? options : {};
			options.width = options.width ? options.width : 0;
			options.height = options.height ? options.height : 0;
			options.maxWidth = options.maxWidth ? options.maxWidth : 0;
			options.maxHeight = options.maxHeight ? options.maxHeight : 0;
			options.is_fullsize =  options.is_fullsize === true;
			options.closable =  options.closable !== false;
			
			var $modal = $("<div>").attr("data-role","modal");
			$modal.data("modal","custom");
			$modal.attr("data-closable",options.closable == true ? "TRUE" : "FALSE");
			$modal.attr("data-fullsize",options.fullsize == true ? "TRUE" : "FALSE");
			$modal.attr("data-width",options.width);
			$modal.attr("data-height",options.height);
			$modal.attr("data-max-width",options.maxWidth);
			$modal.attr("data-max-height",options.maxHeight);
			
			var $form = $("<form>").attr("id","iModuleModalForm");
			var $header = $("<div>").attr("data-role","header");
			var $title = $("<h1>").html(title);
			$header.append($title);
			
			var $close = $("<button>").attr("type","button").attr("data-action","close").html('<i class="mi mi-close"></i>');
			$header.append($close);
			
			$form.append($header);
			
			var $main = $("<div>").attr("data-role","context").html(content);
			$form.append($main);
			
			var $footer = $("<div>").attr("data-role","footer");
			
			if (typeof buttons == "function") {
				var $button = $("<button>").attr("type","button").attr("data-action","close").html(iModule.getText("button/close"));
				$footer.append($("<div>").append($button));
				var $submit = $("<button>").attr("type","submit").html(iModule.getText("button/confirm"));
				$footer.append($("<div>").append($submit));
				
				var callback = buttons;
			} else if (typeof buttons == "object") {
				for (var i=0, loop=buttons.length;i<loop;i++) {
					var $button = $("<button>").attr("type","button").html(buttons[i].text);
					
					if (typeof buttons[i].click == "function") {
						$button.on("click",buttons[i].click);
					} else if (typeof buttons[i].click == "string") {
						if (buttons[i].click == "submit") $button.attr("type","submit");
						else $button.attr("data-action",buttons[i].click);
					}
					
					if (buttons[i].class) $button.addClass(buttons[i].class);
					$footer.append($("<div>").append($button));
				}
			}
			
			$form.append($footer);
			$modal.append($form);
			iModule.modal.showHtml($modal,callback);
		},
		error:function(message,callback) {
			var $modal = $("<div>").attr("data-role","modal");
			$modal.data("modal","error");
			$modal.data("message",message);
			$modal.attr("data-closable","TRUE");
			$modal.attr("data-fullsize","FALSE");
			$modal.attr("data-width",400);
			$modal.attr("data-height",0);
			$modal.attr("data-max-width",400);
			$modal.attr("data-max-height",0);
			
			var $form = $("<form>").attr("id","iModuleModalForm");
			var $header = $("<div>").attr("data-role","header");
			var $title = $("<h1>").html(iModule.getText("text/error"));
			$header.append($title);
			
			var $close = $("<button>").attr("type","button").attr("data-action","close").html('<i class="mi mi-close"></i>');
			$header.append($close);
			
			$form.append($header);
			
			var $main = $("<div>").attr("data-role","context").html($("<div>").attr("data-role","message").html(message));
			$form.append($main);
			
			var $footer = $("<div>").attr("data-role","footer");
			
			var $button = $("<button>").attr("type","submit").html(iModule.getText("button/confirm"));
			$footer.append($("<div>").append($button));
			
			$form.append($footer);
			
			$modal.append($form);
			
			if (typeof callback == "string") {
				var url = callback;
				callback = function($modal,$form) { $form.on("submit",function() { location.href = url; return false; }); return false; };
			} else if (typeof callback == "function") {
				var submitter = callback;
				callback = function($modal,$form) { $form.on("submit",function() { submitter(); return false; }); return false; };
			}
			
			iModule.modal.showHtml($modal,callback);
		},
		alert:function(title,message,callback,closable) {
			var $modal = $("<div>").attr("data-role","modal");
			$modal.data("modal","error");
			$modal.data("message",message);
			$modal.attr("data-closable",closable === false ? "FALSE" : "TRUE");
			$modal.attr("data-fullsize","FALSE");
			$modal.attr("data-width",400);
			$modal.attr("data-height",0);
			$modal.attr("data-max-width",400);
			$modal.attr("data-max-height",0);
			
			var $form = $("<form>").attr("id","iModuleModalForm");
			var $header = $("<div>").attr("data-role","header");
			var $title = $("<h1>").html(title);
			$header.append($title);
			
			var $close = $("<button>").attr("type","button").attr("data-action","close").html('<i class="mi mi-close"></i>');
			$header.append($close);
			
			$form.append($header);
			
			var $main = $("<div>").attr("data-role","context").html($("<div>").attr("data-role","message").html(message));
			$form.append($main);
			
			var $footer = $("<div>").attr("data-role","footer");
			
			var $button = $("<button>").attr("type","submit").html(iModule.getText("button/confirm"));
			$footer.append($("<div>").append($button));
			
			$form.append($footer);
			
			$modal.append($form);
			
			if (typeof callback == "string") {
				var url = callback;
				callback = function($modal,$form) { $form.on("submit",function() { location.href = url; return false; }); return false; };
			} else if (typeof callback == "function") {
				var submitter = callback;
				callback = function($modal,$form) { $form.on("submit",function() { submitter(); return false; }); return false; };
			}
			
			iModule.modal.showHtml($modal,callback);
		},
		confirm:function(title,message,callback) {
			var $modal = $("<div>").attr("data-role","modal");
			$modal.data("modal","confirm");
			$modal.data("message",message);
			$modal.attr("data-closable","TRUE");
			$modal.attr("data-fullsize","FALSE");
			$modal.attr("data-width",400);
			$modal.attr("data-height",0);
			$modal.attr("data-max-width",400);
			$modal.attr("data-max-height",0);
			
			var $form = $("<form>").attr("id","iModuleModalForm");
			var $header = $("<div>").attr("data-role","header");
			var $title = $("<h1>").html(title);
			$header.append($title);
			
			$form.append($header);
			
			var $main = $("<div>").attr("data-role","context").html($("<div>").attr("data-role","message").html(message));
			$form.append($main);
			
			var $footer = $("<div>").attr("data-role","footer");
			
			var $button = $("<button>").attr("type","button").attr("data-action","ok").addClass("submit").html(iModule.getText("button/confirm"));
			$footer.append($("<div>").append($button));
			
			var $cancel = $("<button>").attr("type","button").attr("data-action","cancel").html(iModule.getText("button/cancel"));
			$footer.append($("<div>").append($cancel));
			
			$form.append($footer);
			
			$modal.append($form);
			
			$("button",$footer).on("click",function() {
				callback($(this));
			});
			
			iModule.modal.showHtml($modal);
		},
		showHtml:function(html,callback) {
			iModule.disable();
			
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("div[data-role=disabled] > div[data-role=box]");
			$box.empty();
			
			var $modal = typeof html == "string" ? $(html) : html;
			if ($modal.data("modal") === undefined) $modal.data("modal","html");
			$box.append($modal);
			
			iModule.modal.init();
			var $form = $("#iModuleModalForm");
			$form.status("default");
			
			if (typeof callback == "function" && callback($modal,$form) === false) return;
			
			$form.on("submit",function(e) {
				e.preventDefault();
				iModule.modal.close();
			});
			
			$(document).triggerHandler("modal.show",[$modal]);
		},
		close:function(check_closable) {
			var check_closable = check_closable === true;
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			var $modal = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal]");
			
			if (check_closable === true && $modal.attr("data-closable") == "FALSE") return;
			
			if ($disabled.length == 0 || $box.length == 0 || $modal.length == 0) return;
			
			if ($("body").attr("data-scroll") !== undefined) {
				var scroll = $("body").attr("data-scroll");
				$("body").attr("data-scroll",null);
				$("body").scrollTop(scroll);
			}
			
			$(document).triggerHandler("modal.close",[$modal]);
			
			if (iModule.isMobile == true) {
				$box.animate({marginTop:-$box.outerHeight(true)},"",function() {
					iModule.enable();
				});
			} else {
				iModule.enable();
			}
		}
	},
	/**
	 * 팝업창을 연다.
	 *
	 * @param string url 페이지 URL
	 * @param int width 가로크기
	 * @param int height 가로크기
	 * @param boolean scroll 스크롤바여부
	 * @param string name 창이름
	 * @param window window
	 */
	openPopup:function(url,width,height,scroll,name) {
		if (screen.availWidth < width) width = screen.availWidth - 50;
		if (screen.availHeight < height) height = screen.availHeight - 50;
		
		var windowLeft = Math.ceil((screen.availWidth - width) / 2);
		var windowTop = Math.ceil((screen.availHeight - height) / 2);
		windowTop = windowTop > 20 ? windowTop - 20 : windowTop;
		var opener = window.open(url, name !== undefined ? name : "", "top=" + windowTop + ",left=" + windowLeft + ",width=" + width + ",height=" + height + ",scrollbars=" + (scroll == true ? "1" : "0"));
		
		if (opener) {
			return opener;
		}
		
		return null;
	},
	/**
	 * 빈페이지 팝업창을 연다.
	 *
	 * @param string url 페이지 URL
	 * @param int width 가로크기
	 * @param int height 가로크기
	 * @param boolean scroll 스크롤바여부
	 * @param string name 창이름
	 * @param window window
	 */
	emptyPopup:function(width,height,scroll,name) {
		if (screen.availWidth < width) width = screen.availWidth - 50;
		if (screen.availHeight < height) height = screen.availHeight - 50;
		
		var windowLeft = Math.ceil((screen.availWidth - width) / 2);
		var windowTop = Math.ceil((screen.availHeight - height) / 2);
		windowTop = windowTop > 20 ? windowTop - 20 : windowTop;
		var opener = window.open(ENV.DIR + "/includes/empty.php", name !== undefined ? name : "", "top=" + windowTop + ",left=" + windowLeft + ",width=" + width + ",height=" + height + ",scrollbars=" + (scroll == true ? "1" : "0"));
		
		if (opener) {
			setTimeout(iModule.resizePopup,500,opener,null,width,height);
			return opener;
		}
		
		return null;
	},
	/**
	 * 브라우져에 따라 팝업창 크기가 다르므로, 팝업창이 열린 후 정확한 사이트로 리사이징한다.
	 *
	 * @param DOM window 팝업 Window DOM 객체
	 * @param string url 페이지 URL
	 * @param int width 가로크기
	 * @param int height 가로크기
	 * @param boolean scroll 스크롤바여부
	 * @param string name 창이름
	 */
	resizePopup:function(popup,url,width,height) {
		try {
			if (width > screen.availWidth) width = screen.availWidth;
			if (height > screen.availHeight) height = screen.availHeight;
			
			var resizeWidth = width - $(popup.window).width();
			var resizeHeight = height - $(popup.window).height();
			
			popup.window.resizeBy(resizeWidth,resizeHeight);
		} catch(e) {}
		
		if (url) popup.location.replace(url);
	},
	/**
	 * 현재창의 크기를 리사이즈한다.
	 *
	 * @param int width 가로크기
	 * @param int height 가로크기
	 */
	resizeWindow:function(width,height,center) {
		var resizeWidth = width === null ? 0 : width - $(window).width();
		var resizeHeight = height === null ? 0 : height - $(window).height();
		
		if (center === true) {
			var left = (screen.availWidth - $(window).width() - resizeWidth) / 2;
			var top = (screen.availHeight - $(window).height() - resizeHeight) / 2;
			
			window.moveTo(left,top);
		}
		
		window.resizeBy(resizeWidth,resizeHeight);
	},
	getCookie:function(name) {
		var cookies = document.cookie.split(";");
		var values = "";
	
		for (var i=0, total=cookies.length;i<total;i++) {
			if (cookies[i].indexOf(name+"=")!=-1) {
				var temp = cookies[i].split("=");
				values = temp[1];
				break;
			}
		}
	
		return values;
	},
	setCookie:function(name,value,expire,path) {
		path = !path ? "/" : path;
		var todaydate = new Date();
		unixtime = todaydate.getTime();
	
		if (value == null) {
			extime = unixtime-3600;
			todaydate.setTime(extime);
			expiretime = " expires=" + todaydate.toUTCString() +";";
		} else {
			extime = unixtime+(expire*1000);
			todaydate.setTime(extime);
			if (expire) expiretime = " expires=" + todaydate.toUTCString() +";";
			else expiretime = "";
		}
	
		document.cookie = name + "=" + escape(value) + "; path="+path+";"+expiretime;
	}
};

/**
 * 플러그인 전역변수 설정
 */
var Plugin = {
	plugins:{},
	getText:function(plugin,code) {
		if (Plugin.plugins[plugin] === undefined) return code;
		else return Plugin.plugins[plugin].getText(code);
	},
	getErrorText:function(plugin,code) {
		if (Plugin.plugins[plugin] === undefined) return code;
		else return Plugin.plugins[plugin].getErrorText(code);
	}
};