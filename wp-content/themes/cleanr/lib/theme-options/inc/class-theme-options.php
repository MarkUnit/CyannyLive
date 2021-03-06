<?php
/**
 * Theme Options class
 * Do not edit this file unless you know what you're doing
 *
 * @package cleanr
 * @author Graph Paper Press
 */

/**
 * Includes
 *
 * 
 */
include_once('custom-fonts.php');

/**
 * Main Class
 *
 * 
 */
class ThemeOptions {

	public $sections;
	public $checkboxes;
	public $settings;

	/**
	 * Construct
	 *
	 * 
	 */
	public function __construct() {

		// Get theme info
		if ( function_exists('wp_get_theme') ) {
			$theme = wp_get_theme();
			$this->theme['authorURI'] = $theme->{'Author URI'};
		} else {
			$theme = wp_get_theme( get_stylesheet_directory() . '/style.css' );
			$this->theme['authorURI'] = $theme['AuthorURI'];
		}
		// Set constants for theme info		
		$this->theme['name'] = $theme['Name'];
		$this->theme['nicename'] = strtolower( str_replace( " ", "-", $this->theme['name'] ) );		
		$this->theme['version'] = $theme['Version'];
		$this->theme['author'] = $theme['Author'];
		$this->theme['uri'] = $this->theme['authorURI'] . "themes/" . $this->theme['nicename'];
		$this->theme['support'] = $this->theme['authorURI'] . 'support/';
		$this->theme['readme'] = get_template_directory_uri() . '/readme.txt';

		// This will keep track of the checkbox options for the formHandler function.
		$this->checkboxes = array();
		$this->settings = array();
		$this->getSettings();

		add_action( 'admin_menu', array( &$this, 'addPages' ) );
		add_action( 'admin_init', array( &$this, 'registerSettings' ) );
		add_action( 'admin_notices', array( &$this, 'updateNotice') );
		add_action( 'wp_ajax_readme', array( &$this, 'displayReadme' ) );

		/* if ( ! get_option( 'cleanr_options' ) )
			$this->initializeSettings(); */

	}

	/**
	 * Add options page
	 *
	 * 
	 */
	public function addPages() {

		$admin_page = add_theme_page( __( 'Theme Options', 'cleanr' ), __( 'Theme Options', 'cleanr' ), 'manage_options', 'cleanr-options', array( &$this, 'displayPage' ) );

		add_action( 'admin_print_scripts-' . $admin_page, array( &$this, 'addScripts' ) );
		add_action( 'admin_print_styles-' . $admin_page, array( &$this, 'addStyles' ) );

	}

	/**
	 * Create settings field
	 *
	 * 
	 */
	public function createSetting( $args = array() ) {

		$defaults = array(
			'id'      => 'default_field',
			'title'   => __( 'Default Field', 'cleanr' ),
			'desc'    => __( 'This is a default description.', 'cleanr' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'general',
			'choices' => array(),
			'class'   => '',
			'html'    => ''
		);

		extract( wp_parse_args( $args, $defaults ) );

		$field_args = array(
			'type'      => $type,
			'id'        => $id,
			'desc'      => $desc,
			'std'       => $std,
			'choices'   => $choices,
			'label_for' => $id,
			'class'     => $class,
			'html'      => $html
		);

		if ( $type == 'checkbox' )
			$this->checkboxes[] = $id;

		add_settings_field( $id, $title, array( $this, 'buildOptions' ), 'cleanr-options', $section, $field_args );
	}

	public function updateNotice( $notice=null, $message=null ) {

		if ( is_null( $message ) ) {
			$message = __( 'Settings updated.', 'cleanr' );
		}

		// our default notice
		if ( is_null( $notice ) && ! empty( $_GET['settings-updated'] ) ) {
			$notice = $_GET['settings-updated'];
		}

		// additional classes for styling and fading
		// i.e. 'updated other class more class'
		$classes = __( 'updated ', 'cleanr' );

	    if ( $notice ) {
			add_settings_error( 'cleanr-notices', 'cleanr-updated', $message, $classes );
	    }

		settings_errors( 'cleanr-notices' );
	}


	/**
	 * Display options page
	 *
	 * 
	 */
	public function displayPage() {

		echo '<div id="cleanr-wrap" class="wrap">';
		screen_icon( 'themes' );
		echo '<h2>' . __( 'Theme Options', 'cleanr' ) . '</h2>';
		echo '<ul class="theme-info">';
		echo '<li><a href="' . $this->theme['uri'] . '" target="_blank">' . $this->theme['name'] . '</a></li>';
		echo '<li>' . __( 'Version: ', 'cleanr' ) . $this->theme['version'] . '</li>';
		echo '<li><a href="' . $this->theme['support'] . '"" target="_blank">' . __( 'Support', 'cleanr' ) . '</a></li>';
		echo '<li><a class="thickbox" href="' . get_option('siteurl') . '/wp-admin/admin-ajax.php?action=readme&height=600&width=640" title="' . __( 'Theme Instructions', 'cleanr' ) . '">' . __( 'Instructions', 'cleanr' ) . '</a></li>';
		echo '</ul>';

		echo '<form action="options.php" method="post" id="cleanr_form">';

		$this->updateNotice();

		settings_fields( 'cleanr_options' );

		// show tabbed interface
		if ( $this->tabbed == true ) {

			$tab_html = null;
			foreach ( $this->sections as $section_slug => $section ) {
				$tab_html .= '<li><a href="#' . $section_slug . '">' . $section . '</a></li>';
			}

			print '<div id="cleanr-tabs" class="ui-tabs">';
			print '<ul class="ui-tabs-nav">'.$tab_html.'</ul>';
			do_settings_sections( $_GET['page'] );
			print '</div>';

		} else { // show one page of options

			do_settings_sections( $_GET['page'] );

		}

		echo '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . __( 'Save Changes', 'cleanr' ) . '" /></p>
		<p class="reset"><input type="button" class="reset-button reset-handle button-secondary" name="reset" value="' . __( 'Restore Defaults', 'cleanr' ) . '" /></p>
	</form>';

	echo '<script type="text/javascript">
		jQuery(document).ready(function($) {
			var sections = [];';

			foreach ( $this->sections as $section_slug => $section )
				echo "sections['$section'] = '$section_slug';";

			if ( $this->tabbed == true ) {

				echo 'var wrapped = $(".wrap h3").wrap("<div class=\"ui-tabs-panel\">");
				wrapped.each(function() {
					$(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
				});
				$(".ui-tabs-panel").each(function(index) {
					$(this).attr("id", sections[$(this).children("h3").text()]);
					if (index > 0)
						$(this).addClass("ui-tabs-hide");
				});
				$(".ui-tabs").tabs({
					fx: { opacity: "toggle", duration: 40 }
				});';

			}

			echo '

			$("input[type=text], textarea").each(function() {
				if ($(this).val() == $(this).attr("placeholder") || $(this).val() == "")
					$(this).css("color", "#999");
			});

			$("input[type=text], textarea").focus(function() {
				if ($(this).val() == $(this).attr("placeholder") || $(this).val() == "") {
					$(this).val("");
					$(this).css("color", "#000");
				}
			}).blur(function() {
				if ($(this).val() == "" || $(this).val() == $(this).attr("placeholder")) {
					$(this).val($(this).attr("placeholder"));
					$(this).css("color", "#999");
				}
			});

			$(".wrap h3, .wrap table").show();

			// This will make the "warning" checkbox class really stand out when checked.
			// I use it here for the Reset checkbox.
			$(".warning").change(function() {
				if ($(this).is(":checked"))
					$(this).parent().css("background", "#c00").css("color", "#fff").css("fontWeight", "bold");
				else
					$(this).parent().css("background", "none").css("color", "inherit").css("fontWeight", "normal");
			});

			// Browser compatibility
			if ($.browser.mozilla)
			         $("form").attr("autocomplete", "off");

			 // Slider
			// calls appendo
            $("#slideshow_list").appendo({
                allowDelete: false,
                labelAdd: "Add Another Slide",
                subSelect: "li.slide:last",
                onAdd: clear_fields
            });

            // slide delete button
			$("#slideshow_list li.slide").ready(function() {
				if($("#slideshow_list li.slide").size() == 1) {
					$(".submitdelete").hide();
				}
				return false;
			});
            $("#slideshow_list li.slide .remove_slide").live("click", function() {
                if($("#slideshow_list li.slide").size() == 1) {
                    alert("Sorry, you need at least one slide");
                }
                else {
                    $(this).parent().slideUp(300, function() {
                        $(this).remove();
                    })
                }
                return false;
            });

            function clear_fields($row)
            {

                $row.find("input,textarea").val("");
                $row.find(".previewimg").html("");
                return true;
            };

            $("#slideshow_list").sortable();

			$("#slides-details-button").click(function(){
				$("#slideshow_list li").toggleClass("list-view");
				$("#slides-details-button").toggleClass("list");

			});

		});
	</script>
</div>';
	}

	/**
	 * Description for section
	 *
	 * 
	 */
	public function displaySection() {}

	/**
	 * Parse the readme.txt. Shown in thickbox using wp_ajax_readme call.
	 *
	 * @uses parseFile()
	 * @return html parsed from markdown in txt
	 * 
	 */
	public function displayReadme() {
		print $this->parseFile( $this->theme['readme'] );
		exit();
	}

	/**
	 * HTML output for text field
	 *
	 * 
	 */
	public function buildOptions( $args = array() ) {

		extract( $args );

		$theme_options = get_option( 'cleanr_options' );

		if ( empty($theme_options) )
			$theme_options[$id] = $std;
		elseif ( ! isset( $theme_options[$id] ) )
			$theme_options[$id] = 0;

		$field_class = '';
		if ( $class != '' )
			$field_class = ' ' . $class;

		switch ( $type ) {

			case 'heading':
				echo '</td></tr><tr valign="top"><td colspan="2"><h4>' . $desc . '</h4>';
				break;

			case 'checkbox':
				echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="cleanr_options[' . $id . ']" value="1" ' . checked( $theme_options[$id], 1, false ) . ' /> <label for="' . $id . '">' . $desc . '</label>';
				break;

			case 'select':
				echo '<select class="select' . $field_class . '" name="cleanr_options[' . $id . ']">';
				foreach ( $choices as $value => $label )
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $theme_options[$id], $value, false ) . '>' . $label . '</option>';
				echo '</select>';
				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span>';
				break;

			case 'multiselect':
				echo '<select class="select' . $field_class . '" name="cleanr_options[' . $id . '][]" multiple="multiple">';
				foreach ( $choices as $value => $label ) {

					/**
					 * If the user has saved options use them
					 * or use what is set in the std value
					 */
					if ( is_array( $theme_options[$id] ) && in_array( $value, $theme_options[$id] ) ){
						$selected = 'selected="selected"';
					} else {
						$selected = null;
					}

					echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . $label . '</option>';
				}
				echo '</select>';

				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span>';
				break;

			case 'multicheck':
				$i = 0;
				foreach ( $choices as $value => $label ) {
					/**
					 * If the user has saved options use them
					 * or use what is set in the std value
					 */
					if ( is_array( $theme_options[$id] ) && in_array( $value, $theme_options[$id] ) ){
						$selected = 'checked="checked"';
					} else {
						$selected = null;
					}

					echo '<input class="checkbox' . $field_class . '" type="checkbox" name="cleanr_options[' . $id . '][]" id="' . $id . $i . '" value="' . esc_attr( $value ) . '"' . $selected . '> <label for="' . $id . $i . '">' . $label . '</label>';
					echo '<br />';
					$i++;
				}
				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span>';
				break;

			case 'radio':
				$i = 0;
				foreach ( $choices as $value => $label ) {
					echo '<input class="radio' . $field_class . '" type="radio" name="cleanr_options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $theme_options[$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
					//if ( $i < count( $theme_options ) - 1 )
					echo '<br />';
					$i++;
				}
				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span>';
				break;

			case 'radio_img':
				echo '<fieldset>';
				$i = 0;
				$i++;
				foreach( $choices as $k => $v ) {
					$selected = (checked($theme_options[$id], $k, false) != '')?' cleanr-radio-img-selected':'';
					echo '<label class="cleanr-radio-img'.$selected.' cleanr-radio-img-'.$theme_options[$id].'" for="' . $id . '_' . $i . '">';
					echo '<input type="radio" id="' . $id . '_' . $i . '" name="cleanr_options[' . $id . ']" '.$field_class.' value="'.$k.'" '.checked($theme_options[$id], $k, false).'/>';
					echo '<img src="'.$v['img'].'" alt="'.$v['title'].'" onclick="jQuery:cleanr_radio_img_select(\'' . $id . '_' . $i . '\', \''.$theme_options[$id].'\');" />';
					echo '<br/><span>'.$v['title'].'</span>';
					echo '</label>';
					$i++;
				}//foreach
				echo ( isset( $theme_options[$desc] ) && !empty( $theme_options[$desc] ) ) ? '<br/><span class="description">'.$theme_options[$desc].'</span>':'';
				echo '</fieldset>';
				break;

			case 'textarea':
				echo '<textarea class="' . $field_class . '" id="' . $id . '" name="cleanr_options[' . $id . ']" placeholder="' . $std . '" rows="5" cols="30">' . wp_htmledit_pre( $theme_options[$id] ) . '</textarea>';
				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span>';
				break;

			case 'password':
				echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="cleanr_options[' . $id . ']" value="' . esc_attr( $theme_options[$id] ) . '" autocomplete="off" />';

				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span>';
				break;

			case 'upload':
				echo '<input id="' . $id . '" class="upload-url' . $field_class . '" type="hidden" name="cleanr_options[' . $id . ']" value="' . esc_attr( $theme_options[$id] ) . '" /><div class="imgpreview">';

				if( $theme_options[$id] != '' ) {
					echo '<img class="cleanr-screenshot" id="cleanr-screenshot-'.$id.'" src="' . esc_attr( $theme_options[$id] ) . '" />';
				}
				echo "</div>";

				if ( $theme_options[$id] == '' ) {
					$remove = ' style="display:none;"';
					$upload = '';
				} else {
					$remove = '';
					$upload = ' style="display:none;"';
				}

				echo '<a href="javascript:void(0);" class="upload_button button-secondary"'.$upload.' rel-id="'.$id.'">'.__('Browse', 'cleanr').'</a>';
				echo '<a href="javascript:void(0);" class="upload_button_remove"'.$remove.' rel-id="'.$id.'">'.__('Remove Upload', 'cleanr').'</a>';

				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span>';
				break;

			case 'html':
				echo $html;
				break;

			case 'color':
				echo '<div class="farb-popup-wrapper">';
				echo '<input class="popup-colorpicker' . $field_class . '" type="text" id="' . $id . '" name="cleanr_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $theme_options[$id] ) . '" />
				<div class="farb-popup"><div class="farb-popup-inside"><div id="' . $id . 'picker" class="color-picker"></div></div>';
				echo '</div>';
				break;

			case 'slide':
				if ( $desc != '' )
                    echo '<span class="description slide-desc' . $field_class . '">' . $desc . '</span>';

                echo '<br /><span id="slides-details-button"></span>';
                echo '<ul id="slideshow_list">';

				if ( $theme_options[$id] <> '' ) {

					$slides = array();
					foreach ($theme_options[$id]['title'] as $k => $v) {
						$slides[] = array(
		       				'title' => $v,
		       				'link' => $theme_options[$id]['link'][$k],
							'caption' => $theme_options[$id]['caption'][$k],
							'image' => $theme_options[$id]['image'][$k]
		       			);
					}

					$i = 1;
					foreach ($slides as $slide) {

						echo '<li class="slide">';
						echo '<span class="description">' . __( 'Slide Title', 'cleanr' ) . '</span>';
						echo '<input class="regular-text' . $field_class . '" name="cleanr_options[' . $id . '][title][]" placeholder="' . $std . '" id="'. $id .'_title_'.$i.'"  value="'.$slide['title'].'" type="text" />';

						echo '<span class="description">' . __( 'Slide Link', 'cleanr' ) . '</span>';
						echo '<input class="regular-text' . $field_class . '" name="cleanr_options[' . $id . '][link][]" placeholder="' . $std . '" id="'. $id .'_title_'.$i.'"  value="'.$slide['link'].'" type="text" />';

						echo '<span class="description">' . __( 'Slide Caption', 'cleanr' ) . '</span>';
						echo '<textarea class="'.$field_class.'" name="cleanr_options[' . $id . '][caption][]" id="'. $id .'_caption_'.$i.'" cols="40" rows="4">'.$slide['caption'].'</textarea>';

						echo '<span class="description">' . __( 'Slide Image', 'cleanr' ) . '</span>';
						echo '<input class="upload-input-text src" name="cleanr_options[' . $id . '][image][]" id="'. $id .'_image_'.$i.'" type="text" value="'.$slide['image'].'" type="text" />
						<a href="'.get_option('siteurl').'/wp-admin/admin-ajax.php?action=choice&width=150&height=100" id="'.$id.'_button" class="button upbutton">' . __( 'Upload', 'cleanr' ) . '</a>';

		                echo '<div class="clear"></div><div class="previewimg">';
		                if ( $slide['image'] != "" )
						{
							echo '<img class="uploadedimg" id="image_'. $id .'_image_'.$i.'" src="'.$slide['image'].'" />';
						}
		                echo '</div>';
						echo '<a class="remove_slide submitdelete">' . __( 'Delete Slide', 'cleanr' ) . '</a>';
						echo '</li>';
						$i++;
					}

				} else {
					$i = 1;
					echo '<li class="slide">';
					echo '<span class="description">' . __( 'Slide Title', 'cleanr' ) . '</span>';
					echo '<input class="regular-text' . $field_class . '" name="cleanr_options[' . $id . '][title][]" placeholder="' . $std . '" id="'. $id .'_title_'.$i.'"  value="" type="text" />';

					echo '<span class="description">' . __( 'Slide Link', 'cleanr' ) . '</span>';
					echo '<input class="regular-text' . $field_class . '" name="cleanr_options[' . $id . '][link][]" placeholder="' . $std . '" id="'. $id .'_title_'.$i.'"  value="" type="text" />';

					echo '<span class="description">' . __( 'Slide Caption', 'cleanr' ) . '</span>';
					echo '<textarea class="'.$field_class.'" name="cleanr_options[' . $id . '][caption][]" id="'. $id .'_caption_'.$i.'" cols="40" rows="4"></textarea>';

					echo '<span class="description">' . __( 'Slide Image', 'cleanr' ) . '</span>';
					echo '<input class="upload-input-text src" name="cleanr_options[' . $id . '][image][]" id="'. $id .'_image_'.$i.'" type="text" value="" type="text" />
					<a href="'.get_option('siteurl').'/wp-admin/admin-ajax.php?action=choice&width=150&height=100" id="'.$id.'_button" class="button upbutton">' . __( 'Upload', 'cleanr' ) . '</a>';

	                echo '<div class="clear"></div><div class="previewimg">';
	                echo '</div>';
					echo '<a class="remove_slide submitdelete">' . __( 'Delete Slide', 'cleanr' ) . '</a>';
					echo '</li>';
				}

				echo '</ul>';
				break;

			case 'text':
				default:
		 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="cleanr_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $theme_options[$id] ) . '" autocomplete="off" />';
		 		if ( $desc != '' )
		 			echo '<span class="description">' . $desc . '</span>';
		 		break;
		}
	}

	/**
	 * Settings and defaults
	 *
	 * 
	 */
	public function getSettings() {

		// extend this class to create theme options. See config.php
	}

	/**
	 * Initialize settings to their default values
	 *
	 * 
	 */
	public function initializeSettings( $settings=null ) {

		if ( is_null( $settings ) )
			$settings = $this->settings;

		$default_settings = array();
		foreach ( $settings as $id => $setting ) {
			if ( $setting['type'] != 'heading' )
				$default_settings[$id] = $setting['std'];
		}

		update_option( 'cleanr_options', $default_settings );
	}

	/**
	* Register settings
	*
	* 
	*/
	public function registerSettings() {

		register_setting( 'cleanr_options', 'cleanr_options', array ( &$this, 'formHandler' ) );

		foreach ( $this->sections as $slug => $title ) {
			add_settings_section( $slug, $title, array( &$this, 'displaySection' ), 'cleanr-options' );
		}

		$this->getSettings();

		foreach ( $this->settings as $id => $setting ) {
			$setting['id'] = $id;
			$this->createSetting( $setting );
		}
	}

	/**
	* jQuery Tabs
	*
	* 
	*/
	public function addScripts() {
		if ( $this->tabbed == true )
			wp_print_scripts( 'jquery-ui-tabs' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
    	wp_enqueue_script( 'farbtastic' );
		wp_register_script( 'cleanr-options-scripts', get_template_directory_uri() . '/lib/theme-options/js/options.js', array( 'jquery', 'media-upload','thickbox', 'farbtastic' ) );
		wp_enqueue_script( 'cleanr-options-scripts' );
		wp_register_script( 'appendo', get_template_directory_uri() . '/lib/theme-options/js/jquery.appendo.js', array( 'jquery' ) );
		wp_enqueue_script( 'appendo' );
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	/**
	* Styling for the theme options page
	*
	* 
	*/
	public function addStyles() {

		wp_register_style( 'cleanr-options-styles', get_template_directory_uri() . '/lib/theme-options/css/options.css' );
		if ( $this->tabbed == true )
			wp_enqueue_style( 'cleanr-options-styles' );
		wp_enqueue_style('thickbox');
		wp_enqueue_style( 'farbtastic' );
	}

	/**
	 * Form Handler
	 *
	 * 
	 */
	public function formHandler( $input ) {

		/* if ( ! empty( $_POST['reset'] ) ) {
			//$input = $this->resetSettings( $input );
			$input = "";
			return $input;
		} */
		if ( ! empty( $_POST['reset'] ) ) {
			$defaults = array();
			foreach ( $this->settings as $id => $setting ) {
				if ( $setting['type'] != 'heading' ) {
					$defaults[$id] = $setting['std'];
				}
			}
			return $defaults;
		}


		if ( ! isset( $input['reset_theme'] ) ) {
			$theme_options = get_option( 'cleanr_options' );
			foreach ( $this->checkboxes as $id ) {
				if ( isset( $theme_options[$id] ) && ! isset( $input[$id] ) )
					unset( $theme_options[$id] );
			}

			return $input;
		}

		// Create our array for storing the validated options
		$output = array();

		// Loop through each of the incoming options
		foreach( $input as $key => $value ) {

			// Check to see if the current option has a value. If so, process it.
			if( isset( $input[$key] ) ) {

				// Strip all HTML and PHP tags and properly handle quoted strings
				$output[$key] = wp_filter_nohtml_kses( $input[ $key ] );

			} // end if

		} // end foreach

		// Return the array processing any additional functions filtered by this action
		return apply_filters( 'formHandler', $output, $input );

	}


	/**
	 * Do reset setting stuff here
	 */
	public function resetSettings( $inputs=array() ){

		$defaults = array();

		foreach( $inputs as $key => $value ){
			$defaults[ $key ] = '';
		}
		return $defaults;
	}

	/**
	 * Format categories for $choices array.
	 *
	 * @uses get_terms();
	 * @return $terms['value']['label']
	 * @todo check if taxonomy exists
	 */
	public function getCategories( $taxonomy = null, $firstblank = false ) {

		if ( is_null( $taxonomy ) )
			$taxonomy = 'category';

		$args = array(
			'hide_empty' => 0
			);

		$terms_obj = get_terms( $taxonomy, $args );
		$items = array();
		if( $firstblank ) {
			$terms[''] = '-- Choose One --';
		}
		foreach ( $terms_obj as $tt ) {
			$terms[$tt->term_id] = $tt->name;
		}

		return $terms;
	}

	/**
	 * Format Pages for $choices array.
	 *
	 * @uses get_pages
	 * @param $value, the value to be used in the option value=""
	 * default is ID, any index can be used as a value.
	 * see codex: http://codex.wordpress.org/Function_Reference/get_pages
	 */
	public function getPages( $value = null, $firstblank = false ) {

		if ( is_null( $value ) )
			$value = 'ID';

		$args = array( 'post_type' => 'page' );
		$obj = get_pages( $args );

		$items = array();
		if( $firstblank ) {
			$terms[''] = '-- Choose One --';
		}

		foreach ( $obj as $item ) {
			$items[$item->$value] = $item->post_title;
		}

		return $items;
	}

	/**
	 * Parse Readme.txt
	 *
	 * @param string $url URL of textfile readme.txt
	 * @return string readme code
	 */
	public function parseFile( $url=null ) {

		// no/wrong url
		if ( empty($url) or basename($url) != 'readme.txt')
			return false;

		$response = wp_remote_get( $url );
		if( is_wp_error( $response ) ) {
		   echo 'Unable to load the instructions.';
		} else {
		   $readme = $response['body'];
		}

		// make links clickable
		$readme = make_clickable(nl2br(esc_html($readme)));
		// code, strong, em
		$readme = preg_replace('/`(.*?)`/', '<code>\\1</code>', $readme);
		$readme = preg_replace('/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme);
		$readme = preg_replace('/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme);
		// headings
		$readme = preg_replace('/=== (.*?) ===/', '<h2>\\1</h2>', $readme);
		$readme = preg_replace('/== (.*?) ==/', '<h3>\\1</h3>', $readme);
		$readme = preg_replace('/= (.*?) =/', '<h4>\\1</h4>', $readme);
		// links
		$readme = preg_replace('#(^|[\[]{1}[\s]*)([^\n<>^\)]+)([\]]{1}[\(]{1}[\s]*)(http://|ftp://|mailto:|https://)([^\s<>]+)([\s]*[\)]|$)#', '<a href="$4$5">$2</a>', $readme);
		$readme = preg_replace('#(^|[^\"=]{1})(http://|ftp://|mailto:|https://)([^\s<>]+)([\s\n<>]|$)#', '$1<a href="$2$3">$2$3</a>$4', $readme);

		return  $readme;
	}
} // end class

function cleanr_option( $option ) {
	$theme_options = get_option( 'cleanr_options' );
	if ( isset( $theme_options[$option] ) )
		return $theme_options[$option];
	else
		return false;
}

?>