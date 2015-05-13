$(function() {

    if($('#types').length) {
    
        // Show the fields that match the import type
        $('#types').change(function() {
        
            $('.type').hide().find('input, select').prop('disabled', true);
            $('.' + $(this).val().toLowerCase()).show().find('input, select').prop('disabled', false);     
            
        });
        
        // Trigger change on load
        $('#types').trigger('change');

    }
        
    // Find entry types by chosen section
    $(document).on('change', '#sections', function() {
    
        $('#entrytypes').html('');
        Craft.postActionRequest('import/getEntryTypes', { 'section': $(this).val() }, function(entrytypes) {
                
            $.each(entrytypes, function(index, value) {
                $('#entrytypes').append('<option value="' + value.id + '">' + value.name + '</option>');
            });
        
        });
        
    });
    
    // Only show backup option when receiving email
    $(document).on('change', '#email', function() {
        $('#backup').prop('disabled', !$(this).is(':checked'));
    });
    
    if($('.mapper select').length) {
    
        // Make sure each field gets mapped once
        $(document).on('change', '.mapper select', function() {
        
            // Disable chosen option for all mapper fields
            $('.mapper select option:disabled').prop('disabled', false);
            $('.mapper select option:selected').each(function() {
                   
                // Not import field can always get mapped
                if($(this).val() != 'dont') {
                    $('.mapper select option[value="' + $(this).val() + '"]').not($(this)).prop('disabled', true);
                }
                
            });
            
        });
        
        // Trigger on load also
        $('.mapper select').trigger('change');
    
    }
    
});