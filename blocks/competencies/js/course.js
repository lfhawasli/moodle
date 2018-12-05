$('.competency-edit-list .add').click(function(){
    $(this).closest('li').detach().appendTo($('.competency-edit-list.existing'))
})

$('.competency-edit-list .remove').click(function(){
    var cat = $(this).siblings('span').attr('id').split('-')[1];
    $(this).closest('li').detach().appendTo($('#competency-set-'+cat));
})

$('#page-content form').submit(function(e){
    
    e.preventDefault()
    
    var form = this;
    
    var items = []
    $('.competency-edit-list.existing span.competency').each(function(){
        items.push($(this).attr('id').split('-')[2])
    })
    
    var printMessage = function(cls, msg){
        $('#block_competencies_message').remove()
        $('#configheader').prepend('<div id="block_competencies_message" class="'+cls+'">'+msg+'</div>')
        $('html, body').animate({ scrollTop: 0 }, 0)
    }
    
    var postUrl = '<?php echo $ajax_url; ?>',
        postData = { data: JSON.stringify(items) }
    
    console.log('Calling '+postUrl+' with POST data '+JSON.stringify(items))
    
    $.ajax({
        type: "POST",
        url: postUrl,
        data: postData,
        cache: false
    }).done(function(response){
        if(response == true)
            form.submit()
        else
            printMessage('error', 'ERROR: Could not save course competencies.')
        console.log('Response from '+postUrl+' was '+JSON.stringify(response))
    }).fail(function(jqXHR, message){
        printMessage('error', 'ERROR: Could not contact server to save course competencies.')
    });
})
