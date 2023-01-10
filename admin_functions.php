<?php
/*
 * 
 * Plugin Name: Demo Plugin
 * Version: 1.0
 *
 */
class cs_admin_mv_cart
{

	public static function init()
	{

		self::init_hooks();
	}
	/**
	 *  Function call wordpress hooks
	 */
	public static function init_hooks()
	{
		self::cs_mv_create_post_type();
		add_action('add_meta_boxes', array('cs_admin_mv_cart', 'meta_boxes'));
		add_action('save_post', array('cs_admin_mv_cart', 'save_meta_values_mv'));

		add_action('admin_enqueue_scripts',  array('cs_admin_mv_cart', 'cs_mv_admin_enqueue_script'));

		add_action('admin_footer-post.php', array('cs_admin_mv_cart', 'cs_mv_custom_status_add_in_mvproduct_page'));
		add_action('admin_footer-post-new.php', array('cs_admin_mv_cart', 'cs_mv_custom_status_add_in_mvproduct_page'));
		add_action('admin_footer-edit.php', array('cs_admin_mv_cart', 'cs_mv_custom_status_add_in_quick_edit'));
		add_action('restrict_manage_posts', array('cs_admin_mv_cart', 'cs_mv_post_fiteraddon'));
		add_filter('parse_query',  array('cs_admin_mv_cart', 'cs_mv_mvproduct_post_custom_filter'));
		add_filter('manage_mvproduct_posts_columns',  array('cs_admin_mv_cart', 'cs_mv_mvproduct_columns'));
		add_action('manage_mvproduct_posts_custom_column', array('cs_admin_mv_cart', 'custom_cs_mv_mvproduct_column'), 10, 2);
	}

	/**
	 *  Function To create post type
	 */
	public static function cs_mv_create_post_type()
	{
		register_post_type(
			'Product',
			array(
				'labels' => array(
					'name' => __('Products'),
					'singular_name' => __('Product')
				),
				'public' => true,
				'has_archive' => true,
				'show_in_menu ' => true,
				'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
			)
		);
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x('Product Category', 'taxonomy general name', 'textdomain'),
			'singular_name'     => _x('Product Category', 'taxonomy singular name', 'textdomain'),
			'search_items'      => __('Search Product Categories', 'textdomain'),
			'all_items'         => __('All Product Categories', 'textdomain'),
			'parent_item'       => __('Parent Product Category', 'textdomain'),
			'parent_item_colon' => __('Parent Product Category:', 'textdomain'),
			'edit_item'         => __('Edit Product Category', 'textdomain'),
			'update_item'       => __('Update Product Category', 'textdomain'),
			'add_new_item'      => __('Add New Product Category', 'textdomain'),
			'new_item_name'     => __('New Product Category Name', 'textdomain'),
			'menu_name'         => __('Product Category', 'textdomain'),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array('slug' => 'mvproduct_category'),
		);

		register_taxonomy('mvproduct_category', array('mvproduct'), $args);


		register_post_status('out_of_stock', array(
			'label'                     => _x('Out of stock', 'mvproduct'),
			'label_count'               => _n_noop('Out of stock <span class="count">(%s)</span>', 'Out of stock <span class="count">(%s)</span>'),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array('mvproduct')
		));
	}


	function cs_mv_custom_status_add_in_quick_edit()
	{
		if ($_REQUEST["post_type"] == "mvproduct" || get_post_type($_REQUEST["post"]) == "mvproduct") {
			echo "<script>
        jQuery(document).ready( function() {
            jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"out_of_stock\">Out of stock</option>' );      
        }); 
		</script>";
		}
	}

	function cs_mv_custom_status_add_in_mvproduct_page()
	{
		if ($_REQUEST["post_type"] == "mvproduct" || get_post_type($_REQUEST["post"]) == "mvproduct") {
			echo "<script>
        jQuery(document).ready( function() {        
            jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"out_of_stock\">Out of stock</option>' );
        });
		</script>";
		}
	}

	function cs_mv_post_fiteraddon()
	{
		global $typenow;
		global $wp_query;
		if ($typenow == 'mvproduct') { // Your custom post type slug
			if (isset($_GET['country'])) {
				$current_plugin = $_GET['country']; // Check if option has been selected
			} ?>
			<select name="country" id="country">
				<option value="all"><?php _e('All Countries', 'mvproduct'); ?></option>

				<?php if (get_option('cs_mv_countrysupport') != "") {
					foreach (get_option('cs_mv_countrysupport') as $countrysupport) {
				?>
						<option value="<?php echo esc_attr($countrysupport); ?>" <?php selected($countrysupport, $current_plugin); ?>><?php echo esc_attr($countrysupport); ?></option>
				<?php
					}
				} ?>
			</select>
		<?php }
	}

	/**
	 * Update query
	 * @since 1.1.0
	 * @return void
	 */
	function cs_mv_mvproduct_post_custom_filter($query)
	{
		global $pagenow;
		// Get the post type
		$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';
		if (is_admin() && $pagenow == 'edit.php' && $post_type == 'mvproduct' && isset($_GET['country']) && $_GET['country'] != 'all') {
			$query->query_vars['meta_key'] = 'cs_mv_country';
			$query->query_vars['meta_value'] = $_GET['country'];
			$query->query_vars['meta_compare'] = '=';
		}
	}

	/**
	 *  Function To add custom post types
	 */
	public static function meta_boxes()
	{
		add_meta_box(
			'cs_mv_product_details',
			__('MV Product Details', 'mv_product_details'),
			array('cs_admin_mv_cart', 'product_metabox_callback'),
			array('mvproduct'),
			'normal',
			'high'
		);
	}
	/** 
	 * Callback function for metabox
	 */
	public static function product_metabox_callback($post)
	{
		global $wpdb;
		$post_id = $post->ID;
		global $post;
		?>
		<div class='mv_metabox_main'>
			<div class='form_main'>
				<div class='form_fields'>
					<label>Product Sku : </label>
					<input type='text' name='sku_mvprod' value='<?php echo get_post_meta($post_id, "sku_mvprod", true); ?>' />
				</div>
				<div class='form_fields price'>
					<label>Product Price : </label>
					<span>$</span><input type='number' step="0.01" name='price_mvprod' value='<?php echo get_post_meta($post_id, "price_mvprod", true); ?>' />
				</div>
				<div class='form_fields price'>
					<label>Product Sale Price : </label>
					<span>$</span><input type='number' name='price_sale_mvprod' step="0.01" value='<?php echo get_post_meta($post_id, "price_sale_mvprod", true); ?>' />
				</div>
				<div class='form_fields'>
					<label>Product Bonusable Volume : </label>
					<input type='number' name='bounsablevolume_mvprod' step="0.01" value='<?php echo get_post_meta($post_id, "bounsablevolume_mvprod", true); ?>' />
				</div>
				<div class='form_fields'>
					<label>Autoship Product : </label>
					<input type='checkbox' name='autoship_product_mvprod' value='1' <?php if (get_post_meta($post_id, "autoship_product_mvprod", true) == true) {
																						echo "checked='checked'";
																					} ?> />
				</div>
				<div class='form_fields'>
					<label>Promoter Pack : </label>
					<input type='checkbox' name='cs_mv_promoterpack' value='yes' <?php if (get_post_meta($post_id, "cs_mv_promoterpack", true) == 'yes') {
																						echo "checked='checked'";
																					} ?> />
				</div>
				<div class='form_fields'>
					<label>Promoter Membership Product : </label>
					<input type='checkbox' name='cs_mv_promotermembershipproduct' value='yes' <?php if (get_post_meta($post_id, "cs_mv_promotermembershipproduct", true) == 'yes') {
																									echo "checked='checked'";
																								} ?> />
				</div>
				<div class='form_fields'>
					<label>Promoter Renewal Product : </label>
					<input type='checkbox' name='cs_mv_promoterproduct' value='yes' <?php if (get_post_meta($post_id, "cs_mv_promoterproduct", true) == 'yes') {
																						echo "checked='checked'";
																					} ?> />
				</div>
				<div class='form_fields'>
					<label>Need Promoter Renewal Product for Checkout? : </label>
					<input type='checkbox' name='cs_mv_need_promoterproduct' value='yes' <?php if (get_post_meta($post_id, "cs_mv_need_promoterproduct", true) == 'yes') {
																								echo "checked='checked'";
																							} ?> />
				</div>
				<?php
				if (get_post_meta($post_id, "autoship_product_mvprod", true) == true) {
				?>
					<div class='form_fields price'>
						<label>Product SmartShip Price:</label>
						<span>$</span><input type='number' step="0.01" name='price_smartship_mvprod' value='<?php echo get_post_meta($post_id, "price_smartship_mvprod", true); ?>' />
					</div>
				<?php } ?>
				<div class='form_fields'>
					<label>SmartShip Product:
						<?php if (get_post_meta($post_id, "smartship_mv_product", true)) {
							echo "<a href='" . get_edit_post_link(get_post_meta($post_id, "smartship_mv_product", true)) . "' target='_blank'>View Product</a>";
						}
						?>
					</label>


					<select name='smartship_mv_product' class="cs_mv_select2">
						<option value=''>Select SmartShip Product</option>
						<?php
						$args = array(
							"post_type" => "mvproduct",

							"posts_per_page" => -1,
							'meta_query' => array(
								array(
									'key' => 'autoship_product_mvprod',
									'value' => true,
									'compare' => "=",
								),
							),
						);
						$getProducts = new wp_query($args);
						if ($getProducts->have_posts()) :
							while ($getProducts->have_posts()) :
								$getProducts->the_post();
								$selected = '';
								if (get_post_meta($post_id, "smartship_mv_product", true) == $post->ID) {
									$selected = 'selected';
								}

								echo "<option value='$post->ID' $selected>" . get_the_title() . " - " . get_post_meta($post->ID, "sku_mvprod", true) . "</option>";

							endwhile;
						endif;
						?>
					</select>
				</div>
				<div class='form_fields mvradioboxes'>
					<label>Country : </label>

					<?php if (get_option('cs_mv_countrysupport') != "") {
						foreach (get_option('cs_mv_countrysupport') as $countrysupport) {
					?>
							<div class="container"> <?php echo $countrysupport; ?>
								<input type='radio' name='cs_mv_country' value='<?php echo $countrysupport;  ?>' <?php if (get_post_meta($post_id, "cs_mv_country", true) == "$countrysupport") {
																														echo "checked";
																													} ?> />
								<span class="checkmark"></span>
							</div>
					<?php
						}
					} ?>

				</div>

				<div class='form_fields'>
					<label>Product Heading Main : </label>
					<input type='text' name="mv_product_heading" value="<?php echo get_post_meta($post_id, "mv_product_heading", true); ?>" />
				</div>

				<div class='form_fields'>
					<label>Product Sub Heading : </label>
					<input type='text' name='mv_product_subheading' value="<?php echo get_post_meta($post_id, "mv_product_subheading", true); ?>" />
				</div>
				<div class='form_fields'>
					<label>Product Page : </label>
					<select name='cs_mv_productpage' class="cs_mv_select2">
						<option value=''>Select Page</option>
						<?php
						$args = array(
							"post_type" => "page",

							"posts_per_page" => -1,
							'post_status' => 'publish'
						);
						$getProducts = new wp_query($args);
						if ($getProducts->have_posts()) :
							while ($getProducts->have_posts()) :
								$getProducts->the_post();
								$selected = '';
								if (get_post_meta($post_id, "cs_mv_productpage", true) == $post->ID) {
									$selected = 'selected';
								}

								echo "<option value='$post->ID' $selected>" . get_the_title() . "</option>";

							endwhile;
						endif;
						?>
					</select>
				</div>

				<div class='form_fields'>
					<label>Sort Order : </label>
					<input type='number' name='mv_product_sort_order' value='<?php echo get_post_meta($post_id, "mv_product_sort_order", true); ?>' />
				</div>
				<div class='form_fields'>
					<label>Promotion Page Sort Order : </label>
					<input type='number' name='mv_promotion_sort_order' value='<?php echo get_post_meta($post_id, "mv_promotion_sort_order", true); ?>' />
				</div>


				<div class='form_fields'>
					<label>Product Carousel text : </label>
					<input type='text' name='cs_product_carousel_text' value="<?php echo get_post_meta($post_id, "cs_product_carousel_text", true); ?>" />
				</div>

				<div class='form_fields'>
					<label>Product Tag Text : </label>
					<input type='text' name='cs_product_tag' value='<?php echo get_post_meta($post_id, "cs_product_tag", true); ?>' />
				</div>

				<div class='form_fields'>
					<label>Product Tag Background Color Hex : </label>
					<input type='text' name='cs_product_tag_color_hex' value='<?php echo get_post_meta($post_id, "cs_product_tag_color_hex", true); ?>' />
				</div>

				<?php
				if (get_post_meta($post_id, "autoship_product_mvprod", true) != true) {
				?>
					<div class='form_fields product_variation'>
						<label>Product Variation : </label>

						<select name='cs_mv_productvariation[]' multiple class="cs_mv_select2">
							<option value=''>Select Product Variation</option>
							<?php
							$args = array(
								"post_type" => "mvproduct",

								"posts_per_page" => -1,
								'meta_query' => array(
									array(
										'key' => 'autoship_product_mvprod',
										'value' => true,
										'compare' => "!=",
									),
								),
							);
							$getProducts = new wp_query($args);
							if ($getProducts->have_posts()) :
								while ($getProducts->have_posts()) :
									$getProducts->the_post();
									$selected = '';
									if (in_array($post->ID, get_post_meta($post_id, "cs_mv_productvariation", true))) {
										$selected = 'selected';
									}

									echo "<option value='$post->ID' $selected>" . get_the_title() . " - " . get_post_meta($post->ID, "sku_mvprod", true) . "</option>";

								endwhile;
							endif;
							?>
						</select>
					</div>
				<?php }
				if (get_post_meta($post_id, "cs_mv_productvariation", true) != '') {
					echo "<div class='form_fields'>
					<label>Default Product Variation : </label>";
					echo "<select name='cs_default_variation_id'>";
					echo "<option value='$post_id'>" . get_the_title($post_id) . "</option>";
					foreach (get_post_meta($post_id, "cs_mv_productvariation", true) as $variation_id) {
						if (get_post_meta($post_id, "cs_default_variation_id", true) == $variation_id) {
							echo "<option value='$variation_id' selected>" . get_the_title($variation_id) . "</option>";
						} else {
							echo "<option value='$variation_id'>" . get_the_title($variation_id) . "</option>";
						}
					}
					echo "</select></div>";

					echo "<div class='form_fields'>
					<label>Product Variation Button text : </label>";
					echo "<input type='text' name='cs_product_variaion_button_text' value='" . get_post_meta($post_id, "cs_product_variaion_button_text", true) . "' />";
					echo "</div>";


					echo "<div class='form_fields'>
					<label>Product Variation Default Image : </label>";
					echo "<input type='text' name='cs_product_variaion_defaultimg' value='" . get_post_meta($post_id, "cs_product_variaion_defaultimg", true) . "' />";
					echo "</div>";
				}


				echo "<div class='form_fields'>
				<label>Product Variation Name : </label>";
				echo "<input type='text' name='cs_product_variaion_name' value='" . get_post_meta($post_id, "cs_product_variaion_name", true) . "' />";
				echo "</div>";

				?>
				<div class='form_fields'>
					<label>Product of the month </label>
					<input type='checkbox' name='cs_product_of_month' value='yes' <?php if (get_post_meta($post_id, "cs_product_of_month", true) == 'yes') {
																						echo "checked='checked'";
																					} ?> />
				</div>


			</div>
		</div>
<?php
	}

	/** 
	 * Function to save meta values of custom post type
	 */
	public static function save_meta_values_mv($post_id)
	{

		if (current_user_can('editor') || current_user_can('administrator')) {
			if (get_post_type($post_id) == 'mvproduct') {

				// pointless if $_POST is empty (this happens on bulk edit)
				if (empty($_POST))
					return $post_id;

				// verify quick edit nonce
				if (isset($_POST['_inline_edit']))
					return $post_id;

				// don't save for autosave
				if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
					return $post_id;


				if (isset($_POST['sku_mvprod']))
					update_post_meta($post_id, 'sku_mvprod', $_POST['sku_mvprod']);

				if (isset($_POST['price_mvprod']))
					update_post_meta($post_id, 'price_mvprod', $_POST['price_mvprod']);

				if (isset($_POST['price_sale_mvprod']))
					update_post_meta($post_id, 'price_sale_mvprod', $_POST['price_sale_mvprod']);

				if (isset($_POST['bounsablevolume_mvprod']))
					update_post_meta($post_id, 'bounsablevolume_mvprod', $_POST['bounsablevolume_mvprod']);


				if (isset($_POST['price_smartship_mvprod']) && $_POST['price_smartship_mvprod'] != '') {
					update_post_meta($post_id, 'price_smartship_mvprod', $_POST['price_smartship_mvprod']);
					update_post_meta($post_id, "autoship_product_mvprod", true);
				}

				if (isset($_POST['autoship_product_mvprod']) && $_POST['autoship_product_mvprod'] != '') {
					update_post_meta($post_id, "autoship_product_mvprod", $_POST['autoship_product_mvprod']);
				} else {
					update_post_meta($post_id, "autoship_product_mvprod", '');
				}
				if (isset($_POST['cs_mv_promoterpack']) && $_POST['cs_mv_promoterpack'] != '') {
					update_post_meta($post_id, "cs_mv_promoterpack", $_POST['cs_mv_promoterpack']);
				} else {
					update_post_meta($post_id, "cs_mv_promoterpack", '');
				}


				if (isset($_POST['cs_mv_promotermembershipproduct']) && $_POST['cs_mv_promotermembershipproduct'] != '') {
					update_post_meta($post_id, "cs_mv_promotermembershipproduct", $_POST['cs_mv_promotermembershipproduct']);
				} else {
					update_post_meta($post_id, "cs_mv_promotermembershipproduct", '');
				}

				if (isset($_POST['cs_mv_promoterproduct']) && $_POST['cs_mv_promoterproduct'] != '') {
					update_post_meta($post_id, "cs_mv_promoterproduct", $_POST['cs_mv_promoterproduct']);
				} else {
					update_post_meta($post_id, "cs_mv_promoterproduct", '');
				}

				if (isset($_POST['cs_mv_need_promoterproduct']) && $_POST['cs_mv_need_promoterproduct'] != '') {
					update_post_meta($post_id, "cs_mv_need_promoterproduct", $_POST['cs_mv_need_promoterproduct']);
				} else {
					update_post_meta($post_id, "cs_mv_need_promoterproduct", '');
				}
				if (isset($_POST['cs_product_of_month']) && $_POST['cs_product_of_month'] != '') {
					update_post_meta($post_id, "cs_product_of_month", $_POST['cs_product_of_month']);
				} else {
					update_post_meta($post_id, "cs_product_of_month", '');
				}


				if (isset($_POST['cs_mv_country']))
					update_post_meta($post_id, 'cs_mv_country', $_POST['cs_mv_country']);

				if (isset($_POST['smartship_mv_product']) && $_POST['smartship_mv_product'] != '') {
					update_post_meta($post_id, "smartship_mv_product", $_POST['smartship_mv_product']);
					if (isset($_POST['cs_mv_need_promoterproduct']) && $_POST['cs_mv_need_promoterproduct'] != '') {
						update_post_meta($_POST['smartship_mv_product'], "cs_mv_need_promoterproduct", $_POST['cs_mv_need_promoterproduct']);
					} else {
						update_post_meta($_POST['smartship_mv_product'], "cs_mv_need_promoterproduct", '');
					}
				} else if (isset($_POST['smartship_mv_product']) && $_POST['smartship_mv_product'] == '') {
					update_post_meta($post_id, "smartship_mv_product", '');
				}


				if (isset($_POST["mv_product_heading"]) && $_POST["mv_product_heading"] != '') {
					update_post_meta($post_id, "mv_product_heading", $_POST["mv_product_heading"]);
				}

				if (isset($_POST["mv_product_subheading"]) && $_POST["mv_product_subheading"] != '') {
					update_post_meta($post_id, "mv_product_subheading", $_POST["mv_product_subheading"]);
				}

				if (isset($_POST['cs_mv_productpage']) && $_POST['cs_mv_productpage'] != '') {
					update_post_meta($post_id, "cs_mv_productpage", $_POST['cs_mv_productpage']);
				}


				if (isset($_POST['cs_mv_productvariation']) && $_POST['cs_mv_productvariation'] != '') {
					update_post_meta($post_id, "cs_mv_productvariation", $_POST['cs_mv_productvariation']);
				}

				if (isset($_POST['mv_product_sort_order'])) {
					update_post_meta($post_id, "mv_product_sort_order", $_POST['mv_product_sort_order']);
				}

				if (isset($_POST['mv_promotion_sort_order'])) {
					update_post_meta($post_id, "mv_promotion_sort_order", $_POST['mv_promotion_sort_order']);
				}

				if (isset($_POST['cs_product_carousel_text'])) {
					update_post_meta($post_id, "cs_product_carousel_text", $_POST['cs_product_carousel_text']);
				}

				if (isset($_POST['cs_product_tag']) && $_POST['cs_product_tag'] != '') {
					update_post_meta($post_id, "cs_product_tag", $_POST['cs_product_tag']);
				} else if (isset($_POST['cs_product_tag']) && $_POST['cs_product_tag'] == '') {
					update_post_meta($post_id, "cs_product_tag", '');
				}

				if (isset($_POST['cs_product_tag_color_hex']) && $_POST['cs_product_tag_color_hex'] != '') {
					update_post_meta($post_id, "cs_product_tag_color_hex", $_POST['cs_product_tag_color_hex']);
				} else if (isset($_POST['cs_product_tag_color_hex']) && $_POST['cs_product_tag_color_hex'] == '') {
					update_post_meta($post_id, "cs_product_tag_color_hex", '');
				}

				if (isset($_POST['cs_default_variation_id']) && $_POST['cs_default_variation_id'] != '') {
					update_post_meta($post_id, "cs_default_variation_id", $_POST['cs_default_variation_id']);
				} else {
					update_post_meta($post_id, "cs_default_variation_id", '');
				}

				if (isset($_POST['cs_product_variaion_button_text']) && $_POST['cs_product_variaion_button_text'] != '') {
					update_post_meta($post_id, "cs_product_variaion_button_text", $_POST['cs_product_variaion_button_text']);
				} else if (empty($_POST["cs_mv_productvariation"])) {
					update_post_meta($post_id, "cs_product_variaion_button_text", '');
				} else {
					update_post_meta($post_id, "cs_product_variaion_button_text", '');
				}

				if (isset($_POST['cs_product_variaion_defaultimg'])) {
					update_post_meta($post_id, "cs_product_variaion_defaultimg", $_POST['cs_product_variaion_defaultimg']);
				}
				if (isset($_POST['cs_product_variaion_name'])) {
					update_post_meta($post_id, "cs_product_variaion_name", $_POST['cs_product_variaion_name']);
				}
			}
		}
	}


	/*
	 * Call back function for menu page
	 */
	public static function omc_mv_ref_tools_plugin_page()
	{

		require_once(MV_CART_PLUGIN_DIR . 'AdminTemplates/cs_mv_admin_plugin.tpl.php');
	}

	/*
	* Call back function for menu page
	*/
	public static function omc_mv_discount_tools_plugin_page()
	{

		require_once(MV_CART_PLUGIN_DIR . 'AdminTemplates/cs_mv_admin_discount_plugin.tpl.php');
	}


	/*
	 * Enqueue admin scripts
	 */

	public static function cs_mv_admin_enqueue_script()
	{
		wp_enqueue_script('fontawesome', "https://kit.fontawesome.com/5c709a39d3.js", false);
		if (isset($_GET['page']) && $_GET['page'] == 'cs-mv-cart') {
			wp_enqueue_style('spin-css', plugins_url(MV_CART_PLUGIN_FOLDER . "/spin.css"), false);
			wp_enqueue_style('cc-mv-admin-bootstrapcss', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/css/bootstrap.css"), false);
		}
		wp_enqueue_style('cc-mv-admin-css', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/css/admin.css"), false);

		wp_enqueue_script('jquery');

		if (isset($_GET['page']) && $_GET['page'] == 'cs-mv-cart') {
			wp_enqueue_script('jcolor-js', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/js/jscolor.js"), false);
			wp_enqueue_script('cc-mv-admin-bootstrapjs', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/js/bootstrap.min.js"), false);
			wp_enqueue_script('spin-js', plugins_url(MV_CART_PLUGIN_FOLDER . "/spin.js"), false);
		}
		global $post_type;
		if ((isset($_GET['post_type']) && $_GET['post_type'] == 'mvproduct') || (isset($post_type) && $post_type == 'mvproduct')) :
			wp_enqueue_style('cc-mv-admin-select2-css', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/css/select2.min.css"), false);
			wp_enqueue_style('cc-mv-admin-post-css', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/css/admin_post.css?rd=12123"), false);

			wp_enqueue_script('cc-mv-select2-js', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/js/select2.js"), false);
		endif;
		wp_enqueue_script('cc-mv-admin-js', plugins_url(MV_CART_PLUGIN_FOLDER . "/admin/js/admin.js"), false);
	}

	function cs_mv_mvproduct_columns($columns)
	{
		$offset = 2;
		$newArray = array_slice($columns, 0, $offset, true) +
			array('sku' => 'Product SKU') +
			array_slice($columns, $offset, NULL, true);
		return $newArray;
	}

	function custom_cs_mv_mvproduct_column($column, $post_id)
	{
		switch ($column) {
			case 'sku':
				echo get_post_meta($post_id, 'sku_mvprod', true);

				break;
		}
	}
}
