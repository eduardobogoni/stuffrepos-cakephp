<h2><?php echo __('Alteração de Senha'); ?></h2>

<?php
echo $this->ExtendedForm->create('UserChangePassword');
echo $this->ExtendedForm->input('senha_atual',array('type' => 'password'));
echo $this->ExtendedForm->input('nova_senha',array('type' => 'password'));
echo $this->ExtendedForm->input('confirmacao_senha',array('type' => 'password'));
echo $this->ExtendedForm->end('Alterar');
?>
