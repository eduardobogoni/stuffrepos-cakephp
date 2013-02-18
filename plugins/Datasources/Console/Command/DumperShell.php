<?php

App::uses('Shell', 'Console');
App::uses('ConnectionManager', 'Model');

/**
 * Provides a CakePHP wrapper around PHPUnit.
 * Adds in CakePHP's fixtures and gives access to plugin, app and core test cases
 *
 * @package       Cake.Console.Command
 */
class DumperShell extends Shell {

    /**
     *
     * @var string
     */
    private $connection;

    /**
     *
     * @var string
     */
    private $dumpName;

    /**
     *
     * @var string
     */
    private $dumpPath;

    /**
     *
     * @var DatasourceDumper
     */
    private $dumper;

    /**
     *
     * @var boolean
     */
    private $clear;

    /**
     * get the option parser.
     *
     * @return void
     */
    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->description('Database utilities.');
        $parser->addOptions(
            array(
                'connection' => array(
                    'short' => 'c',
                    'default' => 'default',
                    'choices' => $this->_optionParserConnectionArgumentChoices(),
                    'help' => __('Set db config <config>. Uses \'default\' if none is specified.')
                ),
                'path' => array(
                    'default' => false,
                    'help' => __('Alternative path to dump')
                ),
                'clear' => array(
                    'default' => false,
                    'boolean' => true,
                    'help' => __('Clear database objects before load')
                )
            )
        );
        $parser->addSubcommands(array(
            'load' => array(
                'help' => __('Load a database dump')
            ),
            'show' => array(
                'help' => __('List all database dumps')
            ),
            'dump' => array(
                'help' => __('Dump database')
            ),
            'remove' => array(
                'help' => __('Delete a dump')
            ),
            'clear' => array(
                'help' => __('Clear database')
            )
        ));

        return $parser;
    }

    private function _optionParserConnectionArgumentChoices() {
        return array_keys(ConnectionManager::enumConnectionObjects());
    }

    public function main() {
        $this->out($this->getOptionParser()->help());
    }

    public function clear() {
        $this->_parseArgs();

        $dumper = $this->_getDumper($this->connection);
        $dumper->clear(ConnectionManager::getDataSource($this->connection));
    }

    public function dump() {
        $this->_parseArgs();

        $this->out("Connection: {$this->connection}");

        $dumper = $this->_getDumper($this->connection);
        $dumpPath = $this->_newDumpPath();

        $dumper->dump(
            ConnectionManager::getDataSource($this->connection)
            , $dumpPath
        );

        $this->out("Dump created: \"{$dumpPath}\"");
    }

    public function load() {
        $this->_parseArgs();
        $this->_outArguments();

        if ($this->dumpPath) {
            if (file_exists($this->dumpPath)) {
                $dataSource = ConnectionManager::getDataSource($this->connection);
                if ($this->clear) {
                    $this->dumper->clear($dataSource);
                }

                $this->dumper->load(
                    $dataSource
                    , $this->dumpPath
                );
                $this->out("Dump loaded: \"{$this->dumpPath}\"");
            } else {
                $this->out("Dump not found: \"{$this->dumpPath}\"");
            }
        } else {
            $this->out("No dump name informed");
        }
    }

    public function remove() {
        $this->_parseArgs();

        if ($this->dumpName) {
            if (($dump = $this->_findDump())) {
                unlink($dump['path']);
                $this->out("\"{$dump['path']}\" was deleted.");
            } else {
                $this->out("Dump not found with name \"{$this->dumpName}\"");
            }
        } else {
            $this->out("No dump name informed");
        }
    }

    private function _outArguments() {
        $this->out('Connection: ' . $this->connection);
        $this->out('Dump path: ' . $this->dumpPath);
        $this->out('Dump name: ' . $this->dumpName);
        $this->out('Clear: ' . ($this->clear ? 'yes' : 'no'));
    }

    private function _findDump() {
        foreach ($this->_listDumps() as $dump) {
            if ($dump['name'] == $this->dumpName) {
                return $dump;
            }
        }

        return false;
    }

    private function _newDumpPath() {
        $ds = ConnectionManager::getDataSource($this->connection);
        return $this->_getDumpsDirectory() . DS . $this->connection .
            '_' .
            date('Y-m-d_H-i-s') .
            '_' .
            str_replace('/', '-', $ds->config['datasource']);
    }

    private function _parseArgs() {
        $this->connection = $this->params['connection'];

        if ($this->params['path'] !== false) {
            $this->dumpPath = $this->params['path'];
            $this->dumpName = basename($this->dumpPath);
        } else if (!empty($this->args[0])) {
            $this->dumpName = $this->args[0];
            $this->dumpPath = $this->_getDumpsDirectory() . DS . $this->dumpName;
        } else {
            $this->dumpName = '';
            $this->dumpPath = '';
        }

        $this->dumper = $this->_getDumper($this->connection);
        $this->clear = $this->params['clear'];
    }

    /**
     * 
     * @param Datasource $ds
     * @return DatasourceDumper
     */
    private function _getDumper($connection) {
        $ds = ConnectionManager::getDataSource($connection);
        $path = explode('/', $ds->config['datasource']);
        $className = end($path) . 'Dumper';
        array_pop($path);
        $path = array_merge(array('Lib', 'DatasourceDumper'), $path);
        $location = implode('/', $path);

        $plugins = array_merge(
            array(false)
            , CakePlugin::loaded()
        );

        foreach ($plugins as $plugin) {
            App::uses($className, $plugin ? "$plugin.$location" : $location);

            if (class_exists($className)) {
                return new $className;
            }
        }

        throw new Exception("Class \"$className\" not found");
    }

    public function show() {
        $this->out("Dumps directory: \"{$this->_getDumpsDirectory()}\"");
        $this->hr();

        $total = 0;
        foreach ($this->_listDumps() as $dump) {
            $this->out("{$dump['name']}: {$dump['connection']}|{$dump['date']}|{$dump['datasource']}");
            $total++;
        }

        $this->hr();
        $this->out("Total: $total");
    }

    private function _listDumps() {
        $dir = new DirectoryIterator($this->_getDumpsDirectory());

        $dumps = array();
        while ($dir->valid()) {
            if ($dir->isFile()) {
                $dumps[] = $this->_parseDumpFile($dir->getPathname());
            }
            $dir->next();
        }
        return $dumps;
    }

    private function _parseDumpFile($filepath) {
        $connection = '[_a-zA-Z][_a-zA-Z0-9]*';
        $date = '\d{4}\-\d{2}\-\d{2}_\d{2}\-\d{2}\-\d{2}';
        $datasource = '([_a-zA-Z][_a-zA-Z0-9]*)(\-[_a-zA-Z][_a-zA-Z0-9]*)*';

        $pattern = "/($connection)_($date)_($datasource)/";

        if (preg_match($pattern, basename($filepath), $matches)) {
            return array(
                'name' => basename($filepath),
                'path' => $filepath,
                'connection' => $matches[1],
                'date' => str_replace('_', ' ', $matches[2]),
                'datasource' => str_replace('-', '/', $matches[3])
            );
        } else {
            throw new Exception("File \"$filepath\" has no dump format");
        }
    }

    private function _getDumpsDirectory() {
        $dir = TMP . DS . 'datasource-dump';

        if (file_exists($dir)) {
            if (is_dir($dir)) {
                return $dir;
            } else {
                throw new Exception("Directory \"$dir\" exists, but is not a directory");
            }
        } else if (mkdir($dir)) {
            return $dir;
        } else {
            throw new Exception("Was not possible to create directory \"$dir\"");
        }
    }

}