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
class cphu_shortcode {
	static function init () {
		add_shortcode( 'cp_hookups', array(__CLASS__, 'cp_hookups' ));
		add_shortcode( 'cp_hookup', array(__CLASS__, 'cp_hookups' ));
		add_action('wp_enqueue_scripts', array(__CLASS__, 'cphu_jquery_init'));
		add_action('wp_enqueue_scripts', array(__CLASS__, 'cphu_ajax_enque'));
		add_action('wp_ajax_cphu_ajax', array(__CLASS__, 'cphu_callback'));
		add_action('wp_ajax_nopriv_cphu_ajax', array(__CLASS__, 'cphu_callback'));
	}

	/**
	 * Вывод шорткода
	 */
	static function cp_hookups ( $atts ) {
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
		<h3><small>Связанные дела:</small></h3>
		<div id="cphu_hookups_div">
			<?php
			//Вывод связанных постов из меты
			cphu_shortcode::showhookups();
			?>
		</div>
		<?php

		//вывод чекбокса, если текущий пользователь вправе редактировать посты
		if( current_user_can('edit_post', $cphu_curpost) ) {
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
			$cphu_checkbox = ob_get_contents();
			//Конец вывода в переменную
			ob_end_clean();

			return $cphu_checkbox;
		}
	}

	/**
	 * Переделанная форма из cases_view_admin для поиска дел
	 */
	static function searchform() {
		?>
		<div>
			<input type="hidden" id="cphu_searchform" name="cphu_request" class="cp_select2_single" />
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {

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
					formatResult: function(element){ return "<div>" + element.title + "</div>" }, // omitted for brevity, see the source of this page
					formatSelection: function(element){  return element.title; }, // omitted for brevity, see the source of this page
					dropdownCssClass: "bigdrop", // apply css that makes the dropdown taller
					escapeMarkup: function (m) { return m; } // we do not want to escape markup since we are displaying html in results
				});


			});
		</script>

		<?php
	}

	/**
	 * Вывод связанных постов из меты
	 */
	static function showhookups($usercan = 0) {
		//получаем записи из метабокса и перебираем значения
		$meta = get_post_meta(get_option('cphu_curpost'), 'cp_hookups');
		foreach ($meta as $hookedpostid) {
			$hookedpost = get_post($hookedpostid);
			//Сокращаем слишком длинное название
			if (mb_strlen($hookedpost->post_title)>30) {
				$title = mb_substr($hookedpost->post_title, 0, 30) . '...';
			} else {
				$title = $hookedpost->post_title;
			}
			//Выводим названия связанных постов в виде кнопок с поповером при нажатии,
			//если текущий пользователь вправе редактировать посты
			if(current_user_can('edit_post', $cphu_curpost) || $usercan !=0 ) {
				?>
				<a tabindex="0" class="btn btn-xs btn-default"
				   role="button" data-toggle="popover"

				   title="<h5><?php echo $hookedpost->post_title; ?></h5>"

				   data-content="
			   		<div class='row'>
						<div class='col-md-6'>
							<a class='btn btn-primary' href='<?php echo get_post_permalink($hookedpostid); ?>'>
							<span class='glyphicon glyphicon-arrow-right' aria-hidden='true'></span>
							</a>
						</div>
						<div class='col-md-6' style='text-align: right'>
							<button type='button'
									class='btn btn-warning deletebutton'
									value='<?php echo $hookedpost->ID; ?>'>
								<span class='glyphicon glyphicon-erase' aria-hidden='true'></span>
							</button>
						</div>
					</div>"

				   data-placement="top">
					<?php echo $title ?>
				</a>

				<script>
					jQuery(document).ready(function($){
						$('[data-toggle="popover"]').popover({html:'true'});
					});
				</script>
				<?php

			} else {

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

		//иниициализация поповера
		?>

		<?php
	}




	/**
	 *
	 *	AJAX
	 *
	 */

	//Подключаю jQuery, хотя он вроде и так уже подключен, но хуже ведь не будет
	static function cphu_jquery_init()
	{
		wp_enqueue_script('jquery');
	}

	//Добавляю скрипт для аякса и локализую переменные
	static function cphu_ajax_enque() {
		global $post;
		if( has_shortcode( $post->post_content, 'cp_hookups')  || has_shortcode( $post->post_content, 'cp_hookup')) {
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
	static function cphu_callback()	{
		//Если есть переменная с ID удаляемого из связей поста - удаляем
		if ($_REQUEST['delhookid']) {
			delete_post_meta(get_option('cphu_curpost'), 'cp_hookups', $_REQUEST['delhookid']);
		}
		//иначе, если есть переменная с ID добавляемого к связям поста - добавляем
		elseif ($_REQUEST['cphu_request']) {
			add_post_meta(get_option('cphu_curpost'), 'cp_hookups', $_REQUEST['cphu_request']);
		}

		//Вывод связанных постов из меты
		// (1 - чтобы права для просмотра уже были как у пользователя, иначе после аякса текущий юзер определяется как бесправный)
		cphu_shortcode::showhookups(1);

		wp_die();
	}

}
cphu_shortcode::init();



