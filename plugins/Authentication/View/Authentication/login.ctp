<?php echo $this->Session->flash('auth'); ?>
<h2><?php echo __('Login', true); ?></h2>

<?php
echo $this->Form->create('User');
echo $this->Form->input('email');
echo $this->Form->input('password');
echo $this->Form->end('Login');
?>
<p>
    <?php echo $this->AccessControl->link('Esqueceu sua senha?', '/contas/recuperacao_senha_notificacao'); ?>
</p>