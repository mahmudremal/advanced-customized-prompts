<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
use \WP_Query;
class Import {
	use Singleton;
	private $csv_rows = false;
	private $csv_columns = false;
	private $current_term = false;
	private $json_response = false;
	private $csv_columns_args = false;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		/**
		 * Actions
		 */
		// add_action('wp_ajax_nopriv_sospopsproject/ajax/import/bulks', [$this, 'import_bulks'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/import/bulks', [$this, 'import_bulks'], 10, 0);

		add_filter('sos/import/cats/row', [$this, 'sos_import_cats_row'], 10, 3);
		add_filter('sos/import/areas/row', [$this, 'sos_import_areas_row'], 10, 3);
		add_filter('sos/import/services/row', [$this, 'sos_import_services_row'], 10, 3);
	}
	/**
	 * Import Popup customized data for criteria
	 * Two types of import for Popup.
	 * 1. Replace with previous data.
	 * 2. Append after previous data.
	 */
	public function pops_import() {
		$options = [];$keyI = 0;
		foreach($this->csv_columns as $key => $row) {
			if(in_array($key, ['_sos_custom_services'])) {
				$this->pops_import_meta_to_services_under_category($key, $row);
			} else {
				$options[] = $this->pops_option_row([
					'fieldID'	=> $keyI,
					'heading'	=> $this->csv_columns_args[$key],
					'options'	=> $row
				]);
				$keyI++;
			}
		}
		if(count($options) >= 1) {
			$this->pops_import_to_services_under_category($options);
			$this->json_response['pops_data'] = $options;
		}
	}
	/**
	 * Import Category data
	 * Import Areas data
	 * Import single services
	 */
	public function cats_areas_import($type) {
		$options = [];
		/**
		 * Remove all columns those are empty titled.
		 */
		$this->sortout_empty_columns_fileds();
		/**
		 * Convert columns to rows.
		 */
		$this->columns_to_rows();
		/**
		 * Proceed with CSV Rows.
		 */
		foreach($this->csv_rows as $key => $row) {
			$this->json_response['imported_data'] = $this->json_response['imported_data']??[];
			$this->json_response['imported_data'][] = apply_filters('sos/import/' . $type . '/row', false, $key, $row);
		}
		if(count($this->csv_columns) >= 1) {
			$this->json_response['csv_rows'] = $this->csv_rows;
			$this->json_response['csv_columns'] = $this->csv_columns;
		}
	}
	public function services_import() {

	}
	public function proceed_import($path) {
		$this->csv_rows = [];
		$this->csv_columns = [];
		$this->json_response = [];
		$this->csv_columns_args = [];
		$row_order = 1;$first_row = false;
		if(($handle = fopen($path, "r")) !== FALSE) {
			while(($csv_row = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$row_order++;
				if($first_row) {
					/**
					 * Escape heading row and proceed from 2nd row.
					 */
					$this->sort_single_row($first_row, $csv_row);
				} else {
					/**
					 * Define the first row as CSV heading How.
					 */
					$first_row = ($first_row)?$first_row:$csv_row;
				}
			}
			fclose($handle);

			switch ($_POST['import_type']) {
				case 'pops':
					$this->pops_import();
					break;
				case 'cats':
					$this->cats_areas_import('cats');
					break;
				case 'areas':
					$this->cats_areas_import('areas');
					break;
				case 'services':
					$this->cats_areas_import('services');
					break;
				default:
					# code...
					break;
			}
		}
	}
	/**
	 * Known fields for Track Columns
	 */
	public function known_fields() {
		$args = [
			// 
		];
		return $args;
	}
	/**
	 * Converts CSV columns to CSV rows.
	 */
	public function columns_to_rows() {
		$column_values = array_values($this->csv_columns);
		for ($i=0; $i < count($column_values[0]); $i++) {
			$column_row = [];
			foreach ($this->csv_columns as $key => $row) {
				$column_row[$key] = $row[$i];
			}
			$this->csv_rows[] = $column_row;
		}
	}
	/**
	 * Ajax Request handlers
	 */
	public function import_bulks() {
		$this->json_response = ['message' => []];
		$csv = $_FILES['csv']??($_FILES['sos_import']??false);
		if($csv) {
			$path = $csv['tmp_name'];
			$this->proceed_import($path);
		} else {
			$this->add_response_message(__('Failed to get CSV file.', 'domain'), false);
		}
		$this->json_response = (object) wp_parse_args($this->json_response, ['success' => [], 'message' => [], 'hooks' => ['sos_imports_response']]);
		$this->json_response->columns_args = $this->csv_columns_args;
		$this->json_response->columns = $this->csv_columns;
		if($this->is_success()) {
			wp_send_json_success($this->json_response);
		} else {
			wp_send_json_error($this->json_response);
		}
	}
	/**
	 * Sort Single Row acording to column.
	 */
	public function sort_single_row($first_row, $csv_row) {
		for($cell = 0; $cell < count($csv_row); $cell++) {
			// echo $first_row[$cell] . ': ' . $csv_row[$cell] . "\n";
			$cell_striped = str_replace([' '], [''], strtolower(trim($first_row[$cell])));
			switch($cell_striped) {
				case 'subcategory':
					if(!empty(trim($csv_row[$cell]))) {
						$term_name = $csv_row[$cell];$term_parent = false;
						$term_explode = explode(' <- ', $csv_row[$cell]);$term_img = false;
						if(isset($term_explode[1]) && !empty(trim($term_explode[1]))) {
							$term_name = $term_explode[0];$term_parent = $term_explode[1];
							$term_explode = explode(' <!> ', $term_name);
							if(isset($term_explode[1]) && !empty(trim($term_explode[1]))) {
								$term_name = $term_explode[0];
								$term_img = $term_explode[1];
							}
						}
						
						$this->current_term = $term = get_term_by('name', $term_name, 'services');
						if($term && !is_wp_error($term)) {
							$this->json_response['term_hooked'] = $this->json_response['term_hooked']??[];
							$this->json_response['term_hooked'][] = $term;
						} else {
							$this->add_response_message(sprintf(__('Term (%s) not found!', 'domain'), $term_name), false);
						}
					}
					break;
				default:
					$this->csv_columns_args[$cell_striped] = $first_row[$cell];
					$this->csv_columns[$cell_striped] = $this->csv_columns[$cell_striped]??[];
					$this->csv_columns[$cell_striped][] = $csv_row[$cell];
					break;
			}
		}
	}

	/**
	 * Get pops option row
	 */
	public function pops_option_row($args) {
		$args = (object) wp_parse_args($args, [
			'fieldID'		=> 0,
			'heading'		=> '',
			'options'		=> []
		]);
		$options = [];
		foreach($args->options as $i => $option) {
			$has_amount = explode(' - $', $option);$amount = false;
			if($has_amount && count($has_amount) >= 1 && is_numeric(end($has_amount))) {
				$amount = (float) $has_amount;
			}
			$has_image = explode(' - $', $option);$image = false;
			if($has_image && count($has_image) >= 1 && is_numeric(end($has_image))) {
				$image = (float) $has_image;
			}
			$options[] = [
				'label'	=> $option,
				'next'	=> false,
				'cost'	=> ($amount)?$amount:0,
				'image'	=> ($image)?$image:'',
				'thumb'	=> ($image)?$image:'',
			];
		}
		$option_row = [
			'fieldID'		=> $args->fieldID,
			'type'			=> 'select',
			'stepicon'		=> '',
			'steptitle'		=> '',
			'headerbg'		=> '',
			'heading'		=> $args->heading,
			'subtitle'		=> '',
			'name'			=> '',
			'label'			=> '',
			'description'	=> '',
			'options'		=> $options,
		];
		return $option_row;
	}
	/**
	 * Check this session is success or not
	 */
	public function is_success() {
		$this->json_response = (object) $this->json_response;
		if($this->json_response->success && count($this->json_response->success) >= 1) {
			$falsed = 0;
			foreach($this->json_response->success as $is_it) {
				if($is_it) {$falsed++;}
			}
			return count($this->json_response->success) == $falsed;
		}
		return false;
	}
	/**
	 * Check whether it is meta or not.
	 */
	public function is_meta($key, $row) {
		$slice = explode(':', trim($key));
		return (strtolower($slice[0]) == 'meta');
	}
	/**
	 * Check if field is blank and check data will be escapable.
	 */
	public function is_escapable_blank($key, $row) {
		// print_r([$key, $row]);return true;
		if(empty(trim($row))) {
			$allowed_blanks = [
				// 'texonomy_featured_image', '_faq_template'
			];
			if(!in_array($row, $allowed_blanks)) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Check if it is a remote URL or not
	 */
	public function isRemoteUrl($url) {
		if (wp_http_validate_url($url)) {
		  $homeUrl = get_home_url();
		  $parsedUrl = parse_url($url);
		  $parsedHomeUrl = parse_url($homeUrl);
	  
		  // Check if the host of the URL is different from the host of the home URL
		  if ($parsedUrl['host'] !== $parsedHomeUrl['host']) {
			return true; // Remote URL
		  }
		}
	  
		return false; // Not a remote URL
	}
	/**
	 * Check if it is a file URL or not
	 */
	public function isFileUrl($url) {
		$parsedUrl = parse_url($url);
		/**
		 * No scheme condier it is not an URL
		 */
		if (!isset($parsedUrl['scheme']) || empty($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https', 'ssl'])) {return false;}
		$path = $parsedUrl['path']??'';
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return !empty($extension);
	}
	/**
	 * Get the attachment ID for a given file url
	 *
	 * @link   http://wordpress.stackexchange.com/a/7094
	 * @param  string $url
	 * @return boolean|integer
	 */
	public function get_attachment_id_from_url($url) {
		$dir = wp_upload_dir();
		if (false === strpos($url, $dir['baseurl'] . '/')) {
		return false;
		}
		$query = [
			'post_type'  => 'attachment',
			'fields'     => 'ids',
			'meta_query' => [
				[
					'key'     => '_wp_attached_file',
					'value'   => basename($url),
					'compare' => 'LIKE'
				]
			]
		];
		$ids = get_posts($query);

		if (!empty($ids)) {
			foreach ($ids as $id) {
				if(wp_get_attachment_url($id) === $url) {
					return $id;
				}
			}
		}

		return false;
	}
	/**
	 * Import generated Popup custom fields data to all services under a category.
	 */
	public function pops_import_to_services_under_category($options) {
		if(isset($this->current_term->term_id) || isset($this->csv_columns_args['subcategory'])) {
			$args = [
				// 'category_name'	=> $this->csv_columns_args['subcategory'],
				'cat__in'		=> [
					$this->current_term->term_id??$this->csv_columns_args['subcategory']
				],
				'post_type'			=> 'service',
    			'posts_per_page'	=> -1
			];
			$posts = new WP_Query($args);
			if ($posts->have_posts()) {
				while ($posts->have_posts()) {
					$posts->the_post();
					/**
					 * Replace previous data with imported data.
					 */
					update_post_meta(get_the_ID(), '_sos_custom_popup', $options);
				}
			} else {
				// No posts found
				$this->add_response_message(sprintf(
					__('Services not found or something suspecious happens under the Term (%s)! We failed to implement some rows on any of those services.', 'domain'), 
					$this->current_term->term_id??$this->csv_columns_args['subcategory']
				), false);
			}
			// Restore original post data
			wp_reset_postdata();
		}
	}
	/**
	 * Import generated extra custom services data to all services under a category.
	 */
	public function pops_import_meta_to_services_under_category($key, $row) {
		if(isset($this->current_term->term_id) || isset($this->csv_columns_args['subcategory'])) {
			$args = [
				'cat__in'		=> [
					$this->current_term->term_id??$this->csv_columns_args['subcategory']
				],
				'post_type'			=> 'service',
    			'posts_per_page'	=> -1
			];
			$posts = new WP_Query($args);
			if ($posts->have_posts()) {
				while ($posts->have_posts()) {
					$posts->the_post();
					/**
					 * Replace previous data with imported data.
					 */
					update_post_meta(get_the_ID(), $key, $row);
				}
			} else {
				// No posts found
				$this->add_response_message(sprintf(
					__('Services not found or something suspecious happens under the Term (%s)! We failed to implement some rows on any of those services.', 'domain'), 
					$this->current_term->term_id??$this->csv_columns_args['subcategory']
				), false);
			}
			// Restore original post data
			wp_reset_postdata();
		}
	}
	/**
	 * Sortout empty columns fields those fields titles are empty;
	 */
	public function sortout_empty_columns_fileds() {
		while (isset($this->csv_columns[''])) {unset($this->csv_columns['']);}
	}

	/**
	 * Import cats single meta data.
	 */
	public function sos_import_cats_row($response, $order, $row) {
		$metas = [];$fields = [];
		foreach ($row as $key => $value) {
			if($this->is_escapable_blank($key, $value)) {continue;}
			switch ((bool) $this->is_meta($key, $value)) {
				case true:
					$metas[$key] = $value;
					break;
				default:
					$fields[$key] = $value;
					break;
			}
		}

		$term_name = isset($fields['subcategory'])?$fields['subcategory']:(
			isset($fields['category'])?$fields['category']:false
		);
		$parent_term_name = isset($fields['parentcategory'])?$fields['parentcategory']:(
			(isset($fields['subcategory']) && isset($fields['category']))?$fields['category']:false
		);
		if($term_name && !empty($term_name)) {
			$args = ['description' => $fields['categorydescription']??''];
			if($parent_term_name && !empty($parent_term_name)) {
				$parent_term_id = get_term_by('name', $parent_term_name, 'services');
				if($parent_term_id && !is_wp_error($parent_term_id)) {
					$parent_term_id = (array) $parent_term_id;
					$args['parent'] = $parent_term_id['term_id'];
				} else {
					$inserted_id = wp_insert_term($parent_term_name, 'services', [
						'description' => $fields['parentcategorydescription']??''
					]);
					if($inserted_id && !is_wp_error($inserted_id)) {
						$inserted_id = (array) $inserted_id;
						$args['parent'] = $inserted_id['term_id'];
						$this->add_response_message(sprintf(
							__('New Category Term created (%s) with the ID (%s) as a parent term.', 'domain'),
							$parent_term_name, $inserted_id['term_id']
						), true);
						$this->insert_texonomy_metas($inserted_id['term_id'], $metas, true);
					}
				}
			}
			$inserted_id = wp_insert_term($term_name, 'services', $args);
			if($inserted_id && !is_wp_error($inserted_id)) {
				if(isset($args['parent'])) {
					$this->add_response_message(sprintf(
					__('New Category Term created (%s) with the ID (%s) under the term (%s).', 'domain'),
					$term_name, $inserted_id['term_id'], $args['parent']
				), true);
				} else {
					$this->add_response_message(sprintf(
						__('New Category Term created (%s) with the ID (%s)', 'domain'),
						$term_name, $inserted_id['term_id']
					), true);
				}
				$this->insert_texonomy_metas($inserted_id['term_id'], $metas, false);
				return true;
			} else {
				$this->add_response_message(sprintf(
					__('Error: %s All of the fields of the following rows (%s) are skipped.', 'domain'), $inserted_id->get_error_message(), $order
				), false);
			}
		}
		
		return $response;
	}
	/**
	 * Import Areas row.
	 */
	public function sos_import_areas_row($response, $order, $row) {
		$metas = [];$fields = [];
		foreach ($row as $key => $value) {
			if($this->is_escapable_blank($key, $value)) {continue;}
			switch ((bool) $this->is_meta($key, $value)) {
				case true:
					$metas[$key] = $value;
					break;
				default:
					$fields[$key] = $value;
					break;
			}
		}

		$term_name = isset($fields['subarea'])?$fields['subarea']:(
			isset($fields['area'])?$fields['area']:false
		);
		$parent_term_name = isset($fields['parentarea'])?$fields['parentarea']:(
			(isset($fields['subarea']) && isset($fields['area']))?$fields['area']:false
		);
		if($term_name && !empty($term_name)) {
			$args = ['description' => $fields['areadescription']??''];
			if($parent_term_name && !empty($parent_term_name)) {
				$parent_term_id = get_term_by('name', $parent_term_name, 'area');
				if($parent_term_id && !is_wp_error($parent_term_id)) {
					$parent_term_id = (array) $parent_term_id;
					$args['parent'] = $parent_term_id['term_id'];
				} else {
					$inserted_id = wp_insert_term($parent_term_name, 'area', [
						'description' => $fields['parentareadescription']??''
					]);
					if($inserted_id && !is_wp_error($inserted_id)) {
						$inserted_id = (array) $inserted_id;
						$args['parent'] = $inserted_id['term_id'];
						$this->add_response_message(sprintf(
							__('New Area Term created (%s) with the ID (%s) and is a parent term.', 'domain'),
							$parent_term_name, $inserted_id['term_id']
						), true);
						$this->insert_texonomy_metas($inserted_id['term_id'], $metas, true);
					}
				}
			}
			$inserted_id = wp_insert_term($term_name, 'area', $args);
			if($inserted_id && !is_wp_error($inserted_id)) {
				$this->add_response_message(sprintf(
					__('New Area Term created (%s) with the ID (%s)', 'domain'),
					$term_name, $inserted_id['term_id']
				), true);
				$this->insert_texonomy_metas($inserted_id['term_id'], $metas, false);
				return true;
			} else {
				$this->add_response_message(sprintf(
					__('Error: %s All of the fields of the following rows (%s) (%s) are skipped.', 'domain'), $inserted_id->get_error_message(), $term_name, $order
				), false);
			}
		}
		
		return $response;
	}
	/**
	 * Import services row.
	 */
	public function sos_import_services_row($response, $order, $row) {
		$metas = [];$fields = [];
		foreach ($row as $key => $value) {
			if($this->is_escapable_blank($key, $value)) {continue;}
			switch ((bool) $this->is_meta($key, $value)) {
				case true:
					$metas[$key] = $value;
					break;
				default:
					$fields[$key] = $value;
					break;
			}
		}

		/**
		 * Term & parent Term fields.
		 */
		$term_ids = [];
		$term_names = isset($fields['subcategory'])?$fields['subcategory']:(
			isset($fields['category'])?$fields['category']:false
		);
		if($term_names && !empty($term_names)) {
			$term_names = explode(',', $term_names);
			foreach($term_names as $term_Index => $term_name) {
				$term_name = trim($term_name);
				if(!empty($term_name)) {
					$child_term = get_term_by('name', $term_name, 'services');
					if($child_term && !is_wp_error($child_term)) {
						$child_term = (array) $child_term;
						$term_ids[] = $child_term['term_id'];
					} else {
						$this->add_response_message(sprintf(
							__('Error: %s Term (%s) not found on row no. (%s)', 'domain'), $inserted_id->get_error_message(), $term_name, $order
						), false);
					}
				}
			}
		}

		/**
		 * Other fields to proceed services
		 */
		/**
		 * Insert all meta data on the following services.
		 */
		$_thumbnail_id = $fields['featuredimage']??'';
		if($this->isFileUrl($_thumbnail_id)) {
			if(
				true
				// $this->isRemoteUrl($_thumbnail_id)
			) {
				$_thumbnail_id = $this->insert_attachment_from_url($_thumbnail_id, 0, basename($_thumbnail_id));
				if($_thumbnail_id && !is_wp_error($_thumbnail_id) && is_int($_thumbnail_id)) {
					// Yeah this is a proper thumbnail ID I hope.
					$_thumbnail_id = (int) $_thumbnail_id;
				} else {
					$_thumbnail_id = false;
				}
			}
		}
		$args = [
			'post_title'	=> wp_strip_all_tags($fields['servicetitle']??''),
			'post_status'	=> strtolower($fields['poststatus']??'publish'),
			'post_content'	=> $fields['postcontent']??'',
			'post_excerpt'	=> $fields['postexcerpt']??'',
			'post_author'	=> get_current_user_id(),
			'_thumbnail_id'	=> $_thumbnail_id,
			'post_type'		=> 'service',
			'post_category'	=> $term_ids,
		];
		$post_id = wp_insert_post($args, true); // Service ID.
		if(count($metas) >= 1) {$this->insert_service_metas($post_id, $metas, false);}
		

		
		return $response;
	}
	/**
	 * Insert meta data on the following texonomy
	 * for both of subcategory and parent category
	 */
	public function insert_texonomy_metas($term_id, $metas, $is_parent) {
		foreach($metas as $key => $value) {
			if(empty(trim($value))) {
				$this->add_response_message(sprintf(
					__('Empty value for the meta key (%s) on the term (%s) skipped.', 'domain'),
					$key, $term_id
				), false);
				continue;
			}
			$meta_items = false;
			if($is_parent) {
				/**
				 * Only parent meta
				 */
				if(strtolower(substr(trim($key), 0, 12)) == 'meta:parent:') {
					$meta_items = [
						'key'		=> substr(trim($key), 12),
						'value'		=> $value
					];
				}
			} else {
				/**
				 * Meta without parent meta
				 */
				if(
					strtolower(substr(trim($key), 0, 5)) == 'meta:'
											&&
					strtolower(substr(trim($key), 0, 12)) != 'meta:parent:'
				) {
					$meta_items = [
						'key'		=> substr(trim($key), 5),
						'value'		=> $value
					];
				}
			}
			if($meta_items) {
				if($this->isFileUrl($meta_items['value'])) {
					if(
						true
						// $this->isRemoteUrl($meta_items['value'])
					) {
						$meta_items['value'] = $this->insert_attachment_from_url($meta_items['value'], 0, basename($meta_items['value']));
					}
				}
				$is_updated = update_term_meta($term_id, $meta_items['key'], $meta_items['value']);
				if($is_updated && !is_wp_error($is_updated)) {
					$this->add_response_message(sprintf(
						__('Successfully Imported the meta (%s) with value (%s) on the following term (%d).', 'domain'),
						$meta_items['key'], $meta_items['value'], $term_id
					), true);
				} else {
					$this->add_response_message(sprintf(
						__('Failed to handle meta field. As a result, this meta (%s) skipped with the value (%s) on the following term ID (%s). Error: %s', 'domain'),
						$meta_items['key'], $meta_items['value'], $term_id, $is_updated->get_error_message()
					), false);
				}
			}
		}
	}
	/**
	 * All meta data on a single services
	 */
	public function insert_service_metas($post_id, $metas, $is_parent) {
		foreach($metas as $key => $value) {
			if(empty(trim($value))) {
				$this->add_response_message(sprintf(
					__('Empty value for the meta key (%s) on the Service (%s) skipped.', 'domain'),
					$key, $post_id
				), false);
				continue;
			}
			$meta_items = false;
			/**
			 * Meta without parent meta
			 */
			if(
				strtolower(substr(trim($key), 0, 5)) == 'meta:'
										&&
				strtolower(substr(trim($key), 0, 12)) != 'meta:parent:'
			) {
				$meta_items = [
					'key'		=> substr(trim($key), 5),
					'value'		=> $value
				];
			}
			if($meta_items) {
				if($this->isFileUrl($meta_items['value'])) {
					if(
						true
						// $this->isRemoteUrl($meta_items['value'])
					) {
						$meta_items['value'] = $this->insert_attachment_from_url($meta_items['value'], 0, basename($meta_items['value']));
					}
				}
				$is_updated = update_post_meta($post_id, $meta_items['key'], sanitize_textarea_field($meta_items['value']));
				if($is_updated && !is_wp_error($is_updated)) {
					$this->add_response_message(sprintf(
						__('Successfully Imported the meta (%s) with value (%s) on the following Service (%d).', 'domain'),
						$meta_items['key'], $meta_items['value'], $post_id
					), true);
				} else {
					$this->add_response_message(sprintf(
						__('Failed to handle meta field. As a result, the meta (%s) skipped with the value (%s) on the following Service ID (%s).', 'domain'),
						$this->bold_string($meta_items['key']), $this->bold_string($meta_items['value']), $this->bold_string($post_id)
					), false);
				}
			}
		}
	}
	/**
	 * Add response message.
	 */
	public function add_response_message($message, $status) {
		$this->json_response['success'][] = (bool) $status;
		$this->json_response['message'][] = $message;
	}
	/**
	 * Insert attachment from a remote file URL in WordPress
	 *
	 * @param string $file_url The URL of the remote file
	 * @param int $post_id The post ID to attach the file to
	 * @param string $title Optional. The title for the attachment
	 * @return int|WP_Error The attachment ID on success, or WP_Error object on failure
	 */
	public function insert_attachment_from_url($file_url, $post_id, $title = '') {
		$args = [
			'meta_key'			=> '_remote_file_url',
			'post_type'			=> 'attachment',
			'meta_value'		=> $file_url,
			'numberposts'		=> 1
		];
		$posts = get_posts($args);
		if($posts && !is_wp_error($posts) && count($posts) >= 1 && isset($posts[0])) {
			$this->add_response_message(sprintf(
				__('The URL (%s) matched with the following attachment ID (%s) replaced and returned attachments ID.', 'domain'), $file_url, $posts[0]->ID
			), true);
			return $posts[0]->ID;
		}
		
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		$upload_dir = wp_upload_dir();

		// Download the file
		$temp_file = download_url($file_url);
		$upload_path = $upload_dir['path'] . '/' . basename($file_url);
		if($temp_file && !is_wp_error($temp_file)) {
			copy($temp_file, $upload_path);
			// Set up the array of attachment data
			$attachment = [
				'post_mime_type'	=> wp_check_filetype(basename($file_url), null)['type'],
				'post_status'		=> 'inherit',
				'post_title'		=> $title,
				'post_content'		=> ''
			];
			// Insert the attachment
			$attachment_id = wp_insert_attachment($attachment, $upload_path, $post_id);
			if($attachment_id && !is_wp_error($attachment_id)) {
				// Generate metadata for the attachment
				$attach_data = wp_generate_attachment_metadata($attachment_id, $upload_path);
				wp_update_attachment_metadata($attachment_id, $attach_data);
				// add an post meta to avoid duplicating
				update_post_meta($attachment_id, '_remote_file_url', $file_url);
				// Remove the temporary file
				wp_delete_file($temp_file);
				// Add an success message
				$this->add_response_message(sprintf(
					__('Attachments moved to local storage and returned with the ID (%s).', 'domain'), $attachment_id
				), true);
				return $attachment_id;
			} else {
				$this->add_response_message(sprintf(
					__('Failed to create an attachment from the following file (%s). Error: %s', 'domain'),
					$file_url, $attachment_id->get_error_message()
				), false);
			}
		} else {
			$this->add_response_message(sprintf(
				__('Error while downloading the file (%s) from remote server to local storage Please find the error below. Error: %s', 'domain'),
				$file_url, $temp_file->get_error_message()
			), false);
		}
		
		return $file_url;
	}

	/**
	 * Bold an string with adding Tag before & after a sting
	 */
	public function bold_string($string) {
		return '<strong>' . $string . '</strong>';
	}
}