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

/**
 * Sets up the organizations page.
 * 
 * @returns {null}
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
        var checkout = false;
        $("#myModal").find(".alert").alert('close');
        $.post("backend/search.php", { purpose : "editOrganization",  name : name, organizationID : organizationID, checkout : checkout}, function(json){
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
 * 
 * @returns {null}
 */
function checkinPage(){
    updateCheckinSearchResults("");
    $("#myModal").on('hide.bs.modal', function(){
        $(".paymentArea").removeClass("has-success has-feedback");
        $(".customMoney > .glyphicon").remove();
        $("#myModal").find(".alert").alert('close');
    });
    $("#modalMoney").on("propertychange keyup input paste", function( event ){
        $("#paymentAmount").val($(this).val());
    });
    $(".modalMoneyClearer").each(function() {
        $(this).on("click select", function( event ) {
            $("#modalMoney").val($(this).text());
            $("#paymentAmount").val($(this).text());
        });
    });
    $.post("backend/search.php", { purpose : 'getEvent', eventid : $("#eventID").val() }, function(data) {
        $("#eventName").html(jQuery.parseJSON(data).name);
    });
    $("#save").on("click", function() {
        var money = $("#paymentAmount").val();
        var email = $("#modalEmail").val();
        var name = $("#modalName").val();
        var cid = $("#myModal").find(".cid").val();
        var eventid = $("#eventID").val();
        var checkout = false;
        var useFreeEntrance = $("#useFreeEntrance").is(':checked');
        var numberOfFreeEntrances = $("#numberOfFreeEntrances").val();
        var date = $("#modalDate").data().date;
        money = money.replace('$', '');
        $("#myModal").find(".alert").alert('close');
        $.post("backend/search.php", {
            purpose : "checkinCustomer", 
            eventid : eventid,
            money : money,
            email : email,
            name : name,
            cid : cid,
            checkout : checkout,
            useFreeEntrance: useFreeEntrance,
            numberOfFreeEntrances: numberOfFreeEntrances,
            date : date},
        function(data){
            if(data){
                $("#myModal").find("#result").append(makeAlertBox(data));
            }
            else{
                $("#myModal").modal('hide');
                $("#search").val(name);
                updateCheckinSearchResults(name);
            }
        });
    });
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
 * Sets up the events page
 * 
 * @returns {null}
 */
function eventsPage(){
    updateEventSearchResults("");
    $.post("backend/search.php", { purpose : 'getOrganization', organizationID : $("#organizationID").val() }, function(data) {
        $("#organizationName").html(jQuery.parseJSON(data).name);
    });
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
        var eventID = $("#eventID").val();
        var organizationID = $("#organizationID").val();
        var checkout = false;
        var costFields = [];
        var date = $("#modalDate").data().date;
        $(".costFieldGroup").each(function(e) {
            var first = $(this).find('input:first').val();
            var last = $(this).find('input:last').val();
            if(first === ""){
            } else {
                costFields.push({ item : first, cost : last });
            }
        });
        $("#myModal").find(".alert").alert('close');
        $.post("backend/search.php",
            { purpose : "editEvent",
            eventid : eventID,
            name : name,
            organizationID : organizationID,
            checkout : checkout,
            costs : costFields,
            date : date},
        function(data){
            if(data){
                $("#myModal").find("#result").append(makeAlertBox(data));
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
        });
    });
    updateEventSearchResults("");
}



/**
 * Makes and returns a Bootstrap success box.
 * This is specifically for when you successfully edit/save an organization in the modal.
 * 
 * @param {String} jsontext - a success message, usually in the form of a string
 * @returns {String} - the success box as an HTML String
 */
function makeSaveOrganizationSuccessBox(jsontext){
    var box = '<div class="alert alert-success alert-dismissable" id="modalSuccess"> \n\ \
               <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> \n\ \
               <strong>Success!</strong> ' + jsontext + ' \n\ \
               </div>';
    return box;
}

/**
 * Makes and returns a Bootstrap success box.
 * This is specifically for when you successfully edit/save an event in the modal.
 * 
 * @returns {String} - the success box as an HTML String
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
 * @param {element} data - an error message, usually in the form of a string
 * @returns {String} - the alert box as an HTML String
 */
function makeAlertBox(data){
    var alert = '<div class="alert alert-danger fade in" id="modalProblem">\n\ \
                <button class="close" aria-hidden="true" data-dismiss="alert" type="button">\n\ \
                ×\n\ \
                </button>\n\ \
                <h4>\n\ \
                Oh snap! You got an error!\n\ \
                </h4>\n\ \
                <p>\n\ ' + jQuery.parseJSON(data).error +
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
 * @param {element} customerElem - a customer element that from a search result
 * @returns {null}
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
        $("#numberOfFreeEntrances").val(customerElem.find(".numberOfFreeEntrances").text());
        $("#useFreeEntrance").prop("checked", customerElem.find(".usedFreeEntrance").text() === "true" ? true : false);
        $("#modalMoney").val(payment);
        $("#myModal").find(".cid").val(cid);
        $.post("backend/search.php", { purpose : "getCustomerBirthday", cid : cid}, function(data) {
            setupDate(data);
        });
    }
    else{
        $("#modalEmail").val("");
        $("#paymentAmount").val("");
        $("#modalMoney").val("");
        $("#myModal").find(".cid").val("");
        $("#numberOfFreeEntrances").val("0");
        $("#useFreeEntrance").attr("checked", false);
        setupDate();
    }
    $("#myModal").modal('show');
}

//Loads up the modal for events.php
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
    
    var eventResultID = eventElem.find(".eventResultID").text();
    if(eventResultID){
        $("#eventID").val(eventResultID);
        $("#gotoEvent").show();
        $("#gotoEvent").attr("href", "checkin.php?id=" + eventResultID);
    }
    else{
        $("#gotoEvent").hide();
    }
    $.post("backend/search.php",
        { purpose : "getEventDate", eventID: $("#eventID").val()},
        function ( data ) { setupDate(data); });
    $.post("backend/search.php",
        { purpose : "getEventCosts", eventID : $("#eventID").val()},
        function ( data ) { setupDynamicCostForms(data); });
    $("#myModal").modal('show');
}

//Loads up the organization modal for index.php
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
 * @param {integer} limit - The highest amount of search results to be returned. Defaults to 10.
 * @returns {null}
 */
function updateCheckinSearchResults (name, limit){
    if(!limit){
        limit = 10;
    }
    $.post("backend/search.php",
        { purpose : "searchCustomersFastestReduxCombo", name : name, eventID : $("#eventID").val(), limit : limit },
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
 * @param {string} name - The string to search for (based on whether the desired string contains this param)
 * @returns {null}
 */
function updateEventSearchResults (name){
    $.post("backend/search.php",
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
 * @param {string} name - The string to search for (based on whether the desired string contains this param)
 * @returns {null}
 */
function updateOrganizationSearchResults (name) {
    $.post("backend/search.php",
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
 * @param {JSON} data - JSON string of customer information returned by search
 * @returns {String} - returns customer divs as a string
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
 * @param {JSON} data - JSON string of organization information returned by search
 * @returns {String} - returns organization divs as a string
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
 * @param {JSON} data - JSON string of event information returned by search
 * @returns {String} - returns event divs as a string
 */
function displayEventSearchResults (data) {
    var returnString = '';
    var events = jQuery.parseJSON(data);
    var tmpString = '';
    for (var i = 0; i < events.length; i++){
        var event = events[i];
        tmpString =
                '<div class="eventResultItem col-xs-3">' +
                '<span class="eventResultID">' + event['eventResultID'] + '</span>' +
                '<div id="eventResultName">' + event['eventResultName'] + '</div>' +
                '</div>';
        returnString = returnString + tmpString;
    }
    returnString = returnString + '<div class="eventResultItem col-xs-3" id="newEvent"><div id="eventResultName">Add New Event</div></div>';
    return returnString;
}

/**
 * Sets up the cost forms
 * @param {JSON} retrievedFormData
 * @returns {null}
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
 * Sets up the event date in the event modal
 * @param {JSON} data - JSON string with date for data['date']
 * @returns {undefined}
 */
function setupDate (data){
    data = jQuery.parseJSON(data);
    var date = data['date'];
    if(!date){
        date = "";
    } else {
        //
    }
    $('#modalDate').datetimepicker().data("DateTimePicker").setDate(date);
}

});