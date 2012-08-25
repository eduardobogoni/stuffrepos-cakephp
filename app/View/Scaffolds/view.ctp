<?php echo $this->ActionList->outputModuleMenu(); ?>
<?php echo sprintf(__("View %s", true), $singularHumanName); ?>
<?php
echo $this->ViewUtil->scaffoldViewFieldList(${$singularVar}, $scaffoldFields);
?>
