jQuery(document).ready(function ($) {

    //Обработка нажатия кнопки удалить
    $(document).on('click', '.deletebutton', function (event) {
        event.preventDefault();
        var delhookid = this.value;
        $.ajax({
            url: cphu.ajaxurl,
            type: 'post',
            data: 'cphu_delhookid=' + delhookid + '&action=cphu_ajax',
            success: function (response, textStatus, XMLHttpRequest) {
                $('#cphu_hookups_div').html(response);
            }
        });
    });

    //Обработка нажатия кнопки добавить
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

    //Обработка нажатия кнопки восстановить
    $(document).on('click', '#delcancel', function (event) {
        event.preventDefault();
        var addhookid = $('#delcancelid').html();
        $.ajax({
            url: cphu.ajaxurl,
            type: 'post',
            data: 'cphu_addhookid=' + addhookid +
                  '&cphu_vosst=1' +
                  '&action=cphu_ajax',
            success: function (response, textStatus, XMLHttpRequest) {
                $('#cphu_hookups_div').html(response);
            }
        });
    });


});
