<h2><?php echo __('Alteração de Senha'); ?></h2>

<?php
echo $this->Form->create('UserChangePassword');
echo $this->Form->input('senha_atual',array('type' => 'password'));
echo $this->Form->input('nova_senha',array('type' => 'password'));
echo $this->Form->input('confirmacao_senha',array('type' => 'password'));
echo $this->Form->end('Alterar');
?>
