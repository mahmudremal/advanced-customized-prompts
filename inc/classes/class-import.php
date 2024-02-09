<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
use \WP_Query;use \WP_Error;
class Import {
	use Singleton;
	private $csv_rows = false;
	private $csv_terms = false;
	private $csv_columns = false;
	private $current_term = false;
	private $json_response = false;
	private $csv_attributes = false;
	private $csv_columns_text = false;
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
		add_filter('sos/import/mixed/row', [$this, 'sos_import_mixed_row'], 10, 3);
		add_filter('sos/import/services/row', [$this, 'sos_import_services_row'], 10, 3);

		add_action('wp_ajax_sospopsproject/ajax/import/clean', [$this, 'delete_all_services'], 10, 0);
		add_action('sospopsproject/import/stream/fetch/rows', [$this, 'import_stream_fetch_rows'], 10, 1);
		add_action('sospopsproject/clean/stream/fetch/rows', [$this, 'clean_stream_fetch_rows'], 10, 1);

		add_filter('pre_insert_term', [$this, 'pre_insert_term'], 10, 3);
		
	}
	/**
	 * Import Popup customized data for criteria
	 * Two types of import for Popup.
	 * 1. Replace with previous data.
	 * 2. Append after previous data.
	 */
	public function pops_import() {
		$options = [];$keyI = 0;
		foreach ($this->csv_columns as $key => $row) {
			if (in_array($key, ['_sos_custom_services'])) {
				$this->pops_import_meta_to_services_under_category($key, $row);
			} else {
				$options[] = $this->pops_option_row([
					'fieldID'	=> $keyI,
					'heading'	=> $this->csv_columns_text[$key],
					'options'	=> $row
				]);
				$keyI++;
			}
		}
		if (count($options) >= 1) {
			$this->pops_import_to_services_under_category($options);
			$this->json_response['pops_data'] = $options;
		}
	}

	/**
	 * Import mixed all in one csv
	 */
	public function mixed_allinone_import($type) {
		$this->cats_areas_import($type);
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
		 * Rename any column for text to key.
		 */
		// $this->redefine_column_keys($type);
		/**
		 * Convert columns to rows.
		 */
		// $this->columns_to_rows();

		/**
		 * Register for frontend response.
		 */
		if (count($this->csv_columns) >= 1) {
			$this->json_response['csv_rows'] = $this->csv_rows;
			$this->json_response['csv_columns'] = $this->csv_columns;
		}
		
		/**
		 * Register for event stream.
		 */
		$args = [
			'done'			=> 0,
			'rest'			=> 0,
			'type'			=> $type,
			'total'			=> count($this->csv_rows),
			'hook'			=> 'sospopsproject/import/stream/fetch/rows'
		];
		if (count($this->csv_rows) >= 1) {$args['csv_rows'] = $this->csv_rows;}
		if (count($this->csv_columns) >= 1) {$args['csv_columns'] = $this->csv_columns;}
		// if (count($this->current_term) >= 1) {$args['current_term'] = $this->current_term;}
		// if (count($this->json_response) >= 1) {$args['json_response'] = $this->json_response;}
		if (count($this->csv_attributes) >= 1) {$args['csv_attributes'] = $this->csv_attributes;}
		if (count($this->csv_columns_text) >= 1) {$args['csv_columns_text'] = $this->csv_columns_text;}

		$stream_register = apply_filters('sospopsproject/event/stream/register', $args);
		
		/**
		 * Proceed with event stream.
		 */
		if ($stream_register === true) {
			$this->json_response['hooks'][] = 'event_registered';
		}
	}
	public function services_import() {
	}
	
	public function proceed_import($path) {
		$this->csv_rows = [];
		$this->csv_columns = [];
		$this->json_response = [];
		$this->csv_attributes = [];
		$this->csv_columns_text = [];
		$row_order = 1;$first_row = false;
		$this->csv_terms = (object) ['area' => [], 'services' => []];

		
		// $xml = simplexml_load_file($path);
		// $xml_rows = $xml->Worksheet->Table->Row;
		// $csv_rows = [];
		// foreach ($xml_rows as $xml_row) {
		// 	$cells = $xml_row->Cell;$csv_cells = [];
		// 	foreach ($cells as $cell) {
		// 		$csv_cells[] = (string) $cell->Data;
		// 	}
		// 	$csv_rows[] = $csv_cells;
		// }
			
		if (($handle = fopen($path, "r")) !== FALSE) {
			$csv_rows = [];

			// while(($csv_row = fgetcsv($handle, 1000, ",")) !== FALSE) {
			// 	$csv_rows[] = $csv_row;
			// }
			while(($csv_row = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$row_order++;
				if ($first_row) {
					/**
					 * Escape heading row and proceed from 2nd row.
					 */
					$this->csv_rows[] = $this->sort_single_row($first_row, $csv_row);
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
				case 'mixed':
					$this->mixed_allinone_import('mixed');
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
		$this->json_response = ['message' => [], 'hooks' => []];
		$csv = $_FILES['csv']??($_FILES['sos_import']??false);
		if ($csv) {
			$path = $csv['tmp_name'];
			$this->proceed_import($path);
		} else {
			$this->add_response_message(__('Failed to get CSV file.', 'domain'), false);
		}
		$this->json_response = (object) wp_parse_args(
			$this->json_response,
			[
				'success' => [], 'message' => [], 'hooks' => ['sos_imports_response']
			]
		);
		$this->json_response->csv_columns_text = $this->csv_columns_text;
		$this->json_response->attributes = $this->csv_attributes;
		$this->json_response->csv_columns = $this->csv_columns;
		if ($this->is_success()) {
			wp_send_json_success($this->json_response);
		} else {
			wp_send_json_error($this->json_response);
		}
	}
	
	/**
	 * Sort Single Row acording to column.
	 */
	public function sort_single_row($first_row, $csv_row) {

		$row_args = [];$to_change = $this->column_keys_to_change();
		foreach($first_row as $index => $key) {
			$key_striped = $this->trim_text_to_key($first_row[$index]);
			if (isset($to_change[$key_striped])) {$key_striped = $to_change[$key_striped];}
			if (isset($csv_row[$index])) {
				$row_args[$key_striped] = $csv_row[$index];
			}
		}
		return $row_args;

		
		// for($cell = 0; $cell < count($csv_row); $cell++) {
		// 	// echo $first_row[$cell] . ': ' . $csv_row[$cell] . "\n";
		// 	$cell_striped = $this->trim_text_to_key($first_row[$cell]);
		// 	switch($cell_striped) {
		// 		case 'subcategory':
		// 			// if (!empty(trim($csv_row[$cell]))) {
		// 			// 	$term_name = $csv_row[$cell];$term_parent = false;
		// 			// 	$term_explode = explode(' <- ', $csv_row[$cell]);$term_img = false;
		// 			// 	if (isset($term_explode[1]) && !empty(trim($term_explode[1]))) {
		// 			// 		$term_name = $term_explode[0];$term_parent = $term_explode[1];
		// 			// 		$term_explode = explode(' <!> ', $term_name);
		// 			// 		if (isset($term_explode[1]) && !empty(trim($term_explode[1]))) {
		// 			// 			$term_name = $term_explode[0];
		// 			// 			$term_img = $term_explode[1];
		// 			// 		}
		// 			// 	}
						
		// 			// 	$this->csv_terms->services[] = ['name' => $term_name, 'description' => ''];
		// 			// }
		// 			// break;
		// 		default:
		// 			$this->csv_columns_text[$cell_striped] = $first_row[$cell];
		// 			$this->csv_columns[$cell_striped] = $this->csv_columns[$cell_striped]??[];
		// 			$this->csv_columns[$cell_striped][] = $csv_row[$cell];
		// 			break;
		// 	}
		// }
	}
	
	/**
	 * Trim text to row.
	 */
	public function trim_text_to_key($text) {
		return str_replace([' '], [''], strtolower(trim($text)));
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
		foreach ($args->options as $i => $option) {
			$has_amount = explode(' - $', $option);$amount = false;
			if ($has_amount && count($has_amount) >= 1 && is_numeric(end($has_amount))) {
				$amount = (float) $has_amount;
			}
			$has_image = explode(' - $', $option);$image = false;
			if ($has_image && count($has_image) >= 1 && is_numeric(end($has_image))) {
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
		if ($this->json_response->success && count($this->json_response->success) >= 1) {
			$falsed = 0;
			foreach ($this->json_response->success as $is_it) {
				if ($is_it) {$falsed++;}
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
		if (empty(trim($row))) {
			$unallowed_blanks = [
				// 'texonomy_featured_image', '_faq_template'
			];
			if (in_array($row, $unallowed_blanks)) {
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
				if (wp_get_attachment_url($id) === $url) {
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
		if (isset($this->current_term->term_id) || isset($this->csv_columns_text['subcategory'])) {
			$args = [
				// 'category_name'	=> $this->csv_columns_text['subcategory'],
				'cat__in'		=> [
					$this->current_term->term_id??$this->csv_columns_text['subcategory']
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
					$this->current_term->term_id??$this->csv_columns_text['subcategory']
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
		if (isset($this->current_term->term_id) || isset($this->csv_columns_text['subcategory'])) {
			$args = [
				'cat__in'		=> [
					$this->current_term->term_id??$this->csv_columns_text['subcategory']
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
					$this->current_term->term_id??$this->csv_columns_text['subcategory']
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

	public function column_keys_to_change() {
		return [
			'service(formulafromnextthreefields-donotmanuallyedit)'		=> 'servicetitle',
			'featuredbanner-1800x550'									=> 'featuredimage',
			'details'													=> 'meta:details',
			'overview'													=> 'meta:overview',
			'installation/repair/service'								=> 'meta:service_type',
			'fixedprice/fixedhourly/quoterequired'						=> 'meta:pricing_type',
			'routinemaintenancerequired(y/n)'							=> 'meta:routinemaintenance',
			'pricetocustomer'											=> 'meta:pricetocustomer',
			'costtosos'													=> 'meta:costtosos',
			'profit'													=> 'meta:profit',
		];
	}
	
	/**
	 * Change coloumn title to key for further operations.
	 * Filter Attributes for popup imports.
	 */
	public function redefine_column_keys($type) {
		switch ($type) {
			case 'mixed':
				/**
				 * Titles to change to keys.
				 */
				$to_change = $this->column_keys_to_change();
				foreach ($to_change as $key => $value) {
					if (isset($this->csv_columns[$key])) {
						$this->csv_columns[$value] = $this->csv_columns[$key];
						unset($this->csv_columns[$key]);
					}
				}
				// Parse Attributes for popup importings.
				$this->parse_imports_attributes($type);
				break;
			default:
				break;
		}
	}
	public function parse_imports_attributes($type) {
		switch ($type) {
			case 'cats':
				foreach ($this->csv_columns as $key => $row) {
					$string = $key;
					$pattern = "/^attribute(\d+)-name$/";
					if (preg_match($pattern, $string, $matches)) {
						$integerValue = $matches[1]; // This is the intiger value.
						if ($integerValue && !empty($integerValue) && is_numeric($integerValue)) {
							$_value_key = 'attribute' . $integerValue . '-values';
							if (isset($this->csv_columns[$_value_key])) {
								// Attribute & Attribute value found.
								$this->csv_attributes[$integerValue] = [
									'name'			=> $this->csv_columns[$key],
									'values'		=> $this->csv_columns[$_value_key]
								];
								
								/**
								 * Delete Attributes form CSV_ROW
								 */
								unset($this->csv_columns[$_value_key]);unset($this->csv_columns[$key]);
							}
						}
					}
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Import cats single meta data.
	 */
	public function sos_import_cats_row($response, $order, $row) {
		$metas = [];$fields = [];
		foreach ($row as $key => $value) {
			if ($this->is_escapable_blank($key, $value)) {continue;}
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
		if ($term_name && !empty($term_name)) {
			$args = ['description' => $fields['categorydescription']??''];
			if ($parent_term_name && !empty($parent_term_name)) {
				$parent_term_id = get_term_by('name', $parent_term_name, 'services');
				if ($parent_term_id && !is_wp_error($parent_term_id)) {
					$parent_term_id = (array) $parent_term_id;
					$args['parent'] = $parent_term_id['term_id'];
				} else {
					$inserted_id = wp_insert_term($parent_term_name, 'services', [
						'description' => $fields['parentcategorydescription']??''
					]);
					if ($inserted_id && !is_wp_error($inserted_id)) {
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
			if ($inserted_id && !is_wp_error($inserted_id)) {
				if (isset($args['parent'])) {
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
			if ($this->is_escapable_blank($key, $value)) {continue;}
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
		if ($term_name && !empty($term_name)) {
			$args = ['description' => $fields['areadescription']??''];
			if ($parent_term_name && !empty($parent_term_name)) {
				$parent_term_id = get_term_by('name', $parent_term_name, 'area');
				if ($parent_term_id && !is_wp_error($parent_term_id)) {
					$parent_term_id = (array) $parent_term_id;
					$args['parent'] = $parent_term_id['term_id'];
				} else {
					$inserted_id = wp_insert_term($parent_term_name, 'area', [
						'description' => $fields['parentareadescription']??''
					]);
					if ($inserted_id && !is_wp_error($inserted_id)) {
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
			if ($inserted_id && !is_wp_error($inserted_id)) {
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
			// if ($this->is_escapable_blank($key, $value)) {continue;}
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
		 * Other fields to proceed services
		 * Insert all meta data on the following services.
		 */
		$_thumbnail_id = $fields['featuredimage']??'';
		// if ($this->isFileUrl($_thumbnail_id)) {
		// 	if (
		// 		true
		// 		// $this->isRemoteUrl($_thumbnail_id)
		// 	) {
		// 		$_thumbnail_id = $this->insert_attachment_from_url($_thumbnail_id, 0, basename($_thumbnail_id));
		// 		if ($_thumbnail_id && !is_wp_error($_thumbnail_id) && is_int($_thumbnail_id)) {
		// 			// Yeah this is a proper thumbnail ID I hope.
		// 			$_thumbnail_id = (int) $_thumbnail_id;
		// 		} else {
		// 			$_thumbnail_id = false;
		// 		}
		// 	}
		// }

		/**
		 * Term & parent Term fields.
		 */
		$services_ids = $this->get_all_cat_to_term_id($fields);
		/**
		 * Get All zip code Term ID either create and return ID.
		 */
		$zip_terms = $this->get_all_zip_to_term_id($fields['zipcodeavailability']??'');
		
		$args = [
			'post_title'	=> wp_strip_all_tags($fields['servicetitle']??''),
			'post_status'	=> strtolower($fields['poststatus']??'publish'),
			'post_content'	=> $fields['postcontent']??($fields['content']??($fields['meta:details']??'')),
			'post_excerpt'	=> $fields['postexcerpt']??'',
			'post_author'	=> get_current_user_id(),
			'post_type'		=> 'service',
			// 'post_category'	=> $services_ids,
			'tax_input'		=> [
				'area'		=> $zip_terms,
				'services'	=> $services_ids,
			],
			'meta_input'	=> [
				// 'key'		=> 'value'
			]
		];
		if ($_thumbnail_id && !empty($_thumbnail_id)) {
			$args['_thumbnail_id'] = $_thumbnail_id;
		}
		
		if (!empty(trim($args['post_title']))) {
			$post_id = wp_insert_post($args, true); // Service ID.
			
			if ($post_id && !is_wp_error($post_id)) {
				wp_set_post_terms($post_id, $zip_terms, 'area');
				wp_set_post_terms($post_id, $services_ids, 'services');
				// foreach ($zip_terms as $term_id) {}
				// foreach ($services_ids as $term_id) {}
				$this->add_response_message(sprintf(
					__('New post entry (%s) created with the ID (%d).', 'domain'), $args['post_title'], $post_id
				), true);


				if (count($metas) >= 1) {$this->insert_service_metas($post_id, $metas, false);}

				$this->insert_service_attributes($post_id, $fields, [
					$response, $order, $row
				]);
				$this->insert_service_packages($post_id, $fields, [
					$response, $order, $row
				]);
			}
		} else {
			$this->add_response_message(sprintf(
				__('Post row skipped due to empty title.', 'domain')
			), true);
		}
		
		
		
		return $response;
	}

	/**
	 * Import mixed row.
	 */
	public function sos_import_mixed_row($response, $order, $row) {
		return $this->sos_import_services_row($response, $order, $row);
	}

	/**
	 * Insert attributes as popup configuration.
	 */
	public function insert_service_attributes($post_id, $fields, $args) {
		/**
		 * Sortout attribute to row.
		 */
		$attributes_rows = [];
		foreach ($fields as $key => $value) {
			$pattern = "/^attribute(\d+)-name$/";
			if (preg_match($pattern, $key, $matches)) {
				$integerValue = $matches[1];
				$attribute_row = [];
				// foreach ($this->csv_attributes[$key] as $index => $value) {
				// 	$attribute_row[] = '';
				// }
				$attributes_rows[$integerValue] = [
					'key'		=> $value,
					'value'		=> $fields[sprintf('attribute%d-values', $integerValue)]??''
				];
			}
		}
		$options = [];$keyI = 0;
		foreach ($attributes_rows as $key => $row) {
			if ($row['key'] && $row['value'] && !empty(trim($row['key'])) && !empty(trim($row['value']))) {
				$options[] = $this->pops_option_row([
					'fieldID'	=> $keyI,
					'heading'	=> $row['key'],
					'options'	=> explode(',', $row['value'])
				]);
				$keyI++;
			}
		}
		
		// for ($i=0; $i < 5; $i++) { 
		// 	$fields['category'] = isset($fields['mastercategory1']) && !empty($fields['mastercategory1']) && term_exists($fields['mastercategory1'], 'services')?get_term_by('name', $fields['mastercategory1'], 'services'):false;
		// 	if ($fields['category'] && !is_wp_error($fields['category'])) {break;}
		// }

		if (count($options) >= 1) {
			// do_action('sospopsproject/event/stream/send', $options);
			update_post_meta($post_id, '_sos_custom_popup', $options);
			// $this->pops_import_to_services_under_category($options);
			// $this->json_response['pops_data'] = $options;
			
			$this->add_response_message(sprintf(
				__('Configuration setup done on the service (%s - %s).', 'domain'), get_the_title($post_id), $post_id
			), true);
		} else {
			$this->add_response_message(sprintf(
				__('Configuration setup failed on the service (%s - %s).', 'domain'), get_the_title($post_id), $post_id
			), false);
		}
	}

	/**
	 * Insert attributes as popup configuration.
	 */
	public function insert_service_packages($post_id, $fields, $args) {
		/**
		 * Sortout attribute to row.
		 */
		$attributes_rows = [];
		foreach ($fields as $key => $value) {
			$pattern = "/^package(\d+)-name$/";
			if (preg_match($pattern, $key, $matches)) {
				$integerValue = $matches[1];
				$attribute_row = [];
				// foreach ($this->csv_attributes[$key] as $index => $value) {
				// 	$attribute_row[] = '';
				// }
				$attributes_rows[$integerValue] = [
					'key'		=> $value,
					'value'		=> $fields[sprintf('attribute%d-serviceincluded', $integerValue)]??''
				];
			}
		}
		$options = [];$keyI = 0;
		foreach ($attributes_rows as $key => $row) {
			if ($row['key'] && $row['value'] && !empty(trim($row['key'])) && !empty(trim($row['value']))) {
				/**
				 * Primarily escape name column and implementing only service included field.
				 */
				$options[] = trim($row['value']);
				$keyI++;
			}
		}
		if (count($options) >= 1) {
			update_post_meta($post_id, '_sos_custom_services', $options);
		}
	}
	
	/**
	 * Get All zip code Term ID either create and return ID.
	 */
	public function get_all_zip_to_term_id($zips) {
		$terms_id = [];

		$zips = explode(',', str_replace([' '], [''], $zips));
		foreach ($zips as $zip) {
			$term = get_term_by('name', $zip, 'area');
			if ($term && !is_wp_error($term)) {
				$term = (array) $term;
				$terms_id[] = $term['term_id'];
			} else {
				$term = wp_insert_term($zip, 'area', ['description' => '']);
				if ($term && !is_wp_error($term)) {
					$term = (array) $term;
					$terms_id[] = $term['term_id'];
					$this->add_response_message(sprintf(
						__('New Area Term created (%s) with the ID (%s) and is a parent term.', 'domain'),
						$zip, $term['term_id']
					), true);
				}
			}
		}
		
		return $terms_id;
	}
	
	/**
	 * Get All Category Term ID either create and return ID.
	 */
	public function get_all_cat_to_term_id($fields) {
		$terms_id = [];

		foreach ($fields as $_key => $_cat) {
			if ($this->isFileUrl($_cat) || $this->isRemoteUrl($_cat)) {continue;}
			if (strpos($_key, 'mastercategory') !== false) {
				$term = get_term_by('name', $_cat, 'services');
				if ($term && !is_wp_error($term)) {
					$term = (array) $term;
					$terms_id[] = $term['term_id'];
					unset($fields[$_key]);
				} else {
					$term = wp_insert_term($_cat, 'services', ['description' => '']);
					if ($term && !is_wp_error($term)) {
						$term = (array) $term;
						$terms_id[] = $term['term_id'];
						$this->add_response_message(sprintf(
							__('New Service Term created (%s) with the ID (%s) and is a parent term.', 'domain'),
							$_cat, $term['term_id']
						), true);
						unset($fields[$_key]);
					}
				}
			}
		}
		
		return $terms_id;
	}

	/**
	 * Insert meta data on the following texonomy
	 * for both of subcategory and parent category
	 */
	public function insert_texonomy_metas($term_id, $metas, $is_parent) {
		foreach ($metas as $key => $value) {
			if (empty(trim($value))) {
				$this->add_response_message(sprintf(
					__('Empty value for the meta key (%s) on the term (%s) skipped.', 'domain'),
					$key, $term_id
				), false);
				continue;
			}
			$meta_items = false;
			if ($is_parent) {
				/**
				 * Only parent meta
				 */
				if (strtolower(substr(trim($key), 0, 12)) == 'meta:parent:') {
					$meta_items = [
						'key'		=> substr(trim($key), 12),
						'value'		=> $value
					];
				}
			} else {
				/**
				 * Meta without parent meta
				 */
				if (
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
			if ($meta_items) {
				if ($this->isFileUrl($meta_items['value'])) {
					if (
						true
						// $this->isRemoteUrl($meta_items['value'])
					) {
						$meta_items['value'] = $this->insert_attachment_from_url($meta_items['value'], 0, basename($meta_items['value']));
					}
				}
				$is_updated = update_term_meta($term_id, $meta_items['key'], $meta_items['value']);
				if ($is_updated && !is_wp_error($is_updated)) {
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
		foreach ($metas as $key => $value) {
			if (empty(trim($value))) {
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
			if (
				strtolower(substr(trim($key), 0, 5)) == 'meta:'
										&&
				strtolower(substr(trim($key), 0, 12)) != 'meta:parent:'
			) {
				$meta_items = [
					'key'		=> substr(trim($key), 5),
					'value'		=> $value
				];
			}
			if ($meta_items) {
				if ($this->isFileUrl($meta_items['value'])) {
					if (
						true
						// $this->isRemoteUrl($meta_items['value'])
					) {
						$meta_items['value'] = $this->insert_attachment_from_url($meta_items['value'], 0, basename($meta_items['value']));
					}
				}
				$is_updated = update_post_meta($post_id, $meta_items['key'], sanitize_textarea_field($meta_items['value']));
				if ($is_updated && !is_wp_error($is_updated)) {
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
		if ($posts && !is_wp_error($posts) && count($posts) >= 1 && isset($posts[0])) {
			$this->add_response_message(sprintf(
				__('The URL (%s) matched with the following attachment ID (%s) replaced and returned attachments ID.', 'domain'), $file_url, $posts[0]->ID
			), true);
			return $posts[0]->ID;
		}
		
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		$upload_dir = wp_upload_dir();

		// Check if it is dropbox and then ensure download parameter on url.
		if (strpos($file_url, 'dropbox.com') !== false && substr($file_url, -5) == '&dl=0') {
			$file_url = substr($file_url, 0, -1) . '1';
		}
		
		// Download the file
		$temp_file = download_url($file_url);
		$upload_path = $upload_dir['path'] . '/' . basename($file_url);
		if ($temp_file && !is_wp_error($temp_file)) {
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
			if ($attachment_id && !is_wp_error($attachment_id)) {
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

	/**
	 * Check whether the term name empty or invalid.
	 */
	public function pre_insert_term($term, $taxonomy, $args) {
		if (!in_array($taxonomy, ['area', 'services'])) {return $term;}
		if (empty($term)) {
			$this->add_response_message(sprintf(
				__('Failed to insert term with empty title (%s).', 'domain'),
				$term
			), false);
			return new WP_Error('invalid_term_name', __('Empty term name.', 'domain'));
		}
		if (in_array(trim($term), ['#REF!'])) {
			$this->add_response_message(sprintf(
				__('Failed to insert term with invalid title (%s).', 'domain'),
				$term
			), false);
			return new WP_Error('invalid_term_name', __('Invalid term name.', 'domain'));
		}
		$this->add_response_message(sprintf(
			__('Term (%s) created successfully under texonomy (%s).', 'domain'), $term, $taxonomy
		), true);
		return $term;
	}

	/**
	 * Create new term if not exists.
	 * @return init
	 * @return bool
	 */
	public function get_term_id($args) {
		$this->current_term = $term = get_term_by('name', $args['name'], 'services');
		if ($term && !is_wp_error($term)) {
			$term = (array) $term;
			$this->json_response['term_hooked'] = $this->json_response['term_hooked']??[];
			$this->json_response['term_hooked'][] = $term;
		} else {
			$term = wp_insert_term($args['name'], $args['texonomy']??'services', ['description' => $args['description']??'']);
			if ($term && !is_wp_error($term)) {
				$term = (array) $term;
				$this->json_response['term_hooked'] = $this->json_response['term_hooked']??[];
				$this->json_response['term_hooked'][] = $term;
				$this->add_response_message(sprintf(
					__('New Services Term created (%s) with the ID (%s) and is a parent term.', 'domain'),
					$term_name, $term['term_id']
				), true);
			}
		}
	}

	public function import_stream_fetch_rows($stream_register) {
		$this->json_response['imported_data'] = $this->json_response['imported_data']??[];
		if (isset($stream_register['csv_rows'])) {$this->csv_rows = $stream_register['csv_rows'];}
		if (isset($stream_register['type'])) {$type = $stream_register['type'];} else {$type = '';}
		if (isset($stream_register['csv_columns'])) {$this->csv_columns = $stream_register['csv_columns'];}
		if (isset($stream_register['current_term'])) {$this->current_term = $stream_register['current_term'];}
		if (isset($stream_register['json_response'])) {$this->json_response = $stream_register['json_response'];}
		if (isset($stream_register['csv_attributes'])) {$this->csv_attributes = $stream_register['csv_attributes'];}
		if (isset($stream_register['csv_columns_text'])) {$this->csv_columns_text = $stream_register['csv_columns_text'];}
		
		if (is_array($this->csv_rows)) {
			do_action('sospopsproject/event/stream/init', [
				'total'		=> count($this->csv_rows),
				'message'	=> 'Connected',
				'status'	=> true
			]);

			// do_action('sospopsproject/event/stream/send', $stream_register);
			
			if (isset($stream_register['attributes'])) {
				// do_action('sospopsproject/event/stream/send', $stream_register['attributes']);
			}
			
			// $this->csv_rows = array_slice($this->csv_rows, (count($this->csv_rows) - $stream_register['rest']));
			$increment = 0;$total_yet = count($this->csv_rows);
			foreach ($this->csv_rows as $key => $row) {
				if ($increment >= (($total_yet < 20)?$total_yet:20)) {
					$done_yet = (($stream_register['done']??0) + $increment);
					do_action('sospopsproject/event/stream/send', [
						'progress'	=> ($done_yet / $stream_register['total']) * 100,
						'total'		=> $stream_register['total']??false,
						'rest'		=> count($this->csv_rows),
						'done'		=> $done_yet,
						'message'	=> 'Progress'
					]);
					apply_filters('sospopsproject/event/stream/register', [
						...$stream_register,
						'done'			=> (($stream_register['done']??0) + $increment),
						'rest'			=> count($this->csv_rows),
						'csv_rows'		=> $this->csv_rows,
					]);
					sleep(1);
					do_action('sospopsproject/event/stream/break', [
						'rest'		=> count($this->csv_rows),
						'message'	=> 'Break',
						'type'		=> 'break',
						'status'	=> true
					]);
					break;
				} else {
					$this->json_response['imported_data'][] = apply_filters('sos/import/' . $type . '/row', false, $key, $row);
					unset($this->csv_rows[$key]);$increment++;
				}
			}
			
			apply_filters('sospopsproject/event/stream/register', [
				...$stream_register,
				'done'			=> (($stream_register['total']??0) - count($this->csv_rows)),
				'csv_rows'		=> $this->csv_rows,
				'rest'			=> count($this->csv_rows)
			]);
			if (count($this->csv_rows) <= 0) {
				do_action('sospopsproject/event/stream/send', [
					'message'	=> 'Progress',
					'progress'	=> 0,
					'total'		=> 0,
					'done'		=> 0
				]);
				do_action('sospopsproject/event/stream/close', [
					'response'	=> $this->json_response,
					'message'	=> 'Finished',
					'type'		=> 'finish',
					'status'	=> true
				]);
			} else {}
			
		} else {
			wp_send_json_error($stream_register);
		}
	}

	/**
	 * Delete bulk imported data from here.
	 * https://wordpress-1152450-4011671.cloudwaysapps.com/wp-admin/admin-ajax.php?action=sospopsproject/ajax/import/clean&clean=terms&taxonomy=services
	 */
	public function delete_all_services() {
		global $wpdb;$this->json_response = ['hooks' => []];
		switch ($_REQUEST['clean']??'') {
			case 'terms':
				$args = [
					'taxonomy' => $_REQUEST['taxonomy']??'services',
					'fields' => 'ids', 'number' => 500, 'hide_empty' => false
				];
				$terms = get_terms($args);
				$args['terms'] = $terms;
				$args = wp_parse_args($args, [
					'done'			=> 0,
					'rest'			=> 0,
					'type'			=> 'terms',
					'total'			=> count($terms),
					'hook'			=> 'sospopsproject/clean/stream/fetch/rows'
				]);

				$stream_register = apply_filters('sospopsproject/event/stream/register', $args);

				if (!is_array($stream_register) && !isset($stream_register['type'])) {
					if ($stream_register === true) {
						$this->json_response['hooks'][] = 'event_registered';
					}
				}
				break;
			default:
				$args = [
					'numberposts'	=> -1,
					'fields'		=> 'ids',
					'hide_empty'	=> false,
					'post_status'	=>'publish',
					'post_type'		=>'service'
				];
				$ids = get_posts($args);
				if ($ids && !is_wp_error($ids)) {
					$args['posts'] = $ids;
					$args = wp_parse_args($args, [
						'done'			=> 0,
						'rest'			=> 0,
						'type'			=> 'posts',
						'total'			=> count($ids),
						'hook'			=> 'sospopsproject/clean/stream/fetch/rows'
					]);
					$stream_register = apply_filters('sospopsproject/event/stream/register', $args);
					if (!is_array($stream_register) && !isset($stream_register['type'])) {
						if ($stream_register === true) {
							$this->json_response['hooks'][] = 'event_registered';
						}
					}
				} else {
					$this->json_response['message']	= is_wp_error($ids)?$ids->get_error_message():sprintf(__('Failed to get list or list is empty (%s)', 'domain'), json_encode($ids));
				}
				
				break;
		}
		wp_send_json_success($this->json_response);
	}
	
	public function clean_stream_fetch_rows($stream_register) {
		do_action('sospopsproject/event/stream/init', [
			'message'	=> 'Connected',
			'status'	=> true,
			'total'		=> $stream_register['total']??0
		]);
		switch ($stream_register['type']??'') {
			case 'terms':
			// 	do_action('sospopsproject/event/stream/send', $stream_register);
			// 	break;
			// case 'terms--':
				$terms = $stream_register['terms']??[];
				$type = $stream_register['type']??[];
				$increment = 0;
				foreach ($terms as $index => $term_id) {
					if ($increment >= 20) {
						$done_yet = ($stream_register['done'] + $increment);
						do_action('sospopsproject/event/stream/send', [
							'progress'	=> ($done_yet / $stream_register['total']) * 100,
							'total'		=> $stream_register['total'],
							'rest'		=> count($terms),
							'message'	=> 'Progress',
							'done'		=> $done_yet,
						]);
						apply_filters('sospopsproject/event/stream/register', [
							...$stream_register,
							'terms'			=> $terms,
							'done'			=> $done_yet,
							'rest'			=> count($terms),
						]);
						do_action('sospopsproject/event/stream/break', [
							'message'	=> 'Break',
							'type'		=> 'break',
							'status'	=> true
						]);
						break;
					} else {
						$_is_deleted = wp_delete_term($term_id, $stream_register['taxonomy']??'services');$increment++;
						if ($_is_deleted && !is_wp_error($_is_deleted)) {
							unset($terms[$index]);
						} else {
							do_action('sospopsproject/event/stream/send', [
								'message'	=> is_wp_error($_is_deleted)?$_is_deleted->get_error_message():$_is_deleted,
								'type'		=> 'error',
							]);
						}
						
					}
				}
				$terms = get_terms([
					'taxonomy' => $stream_register['taxonomy']??'services',
					'fields' => 'ids', 'number' => 500, 'hide_empty' => false
				]);
				if (count($terms) <= 0) {
					do_action('sospopsproject/event/stream/close', [
						'message'	=> 'Finished',
						'type'		=> 'finish',
						'status'	=> true
					]);
				}
				break;
			case 'posts':
				$posts = $stream_register['posts']??[];
				$type = $stream_register['type']??[];
				$increment = 0;
				foreach ($posts as $index => $term_id) {
					if ($increment >= 20) {
						$done_yet = ($stream_register['done'] + $increment);
						do_action('sospopsproject/event/stream/send', [
							'progress'	=> ($done_yet / $stream_register['total']) * 100,
							'total'		=> $stream_register['total'],
							'rest'		=> count($posts),
							'message'	=> 'Progress',
							'done'		=> $done_yet,
						]);
						apply_filters('sospopsproject/event/stream/register', [
							...$stream_register,
							'posts'			=> $posts,
							'done'			=> $done_yet,
							'rest'			=> count($posts),
						]);
						do_action('sospopsproject/event/stream/break', [
							'message'	=> 'Break',
							'type'		=> 'break',
							'status'	=> true
						]);
						break;
					} else {
						wp_delete_post($term_id, true);
						wp_trash_post($term_id);
						unset($posts[$index]);
						$increment++;
					}
				}
				$posts = get_posts([
					'numberposts'	=> -1,
					'fields'		=> 'ids',
					'hide_empty'	=> false,
					'post_status'	=>'publish',
					'post_type'		=>'service'
				]);
				if (count($posts) <= 0) {
					do_action('sospopsproject/event/stream/close', [
						'message'	=> 'Finished',
						'type'		=> 'finish',
						'status'	=> true
					]);
				}
				break;
			default:
				do_action('sospopsproject/event/stream/close', [
					'message'	=> 'Invalid Requests',
					'type'		=> 'finish',
					'status'	=> true
				]);
				break;
		}
	}
	
}