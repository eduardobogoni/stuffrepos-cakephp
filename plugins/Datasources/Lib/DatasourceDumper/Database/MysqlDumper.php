<?php

App::uses('DatasourceDumper', 'Datasources.Lib');

class MysqlDumper implements DatasourceDumper {

    private $dumpCommand = 'mysqldump';
    private $loadCommand = 'mysql';

    public function dump(\Datasource $ds, $filepath) {
        if (!$this->_commandExists($this->dumpCommand)) {
            throw new Exception("Command \"{$this->dumpCommand}\" no exists");
        }

        $command = "'$this->dumpCommand' --lock-tables=false";


        if ($ds->config['host']) {
            $command .= " -h '{$ds->config['host']}'";
        }

        if ($ds->config['port']) {
            $command .= " -P '{$ds->config['port']}'";
        }

        if ($ds->config['password']) {
            $command .= " '-p{$ds->config['password']}'";
        }

        if ($ds->config['login']) {
            $command .= " -u '{$ds->config['login']}'";
        }

        $command .= " '{$ds->config['database']}' > '$filepath'";

        exec($command, $output, $return);

        if ($return != 0) {
            throw new Exception("Command \"$command\" returned $return. Output: " . implode("\n", $output));
        }
    }

    public function load(\Datasource $ds, $filepath) {
        if (!$this->_commandExists($this->loadCommand)) {
            throw new Exception("Command \"{$this->loadCommand}\" no exists");
        }

        $command = "'$this->loadCommand'";


        if ($ds->config['host']) {
            $command .= " -h '{$ds->config['host']}'";
        }

        if ($ds->config['port']) {
            $command .= " -P '{$ds->config['port']}'";
        }

        if ($ds->config['password']) {
            $command .= " '-p{$ds->config['password']}'";
        }

        if ($ds->config['login']) {
            $command .= " -u '{$ds->config['login']}'";
        }

        $command .= " '{$ds->config['database']}' < '$filepath'";

        exec($command, $output, $return);

        if ($return != 0) {
            throw new Exception("Command \"$command\" returned $return. Output: " . implode("\n", $output));
        }
    }

    private function _commandExists($command) {
        exec(escapeshellarg($command) . ' --version', $output, $returnVar);
        return $returnVar === 0;
    }

    public function clear(Datasource $ds) {
        foreach ($this->_listTables($ds) as $table) {
            $this->_dropTable($ds, $table);
        }
    }

    private function _listTables(Mysql $ds) {
        $result = $ds->query('show tables');
        $tables = array();
        foreach ($result as $r1) {
            foreach ($r1 as $r2) {
                foreach ($r2 as $r3) {
                    if ($ds->config['prefix'] == '' || strpos($r3, $ds->config['prefix']) === 0) {
                        $tables[] = $r3;
                    }
                }
            }
        }
        return $tables;
    }

    private function _dropTable(Mysql $ds, $table) {
        $ds->query("Drop table `$table`");
    }

}
