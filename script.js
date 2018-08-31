jQuery(document).ready(function($){


	// Disable Links
	$('.menu_editorbody #adminmenu a').click(function(e) {

		e.preventDefault();

	});


	// CHECKBOX WORKS
	var topLinkHref = "";
	$('.menu_editorbody #adminmenu li:not(.wp-submenu-head):not(#collapse-menu)').each(function(index) {

		var li = $(this);

		// Skip if it has empty link
		//if ( li.children('a').text() == "" ) return true;

		var isSeparator = li.hasClass('wp-menu-separator');
		var menuType = li.hasClass('menu-top') || isSeparator ? "topitem" : "subitem";

		var link = "";
		var value = "";
		var checked = "";


		if (isSeparator) {

			var classList = li.attr('class').split(/\s+/);
			var sepName = classList[classList.length-1];

			value = sepName;

			if ( amr_data_top.indexOf(sepName) > -1 ) {

				checked = "checked";

			}

		} else if (menuType == "topitem") {

			link = li.children('a');
			topLinkHref = link.attr('href').replace('admin.php?page=', '').replace(/\&/g, '&amp;');

			value = topLinkHref +' | key';

			if ( amr_data_top.indexOf(topLinkHref) > -1 ) {

				checked = "checked";

			}

		} else if (menuType == "subitem") {

			link = li.children('a'); console.log(link.attr('href'));
			var linkHref = link.attr('href').replace('admin.php?page=', '').replace(topLinkHref + '?page=', '').replace(topLinkHref + '&page=', '').replace(/\&/g, '&amp;');

			value = topLinkHref +' | ' + linkHref;

			if ( amr_data_sub.indexOf(value) > -1 ) {

				checked = "checked";

			}

		}



		// Add the input
		li.prepend('<input type="checkbox" class="'+ menuType +'" name="'+ menuType +'__'+ index +'" value="'+ value + '" '+ checked + '/>');

	});



	// Check / Uncheck All
	$('#checkall').click(function(){
		$('.menu_editorbody #adminmenu input').prop( 'checked', true );
	});

	$('#uncheckall').click(function(){
		$('.menu_editorbody #adminmenu input').prop( 'checked', false );
	});



	// WARNING
	$('#menu-editor-form').submit(function () {

		if ($("#menu-editor-form input[type='checkbox']:checked").length == 0) {

		    return confirm('All the restrictions are going to be removed for this role. Do you confirm?');

		}

	});



/*
	// INPUT CHECKS
	$('.menu_editorbody #adminmenu input').change(function() {

		var isTop = $(this).hasClass('topitem');

		if ( isTop ) {

			$(this).parent('.wp-has-submenu').find('input').prop('checked', $(this).is(':checked'));

		} else {

			var subInputCount = $(this).parents('.wp-submenu').find('input').length; console.log(subInputCount);
			var subInputCheckedCount = $(this).parents('.wp-submenu').find('input:checked').length; console.log(subInputCheckedCount);
			var topInput = $(this).parents('.wp-has-submenu').children('input');

			topInput.prop('checked', (subInputCount == subInputCheckedCount));

		}

	});
*/


}); // document ready