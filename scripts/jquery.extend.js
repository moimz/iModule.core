/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * jQuery 의 기능을 확장하기 위한 클래스로 iModule 전반에 걸쳐 사용된다.
 * 기본적인 객체에 대한 초기화 및 UI 이벤트에 대한 부분을 처리한다.
 * 
 * @file /scripts/jquery.extend.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160903
 */
(function($) {
	/**
	 * jQuery ajax 확장
	 *
	 * @param string url 데이터를 전송할 URL
	 * @param object data 전송할 데이터 (data 가 없을 경우 2번째 인자가 콜백함수가 될 수 있다.)
	 * @param function callback 콜백함수
	 */
	$.send = function(url,data,callback) {
		if (typeof data == "function") {
			callback = data;
			data = null;
		}
		
		$.ajax({
			type:"POST",
			url:url,
			data:data,
			dataType:"json",
			success:function(result) {
				callback(result);
			},
			error:function() {
				iModule.alertMessage.show("error","Server Connect Error!",5);
			}
		});
	};
	
	/**
	 * 특정 객체나 오브젝트를 초기화한다.
	 */
	$.fn.inits = function() {
		/**
		 * 객체가 form 일 경우, submit 함수를 받아 form 을 초기화한다.
		 */
		if (this.is("form") == true) {
			/**
			 * submit 이벤트가 있다면 제거한다.
			 */
			this.off("submit");
			
			/**
			 * 매개변수 처리
			 */
			var submit = arguments.length > 0 ? arguments[0] : null;
			/**
			 * submit 함수가 전달되었다면, 폼 submit 시 이벤트를 발생시킨다.
			 */
			if (submit != null && typeof submit == "function") {
				this.on("submit",function() {
					submit($(this));
					return false;
				});
			}
		}
	};
	
	/**
	 * 폼이나 데이터를 Ajax 방식으로 서버에 전송한다.
	 *
	 * @param string url 전송할 URL
	 * @param function callback 전송이 완료된 후 처리할 콜백함수
	 */
	$.fn.send = function(url,callback) {
		/**
		 * 전송대상이 form 이 아닐경우 아무런 행동을 하지 않는다.
		 */
		if (this.is("form") == false) return;
		var data = this.serialize();
		
		$.ajax({
			type:"POST",
			url:url,
			data:data,
			dataType:"json",
			success:function(result) {
				callback(result);
			},
			error:function() {
				iModule.alertMessage.show("error","Server Connect Error!",5);
			}
		});
	};
	
	/**
	 * 객체를 좌우로 흔든다.
	 */
	$.fn.shake = function(distance,times) {
		var interval = 100;
		var distance = distance ? distance : 10;
		var times = times ? times : 4;

		this.css("position","relative");

		for (var i=0, loop=times+1;i<loop;i++) {
			this.animate({left:(i%2 == 0 ? distance : distance*-1)},interval);
		}

		this.animate({left:0},interval);
	};
	/*
	$.fn.positionScroll = function() {
		if (this.offset().top < $("body").scrollTop() + $("#iModuleNavigation.fixed").outerHeight() + 50) {
			$("html,body").animate({scrollTop:this.offset().top - $("#iModuleNavigation.fixed").outerHeight() - 50},"slow");
		} else if (this.offset().top + this.outerHeight() + $("#iModuleNavigation").outerHeight() > $("body").scrollTop() + $(window).height()) {
			$("html,body").animate({scrollTop:this.offset().top + this.outerHeight() - $(window).height() + $("#iModuleNavigation").outerHeight() },"slow");
		}
	};
	
	$.fn.reset = function() {
		if (this.is("form")) {
			$("input,textarea",this).each(function() {
				$(this).reset();
			});
			
			this.formStatus("default");
		} else if (this.attr("type") == "radio" || this.attr("type") == "checkbox") {
			if (this.attr("checked") == "checked") {
				this.prop("checked",true);
			} else {
				this.prop("checked",false);
			}
		} else if (this.attr("data-wysiwyg") == "true") { 
			this.froalaEditor("html.set","");
			Attachment.reset(this.attr("id")+"-attachment");
		} else if (this.is("div.selectControl") == true) {
			if (this.data("originText")) $("button",this).html(this.data("originText")+' <span class="arrow"></span>');
		} else {
			this.val("");
		}
		
		return this;
	};
	
	$.fn.formInit = function(submitter,checker) {
		if (this.is("form")) {
			this.off("submit");
			
			this.on("submit",function() {
				if ($(this).formCheck() == false) return false;
				if (typeof submitter == "function") {
					submitter($(this));
				}
				return false;
			});
			
			$("input,textarea",this).each(function() {
				$(this).inputInit(checker);
			});
		}
		
		return this;
	};
	
	$.fn.formStatus = function(status,messages) {
		if (this.is("form")) {
			$("input,textarea",this).each(function() {
				if (messages === undefined || (messages !== undefined && messages[$(this).attr("name")] !== undefined)) {
					$(this).inputStatus(status,messages !== undefined && messages[$(this).attr("name")] !== undefined ? messages[$(this).attr("name")] : "");
				}
			});
			
			if (status == "error") $("input[data-loading=true], textarea[data-loading=true]",this).attr("data-loading","false").attr("disabled",false);
			
			$("button[type=submit]",this).each(function() {
				$(this).buttonStatus(status);
			});
		}
		
		return this;
	};
	
	$.fn.formCheck = function() {
		var isSuccess = true;
		var scrollTop = -1;
		
		$("input, textarea",this).each(function() {
			var $inputBlock = $(this).parents(".inputBlock, inputInline");
			var $helpBlock = $(".helpBlock",$inputBlock);
			var $errorBlock = $(".errorBlock",$inputBlock);
			
			$inputBlock.removeClass("hasError hasSuccess");
			
			var isError = false;
			
			if ($(this).attr("data-required") == "true") {
				if ($(this).attr("type") == "checkbox" && $(this).is(":checked") == false) {
					isError = true;
				} else if ($(this).val().length == 0) {
					isError = true;
				}
			}
			
			if (isError == true) {
				scrollTop = scrollTop == -1 || $inputBlock.position().top < scrollTop ? $inputBlock.position().top : scrollTop;
				if ($errorBlock.length > 0 && $errorBlock.attr("data-error")) {
					$errorBlock.html($errorBlock.attr("data-error"));
					$errorBlock.show();
				} else if ($helpBlock.length > 0 && $helpBlock.attr("data-error")) {
					$helpBlock.html($helpBlock.attr("data-error"));
				}
				$inputBlock.addClass("hasError");
				
				isSuccess = isError !== true && isSuccess == true;
			} else {
				if ($helpBlock.length > 0 && $helpBlock.attr("data-success")) {
					$helpBlock.html($helpBlock.attr("data-success"));
				}
				$inputBlock.addClass("hasSuccess");
			}
		});
		
		if (scrollTop > -1 && ($("body").scrollTop() + $(window).height() < scrollTop || $("body").scrollTop() + $("#iModuleNavigation").outerHeight(true) + 30 > scrollTop)) {
			$("html,body").animate({scrollTop:scrollTop - $("#iModuleNavigation").outerHeight(true) - 30},"fast");
		}
		
		return isSuccess;
	};
	
	$.fn.inputInit = function(checker) {
		if (this.is("input,textarea")) {
			if (typeof checker == "function") {
				this.on("blur",function() {
					checker($(this));
				});
			}
			this.inputStatus("default");
		}
	};
	
	$.fn.dateInit = function() {
		this.each(function() {
			if ($(this).is("div.dateControl") == true) {
				var $input = $(this);
				var format = $input.attr("data-format") ? $input.attr("data-format") : "YYYY-MM-DD";
				
				$("input",$input).pikaday({
					format:format,
					onSelect:function() {
						$(this._o.field).triggerHandler("change");
					}
				});
			}
		});
	};
	
	$.fn.selectInit = function() {
		if (this.is("div") == false) return;
		if (this.attr("data-field").indexOf("#") == 0) {
			var $field = $(this.attr("data-field"));
		} else if (this.attr("data-field")) {
			var $field = $("input[name="+this.attr("data-field")+"]",this.parents("form"));
		} else {
			var $field = null;
		}
		
		this.data("originText",$("> button",this).text());
		this.data("field",$field);
		$field.data("controller",this);
		
		if ($field != null && $field.val().length > 0 && $("li[data-value='"+$field.val()+"']",this).length > 0) {
			$("> button",this).html($("li[data-value='"+$field.val()+"']",this).html()+' <span class="arrow"></span>');
		}
		
		$field.off("change",function() {
			var $list = $("li[data-value='"+$(this).val()+"']",$(this).data("controller"));
			if ($list.length > 0) {
				$("> button",$(this).data("controller")).html($list.html()+' <span class="arrow"></span>');
			}
		});
		$field.on("change",function() {
			var $list = $("li[data-value='"+$(this).val()+"']",$(this).data("controller")).clone();
			if ($list.length > 0) {
				$("button",$list).remove();
				$("> button",$(this).data("controller")).html($list.html()+' <span class="arrow"></span>');
			}
		});
	
		$("> button",this).attr("type","button");
		$("> button",this).off("click");
		$("> button",this).on("click",function(event) {
			if ($(this).parents("div.selectControl").hasClass("selectControlExtend") == true) {
				$(this).parents("div.selectControl").removeClass("selectControlExtend");
				$(this).parents("div.selectControl").find("li:not(.divider):visible:not([data-disabled=true])").attr("tabindex",null);
			} else {
				$(this).parents("div.selectControl").addClass("selectControlExtend");
				if ($(this).parents("div.selectControl").attr("value") !== undefined) {
					$(this).parents("div.selectControl").find("li:not(.divider):visible:not([data-disabled=true])").attr("tabindex",1);
					iModule.focusDelay($(this).parents("div.selectControl").find("li[data-value='"+$(this).parents("div.selectControl").attr("value")+"']"),100);
				}
				$(document).triggerHandler("iModule.selectControl.extend",[$(this).parents("div.selectControl")]);
			}
			$(this).focus();
			event.preventDefault();
		});
		
		$("> button",this).off("keydown");
		$("> button",this).on("keydown",function(event) {
			if (event.keyCode == 38 || event.keyCode == 40 || event.keyCode == 27) {
				event.preventDefault();
				if ($(this).parents("div.selectControl").hasClass("selectControlExtend") == false || ($(this).parents("div.selectControl").hasClass("selectControlExtend") == true && event.keyCode == 27)) {
					return $(this).click();
				}
				
				var items = $(this).parents("div.selectControl").find("li:not(.divider):visible:not([data-disabled=true])").attr("tabindex",1);
				if (items.length == 0) return;
				
				var index = items.index(items.filter(":focus"));
	
				if (event.keyCode == 38 && index > 0) index--;
				if (event.keyCode == 40 && index < items.length - 1) index++;
				if (!~index) index = 0;
				
				$(items).eq(index).focus();
			}
		});
		
		$("ul > li",this).off("keydown");
		$("ul > li",this).on("keydown",function(event) {
			event.preventDefault();
			
			if (event.keyCode == 38 || event.keyCode == 40 || event.keyCode == 27) {
				if ($(this).parents("div.selectControl").length == 0 || ($(this).parents("div.selectControl").hasClass("selectControlExtend") == true && event.keyCode == 27)) {
					return $($(this).parents("div.selectControl").find("button")).click();
				}
				
				var items = $(this).parents("div.selectControl").find("li:not(.divider):visible:not([data-disabled=true])");
	
				if (items.length == 0) return;
				
				var index = items.index(items.filter(":focus"));
	
				if (event.keyCode == 38 && index > 0) index--;
				if (event.keyCode == 40 && index < items.length - 1) index++;
				if (!~index) index = 0;
				
				$(items).eq(index).focus();
				event.preventDefault();
			}
			
			if (event.keyCode == 13) {
				var items = $(this).parents("div.selectControl").find("li:not(.divider):visible:not([data-disabled=true])");
				var index = items.index(items.filter(":focus"));
				if (!~index) return;
				
				$(items).eq(index).click();
				event.preventDefault();
			}
		});
		
		$("ul > li",this).off("keyword");
		$("ul > li",this).on("click",function(event) {
			if ($(this).hasClass("divider") == true || $(this).attr("data-disabled") == "true") return;
			
			$(this).parents("div.selectControl").data("field").val($(this).attr("data-value"));
			$(this).parents("div.selectControl").data("field").triggerHandler("change");
			
			$($(this).parents("div.selectControl").find("> button")).click();
			$($(this).parents("div.selectControl").find("> button")).focus();
		});
		
		$("ul > li > button",this).on("click",function(event) {
			event.preventDefault();
			event.stopPropagation();
		});
	};
	
	$.fn.inputStatus = function(status,message) {
		if (this.is("input,textarea")) {
			var $inputBlock = this.parents(".inputBlock, .inputInline");
			if ($inputBlock.length > 0) {
				var $helpBlock = $(".helpBlock",$inputBlock);
				$inputBlock.removeClass("hasSuccess hasError");
				
				if (status == "default") {
					if (!$helpBlock.attr("data-default")) $helpBlock.attr("data-default",$helpBlock.html());
					var helpMessage = message ? message : $helpBlock.attr("data-default");
				} else if (status == "loading" || status == "success") {
					var helpMessage = message ? message : $helpBlock.attr("data-success");
					$inputBlock.addClass("hasSuccess");
				} else if (status == "error") {
					var helpMessage = message ? message : $helpBlock.attr("data-error");
					$inputBlock.addClass("hasError");
				}
				
				if (helpMessage) $helpBlock.html(helpMessage);
				else if ($helpBlock.attr("data-default")) $helpBlock.html($helpBlock.attr("data-default"));
				else if (status == "loading" || status == "success") $helpBlock.empty();
			}
			
			if (status == "error") {
				this.inputScroll();
			}
			
			if ((status == "disable" || status == "loading") && this.is(":disabled") == false && this.attr("data-loading") != "true") this.attr("data-loading","true").attr("disabled",true);
			else if (this.attr("data-loading") == "true") this.attr("data-loading","false").attr("disabled",false);
		}
	};
	
	var inputScrollTimer = null;
	var $inputScrollLast = null;
	$.fn.inputScroll = function() {
		if (inputScrollTimer != null) {
			clearTimeout(inputScrollTimer);
			inputScrollTimer = null;
		}
		
		if (this.attr("data-wysiwyg") == "true") {
			var $object = $(".fr-box",this.parent());
		} else {
			var $object = this;
		}
		
		if ($inputScrollLast == null || $inputScrollLast.offset().top > $object.offset().top) {
			$inputScrollLast = $object;
		}
		
		inputScrollTimer = setTimeout(function() { $inputScrollLast.positionScroll(); inputScrollTimer = null; $inputScrollLast = null;},200);
	};
	
	$.fn.buttonStatus = function(status) {
		if (this.is("button")) {
			if (status == "loading") {
				this.data("default",this.html());
				var text = '<i class="fa fa-spin fa-spinner"></i>';
				if (this.attr("data-loading") !== undefined) text+= ' '+this.attr("data-loading");
				else text+= ' Loading...';
				this.html(text).attr("disabled",true);
			} else if (status == "reset" || status == "default") {
				if (this.data("default") !== undefined) {
					this.html(this.data("default"));
				}
				this.attr("disabled",false);
			} else if (status == "error") {
				setTimeout(function($button) { $button.buttonStatus("reset"); },200,this);
			} else if (status == "disable") {
				this.attr("disabled",true);
			} else if (status == "enable") {
				this.attr("disabled",false);
			}
		}
	};
	*/
})(jQuery);