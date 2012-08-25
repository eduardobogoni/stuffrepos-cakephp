<?php

class BaseModelComponent extends Component {

    var $uses = false;

    function initialize(&$controller) {

        //load required for component models
        if ($this->uses !== false) {
            foreach ($this->uses as $modelClass) {
                $controller->loadModel($modelClass);
                list(,$modelClass) = pluginSplit($modelClass);
                $this->$modelClass = $controller->$modelClass;
            }
        }
    }

}

?>
