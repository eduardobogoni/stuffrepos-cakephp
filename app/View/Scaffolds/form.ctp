<?php echo $this->ControllerMenu->moduleMenu(); ?>
<?php echo sprintf(__("Edit %s", true), $singularHumanName); ?> 
<?php

echo $this->ExtendedForm->defaultForm($scaffoldFields);
?>

