function ExtendedFormHelper() {
}

ExtendedFormHelper.initInputs = function(rootElement) {
    if ($(rootElement).attr('initCallback')) {
        ExtendedFormHelper._initInput(rootElement);
    }
    else {
        $(rootElement).children().map(function(){        
            ExtendedFormHelper.initInputs(this);
        });
    }    
}

ExtendedFormHelper._initInput = function(element) {
    if (!$(element).attr('initialized')) {
        var func = eval($(element).attr('initCallback'));
        func(element);
        $(element).attr('initialized',true);
    }
}

ExtendedFormHelper.setIdIfNoExists = function(element) {
    if (!$(element).attr('id')) {
        $(element).attr('id', ExtendedFormHelper.createNewDomId() );
    }
    return $(element).attr('id');
}

ExtendedFormHelper.createNewDomId = function() {
    var id = null;
    while (id == null) {
        id = 'id_' + Math.floor(Math.random()*99999999);
        if (DomHelper.byId(id,false)) {
            id = null;
        }
    }
    return id;
}