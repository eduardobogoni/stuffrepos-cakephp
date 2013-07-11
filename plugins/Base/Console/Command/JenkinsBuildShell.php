<?php

class JenkinsBuildShell extends Shell {

    /**
     * get the option parser.
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->description("Configure database and execute tests.");
        $parser->addOptions(
                array(
                    'login' => array(
                        'default' => 'jenkins',
                    ),
                    'password' => array(
                        'default' => 'jenkins',
                    ),
                )
        );

        $parser->addArguments(array(
            'database' => array(
                'required' => true,
            )
        ));
        return $parser;
    }

    public function main() {
        $this->_configureDatabase();
        $this->dispatchShell('Migrations.migration', 'run', '0');
        $this->dispatchShell('Base.migration_all_plugins', '--reset');
        $this->dispatchShell('test', 'PluginManager', 'AllLoadedPluginsNoMigrations');
    }

    private function _configureDatabase() {
        file_put_contents(APP . DS . 'Config' . DS . 'database.php', <<<EOT
<?php

        class DATABASE_CONFIG {

            public \$default = array(
                'datasource' => 'Database/Mysql',
                'host' => 'localhost',
                'database' => '{$this->args[0]}',
                'login' => '{$this->params['login']}',
                'password' => '{$this->params['password']}',
                'encoding' => 'utf8',
            );
            public \$test = array(
                'datasource' => 'Database/Mysql',
                'host' => 'localhost',
                'database' => '{$this->args[0]}',
                'login' => '{$this->params['login']}',
                'password' => '{$this->params['password']}',
                'encoding' => 'utf8',
                'prefix' => 'test_',
            );

        }
EOT
        );
    }

}