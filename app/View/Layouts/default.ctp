<?php
$title = 'Put title here'
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <?php echo $this->Html->charset(); ?>
        <title>
            <?php echo $title; ?>
            <?php echo ($title_for_layout ? ' - ' . $title_for_layout : $title_for_layout); ?>
        </title>
        <?php
        echo $this->Html->meta('icon');

        echo $this->Html->css('app');
        echo $this->Html->css('debug');
        echo $this->Html->css('tables');
        echo $this->Html->css('forms');
        
        echo $this->ScaffoldUtil->links();       

        echo $this->fetch('meta');
        echo $this->fetch('css');
        echo $this->fetch('script');
        ?>
    </head>
    <body>
        <div id="container">
            <div id="header">
                <h1><?php echo $title; ?></h1>
                <?php
                echo $this->Menu->dropdown(array(
                    __('Home') => '/',
                ));
                ?>
            </div>
            <div id="content">
                <?php echo $this->Session->flash(); ?>
                <?php echo $this->fetch('content'); ?>
            </div>
        </div>
        <?php echo $this->element('sql_dump'); ?>
    </body>
</html>
