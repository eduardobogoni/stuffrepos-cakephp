ExtendedFormHelper.DateTimeInput = function(rootElement) {
    this._rootElement = rootElement;    
    var initData = $.parseJSON($(rootElement).attr('initData'));
    for (var x in initData) {
        this[x] = initData[x];
    }
}

ExtendedFormHelper.DateTimeInput.initInput = function(rootElement) {
    $(rootElement).data('controller', new ExtendedFormHelper.DateTimeInput(rootElement));
    $(rootElement).data('controller').onReady();
}

ExtendedFormHelper.DateTimeInput.prototype._hiddenInput = function() {
    return $(DomHelper.bySubId(this._rootElement, "hiddenInput"));
}

ExtendedFormHelper.DateTimeInput.prototype._visibleInput = function() {
    return $(DomHelper.bySubId(this._rootElement, "visibleInput"));
}

ExtendedFormHelper.DateTimeInput.prototype.onReady = function() {
    this._visibleInput().inputmask(this._patterns.mask);
    if (this._hiddenInput().val()) {
        date = moment(this._hiddenInput().val(), this._patterns.serverFormat);
        if (date.isValid()) {
            this._visibleInput().val(
                    date.format(this._patterns.guiFormat)
                    );
        }
    }
}

ExtendedFormHelper.DateTimeInput.prototype.onSubmit = function() {
    console.log("Visible input value: \"" + this._visibleInput().val() + "\"");
    switch (this._visibleInput().val()) {
        case this._patterns['emptyMask']:
        case '':
            date = '';
            break;
        default:
            date = moment(this._visibleInput().val(), this._patterns.guiFormat);
            if (date.isValid()) {
                date = date.format(this._patterns.serverFormat);
            }
            else {
                date = 'invalidDate';
            }
    }
    console.log("Date: \"" + date + "\"");
    this._hiddenInput().val(date);
}
