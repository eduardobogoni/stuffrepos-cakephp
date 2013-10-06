<h2><?php echo __('Reset de senha'); ?></h2>

<?php
echo $this->Form->create('UserResetPasswordRequestSubmission');
echo $this->Form->input('username_or_email', array('type' => 'text'));
echo $this->Form->end('Enviar');
?>