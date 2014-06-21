<?php

App::uses('Translator', 'Base.Lib');
App::uses('FileSystem', 'Base.Lib');

class TranslatorShell extends Shell {

    /**
     * get the option parser.
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->addSubcommand('i18n_extract');
        $parser->addSubcommand('terms');
        $parser->addSubcommand('terms_file');
        return $parser;
    }

    public function i18n_extract() {
        $directoryWithTerms = $this->_directoryWithTermsToTranslate();
        $plugins = $this->_pluginPaths();
        $shell = 'i18n extract --app ' . APP .
                ' --paths ' . APP . ',' . $directoryWithTerms . ',' . implode(',', $plugins) .
                ' --extract-core no' .
                ' --merge no' .
                ' --output ' . APP . 'Locale' .
                ' --overwrite yes';
        $this->dispatchShell($shell);
        FileSystem::recursiveRemoveDirectory($directoryWithTerms);
    }

    public function terms() {
        foreach (Translator::termsToTranslate() as $plugin => $terms) {
            foreach ($terms as $term) {
                $this->out("<info>$plugin</info>|$term|");
            }
        }
    }

    public function terms_file() {
        echo $this->out($this->_termsToTranslateFileContent());
    }

    private function _pluginPaths() {
        $paths = array();
        foreach (CakePlugin::loaded() as $plugin) {
            switch ($plugin) {
                case 'Migrations':
                    break;

                default:
                    $paths[] = CakePlugin::path($plugin);
            }
        }
        return $paths;
    }

    private function _directoryWithTermsToTranslate() {
        $tempDir = FileSystem::createTemporaryDirectory();
        $tempFile = tempnam($tempDir, 'to_translate') . '.php';
        file_put_contents($tempFile, $this->_termsToTranslateFileContent());
        return $tempDir;
    }

    private function _termsToTranslateFileContent() {
        $b = "<?php\n";
        foreach (Translator::termsToTranslate() as $plugin => $terms) {
            foreach ($terms as $term) {
                if ($plugin == '') {
                    $b .= '__("' . addslashes($term) . '");' . PHP_EOL;
                } else {
                    $b .= '__d("' . $plugin . '","' . addslashes($term) . '");' . PHP_EOL;
                }
            }
        }
        return $b;
    }

}
