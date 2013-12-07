<?php

class JsonResponseComponent extends Component {

    public function startup(\Controller $controller) {
        parent::startup($controller);
        $controller->autoRender = false;
    }

    public function returnData(Controller $controller, $data) {
        $controller->response->type(array('json' => 'application/json'));
        $controller->response->type('json');
        echo json_encode($data);
    }

    public function returnException(Controller $controller, Exception $ex) {
        $controller->response->statusCode(500);
        echo $ex->getTraceAsString();
    }

}
