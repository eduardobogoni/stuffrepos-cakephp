<?php

App::uses('ScaffoldUtilComponent', 'ExtendedScaffold.Controller/Component');
App::uses('AccessControlComponent', 'AccessControl.Controller/Component');
App::uses('AccessControlFilter', 'AccessControl.Controller/Component/AccessControl');
App::uses('Controller', 'Controller');
App::uses('Model', 'Model');

class ScaffoldUtilComponentTest_AccessControlFilter implements AccessControlFilter {

    public function userHasAccess(\CakeRequest $request, $user, $object, $objectType) {
        switch ($object) {
            case 'deny':
                return false;

            case 'allow':
                return true;

            default:
                throw new Exception("Access control object \"$object\" unknown");
        }
    }

}

class ScaffoldUtilComponentTest_Controller extends Controller {

    public $uses = array(
        'ScaffoldUtilComponentTest_MyModel'
    );

}

class ScaffoldUtilComponentTest_MyModel extends Model {

    public $alias = 'MyModel';

}

class ScaffoldUtilComponentTest extends CakeTestCase {

    public function testSetFields() {
        $ScaffoldUtilComponent = new ScaffoldUtilComponent(
                new ComponentCollection()
                , array(
            'indexSetFields' => array(
                'descricao_resumida',
                'localizacao',
                'descricao',
                'almoxarifado_material_subitem_id',
                'estoque_quantidade' => array(
                    'emptyValue' => '0',
                    'align' => 'right',
                ),
                'estoque_minimo'
            ),
            'viewUnsetFields' => array(
                'id'
            ),
            'viewAppendFields' => array(
                'estoque_quantidade',
            ),
                )
        );
        $Controller = new Controller(new CakeRequest(), new CakeResponse());
        $ScaffoldUtilComponent->initialize($Controller);
        $ScaffoldUtilComponent->startup($Controller);
        $Controller->set('scaffoldFields', array(
            'id',
            'descricao_resumida',
            'localizacao',
            'descricao',
            'almoxarifado_material_subitem_id',
            'estoque_minimo'
        ));
        $Controller->params['action'] = 'index';
        $ScaffoldUtilComponent->beforeRender($Controller);
        $this->assertEqual(
                $Controller->viewVars['scaffoldFields']
                , array(
            'descricao_resumida',
            'localizacao',
            'descricao',
            'almoxarifado_material_subitem_id',
            'estoque_quantidade' => array(
                'emptyValue' => '0',
                'align' => 'right',
            ),
            'estoque_minimo'
                )
        );
    }

    public function testAccessControl() {
        $request = new CakeRequest();
        $request->data = array(
            'MyModel' => array(
                'field1' => 'value1',
                'field2' => 'value2',
                'field3' => 'value3',
                'field4' => 'value4',
                'field5' => 'value5',
            )
        );
        $request->params['action'] = 'add';

        $myUser = array(
            'MyUser' => array(
                'id' => 1,
                'name' => 'My name',
            )
        );

        AccessControlComponent::setRequest($request);
        AccessControlComponent::clearFilters();
        AccessControlComponent::addFilter(new ScaffoldUtilComponentTest_AccessControlFilter());

        $this->assertEqual(
                AccessControlComponent::userHasAccess($myUser, 'deny', 'service')
                , false
        );
        $this->assertEqual(
                AccessControlComponent::userHasAccess($myUser, 'allow', 'service')
                , true
        );

        $scaffoldUtilComponent = new ScaffoldUtilComponent(
                new ComponentCollection()
                , array(
            'addSetFields' => array(
                '_extended' => array(
                    array(
                        'label' => 'Main Form',
                        'lines' => array(
                            'field1',
                            'field2' => array(
                                'accessObjectType' => 'service',
                                'accessObject' => 'deny',
                            ),
                            'field3' => array(
                                'accessObjectType' => 'service',
                                'accessObject' => 'allow',
                            ),
                        )
                    ),
                    array(
                        'label' => 'Denied Form',
                        'accessObjectType' => 'service',
                        'accessObject' => 'deny',
                        'lines' => array(
                            'field4',
                        )
                    ),
                    array(
                        'label' => 'Allowed Form',
                        'accessObjectType' => 'service',
                        'accessObject' => 'allow',
                        'lines' => array(
                            'field5',
                        )
                    ),
                ),
            )
                )
        );
        $controller = new ScaffoldUtilComponentTest_Controller($request, new CakeResponse());
        $scaffoldUtilComponent->initialize($controller);
        $scaffoldUtilComponent->startup($controller);
        $this->assertEqual($controller->request->data, array(
            'MyModel' => array(
                'field1' => 'value1',
                'field3' => 'value3',
                'field5' => 'value5',
            )
        ));
    }

}
