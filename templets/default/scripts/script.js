function TogglePush(button) {
	if ($(button).hasClass("opened") == true) {
		$(button).removeClass("opened");
		$(button).find("li.item, li.loading").remove();
		$(button).find("ul").append($("<li>").addClass("loading"));
	} else {
		$(button).addClass("opened");
		
		var list = $(button).find("ul");
		Push.getRecently(10,function(result) {
			list.find(".loading").remove();
			for (var i=0, loop=result.lists.length;i<loop;i++) {
				var item = $("<li>").addClass("item").addClass(result.lists[i].is_read ? "readed" : "unread").attr("data-push","true").attr("data-module",result.lists[i].module).attr("data-code",result.lists[i].code).attr("data-fromcode",result.lists[i].fromcode);
				if (result.lists[i].link == null) {
					item.addClass("notarget");
				} else {
					item.attr("data-link",result.lists[i].link);
				}
				item.on("click",function(e) {
					Push.read($(this));
					e.stopPropagation();
				});
				if (result.lists[i].image !== null) {
					item.append($("<div>").addClass("image").append($("<img>").attr("src",result.lists[i].image)));
				}
				item.append($("<div>").addClass("content").html(result.lists[i].content));
				list.append(item);
			}
			
			if (result.lists.length == 0) {
				$(button).find("li.noitem").show();
			} else {
				$(button).find("a").css("display","block");
			}
		});
	}
}

$(document).ready(function() {
	$("#iModuleSlideMenu .fa-chevron-up, #iModuleSlideMenu .fa-chevron-down").on("click",function(e) {
		var arrow = $(this);
		var list = $(this).parents("li");
		var subpage = list.find("ul");
			
		if (list.hasClass("opened") == true) {
			var height = subpage.height("auto").height();
			subpage.animate({height:0},{step:function(step) {
				arrow.rotate(180-step/height*180);
				
				if (step == 0) {
					list.removeClass("opened");
					arrow.rotate(0);
				}
			}});
		} else {
			subpage.show();
			var height = subpage.height("auto").height();
			subpage.height(0);
			
			subpage.animate({height:height},{step:function(step) {
				arrow.rotate(step/height*180);
				
				if (step == height) {
					list.addClass("opened");
					arrow.rotate(0);
				}
			}});
		}
		e.preventDefault();
	});
	
	$("div[role=tab] li").on("click",function() {
		var toggleOn = $(this).parents("ul").find(".selected");
		toggleOn.removeClass("selected");
		$("div[role=tabpanel][data-toggle="+toggleOn.attr("data-toggle")+"]").hide();
		
		$(this).addClass("selected");
		$("div[role=tabpanel][data-toggle="+$(this).attr("data-toggle")+"]").show();
	});
	
	$("div[role=tab][data-type=mouseover] li").on("mouseover",function() {
		var toggleOn = $(this).parents("ul").find(".selected");
		toggleOn.removeClass("selected");
		$("div[role=tabpanel][data-toggle="+toggleOn.attr("data-toggle")+"]").hide();
		
		$(this).addClass("selected");
		$("div[role=tabpanel][data-toggle="+$(this).attr("data-toggle")+"]").show();
	});
	
	$(".rightFixed").attr("data-width",$(".rightFixed").width());
	
	var googleTimeout = null;
	$(window).on("resize",function() {
		if ($(".rightFixed").attr("data-width") != $(".rightFixed").width()) {
			if (googleTimeout != null) {
				clearTimeout(googleTimeout);
				googleTimeout = null;
			}
			googleTimeout = setTimeout(ReprintGoogle,500);
		};
		ScrollFixed();
	});
	
	function ReprintGoogle() {
		$(".rightFixed").attr("data-width",$(".rightFixed").width());
		$(".rightFixedInner").css("width",$(".rightFixed").width());
		var html = $(".rightFixedInner").data("html");
		$(".rightFixedInner").empty();
		$(".rightFixedInner").html(html);
	}
	
	var scrollTimeout = null;
	$(document).on("scroll",function() {
		ScrollFixed();
	});
	
	function ScrollFixed() {
		if ($(".rightFixed").length == 0) return;
		var startPosition = $(document).scrollTop() + $("#iModuleNavigation.fixed").height() + 10;
		if (startPosition > $(".rightFixed").offset().top) {
			$(".rightFixedInner").css("position","fixed");
			$(".rightFixedInner").css("width",$(".rightFixedInner").width());
			$(".rightFixedInner").css("top",$("#iModuleNavigation.fixed").height() + 10);
			$(".rightFixedInner").css("left",$(".rightFixed").offset().left);
			
			if (startPosition + $(".rightFixedInner").height() - 30 > $(".footer").offset().top) {
				$(".rightFixedInner").css("position","absolute");
				$(".rightFixedInner").css("top",$(".footer").offset().top - $(".rightFixed").offset().top - $(".rightFixedInner").height() + 30);
				$(".rightFixedInner").css("left",0);
			} else {
				$(".rightFixedInner").css("position","fixed");
				$(".rightFixedInner").css("top",$("#iModuleNavigation.fixed").height() + 10);
				$(".rightFixedInner").css("left",$(".rightFixed").offset().left);
			}
		} else {
			$(".rightFixedInner").css("position","static");
			$(".rightFixedInner").css("top","");
			$(".rightFixedInner").css("left","");
		}
	}
});