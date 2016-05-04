$(document).ready(function(){
    
    var upHandler = function(){
        
        var $item = $(this).closest('li'),
            $itemPrev = $item.prev();
            
        if($itemPrev.length > 0){
            $item.detach().insertBefore($itemPrev);
        }else{
            var $set = $item.closest('ul').closest('li'),
                $setPrev = $set.prev();

            if($setPrev.length > 0)
                $item.detach().appendTo($setPrev.find('.block_competencies_items'))
        }
    }
    
    var downHandler = function(){
        
        var $item = $(this).closest('li'),
            $itemNext = $item.next();
            
        if($itemNext.length > 0){
            $item.detach().insertAfter($itemNext);
        }else{
            var $set = $item.closest('ul').closest('li'),
                $setNext = $set.next();

            if($setNext.length > 0)
                $item.detach().prependTo($setNext.find('.block_competencies_items'))
        }
    }
    
    var deleteHandler = function(){
        
        $(this).closest('li').remove();
        
    }
    
    var createCounter = 0;
    var createHandler = function(e){
        
        e.preventDefault();
        
        var creation = '<li><div id="block_competency_item-n'+createCounter+'">';
        creation += '<div class="controls"><div class="move_up" id="block_competency_item-n'+createCounter+'-up">&uarr;</div><div class="move_down" id="block_competency_item-n'+createCounter+'-down">&darr;</div><div class="delete" id="block_competency_item-n'+createCounter+'-delete">&times;</div></div>';
        creation += '<input type="hidden" name="id" value="n'+createCounter+'" disabled="disabled">';
        creation += '<div><label for="block_competency_item-n'+createCounter+'-ref">Key</label><input type="text" name="ref" id="block_competency_item-n'+createCounter+'-ref"></div>';
        creation += '<div><label for="block_competency_item-n'+createCounter+'-name">Name</label><input type="text" name="name" id="block_competency_item-n'+createCounter+'-name"></div>';
        creation += '<div><label for="block_competency_item-n'+createCounter+'-description">Description</label><textarea id="block_competency_item-n'+createCounter+'-description" name="description"></textarea></div>';
        creation += '</div></li>';
        
        $(this).siblings('.block_competencies_items').append(creation);
        
        var $new = $(this).siblings('.block_competencies_items').children().last();
        $new.find('.controls .move_up').click(upHandler)
        $new.find('.controls .move_down').click(downHandler)
        $new.find('.controls .delete').click(deleteHandler)
        $new.find('.create-control').click(createHandler)
    
        createCounter++
        
    }
    
    var createCategoryCounter = 0;
    var createCategoryHandler = function(e){
        
        e.preventDefault();
        
        var creation = '<li>'
                      +'<h2>Category<span class="delete-category-control">&nbsp;&times;</span></h2>'
                      +'<input type="hidden" name="category_id" value="n'+createCategoryCounter+'" disabled="disabled">'
                      +'<div>'
                      +'<label for="block_competency_set-n'+createCategoryCounter+'-name">Name</label>'
                      +'<input type="text" name="category_name" id="block_competency_set-n'+createCategoryCounter+'-name" value="">'
                      +'</div>'
                      +'<div class="label">Items</div>'
                      +'<ul class="block_competencies_items"></ul>'
                      +'<a class="create-control" id="block_competency_set-n'+createCategoryCounter+'-create" href="#">[Add Item]</a>'
                      +'</li>';
                  
        $('.block_competencies_sets > li').last().before(creation);
        $('#block_competency_set-n'+createCategoryCounter+'-create').click(createHandler)
        $('.block_competencies_sets > li').last().prev().find('.delete-category-control').click(deleteCategoryHandler)
        
        createCategoryCounter++;
        
    }
    
    var deleteCategoryHandler = function(e){
        
        e.preventDefault();
        
        $li = $(this).closest('li');
        
        $li.find('.block_competencies_items li').each(function(){
            
            $(this).detach();
            $('.block_competencies_items').last().append(this);
        })
        
        $li.remove();
        
    }
    
    $('.controls .move_up').click(upHandler)
    $('.controls .move_down').click(downHandler)
    $('.controls .delete').click(deleteHandler)
    $('.create-control').click(createHandler)
    $('.create-category-control').click(createCategoryHandler)
    $('.delete-category-control').click(deleteCategoryHandler)
})

$('#adminsettings').submit(function(e){
    
    e.preventDefault()
    
    sets = {}
    
    $('.block_competencies_sets > li').each(function(){
        
        var items = {}
        
        $(this).find('.block_competencies_items > li').each(function(){
            
            items[$(this).find('input[name="id"]').first().val()] = {
                'ref':$(this).find('input[name="ref"]').first().val(),
                'name':$(this).find('input[name="name"]').first().val(),
                'description':$(this).find('textarea[name="description"]').first().val()
            }
            
        })
        
        sets[$(this).find('input[name="category_id"]').first().val()] = {
            'name':$(this).find('input[name="category_name"]').first().val(),
            'items':items
        }
        
    })
        
    var printMessage = function(cls, msg){
        $('#block_competencies_message').remove();
        $('.block_competencies_sets').before('<div id="block_competencies_message" class="'+cls+'">'+msg+'</div>');
        $('html, body').animate({ scrollTop: 0 }, 0);
    }
    
    var postUrl = '<?php echo $ajax_url; ?>',
        postData = { data: JSON.stringify(sets) }
    
    console.log('Calling '+postUrl+' with POST data '+JSON.stringify(sets))
    
    $.ajax({
        type: "POST",
        url: postUrl,
        data: postData,
        cache: false
    }).done(function(response){
        if(response == true)
            location.reload()
        else
            printMessage('error', 'ERROR: Could not save competencies. Please ensure that all keys are unique.')
        console.log('Response from '+postUrl+' was '+JSON.stringify(response))
    }).fail(function(jqXHR, message){
        printMessage('error', 'ERROR: Could not contact server to save competencies.')
    });
})
