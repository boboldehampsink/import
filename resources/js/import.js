$(function() {

    // Find entry types by chosen section
    $('[name="importSection"]').change(function() {
    
        $('#entrytypes').html('');
        Craft.postActionRequest('import/getEntryTypes', { 'section': $(this).val() }, function(entrytypes) {
                
            $.each(entrytypes, function(index, value) {
                $('#entrytypes').append('<option value="' + value.id + '">' + value.name + '</option>');
            });
        
        });
        
    });
    
    // Make sure each field gets mapped once
    $('.mapper select').change(function() {
    
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
    
});