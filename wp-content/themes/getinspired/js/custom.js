$(document).ready(function(){	
    $('a[href^="#"]').click(function(){
        var el = $(this).attr('href');
        $('body').animate({
            scrollTop: $(el).offset().top}, 200);
        return false; 
    });

    $('.mc4wp-form').find('input[type="email"]').focusin(function(){
        $(this).closest('form').addClass('active');
    });
    $('.mc4wp-form').find('input[type="email"]').focusout(function(){
        $(this).closest('form').removeClass('active');
    });
    
    $('.mc4wp-form').find('input[type="submit"]').hover(function(){
        $(this).closest('form').addClass('active');
    }, function(){
        $(this).closest('form').removeClass('active');
    });
        
    $('.mc4wp-form').find('input[type="submit"]').click(function(){
        var data = {};
        var form = $(this).closest('form');
        
        data.EMAIL = $('form').find('input[name="EMAIL"]').val();
        data._mc4wp_honeypot = form.find('input[name="_mc4wp_honeypot"]').val();
        data._mc4wp_timestamp = form.find('input[name="_mc4wp_timestamp"]').val();
        data._mc4wp_form_element_id = form.find('input[name="_mc4wp_form_element_id"]').val();
        
        $.ajax({
            type: 'POST',
            data: data,
            success: function(data){
                console.log("Data: " + data);
            }
        });
        return false;
    });
});