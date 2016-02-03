var EasyAdminPopup = {};
EasyAdminPopup.datepicker = {};
EasyAdminPopup.datepicker.format = 'dd/mm/yyyy';
EasyAdminPopup.datepicker.delimiter = '/';

formModal = {};

formModal.init = function (url)
{
    $('#modal .modal-content').load(url, function (url, data)
    {
        if (data !== 'error') {
            $('#modal').modal({
                show: true,
                backdrop :'static'
            });
        }
    });
};

formModal.initForm = function ()
{
    $('[data-provider="datepicker"]').datetimepicker({
        autoclose: true,
        format: EasyAdminPopup.datepicker.format,
        language: 'fr',
        minView: 'month',
        pickerPosition: 'bottom-left',
        todayBtn: true,
        startView: 'month'
    });

    $('[data-provider="datetimepicker"]').datetimepicker({
        autoclose: true,
        format: 'dd/mm/yyyy hh:ii',
        language: 'fr',
        pickerPosition: 'bottom-left',
        todayBtn: true
    });

    $('[data-provider="timepicker"]').datetimepicker({
        autoclose: true,
        format: 'hh:ii',
        formatViewType: 'time',
        maxView: 'day',
        minView: 'hour',
        pickerPosition: 'bottom-left',
        startView: 'day'
    });

    // Restore value from hidden input
    $('input[type=hidden]', '.date').each(function(){
        if($(this).val()) {
            $(this).parent().datetimepicker('setValue');
        }
    });

    //enable select2
    $('select').select2({width: '100%'});
};

formModal.postForm = function ()
{
    //get the form included in the modal
    var form = $('#modal form');
    var url = $('#modal form')[0].action;

    $.ajax({
        type: "POST",
        url: url,
        data: form.serialize(),
        success: function(response) {
            var data = response.data;

            var html = data.html;
            var redirect = data.redirect;

            if (redirect !== null) {
                if (redirect === '#') {
                    location.reload(true);
                } else {
                    window.location.replace(redirect);
                }
            } else {
                $("#modal .modal-content").html(html);
            }
        }
    });

    return false;
};

EasyAdminPopup.stringToDate = function (_date,_format,_delimiter)
{
    var formatLowerCase=_format.toLowerCase();
    var formatItems=formatLowerCase.split(_delimiter);
    var dateItems=_date.split(_delimiter);
    var monthIndex=formatItems.indexOf("mm");
    var dayIndex=formatItems.indexOf("dd");
    var yearIndex=formatItems.indexOf("yyyy");
    var month=parseInt(dateItems[monthIndex]);
    month-=1;
    var formatedDate = new Date(dateItems[yearIndex],month,dateItems[dayIndex]);
    return formatedDate;
};

EasyAdminPopup.updateDatetimePickerHiddenInput = function (displayId, hiddenId)
{
    var input = $('#'+displayId);
    var displayVal = input.val();
    var displayDate = EasyAdminPopup.stringToDate(displayVal, EasyAdminPopup.datepicker.format, EasyAdminPopup.datepicker.delimiter);

    if (!isNaN(displayDate.getTime())) {
        var hiddenVal = displayDate.getFullYear() + '-' + (displayDate.getMonth() + 1) + '-' + displayDate.getDate();

        $('#'+hiddenId).val(hiddenVal);
    }
};
