/**
 * Utilidades para objetos e arrays.
 * @constructor
 */
function Lang() {
}

/**
 * Copia todas as propriedades de <b>src</b> para <b>dest</b>.
 * Se dest possui um método setData, ao final da cópia de propriedades, será
 * feita uma chamada <b>dest.setData(src)</b>.
 * @param {Object} src
 * @param {Object} dest
 *
 */
Lang.bindObject = function(src,dest)  {
	
    for(var i in src)
        dest[i] = src[i];

    if (dest.setData)
        dest.setData(src);
};
    
/**
 * Cria um objeto utilizando a função em <b>creator</b> e copia todos as 
 * propriedades de <b>src</b> para esse objeto.
 * Se o objeto criado por <b>creator</b> possui um método setData, ao final 
 * da cópia de propriedades, será feita uma chamada <b>dest.setData(src)</b>.
 * @param {Object} src
 * @param {function} creator
 * @return Object
 */
Lang.bindObjectWithCreator = function(src,creator) {

    if(src == null) {
        return null;
    }
    else{ 
        var dest = creator();	
        Lang.bindObject(src,dest);	
        return dest;
    }

};

/**
 * Copia as propriedades dos itens do array <b>src</b> para os itens do array
 * <b>dest</b>.
 * <p>
 * Os itens considerados de <b>src</b> serão apenas aqueles com índices numéricos.
 * A cópia de itens será feita de src[x] para dest[x], com x variando de 0 até
 * src.length.
 * <p>
 * A cópia dos itens é epreendida com chamadas Lang.bindObject(src[i],dest[i]), 
 * e portanto, está sujeita ao comportamento desse método.
 * @param {Array} src
 * @param {Array} dest
 */
Lang.bindArray = function(src,dest) {

    for(var i=0; i<src.length; ++i)	
        Lang.bindObject(src[i],dest[i]);
};

/**
 * Cria um array com itens gerados pela função em <b>creator</b> e com suas 
 * propriedades copiadas de <b>src</b>.
 * Para cada <b>i</b>, de <b>0..src.length-1</b>, é inserido no array resultante
 * um item gerado por <b>Lang.bindObjectWithCreator(src[i],creator)</b>.
 *
 * @param {Array} src
 * @param {function} creator
 * @return {Array}
 */
Lang.bindArrayWithCreator = function(src,creator) {

    if (src == null){
        return null;
    }else{
        var dest = new Array();

        for(var i=0; i<src.length; ++i) {
            var destObj = Lang.bindObjectWithCreator(src[i],creator);	
            dest.push(destObj);	
        }	    	    	    

        return dest;	    
    }
};

/**
 * Simula uma iteração sobre um array usando FOREACH.
 * Este método trabalha apenas com os itens em <b>array</b> que são indexados
 * por números. Para cada item no array, de índice <b>i</b> variando de <b>0</b>
 * até <b>array.length - 1</b>, é executada
 * uma chamada <b>func(array[i],i,THIS)</b>.
 * @param {Array} array
 * @param {function} func Função com assinatura function(Object obj,int index,THIS).
 * @param {Object} THIS Este método é um auxiliar e pode ser ignorado ou aproveitado
 * da forma que se desejar, pois é processado apenas para a função em <b>func</b>.
 */
Lang.forEach = function(array,func,THIS) {

    if (array !=null)
        if (array instanceof Array) {
            for(var i=0; i<array.length; ++i)
                func(array[i],i,THIS);	
        }
        else {
            throw("Parameter 'array' must be a Array: " + array);
        }		
};

/**
 * Se <b>variable</b> é nulo, mostra um alert e lança uma exceção com a mensagem 
 * em <b>message</b>.
 * @param {Object} variable
 * @param {String} message
 */
Lang.assert = function(variable,message) {
    if (variable == null) {
        alert(message);
        throw(message);
    }

};

/**
 * Verifica se um objeto está vazio. Um objeto está vazio se ele é nulo ou se 
 * não possui nenhuma propriedade.
 * @param {Object} obj
 * @return {Boolean}
 */
Lang.isEmpty = function(obj) {
    if (obj == null) {
        return true;
    }
    else {
        for(var v in obj) {
            return false;
        }
        return true;
    }

};

/** 
 * Concatena dois arrays.
 * @param {Array} a1
 * @param {Array} a2
 * @return {Array} O array produzido pela concatenação de a1 e a2, nesta ordem.
 */
Lang.concat = function(a1,a2) {

    var res = new Array();
    var a;

    for(a in a1) {
        res[a] = a1[a];
    }

    for(a in a2) {
        res[a] = a2[a];
    }

    return res;	
};

/**
 *
 */
Lang.arrayRemoveByKey = function(array,key) {
    var newArray = new Array();
    for(i in array) {
        if (i != key) {
            newArray[i] = array[i];
        }
    }
    return newArray;    
}

Lang.extendsClass = function(object,clazz) {
    for(i in clazz.prototype) {
        object[i] = clazz.prototype[i];
    }
}

Lang.arrayCount = function(array) {
    
    var count =0;
    for(var i in array) {
        count++;
    }
    return count;
}