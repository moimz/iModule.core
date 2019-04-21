/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * jQuery 의 기능을 확장하기 위한 클래스로 iModule 전반에 걸쳐 사용된다.
 * 기본적인 객체에 대한 초기화 및 UI 이벤트에 대한 부분을 처리한다.
 * 
 * @file /scripts/jquery.extend.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 1. 29.
 */
(function($) {
	$.propHooks.disabled = {
		set:function(el,value) {
			el.disabled = value;
			if (value == true) $(el).trigger("disable");
			else $(el).trigger("enable");
		}
	};
	
	$.propHooks.checked = {
		set:function(el,value) {
			var oValue = el.checked;
			el.checked = value;
			if (oValue != value) $(el).trigger("change");
		}
	};
	
	$.attrHooks.disabled = {
		set:function(el,value) {
			el.disabled = value;
			if (value == true) $(el).trigger("disable");
			else $(el).trigger("enable");
		}
	};
	
	$.attrHooks.checked = {
		set:function(el,value) {
			var oValue = el.checked;
			el.checked = value;
			if (oValue != value) $(el).trigger("change");
		}
	};
	
	$.valHooks.select = {
		set:function(el,value) {
			if (typeof value == "string" && $(el).val() != value) {
				el.value = value;
				$(el).trigger("change");
			}
		}
	};
	
	/**
	 * 특정 객체나 오브젝트를 초기화한다.
	 */
	$.fn.inits = function(arg) {
		if (typeof this != "object") return;
		
		var callback = arg !== undefined && typeof arg == "function" ? arg : null;
		var is_reset = callback === null && arg === true;
		
		if (this.length > 1) {
			this.each(function() {
				$(this).inits(arg);
			});
			return;
		} else {
			if (this.data("isInit") === true && is_reset == false) return;
		}
		
		if (this.is("select, input, textarea") == true && this.parent().is("div[data-role=input]") == true) {
			this.parent().inits(arg);
			return;
		}
		
		is_reset = is_reset == true && this.data("isInit") === true;
		
		/**
		 * 객체가 툴팁을 사용할 경우
		 */
		if (this.attr("data-tooltip") !== undefined) {
			if (this.attr("data-tooltip-type") == "click") {
				this.on("click",function(e) {
					iModule.tooltip.show($(this));
					e.stopPropagation();
				});
			} else {
				this.hover(function() {
					iModule.tooltip.show($(this));
				},function() {
					iModule.tooltip.hide(2);
				});
			}
		}
		
		/**
		 * 객체가 form 일 경우, submit 함수를 받아 form 을 초기화한다.
		 */
		if (this.is("form") == true) {
			/**
			 * submit 함수가 전달되었다면, 폼 submit 시 이벤트를 발생시킨다.
			 */
			if (callback !== null) {
				this.on("submit",function() {
					callback($(this));
					return false;
				});
			}
			
			this.status("default");
		}
		
		/**
		 * 객체가 div 이고 data-role 이 input 일 경우
		 */
		if (this.is("div[data-role=input]") == true) {
			if (is_reset == true) {
				this.children("input, select, textarea").status("default");
			}
			
			/**
			 * div[data-role=input] 하위에는 자식이 1개 있어야 한다.
			 */
			if (this.children().length !== 1 && is_reset == false) return;
			
			/**
			 * 자식노드가 버튼인 경우
			 */
			if (this.children().is("button") == true) {
				var $container = this;
				var $button = this.children();
				$container.attr("data-type","button");
			}
			
			/**
			 * 자식노드가 객체가 select 일 경우
			 */
			if (this.children().is("select") == true) {
				var $container = this;
				
				if (is_reset == true) {
					$("button",$container).remove();
					$("ul",$container).remove();
					$("div[data-role=help]",$container).remove();
					var $select = $("select",$container);
				} else {
					var $select = this.children();
				}
				
				$container.attr("data-type","select");
				if ($select.attr("name")) $container.attr("data-name",$select.attr("name"));
				
				var $value = $("option",$select).filter(":selected");
				var $button = $("<button>").attr("type","button");
				if ($value.length == 0) {
					var $text = $("<span>").html("");
				} else {
					$button.attr("data-value",$value.attr("value"));
					var $text = $("<span>").html($value.html());
				}
				
				var $arrow = $("<i>").addClass("mi mi-down");
				$button.append($text).append($arrow);
				$container.append($button);
				if ($select.is(":disabled") == true) {
					$button.disable();
				}
				
				$select.data("submitValue",null);
				
				if (is_reset == false) {
					$select.on("disable",function() {
						var $parent = $(this).parent();
						var $button = $("button",$parent).disable();
					});
					
					$select.on("enable",function() {
						var $parent = $(this).parent();
						var $button = $("button",$parent).enable();
					});
				
					$select.on("change",function() {
						if ($(this).isValid() !== null && $(this).data("submitValue") != $(this).val()) {
							$(this).status("default");
						}
						
						var $parent = $(this).parent();
						var $button = $("button",$parent);
						var $item = $("option[value='"+$(this).val()+"']",$(this));
						if ($item.length == 1) {
							$button.attr("data-value",$item.attr("value"));
							$("span",$button).html($item.html());
						}
					});
				}
				
				/**
				 * 모바일 브라우져에서는 Native UI 를 사용하도록 한다.
				 */
				if (iModule.isMobile == true) {
					if (is_reset == false) {
						$select.on("focus",function() {
							var $parent = $(this).parent();
							var $button = $("button",$parent).addClass("focus");
						});
						
						$select.on("blur",function() {
							var $parent = $(this).parent();
							var $button = $("button",$parent).removeClass("focus");
						});
					}
				} else {
					var $lists = $("<ul>");
					$("option",$select).each(function() {
						var value = $(this).attr("value") ? $(this).attr("value") : "";
						var $item = $("<li>").attr("data-value",value).html($(this).html());
						$lists.append($item);
					});
					$select.hide();
					$container.append($lists);
					
					$button.on("click",function(e) {
						var $parent = $(this).parent();
						var $select = $("select",$parent);
						var $value = $("option",$select).filter(":selected");
						var $lists = $("ul",$parent);
						
						$("div[data-role=input].extend").not($parent).removeClass("extend");
						
						if ($parent.hasClass("extend") == true) {
							$parent.removeClass("extend").removeClass("up");
							$("li:not(.divider):visible:not([data-disabled=true])",$lists).attr("tabindex",null);
						} else {
							$parent.addClass("extend");
							$("li:not(.divider):visible:not([data-disabled=true])",$lists).attr("tabindex",1);
							
							if ($(this).offset().top - $(document).scrollTop() + 250 > $(window).height()) {
								$parent.addClass("up");
							}
						}
						
						if ($("li[data-value='"+$value.attr("value")+"']",$lists).length == 0) {
							$(this).focus();
						} else {
							$("li[data-value='"+$value.attr("value")+"']",$lists).focus();
						}
						
						e.preventDefault();
						e.stopPropagation();
					});
					
					$button.on("keydown",function(e) {
						if (e.keyCode == 9 || e.keyCode == 38 || e.keyCode == 40 || e.keyCode == 27) {
							var $parent = $(this).parent();
							var $lists = $("ul",$parent);
							
							if (e.keyCode == 9) {
								$parent.removeClass("extend");
								return;
							} else {
								$("div[data-role=input].extend").not($parent).removeClass("extend");
							}
							
							e.preventDefault();
							
							if (e.keyCode == 27) {
								if ($parent.hasClass("extend") == true) {
									$button.click();
								}
								return;
							} else {
								if ($parent.hasClass("extend") == false) {
									$button.click();
								}
							}
							
							var $items = $("li:not(.divider):visible:not([data-disabled=true])",$lists).attr("tabindex",1);
							if ($items.length == 0) return;
							
							var index = $items.index($items.filter(":focus"));
				
							if (e.keyCode == 38 && index > 0) index--;
							if (e.keyCode == 40 && index < $items.length - 1) index++;
							if (!~index) index = 0;
							
							$items.eq(index).focus();
						}
						
						if (e.keyCode == 36 || (e.metaKey == true && e.keyCode == 38)) {
							$items.eq(0).focus();
							e.preventDefault();
						}
						
						if (e.keyCode == 35 || (e.metaKey == true && e.keyCode == 40)) {
							$items.eq($items.length - 1).focus();
							e.preventDefault();
						}
						
						if (e.keyCode == 33 || e.keyCode == 34) {
							e.preventDefault();
						}
					});
					
					$("li",$lists).on("mouseover",function(e) {
						if ($(this).hasClass("divider") == true || $(this).attr("data-disabled") == "true") return;
						$(this).focus();
					});
					
					$("li",$lists).on("keydown",function(e) {
						var $parent = $(this).parents("div[data-role=input]");
						var $lists = $("ul",$parent);
						var $items = $("li[tabindex=1]",$lists);
						if ($items.length == 0) return;
						
						var index = $items.index($items.filter(":focus"));
						
						if (e.keyCode == 9 || e.keyCode == 38 || e.keyCode == 40 || e.keyCode == 27) {
							e.preventDefault();
							
							if (e.keyCode == 27) {
								if ($parent.hasClass("extend") == true) {
									$button.click();
								}
								return;
							}
							
							if ((e.keyCode == 38 || (e.keyCode == 9 && e.shiftKey == true)) && index > 0) index--;
							if ((e.keyCode == 40 || (e.keyCode == 9 && e.shiftKey == false)) && index < $items.length - 1) index++;
							if (!~index) index = 0;
							
							$items.eq(index).focus();
						}
						
						if (e.keyCode == 36 || (e.metaKey == true && e.keyCode == 38)) {
							$items.eq(0).focus();
							e.preventDefault();
						}
						
						if (e.keyCode == 35 || (e.metaKey == true && e.keyCode == 40)) {
							$items.eq($items.length - 1).focus();
							e.preventDefault();
						}
						
						if (e.keyCode == 13) {
							$items.eq(index).click();
							e.preventDefault();
						}
						
						if (e.keyCode == 33 || e.keyCode == 34) {
							e.preventDefault();
						}
					});
					
					$("li",$lists).on("click",function(e) {
						if ($(this).hasClass("divider") == true || $(this).attr("data-disabled") == "true") return;
						
						var $parent = $(this).parents("div[data-role=input]");
						var $button = $("button",$parent);
						var $select = $("select",$parent);
						
						$select.val($(this).attr("data-value"));
						
						$button.click();
						$button.focus();
					});
				}
				
				$select.status("default");
			}
			
			/**
			 * 자식객체가 input 일 경우
			 */
			if (this.children().is("input") == true) {
				var $container = this;
				var $input = this.children();
				$container.attr("data-type","input");
				if ($input.attr("name")) $container.attr("data-name",$input.attr("name"));
				
				if ($input.is(":disabled") == true) {
					$container.addClass("disabled");
				}
				
				$input.data("submitValue",null);
				
				$input.on("disable",function() {
					$(this).parent().addClass("disabled");
				});
				
				$input.on("enable",function() {
					$(this).parent().removeClass("disabled");
				});
				
				$input.on("keyup",function(e) {
					if (e.keyCode != 13 && $(this).isValid() !== null && $(this).data("submitValue") != $(this).val()) {
						$(this).status("default");
					}
				});
				
				/**
				 * 숫자필드인 경우
				 */
				if ($input.is("[type=number]") == true) {
					$input.on("keydown",function(e) {
						if (e.keyCode == 8 || e.keyCode == 9 || e.keyCode == 13 || e.ctrlKey == true || e.metaKey == true) return;
						if (e.keyCode >= 38 && e.keyCode <= 40) return;
						if (e.keyCode >= 48 && e.keyCode <= 57 && e.shiftKey == false) return;
						if (e.keyCode >= 96 && e.keyCode <= 105 && e.shiftKey == false) return;
						if (e.keyCode == 110 && e.shiftKey == false) return;
						if (e.keyCode == 190 && e.shiftKey == false) return;
						
						e.preventDefault();
					});
					
					$input.on("blur",function(e) {
						var value = $(this).val().replace(/[^0-9\.]*/g,"");
						$input.val(value);
					});
				}
				
				/**
				 * 검색필드인 경우
				 */
				if ($input.is("[type=search]") == true) {
					if ($input.parents("div[data-role=input]").attr("data-search")) $input.keyword($input.parents("div[data-role=input]").attr("data-search"));
				}
				
				/**
				 * input type이 date 일 경우
				 */
				if ($input.is("[type=date]") == true) {
					var format = $input.attr("data-format") ? $input.attr("data-format") : "YYYY-MM-DD";
					$input.attr("placeholder",format);
					
					/**
					 * 모바일 브라우져에서는 Native UI 를 사용하도록 한다.
					 */
					if (iModule.isMobile == true) {
						$input.attr("type","text").prop("readonly",true);
						
						var $date = $("<input>").attr("type","date");
						$container.append($date);
						
						var $button = $("<button>").attr("type","button").html("<i class='mi mi-calendar'></i>");
						$container.append($button);
						
						$button.on("click",function() {
							$date.focus();
						});
						
						$date.on("focus",function() {
							$input.addClass("focus");
						});
						
						$date.on("blur",function() {
							$input.removeClass("focus");
						});
						
						$date.on("change",function() {
							if (moment($(this).val()).isValid() == true) {
								var format = $input.attr("data-format") ? $input.attr("data-format") : "YYYY-MM-DD";
								$input.val(moment($(this).val()).format(format));
							} else {
								$input.val("");
							}
						});
					} else {
						$input.attr("type","text");
						var $button = $("<button>").attr("type","button").html("<i class='mi mi-calendar'></i>");
						$container.append($button);
						
						$input.on("blur",function(value) {
							if ($(this).val().match(/^[0-9]{8}$/) != null && $(this).val().match(/^[0-9]{8}$/).length == 1) {
								$(this).val($(this).val().substr(0,4)+"-"+$(this).val().substr(4,2)+"-"+$(this).val().substr(6,2));
								$(this).trigger("change");
								return;
							}
							
							if (moment($(this).val()).isValid() == true) {
								var format = $(this).attr("data-format") ? $(this).attr("data-format") : "YYYY-MM-DD";
								$(this).val(moment($(this).val()).format(format));
							} else {
								$(this).val("");
							}
						});
						
						$input.on("click",function(e) {
							var $parent = $(this).parents("div[data-role=input]");
							$("div[data-role=input].extend").not($parent).removeClass("extend");
							e.stopPropagation();
						});
						
						$input.on("keydown",function(e) {
							var $parent = $(this).parents("div[data-role=input]");
							var value = $(this).val() && moment($(this).val()).isValid() == true ? moment($(this).val()) : moment();
							
							if (e.keyCode == 9 || e.keyCode == 27) {
								$parent.removeClass("extend");
							}
							
							if ($parent.hasClass("extend") == true) {
								if (e.keyCode == 37 || e.keyCode == 38 || e.keyCode == 39 || e.keyCode == 40) {
									e.preventDefault();
									if (e.keyCode == 37) value.add(-1,"d");
									if (e.keyCode == 38) value.add(-7,"d");
									if (e.keyCode == 39) value.add(1,"d");
									if (e.keyCode == 40) value.add(7,"d");
									
									$("div[data-role=calendar]",$parent).calendar(value);
								}
							} else {
								if (e.keyCode == 13) {
									e.preventDefault();
									$("button",$parent).triggerHandler("click");
								}
								
								if (e.keyCode == 38 || e.keyCode == 40) {
									e.preventDefault();
									if ($(this).data("wait") === true) return;
									
									if (e.keyCode == 38) value.add(-1,"d");
									if (e.keyCode == 40) value.add(1,"d");
									
									var format = $(this).attr("data-format") ? $(this).attr("data-format") : "YYYY-MM-DD";
									$(this).val(value.format(format));
									$(this).data("wait",true);
									
									$(this).triggerHandler("change",[value.format(format)]);
									
									setTimeout(function($this) { $this.data("wait",false); },50,$(this));
								}
							}
						});
						
						$button.on("click",function(e) {
							var $parent = $(this).parents("div[data-role=input]");
							var $input = $("input",$parent);
							var format = $input.attr("data-format") ? $input.attr("data-format") : "YYYY-MM-DD";
							
							if ($parent.hasClass("extend") == true) {
								$parent.removeClass("extend");
								$input.focus();
							} else {
								$("div[data-role=input].extend").not($parent).removeClass("extend");
								
								$parent.addClass("extend");
								$parent.remove("div[data-role=picker]");
								
								var $picker = $("<div>").attr("data-role","picker").css("minWidth",280).css("maxWidth",340);
								$parent.append($picker);
								$picker.calendar($input.val(),function(value,isDestory) {
									$input.val(value.format(format));
									if (isDestory === true) {
										$parent.removeClass("extend");
										$input.focus();
									}
									$input.triggerHandler("change",[value]);
								});
							}
							
							e.stopPropagation();
						});
					}
					
					$input.on("disable",function() {
						$button.disable();
					});
					
					$input.on("enable",function() {
						$button.enable();
					});
				}
				
				/**
				 * input type이 date 일 경우
				 */
				if ($input.is("[type=time]") == true) {
					$input.attr("placeholder","HH:mm");
					$input.on("change",function() {
						if ($(this).val().length > 0 && $(this).val().search(/[0-9]{2}:[0-9]{2}/) == -1) {
							$input.status("error");
						}
					});
				}
			}
			
			/**
			 * 자식객체가 textarea 일 경우
			 */
			if (this.children().is("textarea") == true) {
				var $container = this;
				var $textarea = this.children();
				$container.attr("data-type","textarea");
				if ($textarea.attr("name")) $container.attr("data-name",$textarea.attr("name"));
				
				if ($textarea.is(":disabled") == true) {
					$container.addClass("disabled");
				}
				
				$textarea.data("submitValue",null);
				
				$textarea.on("disable",function() {
					$(this).parent().addClass("disabled");
				});
				
				$textarea.on("enable",function() {
					$(this).parent().removeClass("disabled");
				});
				
				$textarea.on("keyup",function() {
					if ($(this).isValid() !== null && $(this).data("submitValue") != $(this).val()) {
						$(this).status("default");
					}
				});
				
				if (this.attr("data-auto-height") == "true") {
					$textarea.data("height",$textarea.height());
					$textarea.data("padding",$textarea.prop("scrollHeight") - $textarea.height());
					
					$textarea.data("height",$textarea.height()).on("keypress keyup",function() {
						$(this).height($(this).data("height"));
						if ($(this).val().length > 0 && $(this).prop("scrollHeight") > $(this).height() + $(this).data("padding")) {
							$(this).height($(this).prop("scrollHeight") - $(this).data("padding"));
						}
					});
				}
			}
			
			/**
			 * 자식객체가 label 이고, label 의 자식객체가 checkbox 일 경우
			 */
			if (this.children().is("label") == true && this.children().eq(0).has("input[type=checkbox]").length == 1) {
				var $container = this;
				var $label = this.children();
				var $checkbox = $("input[type=checkbox]",$container);
				$container.attr("data-type","checkbox");
				$container.attr("data-name",$checkbox.attr("name"));
				
				var $icon = $("<button>").attr("type","button").addClass("checkbox");
				if ($checkbox.is(":checked") == true) $icon.addClass("on");
				$checkbox.hide();
				$checkbox.after($icon);
				
				$icon.on("click",function(e) {
					$("input[type=checkbox]",$(this).parents("div[data-role=input]")).trigger("click");
					e.preventDefault();
					e.stopPropagation();
				});
				
				if ($checkbox.is(":disabled") == true) {
					$container.addClass("disabled");
				}
				
				$checkbox.data("submitValue",null);
				
				$checkbox.on("disable",function() {
					$(this).parents("div[data-role=input]").addClass("disabled");
				});
				
				$checkbox.on("enable",function() {
					$(this).parents("div[data-role=input]").removeClass("disabled");
				});
				
				$checkbox.on("change",function() {
					if ($(this).isValid() !== null && $(this).data("submitValue") != $(this).is(":checked")) {
						$(this).status("default");
					}
				});
				
				$checkbox.on("change",function() {
					var $parent = $(this).parent().parent();
					var $icon = $("button.checkbox",$parent);
					if ($(this).is(":checked") == true) {
						$icon.addClass("on");
					} else {
						$icon.removeClass("on");
					}
				});
			}
			
			/**
			 * 자식객체가 label 이고, label 의 자식객체가 radio 일 경우
			 */
			if (this.children().is("label") == true && this.children().eq(0).has("input[type=radio]").length == 1) {
				var $container = this;
				var $label = this.children();
				var $radio = $("input[type=radio]",$container);
				$container.attr("data-type","radio");
				
				var $icon = $("<button>").attr("type","button").addClass("radio");
				if ($radio.is(":checked") == true) $icon.addClass("on");
				$radio.hide();
				$radio.after($icon);
				
				$icon.on("click",function(e) {
					$radio.trigger("click");
					e.preventDefault();
					e.stopPropagation();
				});
				
				if ($radio.is(":disabled") == true) {
					$container.addClass("disabled");
				}
				
				$radio.data("submitValue",null);
				
				$radio.on("disable",function() {
					$(this).parents("div[data-role=input]").addClass("disabled");
				});
				
				$radio.on("enable",function() {
					$(this).parents("div[data-role=input]").removeClass("disabled");
				});
				
				$radio.on("change",function() {
					var $form = $(this).parents("form");
					$form = $form.length == 0 ? $("body") : $form;
					
					$("input[type=radio][name="+$(this).attr("name")+"]",$form).each(function() {
						var $parent = $(this).parent().parent();
						var $icon = $("button.radio",$parent);
					
						if ($(this).is(":checked") == true) {
							$icon.addClass("on");
						} else {
							$icon.removeClass("on");
						}
					});
					
					if ($radio.isValid() !== null && $radio.data("submitValue") != $radio.val()) {
						$("input[type=radio][name="+$(this).attr("name")+"]",$form).status("default");
					}
				});
			}
		}
		
		/**
		 * 객체가 테이블일 경우
		 */
		if (this.is("ul[data-role=table]") == true) {
			var $container = this;
			
			if (this.hasClass("fixed") == false && $("li > span.fixed",$container).length > 0) {
				var width = 0;
				$("li:first-child > span.fixed",$container).each(function() {
					width+= $(this).outerWidth();
				});
				
				var $fixed = $container.clone(true);
				var $origin = $container.clone(true);
				
				if ($container.parents("div[data-role=table-fixer]").length == 0) {
					var $fixer = $("<div>").attr("data-role","table-fixer");
					$container.replaceWith($fixer);
					
					$origin.addClass("origin");
					$fixer.append($origin);
					
					$fixed.addClass("fixed");
					$("li",$fixed).width(width);
					$("li",$fixed).css("minWidth",width+"px");
					$fixer.append($fixed);
				}
			}
		}
		
		/**
		 * 객체가 태그입력기일 경우
		 */
		if (this.is("div[data-role=tags]") == true && this.children().length == 1) {
			var $container = this;
			
			var $input = $container.children().eq(0);
			var tags = $input.val().length > 0 ? $input.val().split(",") : [];
			$input.hide();
			
			$input.on("change",function() {
				$("div[data-role=tag][data-tag]",$container).remove();
				var tags = $(this).val().length > 0 ? $(this).val().split(",") : [];
				for (var i=0, loop=tags.length;i<loop;i++) {
					var $tag = $("<div>").attr("data-role","tag").attr("data-tag",tags[0]);
					$tag.append($("<span>").text(tags[i]));
					$tag.append($("<button>").attr("type","button").append($("<i>").addClass("mi mi-close")));
					$(this).after($tag);
					$tag.inits();
				}
			});
			
			for (var i=0, loop=tags.length;i<loop;i++) {
				var $tag = $("<div>").attr("data-role","tag").attr("data-tag",tags[0]);
				$tag.append($("<span>").text(tags[i]));
				$tag.append($("<button>").attr("type","button").append($("<i>").addClass("mi mi-close")));
				$container.append($tag);
				$tag.inits();
			}
			
			var $insert = $("<div>").attr("data-role","tag");
			$insert.append($("<input>").attr("type","text"));
			$container.append($insert);
			$insert.inits();
		}
		
		/**
		 * 객체가 태그입력기 내 태그일 경우
		 */
		if (this.is("div[data-role=tag]") == true) {
			var $container = this;
			if ($("input",$container).length == 0) {
				$container.attr("data-tag",$("span",$container).text());
				$("span",$container).on("click",function() {
					var tag = $(this).text();
					
					$(this).parents("div[data-role=tags]").children().has("input").remove();
					
					var $insert = $("<div>").attr("data-role","tag");
					$insert.append($("<input>").attr("type","text").data("last",tag).val(tag));
					$container.replaceWith($insert);
					$insert.inits();
					$("input",$insert).select();
				});
				
				$("button",$container).on("click",function() {
					var $parent = $container.parents("div[data-role=tags]");
					$container.remove();
					
					var tags = [];
					var $tags = $("div[data-role=tag][data-tag]",$parent);
					$tags.each(function() {
						tags.push($(this).attr("data-tag"));
					});
					$("> input",$parent).val(tags.join(","));
				});
			} else {
				var $input = $("input",$container);
				$input.on("keydown",function(e) {
					if (e.keyCode == 32 || e.keyCode == 222 || e.keyCode == 191 || e.keyCode == 220 || e.keyCode == 186 || e.keyCode == 187) {
						e.preventDefault();
						return;
					}
					
					if ((e.keyCode == 188 || e.keyCode == 190) && e.shiftKey == true) {
						e.preventDefault();
						return;
					}
					
					if (e.keyCode == 188) {
						var tag = $input.val().replace(/(#| )/,"");
						
						if (tag.length > 0) {
							var $tag = $("<div>").attr("data-role","tag");
							$tag.append($("<span>").html(tag));
							$tag.append($("<button>").attr("type","button").append($("<i>").addClass("mi mi-close")));
							$tag.inits();
							$container.replaceWith($tag);
							
							var $insert = $("<div>").attr("data-role","tag");
							$insert.append($("<input>").attr("type","text"));
							$tag.parents("div[data-role=tags]").append($insert);
							$insert.inits();
							$("input",$insert).focus();
						
							var $parent = $tag.parents("div[data-role=tags]");
						} else {
							var $parent = $container.parents("div[data-role=tags]");
							
							if ($container.next().length > 0) {
								$container.remove();
								
								var $insert = $("<div>").attr("data-role","tag");
								$insert.append($("<input>").attr("type","text").data("last",tag).val(tag));
								$parent.append($insert);
								$insert.inits();
								e.preventDefault();
								$("input",$insert).focus();
							}
						}
						
						var tags = [];
						var $tags = $("div[data-role=tag][data-tag]",$parent);
						$tags.each(function() {
							tags.push($(this).attr("data-tag"));
						});
						
						$("> input",$parent).val(tags.join(","));
						
						e.preventDefault();
						return;
					}
					
					if (e.keyCode == 51 && e.shiftKey == true) {
						e.preventDefault();
						return;
					}
					
					if (e.keyCode == 8) {
						if ($(this).val().length == 0 && $container.prev("div[data-role=tag]").length > 0) {
							var $prev = $container.prev("div[data-role=tag]");
							var tag = $("span",$prev).text();
							
							$container.remove();
							
							var $insert = $("<div>").attr("data-role","tag");
							$insert.append($("<input>").attr("type","text").data("last",tag).val(tag));
							$prev.replaceWith($insert);
							$insert.inits();
							e.preventDefault();
							$("input",$insert).focus();
						}
					}
					
					if (e.keyCode == 13) {
						e.preventDefault();
					}
					
					if (e.keyCode == 9) {
						var tag = $input.val().replace(/(#| )/,"");
						
						if (tag.length > 0) {
							var $tag = $("<div>").attr("data-role","tag");
							$tag.append($("<span>").html(tag));
							$tag.append($("<button>").attr("type","button").append($("<i>").addClass("mi mi-close")));
							$tag.inits();
							$container.replaceWith($tag);
							
							var $insert = $("<div>").attr("data-role","tag");
							$insert.append($("<input>").attr("type","text"));
							$tag.parents("div[data-role=tags]").append($insert);
							$insert.inits();
							$("input",$insert).focus();
						
							var $parent = $tag.parents("div[data-role=tags]");
						} else {
							var $parent = $container.parents("div[data-role=tags]");
							
							if ($container.next().length > 0) {
								$container.remove();
								
								var $insert = $("<div>").attr("data-role","tag");
								$insert.append($("<input>").attr("type","text").data("last",tag).val(tag));
								$parent.append($insert);
								$insert.inits();
								e.preventDefault();
								$("input",$insert).focus();
							}
						}
						
						var tags = [];
						var $tags = $("div[data-role=tag][data-tag]",$parent);
						$tags.each(function() {
							tags.push($(this).attr("data-tag"));
						});
						
						$("> input",$parent).val(tags.join(","));
						
						if (tag.length > 0) e.preventDefault();
					}
				});
				
				$input.on("blur",function(e) {
					setTimeout(function($input) {
						var tag = $input.val().replace(/(#| )/,"");
						
						if (tag.length > 0) {
							var $tag = $("<div>").attr("data-role","tag");
							$tag.append($("<span>").html(tag));
							$tag.append($("<button>").attr("type","button").append($("<i>").addClass("mi mi-close")));
							$tag.inits();
							$container.replaceWith($tag);
							
							var $insert = $("<div>").attr("data-role","tag");
							$insert.append($("<input>").attr("type","text"));
							$tag.parents("div[data-role=tags]").append($insert);
							$insert.inits();
						
							var $parent = $tag.parents("div[data-role=tags]");
						} else {
							var $parent = $container.parents("div[data-role=tags]");
							
							if ($container.next().length > 0) {
								$container.remove();
								
								var $insert = $("<div>").attr("data-role","tag");
								$insert.append($("<input>").attr("type","text").data("last",tag).val(tag));
								$parent.append($insert);
								$insert.inits();
								e.preventDefault();
							}
						}
						
						var tags = [];
						var $tags = $("div[data-role=tag][data-tag]",$parent);
						$tags.each(function() {
							tags.push($(this).attr("data-tag"));
						});
						
						$("> input",$parent).val(tags.join(","));
					},100,$input);
				});
				
				if ($input.parents("div[data-role=tags]").attr("data-search")) $input.keyword($input.parents("div[data-role=tags]").attr("data-search"));
			}
		}
		
		/**
		 * 객체가 tab 일 경우
		 */
		if (this.is("ul[data-role=tab]") == true) {
			var $tab = this;
			var $tabBox = $("div[data-role=tab][data-name="+$tab.attr("data-name")+"]");
			
			if ($("> li.selected",$tab).length == 0) {
				var $first = $("> li",$tab).first();
				$first.addClass("selected");
				$tabBox.tab($first.attr("data-tab"));
			}
			
			$("> li[data-tab] > button",$tab).on("click",function() {
				var tab = $(this).parent().attr("data-tab");
				var name = $(this).parent().parent().attr("data-name");
				var $tabBox = $("div[data-role=tab][data-name="+name+"]");
				
				$tabBox.tab(tab);
			})
		}
		
		if (this.is("div[data-role=tab]") == true) {
			var $tabBox = this;
			var $tab = $("ul[data-role=tab][data-name="+$tabBox.attr("data-name")+"]");
			
			if ($("> li.selected",$tab).length == 0) {
				var tab = $("> li",$tab).first().attr("data-tab");
			} else {
				var tab = $("> li.selected",$tab).attr("data-tab");
			}
			
			$("> div[data-tab]",$tab).hide();
			this.data("isInit",true);
			$tabBox.tab(tab);
		}
		
		this.data("isInit",true);
	};
	
	$.keyword = function($input,url) {
		var $parent = $input.parents("div[data-role]").eq(0);
		if ($input.is(":focus") == false) return;
		
		if ($input.val().length > 0) {
			if ($input.data("last") != $input.val()) {
				var keyword = $input.val();
				
				$.send(url,{keyword:keyword},function(result) {
					if (result.success == true) {
						if (result.keywords.length == 0) {
							$("ul",$parent).remove();
						} else {
							var $list = $("<ul>");
							
							for (var i=0, loop=result.keywords.length;i<loop;i++) {
								var $item = $("<li>").data("keyword",result.keywords[i]).html(result.keywords[i]);
								$item.on("click",function() {
									$input.data("last",$(this).data("keyword"));
									$input.val($(this).data("keyword"));
									$("ul",$parent).remove();
									$input.focus();
								});
								$list.append($item);
							}
							
							if ($("ul",$parent).length == 0) $parent.append($list);
							else $("ul",$parent).replaceWith($list);
						}
						
						$input.data("last",keyword);
						setTimeout($.keyword,100,$input,url);
					}
				});
			} else {
				setTimeout($.keyword,100,$input,url);
			}
		} else {
			$("ul",$parent).remove();
			setTimeout($.keyword,100,$input,url);
		}
	};
	
	/**
	 * 키워드 자동완성을 불러온다.
	 */
	$.fn.keyword = function(url) {
		if (this.is("input") == false) return;
		
		var $input = this;
		var $parent = $input.parents("div[data-role]").eq(0);
		if ($parent.is("div[data-role]") == false) return;
		
		$parent.attr("data-keyword","TRUE");
		
		var url = url ? url : "";
		
		$input.data("timeout",null);
		$input.data("last",$input.val());
		
		$input.on("focus",function() {
			setTimeout($.keyword,100,$(this),url);
		});
		
		$input.on("blur",function() {
			setTimeout(function($input) {
				$("ul",$parent).remove();
				$input.data("last","");
			},100,$input);
		});
		
		$input.on("keydown",function(e) {
			if (e.keyCode == 38 || e.keyCode == 40) {
				e.preventDefault();
				
				var $lists = $("ul",$parent);
				if ($lists.length == 0) return;
				
				var $items = $("li",$lists);
				if ($items.length == 0) return;
				
				var index = $items.index($items.filter(".selected"));
	
				if (e.keyCode == 38 && index > 0) index--;
				if (e.keyCode == 40 && index < $items.length - 1) index++;
				if (!~index) index = 0;
				
				$items.removeClass("selected");
				$items.eq(index).addClass("selected");
				
				if (e.keyCode == 40 && $items.eq(index).position().top + $items.eq(index).height() > $lists.height()) {
					$lists.scrollTop($lists.scrollTop() + $items.eq(index).position().top + $items.eq(index).height() - $lists.height());
				}
				
				if (e.keyCode == 38 && $items.eq(index).position().top < 0) {
					$lists.scrollTop($lists.scrollTop() + $items.eq(index).position().top);
				}
				
				$input.data("last",$items.eq(index).data("keyword"));
				$input.val($items.eq(index).data("keyword"));
			}
			
			if (e.keyCode == 13) {
				if ($("ul > li.selected",$parent).length > 0) {
					$input.data("last",$input.val());
					$("ul",$parent).remove();
				
					e.preventDefault();
				}
			}
			
			if (e.keyCode == 33 || e.keyCode == 34) {
				e.preventDefault();
			}
		});
	};
	
	/**
	 * 데이터를 불러온다.
	 */
	$.fn.set = function(url,params,callback) {
		if (typeof params == "function") {
			callback = params;
			params = {};
		}
		
		if (this.is("form") == true) {
			var $form = this;
			
			$.send(url,params,function(result) {
				if (result.success == true && result.data) {
					for (var field in result.data) {
						var value = result.data[field];
						
						var $field = $("input[name="+field+"], select[name="+field+"], textarea[name="+field+"]",$form);
						if ($field.length > 0) {
							if ($field.is("select") == true) {
								if (value) {
									if ($("option[value="+value+"]",$field).length == 0) {
										$field.attr("data-value",value);
									} else {
										$field.val(value);
									}
								}
							} else if ($field.is("input[type=radio]") == true) {
								$field.each(function() {
									if ($(this).attr("value") == value) {
										$(this).prop("checked",true);
									}
								});
							} else if ($field.is("input[type=checkbox]") == true) {
								$field.prop("checked",value === true || value == $field.attr("value"));
							} else if ($field.is("textarea[data-wysiwyg=TRUE]") == true) {
								$field.froalaEditor("html.set",value);
								$field.val(value);
							} else {
								$field.val(value);
							}
							
							if ($field.parents("div[data-role=tags]").length == 1) {
								$field.parents("div[data-role=tags]").inits();
							}
							
							$field.trigger("change");
						} else {
							var $field = $("input[name='"+field+"[]'], select[name='"+field+"[]'], textarea[name='"+field+"[]']",$form);
							if ($field.length > 0) {
								if (typeof value == "string") value = value.split(",");
								for (var i=0, loop=value.length;i<loop;i++) {
									value[i] = value[i].toString();
								}
								if ($field.is("input[type=checkbox]") == true) {
									$field.each(function() {
										if ($.inArray($(this).attr("value"),value) > -1) {
											$(this).prop("checked",true);
										} else {
											$(this).prop("checked",false);
										}
									});
								}
							}
						}
					}
				}
				
				if (typeof callback == "function") callback(result);
			});
		}
	};
	
	/**
	 * 객체의 값을 초기화한다.
	 */
	$.fn.reset = function() {
		if (this.length > 1) {
			this.each(function() {
				$(this).reset();
			});
			return;
		}
		
		if (this.is("form")) {
			$("input, textarea, select",this).each(function() {
				$(this).reset();
			});
			
			this.status("default");
		} else if (this.is("input") == true && (this.attr("type") == "radio" || this.attr("type") == "checkbox")) {
			if (this.attr("checked") == "checked") {
				this.prop("checked",true);
			} else {
				this.prop("checked",false);
			}
			this.trigger("change");
		} else if (this.is("select") == true) {
			var $option = $("option",this).first();
			this.val($option.attr("value"));
			this.trigger("change");
		} else if (this.is("input") == true) {
			this.val("");
			this.trigger("change");
		}
		
		return this;
	};
	
	/**
	 * 달력을 만든다.
	 */
	$.fn.calendar = function(value,callback) {
		if (value && moment(value).isValid() === false) value = "";
		if (this.is("div[data-role=calendar]") == true) {
			var $calendar = this;
			var $year = $("span.year",$calendar);
			var $month = $("span.month",$calendar);
			
			var year = value ? parseInt(value.format("YYYY")) : $calendar.data("year");
			var month = value ? parseInt(value.format("M")) : $calendar.data("month");
			var value = value ? value : $calendar.data("value");
			
			if ($calendar.data("value").format("YYYY-MM-DD") != value.format("YYYY-MM-DD")) {
				$calendar.data("year",year);
				$calendar.data("month",month);
				$calendar.data("value",value);
				$calendar.triggerHandler("change",[value,false]);
			}
			
			var now = moment([$calendar.data("year"),$calendar.data("month")-1,1]);
			$year.html(now.format("YYYY"));
			$month.html(now.format("MM"));
			
			var $years = $("<select>");
			for (var i=year-100;i<=year+15;i++) {
				var $option = $("<option>").attr("value",i).html(i);
				if (i == year) $option.attr("selected","selected");
				$years.append($option);
			}
			$years.on("click",function(e) {
				e.stopPropagation();
			});
			$years.on("change",function() {
				$calendar.data("year",parseInt($(this).val()));
				$calendar.calendar();
			});
			$year.append($years);
			
			var $months = $("<select>");
			for (var i=1;i<=12;i++) {
				var $option = $("<option>").attr("value",i).html(i);
				if (i == month) $option.attr("selected","selected");
				$months.append($option);
			}
			$months.on("click",function(e) {
				e.stopPropagation();
			});
			$months.on("change",function() {
				$calendar.data("month",parseInt($(this).val()));
				$calendar.calendar();
			});
			$month.append($months);
			
			$("div.dates",$calendar).remove();
			
			var $dates = $("<div>").addClass("dates");
			var $days = $("<ul>");
			$days.append($("<li>").addClass("Sun").html("Sun"));
			$days.append($("<li>").addClass("Mon").html("Mon"));
			$days.append($("<li>").addClass("Tue").html("Tue"));
			$days.append($("<li>").addClass("Wed").html("Wed"));
			$days.append($("<li>").addClass("Thu").html("Thu"));
			$days.append($("<li>").addClass("Fri").html("Fri"));
			$days.append($("<li>").addClass("Sat").html("Sat"));
			
			$dates.append($days);
			
			$calendar.append($dates);
			
			var start = parseInt(now.format("e"));
			
			var thisMonth = false;
			var endOfMonth = false;
			for (var i=0;i<=49;i++) {
				var date = moment(now).add(i - start,"d");
				
				if (thisMonth === false && now.format("M") == date.format("M")) thisMonth = true;
				if (thisMonth == true && now.format("M") != date.format("M")) endOfMonth = true;
				if (endOfMonth == true && i % 7 == 0) break;
				
				var $date = $("<button>").html(date.format("D")).addClass(date.format("ddd")).data("value",date);
				if (now.format("M") != date.format("M")) $date.addClass("notMatched");
				if (date.format("YYYY-MM-DD") == moment(value).format("YYYY-MM-DD")) $date.addClass("selected");
				
				$date.on("click",function(e) {
					var $parent = $(this).parents("div[data-role=calendar]");
					$parent.triggerHandler("change",[$(this).data("value"),true]);
					e.preventDefault();
				});
				$dates.append($date);
			}
			
			$calendar.focus();
		} else {
			var $container = this;
			var value = value ? moment(value) : moment();
		
			var $calendar = $("<div>").attr("data-role","calendar").attr("tabindex","1").data("year",parseInt(value.format("YYYY"))).data("month",parseInt(value.format("M"))).data("value",value);
			
			var $header = $("<div>").addClass("header");
			var $prev = $("<button>").attr("type","button").html('<i class="mi mi-left"></i>');
			$prev.on("mousedown",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				var timeout = setTimeout(function($this) {
					var interval = setInterval(function($this) {
						var prev = moment().year($this.data("year")).month($this.data("month")).add(-2,"months");
						$this.data("year",prev.year());
						$this.data("month",prev.month() + 1);
						$this.calendar();
					},100,$this);
					
					$parent.data("moveInterval",interval);
				},500,$parent);
				$parent.data("moveTimeout",timeout);
			});
			$prev.on("mouseup",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveTimeout")) {
					clearTimeout($parent.data("moveTimeout"));
					$parent.data("moveTimeout",null);
				}
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				e.stopPropagation();
			});
			$prev.on("mouseout",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveTimeout")) {
					clearTimeout($parent.data("moveTimeout"));
					$parent.data("moveTimeout",null);
				}
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				e.stopPropagation();
			});
			$prev.on("click",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveTimeout")) {
					clearTimeout($parent.data("moveTimeout"));
					$parent.data("moveTimeout",null);
				}
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				var prev = moment().year($parent.data("year")).month($parent.data("month")).add(-2,"months");
				$parent.data("year",prev.year());
				$parent.data("month",prev.month() + 1);
				$parent.calendar();
				
				e.preventDefault();
				e.stopPropagation();
			});
			$header.append($prev);
			
			var $navigation = $("<div>").addClass("navigation");
			var $year = $("<span>").addClass("year");
			$navigation.append($year);
			
			var $month = $("<span>").addClass("month");
			$navigation.append($month);
			
			$header.append($navigation);
			
			var $next = $("<button>").attr("type","button").html('<i class="mi mi-right"></i>');
			$next.on("mousedown",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				var timeout = setTimeout(function($this) {
					var interval = setInterval(function($this) {
						var next = moment().year($this.data("year")).month($this.data("month"));
						$this.data("year",next.year());
						$this.data("month",next.month() + 1);
						$this.calendar();
					},100,$this);
					
					$parent.data("moveInterval",interval);
				},500,$parent);
				$parent.data("moveTimeout",timeout);
			});
			$next.on("mouseup",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveTimeout")) {
					clearTimeout($parent.data("moveTimeout"));
					$parent.data("moveTimeout",null);
				}
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				e.stopPropagation();
			});
			$next.on("mouseout",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveTimeout")) {
					clearTimeout($parent.data("moveTimeout"));
					$parent.data("moveTimeout",null);
				}
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				e.stopPropagation();
			});
			$next.on("click",function(e) {
				var $parent = $(this).parents("div[data-role=calendar]");
				
				if ($parent.data("moveTimeout")) {
					clearTimeout($parent.data("moveTimeout"));
					$parent.data("moveTimeout",null);
				}
				
				if ($parent.data("moveInterval")) {
					clearInterval($parent.data("moveInterval"));
					$parent.data("moveInterval",null);
				}
				
				var next = moment().year($parent.data("year")).month($parent.data("month"));
				$parent.data("year",next.year());
				$parent.data("month",next.month() + 1);
				$parent.calendar();
				
				e.preventDefault();
				e.stopPropagation();
			});
			$header.append($next);
			
			$calendar.append($header);
			
			this.append($calendar);
			
			$calendar.calendar();
			
			$calendar.on("keydown",function(e) {
				if (e.keyCode == 9 || e.keyCode == 13 || e.keyCode == 27 || e.keyCode == 37 || e.keyCode == 38 || e.keyCode == 39 || e.keyCode == 40) {
					var year = $(this).data("year");
					var month = $(this).data("month");
					var value = moment($(this).data("value"));
					
					if (e.keyCode == 9 || e.keyCode == 13 || e.keyCode == 27) {
						$(this).triggerHandler("change",[value,true]);
						return;
					}
					
					if (e.keyCode == 37 || e.keyCode == 38 || e.keyCode == 39 || e.keyCode == 40) {
						if ($(this).data("wait") === true) {
							e.preventDefault();
							return;
						}
						
						$(this).data("wait",true);
						
						if (e.keyCode == 37) value.add(-1,"d");
						if (e.keyCode == 38) value.add(-7,"d");
						if (e.keyCode == 39) value.add(1,"d");
						if (e.keyCode == 40) value.add(7,"d");
						
						$(this).calendar(value);
						setTimeout(function($this) { $this.data("wait",false); },50,$(this));
					}
					
					e.preventDefault();
				}
			});
			
			$calendar.on("change",function(e,value,isDestory) {
				if (typeof callback == "function" && value) callback(value,isDestory);
			});
			
			$calendar.on("click",function(e) {
				e.stopPropagation();
			});
		}
	};
	
	/**
	 * 탭을 한다.
	 *
	 * @param string name 탭이름
	 */
	$.fn.tab = function(tab) {
		if (this.is("div[data-role=tab]") == false || this.data("isInit") == false) return;
		var $this = this;
		var $tab = $("ul[data-role=tab][data-name="+this.attr("data-name")+"]");
		var $box = $("> div[data-tab="+tab+"]",this);
		if ($box.length == 1) {
			var position = $("body").scrollTop();
			$("> div[data-tab]:visible",this).hide();
			$("> div[data-tab]:visible",this).triggerHandler("hide");
			
			$("> div[data-tab="+tab+"]",this).show();
			$("> div[data-tab="+tab+"]",this).triggerHandler("show");
			
			this.triggerHandler("tabchange",[tab]);
			
			$("> li.selected",$tab).removeClass("selected");
			$("> li[data-tab="+tab+"]",$tab).addClass("selected");
			
			$("body").scrollTop(position);
		}
	};
	
	/**
	 * jQuery ajax 확장
	 *
	 * @param string url 데이터를 전송할 URL
	 * @param object data 전송할 데이터 (data 가 없을 경우 2번째 인자가 콜백함수가 될 수 있다.)
	 * @param function callback 콜백함수
	 */
	$.send = function(url,data,callback,count) {
		if (typeof data == "function") {
			callback = data;
			data = null;
		}
		var count = count ? count : 0;
		
		$.ajax({
			type:"POST",
			url:url,
			data:data,
			dataType:"json",
			success:function(result) {
				if (typeof callback == "function" && callback(result) === false) return false;
				if (result.success == false) {
					if (result.message || result.error) {
						if (typeof Ext == "object") {
							Ext.Msg.show({title:iModule.getText("text/error"),msg:result.message ? result.message : result.error,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
						} else {
							if (result.message) iModule.alert.show("error",result.message,5);
							if (result.error) iModule.modal.error(result.error,result.url ? result.url : null);
						}
					}
				}
			},
			error:function() {
				if (count == 3) {
					if (typeof Ext == "object") {
						Ext.Msg.show({title:iModule.getText("text/error"),msg:iModule.getErrorText("DISCONNECT_ERROR"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
					} else {
						iModule.alert.show("error",iModule.getErrorText("DISCONNECT_ERROR"),5);
						if (typeof callback == "function") callback({success:false});
					}
				} else {
					setTimeout(function(url,data,callback,count) { $.send(url,data,callback,count); },1000,url,data,callback,++count);
				}
			}
		});
	};
	
	/**
	 * 폼 데이터를 Ajax 방식으로 서버에 전송한다.
	 *
	 * @param string url 전송할 URL
	 * @param function callback 전송이 완료된 후 처리할 콜백함수
	 */
	$.fn.send = function(url,callback,count) {
		/**
		 * 전송대상이 form 이 아닐경우 아무런 행동을 하지 않는다.
		 */
		if (this.is("form") == false) return;
		
		if (this.triggerHandler("beforesubmit",[this]) === false) return;
		
		var count = count !== undefined ? count : 1;
		var $form = this;
		var data = $("input[type=file]",$form).length == 0 ? $form.serialize() : new FormData($form[0]);
		
		$("input, select, textarea",$form).each(function() {
			if ($(this).attr("type") == "checkbox") {
				$(this).data("submitValue",$(this).is(":checked"));
			} else {
				$(this).attr("submitValue",$(this).val());
			}
		});
		
		$form.status("loading");
		
		$.ajax({
			type:"POST",
			url:url,
			data:data,
			processData:$("input[type=file]",$form).length == 0 ? true : false,
			dataType:"json",
			contentType:$("input[type=file]",$form).length == 0 ? "application/x-www-form-urlencoded; charset=UTF-8" : false,
			success:function(result) {
				if (typeof callback == "function" && callback(result) === false) return false;
				if (result.success == false && result.errors) $form.status("error",result.errors);
				if (result.message) {
					iModule.alert.show(result.success == true ? "success" : "error",result.message);
				}
				if (result.error) {
					iModule.modal.error(result.error,result.url ? result.url : null);
				}
				if (result.success == false && !result.errors) $form.status("default");
			},
			error:function() {
				/**
				 * 재시도 횟수가 3회일 경우 에러를 발생하고 멈춘다.
				 */
				if (count === false || count == 3) {
					$form.status("error");
					iModule.alert.show("error","Server Connect Error!",5);
				} else {
					setTimeout(function($form,url,callback,count) { $form.status("default"); $form.send(url,callback,count); },1000,$form,url,callback,++count);
				}
			}
		});
	};
	
	/**
	 * input, select, checkbox 요소의 유효값 상태를 확인한다.
	 *
	 * @return boolean 유효성여부 (null 일 경우 알수없다는 의미)
	 */
	$.fn.isValid = function() {
		if (this.length != 1) return null;
		
		if (this.is("input,textarea,select") == true) {
			var $parent = this.parents("div[data-role=input]").length == 0 ? null : this.parents("div[data-role=input]").eq(0);
			var $inputset = $parent == null || $parent.parents("div[data-role=inputset]").length == 0 ? null : $parent.parents("div[data-role=inputset]").eq(0);
			
			if ($parent == null) return null;
			var $inputbox = $inputset == null ? $parent : $inputset;
			
			if ($inputbox.hasClass("success") == true) return true;
			if ($inputbox.hasClass("error") == true) return false;
			return null;
		}
	};
	
	/**
	 * 폼이나 input, button 요소의 상태를 변경한다.
	 *
	 * @param object object 상태를 변경할 오브젝트
	 */
	$.fn.status = function(status,message) {
		if (typeof this != "object") return;
		var message = message !== undefined ? message : null;
		
		this.each(function() {
			/**
			 * 객체가 Form 일 경우
			 */
			if ($(this).is("form") == true) {
				if (status == "error") {
					$(this).status("default");
					
					if (typeof message == "object") {
						for (var field in message) {
							if (typeof message[field] == "object") {
								for (var value in message[field]) {
									var $field = $("input[type=checkbox][name='"+field+"[]'][value='"+value+"']",$(this));
									if ($field.length == 0) {
										iModule.alert.show("error",field + " : " + message[field][value]);
									} else {
										$field.status("error",message[field][value]);
									}
								}
							} else {
								var $field = $("input[name="+field+"], select[name="+field+"], textarea[name="+field+"], input[type=checkbox][name='"+field+"[]']",$(this));
								if ($field.length == 0) {
									iModule.alert.show("error",field + " : " + message[field]);
								} else {
									$field.status("error",message[field]);
								}
							}
						}
						
						$("div[data-role=input].error, div[data-role=inputset].error, div[data-module=wysiwyg].error").first().scroll();
					}
				} else {
					/**
					 * Form 내부의 input, textarea 객체에 대해서 처리
					 */
					$("input,textarea,select",$(this)).status(status,message);
					$("button[type=submit]",$(this)).status(status);
					$("button[type=button]",$(this)).status(status,false);
				}
			}
			
			/**
			 * 객체가 submit 버튼일 경우
			 */
			if ($(this).is("button[type=submit]") == true || $(this).is("input[type=submit]") == true) {
				if (status == "loading") {
					if ($(this).data("defaultHtml") === undefined) $(this).data("defaultHtml",$(this).html());
					if ($(this).is(":disabled") == false) $(this).attr("data-loading","TRUE");
					$(this).outerWidth($(this).outerWidth());
					if (message) {
						$(this).html('<i class="mi mi-loading"></i>'+message);
					} else {
						$(this).html('<i class="icon mi mi-loading"></i>');
					}
					$(this).disable();
				} else if (status == "default" || status == "success" || status == "error") {
					if ($(this).data("defaultHtml") !== undefined) $(this).html($(this).data("defaultHtml"));
					if ($(this).attr("data-loading") == "TRUE") {
						$(this).enable();
						$(this).attr("data-loading",null);
					}
				} else {
					if ($(this).is(":disabled") == false) $(this).attr("data-loading","TRUE");
					$(this).disable();
				}
			}
			
			/**
			 * 객체가 버튼일 경우
			 */
			if ($(this).is("button[type=button]") == true) {
				var is_indicator = message !== false;
				
				if (status == "loading") {
					if (is_indicator == true) {
						if ($(this).data("defaultHtml") === undefined) $(this).data("defaultHtml",$(this).html());
						$(this).outerWidth($(this).outerWidth());
						$(this).html('<i class="icon mi mi-loading"></i>');
					}
					if ($(this).is(":disabled") == false) $(this).attr("data-loading","TRUE");
					$(this).disable();
				} else if (status == "default" || status == "success" || status == "error") {
					if ($(this).data("defaultHtml") !== undefined) $(this).html($(this).data("defaultHtml"));
					if ($(this).attr("data-loading") == "TRUE") {
						$(this).enable();
						$(this).attr("data-loading",null);
					}
				} else {
					if ($(this).is(":disabled") == false) $(this).attr("data-loading","TRUE");
					if (status == $(this).attr("data-action")) {
						if ($(this).data("defaultHtml") === undefined) $(this).data("defaultHtml",$(this).html());
						$(this).html('<i class="mi mi-loading"></i>');
					}
					$(this).disable();
				}
			}
			
			/**
			 * 객체가 input, select, textarea 일 경우
			 */
			if ($(this).is("input,textarea,select") == true) {
				if (status == "loading") {
					if ($(this).is(":disabled") == false) $(this).attr("data-loading","TRUE");
					$(this).disable();
				} else if (status == "default" || status == "success" || status == "error") {
					if ($(this).attr("data-loading") == "TRUE") {
						$(this).enable();
						$(this).attr("data-loading",null);
					}
				} else {
					if ($(this).is(":disabled") == false) $(this).attr("data-loading","TRUE");
					$(this).disable();
				}
				
				if ($(this).is("textarea[data-wysiwyg=TRUE]") == true) {
					var $parent = $(this).parents("div[data-module=wysiwyg]").length == 0 ? null : $(this).parents("div[data-module=wysiwyg]").eq(0);
				} else {
					var $parent = $(this).parents("div[data-role=input]").length == 0 ? null : $(this).parents("div[data-role=input]").eq(0);
					var $inputset = $parent == null || $parent.parents("div[data-role=inputset]").length == 0 ? null : $parent.parents("div[data-role=inputset]").eq(0);
					
					if ($inputset != null) {
						var $target = $(this).parents("div[data-role=input]");
					}
				}
				
				if ($parent == null) return;
				
				var $inputbox = $inputset == null ? $parent : $inputset;
				
				var setStatus = status;
				if (status == "success") {
					if ($(this).attr("type") == "checkbox" || $(this).attr("type") == "radio") {
						if ($("input[name='"+$(this).attr("name")+"']:checked",$inputbox).length == 0) setStatus = "default";
					} else {
						if ($inputbox.is("[data-role=inputset]") == true) {
							setStatus = "default";
							var $children = $("input, textarea, select",$inputbox);
							for (var i=0, loop=$children.length;i<loop;i++) {
								if ($children.eq(i).val().length > 0) {
									setStatus = "success";
									break;
								}
							}
						} else {
							if ($(this).val().length == 0) setStatus = "default";
						}
					}
				}
				
				$inputbox.removeClass("success error loading default");
				$inputbox.addClass(setStatus);
				if ($inputbox.is("[data-role=inputset]") == true) {
					$target.removeClass("success error loading default");
					$target.addClass(setStatus);
				}
				
				var help = message ? message : ($inputbox.attr("data-"+setStatus) ? $inputbox.attr("data-"+setStatus) : null);
				help = help == null && $inputbox.attr("data-default") ? $inputbox.attr("data-default") : help;
				
				if (help !== null) {
					var $help = $("<div>").attr("data-role","help").addClass(setStatus).html(help);
					if ($parent.data("isInit") !== true) $parent.inits();
				}
				
				$("div[data-role=help]",$inputbox).remove();
				if (help !== null) $inputbox.append($help);
			}
		});
		
		return this;
	};
	
	/**
	 * 객체가 스크롤 범위내에 없을 경우 해당 객체 위치로 스크롤 시킨다.
	 */
	$.fn.scroll = function() {
		if (this.length == 0) return;
		
		var offsetTop = 50;
		var offsetBottom = 50;
		
		if (this.outerHeight() > $(window).height() || this.offset().top - offsetTop < $("body").scrollTop()) {
			$("html,body").animate({scrollTop:this.offset().top - offsetTop});
		} else if (this.offset().top + this.outerHeight(true) + offsetBottom > $(window).height() + $("body").scrollTop()) {
			$("html,body").animate({scrollTop:this.offset().top + this.outerHeight(true) + offsetBottom - $(window).height()});
		}
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
	
	/**
	 * 폼 요소를 JSON 데이터로 변경한다.
	 *
	 * @param boolean isIncludeId 폼의 ID 를 포함할지 여부
	 */
	$.fn.serializeJson = function(isIncludeId) {
		if (this.is("form") == false) return null;
		
		var o = {};
		var a = this.serializeArray();
		$.each(a, function() {
			if (o[this.name] !== undefined) {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		
		if (isIncludeId === true && o.id === undefined) o.id = this.attr("id");
		return o;
	};
	
	/**
	 * 모바일 디바이스의 줌을 막는다.
	 */
	$.fn.preventZoom = function() {
		$(this).bind('touchstart', function preventZoom(e){
			var t2 = e.timeStamp;
			var t1 = $(this).data('lastTouch') || t2;
			var dt = t2 - t1;
			var fingers = e.originalEvent.touches.length;
			$(this).data('lastTouch', t2);
			if (!dt || dt > 500 || fingers > 1){
				return;
			}
			e.preventDefault();
			$(e.target).trigger('click');
		});
	};
	
	/**
	 * 대상을 회전시킨다.
	 */
	$.fn.rotate = function(options) {
		var $this=$(this);
		
		if (typeof options == "number") {
			$this.css({"transform":"rotate("+options+"deg"});/*	'-ms-filter' : 'progid:DXImageTransform.Microsoft.Matrix(M11=' + a + ', M12=' + b + ', M21=' + c + ', M22=' + d + ',sizingMethod=\'auto expand\')',
			'filter' : 'progid:DXImageTransform.Microsoft.Matrix(M11=' + a + ', M12=' + b + ', M21=' + c + ', M22=' + d + ',sizingMethod=\'auto expand\')',
			'-moz-transform' :  "matrix(" + a + ", " + c + ", " + b + ", " + d + ", 0, 0)",
			'-webkit-transform' :  "matrix(" + a + ", " + c + ", " + b + ", " + d + ", 0, 0)",
			'-o-transform' :  "matrix(" + a + ", " + c + ", " + b + ", " + d + ", 0, 0)"
			});
			*/
			return;
		}
		
		var prefixes, opts, wait4css=0;
		prefixes=['-Webkit-', '-Moz-', '-O-', '-ms-', ''];
		opts=$.extend({
			startDeg: false,
			endDeg: 360,
			duration: 1,
			count: 1,
			easing: 'linear',
			animate: {},
			forceJS: false
		}, options);
	
		function supports(prop) {
			var can=false, style=document.createElement('div').style;
			$.each(prefixes, function(i, prefix) {
				if (style[prefix.replace(/\-/g, '')+prop]==='') {
					can=true;
				}
			});
			return can;
		}
	
		function prefixed(prop, value) {
			var css={};
			if (!supports.transform) {
				return css;
			}
			$.each(prefixes, function(i, prefix) {
				css[prefix.toLowerCase()+prop]=value || '';
			});
			return css;
		}
	
		function generateFilter(deg) {
			var rot, cos, sin, matrix;
			if (supports.transform) {
				return '';
			}
			rot=deg>=0 ? Math.PI*deg/180 : Math.PI*(360+deg)/180;
			cos=Math.cos(rot);
			sin=Math.sin(rot);
			matrix='M11='+cos+',M12='+(-sin)+',M21='+sin+',M22='+cos+',SizingMethod="auto expand"';
			return 'progid:DXImageTransform.Microsoft.Matrix('+matrix+')';
		}
	
		supports.transform=supports('Transform');
		supports.transition=supports('Transition');
	
		opts.endDeg*=opts.count;
		opts.duration*=opts.count;
	
		if (supports.transition && !opts.forceJS) { // CSS-Transition
			if ((/Firefox/).test(navigator.userAgent)) {
				wait4css=(!options||!options.animate)&&(opts.startDeg===false||opts.startDeg>=0)?0:25;
			}
			$this.queue(function(next) {
				if (opts.startDeg!==false) {
					$this.css(prefixed('transform', 'rotate('+opts.startDeg+'deg)'));
				}
				setTimeout(function() {
					$this
						.css(prefixed('transition', 'all '+opts.duration+'s '+opts.easing))
						.css(prefixed('transform', 'rotate('+opts.endDeg+'deg)'))
						.css(opts.animate);
				}, wait4css);
	
				setTimeout(function() {
					$this.css(prefixed('transition'));
					if (!opts.persist) {
						$this.css(prefixed('transform'));
					}
					next();
				}, (opts.duration*1000)-wait4css);
			});
	
		} else { // JavaScript-Animation + filter
			if (opts.startDeg===false) {
				opts.startDeg=$this.data('rotated') || 0;
			}
			opts.animate.perc=100;
	
			$this.animate(opts.animate, {
				duration: opts.duration*1000,
				easing: $.easing[opts.easing] ? opts.easing : '',
				step: function(perc, fx) {
					var deg;
					if (fx.prop==='perc') {
						deg=opts.startDeg+(opts.endDeg-opts.startDeg)*perc/100;
						$this
							.css(prefixed('transform', 'rotate('+deg+'deg)'))
							.css('filter', generateFilter(deg));
					}
				},
				complete: function() {
					if (opts.persist) {
						while (opts.endDeg>=360) {
							opts.endDeg-=360;
						}
					} else {
						opts.endDeg=0;
						$this.css(prefixed('transform'));
					}
					$this.css('perc', 0).data('rotated', opts.endDeg);
				}
			});
		}
	
		return $this;
	};
	
	$.fn.enable = function() {
		if (this.is("input, select, textarea, button") == true) {
			this.prop("disabled",false);
		}
		
		return this;
	};
	
	$.fn.disable = function() {
		if (this.is("input, select, textarea, button") == true) {
			this.prop("disabled",true);
		}
		
		return this;
	};
	
	$.fn.checked = function(checked,is_event) {
		if (checked === undefined) {
			return this.prop("checked");
		} else {
			if (this.is("input[type=radio]") == true || this.is("input[type=checkbox]") == true) {
				this.prop("checked",checked,is_event !== false);
			}
			
			return this;
		}
	}
	
	$.fn.setDisabled = function(value) {
		if (value == true) this.disable();
		else this.enable();
	};
	
	$(document).ready(function() {
		iModule.init();
		
		$(document).on("click",function(e) {
			$("div[data-role=input]").removeClass("extend");
			$("div[data-role=picker]").remove();
			iModule.tooltip.hide();
		});
		
		$(window).on("resize",function() {
			iModule.modal.init();
		});
		
		$(document).on("touchstart",function(e) {
			if (e.touches.length >1) e.preventDefault();
		});
		
		/**
		 * 단축키
		 */
		$(document).on("keydown",function(e) {
			if (e.keyCode == 27) {
				iModule.modal.close(true);
			}
		});
	});
	
	/**
	 * postMessage 이벤트처리
	 */
	$(window).on("message",function(e) {
		if (e.originalEvent.data) {
			var event = e.originalEvent.data.event;
			
			if (event == "init") {
				var url = e.originalEvent.data.url;
				
				var parser = document.createElement("a");
				parser.href = url;
				
				var $iframe = $("iframe[src^='"+parser.href+"']");
				$iframe = $iframe.length == 0 ? $("iframe[src^='"+decodeURIComponent(parser.href)+"']") : $iframe;
				$iframe = $iframe.length == 0 ? $("iframe[src^='"+parser.pathname+"']") : $iframe;
				$iframe = $iframe.length == 0 ? $("iframe[src^='"+decodeURIComponent(parser.pathname)+"']") : $iframe;
				$iframe = $iframe.length == 0 ? $("iframe[src^='/"+parser.pathname+"']") : $iframe;
				$iframe = $iframe.length == 0 ? $("iframe[src^='/"+decodeURIComponent(parser.pathname)+"']") : $iframe;
				
				if ($iframe.length > 0) {
					$iframe.triggerHandler("init");
				}
			} else {
				var id = e.originalEvent.data.id;
				var data = e.originalEvent.data.data;
				var $iframe = $("#"+id);
				if ($iframe.length == 0) return;
				$iframe.triggerHandler(event,data);
			}
		}
	});
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