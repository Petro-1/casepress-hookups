jQuery(document).ready(function ($) {

    $(document).on('click', '.deletebutton', function (event) {
        event.preventDefault();
        var delhookid = this.value;
        $.ajax({
            url: cphu.ajaxurl,
            type: 'post',
            data: 'delhookid=' + delhookid + '&action=cphu_ajax',
            success: function (response, textStatus, XMLHttpRequest) {
                $('#cphu_hookups_div').html(response);
            }
        });
    });

    $(document).on('click', '#cphu_add', function (event) {
        event.preventDefault();
        $.ajax({
            url: cphu.ajaxurl,
            type: 'post',
            data: $("#cphu_form").serialize() + '&action=cphu_ajax',
            success: function (response, textStatus, XMLHttpRequest) {
                $('#cphu_hookups_div').html(response);
            }
        });
    });

});
