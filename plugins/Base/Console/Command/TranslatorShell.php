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
        return $parser;
    }

    public function i18n_extract() {
        $directoryWithTerms = $this->_directoryWithTermsToTranslate();
        $plugins = dirname(dirname(dirname(dirname(__FILE__))));
        $shell = 'i18n extract --app ' . APP .
                ' --paths ' . APP . ',' . $directoryWithTerms . ',' . $plugins .
                ' --extract-core no' .
                ' --merge no' .
                ' --output ' . APP . 'Locale' .
                ' --overwrite yes';
        $this->dispatchShell($shell);
    }

    private function _directoryWithTermsToTranslate() {
        $tempDir = FileSystem::createTemporaryDirectory();
        $tempFile = tempnam($tempDir, 'to_translate') . '.php';
        file_put_contents($tempFile, $this->_termsToTranslateFileContent());
        return $tempDir;
    }

    private function _termsToTranslateFileContent() {
        $b = "<?php\n";
        foreach (Translator::termsToTranslate() as $term) {
            $b .= "__('$term');\n";
        }
        return $b;
    }

}
