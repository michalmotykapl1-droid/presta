{extends file="helpers/form/form.tpl"}

{block name="script"}
{if $is_bootstrap}
	$(document).ready(function(){
		
		$(document).on('change', '#categories-tree input', function(){
			load_import_categories();
		});

        $(document).on('click', '#check-all-categories-tree, #uncheck-all-categories-tree, .tt-suggestion.tt-is-under-cursor', function(){
            load_import_categories();
        });
		
		load_import_categories()
		
		function load_import_categories() {
			var _select = $('#id_category');
			var selected_value = $('#id_category').val();
			_select.empty();
			$('#categories-tree input:checked, #categories-treeview input:checked').each(function(){
				_select.append('<option '+(($(this).attr('value') == selected_value) ? 'selected="selected"' : '')+' value="'+$(this).attr('value')+'">'+$(this).siblings('label').text()+'</option>');
			});
		}
		
	});
{else}
	$('#categories-treeview').on('change', 'input', function() {
        if ($(this).is(':checked')) {
            $('select#id_category').append('<option value="'+$(this).val()+'">'+($(this).val() !=1 ? $(this).parent().find('span').html() : home)+'</option>');
            updateNbSubCategorySelected($(this), true);
        }
        else {
            $('select#id_category option[value='+$(this).val()+']').remove();
            updateNbSubCategorySelected($(this), false);
        }
    });
{/if}
{/block}
