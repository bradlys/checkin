//@author Bradly Schlenker
$(document).ready(function(){
$("#nonefound").hide();
$(".modalMoneyClearer").each(function() { 
    $(this).on("click select", function( event) {
        $("#modalMoney").val("");
        $("#paymentamount").val($(this).text());
    });
});
$("#modalMoney").on("propertychange keyup input paste", function(event){
    $("#paymentamount").val($(this).val());
});
$.post("search.php", { purpose : 'getEvent', eventid : $("#theid").val() }, function(data) {
    $("#eventName").html(data);
});
$("#save").on("click", function() {
    var money = $("#paymentamount").val();
    var email = $("#modalEmail").val();
    var name = $("#modalName").val();
    var cid = $("#myModal").find(".cid").val();
    var eventid = $("#theid").val();
    var checkout = false;
    money = money.replace('$', '');
    $.post("search.php", { purpose : "checkin", eventid : eventid, money : money, email : email, name : name, cid : cid, checkout : checkout}, function(data){
        $("#modal").modal('hide');
        updateSearchResults($("#search").val());
    });
});
$('#search').each(function() {
    console.log("Here at #search");
    var elem = $(this);
    // Save current value of element
    elem.data('oldVal', elem.val());
    // Look for changes in the value
    elem.bind("propertychange keyup input paste", function(event){
        console.log("Here at bind");
        // If value has changed
        if (elem.data('oldVal') !== elem.val() || elem.val() === '') {
            console.log("Here at elem.val() === ''");
            // Updated stored value
            elem.data('oldVal', elem.val());
            // Do action
            updateSearchResults(elem.val());
            
            $(this).keypress(function (e) {
                console.log("Here at keypress");
                if (e.which === 13) {
                    console.log("Here after the which");
                    var name = $(this).val();
                    loadupModal(name);
                    e.preventDefault();
                }
            });
        }
    });
});

//Loads up myModal for content
function loadupModal(name){
    console.log("Inside loadupModal and name is " + name);
    $("#modaltitle").text("Checking in " + name);
    var cid = isACustomer(name);
    console.log("just after isacustomer and its value " + cid);
    if(cid){
        console.log("Just before modalemail");
        $("#modalEmail").val(getCustomerEmail(cid));
        var payment = getCustomerPayment(cid, $("#theid").val());
        $("#paymentamount").val(payment);
        $("#modalMoney").val(payment);
        $("#modalName").val(name);
        $("#myModal").find(".cid").val(cid);
    }
    else{
        $("#modalEmail").val("");
        $("#paymentamount").val("");
        $("#modalMoney").val("");
        $("#modalName").val(name);
        $("#myModal").find(".cid").val("");
    }
    $("#myModal").modal('show');
}

//Returns false if not a customer. Returns customer's id if they are!
function isACustomer (name){
    var cid = false;
    $.post("search.php", { purpose : "findUser", name: name}, function (data){
        cid = data;
        console.log("Here in isACustomer and cid in post is " + cid);
    });
    return cid;
    console.log("I made it here, but I shouldn't have?");
}

//obtains the customer's email address
function getCustomerEmail (cid){
    var email;
    $.when($.post("search.php", { purpose : "getEmail", cid : cid }, function (data) {
        email = data;
    }));
    return email;
}

//obtains the customer's CID; currently works under presumption customer name is unique.
//That's fine, I just need this to run.
function getCustomerCID (name){
    var cid;
    $.when($.post("search.php", { purpose : "getCID", name : name }, function (data) {
        cid = data;
    }));
    return cid;
}

function updateSearchResults (name){
    $.post("search.php",
        { name : name, id : $("#theid").val() },
        function ( data ) {
            $("#beforefound").hide();
            $(".customer").remove();
            if(data){
                $("#result").append(data);
                $(".customer").on("click", function ( event ) {
                    var name = $(this).find("#username").text();
                    var cid = $(this).find(".cid").text();
                    $("#modalEmail").val(getCustomerEmail(cid));
                    $("#modaltitle").text("Checking in " + name);
                    $("#modalName").val(name);
                    $("#myModal").find(".cid").val(cid);
                    $("#myModal").modal('show');
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