<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( get_parent_theme_file_uri( 'assets/css/editor-style.css' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

// Enqueues style.css on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues style.css on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;

function generate_or_update_pages_from_json() {
    error_log('Функція generate_or_update_pages_from_json викликається');
    error_log('Функція запущена');

    $json_url = 'https://state-haulex.vercel.app/state.json?' . time();
	
	error_log('Зроблено запит до: ' . $json_url);
	$response = wp_remote_get($json_url);
	if (is_wp_error($response)) {
		error_log('Помилка при отриманні JSON: ' . $response->get_error_message());
	} else {
		error_log('Отримано відповідь: ' . wp_remote_retrieve_body($response));
	}

    if (is_wp_error($response)) {
        error_log('Помилка при отриманні JSON: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        error_log('Отримано порожній або некоректний вміст');
        return;
    }

    $data = json_decode($body, true);
    if (!isset($data['states']) || !is_array($data['states'])) {
        error_log('Некоректний формат JSON');
        return;
    }

    $existing_pages = get_pages();
    $existing_pages_slugs = array();

    foreach ($existing_pages as $page) {
        $existing_pages_slugs[$page->post_name] = $page->ID;
    }

    foreach ($data['states'] as $state) {
        if (!isset($state['slug'], $state['name'], $state['info'])) {
            error_log('Пропущено некоректний запис у JSON');
            continue;
        }

        $slug = sanitize_title($state['slug']);
        $page_title = sanitize_text_field($state['name']);
		$page_content = wp_kses_post('<div class="state-page-container">
			<h1 class="state-title">' . ($state['name']) . '</h1>
			<p class="state-info">' . ($state['info']) . '</p>
		</div>');

        error_log('HTML контент сторінки ' . $slug . ': ' . $page_content);

        if (isset($existing_pages_slugs[$slug])) {
            wp_update_post(array(
                'ID' => $existing_pages_slugs[$slug],
                'post_title' => $page_title,
                'post_content' => $page_content,
            ));

            $updated_content = get_post_field('post_content', $existing_pages_slugs[$slug]);
            if (!empty($updated_content)) {
                error_log('Оновлено сторінку: ' . $page_title);
            } else {
                error_log('ПОМИЛКА: Не вдалося рендерити HTML код на сторінці: ' . $page_title);
            }
        } else {
            wp_insert_post(array(
                'post_title'    => $page_title,
                'post_content'  => $page_content,
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => $slug,
            ));
            error_log('Створено сторінку: ' . $page_title);
        }
    }

    foreach ($existing_pages as $page) {
        if (!in_array($page->post_name, array_column($data['states'], 'slug'))) {
            wp_delete_post($page->ID, true);
            error_log('Видалено сторінку: ' . $page->post_name);
        }
    }
}

add_action('init', 'generate_or_update_pages_from_json');

function custom_state_page_styles() {
    echo '<style>
        .state-page-container {
            padding: 20px;
            margin: 0 auto;
            max-width: 800px;
            background-color: #f4f4f4;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .state-title {
            font-size: 2em;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }
        .state-info {
            font-size: 1.2em;
            color: #34495e;
            line-height: 1.6;
            text-align: justify;
        }
    </style>';
}

add_action('wp_head', 'custom_state_page_styles');
