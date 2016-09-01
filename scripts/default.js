var iModule = {
	addLanguage:function(module,lang,oLang) {
		if (window[module] === undefined) window[module] = {};
		window[module]._LANG = lang;
		window[module]._OLANG = oLang;
		window[module].getLanguage = function(code) {
			var temp = code.split("/");
			if (temp.length == 1) {
				return this._LANG[code] ? this._LANG[code] : (this._OLANG != null && this._OLANG[code] ? this._OLANG[code] : code);
			} else {
				var string = this._LANG;
				for (var i=0, loop=temp.length;i<loop;i++) {
					if (string[temp[i]]) {
						string = string[temp[i]];
					} else {
						string = null;
						break;
					}
				}
				
				if (string != null) return string;
				if (this._OLANG == null) return code;
				
				if (string == null && this._OLANG != null) {
					var string = this._OLANG;
					for (var i=0, loop=temp.length;i<loop;i++) {
						if (string[temp[i]]) string = string[temp[i]];
						else return code;
					}
				}
				
				return string;
			}
		};
	},
	getGeocode:function(address,callback) {
		$.ajax({
			type:"GET",
			url:"https://maps.googleapis.com/maps/api/geocode/json",
			data:{address:address,key:"AIzaSyDqaLBrAx88S39kf3wC571vH0kXGr_9SQo"},
			dataType:"json",
			success:function(result) {
				if (result.results.length == 0) {
					callback(null);
				} else {
					callback(result.results[0]);
				}
			},
			error:function() {
				iModule.alertMessage.show("error","Server Connect Error!",5);
			}
		});
	},
	getFileSize:function(fileSize,isKiB) {
		var isKiB = isKiB === true;
		var depthSize = isKiB == true ? 1024 : 1000;
		
		fileSize = parseInt(fileSize);
		return depthSize > fileSize ? fileSize+"B" : depthSize * depthSize > fileSize ? (fileSize/depthSize).toFixed(2)+(isKiB == true ? "KiB" : "KB") : depthSize * depthSize * depthSize > fileSize ? (fileSize/depthSize/depthSize).toFixed(2)+(isKiB == true ? "MiB" : "MB") : (fileSize/depthSize/depthSize/depthSize).toFixed(2)+(isKiB == true ? "GiB" : "GB");
	},
	focusDelay:function(object,time) {
		if (time !== 0) {
			time = time ? time : 500;
			setTimeout(iModule.focusDelay,time,object,0);
			return;
		} else {
			object.focus();
		}
	},
	isInScroll:function(object) {
		return object.offset().top + 100 < $(window).height() + $(document).scrollTop() && object.offset().top - 100 > $(document).scrollTop();
	},
	selectFieldEvent:function() {
		var list = $(this).data("control").find("li[data-value='"+$(this).val()+"']");
		if (list.length > 0) {
			$(this).data("control").find("button").html(list.html()+' <span class="arrow"></span>');
		}
	},
	getNumberFormat:function(number,round_decimal) {
		var number = parseInt(number);
		return number.toFixed(round_decimal).replace(/(\d)(?=(\d{3})+$)/g, "$1,");
	},
	initSelectControl:function(selectControl) {
		if (selectControl.is("div") == false) return;
		if (selectControl.attr("data-field").indexOf("#") == 0) {
			var selectField = $(selectControl.attr("data-field"));
		} else {
			var selectField = selectControl.parents("form").find("input[name="+selectControl.attr("data-field")+"]");
		}
		selectControl.data("field",selectField);
		selectField.data("control",selectControl);
		
		var selectValue = selectField.val();
		
		if (selectValue.length > 0 && selectControl.find("li[data-value='"+selectValue+"']").length > 0) {
			selectControl.find("button").html(selectControl.find("li[data-value='"+selectValue+"']").html()+' <span class="arrow"></span>');
		}
		
		selectField.off("change",iModule.selectFieldEvent);
		selectField.on("change",iModule.selectFieldEvent);
	
		selectControl.find("button").attr("type","button");
		selectControl.find("button").off("click");
		selectControl.find("button").on("click",function(event) {
			if ($(this).parents("div.selectControl").hasClass("selectControlExtend") == true) {
				$(this).parents("div.selectControl").removeClass("selectControlExtend");
				$(this).parents("div.selectControl").find("li:not(.divider):visible").attr("tabindex",null);
			} else {
				$(this).parents("div.selectControl").addClass("selectControlExtend");
				if ($(this).parents("div.selectControl").attr("value") !== undefined) {
					$(this).parents("div.selectControl").find("li:not(.divider):visible").attr("tabindex",1);
					iModule.focusDelay($(this).parents("div.selectControl").find("li[data-value='"+$(this).parents("div.selectControl").attr("value")+"']"),100);
				}
			}
			$(this).focus();
			event.preventDefault();
		});
		
		selectControl.find("button").off("keydown");
		selectControl.find("button").on("keydown",function(event) {
			if (event.keyCode == 38 || event.keyCode == 40 || event.keyCode == 27) {
				event.preventDefault();
				if ($(this).parents("div.selectControl").hasClass("selectControlExtend") == false || ($(this).parents("div.selectControl").hasClass("selectControlExtend") == true && event.keyCode == 27)) {
					return $(this).click();
				}
				
				var items = $(this).parents("div.selectControl").find("li:not(.divider):visible").attr("tabindex",1);
				if (items.length == 0) return;
				
				var index = items.index(items.filter(":focus"));
	
				if (event.keyCode == 38 && index > 0) index--;
				if (event.keyCode == 40 && index < items.length - 1) index++;
				if (!~index) index = 0;
				
				$(items).eq(index).focus();
			}
		});
		
		selectControl.find("ul > li").off("keydown");
		selectControl.find("ul > li").on("keydown",function(event) {
			event.preventDefault();
			
			if (event.keyCode == 38 || event.keyCode == 40 || event.keyCode == 27) {
				if ($(this).parents("div.selectControl").length == 0 || ($(this).parents("div.selectControl").hasClass("selectControlExtend") == true && event.keyCode == 27)) {
					return $($(this).parents("div.selectControl").find("button")).click();
				}
				
				var items = $(this).parents("div.selectControl").find("li:not(.divider):visible");
	
				if (items.length == 0) return;
				
				var index = items.index(items.filter(":focus"));
	
				if (event.keyCode == 38 && index > 0) index--;
				if (event.keyCode == 40 && index < items.length - 1) index++;
				if (!~index) index = 0;
				
				$(items).eq(index).focus();
				event.preventDefault();
			}
			
			if (event.keyCode == 13) {
				var items = $(this).parents("div.selectControl").find("li:not(.divider):visible");
				var index = items.index(items.filter(":focus"));
				if (!~index) return;
				
				$(items).eq(index).click();
				event.preventDefault();
			}
		});
		
		selectControl.find("ul > li").off("keyword");
		selectControl.find("ul > li").on("click",function(event) {
			if ($(this).hasClass("divider") == true) return;
			
			$(this).parents("div.selectControl").data("field").val($(this).attr("data-value"));
			$(this).parents("div.selectControl").data("field").triggerHandler("change");
			
			$($(this).parents("div.selectControl").find("button")).click();
			$($(this).parents("div.selectControl").find("button")).focus();
		});
	},
	inputStatus:function(object,status,message) {
		if (object.is("form") == true) object = object.find("input, textarea");
		if (object.is("input, textarea") == false) return;
		if (object.parents(".inputBlock, .inputInline").length == 0) return;
		
		if (object.length > 1) {
			for (var i=0, loop=object.length;i<loop;i++) {
				iModule.inputStatus($(object[i]),status,message);
			}
		} else {
			var inputBlock = object.parents(".inputBlock, .inputInline");
			var helpBlock = inputBlock.find(".helpBlock");
			
			inputBlock.removeClass("hasSuccess hasError");
			if (status == "success") {
				inputBlock.addClass("hasSuccess");
				
				if (helpBlock.length == 1) {
					if (message !== undefined) helpBlock.html(message);
					else if (helpBlock.attr("data-success") !== undefined) helpBlock.html(helpBlock.attr("data-success"));
				}
			} else if (status == "error") {
				inputBlock.addClass("hasError");
				if (helpBlock.length == 1) {
					if (message !== undefined) helpBlock.html(message);
					else if (helpBlock.attr("data-error") !== undefined) helpBlock.html(helpBlock.attr("data-error"));
				} else if (message !== undefined) {
					iModule.alertMessage.show("error",message,5);
				}
			} else if (status == "default") {
				if (message !== undefined) helpBlock.html(message);
				else if (helpBlock.attr("data-default") !== undefined) helpBlock.html(helpBlock.attr("data-default"));
			}
		}
	},
	buttonStatus:function(object,status) {
		if (object.is("button") == true) var button = object;
		else if (object.is("form") == true) var button = object.find("button[type=submit]");
		else return;
		if (button.length == 0) return;
		
		if (status == "loading") {
			button.data("default",button.html());
			var text = '<i class="fa fa-spin fa-spinner"></i>';
			if (button.attr("data-loading") !== undefined) text+= ' '+button.attr("data-loading");
			else text+= ' Loading...';
			button.html(text).attr("disabled",true);
		} else if (status == "reset") {
			if (button.data("default") !== undefined) {
				button.html(button.data("default"));
			}
			button.attr("disabled",false);
		}
	},
	alertMessage:{
		idx:0,
		show:function(type,message,timer) {
			if ($("#iModuleAlertMessage").length == 0) {
				alert(message);
			} else {
				var idx = iModule.alertMessage.idx;
				var item = $("<div>").attr("id","iModuleAlertMessageItem-"+iModule.alertMessage.idx).addClass(type).addClass("message").css("display","none");
				item.html(message);
				var close = $("<div>").addClass("close").append($("<i>").addClass("fa fa-times-circle"));
				close.data("idx",idx);
				close.on("click",function() {
					iModule.alertMessage.close($(this).data("idx"));
				});
				item.append(close);
				
				$("#iModuleAlertMessage").append(item);
				iModule.alertMessage.idx++;
				
				iModule.alertMessage.slideDown(idx);
				setTimeout(iModule.alertMessage.close,timer * 1000,idx);
			}
		},
		slideDown:function(idx) {
			$("#iModuleAlertMessageItem-"+idx).slideDown();
		},
		close:function(idx) {
			if ($("#iModuleAlertMessageItem-"+idx).length > 0) {
				$("#iModuleAlertMessageItem-"+idx).find(".close").css("visibility","hidden");
				$("#iModuleAlertMessageItem-"+idx).width($("#iModuleAlertMessageItem-"+idx).width());
				$("#iModuleAlertMessageItem-"+idx).animate({marginLeft:-$("#iModuleAlertMessageItem-"+idx).outerWidth(true),opacity:0},"",function() {
					$(this).remove();
				});
			}
		},
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
	},
	modal:{
		modal:null,
		init:function() {
			if (iModule.modal.modal == null) return;
			
			var inputs = $("input[type!=hidden],div.selectControl > button",iModule.modal.modal);
			if (inputs.length > 0) {
				inputs[0].focus();
			} else {
				$("button[type=submit]",iModule.modal.modal).focus();
			}
		},
		enable:function() {
			var scroll = parseInt($("#iModuleWrapper").css("marginTop").replace("px","")) * -1;
			
			$("#iModuleWindowDisabled").remove();
			$("#iModuleWrapper").css("paddingLeft",0);
			$("body").css("overflow","auto");
			$("#iModuleWrapper").css("position","static");
			$("#iModuleWrapper").css("marginTop",0);
			
			$(document).scrollTop(scroll);
			
			if ($("#iModuleNavigation.fixed").length == 1) {
				$("#iModuleNavigation.fixed").css("left",0);
			}
			
			$(document).triggerHandler("modal.enable");
		},
		loading:function(text,disableNavigation,callback) {
			iModule.modal.disable(disableNavigation,callback);
			$("#iModuleWindowDisabled").empty();
			
			var $loading = $("<div>").css("width","100%").css("height","100%").css("display","table").append($("<div>").css("display","table-cell").css("verticalAlign","middle").css("textAlign","center").css("fontSize","13px").css("fontWeight","bold").css("color","#fff").html('<i class="fa fa-spin fa-spinner" style="font-size:24px;"></i>'+(text ? "<br><br>"+text : "")));
			$("#iModuleWindowDisabled").append($loading);
		},
		disable:function(disableNavigation,callback) {
			if ($("#iModuleWindowDisabled").length == 0) {
				$("#iModuleWrapper").append($("<div>").attr("id","iModuleWindowDisabled"));
			}
			
			if (typeof callback == "function") {
				$("#iModuleWindowDisabled").on("click",callback);
			}
			
			if (disableNavigation == true) {
				$("#iModuleWindowDisabled").css("zIndex",3000);
			} else {
				$("#iModuleWindowDisabled").css("zIndex",1000);
			}
			
			var width = $(document).width();
			var scroll = $(document).scrollTop();
			
			$("body").css("overflow","hidden");
			$("#iModuleWrapper").css("marginTop",-scroll).css("position","fixed");
			$("#iModuleWrapper").css("width","100%");
			
			if (disableNavigation == true) {
				if ($(document).width() - width > 0) {
					$("body").css("overflowY","scroll");
				} else {
					$("#iModuleWrapper").css("paddingLeft",$(document).width() - width);
				}
			}
			
			if ($("#iModuleNavigation.fixed").length == 1) {
				$("#iModuleNavigation.fixed").css("left",-($(document).width() - width)/2);
			}
			$("#iModuleWindowDisabled").height($(window).height());
			
			$(document).triggerHandler("modal.disable",[disableNavigation,$(document).width() - width]);
		},
		show:function(title,contentHtml,submit,buttons) {
			iModule.modal.disable(true);
			
			iModule.modal.modal = $("<div>").addClass("modal").attr("data-role","modal");
			
			var header = $("<header>").html(title);
			var close = $("<i>").addClass("fa fa-times");
			close.on("click",function() {
				iModule.modal.close();
			});
			header.prepend(close);
			
			iModule.modal.modal.append(header);
			
			var content = $("<div>").addClass("content");
			content.html(contentHtml);
			content.find("img").each(function() {
				$(this).on("load",function() {
					if ($(this).parents(".content").width() < $(this).width()) {
						$(this).width($(this).parents(".content").width());
					}
					iModule.modal.center();
				});
			});
			
			iModule.modal.modal.append(content);
			
			var button = $("<div>").addClass("button");
			if ($.isArray(buttons) == true) {
				for (var i=0, loop=buttons.length;i<loop;i++) {
					var item = $("<button>").addClass(buttons[i].style ? buttons[i].style : "default").html(buttons[i].text);
					item.on("click",buttons[i].click);
					button.append($("<div>").append(item));
				}
			}
			
			var submitButton = $("<button>").addClass(submit.style ? submit.style : "submit").html(submit.text);
			if (submit.type) submitButton.attr("type",submit.type);
			submitButton.on("click",submit.click);
			button.append($("<div>").append(submitButton));
			
			iModule.modal.modal.append(button);
			
			$("#iModuleWindowDisabled").append(iModule.modal.modal);
			iModule.modal.modal.data("width",iModule.modal.modal.outerWidth());
			
			if ($("#iModuleWindowDisabled").innerWidth() > iModule.modal.modal.data("width")) {
				iModule.modal.modal.outerWidth(iModule.modal.modal.data("width"));
			} else {
				iModule.modal.modal.outerWidth($("#iModuleWindowDisabled").innerWidth() - 20);
			}
			
			if ($("#iModuleWindowDisabled").innerHeight() > iModule.modal.modal.outerHeight() + 40) {
				iModule.modal.modal.css("marginTop",($("#iModuleWindowDisabled").innerHeight() - iModule.modal.modal.outerHeight()) / 2);
			} else {
				iModule.modal.modal.css("margin","20px auto");
				$("#iModuleWindowDisabled").css("overflowY","scroll");
			}
			
			iModule.modal.init();
			$(document).triggerHandler("modal.show",iModule.modal.modal);
		},
		showForm:function(title,contentHtml,submit,buttons) {
			iModule.modal.disable(true);
			
			iModule.modal.modal = $("<div>").addClass("modal").attr("data-role","modal");
			
			var header = $("<header>").html(title);
			var close = $("<i>").addClass("fa fa-times");
			close.on("click",function() {
				iModule.modal.close();
			});
			header.prepend(close);
			
			iModule.modal.modal.append(header);
			
			var content = $("<div>").addClass("content");
			content.html(contentHtml);
			content.find("img").each(function() {
				$(this).on("load",function() {
					if ($(this).parents(".content").width() < $(this).width()) {
						$(this).width($(this).parents(".content").width());
					}
					iModule.modal.center();
				});
			});
			
			iModule.modal.modal.append(content);
			
			var button = $("<div>").addClass("button");
			if ($.isArray(buttons) == true) {
				for (var i=0, loop=buttons.length;i<loop;i++) {
					var item = $("<button>").addClass(buttons[i].style ? buttons[i].style : "default").html(buttons[i].text);
					item.on("click",buttons[i].click);
					button.append($("<div>").append(item));
				}
			}
			
			var submitButton = $("<button>").attr("type","submit").addClass(submit.style ? submit.style : "submit").html(submit.text);
			button.append($("<div>").append(submitButton));
			
			iModule.modal.modal.append(button);
			
			var form = $("<form>");
			form.on("submit",function() {
				submit.fn($(this));
				return false;
			});
			form.append(iModule.modal.modal);
			$("#iModuleWindowDisabled").append(form);
			iModule.modal.modal.data("width",iModule.modal.modal.outerWidth());
			
			if ($("#iModuleWindowDisabled").innerWidth() > iModule.modal.modal.data("width")) {
				iModule.modal.modal.outerWidth(iModule.modal.modal.data("width"));
			} else {
				iModule.modal.modal.outerWidth($("#iModuleWindowDisabled").innerWidth() - 20);
			}
			
			if ($("#iModuleWindowDisabled").innerHeight() > iModule.modal.modal.outerHeight() + 40) {
				iModule.modal.modal.css("marginTop",($("#iModuleWindowDisabled").innerHeight() - iModule.modal.modal.outerHeight()) / 2);
			} else {
				iModule.modal.modal.css("margin","20px auto");
				$("#iModuleWindowDisabled").css("overflowY","scroll");
			}
			
			iModule.modal.init();
			$(document).triggerHandler("modal.show",[iModule.modal.modal]);
		},
		showHtml:function(html) {
			iModule.modal.disable(true);
			
			$("#iModuleWindowDisabled").html(html);
			
			$("#iModuleWindowDisabled").find("img").each(function() {
				$(this).on("load",function() {
					if ($(this).parents(".content").width() < $(this).width()) {
						$(this).width($(this).parents(".content").width());
					}
					iModule.modal.center();
				});
			});
			
			iModule.modal.modal = $("#iModuleWindowDisabled *[data-role=modal]");
			iModule.modal.modal.css("margin","0px auto");
			iModule.modal.modal.data("width",iModule.modal.modal.outerWidth());
			
			if ($("#iModuleWindowDisabled").innerWidth() > iModule.modal.modal.data("width")) {
				iModule.modal.modal.outerWidth(iModule.modal.modal.data("width"));
			} else {
				iModule.modal.modal.outerWidth($("#iModuleWindowDisabled").innerWidth() - 20);
			}
			
			if ($("#iModuleWindowDisabled").innerHeight() > iModule.modal.modal.outerHeight() + 40) {
				iModule.modal.modal.css("marginTop",($("#iModuleWindowDisabled").innerHeight() - iModule.modal.modal.outerHeight()) / 2);
			} else {
				iModule.modal.modal.css("margin","20px auto");
				$("#iModuleWindowDisabled").css("overflowY","scroll");
			}
			
			iModule.modal.init();
			$(document).triggerHandler("modal.show",iModule.modal.modal);
		},
		center:function() {
			if (iModule.modal.modal == null) return;
			
			if ($("#iModuleWindowDisabled").innerWidth() > iModule.modal.modal.data("width")) {
				iModule.modal.modal.outerWidth(iModule.modal.modal.data("width"));
			} else {
				iModule.modal.modal.outerWidth($("#iModuleWindowDisabled").innerWidth() - 20);
			}
			
			if ($("#iModuleWindowDisabled").innerHeight() > iModule.modal.modal.outerHeight() + 40) {
				iModule.modal.modal.css("marginTop",($("#iModuleWindowDisabled").innerHeight() - iModule.modal.modal.outerHeight()) / 2);
			} else {
				iModule.modal.modal.css("margin","20px auto");
				$("#iModuleWindowDisabled").css("overflowY","scroll");
			}
		},
		close:function() {
			if (iModule.modal.modal == null) return;
			
			$(document).triggerHandler("modal.close",iModule.modal.modal);
			
			iModule.modal.modal.remove();
			iModule.modal.enable();
		}
	},
	slideMenu:{
		hide:function() {
			if ($("#iModuleSlideMenu").is(":visible") == true) {
				iModule.slideMenu.toggle(false);
				$(document).triggerHandler("slideMenu.hide",$("#iModuleSlideMenu"));
			}
		},
		toggle:function(animate) {
			if ($("#iModuleSlideMenu").length == 0) return;
			
			if ($("#iModuleSlideMenu").is(":visible") == true) {
				if (animate == true) {
					$("#iModuleSlideMenu").animate({right:-$("#iModuleSlideMenu").outerWidth(true)},{step:function(now) {
						$("#iModuleWrapper").css("left",-($("#iModuleSlideMenu").outerWidth(true)+now));
						
						if ($("#iModuleNavigation.fixed").length == 1) {
							$("#iModuleNavigation").css("left",-($("#iModuleSlideMenu").outerWidth(true)+now));
						}
						
						$(document).triggerHandler("slideMenu.animate",now);
						
						if (now == -$("#iModuleSlideMenu").outerWidth(true)) {
							iModule.modal.enable();
							$("#iModuleSlideMenu").hide();
							$(document).triggerHandler("slideMenu.hide",$("#iModuleSlideMenu"));
						}
					}});
				} else {
					$("#iModuleSlideMenu").css("right",-$("#iModuleSlideMenu").outerWidth(true));
					$("#iModuleWrapper").css("left",0);
					if ($("#iModuleNavigation.fixed").length == 1) {
						$("#iModuleNavigation").css("left",0);
					}
					iModule.modal.enable();
					$("#iModuleSlideMenu").hide();
					$(document).triggerHandler("slideMenu.hide",$("#iModuleSlideMenu"));
				}
			} else {
				$("#iModuleSlideMenu").show();
				$("#iModuleSlideMenu").css("right",-$("#iModuleSlideMenu").outerWidth(true));
				
				iModule.modal.disable(false,function() { iModule.slideMenu.toggle(true); });
				
				$("#iModuleSlideMenu").height($(window).height());
				
				if (animate == true) {
					$("#iModuleSlideMenu").animate({right:0},{step:function(now) {
						$("#iModuleWrapper").css("left",-($("#iModuleSlideMenu").outerWidth(true)+now));
						if ($("#iModuleNavigation.fixed").length == 1) {
							$("#iModuleNavigation").css("left",-($("#iModuleSlideMenu").outerWidth(true)+now));
						}
						$(document).triggerHandler("slideMenu.animate",now);
						if (now == $("#iModuleSlideMenu").outerWidth(true)) {
							$(document).triggerHandler("slideMenu.show",$("#iModuleSlideMenu"));
						}
					}});
				} else {
					$("#iModuleSlideMenu").css("right",0);
					$("#iModuleWrapper").css("left",-$("#iModuleSlideMenu").outerWidth(true));
					if ($("#iModuleNavigation.fixed").length == 1) {
						$("#iModuleNavigation").css("left",-$("#iModuleSlideMenu").outerWidth(true));
					}
					$(document).triggerHandler("slideMenu.show",$("#iModuleSlideMenu"));
				}
			}
		},
		resize:function() {
			if ($("#iModuleSlideMenu").is(":visible") == false) return;
			$("#iModuleSlideMenu").css("right",-$("#iModuleSlideMenu").outerWidth(true));
			
			$("#iModuleSlideMenu").height($(window).height());
			
			$("#iModuleSlideMenu").css("right",0);
			$("#iModuleWrapper").css("left",-$("#iModuleSlideMenu").outerWidth(true));
			if ($("#iModuleNavigation.fixed").length == 1) {
				$("#iModuleNavigation").css("left",-$("#iModuleSlideMenu").outerWidth(true));
			}
			$(document).triggerHandler("slideMenu.show",$("#iModuleSlideMenu"));
		}
	},
	openPopup:function(url,width,height,scroll,name) {
		var windowLeft = (screen.width - width) / 2;
		var windowTop = (screen.height - height) / 2;
		windowTop = windowTop > 20 ? windowTop - 20 : windowTop;
		var opener = window.open("", name !== undefined ? name : "", "top=" + windowTop + ",left=" + windowLeft + ",width=" + width + ",height=" + height + ",scrollbars=" + (scroll == true ? "1" : "0"));
		
		if (opener) {
			setTimeout(iModule.resizePopup,500,opener,url,width,height);
		}
	},
	resizePopup:function(popup,url,width,height) {
		var resizeWidth = width - $(popup.window).width();
		var resizeHeight = height - $(popup.window).height();
		
		popup.window.resizeBy(resizeWidth,resizeHeight);
		popup.location.href = url;
	}
};

$(document).ready(function() {
	$("span.iModuleEmail").each(function() {
		$(this).css("cursor","pointer");
		$(this).on("click",function() {
			var temp = $(this).html().split('<i class="fa fa-at"></i>');
			location.href = "mailto:"+temp[0]+"@"+temp[1];
		});
	});
	
	if ($("#iModuleHeader").length == 1 && $("#iModuleNavigation").length == 1) {
		if ($("#iModuleHeader").is(":visible") == false || $(document).scrollTop() > $("#iModuleHeader").outerHeight(true)) {
			$("#iModuleNavigation").addClass("fixed");
//			$("#iModuleWrapper").css("paddingTop",$("#iModuleNavigation").outerHeight(true));
			$("#iModuleAlertMessage").css("top",$("#iModuleNavigation").outerHeight(true));
		} else {
			$("#iModuleNavigation").removeClass("fixed");
//			$("#iModuleWrapper").css("paddingTop",0);
			$("#iModuleAlertMessage").css("top",0);
		}
	}
	
	$("input, textarea").on("focus",function() {
		if ($("#iModuleNavigation").hasClass("fixed") == false) return;
		
		if (iModule.isInScroll($(this)) == false) {
			if ($(this).offset().top < $(document).scrollTop() + $("#iModuleNavigation.fixed").outerHeight(true) + 10) {
				$(document).scrollTop($(this).offset().top - $("#iModuleNavigation.fixed").outerHeight(true) - 10);
			}
		}
	});
	
	$("#iModuleSlideMent a").bind("touchstart touchend", function(e) {
		$(this).toggleClass("touch");
	});

	$(".selectControl").each(function() {
		$(this).selectInit();
//		iModule.initSelectControl($(this));
	});
	
	$("body").on("click",function(event) {
		if ($(event.target).parents("div.selectControl").length == 0) {
			$("div.selectControl").removeClass("selectControlExtend");
		}
	});
	/*
	$(".wrapContent img").each(function() {
		$(this).width("auto");
		console.log("IN",$(this).width());
		if ($(this).width() > 0 && $(this).parents(".wrapContent").innerWidth() < $(this).width()) {
			$(this).width($(this).parents(".wrapContent").innerWidth());
		}
	});
	*/
	$(".wrapContent img").on("load",function() {
		if ($(this).width() > 0) {
			$(this).data("width",$(this).width());
			$(this).data("height",$(this).height());
		}
		if ($(this).parents(".wrapContent").innerWidth() < $(this).width()) {
			$(this).width($(this).parents(".wrapContent").innerWidth());
			$(this).height(Math.round($(this).width() * $(this).data("height") / $(this).data("width")));
		}
	});
	
	$(".wrapContent iframe").each(function() {
		$(this).width("100%").height(9 * $(this).parents(".wrapContent").innerWidth() / 16);
	});
	
	$(document).on("scroll",function() {
		if ($("#iModuleHeader").length == 1 && $("#iModuleNavigation").length == 1) {
			if ($("#iModuleWindowDisabled").is(":visible") == true) return;
			
			if ($("#iModuleHeader").is(":visible") == false || $(document).scrollTop() > $("#iModuleHeader").outerHeight(true)) {
				$("#iModuleNavigation").addClass("fixed");
//				$("#iModuleWrapper").css("paddingTop",$("#iModuleNavigation").outerHeight(true));
				$("#iModuleAlertMessage").css("top",$("#iModuleNavigation").outerHeight(true));
			} else {
				$("#iModuleNavigation").removeClass("fixed");
//				$("#iModuleWrapper").css("paddingTop",0);
				$("#iModuleAlertMessage").css("top",0);
			}
		}
	});
	
	$(window).on("resize",function() {
		iModule.slideMenu.resize();
		
		if ($("#iModuleHeader").length == 1 && $("#iModuleNavigation").length == 1) {
			if ($("#iModuleWindowDisabled").is(":visible") == true) return;
			
			if ($("#iModuleHeader").is(":visible") == false || $(document).scrollTop() > $("#iModuleHeader").outerHeight(true)) {
				$("#iModuleNavigation").addClass("fixed");
//				$("#iModuleWrapper").css("paddingTop",$("#iModuleNavigation").outerHeight(true));
			} else {
				$("#iModuleNavigation").removeClass("fixed");
//				$("#iModuleWrapper").css("paddingTop",0);
			}
		}
		
		if ($("#iModuleWindowDisabled").is(":visible") == true) {
			$("#iModuleWindowDisabled").height($(window).height());
			
			if (iModule.modal.modal != null) {
				if ($("#iModuleWindowDisabled").innerWidth() > iModule.modal.modal.data("width")) {
					iModule.modal.modal.outerWidth(iModule.modal.modal.data("width"));
				} else {
					iModule.modal.modal.outerWidth($("#iModuleWindowDisabled").innerWidth() - 20);
				}
				
				if ($("#iModuleWindowDisabled").innerHeight() > iModule.modal.modal.outerHeight() + 40) {
					iModule.modal.modal.css("marginTop",($("#iModuleWindowDisabled").innerHeight() - iModule.modal.modal.outerHeight()) / 2);
				} else {
					iModule.modal.modal.css("margin","20px auto");
					$("#iModuleWindowDisabled").css("overflowY","scroll");
				}
			}
		}
		
		var wrapImages = $(".wrapContent img");
		for (var i=0, loop=wrapImages.length;i<loop;i++) {
			var image = $(wrapImages[i]);
			if (image.data("width") !== undefined && image.parents(".wrapContent").innerWidth() < image.data("width")) {
				image.width(image.parents(".wrapContent").innerWidth());
				image.height(Math.round(image.width() * image.data("height") / image.data("width")));
			} else {
				image.width(image.data("width")).height(image.data("height"));
			}
		}
		
		var wrapIframes = $(".wrapContent iframe");
		for (var i=0, loop=wrapIframes.length;i<loop;i++) {
			$(wrapIframes[i]).width("100%").height(9 * iframe.parents(".wrapContent").innerWidth() / 16);
		}
	});
});

/* jQuery iModule plugin */
(function($) {
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
})(jQuery);

$(document).ready(function() {
	$("span.moment[data-time][data-format][data-moment]").each(function() {
		$(this).html(moment.unix($(this).attr("data-time")).locale($("html").attr("lang")).format($(this).attr("data-moment")));
	});
	
	$(document).on("dragenter",function(e) {
//		console.log("dragenter",e);
	});
	
	$(document).on("dragleave",function(e) {
//		console.log("dragenter",e);
	});
	
	$(document).on("dragover",function(e) {
		$("*[data-role=filedrop]").each(function() {
			if (!$(this).attr("data-status-before")) $(this).attr("data-status-before",$(this).attr("data-status"));
			$(this).attr("data-status","ready");
		});
		e.stopPropagation();
		e.preventDefault();
	});
	
	$(document).on("drop",function(e) {
		$("*[data-role=filedrop]").each(function() {
			$(this).attr("data-status",$(this).attr("data-status-init"));
			$(this).attr("data-status-before",null);
		});
		
		e.stopPropagation();
		e.preventDefault();
	});
	
	$(document).on("dragleave",function(e) {
		$("*[data-role=filedrop]").each(function() {
			if ($(this).attr("data-status") != "dragenter") {
				if ($(this).attr("data-status-before") && $(this).attr("data-status-before") != "dragenter") {
					$(this).attr("data-status",$(this).attr("data-status-before"));
					$(this).attr("data-status-before",null);
				} else {
					$(this).attr("data-status",$(this).attr("data-status-init"));
				}
			}
		});
	});
	
	$(document).on("keypress",function(e) {
		if ($("#iModuleWindowDisabled").is(":visible") == true) {
			if (iModule.modal.modal != null) {
				if (e.keyCode == 27) {
					iModule.modal.close();
				
					e.stopPropagation();
					e.preventDefault();
				}
				
				if (e.keyCode == 13) {
					if ($(e.target).is("input") == true || $(e.target).is("button") == true) {
						if ($("form",$("#iModuleWindowDisabled")).length == 1) {
							$("form",$("#iModuleWindowDisabled")).submit();
						}
						e.stopPropagation();
						e.preventDefault();
					}
				}
			}
			
			if ($(e.target).is("body") == true) {
				e.stopPropagation();
				e.preventDefault();
			}
		}
	});
	
	$(document).ajaxError(function() {
		iModule.alertMessage.show("error","Sever Connecting Error! Please try again.",5);
	});
});