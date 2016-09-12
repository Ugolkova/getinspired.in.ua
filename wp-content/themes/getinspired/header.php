<!DOCTYPE html>
<html>
    <head>
        <title>Get Inspired</title>
        
        <meta name="description" itemprop="description" content="Get Inspired" />
        <meta name="keywords" itemprop="keywords" content="get inspired" />

        <link rel="shortcut icon" type="image/x-icon" href="<?php echo esc_url( get_template_directory_uri() ); ?>/img/favicon.ico" />
        
        <script src="<?php echo esc_url( get_template_directory_uri() ); ?>/js/jquery.min.js"></script>

        <script src="<?php echo esc_url( get_template_directory_uri() ); ?>/js/jquery-ui/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() ); ?>/js/jquery-ui/jquery-ui.min.css" />

        <script src="<?php echo esc_url( get_template_directory_uri() ); ?>/js/scrolling.js"></script>
   
	<?php wp_head(); ?>
     
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() ); ?>/css/custom.css" />
        <script src="<?php echo esc_url( get_template_directory_uri() ); ?>/js/custom.js"></script>
    </head>
    <body>
        <header>
            <a id="logo" href="/">Get Inspired</a>

            <menu>
                <li><a href="/events" title="События">События</a></li>
                <li><a href="/blog" title="Блог">Блог</a></li>
                <li><a href="/contact" title="Контакты">Контакты</a></li>
            </menu>
            
            <a id="signin" href="/signin">Войти</a>
        </header>
        <div id="content">
            <div id="inner">