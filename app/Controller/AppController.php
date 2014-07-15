<?php

App::uses('Controller', 'Controller');

class AppController extends Controller {

    public $helpers = array(
        'AccessControl.AccessControl',
        'ExtendedScaffold.ExtendedForm',
        'ExtendedScaffold.Lists',
        'ExtendedScaffold.ViewUtil',
        'Widgets.ControllerMenu' => array(
            'beforeAll' => '<div class="ControllerMenu">',
            'afterAll' => '</div>',
            'beforeEach' => '',
            'afterEach' => '',
        ),
        'Widgets.Menu',
    );
    public $paginate;

    public function flash($message, $url, $pause = 1, $layout = 'flash') {
        $this->Session->setFlash($message);
        $this->redirect($url);
    }

}
