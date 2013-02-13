<?php

App::import('Lib', 'Migrations.MigrationVersion');

class MigrationAllPluginsShell extends Shell {

    public function main() {
        $version = & new MigrationVersion(
                        array(
                            'connection' => 'default',
                            'autoinit' => true
                        )
        );

        foreach (CakePlugin::loaded() as $plugin) {
            if ($version->getMapping($plugin)) {
                $this->dispatchShell('Migrations.migration', '--plugin', $plugin, 'all');
            }
        }

        $this->dispatchShell('Migrations.migration', 'all');
    }

}
?>

