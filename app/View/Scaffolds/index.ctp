<?php echo $this->ActionList->outputModuleMenu(); ?>
<h2><?php __($pluralHumanName) ?></h2>
<?php
echo $this->Lists->listElement($scaffoldFields, ${$pluralVar});
?>            
