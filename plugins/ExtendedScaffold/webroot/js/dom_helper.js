/**
 * Classe estática. Seu construtor não é utilizado.
 *
 * @class Utilidades para DomHelper.
 * @requires
 * @constructor
 */
function DomHelper(){
} 

/**
 * @private
 */
DomHelper.owners = new Array();
    
/**
 * @private
 */ 
DomHelper.ownersId = new Array();

/**
 * Registra um proprietário.
 *
 * DomHelper mantém um mapa de proprietários (Owners) de elementos. Esta funcionalidade
 * é útil quando se deseja manter referência bidirecional entre um objeto e
 * um elemento DomHelper qualquer que esse objeto controla.
 * @param {Object} owner
 * @param {Element} element
 */
DomHelper.registerOwner = function(owner,element) {
	
    var entry = {};	
    entry.owner = owner;
    entry.element = element;

    this.owners.push(entry);
};

/**
 * Retorna o objeto proprietário de <b>element</b>.
 * @param {Element} element
 * @return {Object}
 * @throws Exception Se não encontrar um proprietário para element.
 */
DomHelper.getOwner = function(element) {
	
    for(var i=0; i<this.owners.length; ++i)
        if (this.owners[i].element == element)
            return this.owners[i].owner;	    

    throw("Owner not found.");
};
    
/**
 * Remove a referência de propriedade de <b>element</b>. 
 * @param {Element} element
 */
DomHelper.unregisterOwner = function(element) {
    for(var i=0; i<this.owners.length; ++i)	    
        if (this.owners[i].element == element) {
            this.owners.slice(i,1);
            return;
        }				
};

/**
 * Semelhante a {@link DomHelper#registerOwner}, mas o elemento propriedade é
 * referenciado por seu ID HTML.
 * @param {Object} owner
 * @param {String} id
 */
DomHelper.registerOwnerById = function(owner,id) {
    this.ownersId[id] = owner;
};
    
/**
 * Semelhante a {@link DomHelper#getOwner}, mas o elemento propriedade é referenciado
 * por seu ID HTML.
 * @param {String} id
 * @return {Object}
 */
DomHelper.getOwnerById = function(id) {
    return this.ownersId[id];
};

/**
 * Semelhante a {@link DomHelper#unregisterOwner}, mas o elemento propriedade é
 * referenciado por seu ID HTML.
 * @param {String} id
 */
DomHelper.unregisterOwnerById = function(id) {
    if (this.ownersId[id])
        this.ownersId[id] =  null;
};

/**
 * 
 * @param {Node} parent
 * @param {String} tag
 * @return {Element}
 */
DomHelper.createChildElement = function(parent,tag) {
    var child = document.createElement(tag);
    parent.appendChild(child);
    return child;
};

/**
 * (Método não implementado)
 * @param {Node} parent
 * @param {String} tag
 * @return {Element}
 */
DomHelper.createChildElements = function(parent,tags) {
    if (tags instanceof Array){
    //TO-DO
    }
    else {
        throw "Tags não é do tipo Array.";
    }
};

/**
 * 
 * @param {String} id
 * @param {Boolean} required
 * @return {Node}
 */
DomHelper.byId = function(id,required) {
    var node = document.getElementById(id);

    required = required == false ? false : true;

    if ((node == undefined && node==null) && required==true)
        throw ("Em Dom.byId:\n\tID não encontrado: " + id);

    return node;
};

/**
 *
 * @param {Node} node
 * @param {String} subId
 * @param {Boolean} required
 * @return {Node}
 */
DomHelper.bySubId = function(node,subId,required) {

    //console.debug(node);

    required = required == false ? false : true;	

    if (DomHelper.getAttributeValue(node,"subid") == subId)
        return node;		

    var children = node.childNodes;

    if (children != null)	{
        for(var i=0; i<children.length; ++i) {

            var child = children[i];

            if (child instanceof Element) {

                var result = DomHelper.bySubId(child,subId,false);
                if(result != null) {			
                    return result;	
                }

            }
        }
    }	

    if (required)
        throw ("Em Dom.bySubId:\n\tID não encontrado: " + subId);	    

    return null;
};

/**
 *
 * @param {Node} node
 * @param {String} superId
 * @param {Boolean} required
 * @return {Node}
 */
DomHelper.bySuperId = function(node,superId,required) {


    //console.debug("superId: " + node.getAttribute("superid"));

    required = required == false ? false : true;	

    //alert(node + "\nsuperId: " + node.getAttribute("superid") + "\nsubId: " + node.getAttribute("subid"));

    if (DomHelper.getAttributeValue(node,"superid") == superId)
        return node;		

    if (node.parentNode != null) {
        return this.bySuperId(node.parentNode,superId,false);
    }
    else {

        if (required)
            throw ("Em Dom.bySuperId:\n\tID não encontrado: " + superId);	    

        return null;

    }			
};

/**
 *
 * @param {Node} node
 */
DomHelper.traceParent = function(node) {

    alert(node + "\nsuperId: " + node.getAttribute("superid") + "\nsubId: " + node.getAttribute("subid"));

    if (node.parentNode != null) {
        this.traceParent(node.parentNode);
    }
};

/**
 *
 * @param {HTMLElement} node
 * @param {Boolean} visible
 */
DomHelper.setVisible = function(node,visible) {
    node.style.display = visible ? "" : "none";    
};

/**
 *
 * @param {HTMLInputElement} node
 * @param {Boolean} disabled
 */
DomHelper.setDisabled = function(node,disabled) {
    node.disabled = disabled ? "disabled" : "";
};

/**
 *
 * @param {Element} node
 * @param {String} attributeName
 */
DomHelper.getAttributeValue = function(node,attributeName){
    
    if (node == null) {
        throw('Parâmetro node é nulo.\nattributeName: ' + attributeName)
    }    

    return node.getAttribute(attributeName);
};

/** 
 * Remove todos os filhos de um nó.
 * @param {Node} node
 */
DomHelper.clear = function(node) {

    while(node.lastChild != null)
        node.removeChild(node.lastChild);
};

/**
 * Retorna a posição de um elemento HTML em relação à origem da página.
 * 
 * @param {HTMLElement} element
 * @return {Vector2D}
 */
DomHelper.getPosition = function(element) {

    var pos = Vector2D.create();	    

    if (element.offsetParent) {
        pos.setX(element.offsetLeft);
        pos.setY(element.offsetTop);

        while (element = element.offsetParent) {
            pos.incX(element.offsetLeft);
            pos.incY(element.offsetTop);
        }
    }

    return pos;
};
    
/**
 * @class
 * @constructor
 */ 
DomHelper.Select = function() {
    };

/**
 * Preenche um elemento Select com valores de um array.
 *
 * @param {HTMLSelectElement} select
 * @param {Array} list
 * @param {String/Function} valueField
 * @param {String/Function} contentField
 * @param {Number} selectIndex 
 * @param {String/Function} classNameField 
 */
DomHelper.Select.fill = function(select,list,valueField,contentField,selectIndex, classNameField) {
	    
    select.innerHTML = "";
    var empty;

    if (list != null) {		
        if (list.length >0) {
            empty = false;
        }
        else {
            empty = true;
        }
    }    
    else {
        empty = true;		
    }

    if (empty) {
        var option = document.createElement("option");
        option.setAttribute("value","");
        option.setAttribute("disabled","disabled");
        option.innerHTML = "--- VAZIO ---";						
        select.appendChild(option);

        select.selectedIndex = 0;   
    }
    else{


        var valueFunction = typeof(valueField) == "function"
        ? valueField
        : function(obj) {
            return eval("p." + valueField);
        };

        var contentFunction = typeof(contentField) == "function"
        ? contentField
        : function(obj) {
            return eval("p." + contentField);
        };

        var classNameFunction;
        if(classNameField == null) {
            classNameFunction = function(obj) {
                return "";
            };
        }
        else {
            classNameFunction = typeof(classNameField) == "function"
            ? classNameField
            : function(obj) {
                return eval("p." + classNameField);
            };
        }



        for(var i=0;i<list.length; ++i) {

            var p = list[i];

            var value = valueFunction(p);
            var label = contentFunction(p);
            var className = classNameFunction(p);

            var option = document.createElement("option");
            option.setAttribute("value",value);
            option.innerHTML = label;
            option.className = className;

            select.appendChild(option);		   
        }

        if (selectIndex==null) {
            select.selectedIndex=-1;
        }
        else {
            select.selectedIndex = selectIndex;

            if (select.onchange){
                select.onchange(select);
            }

        }

    }
};

/**
 * Retorna o valor da opção selecionada em select.  
 *
 * @param {HTMLSelectElement} select
 * @return {String}
 */
DomHelper.Select.getSelectedItemValue = function(select) {
    if (select.selectedIndex<0) {
        return null;
    }
    else{
        return select.options.item(select.selectedIndex).value;
    }

};

/**
 * Seleciona em <b>select</b> um option por seu valor.
 *
 * @param {HTMLSelectElement} select
 * @param {String} value
 */
DomHelper.Select.setSelectedByValue = function(select,value) {
    for(var i=0; i<select.options.length; i++) {
        if(select.options[i].value == value) {
            select.selectedIndex = i;
            break;
        }
    }
};

/**
 * Seleciona em <b>select</b> um option por seu conteúdo.
 * 
 * @param {HTMLSelectElement} select
 * @param {String} text 
 */
DomHelper.Select.setSelectedByText = function(select,text) {
    for(var i=0; i<select.options.length; i++) {
        if(select.options[i].text == text) {
            select.selectedIndex = i;
            break;
        }
    }
};