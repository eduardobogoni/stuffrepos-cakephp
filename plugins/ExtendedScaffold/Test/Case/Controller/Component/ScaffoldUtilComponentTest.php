<?php

App::uses('ScaffoldUtilComponent', 'ExtendedScaffold.Controller/Component');
App::uses('Controller', 'Controller');

class ScaffoldUtilComponentTest extends CakeTestCase {

    public $ScaffoldUtilComponent = null;
    public $Controller = null;

    public function setUp() {
        parent::setUp();
        // Setup our component and fake test controller        
        $this->ScaffoldUtilComponent = new ScaffoldUtilComponent(
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
        $this->Controller = new Controller(new CakeRequest(), new CakeResponse());
        $this->ScaffoldUtilComponent->initialize($this->Controller);
        $this->ScaffoldUtilComponent->startup($this->Controller);
        $this->Controller->set('scaffoldFields', array(
            'id',
            'descricao_resumida',
            'localizacao',
            'descricao',
            'almoxarifado_material_subitem_id',
            'estoque_minimo'
        ));
    }

    public function testSetFields() {
        $this->Controller->params['action'] = 'index';
        $this->ScaffoldUtilComponent->beforeRender($this->Controller);
        $this->assertEqual(
                $this->Controller->viewVars['scaffoldFields']
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

}
