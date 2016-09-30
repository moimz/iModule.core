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
			
			targetObject.getLanguage = function(code,replacement) {
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
				else if ($.inArray(temp[0],["text","button","action"]) > -1) return iModule.getLanguage(code,replacement);
				else return replacement == null ? code : replacement;
			};
			
			targetObject.getErrorMessage = function(code) {
				var message = this.getLanguage("error/"+code,code);
				if (message === code && typeof Admin == "object") message = Admin.getLanguage("error/"+code,code);
				if (message === code) message = iModule.getErrorMessage(code);
				
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
	getLanguage:function(code,replacement) {
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
	getErrorMessage:function(code) {
		var message = this.getLanguage("error/"+code,code);
		if (message === code) message = iModule.getLanguage("error/UNKNOWN")+" ("+code+")";
		
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
			if ($("#iModuleAlertMessage").length == 0) {
				alert(message);
			} else {
				var timer = timer ? timer : 5;
				var idx = iModule.alertMessage.idx++;
				var $item = $("<div>").attr("data-idx",idx).addClass(type).addClass("message").css("display","none");
				$item.html(message);
				var $close = $("<div>").addClass("close").append($("<i>").addClass("fa fa-times-circle"));
				$close.on("click",function() {
					var idx = $(this).parent().attr("data-idx");
					iModule.alertMessage.close(idx);
				});
				$item.append($close);
				
				$("#iModuleAlertMessage").append($item);
				iModule.alertMessage.slideDown(idx);
				setTimeout(iModule.alertMessage.close,timer * 1000,idx);
			}
		},
		/**
		 * 새로 추가된 알림메세지를 위에서 아래로 내려오게 한다.
		 *
		 * @param int idx 알림메세지 고유번호
		 */
		slideDown:function(idx) {
			$("#iModuleAlertMessage > div[data-idx="+idx+"]").slideDown();
		},
		/**
		 * 알림메세지를 닫는다.
		 *
		 * @param int idx 알림메세지 고유번호
		 */
		close:function(idx) {
			var $item = $("#iModuleAlertMessage > div[data-idx="+idx+"]");
			if ($item.length > 0) {
				$item.find(".close").css("visibility","hidden");
				$item.width($("#iModuleAlertMessageItem-"+idx).width());
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
			if ($("#iModuleAlertMessage").length == 0) return;
			
			if (total > 0 && loaded < total) {
				if ($("#iModuleAlertMessageProgress-"+id).length == 0) {
					$("#iModuleAlertMessage").append($("<div>").addClass("progress").attr("id","iModuleAlertMessageProgress-"+id).append($("<span>")));
				}
				$("#iModuleAlertMessageProgress-"+id+" > span").css("width",(loaded/total*100)+"%");
			} else {
				if ($("#iModuleAlertMessageProgress-"+id).length == 0) return;
				
				$("#iModuleAlertMessageProgress-"+id+" > span").css("width","100%");
				$("#iModuleAlertMessageProgress-"+id).fadeOut(3000,function() {
					$(this).remove();
				});
			}
		}
	}
};