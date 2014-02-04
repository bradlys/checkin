







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
            var posting = $.post("search.php", { name: elem.val() });
            posting.done(function ( data ) {
                $("#result").empty();
                var temp;
                for(var i in data){
                    temp = data[i];
                    $("#result").append(temp);
                }
            });
        }
    });
});