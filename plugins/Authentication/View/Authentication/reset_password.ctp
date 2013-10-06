<h2><?php echo __('Reset de Senha'); ?></h2>

<?php
echo $this->Form->create('UserResetPassword');
echo $this->Form->input('username',array('type' => 'text', 'readonly'));
echo $this->Form->input('nova_senha',array('type' => 'password'));
echo $this->Form->input('confirmacao_senha',array('type' => 'password'));
echo $this->Form->end('Reset');
?>
