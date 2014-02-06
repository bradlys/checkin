//@author Bradly Schlenker
$(document).ready(function(){
$("#nonefound").hide();
$(".modalMoneyClearer").each(function() { 
    $(this).on("click select", function( event) {
        $("#modalMoney").val($(this).text());
        $("#paymentAmount").val($(this).text());
    });
});
$("#modalMoney").on("propertychange keyup input paste", function(event){
    $("#paymentAmount").val($(this).val());
});
$.post("search.php", { purpose : 'getEvent', eventid : $("#eventID").val() }, function(data) {
    $("#eventName").html(data);
});
$("#save").on("click", function() {
    var money = $("#paymentAmount").val();
    var email = $("#modalEmail").val();
    var name = $("#modalName").val();
    var cid = $("#myModal").find(".cid").val();
    var eventid = $("#eventID").val();
    var checkout = false;
    money = money.replace('$', '');
    $("#myModal").find(".alert").alert('close');
    $.post("search.php", { purpose : "checkin", eventid : eventid, money : money, email : email, name : name, cid : cid, checkout : checkout}, function(data){
        if(data){
            $("#myModal").find("#result").append(makeAlertBox(data));
        }
        else{
            $("#myModal").modal('hide');
            $("#search").val(name);
            updateSearchResults(name);
        }
    });
});
$('#search').each(function() {
    console.log("Here at #search");
    var elem = $(this);
    // Save current value of element
    elem.data('oldVal', elem.val());
    // Look for changes in the value
    elem.bind("propertychange keyup input paste", function(event){
        console.log("Here at bind for search");
        // If value has changed
        if (elem.data('oldVal') !== elem.val() || elem.val() === '') {
            console.log("Here at elem.val() === ''");
            // Updated stored value
            elem.data('oldVal', elem.val());
            // Do action
            updateSearchResults(elem.val());
        }
    });
});

function makeAlertBox(data){
    var alert = '<div class="alert alert-danger fade in" id="modalProblem">\n\ \
                <button class="close" aria-hidden="true" data-dismiss="alert" type="button">\n\ \
                ×\n\ \
                </button>\n\ \
                <h4>\n\ \
                Oh snap! You got an error!\n\ \
                </h4>\n\ \
                <p>\n\ ' + data +
                '\n\ \
                </p>\n\ \
                </div>';
    return alert;
}

$("#myModal").on('hide.bs.modal', function(){
    $(".paymentArea").removeClass("has-success has-feedback");
    $(".glyphicon").remove();
    $("#myModal").find(".alert").alert('close');
});

//Loads up myModal for content
function loadupModal(customerElem){
    var name = customerElem.find("#username").text();
    if(!name || name === 'Add New User'){
        name = $("#search").val();
    }
    $("#modalTitle").text("Checking in " + name);
    $("#modalName").val(name);
    
    var cid = customerElem.find(".cid").text();
    if(cid){
        var email = customerElem.find(".email").text();
        $("#modalEmail").val(email);
        var payment = customerElem.find(".payment").text();
        $("#paymentAmount").val(payment);
        if(payment){
            $(".customMoney").append('<span class="glyphicon glyphicon-ok form-control-feedback" style="right:0px;"></span>');
            $(".paymentArea").addClass("has-success has-feedback");
        }
        $("#modalMoney").val(payment);
        $("#myModal").find(".cid").val(cid);
    }
    else{
        $("#modalEmail").val("");
        $("#paymentAmount").val("");
        $("#modalMoney").val("");
        $("#myModal").find(".cid").val("");
    }
    $("#myModal").modal('show');
}


function updateSearchResults (name){
    $.post("search.php",
        { name : name, id : $("#eventID").val() },
        function ( data ) {
            $("#beforefound").hide();
            $(".customer").remove();
            if(data){
                $("#result").append(data);
                $(".customer").on("click", function ( event ) {
                    loadupModal($(this));
                });
                $(".customer").mouseover(function (event ){
                    $(this).addClass("border-highlight");
                });
                $(".customer").mouseout(function (event ){
                    $(this).removeClass("border-highlight");
                });
                $("#nonefound").hide();
            } else{
                $("#nonefound").show();
            }
    });
}

});