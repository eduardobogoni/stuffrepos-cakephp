<?php echo $this->Session->flash('auth'); ?>
<h2><?php echo __('Login', true); ?></h2>

<?php
echo $this->Form->create('User');
echo $this->Form->input('email');
echo $this->Form->input('password');
echo $this->Form->end('Login');
?>

<?php
$helper = $this->Helpers->enabled('AccessControl')
        ? $this->AccessControl
        : $this->Html;
?>
<p>
    <?php echo $helper->link('Esqueceu sua senha?', '/contas/recuperacao_senha_notificacao'); ?>
</p>