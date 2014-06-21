<?php

App::import('Lib', 'Migrations.MigrationVersion');

class MigrationAllPluginsShell extends Shell {

    /**
     * get the option parser.
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->description("Migrate all plugins database' schemas utilities.");
        $parser->addOptions(
                array(
                    'reset' => array(
                        'default' => false,
                        'boolean' => true,
                        'help' => __d('base','Reset database schema before migrate')
                    )
                )
        );
        return $parser;
    }

    public function main() {
        $this->dispatchShell('Migrations.migration', 'status', '-q');

        if ($this->params['reset']) {
            $this->_migrateAllPlugins('reset');
        }

        $this->_migrateAllPlugins('all');
    }

    private function _migrateAllPlugins($toVersion) {
        $version = new MigrationVersion();
        foreach (CakePlugin::loaded() as $plugin) {
            if ($plugin != 'Migrations' && $version->getMapping($plugin)) {
                $this->out('Migrating plugin ' . $plugin);
                $this->dispatchShell('Migrations.migration', 'run', '--plugin', $plugin, $toVersion);
            }
        }

        $this->dispatchShell('Migrations.migration', 'run', $toVersion);
    }

}
?>

