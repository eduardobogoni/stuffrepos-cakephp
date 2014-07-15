<?php echo $this->ControllerMenu->moduleMenu(); ?>
<h2><?php echo __($pluralHumanName) ?></h2>
<?php
echo $this->Lists->listElement($scaffoldFields, ${$pluralVar});
?>            
