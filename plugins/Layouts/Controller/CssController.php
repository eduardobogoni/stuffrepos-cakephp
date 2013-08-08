<?php

App::uses('CssBox', 'Layouts.Lib');

class CssController extends AppController {

    public function beforeFilter() {
        parent::beforeFilter();

        if (isset($this->Auth)) {
            $this->Auth->allow('process');
        }
    }

    public function process($path) {
        if (file_exists($this->_cssFilePath($path))) {
            $this->_serveCss(file_get_contents($this->_cssFilePath($path)));
        } else {
            throw new Exception("File not found: {$this->_cssFilePath($path)}");
        }
    }

    public function css_box($selectors) {        
        $box = new CssBox($this->request->params['named']);
        $this->_serveCss($box->build($selectors));
    }

    private function _serveCss($content) {
        $this->response->type('text/css');
        //$this->response->header('Expires: '.gmdate('D, d M Y H:i:s',time() + (60 * 60 * 24 * $days_to_cache)).' GMT');

        foreach ($this->_variables() as $name => $value) {
            $content = preg_replace('/\$' . preg_quote($name) . '(?!\w)/', $value, $content);
        }

        $this->response->body($content);
        $this->autoRender = false;
        $this->autoLayout = false;
        return $this->response;
    }

    private function _variables() {
        if (file_exists($this->_variablesFilePath())) {
            $vars = parse_ini_file($this->_variablesFilePath());
        } else {
            $vars = array();
        }

        $vars['WEBROOT'] = Router::url('/', true);

        return $vars;
    }

    private function _variablesFilePath() {
        return $this->_cssDirectoryPath() . DS . 'variables.ini';
    }

    private function _cssDirectoryPath() {
        return ROOT . DS . APP_DIR . DS . WEBROOT_DIR . DS . 'css';
    }

    private function _cssFilePath($path) {
        return ROOT . DS . APP_DIR . DS . WEBROOT_DIR . DS . 'css' . DS . $path . '.css';
    }

}

?>
