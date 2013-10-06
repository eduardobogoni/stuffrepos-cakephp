<h1><?php __('Reset Password Request'); ?></h1>

<p>    
    <strong>Nome de usuário: </strong><?php echo $user['AuthenticationUser']['username']; ?>
    <br/>
    <strong>E-mail: </strong><?php echo $user['AuthenticationUser']['email']; ?>
</p>

<p>Para resetar sua senha utilize o link abaixo:</p>

<p>
    <?php
    $link = Router::url(array(
                'plugin' => 'authentication',
                'controller' => 'authentication',
                'action' => 'reset_password',
                $userResetPasswordRequest['UserResetPasswordRequest']['chave']
                    ), true);
    echo $this->Html->link($link, $link);
    ?>
</p>

