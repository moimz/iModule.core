/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * iModule 에서 사용하는 각종 자바스크립트 함수 라이브러리
 * jQuery 기능을 사용하는 확장기능은 jquery.extend.js 파일에 정의되어 있다.
 * 
 * @file /scripts/common.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160904
 */
var iModule = {
	isMobile:navigator.userAgent.match(/(iPhone|iPod|iPad|Android)/) !== null,
	/**
	 * 아이모듈 DOM 객체를 초기화한다.
	 */
	init:function(container) {
		var $container = container ? $(document) : container;
		
		/**
		 * input 객체 초기화
		 */
		$("div[data-role=input]",$container).inits();
		
		/**
		 * tab 객체 초기화
		 */
		$("*[data-role=tab]",$container).inits();
		
		/**
		 * 시간출력
		 */
		$("time[data-time][data-moment]",$container).each(function() {
			if ($(this).attr("data-moment") == "fromNow") {
				$(this).html(moment.unix($(this).attr("data-time")).locale($("html").attr("lang")).fromNow());
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
		 * 페이지이동 네비게이션 처리
		 */
		$("div[data-role=pagination] > ul > li.disabled > a",$container).on("click",function(e) {
			e.preventDefault();
		});
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
			}
			
			targetObject._LANG = lang;
			targetObject._OLANG = oLang;
			
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
			
			if ($("div[data-role=input]",$modal).length > 0) {
				var $input = $("div[data-role=input]",$modal).first();
				if ($input.attr("data-type") == "select") {
					$("button",$input).focus();
				} else if ($input.attr("data-type") == "textarea") {
					$("textarea",$input).focus();
				} else {
					$("input",$input).focus();
				}
			}
			
			$("button[data-action]",$modal).on("click",function() {
				if ($(this).attr("data-action") == "close") {
					iModule.modal.close();
				}
			});
			
			$modal.on("click",function(e) {
				e.stopPropagation();
			});
			
			if ($modal.attr("data-closable") == "TRUE") {
				$box.on("click",function() {
					iModule.modal.close();
				});
			}
			
			$modal.data("isInit",true);
			iModule.modal.set();
		},
		/**
		 * 모달창의 크기와 위치를 정의한다.
		 */
		set:function() {
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			var $modal = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal]");
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
				} else {
					$modal.css("minWidth",($(window).width() - 20)+"px").css("width",($(window).width() - 20)+"px");
				}
			}
			
			if ($modal.height() < maxHeight) {
				if (maxHeight < $(window).height()) {
					$modal.css("minHeight",maxHeight+"px").css("height",maxHeight+"px");
				} else {
					$modal.css("minHeight",($(window).height() - 20)+"px").css("height",($(window).height() - 20)+"px");
				}
			}
			
			if (is_fullsize == true) {
				if (iModule.isMobile == true || ($modal.width() < width || $modal.height() < height)) {
					$modal.css("minWidth","100%").css("width","100%").css("minHeight","100%").css("height","100%");
					$("body").attr("data-scroll",$("body").scrollTop());
				}
			}
		},
		/**
		 * 모달창을 서버로 부터 가져온다.
		 *
		 * @param string url 모달창을 가져올 주소
		 * @param object data 전달할 데이터
		 */
		get:function(url,data,callback) {
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
				}
			});
		},
		showHtml:function(html,callback) {
			iModule.disable();
			
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("div[data-role=disabled] > div[data-role=box]");
			$box.empty();
			
			var $modal = $(html);
			$box.append($modal);
/*
			
			if (iModule.isMobile == true) {
				var position = $("body").scrollTop();
				$("body").data("position",position);
				$("body").addClass("mobile");
				$("body > div[data-role=wrapper]").height(Math.max($(window).height(),$box.outerHeight(true))).scrollTop(position);
				$("body").scrollTop(0);
				$disabled.height(Math.max($(window).height(),$box.outerHeight(true)));
				
				$box.css("marginTop",-$box.outerHeight(true));
				$box.animate({marginTop:0});
			}
*/
			
			iModule.modal.init();
			var $form = $("#iModuleModalForm");
			
			if (typeof callback == "function" && callback($modal,$form) === false) return;
			
			$form.on("submit",function(e) {
				e.preventDefault();
				iModule.modal.close();
			});
		},
		close:function() {
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			var $modal = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal]");
			if ($disabled.length == 0 || $box.length == 0 || $modal.length == 0) return;
			
			if ($("body").attr("data-scroll") !== undefined) {
				var scroll = $("body").attr("data-scroll");
				$("body").attr("data-scroll",null);
				$("body").scrollTop(scroll);
			}
			
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
	 */
	openPopup:function(url,width,height,scroll,name) {
		var windowLeft = (screen.width - width) / 2;
		var windowTop = (screen.height - height) / 2;
		windowTop = windowTop > 20 ? windowTop - 20 : windowTop;
		var opener = window.open("", name !== undefined ? name : "", "top=" + windowTop + ",left=" + windowLeft + ",width=" + width + ",height=" + height + ",scrollbars=" + (scroll == true ? "1" : "0"));
		
		if (opener) {
			setTimeout(iModule.resizePopup,500,opener,url,width,height);
		}
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
		var resizeWidth = width - $(popup.window).width();
		var resizeHeight = height - $(popup.window).height();
		
		popup.window.resizeBy(resizeWidth,resizeHeight);
		popup.location.href = url;
	}
};