<?php
function getinspired_widgets_init() {
    require get_template_directory() . '/inc/widgets.php';
    register_widget( 'Getinspired_Widget' );
    
    register_sidebar( array(
        'name' => __( 'Main Sidebar', 'getinspired' ),
        'id' => 'sidebar-1',
        'description' => __( 'Widgets in this area will be shown on all posts and pages.', 'theme-slug' ),
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
	'after_widget'  => '</li>',
	'before_title'  => '<h2 class="widgettitle">',
	'after_title'   => '</h2>',
    ) );
}

add_action( 'widgets_init', 'getinspired_widgets_init' );