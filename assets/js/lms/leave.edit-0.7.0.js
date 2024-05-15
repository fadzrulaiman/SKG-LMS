/**
 * This Javascript code is used on the create/edit leave request
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since      0.3.0
 */

// Try to calculate the length of the leave
function getLeaveLength(refreshInfos = true) {
    const start = moment($('#startdate').val());
    const end = moment($('#enddate').val());
    const startType = $('#startdatetype').val();
    const endType = $('#enddatetype').val();

    if (start.isValid() && end.isValid()) {
        if (start.isSame(end)) {
            displaySingleDayLeave(startType, endType);
        } else if (start.isBefore(end)) {
            displayMultiDayLeave(startType, endType);
        }
        if (refreshInfos) getLeaveInfos(false);
    }
}

function displaySingleDayLeave(startType, endType) {
    const dayTypeMapping = {
        "MorningMorning": "leave_1d_MM.png",
        "AfternoonAfternoon": "leave_1d_AA.png",
        "MorningAfternoon": "leave_1d_MA.png",
        "AfternoonMorning": "date_error.png"
    };
    const key = startType + endType;
    const imgSrc = baseURL + "assets/images/" + (dayTypeMapping[key] || "date_error.png");
    $("#spnDayType").html("<img src='" + imgSrc + "' />");
}

function displayMultiDayLeave(startType, endType) {
    const dayTypeMapping = {
        "MorningMorning": "leave_2d_MM.png",
        "AfternoonAfternoon": "leave_2d_AA.png",
        "MorningAfternoon": "leave_2d_MA.png",
        "AfternoonMorning": "leave_2d_AM.png"
    };
    const key = startType + endType;
    const imgSrc = baseURL + "assets/images/" + (dayTypeMapping[key] || "date_error.png");
    $("#spnDayType").html("<img src='" + imgSrc + "' />");
}

// Get the leave credit, duration, and detect overlapping cases (Ajax request)
// Default behavior is to set the duration field. Pass false if you want to disable this behavior
function getLeaveInfos(preventDefault = false) {
    $('#frmModalAjaxWait').modal('show');
    const start = moment($('#startdate').val());
    const end = moment($('#enddate').val());

    $.ajax({
        type: "POST",
        url: baseURL + "leaves/validate",
        data: {
            id: userId,
            type: $("#type option:selected").text(),
            startdate: $('#startdate').val(),
            enddate: $('#enddate').val(),
            startdatetype: $('#startdatetype').val(),
            enddatetype: $('#enddatetype').val(),
            leave_id: leaveId
        }
    })
    .done(function(leaveInfo) {
        if (leaveInfo.length !== undefined) {
            const duration = parseFloat(leaveInfo.length).toFixed(3);
            if (!preventDefault && start.isValid() && end.isValid()) {
                $('#duration').val(duration);
            }
        }
        updateCreditAndAlerts(leaveInfo);
        $('#frmModalAjaxWait').modal('hide');
    });
}

function updateCreditAndAlerts(leaveInfo) {
    if (leaveInfo.credit !== undefined) {
        const credit = parseFloat(leaveInfo.credit);
        const duration = parseFloat($("#duration").val());
        $("#lblCreditAlert").toggle(duration > credit);
        if (leaveInfo.credit != null) {
            $("#lblCredit").text('(' + leaveInfo.credit + ')');
        }
    }
    showOverlappingMessage(leaveInfo);
    showOverlappingDayOffMessage(leaveInfo);
    if (!leaveInfo.hasContract) {
        bootbox.alert(noContractMsg);
    } else {
        validateLeavePeriod(leaveInfo);
    }
    showListDayOff(leaveInfo);
    toggleSubmitButton();
}

function validateLeavePeriod(leaveInfo) {
    const start = moment($('#startdate').val());
    const end = moment($('#enddate').val());
    const periodStartDate = moment(leaveInfo.PeriodStartDate);
    const periodEndDate = moment(leaveInfo.PeriodEndDate);
    if (start.isValid() && end.isValid() && periodEndDate.isValid()) {
        if (start.isBefore(periodEndDate) && periodEndDate.isBefore(end)) {
            bootbox.alert(noTwoPeriodsMsg);
        }
        if (start.isBefore(periodStartDate)) {
            bootbox.alert(noTwoPeriodsMsg);
        }
    }
}

function toggleSubmitButton() {
    const isVisible = selector => $(selector).is(":visible");
    const disable = isVisible("#lblCreditAlert") || isVisible("#lblOverlappingAlert") || isVisible("#lblOverlappingDayOffAlert");
    $("button[name='submit']").prop("disabled", disable);
    $("select[name='status']").prop("disabled", disable);
    $("button[name='request']").prop("disabled", disable);
}

function toggleAttachmentRequired() {
    const leaveType = $('#type').val();
    const attachmentField = $('#attachment');
    attachmentField.prop('required', leaveType === '2');
}

$('#type').change(toggleAttachmentRequired);
$(document).ready(toggleAttachmentRequired);

function refreshLeaveInfo() {
    $('#frmModalAjaxWait').modal('show');
    $.ajax({
        type: "POST",
        url: baseURL + "leaves/validate",
        data: {
            id: userId,
            type: $("#type option:selected").text(),
            startdate: $('#startdate').val(),
            enddate: $('#enddate').val(),
            startdatetype: $('#startdatetype').val(),
            enddatetype: $('#enddatetype').val(),
            leave_id: leaveId
        }
    })
    .done(function(leaveInfo) {
        showOverlappingMessage(leaveInfo);
        showOverlappingDayOffMessage(leaveInfo);
        showListDayOff(leaveInfo);
        $('#frmModalAjaxWait').modal('hide');
    });
}

function showListDayOff(leaveInfo) {
    if (leaveInfo.listDaysOff !== undefined) {
        const daysOffHTML = generateDaysOffHTML(leaveInfo.listDaysOff);
        $("#spnDaysOffList").html(daysOffHTML.tooltip);
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    }
}

function generateDaysOffHTML(daysOff) {
    const htmlTable = `
        <a href='#divDaysOff' data-toggle='collapse' class='btn btn-primary input-block-level'>
            ${listOfDaysOffTitle.replace("%s", daysOff.length)}
            &nbsp;<i class='icon-chevron-down icon-white'></i>
        </a>
        <div id='divDaysOff' class='collapse'>
            <table class='table table-bordered table-hover table-condensed'>
                <tbody>${daysOff.map(day => `
                    <tr>
                        <td>${moment(day.date, 'YYYY-MM-DD').format(dateMomentJsFormat)} / <b>${day.title}</b></td>
                        <td>${day.length}</td>
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>`;
    const tooltip = `<a href='#' id='showNoneWorkedDay' data-toggle='tooltip' data-placement='right' title='${listOfDaysOffTitle.replace("%s", daysOff.length)}'><i class='icon-info-sign'></i></a>`;
    return { htmlTable, tooltip };
}

function showOverlappingMessage(leaveInfo) {
    $("#lblOverlappingAlert").toggle(Boolean(leaveInfo.overlap));
}

function showOverlappingDayOffMessage(leaveInfo) {
    $("#lblOverlappingDayOffAlert").toggle(Boolean(leaveInfo.overlapDayOff));
}

$(function () {
    getLeaveLength(false);

    const datePickerOptions = {
        changeMonth: true,
        changeYear: true,
        dateFormat: dateJsFormat,
        altFormat: "yy-mm-dd",
        numberOfMonths: 1,
    };

    $("#viz_startdate").datepicker({
        ...datePickerOptions,
        altField: "#startdate",
        onClose: function(selectedDate) {
            $("#viz_enddate").datepicker("option", "minDate", selectedDate);
        }
    });

    $("#viz_enddate").datepicker({
        ...datePickerOptions,
        altField: "#enddate",
        onClose: function(selectedDate) {
            $("#viz_startdate").datepicker("option", "maxDate", selectedDate);
        }
    });

    $("#days").keyup(function() {
        const value = $("#days").val().replace(",", ".");
        $("#days").val(value);
    });

    $('#viz_startdate, #viz_enddate, #startdatetype, #enddatetype, #type').change(getLeaveInfos.bind(null, false));
    $("#duration").keyup(getLeaveInfos.bind(null, true));

    $("#frmLeaveForm").submit(function(e) {
        if (!validate_form()) {
            e.preventDefault();
        }
    });
});
