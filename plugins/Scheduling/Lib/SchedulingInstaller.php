<?php

interface SchedulingInstaller {

    public function install();

    public function uninstall();

    /**
     * @return boolean
     */
    public function isInstalled();
}
