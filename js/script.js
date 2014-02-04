//@author Bradly Schlenker
$(document).ready(function(){
$("#nonefound").hide();
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
                $("#beforefound").hide();
                $(".customer").remove();
                if(data){
                    $("#result").append(data);
                    $(".customer.thumbnail").on("click", function ( event ) {
                        var name = $(this).find("#username").text();
                        $("#modaltitle").text("Checking in " + name);
                        $("#modalName").val(name);
                        $("#myModal").modal('show');
                        
                    });
                    $("#nonefound").hide();
                } else{
                    $("#nonefound").show();
                }
            }); 
           
            $(this).keypress(function (e) {
                if (e.which === 13) {
                    var name = $(this).val();
                    $("#modaltitle").text("Checking in " + name);
                    $("#modalName").val(name);
                    $("#myModal").modal('show');
                    e.preventDefault();
                }
            });
        }
    });
});
});