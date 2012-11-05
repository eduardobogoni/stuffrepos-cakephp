<?php echo $this->ActionList->outputModuleMenu(); ?>
<?php echo sprintf(__("Edit %s", true), $singularHumanName); ?> 
<?php

echo $this->ExtendedForm->defaultForm($scaffoldFields);
?>

