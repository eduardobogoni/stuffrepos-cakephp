ExtendedFormHelper.ListFieldSet = function(tableId,newRowPrototypeId,lastRowIndex) {
    this.tableId = tableId;
    this.newRowPrototypeId = newRowPrototypeId
    this.lastRowIndex = lastRowIndex;
}

ExtendedFormHelper.ListFieldSet.prototype.addRow = function() {    
    var clone = $('#' + this.newRowPrototypeId).clone(true);    
    this._removeIds(clone);
    this._replaceInputsNames(clone, ++this.lastRowIndex);
    $('#' + this.tableId).append($(clone));
    ExtendedFormHelper.initInputs(clone);
}

ExtendedFormHelper.ListFieldSet.prototype.removeRow = function(subelement) {
    var row = $(subelement).parents("tr").get(0);
    $(row).remove();    
}

ExtendedFormHelper.ListFieldSet.prototype.removeElementFromForm = function(element) {
    element = $(element).detach();    
    element.appendTo("body");   
}

ExtendedFormHelper.ListFieldSet.prototype._removeIds = function(rootElement) {
    var THIS = this;
    $(rootElement).removeAttr('id');
    $(rootElement).children().map(function(){        
        THIS._removeIds(this);
    });
}

ExtendedFormHelper.ListFieldSet.prototype._replaceInputsNames = function(rootElement,rowIndex) {
    switch($(rootElement).get(0).tagName) {
        case 'INPUT':
        case 'SELECT':
            rootElement.setAttribute('name',$(rootElement).attr('name').replace('%rowIndex%',rowIndex));            
            break;
            
        default:
            var THIS = this;
            $(rootElement).children().map(function(){        
                THIS._replaceInputsNames(this,rowIndex);
            });
    }
    

}