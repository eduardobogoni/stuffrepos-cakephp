<?php echo $this->ControllerMenu->moduleMenu(); ?>
<?php echo sprintf(__("View %s", true), $singularHumanName); ?>
<?php
echo $this->ViewUtil->scaffoldViewFieldList(${$singularVar}, $scaffoldFields);
?>
