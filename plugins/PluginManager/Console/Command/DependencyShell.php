<?php

App::uses('Shell', 'Console');
App::uses('PluginManager', 'PluginManager.Lib');

class DependencyShell extends Shell {

    /**
     * get the option parser.
     *
     * @return void
     */
    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->description('Plugin manager.');
        $parser->addOptions(
            array(
                'pluginName' => array(
                    'short' => 'p',
                    'default' => 'app',
                    'help' => __d('plugin_manager',"Plugin's name or \"app\".")
                ),
            )
        );
        $parser->addSubcommands(array(
            'tree' => array(
                'help' => __d('plugin_manager','Show a dependency tree')
            ),
            'inTree' => array(
                'help' => __d('plugin_manager','Show plugins in the tree')
            ),
            'notInTree' => array(
                'help' => __d('plugin_manager','Show plugins not in the tree')
            ),
        ));

        return $parser;
    }

    public function main() {
        $this->out($this->getOptionParser()->help());
    }

    public function tree() {
        $this->_pluginDependencies(
            PluginManager::plugin($this->params['pluginName'])
            , 0);
    }

    private function _pluginDependencies(Plugin $plugin, $level) {
        $this->out(str_repeat(' ', $level * 2) . '- ' . $plugin->getName());

        foreach ($plugin->dependencies() as $dependencyName) {            
            $this->_pluginDependencies(PluginManager::plugin($dependencyName), $level + 1);
        }
    }

    public function inTree() {
        foreach(PluginManager::inTree($this->params['pluginName']) as $plugin) {
            $this->out($plugin);
        }
    }
    
    public function notInTree() {
        foreach(PluginManager::notInTree($this->params['pluginName']) as $plugin) {
            $this->out($plugin);
        }
    }

}