ExtendedFormHelper.InputMasked = function(rootElement) {
    this._rootElement = rootElement;    
    var initData = $.parseJSON($(rootElement).attr('initData'));
    for (var x in initData) {
        this['_' + x] = initData[x];
    }
}

ExtendedFormHelper.InputMasked.initInput = function(rootElement) {
    $(rootElement).data('controller', new ExtendedFormHelper.InputMasked(rootElement));
    $(rootElement).data('controller').onReady();
}

ExtendedFormHelper.InputMasked.prototype._hiddenInput = function() {
    return $(DomHelper.bySubId(this._rootElement, "hiddenInput"));
}

ExtendedFormHelper.InputMasked.prototype._visibleInput = function() {
    return $(DomHelper.bySubId(this._rootElement, "visibleInput"));
}

ExtendedFormHelper.InputMasked.prototype.onReady = function() {
    this._visibleInput().inputmask({'mask': this._mask, 'autoUnmask': true});
    this._visibleInput().inputmask('setvalue',this._hiddenInput().val());
}

ExtendedFormHelper.InputMasked.prototype.onSubmit = function() {
    this._hiddenInput().val(this._visibleInput().val());    
}