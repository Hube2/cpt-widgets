<?php 
	
	/*
	    Custom Widget for Custom Post Type Date Archives
			
			This template has more options than the standard archives widget
			It allow the person setting it up to choose the type of list and
			allows them to limit the number of links shown
	    
	    Instructions:
	    	
	      1) Copy this file to your theme and rename it. Replace CPT with your Custom Post Type Slug
	         example: news-archives-widget.php
	         
	         OR
	         
	         Copy and past code into your theme's functions.php file or other location
					 
					 OR
					 
					 Can easily be added to a plugin
			     
			  2) search for CPT_NICE_NAME and replace with your Custom Post Type name
			     with no special characters or spaces, use underscores
			     example: News_Archive
			     
			  3) Search for CPT_LOWER_NICE_NAME and replace with youir custom post type name
			     this may actually be the same as 2 but this one requires lower case letters
			     example: news_archive
			     
			  4) Search for CPT_NAME and replace with the Plural Label value of your Custom Post Type
			     example: News Archive
			     
			  5) Search for CPT_SLUG and replace with your Custom Post Type Slug
			     example: news-archive
			     
			  6) See other comments for important infromation
		
	*/
	
	add_action('widgets_init', 'register_sidebar_CPT_LOWER_NICE_NAME_archive_widget');
	function register_sidebar_CPT_LOWER_NICE_NAME_archive_widget() {
		// this function will register a sidebar specifically for the
		// CPT of the site if a sidebar has not allready been defined
		// with the same sidebar id value
		// a check is done first so that there is no conflict with
		// other widget examples in this series
		$sidebar_id = 'CPT_SLUG-sidebar';
		global $wp_registered_sidebars;
		if (isset($wp_registered_sidebars[$sidebar_id])) {
			return;
		}
		register_sidebar(
			array(
				'name' => __('CPT_NAME Sidebar'),
				'id' => $sidebar_id,
				'description' => __('Custom Widget Area for CPT_NAME'),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget' => '</div>',
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>'
			)
		);
	} // end function register_sidebar_CPT_LOWER_NICE_NAME_archive_widget
	
	// register the widget
	add_action('widgets_init', 'register_CPT_LOWER_NICE_NAME_archive_widget');
	function register_CPT_LOWER_NICE_NAME_archive_widget() {
		register_widget('Class_CPT_NICE_NAME_Archives_Widget');
	} // end function register_CPT_LOWER_NICE_NAME_archive_widget
	
	class Class_CPT_NICE_NAME_Archives_Widget extends WP_Widget {
		/*
			This is a copy of the WP Archives Widget
				with modifications for a specific post type
				also some code cleanup to make is easier to understand
				as well all more extensive settings
		*/
		
		public function __construct() {
			$widget_ops = array(
				'classname' => 'CPT_LOWER_NICE_NAME_archive',
				'description' => __('A monthly archive of your site\'s CPT_NAME.')
			);
			parent::__construct(
				'CPT_LOWER_NICE_NAME_archives',
				__('CPT_NAME Archives'), $widget_ops
			);
		} // end public function __construct
		
		public function widget($args, $instance) {
			
			$count = 0;
			if (!empty($instance['count'])) {
				$count = 1;
			}
			$dropdown = 0;
			if (!empty($instance['dropdown'])) {
				$dropdown = 1;
			}
			$type = 'monthly';
			if (!empty($instance['type'])) {
				$type = $instance['type'];
			}
			$limit = '';
			if (!empty($instance['limit'])) {
				$limit = $instance['limit'];
			}
	
			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$title = __('CPT_NAME Archives');
			if (!empty($instance['title'])) {
				$title = $instance['title'];
			}
			$title = apply_filters('widget_title', $title, $instance, $this->id_base);
	
			echo $args['before_widget'];
			if ( $title ) {
				echo $args['before_title'].$title.$args['after_title'];
			}
			
			$list_args = array(
				'type' => $type,
				'show_post_count' => $count,
				'post_type' => 'CPT_SLUG',
				'limit' => $limit
			);
			if ($dropdown) {
				$list_args['format'] = 'option';
			}
			$list_args =  apply_filters('widget_CPT_SLUG_archives_args', $list_args);
			
			// do not echo
			$list_args['echo'] = 0;
			
			$options_list = wp_get_archives($list_args);
			
			// for pretty urls, replace post_type query string with pretty url
			$options_list = preg_replace('#([\'"])(https?://[^/]+)?(.*)\?post_type\=CPT_SLUG(.*[\'"])#i', 
			                             '\1\2/CPT_SLUG\3\4', $options_list);
	
			if ($dropdown) {
				$dropdown_id = "{$this->id_base}-dropdown-{$this->number}";
				?>
					<label class="screen-reader-text" for="<?php 
						echo esc_attr($dropdown_id); ?>"><?php echo $title; ?></label>
					<select id="<?php 
							echo esc_attr($dropdown_id); 
							?>" name="archive-dropdown" onchange='document.location.href=this.options[this.selectedIndex].value;'>
						<?php 
			
							switch ($list_args['type']) {
								case 'yearly':
									$label = __('Select Year');
									break;
								case 'monthly':
									$label = __('Select Month');
									break;
								case 'daily':
									$label = __('Select Day');
									break;
								case 'weekly':
									$label = __('Select Week');
									break;
								default:
									$label = __('Select Post');
									break;
							}
						?>
						<option value="/CPT_SLUG/"><?php echo esc_attr($label); ?></option>
						<?php echo $options_list; ?>
					</select>
				<?php 
			} else {
				?>
					<ul>
						<?php echo $options_list; ?>
					</ul>
				<?php
			}
			echo $args['after_widget'];
		} // end public function widget
		
		public function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$new_instance = wp_parse_args(
				(array) $new_instance, 
				array(
					'title' => '',
					'count' => 0,
					'dropdown' => '',
					'type' => 'monthly',
					'limit' => 0
				)
			);
			$instance['title'] = sanitize_text_field($new_instance['title']);
			$instance['count'] = $new_instance['count'] ? 1 : 0;
			$instance['dropdown'] = $new_instance['dropdown'] ? 1 : 0;
	
			return $instance;
		} // end public function update
		
		public function form( $instance ) {
			$instance = wp_parse_args(
				(array) $instance, 
				array(
					'title' => '',
					'count' => 0,
					'dropdown' => '',
					'type' => 'monthly',
					'limit' => 0
				)
			);
			$title = sanitize_text_field($instance['title']);
			?>
				<p>
					<label for="<?php 
							echo $this->get_field_id('title'); ?>"><?php 
							_e('Title:'); ?></label>
					<input class="widefat" id="<?php 
							echo $this->get_field_id('title'); ?>" name="<?php 
							echo $this->get_field_name('title'); ?>" type="text" value="<?php 
							echo esc_attr($title); ?>" />
				</p>
				<p>
					<label for="<?php 
							echo $this->get_field_id('type'); ?>"><?php 
							_e('Type')?></label>
					<select class="widefat" id="<?php 
							echo $this->get_field_id('type'); ?>" name="<?php 
							echo $this->get_field_name('type'); ?>">
							<?php 
								$options = array(
									'yearly' => __('Yearly'),
									'monthly' => __('Monthly'),
									'daily' => __('Daily'),
									'weekly' => __('Weekly'),
									'postbypost' => __('Individual Posts by Post Date'),
									'alpha' => __('Individual Posts by Title')
								);
								foreach ($options as $value => $label) {
									?><option value="<?php echo esc_attr($value); ?>"<?php 
											if ($value == $instance['type']) {
												?> selected="selected"<?php 
											}
											?>><?php 
											echo $label; ?></option><?php 
								}
							?>
					</select>
				</p>
				<p>
					<input class="checkbox" type="checkbox"<?php 
							checked($instance['dropdown']); ?> id="<?php 
							echo $this->get_field_id('dropdown'); ?>" name="<?php 
							echo $this->get_field_name('dropdown'); ?>" /> <label for="<?php 
							echo $this->get_field_id('dropdown'); ?>"><?php 
							_e('Display as dropdown'); ?></label>
					<br/>
					<input class="checkbox" type="checkbox"<?php 
							checked($instance['count']); ?> id="<?php 
							echo $this->get_field_id('count'); ?>" name="<?php 
							echo $this->get_field_name('count'); ?>" /> <label for="<?php 
							echo $this->get_field_id('count'); ?>"><?php 
							_e('Show post counts'); ?></label>
				</p>
				<p>
					<label for="<?php
							echo $this->get_field_id('type'); ?>"><?php 
							_e('Limit (0 for no limit)'); ?></label>
					<input type="number" class="tiny-text" size="3" value="<?php 
							echo esc_attr($instance['limit']); ?>" min="0" step="1" id="<?php
							echo $this->get_field_id('type'); ?>" name="<?php 
							echo $this->get_field_name('type') ?>" />
				</p>
			<?php
		} // end public function form
		
	} // end class Class_CPT_NICE_NAME_Archives_Widget
	
	// add date archive rewrite rules for the CPT
	add_action('init', 'CTP_NICE_NAME_archives_widget_rewrite_rules');
	function CTP_NICE_NAME_archives_widget_rewrite_rules() {
		$rules = array(
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/page/?([0-9]{1,})/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&paged=$matches[2]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&feed=$matches[2]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&feed=$matches[2]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]'
			),
			array(
				'rule' => 'CPT_SLUG/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$',
				'rewrite' => 'index.php?post_type=CPT_SLUG&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]'
			)
		);
		foreach ($rules as $rule) {
			add_rewrite_rule($rule['rule'], $rule['rewrite'], 'top');
		}
	} // end function CTP_NICE_NAME_archives_widget_rewrite_rules
	
	
	
?>