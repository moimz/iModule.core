/**
 * 이 파일은 MoimzTools 의 일부입니다. (https://www.moimz.com)
 *
 * MoimzTools 에 포함된 ExtJS 라이브러리 기능을 확장하고 MoimzTools 에 맞게 재정의한다.
 * 
 * @file /scripts/extjs.extend.js
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 1.1.0
 * @modified 2021. 5. 28.
 */
Ext.Ajax.setTimeout(600000);
Ext.define("Ext.moimz.data.reader.Json",{override:"Ext.data.reader.Json",rootProperty:"lists",totalProperty:"total",messageProperty:"message"});
Ext.define("Ext.moimz.data.JsonStore",{override:"Ext.data.JsonStore",pageSize:0});
Ext.define("Ext.moimz.toolbar.Toolbar",{override:"Ext.toolbar.Toolbar",scrollable:"x",enableFocusableContainer:false});
Ext.define("Ext.moimz.data.proxy.Ajax",{override:"Ext.data.proxy.Ajax",timeout:60000});
Ext.define("Ext.moimz.PagingToolbar",{override:"Ext.PagingToolbar",inputItemWidth:60});
Ext.define("Ext.moimz.grid.column.Column",{override:"Ext.grid.column.Column",sortable:false,hideable:false,lockable:false,usermenu:false,
	beforeRender:function() {
		var me = this,
			rootHeaderCt = me.getRootHeaderCt(),
			isSortable = me.isSortable(),
			labels = [],
			ariaAttr;
 
		me.callParent();
		
		if (!me.usermenu && !me.requiresMenu && !isSortable && !me.groupable &&
				!me.lockable && (rootHeaderCt.grid.enableColumnHide === false ||
				!rootHeaderCt.getHideableColumns().length)) {
			me.menuDisabled = true;
		}
		
		if (me.usermenu == true) me.menuDisabled = false;
		if (me.cellWrap) {
			me.variableRowHeight = true;
		}
		
		if (me.menuDisabled === false && !isSortable) {
			// @todo sort 메뉴 제거
		}
		
		ariaAttr = me.ariaRenderAttributes || (me.ariaRenderAttributes = {});
		
		ariaAttr['aria-readonly'] = true;
		
		if (isSortable) {
			ariaAttr['aria-sort'] = me.ariaSortStates[me.sortState];
		}
		
		if (me.isSubHeader) {
			labels = me.getLabelChain();
			
			if (me.text) {
				labels.push(Ext.util.Format.stripTags(me.text));
			}
			
			if (labels.length) {
				ariaAttr['aria-label'] = labels.join(' ');
			}
		}
		
		me.protoEl.unselectable();
	}
});
Ext.define("Ext.moimz.grid.Panel",{override:"Ext.grid.Panel",columnLines:true,enableColumnMove:false});
Ext.define("Ext.moimz.selection.CheckboxModel",{override:"Ext.selection.CheckboxModel",headerWidth:30,checkOnly:false});
Ext.define("Ext.moimz.menu.Menu",{override:"Ext.menu.Menu",addTitle:function(title) {
	this.add('<div class="x-menu-title"><div>'+title+'</div></div>');
}});
Ext.define("Ext.moimz.form.Basic",{override:"Ext.form.Basic",scrollToFirstErrorField:function(form) {
	var form = form ? form : this;
	
	var invalid = form.getFields().filterBy(function(field) {
		return field.getActiveErrors().length > 0;
	});
	
	if (invalid.items.length > 0) {
		var topField = invalid.items.shift();
		var panel = form.owner;
		if (panel.getScrollable() != null) {
			var position = topField.getPosition()[1] - panel.getPosition()[1];
			var scroll = panel.getScrollable().getPosition().y;
			
			if (position < 50) {
				var scrollTo = Math.max(0,scroll - (50 - position));
				panel.scrollTo(0,scrollTo,true);
			}
			
			if (position + 50 > panel.getScrollable().getElement().getBox().height) {
				var scrollTo = position + scroll - panel.getScrollable().getElement().getBox().height + topField.getBox().height + 50;
				panel.scrollTo(0,scrollTo,true);
			}
		} else if (panel.ownerCt.scrollable != null) {
			var position = topField.getPosition()[1] - panel.ownerCt.getPosition()[1];
			var scroll = panel.ownerCt.getScrollable().getPosition().y;
			
			if (position < 50) {
				var scrollTo = Math.max(0,scroll - (50 - position));
				panel.ownerCt.scrollTo(0,scrollTo,true);
			}
			
			if (position + 50 > panel.ownerCt.getScrollable().getElement().getBox().height) {
				var scrollTo = position + scroll - panel.ownerCt.getScrollable().getElement().getBox().height + topField.getBox().height + 50;
				panel.ownerCt.scrollTo(0,scrollTo,true);
			}
		}
	}
}});
Ext.define("Ext.moimz.form.field.Display",{override:"Ext.form.field.Display",fieldSubTpl:[
	'<div id="{id}" data-ref="inputEl" role="textbox" aria-readonly="true"',
	' aria-labelledby="{cmpId}-labelEl" {inputAttrTpl}',
	' tabindex="<tpl if="tabIdx != null">{tabIdx}<tpl else>-1</tpl>"',
	'<tpl if="fieldStyle"> style="{fieldStyle}"</tpl>',
	' class="{fieldCls} {fieldCls}-{ui} x-selectable">{value}</div>',
	{compiled:true,disableFormats: true}
]});
Ext.define("Ext.moimz.form.Panel",{override:"Ext.form.Panel",trackResetOnLoad:true});
Ext.define("Ext.moimz.form.action.Action",{override:"Ext.form.action.Action",submitEmptyText:false});
Ext.define("Ext.moimz.form.action.Submit",{
	override:"Ext.form.action.Submit",
	run:function(){
		var me = this,
			form = me.form;
			
		if (me.clientValidation === false || form.isValid()) {
			me.doSubmit();
		} else {
			me.failureType = Ext.form.action.Action.CLIENT_INVALID;
			form.afterAction(me, false);
			
			form.scrollToFirstErrorField();
		}
	},
	onSuccess:function(response) {
		var form = this.form,
			formActive = form && !form.destroying && !form.destroyed,
			success = true,
			result = this.processResponse(response);
		
		if (result !== true && !result.success) {
			if (result.errors && formActive) {
				form.markInvalid(result.errors);
				
				setTimeout(form.scrollToFirstErrorField,100,form);
			}
			this.failureType = Ext.form.action.Action.SERVER_INVALID;
			success = false;
		}
		
		if (formActive) {
			form.afterAction(this, success);
		}
	}
});
Ext.define("Ext.moimz.chart.CartesianChart",{override:"Ext.chart.CartesianChart",bodyBorder:false});
Ext.define("Ext.moimz.Component",{override:"Ext.Component",getRoot:function() {
	var parent = this;
	while (true) {
		if (parent.ownerCt == null) return parent;
		parent = parent.ownerCt;
	}
}});
Ext.define("Ext.moimz.form.field.Base",{override:"Ext.form.field.Base",getPanel:function() {
	var parent = this.ownerCt;
	while (true) {
		if (parent === undefined) return null;
		if (parent.is("form") == true) return parent;
		parent = parent.ownerCt;
	}
},getForm:function() {
	var parent = this.ownerCt;
	while (true) {
		if (parent === undefined) return null;
		if (parent.is("form") == true) return parent.getForm();
		parent = parent.ownerCt;
	}
}});
Ext.define("Ext.ux.ColorField",{
	extend:"Ext.form.field.Trigger",
	lengthText:"색상코드가 잘못입력되었습니다. (#333 or #333333)",
	blankText:"색상코드가 잘못입력되었습니다. (#333 or #333333)",
	preview:true,
	regex:/^#[0-9a-f]{3,6}$/i,
	validateValue:function(value){
		if (!this.getEl()) {
			return true;
		}
		if (value.length!=4 && value.length!=7) {
			this.markInvalid(Ext.String.format(this.lengthText,value));
			return false;
		}
		if ((value.length < 1 && !this.allowBlank) || !this.regex.test(value)) {
			this.markInvalid(Ext.String.format(this.blankText,value));
			return false;
		}
		
		this.markInvalid();
		this.setColor(value);
		return true;
	},
	markInvalid:function( msg ) {
		Ext.ux.ColorField.superclass.markInvalid.call(this,msg);
	},
	setValue:function(hex){
		Ext.ux.ColorField.superclass.setValue.call(this,hex);
		this.setColor(hex);
	},
	setColor:function(hex) {
		if (this.preview == true) {
			Ext.ux.ColorField.superclass.setFieldStyle.call(this,{
				"background-color":hex,
				"background-image":"none"
			});
		}
	},
	menuListeners:{
		select:function(m,d){
			this.setValue("#"+d);
		},
		show:function(){
			this.onFocus();
		},
		hide:function(){
			this.focus();
			var ml = this.menuListeners;
			this.menu.un("select",ml.select,this);
			this.menu.un("show",ml.show,this);
			this.menu.un("hide",ml.hide,this);
		}
	},
	onTriggerClick:function(e){
		if (this.disabled){
			return;
		}
		
		this.menu = new Ext.menu.ColorPicker({
			shadow:true,
			autoShow :true
		});
		this.menu.alignTo(this.inputEl,"tl-bl?");
		
		this.menu.on(Ext.apply({},this.menuListeners,{
			scope:this
		}));
		
		this.menu.show(this.inputEl);
	}
});
Ext.define("Ext.moimz.form.field.ComboBox",{override:"Ext.form.field.ComboBox",cls:"x-form-no-padding",queryMode:"local",editable:false,autoLoadOnValue:true});
Ext.define("Ext.moimz.form.field.Date",{override:"Ext.form.field.Date",cls:"x-form-no-padding",submitFormat:"Y-m-d",format:"Y-m-d"});
Ext.define("Ext.moimz.form.field.Number",{override:"Ext.form.field.Number",fieldStyle:{textAlign:"right"},
	allowThousandSeparator:true,
	submitLocaleSeparator:false,
	decimalPrecision:0,
	toBaseNumber:function(value) {
		var me = this;
		return String(value).replace(new RegExp("[" + Ext.util.Format.thousandSeparator + "]", "g"), '').replace(me.decimalSeparator, '.');
	},
	parseRawValue:function(value) {
		var me = this;
		value = parseFloat(me.toBaseNumber(value));
		return isNaN(value) ? null : value;
	},
	onChange:function(newValue) {
		var ariaDom = this.ariaEl.dom;
		
		this.toggleSpinners();
		this.callParent(arguments);
		
		if (ariaDom) {
			if (Ext.isNumber(newValue) && isFinite(newValue)) {
				ariaDom.setAttribute('aria-valuenow', newValue);
			} else {
				ariaDom.removeAttribute('aria-valuenow');
			}
		}
		
		if (this.allowThousandSeparator) {
			this.setValue(newValue);
		}
	},
	getErrors:function(value) {
		if (!this.allowThousandSeparator) return this.callParent(arguments);
		value = arguments.length > 0 ? value : this.processRawValue(this.getRawValue());
	
		var me = this,
			errors = me.callSuper([value]),
			format = Ext.String.format,
			num;
	
		if (value.length < 1) {
			return errors;
		}
	
		value = me.toBaseNumber(value);
	
		if (isNaN(value)){
			errors.push(format(me.nanText, value));
		}
	
		num = me.parseValue(value);
	
		if (me.minValue === 0 && num < 0) {
			errors.push(this.negativeText);
		} else if (num < me.minValue) {
			errors.push(format(me.minText, me.minValue));
		}
	
		if (num > me.maxValue) {
			errors.push(format(me.maxText, me.maxValue));
		}
	
		return errors;
	},
	rawToValue:function(rawValue) {
		if (!this.allowThousandSeparator)
			return this.callParent(arguments);
		var value = this.fixPrecision(this.parseRawValue(rawValue));
		if (value === null) {
			value = rawValue || null;
		}
		return value;
	},
	valueToRaw:function(value) {
		if (!this.allowThousandSeparator) {
			return this.callParent(arguments);
		}
		var me = this,
			decimalSeparator = me.decimalSeparator,
			format = "0,000";
		if (me.allowDecimals) {
			for (var i = 0; i < me.decimalPrecision; i++) {
				if (i == 0) {
					format += ".";
				}
				format += "0";
			}
		}
		value = me.parseValue(value);
		value = me.fixPrecision(value);
		value = Ext.isNumber(value) ? value :parseFloat(String(value).replace(decimalSeparator, '.'));
		value = isNaN(value) ? '' :Ext.util.Format.number(value, format);
		return value;
	},
	getSubmitValue:function() {
		if (!this.allowThousandSeparator)
			return this.callParent();
		var me = this,
			value = me.callSuper();
	
		if (!me.submitLocaleSeparator) {
			value = me.toBaseNumber(value);
		}
		return value;
	},
	setMinValue:function(value) {
		if (!this.allowThousandSeparator)
			return this.callParent(arguments);
		var me = this,
			ariaDom = me.ariaEl.dom,
			minValue, allowed, ariaDom;
	
		me.minValue = minValue = Ext.Number.from(value, Number.NEGATIVE_INFINITY);
		me.toggleSpinners();
		
		if (ariaDom) {
			if (minValue > Number.NEGATIVE_INFINITY) {
				ariaDom.setAttribute('aria-valuemin', minValue);
			}
			else {
				ariaDom.removeAttribute('aria-valuemin');
			}
		}
	
		if (me.disableKeyFilter !== true) {
			allowed = me.baseChars + '';
	
			if (me.allowExponential) {
				allowed += me.decimalSeparator + 'e+-';
			}
			else {
				allowed += Ext.util.Format.thousandSeparator;
				if (me.allowDecimals) {
					allowed += me.decimalSeparator;
				}
				if (me.minValue < 0) {
					allowed += '-';
				}
			}
	
			allowed = Ext.String.escapeRegex(allowed);
			me.maskRe = new RegExp('[' + allowed + ']');
			if (me.autoStripChars) {
				me.stripCharsRe = new RegExp('[^' + allowed + ']', 'gi');
			}
		}
	}
});
Ext.define("Ext.moimz,window.MessageBox",{override:"Ext.window.MessageBox",show:function(cfg) {
	var me = this, visibleFocusables;

	cfg = cfg || {};

	if (Ext.Component.layoutSuspendCount) {
		Ext.on({
			resumelayouts:function() {
				me.show(cfg);
			},
			single:true
		});
		return me;
	}

	me.reconfigure(cfg);
	if (cfg.cls) {
		me.addCls(cfg.cls);
	}

	visibleFocusables = me.query('textfield:not([hidden]),textarea:not([hidden]),button:not([hidden])');
	me.preventFocusOnActivate = !visibleFocusables.length;

	Ext.window.Window.prototype.show.call(this);
	me.center();
	
	return me;
}});
Ext.define("Ext.moimz.grid.filters.filter.List",{override:"Ext.grid.filters.filter.List",onCheckChange:function() {
	var me = this, updateBuffer = me.updateBuffer;
	var value = [], i, len, checkItem;
	var items = me.menu.items;
	for (i=0, len=items.length;i<len;i++) {
		checkItem = items.getAt(i);
	
		if (checkItem.checked) {
			value.push(checkItem.value);
		}
	}
	
	me.grid.fireEvent("updateColumnFilter",me.grid,me,me.filter,value,me.filter.getValue());
	
	if (updateBuffer) {
		me.task.delay(updateBuffer);
	} else {
		me.setValue();
	}
}});
Ext.define("Ext.moimz.Component",{override:"Ext.Component",onBoxReady:function(width,height) {
	var me = this, label;
	
	if (me.ariaLabelledBy || me.ariaDescribedBy) {
		if (me.ariaLabelledBy) {
			label = me.getAriaLabelEl(me.ariaLabelledBy);
			
			if (label) {
				me.ariaEl.dom.setAttribute('aria-labelledby', label);
			}
		}
		
		if (me.ariaDescribedBy) {
			label = me.getAriaLabelEl(me.ariaDescribedBy);
			
			if (label) {
				me.ariaEl.dom.setAttribute('aria-describedby', label);
			}
		}
	}
	
	if (me.resizable) {
		me.initResizable(me.resizable);
	}
	
	if (me.autoScroll) me.body.on("scroll",function() { setTimeout(function() { me.storedScrollY = me.getScrollY(); },100); });
	
	if (me.draggable) {
		me.initDraggable();
	}

	if (me.hasListeners.boxready) {
		me.fireEvent('boxready', me, width, height);
	}
}});
Ext.define("Ext.moimz.window.Window",{override:"Ext.window.Window",onRender:function(ct,position) {
	var me = this;
	me.callParent(arguments);

	if (me.header) me.header.on({scope:me,click:me.onHeaderClick});
	if (me.maximizable) me.header.on({scope:me,dblclick:me.toggleMaximize});
	if (me.autoScroll) me.body.on("scroll",function() { setTimeout(function() { me.storedScrollY = me.getScrollY(); },100); });
},afterRender:function() {
	var me = this, header = me.header;

	me.minWidth = me.getWidth();
	me.maxHeight = $(window).height() - 50;

	if (me.maximized) {
		me.maximized = false;
		me.maximize(null, true);
		if (header) {
			header.removeCls(header.indicateDragCls);
		}
	}

	me.callParent();
	
	me.initTabGuards();
},onResize:function(width,height,oldWidth,oldHeight) {
	var me = this;
	
	if (me.floating && me.constrain) {
		me.doConstrain();
	}
	
	if (me.hasListeners.resize) {
		me.fireEvent("resize", me, width, height, oldWidth, oldHeight);
	}
	
	me.updateLayout();
	
	if (me.isInit !== true) {
		me.center();
		me.isInit = true;
	}
	
	if (me.getY() + me.getHeight() > $(window).height()) {
		me.setY(Math.max(25,$(window).height() - me.getHeight() - 25));
	}
}});
Ext.define("Ext.moimz.container.Container",{override:"Ext.container.Container",afterLayout:function(layout) {
	var me = this;
	++me.layoutCounter;
 
	if (me.hasListeners.afterlayout) me.fireEvent('afterlayout', me, layout);
	if (me.storedScrollY) me.setScrollY(me.storedScrollY);
}});
Ext.define("Ext.moimz.form.FileUploadField",{override:"Ext.form.FileUploadField",accept:null,clearOnSubmit:false,reset:function() {
	var me = this, clear = me.clearOnSubmit;
	if (me.rendered) {
		me.button.reset(clear);
		me.fileInputEl = me.button.fileInputEl;
		
		if (clear) {
			me.inputEl.dom.value = "";
			Ext.form.field.File.superclass.setValue.call(this, null);
		}
	}
	me.callParent();
	
	if (me.accept != null) {
		me.fileInputEl.set({accept:me.accept});
	}
},afterRender:function() {
	var me = this;
	if (me.accept != null) {
		me.fileInputEl.set({accept:me.accept});
	}
	
	me.autoSize();
	Ext.form.field.Base.prototype.afterRender.call(this);
	me.invokeTriggers("afterFieldRender");
}});

/**
 * ExtJS 라이브러리 언어셋 적용
 * @todo iModule 언어셋에서 처리
 */
Ext.onReady(function() {
	if (Ext.Date) {
		Ext.Date.monthNames = ["1월", "2월", "3월", "4월", "5월", "6월", "7월", "8월", "9월", "10월", "11월", "12월"];

		Ext.Date.dayNames = ["일", "월", "화", "수", "목", "금", "토"];
	}

	if (Ext.util && Ext.util.Format) {
		Ext.apply(Ext.util.Format,{
			thousandSeparator:",",
			decimalSeparator:".",
			currencySign:"\u20a9",
			// Korean Won
			dateFormat:"m/d/Y"
		});
	}
});

Ext.define("Ext.locale.ko.view.View",{
	override:"Ext.view.View",
	emptyText:""
});

Ext.define("Ext.locale.ko.grid.plugin.DragDrop",{
	override:"Ext.grid.plugin.DragDrop",
	dragText:"{0} 개가 선택되었습니다."
});

Ext.define("Ext.locale.ko.tab.Tab",{
	override:"Ext.tab.Tab",
	closeText:"닫기"
});

Ext.define("Ext.locale.ko.form.field.Base",{
	override:"Ext.form.field.Base",
	invalidText:"올바른 값이 아닙니다."
});

// changing the msg text below will affect the LoadMask
Ext.define("Ext.locale.ko.view.AbstractView",{
	override:"Ext.view.AbstractView",
	loadingText:"로딩중..."
});

Ext.define("Ext.locale.ko.picker.Date",{
	override:"Ext.picker.Date",
	todayText:"오늘",
	minText:"최소 날짜범위를 넘었습니다.",
	maxText:"최대 날짜범위를 넘었습니다.",
	disabledDaysText:"",
	disabledDatesText:"",
	nextText:"다음달(컨트롤키+오른쪽 화살표)",
	prevText:"이전달 (컨트롤키+왼족 화살표)",
	monthYearText:"월을 선택해주세요. (컨트롤키+위/아래 화살표)",
	todayTip:"{0} (스페이스바)",
	format:"m/d/y",
	startDay:0
});

Ext.define("Ext.locale.ko.picker.Month",{
	override:"Ext.picker.Month",
	okText:"확인",
	cancelText:"취소"
});

Ext.define("Ext.locale.ko.toolbar.Paging",{
	override:"Ext.PagingToolbar",
	beforePageText:"페이지",
	afterPageText:"/ {0}",
	firstText:"첫 페이지",
	prevText:"이전 페이지",
	nextText:"다음 페이지",
	lastText:"마지막 페이지",
	refreshText:"새로고침",
	displayMsg:"전체 {2} 중 {0} - {1}",
	emptyMsg:"표시할 데이터가 없습니다."
});

Ext.define("Ext.locale.ko.form.field.Text",{
	override:"Ext.form.field.Text",
	minLengthText:"최소길이는 {0}입니다.",
	maxLengthText:"최대길이는 {0}입니다.",
	blankText:"값을 입력해주세요.",
	regexText:"",
	emptyText:null
});

Ext.define("Ext.locale.ko.form.field.Number",{
	override:"Ext.form.field.Number",
	minText:"최소값은 {0}입니다.",
	maxText:"최대값은 {0}입니다.",
	nanText:"{0}는 올바른 숫자가 아닙니다."
});

Ext.define("Ext.locale.ko.form.field.Date",{
	override:"Ext.form.field.Date",
	disabledDaysText:"비활성",
	disabledDatesText:"비활성",
	minText:"{0}일 이후여야 합니다.",
	maxText:"{0}일 이전이어야 합니다.",
	invalidText:"{0}는 올바른 날짜형식이 아닙니다. - 다음과 같은 형식이어야 합니다. {1}",
	format:"m/d/y"
});

Ext.define("Ext.locale.ko.form.field.ComboBox",{
	override:"Ext.form.field.ComboBox",
	valueNotFoundText:undefined
}, function() {
	Ext.apply(Ext.form.field.ComboBox.prototype.defaultListConfig,{
		loadingText:"로딩중..."
	});
});

Ext.define("Ext.locale.ko.form.field.VTypes",{
	override:"Ext.form.field.VTypes",
	emailText:"이메일 주소 형식에 맞게 입력해야합니다. (예:user@example.com)",
	urlText:"URL 형식에 맞게 입력해야합니다. (예:http://www.example.com')",
	alphaText:"영문, 밑줄(_)만 입력할 수 있습니다.",
	alphanumText:"영문, 숫자, 밑줄(_)만 입력할 수 있습니다."
});

Ext.define("Ext.locale.ko.form.field.HtmlEditor",{
	override:"Ext.form.field.HtmlEditor",
	createLinkText:"URL을 입력해주세요:"
}, function() {
	Ext.apply(Ext.form.field.HtmlEditor.prototype,{
		buttonTips:{
			bold:{
				title:"굵게 (Ctrl+B)",
				text:"선택한 텍스트를 굵게 표시합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			italic:{
				title:"기울임꼴 (Ctrl+I)",
				text:"선택한 텍스트를 기울임꼴로 표시합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			underline:{
				title:"밑줄 (Ctrl+U)",
				text:"선택한 텍스트에 밑줄을 표시합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			increasefontsize:{
				title:"글꼴크기 늘림",
				text:"글꼴 크기를 크게 합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			decreasefontsize:{
				title:"글꼴크기 줄임",
				text:"글꼴 크기를 작게 합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			backcolor:{
				title:"텍스트 강조 색",
				text:"선택한 텍스트의 배경색을 변경합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			forecolor:{
				title:"글꼴색",
				text:"선택한 텍스트의 색을 변경합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			justifyleft:{
				title:"텍스트 왼쪽 맞춤",
				text:"왼쪽에 텍스트를 맞춥니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			justifycenter:{
				title:"가운데 맞춤",
				text:"가운데에 텍스트를 맞춥니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			justifyright:{
				title:"텍스트 오른쪽 맞춤",
				text:"오른쪽에 텍스트를 맞춥니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			insertunorderedlist:{
				title:"글머리 기호",
				text:"글머리 기호 목록을 시작합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			insertorderedlist:{
				title:"번호 매기기",
				text:"번호 매기기 목록을 시작합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			createlink:{
				title:"하이퍼링크",
				text:"선택한 텍스트에 하이퍼링크를 만듭니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			},
			sourceedit:{
				title:"소스편집",
				text:"소스편집 모드로 변환합니다.",
				cls:Ext.baseCSSPrefix + "html-editor-tip"
			}
		}
	});
});

Ext.define("Ext.locale.ko.grid.header.Container",{
	override:"Ext.grid.header.Container",
	sortAscText:"오름차순 정렬",
	sortDescText:"내림차순 정렬",
	lockText:"칼럼 잠금",
	unlockText:"칼럼 잠금해제",
	columnsText:"칼럼 목록"
});

Ext.define("Ext.locale.ko.grid.GroupingFeature",{
	override:"Ext.grid.feature.Grouping",
	emptyGroupText:"(None)",
	groupByText:"현재 필드로 그룹핑합니다.",
	showGroupsText:"그룹으로 보여주기"

});

Ext.define("Ext.locale.ko.grid.PropertyColumnModel",{
	override:"Ext.grid.PropertyColumnModel",
	nameText:"항목",
	valueText:"값",
	dateFormat:"m/j/Y"
});

Ext.define("Ext.locale.ko.window.MessageBox",{
	override:"Ext.window.MessageBox",
	buttonText:{
		ok:"확인",
		cancel:"취소",
		yes:"예",
		no:"아니오"
	}	
});

Ext.define("Ext.locale.ko.Component",{	
	override:"Ext.Component"
});