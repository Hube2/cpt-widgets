<?php 
	
	/*
			Taxonomy Widget
			similar to WP Category widget for any taxonomy
			includes addtional features
			
			This file requires taxonomy-widget.js to be in the same folder
			as this file or the file that this code is added to
	*/
	
	
	add_action('widgets_init', 'register_blunt_taxonomy_widget');
	function register_blunt_taxonomy_widget() {
		register_widget('Blunt_Taxonomy_Widget');
	} // end function register_blunt_taxonomy_widget
	
	class Blunt_Taxonomy_Widget extends WP_Widget {
		
		private $defaults = array(
			'title' => '',
			'taxonomy' => 'category',
			'exclude' => array(),
			'hierarchical' => 0,
			'dropdown' => 0,
			'count' => 0,
			'number' => 0
		);
		
		private $number_shown = 0;
		
		public function __construct() {
			$widget_ops = array(
				'classname' => 'blunt_taxonomy_widget', 
				'description' => __('A list or dropdown of terms for any taxonomy.')
			);
			parent::__construct('blunt_taxonomy_widget', __('Taoxnomy'), $widget_ops);
			
			add_action('admin_enqueue_scripts', array($this, 'admin_script'));
			add_action('wp_ajax_blunt_tax_widget_change_tax', array($this, 'change_tax_ajax'));
			add_action('wp_ajax_blunt_tax_widget_change_tax_title', array($this, 'get_tax_title'));
			add_action('wp_ajax_blunt_tax_widget_change_is_hierarchical', array($this, 'is_hierarchical_tax'));
		} // end public function __construct
		
		public function is_hierarchical_tax() {
			if (empty($_POST['nonce']) || 
					!wp_verify_nonce($_POST['nonce'], 'blunt_tax_widget_nonce') ||
					empty($_POST['taxonomy'])) {
				//echo 'failed';
				echo json_encode(false);
				exit;
			}
			
			$value = false;
			$tax_args = array(
				'public' => true,
				'publicly_queryable' => true
			);
			$taxonomies = get_taxonomies($tax_args, 'objects');
			if (isset($taxonomies[$_POST['taxonomy']])) {
				if ($taxonomies[$_POST['taxonomy']]->hierarchical) {
					$value = true;
				}
			}
			echo json_encode($value);
			exit;
		} // end public function is_hierarchical_tax
		
		public function get_tax_title() {
			if (empty($_POST['nonce']) || 
					!wp_verify_nonce($_POST['nonce'], 'blunt_tax_widget_nonce') ||
					empty($_POST['taxonomy'])) {
				//echo 'failed';
				echo '';
				exit;
			}
			
			$tax_args = array(
				'public' => true,
				'publicly_queryable' => true
			);
			$taxonomies = get_taxonomies($tax_args, 'objects');
			
			$title = '';
			if (isset($taxonomies[$_POST['taxonomy']])) {
				$title = $taxonomies[$_POST['taxonomy']]->labels->name;
			}
			echo $title;
			exit;
		} // end public function get_tax_title
		
		public function change_tax_ajax() {
			if (empty($_POST['nonce']) || 
					!wp_verify_nonce($_POST['nonce'], 'blunt_tax_widget_nonce') ||
					empty($_POST['taxonomy'])) {
				//echo 'failed';
				echo false;
				exit;
			}
			//echo $_POST['taxonomy']; exit;
			$args = array(
				'taxonomy' => $_POST['taxonomy'],
				'hide_empty' => false
			);
			$terms = get_terms($args);
			$values = array();
			$count = count($terms);
			for ($i=0; $i<$count; $i++) {
				$values[] = array(
					'value' => $terms[$i]->term_id,
					'label' => $terms[$i]->name
				);
			}
			echo json_encode($values);
			exit;
		} // end public function change_tax_ajax
		
		public function admin_script() {
			//return;
			$screen = get_current_screen();
			//echo '<pre>'; print_r($screen); die;
			if ($screen->id != 'widgets') {
				return;
			}
			// if on options page, enqueue script for ajax
			// customizing acf fields
			// using a separate script so it can be removed easily
			// if I decide to leave it in permenantly I will more the code
			// into admin.js
			$handle = 'blunt-tax-widget';
			$src = '/'.str_replace(ABSPATH, '', dirname(__FILE__)).'/taxonomy-widget.js';
			$deps = array('jquery');
			wp_register_script($handle, $src, $deps);
			
			$object_name = 'blunt_tax_widget';
			$data = array();
			$data['_ajax_url'] = admin_url('admin-ajax.php');
			$data['_nonce'] = wp_create_nonce('blunt_tax_widget_nonce');
			
			wp_localize_script($handle, $object_name, $data);
			wp_enqueue_script($handle);
		} // end public function admin_script
		
		public function widget($args, $instance) {
			/*
		
		private $defaults = array(
			'title' => '',
			'taxonomy' => 'category',
			'exclude' => array(),
			'hierarchical' => 0,
			'dropdown' => 0,
			'count' => 0,
			'number' => 0
		)
			*/
			$instance = wp_parse_args((array) $instance, $this->defaults);
			if (!is_array($instance['exclude']) || !count($instance['exclude'])) {
				$instance['exclude'] = '';
			}
			
			$term_args = array(
				'taxonomy' => $instance['taxonomy'],
				'exclude' => $instance['exclude']
			);
			if ($instance['hierarchical']) {
				$terms = $this->get_nested_terms($term_args);
			} else {
				$term_args['number'] = $instance['number'];
				$terms = get_terms($term_args);
			}
			if (!count($terms)) {
				return;
			}
			//echo '<pre>'; print_r($terms); echo '</pre>';
			
			$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
			
			echo $args['before_widget'];
			if ($title) {
				echo $args['before_title'].$title.$args['after_title'];
			}
			
			$tax_args = array(
				'public' => true,
				'publicly_queryable' => true
			);
			$taxonomies = get_taxonomies($tax_args, 'objects');
			//echo '<pre>'; print_r($taxonomies); echo '</pre>';
			$single_label = $taxonomies[$instance['taxonomy']]->labels->singular_name;
			
			$this->number_shown = 0;
			
			if ($instance['dropdown']) {
				// show select
				$dropdown_id = $this->id_base.'-dropdown-'.$this->number;
				?>
					<label class="screen-reader-text" for="<?php 
						echo esc_attr($dropdown_id); ?>"><?php echo $title; ?></label>
					<select id="<?php 
							echo esc_attr($dropdown_id); 
							?>" onchange="document.location.href=this.options[this.selectedIndex].value;" name="<?php 
							echo $this->get_field_id('dropdown'); ?>">
						<option value="">Select <?php echo $single_label; ?></option>
						<?php 
							$this->walk_options($instance, $terms);
						?>
					</select>
				<?php 
			} else {
				// show list
				?>
					<ul>
						<?php 
							$this->walk_list($instance, $terms);
						?>
					</ul>
				<?php 
			}
			
			
			echo $args['after_widget'];
			
		} // end public function widget
		
		private function walk_options($instance, $terms, $level=0) {
			foreach ($terms as $term) {
				?>
					<option value="<?php 
							echo get_term_link($term, $instance['taxonomy']); ?>"><?php 
							echo str_repeat('&nbsp;', $level*3);
							echo $term->name; 
							if ($instance['count']) {
								echo ' &nbsp;(',$term->count,')';
							}
							?></option>
				<?php 
				$this->number_shown++;
				if (($instance['number']) && $this->number_shown >= $instance['number']) {
					return;
				}
				if (isset($term->children) && count($term->children)) {
					$this->walk_options($instance, $term->children, $level+1);
				} // end if children
				if (($instance['number']) && $this->number_shown >= $instance['number']) {
					return;
				}
			} // end foreach term
		} // end private function walk_options
		
		private function walk_list($instance, $terms) {
			$break = false;
			foreach ($terms as $term) {
				?>
					<li class="<?php 
							echo $instance['taxonomy']; ?>-item <?php 
							echo $instance['taxonomy']; ?>-item-<?php echo $term->term_id; ?>">
						<a href="<?php 
							echo get_term_link($term, $instance['taxonomy']); ?>" title="<?php 
							echo $term->description; ?>"><?php echo $term->name;  
							if ($instance['count']) {
								echo ' &nbsp;(',$term->count,')';
							}
							?></a>
						<?php 
							$this->number_shown++;
							if (($instance['number']) && $this->number_shown >= $instance['number']) {
								$break = true;
							}
							if (!$break && isset($term->children) && count($term->children)) {
								?>
									<ul class="children">
										<?php 
											$this->walk_list($instance, $term->children);
										?>
									</ul>
								<?php 
							} // end if children
							if (($instance['number']) && $this->number_shown >= $instance['number']) {
								$break = true;
							}
						?>
					</li>
				<?php 
				if ($break) {
					return;
				}
			} // end foreach term
		} // end private function walk_list
		
		private function get_nested_terms($args, $parent=0) {
			$terms = get_terms(array_merge($args, array('parent' => $parent)));
			if (count($terms)) {
				foreach ($terms as $index => $term) {
					$terms[$index]->children = $this->get_nested_terms($args, $term->term_id);
				}
			}
			return $terms;
		} // end private function get_nested_terms
		
		public function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = sanitize_text_field($new_instance['title']);
			$instance['taxonomy'] = $new_instance['taxonomy'];
			$instance['exclude'] = array();
			if (!empty($new_instance['exclude'])) {
				$instance['exclude'] = $new_instance['exclude'];
			}
			if (!is_array($instance['exclude'])) {
				$instance['exclude'] = array($instance['exclude']);
			}
			$instance['hierarchical'] = 0;
			if (!empty($new_instance['hierarchical'])) {
				$instance['hierarchical'] = 1;
			}
			$instance['dropdown'] = 0;
			if (!empty($new_instance['dropdown'])) {
				$instance['dropdown'] = 1;
			}
			$instance['count'] = 0;
			if (!empty($new_instance['count'])) {
				$instance['count'] = 1;
			}
			$instance['number'] = intval($new_instance['number']);
			return $instance;
		} // end public function update
		
		public function form($instance) {
			
			$tax_args = array(
				'public' => true,
				'publicly_queryable' => true
			);
			$taxonomies = get_taxonomies($tax_args, 'objects');
			//echo '<pre>'; print_r($taxonomies); echo '</pre>';
			
			$instance = wp_parse_args((array) $instance, $this->defaults);
			//echo '<pre>'; print_r($instance['exclude']); echo '</pre>';
			if (!is_array($instance['exclude'])) {
				$instance['exclude'] = array($instance['exclude']);
			}
			$title = sanitize_text_field($instance['title']);
			if (!$title) {
				$title = $taxonomies[$instance['taxonomy']]->labels->name;
			}
			?>
				<p>
					<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
					<input class="widefat" id="<?php 
							echo $this->get_field_id('title'); ?>" name="<?php 
							echo $this->get_field_name('title'); ?>" type="text" value="<?php 
							echo esc_attr($title); ?>" />
				</p>
				<p>
					<label for="<?php 
							echo $this->get_field_id('taxonomy'); ?>"><?php 
							_e('Taxonomy')?></label>
					<select class="widefat" id="<?php 
							echo $this->get_field_id('taxonomy'); ?>" name="<?php 
							echo $this->get_field_name('taxonomy'); ?>" onchange="blunt_tax_widget_change_tax('<?php 
							echo $this->get_field_id('taxonomy'); ?>', '<?php 
							echo $this->get_field_id('exclude'); ?>', '<?php 
							echo $this->get_field_id('title'); ?>', '<?php 
							echo $this->get_field_id('hierarchical'); ?>');">
							<?php 
								foreach ($taxonomies as $value => $taxonomy) {
									if ($value == 'post_format') {
										continue;
									}
									?><option value="<?php echo esc_attr($value); ?>"<?php 
											if ($value == $instance['taxonomy']) {
												?> selected="selected"<?php 
											}
											?>><?php 
											echo $taxonomy->labels->name; ?></option><?php 
								}
							?>
					</select>
				</p>
				<?php 
					$term_args = array(
						'taxonomy' => $instance['taxonomy'],
						'hide_empty' => false
					);
					$terms = get_terms($term_args);
				?>
				<p id="<?php 
							echo $this->get_field_id('exclude'); ?>-p"<?php 
							if (!count($terms)) {
								?> style="display: none;"<?php 
							}
							?>>
					<label for="<?php 
							echo $this->get_field_id('exclude'); ?>"><?php 
							_e('Exclude Terms')?></label>
					<select class="widefat" id="<?php 
							echo $this->get_field_id('exclude'); ?>" name="<?php 
							echo $this->get_field_name('exclude'); ?>[]" multiple="multiple">
							<?php 
								if (count($terms)) {
									foreach ($terms as $term) {
										?><option value="<?php echo esc_attr($term->term_id); ?>"<?php 
												if (in_array($term->term_id, $instance['exclude'])) {
													?> selected="selected"<?php 
												}
												?>><?php 
												echo $term->name;
												//echo ' ',$value,' ';
												//print_r($instance['exclude']);
												?></option><?php 
									}
								}
							?>
					</select>
				</p>
				<p>
					<?php 
						$is_hierarchical = false;
						if (isset($taxonomies[$instance['taxonomy']]) && $taxonomies[$instance['taxonomy']]->hierarchical) {
							$is_hierarchical = true;
						} else {
							$instance['hierarchical'] = 0;
						}
					?>
					<span id="<?php echo $this->get_field_id('hierarchical'); ?>-container"<?php 
								if (!$is_hierarchical) {
									?> style="display:none;"<?php 
								}
							?>>
						<input class="checkbox" type="checkbox"<?php 
								checked($instance['hierarchical']); ?> id="<?php 
								echo $this->get_field_id('hierarchical'); ?>" name="<?php 
								echo $this->get_field_name('hierarchical'); ?>" /> <label for="<?php 
								echo $this->get_field_id('hierarchical'); ?>"><?php 
								_e('Hierarchical'); ?></label>
						<br />
					</span>
					<input class="checkbox" type="checkbox"<?php 
							checked($instance['dropdown']); ?> id="<?php 
							echo $this->get_field_id('dropdown'); ?>" name="<?php 
							echo $this->get_field_name('dropdown'); ?>" /> <label for="<?php 
							echo $this->get_field_id('dropdown'); ?>"><?php 
							_e('Display as dropdown'); ?></label>
					<br />
					<input class="checkbox" type="checkbox"<?php 
							checked($instance['count']); ?> id="<?php 
							echo $this->get_field_id('count'); ?>" name="<?php 
							echo $this->get_field_name('count'); ?>" /> <label for="<?php 
							echo $this->get_field_id('count'); ?>"><?php 
							_e('Show post counts'); ?></label>
				</p>
				<p>
					<label for="<?php
							echo $this->get_field_id('number'); ?>"><?php 
							_e('Limit (0 for no limit)'); ?></label>
					<input type="number" class="tiny-text" size="3" value="<?php 
							echo esc_attr($instance['number']); ?>" min="0" step="1" id="<?php
							echo $this->get_field_id('number'); ?>" name="<?php 
							echo $this->get_field_name('number') ?>" />
				</p>
			<?php 
		} // end public function form
		
	} // end class Blunt_Taxonomy_Widget
	
?>