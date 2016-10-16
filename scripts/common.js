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
				if ($("body > div[data-role=alert]Progress-"+id).length == 0) {
					$("body > div[data-role=alert]").append($("<div>").addClass("progress").attr("id","iModuleAlertMessageProgress-"+id).append($("<span>")));
				}
				$("body > div[data-role=alert]Progress-"+id+" > span").css("width",(loaded/total*100)+"%");
			} else {
				if ($("body > div[data-role=alert]Progress-"+id).length == 0) return;
				
				$("body > div[data-role=alert]Progress-"+id+" > span").css("width","100%");
				$("body > div[data-role=alert]Progress-"+id).fadeOut(3000,function() {
					$(this).remove();
				});
			}
		}
	},
	enable:function() {
		var $disabled = $("body > div[data-role=disabled]");
		if ($disabled.length == 1) {
			if ($("body").hasClass("mobile") == true) {
				$("body").removeClass("disabled");
				$("body").scrollTop($("body").data("position"));
			}
			$("body").removeClass("disabled").removeClass("mobile");//.scrollTop($disabled.data("position"));
			$disabled.remove();
		}
	},
	disable:function() {
		var $disabled = $("body > div[data-role=disabled]");
		var $box = $("body > div[data-role=disabled] > div[data-role=box]");
		
		if ($disabled.length == 0) {
			var $disabled = $("<div>").attr("data-role","disabled");
			$("body").append($disabled);
			
			var position = $("body").scrollTop();
			$("body").addClass("disabled");
//			$("body > div[data-role=wrapper]").scrollTop(position);
			
			var $box = $("<div>").attr("data-role","box");
			$disabled.data("position",position);
			$disabled.append($box);
			
//			$disabled.css("background","rgba(0,0,0,0.3)");
		}
		
		$box.empty();
		
		/*
		$box.data("scroll",false);
		$box.data("touch",false);
		$box.data("up",false);
		$box.data("down",false);
		$box.data("bounce",true);
		
		$(document).on("touchstart",function(e) {
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			if ($box.length == 0 || $box.data("touch") == true) return;
			
			if ($box.scrollTop() <= 0) $box.data("up",false);
			else $box.data("up",true);
			
			if ($box.prop("scrollHeight") <= $box.height() + $box.scrollTop()) $box.data("down",false);
			else $box.data("down",true);
			
			$box.data("start",e.pageY);
		});
		
		$(document).on("touchmove",function(e) {
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			if ($box.length == 0 || $box.data("touch") == true) return;
			
			var isUp = e.pageY > $box.data("start");
			var isDown = e.pageY < $box.data("start");
			
			if ((isUp == true && $box.data("up") == false) || (isDown == true && $box.data("down") == false)) {
				if ($box.data("scroll") == true) {
					$box.css("marginTop",(e.pageY - $box.data("start")) / 2);
					$box.data("bounce",true);
				}
				e.preventDefault();
			}
		});
		
		$(document).on("touchend",function(e) {
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			if ($box.length == 0 || $box.data("touch") == true) return;
			
			if ($box.data("bounce") == true) {
				$box.animate({marginTop:0},"slow");
				$box.data("bounce",false);
			}
		});
		*/
	},
	modal:{
		init:function() {
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			var $modal = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal]");
			if ($disabled.length == 0 || $box.length == 0 || $modal.length == 0) return;
			
			if (iModule.isMobile == false) {
				if ($modal.outerHeight() + 40 < $box.innerHeight()) {
//					$modal.css("margin",(($box.innerHeight() - $modal.outerHeight()) / 2)+"px auto");
//					$modal.css("left","calc(50% - "+($modal.outerWidth()/2)+"px)");
					$modal.css("margin",(($box.innerHeight() - $modal.outerHeight(true)) / 2)+"px auto");
				} else {
					$modal.css("margin","20px auto");
				}
			}
			
			if ($modal.data("isInit") == true) return;
			
			$modal.on("click",function(e) {
				e.stopPropagation();
			});
			
			$box.on("click",function() {
				iModule.modal.close();
			});
			
			$modal.data("isInit",true);
		},
		show:function(isContent) {
			iModule.disable();
			
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("div[data-role=disabled] > div[data-role=box]");
			$box.empty();
			
			var $modal = $("<div>").attr("data-role","modal");
			
			if (isContent == true) {
				var content = "";
				for (var i=0;i<100;i++) content+= i+"<br>";
			
				$modal.html(content);
			} else {
				$modal.html('<br><br><div data-role="input"><input type="text"></div><br><br><br><br><br><br><select><option>dkasdkasd</option></select><br><br><br>');
			}
			
			$box.append($modal);
			
			$("div[data-role=input]",$modal).inits();
			
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
			
			iModule.modal.init();
		},
		close:function() {
			var $disabled = $("body > div[data-role=disabled]");
			var $box = $("body > div[data-role=disabled] > div[data-role=box]");
			var $modal = $("body > div[data-role=disabled] > div[data-role=box] > div[data-role=modal]");
			if ($disabled.length == 0 || $box.length == 0 || $modal.length == 0) return;
			
			if (iModule.isMobile == true) {
				$box.animate({marginTop:-$box.outerHeight(true)},"",function() {
					iModule.enable();
				});
			} else {
				iModule.enable();
			}
		}
	}
};