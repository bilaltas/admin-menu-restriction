jQuery(document).ready(function($){

    // **********************************************************************// 
    // ! FOR MENU EDITOR SECTION
    // **********************************************************************//
    
    //$('.menu_editorbody li.hide-if-no-customize').css('display', 'none');
    //$('.menu_editorbody li.hide-if-no-customize:first').css('display', 'list-item');
    
    $('.menu_editorbody li.hide-if-no-customize').each(function(e) {
	    
	    
	    var parentul  = $( this ).parent().get( 0 ).id;
	    var thisli 	  = $( this ).get( 0 ).id;
	    var thislabel = $( "#" + thisli + " label" ).text();
	    
	    $('#' + parentul + ' li label:contains("' + thislabel + '")').parent().not(".hide-if-no-customize").css('display', 'none');
	    
    });
    
    

    $('.menu_editorbody li input.subitem').click(function(e) {
	    
	    var parentul = $( this ).parent().parent().get( 0 ).id;
	    var thisli = $( this ).parent().get( 0 ).id;
	    var thislabel = $( "#" + thisli + " label" ).text();

	    
	    if ( this.checked ) {
		    
		    //$('#' + parentul + ' li.hide-if-no-customize > input').prop( 'checked', true );
		    //$('#' + parentul + ' li.hide-if-no-customize label').addClass('checked-label');
		    
		    $('#' + parentul + ' li label:contains("' + thislabel + '")').prev().prop( 'checked', true );
		    $('#' + parentul + ' li label:contains("' + thislabel + '")').addClass('checked-label');
		    
		} else {
			
			//$('#' + parentul + ' li.hide-if-no-customize > input').prop( 'checked', false );
		    //$('#' + parentul + ' li.hide-if-no-customize label').removeClass('checked-label');
		    
		    $('#' + parentul + ' li label:contains("' + thislabel + '")').prev().prop( 'checked', false );
		    $('#' + parentul + ' li label:contains("' + thislabel + '")').removeClass('checked-label');
			
		}
		
    });

        
	$('#checkall').click(function(){
		$('.topmenu-items input').prop( 'checked', true );
		$('.topmenu-items label').addClass('checked-label');
	});
	
	$('#uncheckall').click(function(){
		$('.topmenu-items input').prop( 'checked', false );
		$('.topmenu-items label').removeClass('checked-label');
	});
	
	
	// SET COLOR
	$('input[type="checkbox"]').each(function(e) {
        if ($(this).is(":checked")) {
            $( '#' + this.id + ' + label').addClass('checked-label');
        }
    });
    
	//if ($('li input').is(':checked')) {
		//$( this.id + ' label').css( "background-color", "rgba(255, 123, 0, 0.44)" );
	//} else {
		
	//}
	
	
	$( "li.topmenu-item input.topitem" ).click(function(e) {
		if (this.checked) {
			$('li#top-' + e.target.id + ' + ul li input').prop( 'checked', true );

			// COLOR
			$('li#top-' + e.target.id + ' input + label').addClass('checked-label');
			$('li#top-' + e.target.id + ' + ul li input + label').addClass('checked-label');
    	} else {
	    	
	    	// COLOR
			$('li#top-' + e.target.id + ' input + label').removeClass('checked-label');
			$('li#top-' + e.target.id + ' + ul li input + label').removeClass('checked-label');
	    	
	    	
	    	if ($('li#top-' + e.target.id + ' + ul li input:checked').length == $('li#top-' + e.target.id + ' + ul li input').length) {
		    	$('li#top-' + e.target.id + ' + ul li input').prop( 'checked', false );	
		    }
    	}

	});
	
	
	$( "li.submenu-item input.subitem" ).click(function(e) {
		
		if (this.checked) {
			// COLOR
			$('li#sub-' + e.target.id + ' input + label').addClass('checked-label');
    	} else {
	    	
	    	// COLOR
			$('li#sub-' + e.target.id + ' input + label').removeClass('checked-label');
    	}
				
		var parentID = $( this ).parent().parent().get( 0 ).id;
		var bigparentID = $( this ).parent().parent().prev().get( 0 ).id;
			
		if(!$(this).is(':checked')) {
			
			
			if ($('#' + parentID + ' input').filter(':checked').length > 0) {
		        //alert('at least one checked');
		        $('#' + bigparentID + ' input').prop( 'checked', false );
				
				// COLOR
				$('#' + bigparentID + ' input + label').removeClass('checked-label');
		    } else {
		        //alert('none of them is checked');
		        $('#' + bigparentID + ' input').prop( 'checked', false );
				
				// COLOR
				$('#' + bigparentID + ' input + label').removeClass('checked-label');
		    }
		    
		    
    	}
    	if ($('#' + parentID + ' input:checked').length == $('#' + parentID + ' input').length) {
				//alert('everybox is checked');
				$('#' + bigparentID + ' input').prop( 'checked', true );
	
				// COLOR
				$('#' + bigparentID + ' input + label').addClass('checked-label');
    	}

	});
	

	$('#menu-editor-form').submit(function () {
		
		if ($("#menu-editor-form input:checkbox:checked").length == 0) {
		    
		    return confirm('All the restrictions are going to be removed for this role. Do you confirm?');
		    
		}
		
	});




}); // document ready