<?php

App::uses('DatasourceDumper', 'Datasources.Lib');

class MysqlDumper implements DatasourceDumper {

    private $dumpCommand = 'mysqldump';
    private $loadCommand = 'mysql';

    public function dump(\Datasource $ds, $filepath) {
        if (!$this->_commandExists($this->dumpCommand)) {
            throw new Exception("Command \"{$this->dumpCommand}\" no exists");
        }
        $this->_executeMysqlCommand(
                $ds,
                escapeshellarg($this->dumpCommand) .
                ' ' . escapeshellarg('--lock-tables=false'),
                ' > ' . escapeshellarg($filepath));
    }

    public function load(\Datasource $ds, $filepath) {
        if (!$this->_commandExists($this->loadCommand)) {
            throw new Exception("Command \"{$this->loadCommand}\" no exists");
        }
        $this->_executeMysqlCommand(
                $ds, escapeshellarg($this->loadCommand),
                ' < ' . escapeshellarg($filepath));
    }

    private function _executeMysqlCommand(\Datasource $ds, $command, $append) {
        $command = $this->__mysqlCommand($ds, $command).' ' . $append;
        exec($command, $output, $return);
        if ($return != 0) {
            throw new Exception("Command \"$command\" returned $return. Output: " . implode("\n", $output));
        }
    }
    
    private function __mysqlCommand(\Datasource $ds, $command) {
		$options = ['host' => 'h', 'port' => 'P', 'login' => 'u'];
		foreach($options as $config => $option) {
			if ($ds->config[$config]) {
				$command .= ' -'.$option.' ' . escapeshellarg($ds->config[$config]);
			}
		}
        if ($ds->config['password']) {
            $command .= ' ' . escapeshellarg('-p' . $ds->config['password']);
        }
        $command .= ' ' . escapeshellarg($ds->config['database']);
        return $command;
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
