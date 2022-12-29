<?php
/**
 * Plugin Name:     Reading Comprehension Questions
 * Plugin URI:      https://github.com/robby1066/wp-reading-comprehension-questions
 * Description:     A plugin for educational sites to easily add reading comprehension questions to the end of their posts.
 * Author:          Robby Macdonell
 * Author URI:      https://www.robbymacdonell.com
 * Text Domain:     reading-comprehension-questions
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Reading_Comprehension_Questions
 */

// Your code starts here.

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

define("RCQ_POST_TYPE_KEY", 'rcq-question');

// Create post type for reading comprehension questions
function rcq_create_question_post_type() {
    register_post_type( RCQ_POST_TYPE_KEY,
      array(
        'labels' => array(
			'name' => _x( 'Questions', 'Post type general name', 'reading-comprehension-questions' ),
			'singular_name' => _x( 'Question', 'Post type singular name', 'reading-comprehension-questions' ),
			'menu_name' => _x( 'Questions', 'Admin Menu text', 'reading-comprehension-questions' ),
			'name_admin_bar' => _x( 'Question', 'Add New on Toolbar', 'reading-comprehension-questions' ),
			'parent_item_colon' => __( 'Parent Post:', 'reading-comprehension-questions' ),
            'add_new'            => _x( 'Add New', 'question', 'reading-comprehension-questions' ),
            'add_new_item'       => __( 'Add New Question', 'reading-comprehension-questions' ),
            'edit_item'          => __( 'Edit Question', 'reading-comprehension-questions' ),
            'new_item'           => __( 'New Question', 'reading-comprehension-questions' ),
            'all_items'          => __( 'All Questions', 'reading-comprehension-questions' ),
            'view_item'          => __( 'View Question', 'reading-comprehension-questions' ),
            'search_items'       => __( 'Search Questions', 'reading-comprehension-questions' ),
            'not_found'          => __( 'No questions found', 'reading-comprehension-questions' ),
            'not_found_in_trash' => __( 'No questions found in the Trash', 'reading-comprehension-questions' ),
        ),
        'public' => true,
        'has_archive' => true,
        'hierarchical' => true,
        'supports' => array( 'title'),
        'show_in_rest' => array(
            'parent_item_colon' => true
        )
      )
    );
}

add_action( 'init', 'rcq_create_question_post_type' );


function rcq_add_meta_boxes() {
	// Add meta box to the 'Questions' content type to show the parent post
    add_meta_box(
        'parent_post_meta_box', // ID of the meta box
        __( 'Parent Post:', 'reading-comprehension-questions' ), // Title of the meta box
        'display_parent_post_meta_box', // Callback function to display the meta box
        RCQ_POST_TYPE_KEY, // Post type for which the meta box should be displayed
        'side', // Context (sidebar) in which the meta box should be displayed
        'default' // Priority of the meta box
    );

	// Add meta box to the 'Questions' content type to show the responses
    add_meta_box(
        'rcq_responses_box', // ID of the meta box
        __( 'Responses', 'reading-comprehension-questions' ), // Title of the meta box
        'display_rcq_responses_meta_box', // Callback function to display the meta box
        RCQ_POST_TYPE_KEY, // Post type for which the meta box should be displayed
        'normal', // Context (sidebar) in which the meta box should be displayed
        'high' // Priority of the meta box
    );

	// Add meta box to the 'Post' content type
    add_meta_box(
        'rcq_post_meta_box', // ID of the meta box
        'Reading Comprehension Questions', // Title of the meta box
        'rcq_display_questions_meta_box', // Callback function to display the meta box
        get_option('rcq_parent_post_type'), // Post type for which the meta box should be displayed
        'normal', // Context (sidebar) in which the meta box should be displayed
        'default' // Priority of the meta box
    );
}

add_action( 'add_meta_boxes', 'rcq_add_meta_boxes' );


function display_parent_post_meta_box( $post ) {
    $parent_post_id = get_post_meta( $post->ID, 'parent_post_id', true );
    wp_nonce_field( basename( __FILE__ ), 'parent_post_nonce' );

    display_posts_dropdown_menu($post);
}

function rcq_display_questions_meta_box( $post ) {
    rcq_display_attached_questions_for_editor( $post );
}

function display_rcq_responses_meta_box( $post ) {
    // ensure that the view has at least three elements to work with
    $rcq_responses = array_pad(rcq_get_responses_from_meta( $post ), 3, '');

    ?>
        <ul id="rcq-options">
            <?php
            $index = 0;
            foreach ( $rcq_responses['choices'] as $rcq_reponse ) {
                print('<li>');
                print('<input type="radio" name="rcq_correct_index" value="'. $index .'" ' . ($rcq_responses['correct_index'] == $index ? 'checked' : "") . ' />');
                print('<input type="text" name="rcq_choices[]" value="'. $rcq_reponse .'" />');
                print('</li>');
                $index++;
            }
            ?>
        </ul>
        <p><a href="" data-add-new-option-control><?php _e( 'Add new', 'reading-comprehension-questions' ); ?></a></p>
        <template id="rcq-option-row">
            <li>
                <input type="radio" name="rcq_correct_index" value="RCQ-INDEX"  />
                <input type="text" name="rcq_choices[]" value="" />
            </li>
        </template>
        
    <?php
}

function display_posts_dropdown_menu( $post ) {
    $parent = get_post_parent( $post );
    
    if ( empty( $parent ) && array_key_exists( 'parent_id', $_GET ) ) {
        $parent = get_post( intval($_GET['parent_id']) );
    }

    if ( $parent ) {
        print('<input type="hidden" name="post_parent" value="' . $parent->ID .'"/>');
        printf(
            '<p>%s</p>',
            edit_post_link(
                get_the_title( $parent->ID ),
                '',
                '', 
                $parent
            )
        );
        return true;
    }

    $blogposts_args = array(
      'post_type' => 'post', // Only retrieve posts, not pages or other post types
      'posts_per_page' => -1 // Retrieve all posts
    );
    $blogposts = get_posts( $blogposts_args );
    ?>
    <label for="rcq_post_parent"><?php _e( 'Select a post', 'reading-comprehension-questions' ) ?>:</label>
    <select name="post_parent" id="rcq_post_parentt">
        <option value=""></option>
        <?php
            foreach ( $blogposts as $blogpost ) {
            printf(
                '<option value="%s">%s</option>',
                $blogpost->ID,
                get_the_title( $blogpost->ID )
            );
            }
        ?>
    </select>
    <?php
}

function rcq_display_attached_questions_for_editor( $post ) {
    $questions = rcq_get_questions( $post->ID );

    $post_length = strlen( wp_strip_all_tags( $post->post_content ) );

    if ( empty( $questions ) && $post_length < 50 ) {
        echo "<p>" . __( 'Once you add some content and save this post, you will be able to add reading comprehension questions.', 'reading-comprehension-questions' ) . "</p>";
        return;
    }

    $rcq_nonce = wp_create_nonce( 'rcq-nonce' );
    $get_data = http_build_query( array( 
        'rcq_nonce' => $rcq_nonce, 
        'action' => 'rcq_generate_questions', 
        'post_id' => $post->ID, 
    ) );

    $generate_questions_url = admin_url( 'admin-post.php' ) . '?' . $get_data;
    ?>
        <ul>
            <?php
            foreach ( $questions as $question ) {
                printf(
                    '<li>%s</li>',
                    edit_post_link(
                        get_the_title( $question->ID ),
                        '',
                        '', 
                        $question
                    )
                );
            }
            ?>
        </ul>
        <p>
            <a href="<?php echo esc_url( $generate_questions_url ); ?>"><?php _e( 'Generate questions', 'reading-comprehension-questions' ); ?></a> |
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=question&parent_id=' . $post->ID ) ); ?>"><?php _e( 'Add manually', 'reading-comprehension-questions' ); ?></a>
        </p>
    <?php
}

function rcq_update_post_on_save( $post_id ) {
    if ( array_key_exists( 'rcq_choices', $_POST ) ) {
        $rcq_choices = array_filter( $_POST['rcq_choices'] );
        update_post_meta(
            $post_id,
            'rcq_choices',
            $rcq_choices
        );
    }

    if ( array_key_exists( 'rcq_correct_index', $_POST ) ) {
        $rcq_correct_index = $_POST['rcq_correct_index'];
        update_post_meta(
            $post_id,
            'rcq_correct_index',
            $rcq_correct_index
        );
    }
}

add_action( 'save_post', 'rcq_update_post_on_save' );


function rcq_get_responses_from_meta( $post ) {
    $rcq_responses = array(
        'choices' => get_post_meta( $post->ID, 'rcq_choices', true ),
        'correct_index' => get_post_meta( $post->ID, 'rcq_correct_index', true )
    );
    if (is_string($rcq_responses['choices'])) {
        $rcq_responses['choices'] = array_pad( array( $rcq_responses['choices'] ), 3, '' );
    }
    
    return $rcq_responses;
}

// Utility functions
function rcq_has_questions( $post_id ) {
    return ! empty( rcq_get_questions($post_id) );
}

function rcq_get_questions( $post_id ) {
    $questions_args = array(
        'post_type' => 'rcq-question', // Only retrieve question pages, not pages or other post types
        'post_parent' => $post_id
    );
    return get_children( $questions_args );
}

// Add a javascript file to the admin section
function rcq_enqueue_admin_scripts() {
    // Register the script
    wp_register_script( 'rcq-admin', plugin_dir_url( __FILE__ ) . 'js/rcq-admin.js', array(), '1.0.0', true );
  
    // Enqueue the script
    wp_enqueue_script( 'rcq-admin' );
}

add_action( 'admin_enqueue_scripts', 'rcq_enqueue_admin_scripts' );

function rcq_enqueue_frontend_scripts() {
    // Register the script
    wp_register_script( 'rcq-frontend', plugin_dir_url( __FILE__ ) . 'js/rcq-frontend.js', array(), '1.0.0', true );
  
    // Enqueue the script
    wp_enqueue_script( 'rcq-frontend' );
}

add_action( 'wp_enqueue_scripts', 'rcq_enqueue_frontend_scripts' );

function rcq_enqueue_frontend_style() {
    // Register the style
    wp_register_style( 'rcq-frontend', plugin_dir_url( __FILE__ ) . 'css/rcq-frontend.css', array(), '1.0.0' );
  
    // Enqueue the style
    wp_enqueue_style( 'rcq-frontend' );
}

add_action( 'wp_enqueue_scripts', 'rcq_enqueue_frontend_style' );


// OPENAI CONSTANTS
define("RCQ_OPENAI_PROMPT", "Write two multiple choice questions about the passage below to test reading comprehension. Give three or four options, for each question, and specify which is the correct answer.");
define("RCQ_OPENAI_FORMAT", "The response should be in the following format: `[{ \"question\": \"QUESTION\", \"options\": [{\"text\": \"ANWSER\",\"correct\": false}, ]},{ \"question\": \"QUESTION\", \"options\": [{\"text\": \"ANWSER\",\"correct\": false}, ]}]`");
define("RCQ_OPENAI_MAX_LENGTH", 1500);

// CODE TO DISPLAY THE QUESTIONS
function rcq_post_content( $content ) {
    // Check if we are on a single post page
    if ( is_single() && in_the_loop() && rcq_has_questions( get_the_ID() ) ) {
		// Modify the post content
		$content .= rcq_display_questions( get_the_ID() );
    }
    return $content;
}

add_filter( 'the_content', 'rcq_post_content' );


// Hooks for handling the post requests to generate questions
function rcq_generate_questions() {
    $token = get_option( 'rcq_api_token' );
    $post_id = $_GET['post_id'];

    if ( empty( $token ) ) {
        return false;
    }

    $client = OpenAI::client( $token );
    
    $post_to_parse = get_post( $post_id );
    $plain_text_content = rcq_get_question_text_block( $post_to_parse );

    $result = $client->completions()->create([
        'model' => 'text-davinci-003',
        'max_tokens' => 256,
        'prompt' => rcq_openai_prompt() . $plain_text_content . "\n\n" . rcq_openai_format(),
    ]);

    if ( rcq_create_question_from_json( $result['choices'][0]['text'], $post_id ) == false ) {
        rcq_set_notification_message( __( 'There was a problem with the response from GPT. Please try again.', 'reading-comprehension-questions' ) );
    };

    $get_data = http_build_query( array( 
        'action' => 'edit', 
        'post' => $_GET['post_id'],
    ) );
    $redirect_url = admin_url( 'post.php' ) . '?' . $get_data;
    wp_safe_redirect( $redirect_url );
    exit;
}

add_action( 'admin_post_rcq_generate_questions', 'rcq_generate_questions' );


function rcq_get_question_text_block( $post ) {
    $plain_content = wp_strip_all_tags( $post->post_content );
    if (strlen($plain_content) <= RCQ_OPENAI_MAX_LENGTH) {
        return $plain_content;
    }

    // try to split the tet into comprehensible chunks under the max length.
    $blocks = array();
    $current_block = $post->post_title . ' ';
    $current_level = 0;

    // Use DOMDocument to parse the HTML
    $doc = new DOMDocument();
    $doc->loadHTML( '<?xml encoding="UTF-8">' . strip_tags($post->post_content, ['p','h1','h2'] ) );
    
    foreach ( $doc->getElementsByTagName('body')->item(0)->childNodes as $element ) {
        // If the heading is at a lower level, start a new block
		// TODO: Make these splits more intelligent
        if ( $element->nodeName == 'h2' ) {
            $blocks[] = $current_block;
            $current_block = trim($element->textContent) . ' ';
        } else {
            $current_block .= trim($element->textContent) . ' ';
        }
    }
    
    // Add the final block
    $blocks[] = $current_block;

    // return a random block of text to be parsed
    return $blocks[ array_rand( $blocks ) ];
}

function rcq_create_question_from_json( $json, $post_id ) {
    $json_decoded = json_decode( $json );
    $processed = 0;

    if ( is_array( $json_decoded ) ) {
        $objects = $json_decoded;
    } elseif ( is_object( $json_decoded ) ) {
        // we've recived a singular object as a response
        $objects = array( $json_decoded );
    } else {
        // we've recieved something weird. Bail.
        return false;
    }

    foreach ( $objects as $object ) {

        if ( is_null( $object ) || ! property_exists( $object, 'options' ) || !property_exists( $object, 'question' ) ) {
            array_push( $messages, 'Something went wrong with one of the objects');
			continue;
        }
        $new_post = array(
            'post_title' => $object->question,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_parent' => $post_id,
            'post_type' => 'rcq-question',
        );

        if ( is_array( $object->options ) ) {
        	$options = array();
            $correct_index = null;
    
            foreach ( $object->options as $key => $value ) {
                array_push( $options, $value->text );
				if ( $value->correct == true ) {
                  $correct_index = $key;
                }
            }
    
            // Insert the post into the database
            $question_post_id = wp_insert_post( $new_post );
    
            // update the questions
            update_post_meta(
                $question_post_id,
                'rcq_choices',
                $options
            );
    
            // update the selected index
            if ( is_integer( $correct_index ) ) {
                update_post_meta(
                    $question_post_id,
                    'rcq_correct_index',
                    $correct_index,
                );
            }

            $processed = $processed + 1;

        }
    }

    if ( $processed > 0 ) {
        rcq_set_notification_message( __( 'Created', 'reading-comprehension-questions' ) . ' ' . $processed . ' ' . _n( 'question', 'questions', $processed, 'reading-comprehension-questions' ) );
        return true;
    } else {
        return false;
    }
}

function rcq_language_modifier() {
    $question_language = '';

    $language = get_option( 'rcq_question_language' );

    if ( 'en' == $language ) {
        $question_language = '/language en';
    } elseif ( 'es' == $language ) {
        $question_language = '/language es';
    } elseif ( 'it' == $language ) {
        $question_language = '/language it';
    } elseif ( 'fr' == $language ) {
        $question_language = '/language fr';
    }

    return $question_language;
}

function rcq_language_string() {
    $question_language = '';

    $language = get_option( 'rcq_question_language' );

    if ( 'en' == $language ) {
        $question_language = 'English';
    } elseif ( 'es' == $language ) {
        $question_language = 'Spanish';
    } elseif ( 'it' == $language ) {
        $question_language = 'Italian';
    } elseif ( 'fr' == $language ) {
        $question_language = 'French';
    }
    return $question_language;
}

function rcq_openai_prompt() {
    return RCQ_OPENAI_PROMPT . "\n\n";
}

function rcq_openai_format() {
    $format = RCQ_OPENAI_FORMAT;
    return $format . "\n\n/format json\n" . rcq_language_modifier();
}


// FRONT-END
// Display the questions on the post page

function rcq_display_questions( $post_id ) {
	$questions = rcq_get_questions( $post_id );

	if (empty( $questions )) {
		return '';
	}

	$_template = plugin_dir_path( __FILE__ ) . 'templates/rcq_question_html.php';

	ob_start();
	?>
		<details data-rcq>
			<summary><?php echo _e( 'Reading Comprehension Questions', 'reading-comprehension-questions' ) ?></summary>
			<?php foreach ( $questions as $question ): ?>
				<?php load_template( $_template, false, array(
					'question' => $question
				) ); ?>
			<?php endforeach; ?>
		</details>
	<?php
	return ob_get_clean();
}

// SETTINGS
// -----------------------------------------------------------------------

// Set notifications that will get picked up by the block editor
// we expect these notifications to get picked up very quickly so expire them after a few seconds.
function rcq_set_notification_message( $message ) {
    set_transient( 'rcq_notifiction', $message, 10 );
}

// check for notices to display to the user after a page refresh
function rcq_enqueue_notifications() {
    $notification_message = get_transient( 'rcq_notifiction' );
    if ( empty( $notification_message ) ) {
        return false;
    }

    wp_enqueue_script(
        'rcq-notifications',
        plugins_url( 'js/notifications.js', __FILE__ ),
        array( ),
        false,
        true
    );

    wp_add_inline_script( 'rcq-notifications', 'const RCQ_NOTIFICATION = "'. $notification_message .'";', 'before' );

    // delete the notification so we don't get confused on future page loads
    delete_transient( 'rcq_notifiction' );
}
add_action( 'enqueue_block_editor_assets', 'rcq_enqueue_notifications' );

// Register the setting
function rcq_register_settings() {
    register_setting( 'rcq_settings', 'rcq_api_token' );
    register_setting( 'rcq_settings', 'rcq_question_language' );
	register_setting( 'rcq_settings', 'rcq_parent_post_type', array(
		'default' => 'post',
	) );
}

add_action( 'admin_init', 'rcq_register_settings' );

// Add the settings page
function rcq_settings_page() {
    add_options_page(
		__( 'Reading Comprehension Questions Settings', 'reading-comprehension-questions' ), // Page title
		__( 'Reading Comprehension Questions', 'reading-comprehension-questions' ), // Menu title
		'manage_options', // Capability
		'rcq_settings', // Menu slug
		'rcq_settings_page_callback' // Callback function
    );
}

add_action( 'admin_menu', 'rcq_settings_page' );

// Callback function for the settings page
function rcq_settings_page_callback() {
    // Check user capability
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }
  
    // Display the settings form
    ?>

    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php
                // Output nonce, action, and option_page fields for the settings form
                settings_fields( 'rcq_settings' );
                do_settings_sections( 'rcq_settings' );
            ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="rcq_api_token"><?php _e( 'OpenAI API Key', 'reading-comprehension-questions' ); ?></label></th>
                    <td>
                        <input type="text" name="rcq_api_token" id="rcq_api_token" value="<?php echo esc_attr( get_option( 'rcq_api_token' ) ); ?>" class="regular-text" />
                        <p class="description"><?php _e( 'Enter your OpenAI API key here.', 'reading-comprehension-questions' ); ?></p>
                    </td>
                </tr>

                

                <tr>
                    <th scope="row"><label for="rcq_api_token"><?php _e( 'Language to generate questions in', 'reading-comprehension-questions' ); ?></label></th>
                    <td>
                        <?php 
                            $question_language = get_option( 'rcq_question_language' ); 
                            if (is_null($question_language)) {
                                $question_language = 'auto';
                            }
                        ?>
                        <select name="rcq_question_language" id="rcq_question_language">
                            <option value="auto" <?php selected( $question_language, 'auto' ); ?> ><?php _e( 'Auto-detect', 'reading-comprehension-questions' ); ?></option>
                            <option value="en" <?php selected( $question_language, 'en' ); ?> ><?php _e( 'English', 'reading-comprehension-questions' ); ?></option>
                            <option value="es" <?php selected( $question_language, 'es' ); ?> ><?php _e( 'Spanish', 'reading-comprehension-questions' ); ?></option>
                            <option value="it" <?php selected( $question_language, 'it' ); ?> ><?php _e( 'Italian', 'reading-comprehension-questions' ); ?></option>
                            <option value="fr" <?php selected( $question_language, 'fr' ); ?> ><?php _e( 'French', 'reading-comprehension-questions' ); ?></option>
                        </select>
                        <p class="description"><?php _e( 'Choose the language you want your questions to be generated in. Leave set on "Auto-detect" to let the AI take it\'s best guess.', 'reading-comprehension-questions' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="rcq_parent_post_type"><?php _e( 'Post type to allow reading comprehension questions', 'reading-comprehension-questions' ); ?></label></th>
                    <td>
                        <?php 
                            function filter_post_types($pt) {
                                $not_allowed = array( 'attachment', RCQ_POST_TYPE_KEY );
                                return ! in_array( $pt->name, $not_allowed );
                            }
                            $parent_post_type = get_option( 'rcq_parent_post_type' ); 
                            $post_types = array_values(
                                array_filter( 
                                    get_post_types( array( 'public' => true, 'show_in_menu' => true ), 'objects' ), 
                                    'filter_post_types'
                                ) 
                            );
                        ?>
                        <select name="rcq_parent_post_type" id="rcq_parent_post_type">
                            <?php foreach ($post_types as $post_type): ?>
                                <option value="<?php echo $post_type->name; ?>" <?php selected( $parent_post_type, $post_type->name); ?> ><?php _e($post_type->labels->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>


            </table>
            
            <?php submit_button(); ?>   
        </form>
    </div>
    <?php
}


// LOAD THE LANGUAGE FILES FOR THE UI
function rcq_text_domain_load() {
	load_plugin_textdomain( 'reading-comprehension-questions', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

add_action( 'init', 'rcq_text_domain_load' );