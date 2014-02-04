//@author Bradly Schlenker
$(document).ready(function(){
$('#search').each(function() {
    var elem = $(this);
    // Save current value of element
    elem.data('oldVal', elem.val());
    // Look for changes in the value
    elem.bind("propertychange keyup input paste", function(event){
        // If value has changed
        if (elem.data('oldVal') !== elem.val()) {
            // Updated stored value
            elem.data('oldVal', elem.val());
            // Do action
            $.post("search.php",
            { name : elem.val() },
            function ( data ) {
                $("#result").empty();
                $("#result").append(data);
                
            });
        }
    });
});
});