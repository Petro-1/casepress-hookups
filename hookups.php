<?php
/**
 * @package Casepress-hookups
 * @version 1.0
 */
/*
Plugin Name: Шорткод "Связи"
Plugin URI: -
Author: Petro-1
Version: 1.0
*/


//Начал делать через класс, в надежде, что в обработчике аякса будут нормально работать global, static
class cphu_shortcode
{
    static function init()
    {
        add_shortcode('cp_hookups', array(__CLASS__, 'cp_hookups'));
        add_shortcode('cp_hookup', array(__CLASS__, 'cp_hookups'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'cphu_jquery_init'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'cphu_ajax_enque'));
        add_action('wp_ajax_cphu_ajax', array(__CLASS__, 'cphu_callback'));
        add_action('wp_ajax_nopriv_cphu_ajax', array(__CLASS__, 'cphu_callback'));
    }

    /**
     * Вывод шорткода
     */
    static function cp_hookups($atts)
    {
        //Сохраняем в опцию ID текущего поста
        global $post;
        if (!get_option('cphu_curpost')) {
            add_option('cphu_curpost', $post->ID);
        } else {
            update_option('cphu_curpost', $post->ID);
        }
        $cphu_curpost = get_option('cphu_curpost');

        //начало вывода в переменную
        ob_start();

        //Связанные дела
        ?>
        <h3>
            <small>Связанные дела:</small>
        </h3>
        <div id="cphu_hookups_div">
            <?php
            //Вывод связанных постов из меты
            cphu_shortcode::showhookups();
            ?>
         </div>
        <?php

        //вывод чекбокса, если текущий пользователь вправе редактировать посты
        if (current_user_can('edit_post', $cphu_curpost)) {
            ?>
            <div class="checkbox">
                <label>
                    <input type="checkbox" value="" data-toggle="collapse" data-target="#cphu_searchform_div">
                    Добавить связи
                </label>
            </div>

            <!-- Выпадающая форма поиска по нажатию на чекбокс -->
            <div id="cphu_searchform_div" class="collapse">
                <form id="cphu_form" action="#" method="post">
                    <div class="row">
                        <div class="col-xs-12 col-md-8">
                            <?php
                            // Вывод формы для поиска
                            cphu_shortcode::searchform();
                            ?>
                          </div>
                        <div class="col-xs-6 col-md-4">
                            <button type="submit" class="btn btn-primary" id="cphu_add">Добавить</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php
        }
        $cphu_checkbox = ob_get_contents();
        //Конец вывода в переменную
        ob_end_clean();

        return $cphu_checkbox;

    }

    /**
     * Вывод связанных постов из меты
     */
    static function showhookups($message = '')
    {
        //получаем записи из метабокса и перебираем значения
        $meta = get_post_meta(get_option('cphu_curpost'), 'cp_hookups');
        foreach ($meta as $hookedpostid) {
            $hookedpost = get_post($hookedpostid);
            //Сокращаем слишком длинное название
            if (mb_strlen($hookedpost->post_title) > 30) {
                $title = mb_substr($hookedpost->post_title, 0, 30) . '...';
            } else {
                $title = $hookedpost->post_title;
            }
            //Выводим названия связанных постов в виде кнопок с поповером при нажатии,
            //если текущий пользователь вправе редактировать посты
            if (current_user_can('edit_post', get_option('cphu_curpost'))) {
                //Заносим в переменную содержимое поповера
                ob_start();
                ?>
                <div class='row'>
                    <div class='col-md-6'>
                        <button type='button'
                                class='btn btn-warning deletebutton'
                                value='<?php echo $hookedpost->ID; ?>'>
                            <span class='glyphicon glyphicon-erase' aria-hidden='true'></span>
                        </button>
                    </div>
                    <div class='col-md-6' style='text-align: right'>
                        <a class='btn btn-primary' href='<?php echo get_post_permalink($hookedpostid); ?>'>
                            <span class='glyphicon glyphicon-arrow-right' aria-hidden='true'></span>
                        </a>
                    </div>
                </div>
                <?php
                $data_content = ob_get_contents();
                ob_end_clean();
                ?>

                <a tabindex="0" class="btn btn-xs btn-default"
                   role="button" data-toggle="popover"
                   title="<h5><?php echo $hookedpost->post_title; ?></h5>"
                   data-content="<?php echo $data_content; ?>"
                   data-placement="top">
                    <?php echo $title ?>
                </a>

                <script>
                    jQuery(document).ready(function ($) {
                        $('[data-toggle="popover"]').popover({html: 'true'});
                    });
                </script>
                <?php

            } else {

                //Если у юзера нет права редактировать,
                // выводим список связей с переходом к делу по нажатию и тултипом с полным заголовком при наведении
                ?>
                <a class="btn btn-xs btn-default"
                   href="<?php echo get_post_permalink($hookedpostid); ?>"
                   data-toggle="tooltip" data-placement="top" title="<?php echo $hookedpost->post_title; ?>">
                    <?php
                    echo $title;
                    ?>
                </a>
                <script>
                    jQuery(function () {
                        jQuery('[data-toggle="tooltip"]').tooltip()
                    })
                </script>
                <?php
            }

        }
        //Выводим сообщение об ошибке или удачном добавлении
        // с анимацией...
        ?>
        <script>
            jQuery(document).ready(function($){
                $("#cphu_mesdiv").animate({
                    opacity: '1'
                });
            });
        </script>
        <div id="cphu_mesdiv" style="opacity:0; margin-top: 2%;">
            <?php echo $message; ?>
        </div>
        <?php
    }

    /**
     * Переделанная форма из cases_view_admin для поиска дел
     */
    static function searchform()
    {
        ?>
        <div>
            <input type="hidden" id="cphu_searchform" name="cphu_addhookid" class="cp_select2_single"/>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {

                $("#cphu_searchform").select2({
                    placeholder: "Начните вводить часть содержимого",
                    width: '100%',
                    allowClear: true,
                    minimumInputLength: 1,
                    ajax: {
                        url: "<?php echo admin_url('admin-ajax.php') ?>",
                        dataType: 'json',
                        quietMillis: 100,
                        data: function (term, page) { // page is the one-based page number tracked by Select2
                            return {
                                action: 'query_posts_cases',
                                posts_per_page: 10, // page size
                                paged: page, // page number
                                s: term //search term
                            };
                        },
                        results: function (data, page) {
                            //alert(data.total);
                            var more = (page * 10) < data.total; // whether or not there are more results available

                            // notice we return the value of more so Select2 knows if more results can be loaded
                            return {
                                results: data.items,
                                more: more
                            };
                        }
                    },
                    formatResult: function (element) {
                        return "<div>" + element.title + "</div>"
                    }, // omitted for brevity, see the source of this page
                    formatSelection: function (element) {
                        return element.title;
                    }, // omitted for brevity, see the source of this page
                    dropdownCssClass: "bigdrop", // apply css that makes the dropdown taller
                    escapeMarkup: function (m) {
                        return m;
                    } // we do not want to escape markup since we are displaying html in results
                });


            });
        </script>

        <?php
    }


    /**
     *
     *    AJAX
     *
     */

    //Подключаю jQuery, хотя он вроде и так уже подключен, но хуже ведь не будет

    static function cphu_jquery_init()
    {
        wp_enqueue_script('jquery');
    }

    //Добавляю скрипт для аякса и локализую переменные
    static function cphu_ajax_enque()
    {
        global $post;
        if (has_shortcode($post->post_content, 'cp_hookups') || has_shortcode($post->post_content, 'cp_hookup')) {
            wp_enqueue_script('cphu_ajax', plugins_url('ajax.js', __FILE__), array('jquery'), '1.0', true);
            wp_localize_script('cphu_ajax', 'cphu',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'siteurl' => site_url('/')
                )
            );
        }
    }

    //Обработка данных, отправленых через аякс, и вывод постов
    static function cphu_callback()
    {

        //Кнопка закрытия сообщения
        ob_start();
        ?>
        <div style="display: inline; float: right">
            <button id="closealert" type="button" class="close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
        $cphu_closebut = ob_get_contents();
        ob_end_clean();

        //Если есть переменная с ID удаляемого из связей поста - удаляем
        if ($_REQUEST['cphu_delhookid']) {
            $delhookid = $_REQUEST['cphu_delhookid'];
            delete_post_meta(get_option('cphu_curpost'), 'cp_hookups', $delhookid);

            ob_start();
            ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                Дело
                <a class="alert-link" href="<?php echo get_post_permalink($delhookid); ?>">
                    <?php echo get_the_title($delhookid); ?>
                </a>
                удалено из списка связей.

                <!-- Кнопка для восстновления удаленного дела -->

                <button id="delcancel" type="button" class="btn btn-link">
                    <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span> Восстановить
                </button>
                <div id="delcancelid" style="display:none;"><?php echo $delhookid; ?></div>

            </div>
            <?php
            $message = ob_get_contents();
            ob_end_clean();

        } //иначе, если есть переменная с ID добавляемого к связям поста - добавляем
        elseif ($_REQUEST['cphu_addhookid']) {
            $addhookid = $_REQUEST['cphu_addhookid'];

            //если уже есть такой элемент, добавляем в переменную текст сообщения об ошибке
            if (in_array($addhookid, get_post_meta(get_option('cphu_curpost'), 'cp_hookups'))) {
                ob_start();
                ?>
                <div class="alert alert-warning" role="alert">
                    <?php echo $cphu_closebut; ?>
                    Ошибка! Дело
                    <a class="alert-link" href="<?php echo get_post_permalink($addhookid); ?>">
                        <?php echo get_the_title($addhookid); ?>
                    </a>
                    уже имеется в списке связей.
                </div>
                <?php
                $message = ob_get_contents();
                ob_end_clean();
            }

            //Иначе добавляем связанный пост и текст сообщения об успехе
            elseif (1<2) { //пришлось добавить выражение, т.к. если делать просто else, то оно путается с else условия верхнего уровня
                add_post_meta(get_option('cphu_curpost'), 'cp_hookups', $addhookid);

                //восстановлено или добавлено
                switch ($_REQUEST['cphu_vosst']) {
                    case 1:
                        $v = ' восстановлено.';
                        break;
                    default:
                        $v = ' добавлено.';
                        break;
                }
                ob_start();
                ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $cphu_closebut; ?>
                    Дело
                    <a class="alert-link" href="<?php echo get_post_permalink($addhookid); ?>">
                        <?php echo get_the_title($addhookid); ?>
                    </a>
                    <?php echo $v; ?>
                    <!-- кнопка отмены добавления последнего дела -->
                    <button type="button"
                            class="btn btn-link deletebutton"
                            value="<?php echo $addhookid; ?>">
                        <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span> Отмена
                    </button>
                </div>
                <?php
                $message = ob_get_contents();
                ob_end_clean();
            }
        } else {
            //Иначе, остается только ситуация, если ничего не выбрано при нажатии кнопки добавить,
            // поэтому вставляем соответствующее сообщение
            ob_start();
            ?>
            <div class="alert alert-warning" role="alert">
                <?php echo $cphu_closebut; ?>
                Ничего не выбрано.
            </div>
            <?php
            $message = ob_get_contents();
            ob_end_clean();
        }

        //Вывод связанных постов из меты
        cphu_shortcode::showhookups($message);

        wp_die();
    }


}

cphu_shortcode::init();



