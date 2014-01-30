function Collections() {

}

Collections.entrySet = function(arrayMap) {
    var entrySet = new Array();
    for(i in arrayMap) {
        entrySet.push({
            'key': i,
            'value': arrayMap[i]
        });
    }
    return entrySet;
}

Collections.reverseEntrySet = function(entrySet) {
    var array = new Array();
    for(i in entrySet) {
        array[entrySet[i].key] = entrySet[i].value;
    }
    return array;
}

Collections.swap = function(array, key1,key2) {
    var temp = array[key1];
    array[key1] = array[key2];
    array[key2] = temp;
}

Collections.orderByField = function(array,orderField) {
    var entrySet = Collections.entrySet(array);
    for(var i=0; i< entrySet.length-1; ++i) {
        var min = i;
        for(var j=i+1; j < entrySet.length; ++j) {
            if (entrySet[j].value[orderField] < entrySet[min].value[orderField]) {
                min = j;
            }
        }
        if (min != i) {
            Collections.swap(entrySet,i,min);
        }
    }

    var result = new Array();
    for(i in entrySet) {
        result[i] = entrySet[i].value;
    }
    return result;
}

Collections.dumpField = function(array,field) {
    var b = '';
    var first = true;
    for(i in array) {
        if (first ){
            first = false;
        }
        else {
            b += ' | ';
        }
        b += i + ":" + array[i][field];
    }
    return b;
}

Collections.quicksortByField = function(array,field){
    var entrySet = Collections.entrySet(array);
    Collections._entrySetQsort(entrySet, 0, entrySet.length,field);
    var newArray = new Array();
    for(i in entrySet) {
        newArray.push(entrySet[i].value);
    }
    return newArray;
}

Collections._entrySetQsort = function (entrySet, begin, end,field) {
    if(end-1>begin) {
        var pivot=begin+Math.floor(Math.random()*(end-begin));

        pivot=Collections._entrySetPartition(entrySet, begin, end, pivot,field);

        Collections._entrySetQsort(entrySet, begin, pivot,field);
        Collections._entrySetQsort(entrySet, pivot+1, end,field);
    }
}

Collections._entrySetPartition = function(entrySet, begin, end, pivot,field) {
    var piv=entrySet[pivot].value[field];
    Collections.swap(entrySet,pivot, end-1);
    var store=begin;
    var ix;
    for(ix=begin; ix<end-1; ++ix) {
        if(entrySet[ix].value[field]<=piv) {
            Collections.swap(entrySet,store, ix);
            ++store;
        }
    }
    Collections.swap(entrySet,end-1, store);

    return store;
}
