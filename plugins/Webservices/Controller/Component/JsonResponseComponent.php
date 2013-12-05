<?php

class JsonResponseComponent extends Component {

    public function startup(\Controller $controller) {
        parent::startup($controller);
        $controller->autoRender = false;
    }

    public function returnData(Controller $controller, $data) {
        $this->_return('ok', $data);
    }

    public function returnException(Exception $ex) {
        $this->_return('error', $ex);
    }

    private function _return($status, $data) {
        echo json_encode(compact('status', 'data'));
    }

}
