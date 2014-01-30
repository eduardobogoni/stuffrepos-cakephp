
ExtendedFormHelper.InputSearchable = function(hiddenInputId,visibleInputId,searchOptions,initialId,initialLabel) {
    
    var THIS = this;
    
    this._hiddenInputId = hiddenInputId;
    this._visibleInputId = visibleInputId;
    this._searchOptions = searchOptions;    
    this._selectedItem = null;
    this._lastSelectedItem = null;
       
    DomHelper.registerOwnerById(this, hiddenInputId);
    DomHelper.registerOwnerById(this, visibleInputId);
    
    $(document).ready(function(){
        THIS._visibleInput().bind('textchange', function (event, previousText) {
            THIS._checkTextChanged(event,previousText);
        });
        THIS._visibleInput().autocomplete({
            source: THIS._sourceValue(),
            delay: 0,            
            select: THIS._onSelect,            
            autoFocus: true
        });
        
        THIS._setValue(initialId,initialLabel);
    });
}

ExtendedFormHelper.InputSearchable.initCallback = function(visibleInput) {    
    var hiddenInput = ExtendedFormHelper.InputSearchable._hiddenInputByVisibleInput(visibleInput);
    var initOptions = jQuery.parseJSON(visibleInput.getAttribute('initOptions'));

    new ExtendedFormHelper.InputSearchable(
        ExtendedFormHelper.setIdIfNoExists(hiddenInput),
        ExtendedFormHelper.setIdIfNoExists(visibleInput),
        initOptions.searchOptions,
        initOptions.initialId,
        initOptions.initialLabel                             
        );
}

ExtendedFormHelper.InputSearchable._hiddenInputByVisibleInput = function(visibleInput) {
    var parent = $(visibleInput).parent();
    var hiddenInputName = $(visibleInput).attr('name').replace('_search','');   
    var hiddenInput = null;
    $(parent).children().map(function(){        
        if ($(this).attr('name') == hiddenInputName) {
            hiddenInput = this;
        }
    });
    
    return hiddenInput;
}

ExtendedFormHelper.InputSearchable.prototype._sourceValue = function() {
    var url = '.?_autocompleteDatasource=true';
    var THIS = this;
    
    Lang.forEach(['modelName','displayField','queryField','termInAnyPlace'], function(param){
        if (THIS._searchOptions[param] != undefined) {
            url += '&' + param + '=' + THIS._searchOptions[param];
        }
    });

    return url;
}

ExtendedFormHelper.InputSearchable.prototype._hiddenInput = function() {
    return  $('#' + this._hiddenInputId);
}

ExtendedFormHelper.InputSearchable.prototype._visibleInput = function() {
    return  $('#' + this._visibleInputId);
}

ExtendedFormHelper.InputSearchable.prototype._applyColor = function() {
    var color = this._hiddenInput().val() ? '#CFC' : '#FCC';
    this._visibleInput().css('background-color',color);
}

ExtendedFormHelper.InputSearchable.prototype._onSelect = function(event, ui) {
    var THIS = DomHelper.getOwnerById(this.id);
    THIS._setValue(ui.item);
        
}

ExtendedFormHelper.InputSearchable.prototype._onChange = function(event, ui) {
    var THIS = DomHelper.getOwnerById(this.id);    
    THIS._applyColor();
}

ExtendedFormHelper.InputSearchable.prototype._checkTextChanged = function(event, previousText) {                
    if (this._selectedItem) {
        if (this._selectedItem.label != DomHelper.byId(this._visibleInputId).value) {
            this._setValue(null);
        }    
    }
    
    if (this._lastSelectedItem) {
        if (this._lastSelectedItem.label == DomHelper.byId(this._visibleInputId).value) {
            this._setValue(this._lastSelectedItem);
        } 
    }        
}

ExtendedFormHelper.InputSearchable.prototype._setValue = function(id,label) {   
    if (id == null) {
        label = null;
    }
    else if (typeof(id) == 'object') {        
        label = id.label;
        id = id.id;
    }
    
    if (id) {
        this._hiddenInput().val(id);
        this._visibleInput().val(label);
        this._selectedItem = {
            "id": id,
            "label": label
        };
        this._lastSelectedItem = this._selectedItem;
    }
    else {
        this._hiddenInput().val(null);
        this._selectedItem = null;
    }
    
    this._applyColor();
}