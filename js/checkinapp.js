/**
 * JavaScript to help create the view
 * @author Bradly Schlenker
 */
$(document).ready(function(){
$("#nonefound").hide();


//if(checkin.php) essentially
if($("#search").length > 0){
    checkinPage();
}

//if(events.php)
if($("#eventSearch").length > 0){
    eventsPage();
}

//if(index.php)
if($("#organizationSearch").length > 0){
    organizationsPage();
}

if($("#eventCheckinsTable").length > 0){
    checkinAboutPage();
}

if($("#organizationStatistics").length > 0){
    organizationAboutPage();
}

/**
 * Sets up the event date in the event modal
 * @param {string} date date string
 * @param {boolean} showTime Show time or not.
 */
function setupDate(date, showTime){
    showTime = typeof showTime !== 'undefined' ? showTime : false;
    if(!date || date === "0000-00-00 00:00:00"){
        date = "";
    }
    if(showTime){
        $('#modalDate').datetimepicker().data("DateTimePicker").setDate(date);
    } else {
        if(date){
            date = date.substring(0, 10);
        }
        $('#modalDate').datetimepicker({
            pickTime: true,
            useMinutes: true,
            useSeconds: true
        }).data("DateTimePicker").setDate(date);
    }
}

/**
 * Sets up the organizations page.
 * 
 */
function organizationsPage(){
    updateOrganizationSearchResults("");
    $("#myModal").on('hide.bs.modal', function(){
        updateOrganizationSearchResults($("#organizationSearch").val());
        $("#myModal").find(".alert").alert('close');
    });
    $("#organizationSearch").each(_.throttle(function() {
        var elem = $(this);
        // Save current value of element
        elem.data('oldVal', elem.val());
        // Look for changes in the value
        elem.bind("propertychange keyup input paste", function( event ){
            // If value has changed
            if (elem.data('oldVal') !== elem.val() || elem.val() === '') {
                // Updated stored value
                elem.data('oldVal', elem.val());
                // Do action
                updateOrganizationSearchResults(elem.val());
            }
        });
    }, 400));
    $("#save").on("click", function() {
        var name = $("#modalName").val();
        var organizationID = $("#organizationID").val();
        $("#myModal").find(".alert").alert('close');
        $.post("backend/post.php", { purpose : "editOrganization",  name : name, organizationID : organizationID}, function(json){
            json = $.parseJSON(json);
            if(json.error){
                $("#myModal").find("#result").append(makeAlertBox(json.error));
            }
            else{
                $("#myModal").find("#organizationID").val(json.organizationID);
                $("#myModal").find("#result").append(makeSaveOrganizationSuccessBox(json.success));
                if(json.neworganization){
                    $("#gotoOrganization").show();
                    $("#gotoOrganization").attr("href", "events.php?id=" + json.organizationID);
                }
            }
        });
    });
    updateOrganizationSearchResults("");
}

/**
 * Sets up the check-in page
 */
function checkinPage(){
    setupCheckinModal();
    $('#search').each(function() {
        var elem = $(this);
        // Save current value of element
        elem.data('oldVal', elem.val());
        // Look for changes in the value
        elem.bind("propertychange keyup input paste", _.throttle(function( event ){
            // If value has changed
            if (elem.data('oldVal') !== elem.val() || elem.val() === '') {
                // Updated stored value
                elem.data('oldVal', elem.val());
                // Do action
                updateCheckinSearchResults(elem.val());
            }
        }, 400));
    });
    updateCheckinSearchResults("");
}
/**
 * Sets up the checkin-about page
 */
function checkinAboutPage(){
    setupCheckinModal();
    $(".eventCheckinsTableCustomerRow").on("click", function(event) {
        var rowElem = $(this);
        loadupCheckinAboutModal(rowElem);
    });
}

/**
 * Sets up the checkin modal for proper usage.
 */
function setupCheckinModal(){
    $("#myModal").on('hide.bs.modal', function(){
        $(".paymentArea").removeClass("has-success has-feedback");
        $(".customMoney > .glyphicon").remove();
        $("#myModal").find(".alert").alert('close');
    });
    $(".modalMoneyClearer").each(function() {
        $(this).on("click select", function( event ) {
            $("#modalMoney").val($(this).text());
        });
    });
    $("#save").on("click", function() {
        //Convert format from YYYY-MM-DD to YYYY-MM-DD 00:00:00
        var birthday = $("#modalDate").data().date + " 00:00:00";
        //Modal date doesn't update when you clear the box
        if($("input#modalDateForm.form-control").val() === ""){
            birthday = "";
        }
        var checkinID;
        var cid = $("#myModal").find(".cid").val();
        var email = $("#modalEmail").val();
        var eventID = $("#eventID").text();
        var name = $("#modalName").val();
        var numberOfFreeEntrances = $("#numberOfFreeEntrances").val();
        var payment = $("#modalMoney").val();
        var useFreeEntrance = $("#useFreeEntrance").is(':checked');
        if($("#search").length > 0){
            checkinID = 0;
        } else {
            checkinID = $("#modalCheckinID").text();
        }
        payment = payment.replace('$', '');
        $("#myModal").find(".alert").alert('close');
        $.post("backend/post.php", {
            birthday : birthday,
            checkinID : checkinID,
            cid : cid,
            email : email,
            eventID : eventID,
            name : name,
            numberOfFreeEntrances: numberOfFreeEntrances,
            purpose : "checkinCustomer", 
            payment : payment,
            useFreeEntrance: useFreeEntrance
        },
        function(data){
            data = jQuery.parseJSON(data);
            if(data !== "null" && data.error){
                $("#myModal").find("#result").append(makeAlertBox(data.error));
            }
            else{
                $("#myModal").modal('hide');
                if($("#search").length > 0){
                    $("#search").val("");
                    updateCheckinSearchResults("");
                } else {
                    $("#" + data.checkinID + "").removeClass("danger");
                }
                setupModalCheckout();
            }
        });
    });
    setupModalCheckout();
}

/**
 * Sets up the Checkout Modal. (#modalCheckout)
 * @returns {undefined}
 */
function setupModalCheckout(){
    $("#modalCheckedout").removeClass("btn-success")
                        .addClass("btn-danger").text("Checkout")
                        .attr("id", "modalCheckout").unbind();
    $("#modalCheckout").unbind();
    $("#modalCheckout").on("click", function() {
        var cid = $("#myModal").find(".cid").val();
        var eventID = $("#eventID").text();
        var checkinID = $("#modalCheckinID").text();
        $.post("backend/post.php", {
            purpose : "checkoutCustomer",
            checkinID : checkinID,
            cid : cid,
            eventID : eventID
        },
        function(data){
            if(data !== null){
                $("#myModal").find(".alert").alert('close');
                data = jQuery.parseJSON(data);
                if(data.error){
                    $("#myModal").find("#result").append(makeAlertBox(data.error));
                } else {
                    $("#myModal").find("#result").append(makeSaveEventSuccessBox());
                    $("#numberOfFreeEntrances").val(data['numberOfFreeEntrances']);
                    $("#useFreeEntrance").attr('checked', false);
                    if($("#search").length){
                        $("#search").val("");
                        updateCheckinSearchResults("");
                    } else {
                        $("#" + data.checkinID + "").addClass("danger");
                    }
                    setupModalCheckedout();
                }
            }
        });
    });
}

/**
 * When the modal is used to checkout a customer,
 * the modal needs to be setup to know that a
 * customer has been checked out. So, it changes
 * the button from "Checkout" to "Checked-out"
 * @returns {undefined}
 */
function setupModalCheckedout(){
    $("#modalCheckedout").unbind();
    $("#modalCheckout").removeClass("btn-danger")
                        .addClass("btn-success").text("Checked-out")
                        .attr("id", "modalCheckedout").unbind();
}

/**
 * Sets up the events page
 */
function eventsPage(){
    $("#myModal").on('hide.bs.modal', function(){
        $("#myModal").find(".alert").alert('close');
    });
    $("#eventSearch").each(_.throttle(function() {
        var elem = $(this);
        // Save current value of element
        elem.data('oldVal', elem.val());
        // Look for changes in the value
        elem.bind("propertychange keyup input paste", function( event ){
            // If value has changed
            if (elem.data('oldVal') !== elem.val() || elem.val() === '') {
                // Updated stored value
                elem.data('oldVal', elem.val());
                // Do action
                updateEventSearchResults(elem.val());
            }
        });
    }, 400));
    $("#save").on("click", function() {
        var name = $("#modalName").val();
        var eventID = $("#eventID").text();
        var organizationID = $("#organizationID").val();
        var costFields = [];
        var date = $("#modalDate").data().date;
        //Modal date doesn't update when you clear the box
        if($("input#modalDateForm.form-control").val() === ""){
            date = "";
        }
        $(".costFieldGroup").each(function(e) {
            var first = $(this).find('input:first').val();
            var last = $(this).find('input:last').val();
            if(first !== ""){
                costFields.push({ item : first, cost : last });
            }
        });
        $("#myModal").find(".alert").alert('close');
        //jQuery won't send an empty array
        if(costFields.length === 0){
            costFields = "";
        }
        $.post("backend/post.php",
            { purpose : "editEvent",
            costs : costFields,
            date : date,
            eventID : eventID,
            name : name,
            organizationID : organizationID},
        function(data){
            data = jQuery.parseJSON(data);
            if(data){
            if(typeof data.error !== "undefined"){
                $("#myModal").find("#result").append(makeAlertBox(data.error));
            }
            else{
                if(eventID){
                    $("#myModal").find("#result").append(makeSaveEventSuccessBox());
                }
                else{
                    $("#myModal").modal('hide');
                    updateEventSearchResults($("#eventSearch").val());
                }
            }
            }
        });
    });
    updateEventSearchResults("");
}



/**
 * Makes and returns a Bootstrap success box.
 * This is specifically for when you successfully edit/save an organization in the modal.
 * 
 * @param {string} text a success message
 * @returns {string} the success box as an HTML String
 */
function makeSaveOrganizationSuccessBox(text){
    var box = '<div class="alert alert-success alert-dismissable" id="modalSuccess"> \n\ \
               <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> \n\ \
               <strong>Success!</strong> ' + text + ' \n\ \
               </div>';
    return box;
}

/**
 * Makes and returns a Bootstrap success box.
 * This is specifically for when you successfully edit/save an event in the modal.
 * 
 * @returns {string} the success box as an HTML String
 */
function makeSaveEventSuccessBox(){
    var box = '<div class="alert alert-success alert-dismissable" id="modalSuccess"> \n\ \
               <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> \n\ \
               <strong>Success!</strong> You\'re a real champion at editing! \n\ \
               </div>';
    return box;
}

/**
 * Makes and returns a Bootstrap alert box.
 * 
 * @param {string} string error message
 * @returns {string} the alert box as an HTML String
 */
function makeAlertBox(string){
    var alert = '<div class="alert alert-danger fade in" id="modalProblem">\n\ \
                <button class="close" aria-hidden="true" data-dismiss="alert" type="button">\n\ \
                ×\n\ \
                </button>\n\ \
                <h4>\n\ \
                Oh snap! You got an error!\n\ \
                </h4>\n\ \
                <p>\n\ ' + string +
                '\n\ \
                </p>\n\ \
                </div>';
    return alert;
}

/**
 * Loads up the modal (#myModal on checkin.php) with information about
 * the customer provided in first argument. Allows the customer to be checked-in and
 * have their information modified.
 * 
 * @param {object} customerElem jQuery object; a customer element from a search result
 */
function loadupCheckinModal(customerElem){
    var name = customerElem.find("#username").text();
    var modalTitleText;
    if(name === 'Add New User'){
        modalTitleText = "Adding user ";
    }
    else{
        modalTitleText = "Editing user ";
    }
    if(!name || name === 'Add New User'){
        name = $("#search").val();
    }
    $("#modalTitle").text(modalTitleText + name);
    $("#modalName").val(name);
    $("#modalCheckinID").text(customerElem.find(".checkinID").text());
    var cid = customerElem.find(".cid").text();
    setupModalCheckout();
    if(cid){
        var email = customerElem.find(".email").text();
        var payment = customerElem.find(".payment").text();
        var birthday = customerElem.find(".birthday").text();
        $("#modalEmail").val(email);
        if(payment){
            $(".customMoney").append('<span class="glyphicon glyphicon-ok form-control-feedback" style="right:0px;"></span>');
            $(".paymentArea").addClass("has-success has-feedback");
        }
        $("#numberOfFreeEntrances").val(customerElem.find(".numberOfFreeEntrances").text());
        $("#useFreeEntrance").prop("checked", customerElem.find(".usedFreeEntrance").text() === "true" ? true : false);
        $("#modalMoney").val(payment);
        $("#myModal").find(".cid").val(cid);
        setupDate(birthday);
    }
    else{
        $("#modalEmail").val("");
        $("#modalMoney").val("");
        $("#myModal").find(".cid").val("");
        $("#numberOfFreeEntrances").val("0");
        $("#useFreeEntrance").attr("checked", false);
        setupDate("");
    }
    $("#myModal").modal('show');
}

/**
 * Loads up the modal (#myModal on checkin-about.php) with information about
 * the customer provided in first argument. Allows the customer to be checked-out and
 * have their information modified.
 * 
 * @param {object} rowElem jQuery object; a customer row element from a checkin table
 */
function loadupCheckinAboutModal(rowElem){
    var name = rowElem.find(".customerName").text();
    var modalTitleText = "Editing user " + name;
    $("#modalTitle").text(modalTitleText);
    $("#modalName").val(name);
    
    var checkinID = rowElem.find(".checkinID").text();
    $("#modalCheckinID").text(checkinID);
    if(checkinID){
        $.post("backend/post.php", { purpose : "getCustomerByCheckinID", checkinID : checkinID}, function(data) {
            if(data){
                data = jQuery.parseJSON(data);
            } else {
                console.log("No data returned for " + checkinID);
                return;
            }
            var email = data.email;
            var payment = data.payment;
            var usedFreeEntrance = data.usedFreeEntrance;
            var numberOfFreeEntrances = data.numberOfFreeEntrances;
            var cid = data.cid;
            var isCheckedIn = data.isCheckedIn;
            if(isCheckedIn){
                setupModalCheckout();
            } else {
                setupModalCheckedout();
            }
            $("#modalEmail").val(email);
            if(payment){
                $(".customMoney").append('<span class="glyphicon glyphicon-ok form-control-feedback" style="right:0px;"></span>');
                $(".paymentArea").addClass("has-success has-feedback");
            }
            $("#numberOfFreeEntrances").val(numberOfFreeEntrances);
            $("#useFreeEntrance").prop("checked", usedFreeEntrance === "true" ? true : false);
            $("#modalMoney").val(payment);
            $("#myModal").find(".cid").val(cid);
            setupDate(data.birthday);
            
            $("#myModal").modal('show');
        });
    }
}

/**
 * Loads up the modal (#myModal on events.php) with information about
 * the event provided in first argument. Allows for editing of events
 * and going to them.
 * 
 * @param {object} eventElem jQuery object; an event element from a search result
 */
function loadupEventModal(eventElem){
    var name = eventElem.find("#eventResultName").text();
    var modalTitleText;
    if(name === 'Add New Event'){
        modalTitleText = "Adding";
    }
    else{
        modalTitleText = "Editing";
    }
    if(!name || name === 'Add New Event'){
        name = $("#eventSearch").val();
    }
    $("#modalTitle").text(modalTitleText + " event " + name);
    $("#modalName").val(name);
    
    var eventID = eventElem.find(".eventResultID").text();
    if(eventID){
        $("#eventID").text(eventID);
        $("#gotoEvent").show();
        $("#gotoEvent").attr("href", "checkin.php?id=" + eventID);
        $.post("backend/post.php",
        { purpose : "getEventDate", eventID: eventID},
        function ( data ) {
            if(data === '""'){
                setupDate('', true);
            } else {
                setupDate(data, true);
            }
        });
    }
    else{
        $("#eventID").text("");
        $("#gotoEvent").hide();
        setupDate('', true);
    }
    $.post("backend/post.php",
        { purpose : "getEventCosts", eventID : eventID},
        function ( data ) { setupDynamicCostForms(data); });
    $("#myModal").modal('show');
}

/**
 * Loads up the modal (#myModal on index.php) with information about
 * the organization provided in first argument. Allows for editing of organizations
 * and loading them for viewing events.
 * 
 * @param {object} organizationElem jQuery object; an organization element from a search result
 */
function loadupOrganizationModal(organizationElem){
    var name = organizationElem.find("#organizationResultName").text();
    var modalTitleTextBegin;
    if(name === 'Add New Organization'){
        modalTitleTextBegin = "Adding New Organization";
    }
    else{
        modalTitleTextBegin = "Editing An Existing Organization";
    }
    if(!name || name === 'Add New Organization'){
        name = $("#organizationSearch").val();
    }
    var modalTitleTextEnd = name;
    if(name){
        modalTitleTextEnd = " - " + name;
    }
    $("#modalTitle").text(modalTitleTextBegin + modalTitleTextEnd);
    $("#modalName").val(name);
    
    var organizationResultID = organizationElem.find(".organizationResultID").text();
    if(organizationResultID){
        $("#organizationID").val(organizationResultID);
        $("#gotoOrganization").show();
        $("#gotoOrganization").attr("href", "events.php?id=" + organizationResultID);
    }
    else{
        $("#gotoOrganization").hide();
    }
    $("#myModal").modal('show');
}

/**
 * Updates the search results div (#result) based on searching the
 * database for customers whose name contains the first argument supplied.
 * 
 * 
 * @param {string} name - The string to search for (based on whether the desired string contains this param)
 * @param {number} limit - The highest amount of search results to be returned. Defaults to 10.
 */
function updateCheckinSearchResults (name, limit){
    if(!limit){
        limit = 10;
    }
    $.post("backend/post.php",
        { purpose : "searchCustomers", name : name, eventID : $("#eventID").text(), limit : limit },
        function ( data ) {
            $("#beforefound").hide();
            $(".customer").remove();
            if(data){
                $("#result").append(displayCheckinSearchResults(data));
                $(".customer").on("click", function ( event ) {
                    if($("#seemore").is($(this))){
                        updateCheckinSearchResults(name, (limit + 8) );
                    } else {
                        loadupCheckinModal($(this));
                    }
                });
                $(".customer").mouseover(function ( event ){
                    $(this).addClass("border-highlight");
                });
                $(".customer").mouseout(function ( event ){
                    $(this).removeClass("border-highlight");
                });
                $("#nonefound").hide();
            } else{
                $("#nonefound").show();
            }
    });
}

/**
 * Updates the search results div (#eventResultArea) based on searching the
 * database for events whose name contains the first argument supplied.
 * 
 * 
 * @param {string} name The string to search for (based on whether the desired string contains this param)
 */
function updateEventSearchResults (name){
    $.post("backend/post.php",
        { purpose : "searchEvents", name : name, organizationID : $("#organizationID").val() },
        function ( data ) {
            $("#beforefound").hide();
            $(".eventResultItem").remove();
            if(data){
                $("#eventResultArea").append(displayEventSearchResults(data));
                $(".eventResultItem").on("click", function ( event ) {
                    loadupEventModal($(this));
                });
                $(".eventResultItem").mouseover(function ( event ){
                    $(this).addClass("border-highlight");
                });
                $(".eventResultItem").mouseout(function ( event ){
                    $(this).removeClass("border-highlight");
                });
                if($(".eventResultID").length > 0){
                    $("#nonefound").hide();
                } else {
                    $("#nonefound").show();
                }
            }
    });
}

/**
 * Updates the search results div (#organizationResult) based on searching the
 * database for organizations whose name contains the first argument supplied.
 * 
 * 
 * @param {string} name The string to search for (based on whether the desired string contains this param)
 */
function updateOrganizationSearchResults (name) {
    $.post("backend/post.php",
        { purpose : "searchOrganizations", name : name },
        function ( data ) {
            $("#beforefound").hide();
            $(".organizationResultItem").remove();
            if(data){
                $("#organizationResult").append(displayOrganizationSearchResults(data));
                $(".organizationResultItem").on("click", function ( event ) {
                    loadupOrganizationModal($(this));
                });
                $(".organizationResultItem").mouseover(function ( event ){
                    $(this).addClass("border-highlight");
                });
                $(".organizationResultItem").mouseout(function ( event ){
                    $(this).removeClass("border-highlight");
                });
                if($(".organizationResultID").length > 0){
                    $("#nonefound").hide();
                } else {
                    $("#nonefound").show();
                }
            }
    });
}


/**
 * Formats data that was returned from searching the customer database into customer divs.
 * @param {string} data JSON string of customer information returned by search
 * @returns {string} returns customer divs as a string
 */
function displayCheckinSearchResults (data) {
    var returnString = '';
    var parsedData = jQuery.parseJSON(data);
    var tmpString = '';
    var customers = parsedData['customers'];
    for (var i = 0; i < customers.length; i++){
        var customer = customers[i];
        tmpString =
            '<div class="customer col-xs-3">' +
            '<span class="birthday">' + customer['birthday'] + '</span>' + 
            '<span class="checkinID">' + customer['checkinID'] + '</span>' + 
            '<span class="cid">' + customer['cid'] + '</span>' +
            '<span class="email">' + customer['email'] + '</span>' +
            '<span class="payment">' + customer['payment'] + '</span>' +
            '<span class="usedFreeEntrance">' + customer['usedFreeEntrance'] + '</span>' +
            '<span class="numberOfFreeEntrances">' + customer['numberOfFreeEntrances'] + '</span>' +
            '<div id="username">' + customer['name'] + '</div>' + 
            '<div id="visits">' + customer['visits'] + ' visits</div>' +
            (customer['isCheckedIn'] ? '<small>Already Checked In</small>' : '') +
            '</div>';
        returnString = returnString + tmpString;
    }
    if(parsedData['numberOfExtra'] > 0){
        returnString = returnString + '<div class="customer col-xs-3" id="seemore"><div id="username">' + (parsedData['numberOfExtra']) + ' more...</div></div>';
    }
    returnString = returnString + '<div class="customer col-xs-3" id="newuser"><div id="username">Add New User</div></div>';
    return returnString;
}

/**
 * Formats data that was returned from searching the organization database into organizationResultItem divs.
 * @param {string} data JSON string of organization information returned by search
 * @returns {string} returns organization divs as a string
 */
function displayOrganizationSearchResults (data) {
    var returnString = '';
    var organizations = jQuery.parseJSON(data);
    var tmpString = '';
    for (var i = 0; i < organizations.length; i++){
        var organization = organizations[i];
        tmpString =
                '<div class="organizationResultItem col-xs-3">' + 
                '<span class="organizationResultID">' + organization['organizationResultID'] + '</span>' +
                '<div id="organizationResultName">' + organization['organizationResultName'] + '</div>' +
                '</div>';
        returnString = returnString + tmpString;
    }
    returnString = returnString + '<div class="organizationResultItem col-xs-3" id="newEvent"><div id="organizationResultName">Add New Organization</div></div>';
    return returnString;
}

/**
 * Formats data that was returned from searching the events database into eventResultItem divs.
 * @param {string} data JSON string of event information returned by search
 * @returns {string} returns event divs as a string
 */
function displayEventSearchResults (data) {
    var returnString = '';
    var events = jQuery.parseJSON(data);
    var tmpString = '';
    for (var i = 0; i < events.length; i++){
        var event = events[i];
        var eventDate = event['eventResultDate'];
        if(eventDate === "0000-00-00 00:00:00" || eventDate === ""){
            eventDate = "";
        }
        tmpString =
                '<div class="eventResultItem col-xs-3">' +
                '<span class="eventResultID">' + event['eventResultID'] + '</span>' +
                '<div id="eventResultName">' + event['eventResultName'] + '</div>' +
                '<span class="eventResultDate">' + eventDate + '</span>' +
                '</div>';
        returnString = returnString + tmpString;
    }
    returnString = returnString + '<div class="eventResultItem col-xs-3" id="newEvent"><div id="eventResultName">Add New Event</div></div>';
    return returnString;
}

/**
 * Sets up the cost forms for event modal
 * @param {string} retrievedFormData JSON String (encoded JSON)
 * containing cost form information.
 */
function setupDynamicCostForms( retrievedFormData ) {
    $(".costFieldGroup").each(function(){
        $(this).remove();
    });
    var newElem = '<div class="entry input-group col-sm-6 costFieldGroup"> \n\
        <input class="costField" name="fields[]" type="text" placeholder="" /> \n\
        <input class="costField" name="fields[]" type="text" placeholder="" /> \n\
        <span class="input-group-btn"> \n\
            <button class="btn btn-success btn-add" type="button"> \n\
                <span class="glyphicon glyphicon-plus"></span> \n\
            </button> \n\
        </span> \n\
        </div>';
    $('.controls form:first').append($(newElem));
    retrievedFormData = jQuery.parseJSON(retrievedFormData);
    $(document).off('click', '.btn-add').on('click', '.btn-add', function(e)
    {
        e.preventDefault();

        var controlForm = $('.controls form:first'),
            currentEntry = $(this).parents('.entry:first'),
            newEntry = $(currentEntry.clone()).appendTo(controlForm);

        newEntry.find('input').val('');
        controlForm.find('.entry:not(:last) .btn-add')
            .removeClass('btn-add').addClass('btn-remove')
            .removeClass('btn-success').addClass('btn-danger')
            .html('<span class="glyphicon glyphicon-minus"></span>');
    }).off('click', '.btn-remove').on('click', '.btn-remove', function(e)
    {
        $(this).parents('.entry:first').remove();
        e.preventDefault();
        return false;
    });
    if(retrievedFormData){
        for(var i = 0; i < retrievedFormData.length; i++){
            var cf = $('.controls form:first'),
                    ce = $(".btn-add").parents('.entry:first'),
                    ne = $(ce.clone()).prependTo(cf);
            ne.find('input:first').val(retrievedFormData[i]['item']);
            ne.find('input:last').val(retrievedFormData[i]['cost']);
            cf.find('.entry:not(:last) .btn-add')
                .removeClass('btn-add').addClass('btn-remove')
                .removeClass('btn-success').addClass('btn-danger')
                .html('<span class="glyphicon glyphicon-minus"></span>');
        }
    }
}

/**
 * Sets up the organization-about.php page
 * Currently full of filler
 */
function organizationAboutPage(){
    //This used to have d3.js material.
    //I'm waiting on implementing this.
    //Back-end comes first.
}
});
