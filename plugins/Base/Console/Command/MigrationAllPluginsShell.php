<?php

App::import('Lib', 'Migrations.MigrationVersion');

class MigrationAllPluginsShell extends Shell {

    public function main() {
        $version = new MigrationVersion();

        foreach (CakePlugin::loaded() as $plugin) {
            if ($version->getMapping($plugin)) {
                $this->out('Migrating plugin '.$plugin);
                $this->dispatchShell('Migrations.migration', 'run', '--plugin', $plugin, 'all');
            }
        }

        $this->dispatchShell('Migrations.migration', 'run', 'all');
    }

}
?>

