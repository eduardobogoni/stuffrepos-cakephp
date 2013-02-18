<?php

interface DatasourceDumper {
    
    /**
     * Dump datasource into file.
     * @param Datasource $ds
     * @param string $filepath
     */
    public function dump(Datasource $ds, $filepath);
    
    /**
     * Load datasource's dump into datasource.
     * @param Datasource $ds
     * @param string $filepath
     */
    public function load(Datasource $ds, $filepath);
    
    /**
     * Remove all objects from datasource.
     * @param Datasource $ds
     */
    public function clear(Datasource $ds);
    
}