
formModal = {};

formModal.init = function (url)
{
    $('#modal .modal-content').load(url);
    $('#modal').modal('show');
};

formModal.initForm = function ()
{
    $('[data-provider="datepicker"]').datetimepicker({
        autoclose: true,
        format: 'dd/mm/yyyy',
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
    $('select').select2();
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
                    window.location.replace(redirect);
            } else {
                $("#modal .modal-content").html(html);
            }
        }
    });

    return false;
};