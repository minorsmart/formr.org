<?php

class ItemFactory {

	public $errors;
	private $choice_lists = array();
	private $used_choice_lists = array();
	public $showifs = array();
	public $openCPU_errors = array();

	function __construct($choice_lists) {
		$this->choice_lists = $choice_lists;
	}

	public function make($item) {
		$type = "";
		if (isset($item['type'])) {
			$type = $item['type'];
		}

		if (!empty($item['choice_list'])): // if it has choices
			if (isset($this->choice_lists[$item['choice_list']])): // if this choice_list exists
				$item['choices'] = $this->choice_lists[$item['choice_list']]; // take it
				$this->used_choice_lists[] = $item['choice_list']; // check it as used
			else:
				$item['val_errors'] = array(__("Choice list %s does not exist, but is specified for item %s", $item['choice_list'], $item['name']));
			endif;
		endif;

		$type = str_replace("-", "_", $type);
		$class = "Item_" . $type;

		if (!class_exists($class, true)) {
			return false;
		}

		return new $class($item);
	}

	public function unusedChoiceLists() {
		return array_diff(
				array_keys($this->choice_lists), $this->used_choice_lists
		);
	}


}

// the default item is a text input, as many browser render any input type they don't understand as 'text'.
// the base class should also work for inputs like date, datetime which are either native or polyfilled but don't require
// special handling here

class Item extends HTML_element {

	public $id = null;
	public $name = null;
	public $type = null;
	public $type_options = null;
	public $choice_list = null;
	public $label = null;
	public $label_parsed = null;
	public $optional = 0;
	public $class = null;
	public $showif = null;
	public $js_showif = null;
	public $value = null; // syntax for sticky value
	public $value_validated = null;
	public $order = null;
	public $block_order = null;
	public $item_order = null;
	public $displaycount = null;
	public $error = null;
	public $dont_validate = null;
	public $reply = null;
	public $val_errors = array();
	public $val_warnings = array();
	public $mysql_field = 'TEXT DEFAULT NULL';
	protected $prepend = null;
	protected $append = null;
	protected $type_options_array = array();
	public $choices = array();
	protected $hasChoices = false;
	protected $data_showif = false;
	public $hidden = false;
	public $no_user_input_required = false;
	public $save_in_results_table = true;
	public $input_attributes = array(); // so that the pre-set value can be set externally
	protected $classes_controls = array('controls');
	protected $classes_wrapper = array('form-group', 'form-row');
	protected $classes_input = array();
	protected $classes_label = array('control-label');
	protected $presetValues = array();
	protected $probably_render = null;
	protected $js_hidden = false;
	public $presetValue = null;
	public $allowed_classes = array(
		"",
		"clickable_map",
		"thick_border_top",
		"red",
		"green",
		"people_list",
		"vertical_range",
		"space_label_answer_vertical_10",
		"space_label_answer_vertical_20",
		"space_label_answer_vertical_30",
		"space_label_answer_vertical_40",
		"space_label_answer_vertical_50",
		"space_label_answer_vertical_60",
		"space_bottom_10",
		"space_bottom_20",
		"space_bottom_30",
		"space_bottom_40",
		"space_bottom_50",
		"space_bottom_60",
		"hidden",
		"rating_button_label_width50",
		"rating_button_label_width60",
		"rating_button_label_width70",
		"rating_button_label_width80",
		"rating_button_label_width90",
		"rating_button_label_width100",
		"rating_button_label_width150",
		"rating_button_label_width200",
		"mc_width50",
		"mc_width60",
		"mc_width70",
		"mc_width80",
		"mc_width100",
		"mc_width150",
		"mc_width200",
		"mc_equal_widths",
		"mc_vertical",
		"mc_block",
		"mc_boxed",
		"rotate_label30",
		"rotate_label45",
		"rotate_label90",
		"hide_label",
		"answer_align_right",
		"answer_align_left",
		"answer_align_center",
		"label_align_right",
		"label_align_left",
		"label_align_center",
		"right20",
		"right30",
		"right50",
		"right70",
		"right80",
		"right100",
		"right100",
		"right150",
		"right200",
		"right300",
		"right400",
		"right500",
		"right600",
		"right700",
		"right800",
		"right900",
		"padding100",
		"padding200",
		"padding300",
		"padding400",
		"padding500",
		"padding600",
		"padding700",
		"padding800",
		"padding900",
		"left_offset0",
		"left_offset100",
		"left_offset200",
		"left_offset300",
		"left_offset400",
		"left_offset500",
		"left_offset600",
		"left_offset700",
		"left_offset800",
		"left_offset900",
		"right_offset0",
		"right_offset100",
		"right_offset200",
		"right_offset300",
		"right_offset400",
		"right_offset500",
		"right_offset600",
		"right_offset700",
		"right_offset800",
		"right_offset900",
		"left0",
		"left100",
		"left200",
		"left300",
		"left400",
		"left500",
		"left600",
		"left700",
		"left800",
		"left900",
		"align_horizontally",
		"clear",
		"row_height_40",
		"answer_below_label",
		"float_image_left",
		"float_image_right",
		"label_as_placeholder"
	);
	
	public function __construct($options = array()) {
		// simply load the array into the object, with some sensible defaults
		$this->id = isset($options['id']) ? $options['id'] : 0;

		if (isset($options['type'])) {
			$this->type = $options['type'];
		}

		if (isset($options['name'])) {
			$this->name = $options['name'];
		}

		if (isset($options['value'])) {
			$this->value = $options['value'];
		}

		if (isset($options['order'])) {
			$this->order = $options['order'];
		}
		if (isset($options['block_order'])) {
			$this->block_order = $options['block_order'];
		}
		if (isset($options['item_order'])) {
			$this->item_order = $options['item_order'];
		}

		$this->label = isset($options['label']) ? $options['label'] : '';
		$this->label_parsed = isset($options['label_parsed']) ? $options['label_parsed'] : null;

		if (isset($options['type_options'])):
			$this->type_options = trim($options['type_options']);
			$this->type_options_array = array($options['type_options']);
		endif;

		if (isset($options['choice_list'])) {
			$this->choice_list = $options['choice_list'];
		}
		
		if (empty($this->choice_list) && $this->hasChoices && $this->type_options != "") {
			$lc = explode(' ', trim($this->type_options));
			if(count($lc) > 1) {
				$this->choice_list = end($lc);
			}
		}

		if (isset($options['choices'])) {
			$this->choices = $options['choices'];
		}

		if (isset($options['showif'])) {
			$this->showif = $options['showif'];
		}

		if (array_key_exists('hidden', $options)) {
			$this->hidden = $options['hidden'];
		}

		if (isset($options['val_error']) && $options['val_error']) {
			$this->val_error = $options['val_error'];
		}

		if (isset($options['error']) && $options['error']) {
			$this->error = $options['error'];
			$this->classes_wrapper[] = "has-error";
		}

		if (isset($options['displaycount']) && $options['displaycount'] !== null) {
			$this->displaycount = $options['displaycount'];
			if (!$this->error) {
//				$this->classes_wrapper[] = "has-warning";
			}
		}

		$this->input_attributes['name'] = $this->name;

		$this->setMoreOptions();

		// after the easily overridden setMoreOptions, some post-processing that is universal to all items.

		if (isset($options['optional']) && $options['optional']) {
			$this->optional = 1;
			unset($options['optional']);
		} elseif (isset($options['optional']) && ! $options['optional']) {
			$this->optional = 0;
		} // else optional stays default

		if (!$this->optional) {
			$this->classes_wrapper[] = 'required';
			$this->input_attributes['required'] = 'required';
		} else {
			$this->classes_wrapper[] = 'optional';
		}

		if (isset($options['class']) && $options['class']):
			$this->classes_wrapper = array_merge($this->classes_wrapper, explode(" ", $options['class']));
			$this->class = $options['class'];
		endif;

		$this->classes_wrapper[] = "item-" . $this->type;

		if (!isset($this->input_attributes['type'])) {
			$this->input_attributes['type'] = $this->type;
		}

		$this->input_attributes['class'] = implode(" ", $this->classes_input);

		$this->input_attributes['id'] = "item{$this->id}";

		if (in_array("label_as_placeholder", $this->classes_wrapper)) {
			$this->input_attributes['placeholder'] = $this->label;
		}

		if ($this->showif):
			// primitive R to JS translation
			$this->js_showif = preg_replace("/current\(\s*(\w+)\s*\)/", "$1", $this->showif); // remove current function
			$this->js_showif = preg_replace("/tail\(\s*(\w+)\s*, 1\)/", "$1", $this->js_showif); // remove current function, JS evaluation is always in session			
			// all other R functions may break
			$this->js_showif = preg_replace("/(^|[^&])(\&)([^&]|$)/", "$1&$3", $this->js_showif); // & operators, only single ones need to be doubled
			$this->js_showif = preg_replace("/(^|[^|])(\|)([^|]|$)/", "$1|$3", $this->js_showif); // | operators, only single ones need to be doubled
			$this->js_showif = preg_replace("/FALSE/", "false", $this->js_showif); // uppercase, R, FALSE, to lowercase, JS, false
			$this->js_showif = preg_replace("/TRUE/", "true", $this->js_showif); // uppercase, R, TRUE, to lowercase, JS, true
			$quoted_string = "([\"'])((\\\\{2})*|(.*?[^\\\\](\\\\{2})*))\\1";
			$this->js_showif = preg_replace("/\s*\%contains\%\s*".$quoted_string."/", ".indexOf($1$2$1) > -1", $this->js_showif);
			$this->js_showif = preg_replace("/\s*\%begins_with\%\s*".$quoted_string."/", ".indexOf($1$2$1) === 0", $this->js_showif);
			$this->js_showif = preg_replace("/\s*\%starts_with\%\s*".$quoted_string."/", ".indexOf($1$2$1) === 0", $this->js_showif);
			$this->js_showif = preg_replace("/\s*\%ends_with\%\s*".$quoted_string."/", ".endsWith($1$2$1)", $this->js_showif);
			$this->js_showif = preg_replace("/\s*stringr::str_length\(([a-zA-Z0-9_'\"]+)\)/", "$1.length", $this->js_showif);

			if (strstr($this->showif, "//js_only") !== false) {
				$this->setVisibility(array(null));
			}
		endif;
	}

	public function refresh($options = array(), $properties = array()) {
		foreach ($properties as $property) {
			if (property_exists($this, $property) && isset($options[$property])) {
				$this->{$property} = $options[$property];
			}
		}

		$this->setMoreOptions();
		$this->classes_wrapper = array_merge($this->classes_wrapper, array('item-'.$this->type));
		return $this;
	}

	public function hasBeenRendered() {
		return $this->displaycount !== null;
	}

	public function hasBeenViewed() {
		return $this->displaycount > 0;
	}

	protected function chooseResultFieldBasedOnChoices() {
		if ($this->mysql_field == null) {
			return;
		}
		$choices = array_keys($this->choices);

		$len = count($choices);
		if ($len == count(array_filter($choices, "is_numeric"))):
			$this->mysql_field = 'TINYINT UNSIGNED DEFAULT NULL';

			$min = min($choices);
			$max = max($choices);

			if ($min < 0):
				$this->mysql_field = str_replace("UNSIGNED ", "", $this->mysql_field);
			endif;

			if (abs($min) > 32767 OR abs($max) > 32767):
				$this->mysql_field = str_replace("TINYINT", "MEDIUMINT", $this->mysql_field);
			elseif (abs($min) > 126 OR abs($min) > 126):
				$this->mysql_field = str_replace("TINYINT", "SMALLINT", $this->mysql_field);
			elseif (count(array_filter($choices, "is_float"))):
				$this->mysql_field = str_replace("TINYINT", "FLOAT", $this->mysql_field);
			endif;
		else:
			$lengths = array_map("strlen", $choices);
			$maxlen = max($lengths);
			$this->mysql_field = 'VARCHAR (' . $maxlen . ') DEFAULT NULL';
		endif;
	}
	public function isStoredInResultsTable() {
		return $this->save_in_results_table;
	}

	public function getResultField() {
		if (!empty($this->choices)):
			$this->chooseResultFieldBasedOnChoices();
		endif;

		if ($this->mysql_field !== null):
			return "`{$this->name}` {$this->mysql_field}";
		else:
			return null;
		endif;
	}

	public function validate() {
		if (!$this->hasChoices && ($this->choice_list !== null OR count($this->choices))):
			$this->val_errors[] = "'{$this->name}' You defined choices for this item, even though this type doesn't have choices.";
		elseif ($this->hasChoices && ($this->choice_list === null && count($this->choices)===0) && $this->type !== "select_or_add_multiple"):
			$this->val_errors[] = "'{$this->name}' You forgot to define choices for this item.";
		elseif($this->hasChoices && count(array_unique($this->choices)) < count($this->choices)):
			$dups = implode(array_diff_assoc($this->choices, array_unique($this->choices)), ", ");
			$this->val_errors[] = "'{$this->name}' You defined duplicated choices (".h($dups).") for this item.";
		endif;
		
		if (!preg_match('/^[A-Za-z][A-Za-z0-9_]+$/', $this->name)):
			$this->val_errors[] = "'{$this->name}' The variable name can contain <strong>a</strong> to <strong>Z</strong>, <strong>0</strong> to <strong>9</strong> and the underscore. It needs to start with a letter. You cannot use spaces, dots, or dashes.";
		endif;

		if (trim($this->type) == ""):
			$this->val_errors[] = "{$this->name}: The type column must not be empty.";
		endif;
		
		$defined_classes = array_map("trim", explode(" ",$this->class));
		$missing_classes = array_diff($defined_classes, $this->allowed_classes);
		if(count($missing_classes) > 0) {
			$this->val_warnings[] = "'{$this->name}' You used CSS classes that aren't part of the standard set (but maybe you defined them yourself): ". implode(", ", $missing_classes);
		}

		return array("val_errors" =>  $this->val_errors, "val_warnings" => $this->val_warnings);
	}

	public function validateInput($reply) {
		$this->reply = $reply;

		if (!$this->optional && (($reply === null || $reply === false || $reply === array() || $reply === '') || (is_array($reply) && count($reply) === 1 && current($reply) === ''))
		) { // missed a required field
			$this->error = __("You missed entering some required information.<span class='hidden'>item name: %s</span>", h($this->name));
		} elseif ($this->optional && $reply == '') {
			$reply = null;
		}
		return $reply;
	}

	protected function setMoreOptions() {
		
	}

	protected function render_label() {
		return '<label class="' . implode(" ", $this->classes_label) . '" for="item' . $this->id . '">' .
				($this->error ? '<span class="label label-danger hastooltip" title="' . $this->error . '"><i class="fa fa-exclamation-triangle"></i></span> ' : '') .
				$this->label_parsed . '</label>';
	}

	protected function render_prepended() {
		if (isset($this->prepend)) {
			return '<span class="input-group-addon"><i class="fa fa-fw ' . $this->prepend . '"></i></span>';
		}
		return '';
	}

	protected function render_input() {
		if ($this->value_validated) {
			$this->input_attributes['value'] = $this->value_validated;
		}

		return '<span><input ' . self::_parseAttributes($this->input_attributes) . '></span>';
	}

	protected function render_appended() {
		if (isset($this->append)) {
			return '<span class="input-group-addon"><i class="' . $this->append . '"></i></span>';
		}

		return '';
	}

	protected function render_inner() {
		$inputgroup = isset($this->prepend) || isset($this->append);
		return $this->render_label() . '
					<div class="' . implode(" ", $this->classes_controls) . '"><div class="controls-inner">' .
				($inputgroup ? '<div class="input-group">' : '') .
				$this->render_prepended() .
				$this->render_input() .
				$this->render_appended() .
				($inputgroup ? '</div>' : '') .
				'</div></div>
		';
	}

	protected function render_item_view_input() {
		return '<input class="item_shown" type="hidden" name="_item_views[shown][' . $this->id . ']"><input class="item_shown_relative" type="hidden" name="_item_views[shown_relative][' . $this->id . ']"><input class="item_answered" type="hidden" name="_item_views[answered][' . $this->id . ']"><input class="item_answered_relative" type="hidden" name="_item_views[answered_relative][' . $this->id . ']">';
	}

	public function render() {
		if ($this->error) {
			$this->classes_wrapper[] = "has-error";
		}

		$this->classes_wrapper = array_unique($this->classes_wrapper);
		return '<div class="' . implode(" ", $this->classes_wrapper) . '"' . ($this->data_showif ? ' data-showif="' . h($this->js_showif) . '"' : '') . '>' . $this->render_inner() . $this->render_item_view_input() . '
			<div class="hidden_debug_message hidden item_name">
						<span class="badge hastooltip" title="'.h($this->js_showif).'">' . h($this->name) . '</span>
			</div>
			</div>';
	}

	protected function splitValues() {
		if (isset($this->input_attributes['value'])):
			$this->presetValues = array_map("trim", explode(",", $this->input_attributes['value']));
			unset($this->input_attributes['value']);
		else:
			$this->presetValues = array();
		endif;
	}

	public function hide() {
		if (!$this->hidden):
			$this->classes_wrapper[] = "hidden";
			$this->data_showif = true;
			$this->input_attributes['disabled'] = true; ## so it isn't submitted or validated
			$this->hidden = true; ## so it isn't submitted or validated
		endif;
	}

	public function alwaysInvalid() {
		$this->error = _('There were problems with openCPU.');
		if (!isset($this->input_attributes['class'])) {
			$this->input_attributes['class'] = '';
		}
		$this->input_attributes['class'] .= " always_invalid";
	}

	public function needsDynamicLabel() {
		return $this->label_parsed === null;
	}

	public function getShowIf() {
		if(strstr($this->showif, "//js_only") !== false) {
			return "NA";
		}

		if($this->hidden !== null) {
			return false;
		}
		if (trim($this->showif)!= "") {
			return $this->showif;
		}
		return false;
	}

	public function needsDynamicValue() {
		$this->value = trim($this->value);
		if (!(is_formr_truthy($this->value))) {
			$this->presetValue = null;
			return false;
		}
		if (is_numeric($this->value)) {
			$this->input_attributes['value'] = $this->value;
			return false;
		}

		return true;
	}

	public function evaluateDynamicValue(Survey $survey) {}

	public function getValue(Survey $survey = null) {
		if ($survey && $this->value === 'sticky') {
			$this->value = "tail(na.omit({$survey->results_table}\${$this->name}),1)";
		}
		return trim($this->value);
	}

	/**
	 * Set the visibility of an item based on show-if results returned from opencpu
	 * $showif_result Can be an array or an interger value returned by ocpu. If a non-empty array then $showif_result[0] can have the following values
	 * - NULL if the variable in $showif is Not Avaliable,
	 * - TRUE if it avalaible and true,
	 * - FALSE if it avalaible and not true
	 * - An empty array if a problem occured with opencpu
	 *
	 * @param array|int $showif_result
	 * @return null
	 */
	public function setVisibility($showif_result) {
		if (!$showif_result) {
			return true;
		}

		$result = true;
		if (is_array($showif_result) && array_key_exists(0, $showif_result)) {
			$result = $showif_result[0];
		} elseif ($showif_result === array()) {
			notify_user_error("You made a mistake, writing a showif <code class='r hljs'>" . $this->showif . "</code> that returns an element of length 0. The most common reason for this is to e.g. refer to data that does not exist. Valid return values for a showif are TRUE, FALSE and NULL/NA.", " There are programming problems in this survey.");
			$this->alwaysInvalid();
			$this->error = _('Incorrectly defined showif.');
		}

		if (!$result) {
			$this->hide();
			$this->probably_render = false;
		}
		// null means we can't determine clearly if item should be visible or not
		if ($result === null) {
			$this->probably_render = true;
		}
		return $result;
	}

	/**
	 * Set the dynamic value computed on opencpu
	 *
	 * @param mixed $value Value
	 * @return null
	 */
	public function setDynamicValue($value) {
		if (!$value) {
			return;
		}

		$currentValue = $this->getValue();
		if ($value === array()) {
			notify_user_error("You made a mistake, writing a dynamic value <code class='r hljs'>" . h($currentValue) . "</code> that returns an element of length 0. The most common reason for this is to e.g. refer to data that does not exist, e.g. misspell an item. Valid values need to have a length of one.", " There are programming problems related to zero-length dynamic values in this survey.");
			$this->openCPU_errors[$value] = _('Incorrectly defined value (zero length).');
			$this->alwaysInvalid();
			$value = null;
		} elseif ($value === null) {
			notify_user_error("You made a mistake, writing a dynamic value <code class='r hljs'>" . h($currentValue) . "</code> that returns NA (missing). The most common reason for this is to e.g. refer to data that is not yet set, i.e. referring to questions that haven't been answered yet. To circumvent this, add a showif to your item, checking whether the item is answered yet using is.na(). Valid values need to have a length of one.", " There are programming problems related to null dynamic values in this survey.");
			$this->openCPU_errors[$value] = _('Incorrectly defined value (null).');
			$this->alwaysInvalid();
		} elseif (is_array($value) && array_key_exists(0, $value)) {
			$value = $value[0];
		}

		$this->input_attributes['value'] = $value;
	}
	
	public function getComputedValue() {
		if(isset($this->input_attributes['value'])) {
			return $this->input_attributes['value'];
		} else {
			return null;
		}
	}

	/**
	 * Says if an item is visible or not. An item is visible if:
	 * - It's hidden property is FALSE OR
	 * - It's state cannot be determined at time of rendering
	 *
	 * @return boolean
	 */
	public function isRendered() {
		return $this->requiresUserInput() && (!$this->hidden || $this->probably_render);
	}
	
	/**
	 * Says if an item requires user input.
	 *
	 * @return boolean
	 */
	public function requiresUserInput() {
		return !$this->no_user_input_required;
	}

	/**
	 * Is an element hidden in DOM but rendered?
	 *
	 * @return boolean
	 */
	public function isHiddenButRendered() {
		return $this->hidden && $this->probably_render;
	}

	public function setChoices($choices) {
		$this->choices = $choices;
	}

	public function getReply($reply) {
		return $reply;
	}

}

class Item_text extends Item {

	public $type = 'text';
	public $input_attributes = array('type' => 'text');
	public $mysql_field = 'TEXT DEFAULT NULL';

	protected function setMoreOptions() {
		if (is_array($this->type_options_array) && count($this->type_options_array) == 1) {
			$val = trim(current($this->type_options_array));
			if (is_numeric($val)) {
				$this->input_attributes['maxlength'] = (int) $val;
			} else if (trim(current($this->type_options_array))) {
				$this->input_attributes['pattern'] = trim(current($this->type_options_array));
			}
		}
		$this->classes_input[] = 'form-control';
	}

	public function validateInput($reply) {
		if (isset($this->input_attributes['maxlength']) && $this->input_attributes['maxlength'] > 0 && strlen($reply) > $this->input_attributes['maxlength']) { // verify maximum length 
			$this->error = __("You can't use that many characters. The maximum is %d", $this->input_attributes['maxlength']);
		}
		return parent::validateInput($reply);
	}

}

class Item_textarea extends Item {

	public $type = 'textarea';
	public $mysql_field = 'TEXT DEFAULT NULL'; // change to mediumtext to get 64KiB to 16MiB

	protected function setMoreOptions() {
		$this->classes_input[] = 'form-control';
	}

	protected function render_input() {
		if ($this->value_validated) {
			$this->input_attributes['value'] = $this->value_validated;
		}

		$value = array_val($this->input_attributes, 'value');
		unset($this->input_attributes['value']);
		return '<textarea ' . self::_parseAttributes($this->input_attributes, array('type')) . '>' . $value . '</textarea>';
	}

}

class Item_letters extends Item_text {

	public $type = 'letters';
	public $input_attributes = array('type' => 'text');
	public $mysql_field = 'TEXT DEFAULT NULL';

	protected function setMoreOptions() {
		$this->input_attributes['pattern'] = "[A-Za-züäöß.;,!: ]+";
		return parent::setMoreOptions();
	}

}

// todo: the min/max stuff is confusing and will fail for real big numbers
// spinbox is polyfilled in browsers that lack it 
class Item_number extends Item {

	public $type = 'number';
	public $input_attributes = array('type' => 'number', 'min' => 0, 'max' => 10000000, 'step' => 1);
	public $mysql_field = 'INT UNSIGNED DEFAULT NULL';

	protected function setMoreOptions() {
		$this->classes_input[] = 'form-control';
		if (isset($this->type_options) && trim($this->type_options) != "") {
			$this->type_options_array = explode(",", $this->type_options, 3);

			$min = trim(reset($this->type_options_array));
			if (is_numeric($min) OR $min === 'any') {
				$this->input_attributes['min'] = $min;
			}

			$max = trim(next($this->type_options_array));
			if (is_numeric($max) OR $max === 'any') {
				$this->input_attributes['max'] = $max;
			}

			$step = trim(next($this->type_options_array));
			if (is_numeric($step) OR $step === 'any') {
				$this->input_attributes['step'] = $step;
			}
		}

		$multiply = 2;
		if ($this->input_attributes['min'] < 0) :
			$this->mysql_field = str_replace("UNSIGNED", "", $this->mysql_field);
			$multiply = 1;
		endif;

		if ($this->input_attributes['step'] === 'any' OR $this->input_attributes['min'] === 'any' OR $this->input_attributes['max'] === 'any'): // is any any
			$this->mysql_field = str_replace(array("INT"), "FLOAT", $this->mysql_field); // use FLOATing point accuracy
		else:
			if (
					(abs($this->input_attributes['min']) < ($multiply * 127) ) && ( abs($this->input_attributes['max']) < ($multiply * 127) )
			):
				$this->mysql_field = preg_replace("/^INT\s/", "TINYINT ", $this->mysql_field);
			elseif (
					(abs($this->input_attributes['min']) < ($multiply * 32767) ) && ( abs($this->input_attributes['max']) < ($multiply * 32767) )
			):
				$this->mysql_field = preg_replace("/^INT\s/", "SMALLINT ", $this->mysql_field);
			elseif (
					(abs($this->input_attributes['min']) < ($multiply * 8388608) ) && ( abs($this->input_attributes['max']) < ($multiply * 8388608) )
			):
				$this->mysql_field = preg_replace("/^INT\s/", "MEDIUMINT ", $this->mysql_field);
			elseif (
					(abs($this->input_attributes['min']) < ($multiply * 2147483648) ) && ( abs($this->input_attributes['max']) < ($multiply * 2147483648) )
			):
				$this->mysql_field = str_replace("INT", "INT", $this->mysql_field);
			elseif (
					(abs($this->input_attributes['min']) < ($multiply * 9223372036854775808) ) && ( abs($this->input_attributes['max']) < ($multiply * 9223372036854775808) )
			):
				$this->mysql_field = preg_replace("/^INT\s/", "BIGINT ", $this->mysql_field);
			endif;

			// FIXME: why not use is_int()? why casting to int before strlen?
			if ((string) (int) $this->input_attributes['step'] != $this->input_attributes['step']): // step is integer?
				$before_point = max(strlen((int) $this->input_attributes['min']), strlen((int) $this->input_attributes['max'])); // use decimal with this many digits
				$after_point = strlen($this->input_attributes['step']) - 2;
				$d = $before_point + $after_point;

				$this->mysql_field = str_replace(array("TINYINT", "SMALLINT", "MEDIUMINT", "INT", "BIGINT"), "DECIMAL($d, $after_point)", $this->mysql_field);
			endif;
		endif;
	}

	public function validateInput($reply) { // fixme: input is not re-displayed after this
		$reply = trim(str_replace(",", ".", $reply));
		if (!$reply && $reply !== 0 && $this->optional) {
			return null;
		}

		if ($this->input_attributes['min'] !== 'any' && $reply < $this->input_attributes['min']) { // lower number than allowed
			$this->error = __("The minimum is %d.", $this->input_attributes['min']);
		} elseif ($this->input_attributes['max'] !== 'any' && $reply > $this->input_attributes['max']) { // larger number than allowed
			$this->error = __("The maximum is %d.", $this->input_attributes['max']);
		} elseif ($this->input_attributes['step'] !== 'any' AND
				abs(
						(round($reply / $this->input_attributes['step']) * $this->input_attributes['step'])  // divide, round and multiply by step
						- $reply // should be equal to reply
				) > 0.000000001 // with floats I have to leave a small margin of error
		) {
			$this->error = __("Numbers have to be in steps of at least %d.", $this->input_attributes['step']);
		}

		return parent::validateInput($reply);
	}

	public function getReply($reply) {
		$reply = trim(str_replace(",", ".", $reply));
		if (!$reply && $reply !== 0 && $this->optional) {
			return null;
		}
		return $reply;
	}

}

// slider, polyfilled everywhere
class Item_range extends Item_number {

	public $type = 'range';
	public $input_attributes = array('type' => 'range', 'min' => 0, 'max' => 100, 'step' => 1);
	protected $hasChoices = true;
	public $mysql_field = 'INT UNSIGNED DEFAULT NULL';

	protected function setMoreOptions() {
		$this->lower_text = current($this->choices);
		$this->upper_text = next($this->choices);
		parent::setMoreOptions();

		$this->classes_input = array_diff($this->classes_input, array('form-control'));
	}

	protected function render_input() {
		if ($this->value_validated) {
			$this->input_attributes['value'] = $this->value_validated;
		}

		return (isset($this->choices[1]) ? '<label class="pad-right keep-label">' . $this->choices[1] . ' </label>' : '') .
				'<input ' . self::_parseAttributes($this->input_attributes, array('required')) . ' />' .
				(isset($this->choices[2]) ? ' <label class="pad-left keep-label">' . $this->choices[2] . ' </label>' : '');
	}

}

// slider with ticks
class Item_range_ticks extends Item_number {

	public $type = 'range_ticks';
	public $input_attributes = array('type' => 'range', 'step' => 1);
	protected $hasChoices = true;

	protected function setMoreOptions() {
		$this->input_attributes['min'] = 0;
		$this->input_attributes['max'] = 100;
		$this->input_attributes['list'] = 'dlist' . $this->id;
		$this->input_attributes['data-range'] = '{"animate": true, "classes": "show-activevaluetooltip"}';
		$this->classes_input[] = "range-list";

		$this->classes_wrapper[] = 'range_ticks_output';

		parent::setMoreOptions();
		$this->classes_input = array_diff($this->classes_input, array('form-control'));
	}

	protected function render_input() {
		$ret = (isset($this->choices[1]) ? '<label class="pad-right keep-label">' . $this->choices[1] . ' </label> ' : '') .
				'<input ' . self::_parseAttributes($this->input_attributes, array('required')) . '>';
		$ret .= '<datalist id="dlist' . $this->id . '">
        <select class="">';
		for ($i = $this->input_attributes['min']; $i <= $this->input_attributes['max']; $i = $i + $this->input_attributes['step']):
			$ret .= '<option value="' . $i . '">' . $i . '</option>';
		endfor;
		$ret .= '
	        </select>
	    </datalist>';
		$ret .= (isset($this->choices[2]) ? ' <label class="pad-left keep-label">' . $this->choices[2] . ' </label>' : '');

		return $ret;
	}

}

// email is a special HTML5 type, validation is polyfilled in browsers that lack it
class Item_email extends Item_text {

	public $type = 'email';
	public $input_attributes = array('type' => 'email', 'maxlength' => 255);
	protected $prepend = 'fa-envelope';
	public $mysql_field = 'VARCHAR (255) DEFAULT NULL';

	public function validateInput($reply) {
		if ($this->optional && trim($reply) == ''):
			return parent::validateInput($reply);
		else:
			$reply_valid = filter_var($reply, FILTER_VALIDATE_EMAIL);
			if (!$reply_valid):
				$this->error = __('The email address %s is not valid', h($reply));
			endif;
		endif;
		return $reply_valid;
	}

}

class Item_url extends Item_text {

	public $type = 'url';
	public $input_attributes = array('type' => 'url');
	protected $prepend = 'fa-link';
	public $mysql_field = 'VARCHAR(255) DEFAULT NULL';

	public function validateInput($reply) {
		if ($this->optional && trim($reply) == ''):
			return parent::validateInput($reply);
		else:
			$reply_valid = filter_var($reply, FILTER_VALIDATE_URL);
			if (!$reply_valid):
				$this->error = __('The URL %s is not valid', h($reply));
			endif;
		endif;
		return $reply_valid;
	}

	protected function setMoreOptions() {
		$this->classes_input[] = 'form-control';
	}

}

class Item_tel extends Item_text {

	public $type = 'tel';
	public $input_attributes = array('type' => 'tel');
	protected $prepend = 'fa-phone';
	public $mysql_field = 'VARCHAR(100) DEFAULT NULL';

	protected function setMoreOptions() {
		$this->classes_input[] = 'form-control';
	}

}

class Item_cc extends Item_text {

	public $type = 'cc';
	public $input_attributes = array('type' => 'cc', "data-luhn" => "");
	protected $prepend = 'fa-credit-card';
	public $mysql_field = 'VARCHAR(255) DEFAULT NULL';

	protected function setMoreOptions() {
		$this->classes_input[] = 'form-control';
	}

}

class Item_color extends Item {

	public $type = 'color';
	public $input_attributes = array('type' => 'color');
	protected $prepend = 'fa-tint';
	public $mysql_field = 'CHAR(7) DEFAULT NULL';

	protected function setMoreOptions() {
		$this->classes_input[] = 'form-control';
	}

	public function validateInput($reply) {
		if ($this->optional && trim($reply) == ''):
			return parent::validateInput($reply);
		else:
			$reply_valid = preg_match("/^#[0-9A-Fa-f]{6}$/", $reply);
			if (!$reply_valid):
				$this->error = __('The color %s is not valid', h($reply));
			endif;
		endif;
		return $reply;
	}

}

class Item_datetime extends Item {

	public $type = 'datetime';
	public $input_attributes = array('type' => 'datetime');
	protected $prepend = 'fa-calendar';
	public $mysql_field = 'DATETIME DEFAULT NULL';
	protected $html5_date_format = 'Y-m-d\TH:i';

	protected function setMoreOptions() {
#		$this->input_attributes['step'] = 'any';
		$this->classes_input[] = 'form-control';

		if (isset($this->type_options) && trim($this->type_options) != "") {
			$this->type_options_array = explode(",", $this->type_options, 3);

			$min = trim(reset($this->type_options_array));
			if (strtotime($min)) {
				$this->input_attributes['min'] = date($this->html5_date_format, strtotime($min));
			}

			$max = trim(next($this->type_options_array));
			if (strtotime($max)) {
				$this->input_attributes['max'] = date($this->html5_date_format, strtotime($max));
			}
		}
	}

	public function validateInput($reply) {
		if (!($this->optional && $reply == '')) {

			$time_reply = strtotime($reply);
			if ($time_reply === false) {
				$this->error = _('You did not enter a valid date.');
			}

			if (isset($this->input_attributes['min']) && $time_reply < strtotime($this->input_attributes['min'])) { // lower number than allowed
				$this->error = __("The minimum is %d", $this->input_attributes['min']);
			} elseif (isset($this->input_attributes['max']) && $time_reply > strtotime($this->input_attributes['max'])) { // larger number than allowed
				$this->error = __("The maximum is %d", $this->input_attributes['max']);
			}
		}
		return parent::validateInput($reply);
	}
	public function getReply($reply) {
		$time_reply = strtotime($reply);
		return date($this->html5_date_format, $time_reply);
	}

}

// time is polyfilled, we prepended a clock
class Item_time extends Item_datetime {

	public $type = 'time';
	public $input_attributes = array('type' => 'time', 'style' => 'width:160px');
	protected $prepend = 'fa-clock-o';
	public $mysql_field = 'TIME DEFAULT NULL';
	protected $html5_date_format = 'H:i';

}

class Item_datetime_local extends Item_datetime {

	public $type = 'datetime-local';
	public $input_attributes = array('type' => 'datetime-local');

}

class Item_date extends Item_datetime {

	public $type = 'date';
	public $input_attributes = array('type' => 'date');
	protected $prepend = 'fa-calendar';
	public $mysql_field = 'DATE DEFAULT NULL';
	protected $html5_date_format = 'Y-m-d';

}

class Item_yearmonth extends Item_datetime {

	public $type = 'yearmonth';
	public $input_attributes = array('type' => 'yearmonth');
	protected $prepend = 'fa-calendar-o';
	public $mysql_field = 'DATE DEFAULT NULL';
	protected $html5_date_format = 'Y-m-01';

}

class Item_month extends Item_yearmonth {

	public $type = 'month';
	protected $prepend = 'fa-calendar-o';
	public $input_attributes = array('type' => 'month');

}

class Item_year extends Item_datetime {

	public $type = 'year';
	public $input_attributes = array('type' => 'year');
	protected $html5_date_format = 'Y';
	protected $prepend = 'fa-calendar-o';
	public $mysql_field = 'YEAR DEFAULT NULL';

}

class Item_week extends Item_datetime {

	public $type = 'week';
	public $input_attributes = array('type' => 'week');
	protected $html5_date_format = 'Y-mW';
	protected $prepend = 'fa-calendar-o';
	public $mysql_field = 'VARCHAR(9) DEFAULT NULL';

}

// notes are rendered at full width
class Item_note extends Item {

	public $type = 'note';
	public $mysql_field = null;
	public $input_attributes = array('type' => 'hidden', "value" => 1);
	public $save_in_results_table = false;

	public function setMoreOptions() {
		unset($this->input_attributes['required']);
	}

	protected function render_label() {
		return '<div class="' . implode(" ", $this->classes_label) . '">' .
				($this->error ? '<span class="label label-danger hastooltip" title="' . $this->error . '"><i class="fa fa-exclamation-triangle"></i></span> ' : '') .
				$this->label_parsed . '</div>';
	}

	public function validateInput($reply) {
		if ($reply != 1) {
			$this->error = _("You can only answer notes by viewing them.");
		}
		return $reply;
	}

}

class Item_submit extends Item {

	public $type = 'submit';
	public $input_attributes = array('type' => 'submit', 'value' => 1);
	public $mysql_field = null;
	public $save_in_results_table = false;

	protected function setMoreOptions() {
		$this->classes_input[] = 'btn';
		$this->classes_input[] = 'btn-lg';
		$this->classes_input[] = 'btn-info';
		if($this->type_options !== NULL && is_numeric($this->type_options)) {
			$this->input_attributes["data-timeout"] = $this->type_options;
			$this->classes_input[] = "submit_automatically_after_timeout";
		}
	}

	protected function render_inner() {
		$ret = '<button ' . self::_parseAttributes($this->input_attributes, array('required')) . '>' . $this->label_parsed . '</button>';
		if(isset($this->input_attributes["data-timeout"])) {
			$ret .= '<div class="white_cover"></div>';
		}
		return $ret;
	}

}

// radio buttons
class Item_mc extends Item {

	public $type = 'mc';
	public $lower_text = '';
	public $upper_text = '';
	public $input_attributes = array('type' => 'radio');
	public $mysql_field = 'TINYINT UNSIGNED DEFAULT NULL';
	protected $hasChoices = true;

	public function validateInput($reply) {
		if (!($this->optional && $reply == '') && ! empty($this->choices) && // check
				( is_string($reply) && ! in_array($reply, array_keys($this->choices)) ) OR // mc
				( is_array($reply) && ($diff = array_diff($reply, array_keys($this->choices))) && ! empty($diff) && current($diff) !== '' ) // mc_multiple
		) { // invalid multiple choice answer 
			if (isset($diff)) {
				$problem = $diff;
			} else {
				$problem = $reply;
			}

			if (is_array($problem)) {
				$problem = implode("', '", $problem);
			}

			$this->error = __("You chose an option '%s' that is not permitted.", h($problem));
		}
		return parent::validateInput($reply);
	}

	protected function render_label() {
		return '<div class="' . implode(" ", $this->classes_label) . '">' .
				($this->error ? '<span class="label label-danger hastooltip" title="' . $this->error . '"><i class="fa fa-exclamation-triangle"></i></span> ' : '') . $this->label_parsed .
				'</div>';
	}

	protected function render_input() {

		$this->splitValues();

		$ret = '<div class="mc-table'. ($this->js_hidden ? ' js_hidden' : '').'"><input ' . self::_parseAttributes($this->input_attributes, array('type', 'id', 'required')) . ' type="hidden" value="" id="item' . $this->id . '_">';

		$opt_values = array_count_values($this->choices);
		if (isset($opt_values['']) && /* $opt_values[''] > 0 && */ current($this->choices) != '') { // and the first option isn't empty
			$this->label_first = true;  // the first option label will be rendered before the radio button instead of after it.
		} else {
			$this->label_first = false;
		}

		if (mb_strpos(implode(" ", $this->classes_wrapper), 'mc-first-left') !== false) {
			$this->label_first = true;
		}
		$all_left = false;
		if (mb_strpos(implode(" ", $this->classes_wrapper), 'mc-all-left') !== false) {
			$all_left = true;
		}

		if ($this->value_validated) {
			$this->presetValues[] = $this->value_validated;
		}

		foreach ($this->choices AS $value => $option):
			// determine whether options needs to be checked
			if (in_array($value, $this->presetValues)) {
				$this->input_attributes['checked'] = true;
			} else {
				$this->input_attributes['checked'] = false;
			}
			$ret .= '<label for="item' . $this->id . '_' . $value . '">' . (($this->label_first || $all_left) ? '<span> ' . $option . ' </span>' : '') .
					'<input ' . self::_parseAttributes($this->input_attributes, array('id')) . ' value="' . $value . '" id="item' . $this->id . '_' . $value . '">' . (($this->label_first || $all_left) ? "<span>&nbsp;</span>" : '<span> ' . $option . '</span>') .
					'</label>';

			if ($this->label_first) {
				$this->label_first = false;
			}

		endforeach;

		$ret .= '</div>';
		return $ret;
	}

}

// multiple multiple choice, also checkboxes
class Item_mc_multiple extends Item_mc {

	public $type = 'mc_multiple';
	public $input_attributes = array('type' => 'checkbox');
	public $optional = 1;
	public $mysql_field = 'VARCHAR(40) DEFAULT NULL';

	protected function setMoreOptions() {
		$this->input_attributes['name'] = $this->name . '[]';
	}

	protected function chooseResultFieldBasedOnChoices() {
		$choices = array_keys($this->choices);
		$max = implode(", ", array_filter($choices));
		$maxlen = strlen($max);
		$this->mysql_field = 'VARCHAR (' . $maxlen . ') DEFAULT NULL';
	}

	protected function render_input() {
		if (!$this->optional) {
			$this->input_attributes['data-grouprequired'] = "";
		}
		$this->splitValues();

		$ret = '<div class="mc-table'. ($this->js_hidden ? ' js_hidden' : '').'"><input type="hidden" value="" id="item' . $this->id . '_" ' . self::_parseAttributes($this->input_attributes, array('id', 'type', 'required', 'data-grouprequired')) . '>';
		if (!$this->optional) {
			$ret .= '<input class="hidden" value="" id="item' . $this->id . '__" ' . self::_parseAttributes($this->input_attributes, array('id', 'required', 'class')) . '>'; // this is a kludge, but if I don't add this, checkboxes are always circled red
		}
		if ($this->value_validated) {
			$this->presetValues[] = $this->value_validated;
		}
		foreach ($this->choices AS $value => $option) {
			// determine whether options needs to be checked
			if (in_array($value, $this->presetValues)) {
				$this->input_attributes['checked'] = true;
			} else {
				$this->input_attributes['checked'] = false;
			}

			$ret .= '<label for="item' . $this->id . '_' . $value . '">' .
					'<input ' . self::_parseAttributes($this->input_attributes, array('id', 'required', 'data-grouprequired')) . ' value="' . $value . '" id="item' . $this->id . '_' . $value . '" /> ' . $option .
					'</label> ';
		}
		$ret .= '</div>';
		return $ret;
	}

	public function getReply($reply) {
		if (is_array($reply)) {
			$reply = implode(", ", array_filter($reply));
		}
		return $reply;
	}
}

// multiple multiple choice, also checkboxes
class Item_check extends Item_mc_multiple {

	public $mysql_field = 'TINYINT UNSIGNED DEFAULT NULL';
	public $choice_list = NULL;
	protected $hasChoices = false;

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->input_attributes['name'] = $this->name;
	}

	protected function render_label() {
		return '<label  for="item' . $this->id . '_1" class="' . implode(" ", $this->classes_label) . '">' .
				($this->error ? '<span class="label label-danger hastooltip" title="' . $this->error . '"><i class="fa fa-exclamation-triangle"></i></span> ' : '') . $this->label_parsed .
				'</label>';
	}

	public function validateInput($reply) {
		if (!in_array($reply, array(0, 1))) {
			$this->error = __("You chose an option '%s' that is not permitted.", h($reply));
		}
		$reply = parent::validateInput($reply);
		return $reply ? 1 : 0;
	}

	public function getReply($reply) {
		return $reply ? 1 : 0;
	}

	protected function render_input() {
		if (!empty($this->input_attributes['value']) || !empty($this->value_validated)) {
			$this->input_attributes['checked'] = true;
		} else {
			$this->input_attributes['checked'] = false;
		}
		unset($this->input_attributes['value']);

		$ret = '<input type="hidden" value="" id="item' . $this->id . '_" ' . self::_parseAttributes($this->input_attributes, array('id', 'type', 'required')) . '>
			<label class="'. ($this->js_hidden ? ' js_hidden' : '').'" for="item' . $this->id . '_1"><input ' . self::_parseAttributes($this->input_attributes, array('id')) . ' value="1" id="item' . $this->id . '_1"></label>';
		return $ret;
	}

}

// dropdown select, choose one
class Item_select_one extends Item {

	public $type = 'select';
	public $mysql_field = 'TEXT DEFAULT NULL';
	public $input_attributes = array('type' => 'select');
	protected $hasChoices = true;

	protected function setMoreOptions() {
		$this->classes_input[] = "form-control";
	}

	protected function render_input() {
		$this->splitValues();
		$ret = '<input type="hidden" value="" id="item' . $this->id . '_" ' . self::_parseAttributes($this->input_attributes, array('id', 'type', 'required','multiple')) . '>';
		$ret .= '<select ' . self::_parseAttributes($this->input_attributes, array('type')) . '>';

		if (!isset($this->input_attributes['multiple'])) {
			$ret .= '<option value="">&nbsp;</option>';
		}

		if ($this->value_validated) {
			$this->presetValues[] = $this->value_validated;
		}

		// Hack to split choices if comma separated and have only one element
		// ASSUMPTION: choices are not suppose to have commas (weirdo)
		$choice = current($this->choices);
		if (count($this->choices) == 1 && strpos($choice, ',') !== false) {
			$choices = explode(',', $choice);
			$this->choices = array_combine($choices, $choices);
		}

		foreach ($this->choices as $value => $option):
			// determine whether options needs to be checked
			$selected = '';
			if (in_array($value, $this->presetValues)) {
				$selected = ' selected="selected"';
			}
			$ret .= '<option value="' . $value . '"' . $selected . '>' . $option . '</option>';
		endforeach;

		$ret .= '</select>';
		return $ret;
	}

	protected function chooseResultFieldBasedOnChoices() {
		if (count($this->choices) == count(array_filter($this->choices, 'is_numeric'))) {
			return parent::chooseResultFieldBasedOnChoices();
		}
	}

}

// dropdown select, choose multiple
class Item_select_multiple extends Item_select_one {

	public $type = 'select_multiple';
	public $mysql_field = 'VARCHAR (40) DEFAULT NULL';

	protected function chooseResultFieldBasedOnChoices() {
		$choices = array_keys($this->choices);
		$max = implode(", ", array_filter($choices));
		$maxlen = strlen($max);
		$this->mysql_field = 'VARCHAR (' . $maxlen . ') DEFAULT NULL';
	}

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->input_attributes['multiple'] = true;
		$this->input_attributes['name'] = $this->name . '[]';
	}
	public function getReply($reply) {
		if (is_array($reply)) {
			$reply = implode(", ", array_filter($reply));
		}
		return $reply;
	}
}

// dropdown select, choose one
class Item_select_or_add_one extends Item {

	public $type = 'select_or_add_one';
	public $mysql_field = 'TEXT DEFAULT NULL';
	public $input_attributes = array('type' => 'text');
	protected $hasChoices = true;
	private $maxSelect = 0;
	private $maxType = 255;

	protected function setMoreOptions() {
		parent::setMoreOptions();

		if (isset($this->type_options) && trim($this->type_options) != "") {
			$this->type_options_array = explode(",", $this->type_options, 3);

			$this->maxType = trim(reset($this->type_options_array));
			if (!is_numeric($this->maxType)) {
				$this->maxType = 255;
			}

			if (count($this->type_options_array) > 1) {
				$this->maxSelect = trim(next($this->type_options_array));
			}
			if (!isset($this->maxSelect) OR ! is_numeric($this->maxSelect)) {
				$this->maxSelect = 0;
			}
		}

		$this->classes_input[] = 'select2add';
		$this->classes_input[] = 'form-control';
	}
	public function setChoices($choices) {
		$this->choices = $choices;
		// Hack to split choices if comma separated and have only one element
		// ASSUMPTION: choices are not suppose to have commas (weirdo)
		$choice = current($this->choices);
		if (count($this->choices) == 1 && strpos($choice, ',') !== false) {
			$this->choices = explode(',', $choice);
		}
		$for_select2 = array();

		foreach ($this->choices AS $option) {
			$for_select2[] = array('id' => $option, 'text' => $option);
		}

		$this->input_attributes['data-select2add'] = json_encode($for_select2, JSON_UNESCAPED_UNICODE);
		$this->input_attributes['data-select2maximumSelectionSize'] = (int) $this->maxSelect;
		$this->input_attributes['data-select2maximumInputLength'] = (int) $this->maxType;
	}

	protected function chooseResultFieldBasedOnChoices() {} // override parent

}

class Item_select_or_add_multiple extends Item_select_or_add_one {

	public $type = 'select_or_add_multiple';
	public $mysql_field = 'TEXT DEFAULT NULL';
	public $input_attributes = array('type' => 'text');

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->text_choices = true;
		$this->input_attributes['data-select2multiple'] = 1;
	}

	public function getReply($reply) {
		if (is_array($reply)) {
			$reply = implode("\n", array_filter($reply));
		}
		return $reply;
	}
	protected function chooseResultFieldBasedOnChoices() { // override parent
	}
}

// dropdown select, choose multiple
class Item_mc_button extends Item_mc {

	public $mysql_field = 'TINYINT UNSIGNED DEFAULT NULL';
	protected $js_hidden = true;
	
	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->classes_wrapper[] = 'btn-radio';
	}

	protected function render_appended() {
		$ret = '<div class="btn-group js_shown">';
		foreach ($this->choices AS $value => $option):
			$ret .= '<button type="button" class="btn" data-for="item' . $this->id . '_' . $value . '">' .
					"<span class='btn_value'>$value</span><span class='btn_label'>$option</span>" .
					'</button>';
		endforeach;
		$ret .= '</div>';

		return $ret;
	}

}

// dropdown select, choose multiple
class Item_rating_button extends Item_mc_button {

	public $mysql_field = 'SMALLINT DEFAULT NULL';
	public $type = "rating_button";
	private $step = 1;
	private $lower_limit = 1;
	private $upper_limit = 5;

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->step = 1;
		$this->lower_limit = 1;
		$this->upper_limit = 5;

		if (isset($this->type_options_array) && is_array($this->type_options_array)) {
			if (count($this->type_options_array) == 1) {
				$this->type_options_array = explode(",", current($this->type_options_array));
			}

			if (count($this->type_options_array) == 1) {
				$this->upper_limit = (int) trim($this->type_options_array[0]);
			} elseif (count($this->type_options_array) == 2) {
				$this->lower_limit = (int) trim($this->type_options_array[0]);
				$this->upper_limit = (int) trim($this->type_options_array[1]);
			} elseif (count($this->type_options_array) == 3) {
				$this->lower_limit = (int) trim($this->type_options_array[0]);
				$this->upper_limit = (int) trim($this->type_options_array[1]);
				$this->step = (int) trim($this->type_options_array[2]);
			}
		}

		/**
		 * For obvious reason $this->choices can still be empty at this point (if user doesn't have choice1, choice2 columns but used a choice_list instead)
		 * So get labels from choice list which should be gotten from last item in options array
		 */
		// force step to be a non-zero positive number less than or equal to upper limit
		if ($this->step <= 0 || $this->step > $this->upper_limit) {
			$this->step = $this->upper_limit;
		}

		$choices = range($this->lower_limit, $this->upper_limit, $this->step);
		$this->choices = array_combine($choices, $choices);
	}
	public function setChoices($choices) {
		$this->lower_text = current($choices);
		$this->upper_text = next($choices);
	}
	protected function render_input() {

		$this->splitValues();


		$ret = '<input ' . self::_parseAttributes($this->input_attributes, array('type', 'id', 'required')) . ' type="hidden" value="" id="item' . $this->id . '_">';

		$ret .= "<label class='keep-label'>{$this->lower_text} </label> ";
		
		$ret .= '<span class="js_hidden">';
		if ($this->value_validated) {
			$this->presetValues[] = $this->value_validated;
		}
		foreach ($this->choices AS $option):
			// determine whether options needs to be checked
			if (in_array($option, $this->presetValues)) {
				$this->input_attributes['checked'] = true;
			} else {
				$this->input_attributes['checked'] = false;
			}

			$ret .= '<label for="item' . $this->id . '_' . $option . '">' .
					'<input ' . self::_parseAttributes($this->input_attributes, array('id')) . ' value="' . $option . '" id="item' . $this->id . '_' . $option . '">' . $option .
					'</label>';
		endforeach;
		$ret .= '</span>';

		return $ret;
	}

	protected function render_appended() {
		$ret = parent::render_appended();
		$ret .= " <label class='keep-label'> {$this->upper_text}</label>";

		return $ret;
	}

}

class Item_mc_multiple_button extends Item_mc_multiple {

	public $mysql_field = 'VARCHAR (40) DEFAULT NULL';
	public $type = "mc_multiple_button";
	protected $js_hidden = true;

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->classes_wrapper[] = 'btn-checkbox';
	}

	protected function render_appended() {
		$ret = '<div class="btn-group js_shown">';
		foreach ($this->choices AS $value => $option):
			$ret .= '<button type="button" class="btn" data-for="item' . $this->id . '_' . $value . '">' .
					"<span class='btn_value'>$value</span><span class='btn_label'>$option</span>" .
					'</button>';
		endforeach;
		$ret .= '</div>';

		return $ret;
	}

}

class Item_check_button extends Item_check {

	public $mysql_field = 'TINYINT UNSIGNED DEFAULT NULL';
	protected $js_hidden = true;

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->classes_wrapper[] = 'btn-check';
	}

	protected function render_label() {
		return '<label class="' . implode(" ", $this->classes_label) . '">' .
				($this->error ? '<span class="label label-danger hastooltip" title="' . $this->error . '"><i class="fa fa-exclamation-triangle"></i></span> ' : '') . $this->label_parsed .
				'</label>';
	}

	protected function render_appended() {
		$ret = '<div class="btn-group js_shown">
					<button type="button" class="btn" data-for="item' . $this->id . '_1"><i class="fa fa-2x fa-fw"></i></button>' .
				'</div>';
		return $ret;
	}

}

class Item_sex extends Item_mc_button {

	public $mysql_field = 'TINYINT UNSIGNED DEFAULT NULL';

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->setChoices(array());
	}
	public function setChoices($choices) {
		$this->choices = array(1 => '♂', 2 => '♀');
	}

}

class Item_geopoint extends Item {

	public $type = 'geopoint';
	public $input_attributes = array('type' => 'text', 'readonly');
	protected $append = true;
	public $mysql_field = 'TEXT DEFAULT NULL';

	protected function setMoreOptions() {
		$this->input_attributes['name'] = $this->name . '[]';
		$this->classes_input[] = "form-control";
	}

	public function getReply($reply) {
		if (is_array($reply)):
			$reply = array_filter($reply);
			$reply = end($reply);
		endif;
		return $reply;
	}

	protected function render_prepended() {
		return '
			<input type="hidden" name="' . $this->name . '" value="">
			<div class="input-group">';
	}

	protected function render_appended() {
		$ret = '
			<span class="input-group-btn hidden">
				<button type="button" class="btn btn-default geolocator item' . $this->id . '">
					<i class="fa fa-location-arrow fa-fw"></i>
				</button>
			</span>
			</div>
			';
		return $ret;
	}

}

class Item_random extends Item_number {

	public $type = 'random';
	public $input_attributes = array('type' => 'hidden', 'step' => 1);
	public $mysql_field = 'INT UNSIGNED DEFAULT NULL';
	public $no_user_input_required = true;

	protected function setMoreOptions() {
		parent::setMoreOptions();
		$this->input_attributes['value'] = $this->validateInput();
	}

	public function validateInput($reply = '') {
		if (isset($this->input_attributes['min']) && isset($this->input_attributes['max'])) { // both limits specified
			$reply = mt_rand($this->input_attributes['min'], $this->input_attributes['max']);
		} elseif (!isset($this->input_attributes['min']) && ! isset($this->input_attributes['max'])) { // neither limit specified
			$reply = mt_rand(0, 1);
		} else {
			$this->error = __("Both random minimum and maximum need to be specified");
		}
		return $reply;
	}
	public function getReply($reply) {
		return $this->input_attributes['value'];
	}

}

class Item_calculate extends Item {

	public $type = 'calculate';
	public $input_attributes = array('type' => 'hidden');
	public $no_user_input_required = true;
	public $mysql_field = 'TEXT DEFAULT NULL';

	public function render() {
		return $this->render_input();
	}

}

class Item_opencpu_session extends Item {

	public $type = 'opencpu_session';
	public $input_attributes = array('type' => 'hidden');
	public $no_user_input_required = true;
	public $mysql_field = 'VARCHAR (255) DEFAULT NULL';

	public function render() {
		return $this->render_input();
	}

	public function evaluateDynamicValue(Survey $survey) {
		$value = $this->getValue();
		$variables = $survey->getUserDataInRun($value, $survey->name);
		$ocpu_session = opencpu_evaluate($value, $variables, 'json', $survey->name, true);
		if ($ocpu_session && !$ocpu_session->hasError()) {
			$this->value = $ocpu_session->getLocation();
			$this->input_attributes['value'] = $this->value;
			return $this->value;
		}
		// @todo Add error reporting if $ocpu_session has an error
		return null;
	}

}

class Item_ip extends Item {

	public $type = 'ip';
	public $input_attributes = array('type' => 'hidden');
	public $mysql_field = 'VARCHAR (46) DEFAULT NULL';
	public $no_user_input_required = true;
	public $optional = 1;	

	protected function setMoreOptions() {
		$this->input_attributes['value'] = $_SERVER["REMOTE_ADDR"];
	}

	public function getReply($reply) {
		return $_SERVER["REMOTE_ADDR"];
	}

	public function render() {
		return $this->render_input();
	}

}

class Item_referrer extends Item {

	public $type = 'referrer';
	public $input_attributes = array('type' => 'hidden');
	public $mysql_field = 'TEXT DEFAULT NULL';
	public $no_user_input_required = true;
	public $optional = 1;

	protected function setMoreOptions() {
		global $site;

		$this->input_attributes['value'] = $site->last_outside_referrer;
	}

	public function validateInput($reply) {
		return $reply;
	}
	public function getReply($reply) {
		global $site;
		return $site->last_outside_referrer;
	}

	public function render() {
		return $this->render_input();
	}

}

class Item_server extends Item {

	public $type = 'server';
	public $input_attributes = array('type' => 'hidden');
	private $get_var = 'HTTP_USER_AGENT';
	public $mysql_field = 'TEXT DEFAULT NULL';
	public $no_user_input_required = true;
	public $optional = 1;
	

	protected function setMoreOptions() {
		if (isset($this->type_options_array) && is_array($this->type_options_array)) {
			if (count($this->type_options_array) == 1) {
				$this->get_var = trim(current($this->type_options_array));
			}
		}
		$this->input_attributes['value'] = array_val($_SERVER, $this->get_var);
	}

	public function getReply($reply) {
		return $this->input_attributes['value'];
	}

	public function validate() {
		if (!in_array($this->get_var, array(
					'HTTP_USER_AGENT',
					'HTTP_ACCEPT',
					'HTTP_ACCEPT_CHARSET',
					'HTTP_ACCEPT_ENCODING',
					'HTTP_ACCEPT_LANGUAGE',
					'HTTP_CONNECTION',
					'HTTP_HOST',
					'QUERY_STRING',
					'REQUEST_TIME',
					'REQUEST_TIME_FLOAT'
				))) {
			$this->val_errors[] = __('The server variable %s with the value %s cannot be saved', $this->name, $this->get_var);
			return parent::validate();
		}

		return $this->val_errors;
	}

	public function render() {
		return $this->render_input();
	}

}
class Item_browser extends Item_server {
}

class Item_get extends Item {

	public $type = 'get';
	public $input_attributes = array('type' => 'hidden');
	public $no_user_input_required = true;
	public $probably_render = true;
	public $mysql_field = 'TEXT DEFAULT NULL';
	protected $hasChoices = false;
	private $get_var = 'referred_by';
	

	protected function setMoreOptions() {
		if (isset($this->type_options_array) && is_array($this->type_options_array)) {
			if (count($this->type_options_array) == 1) {
				$this->get_var = trim(current($this->type_options_array));
			}
		}

		$this->input_attributes['value'] = '';
		$request = new Request($_GET);
		if (($value = $request->getParam($this->get_var)) !== null) {
			$this->input_attributes['value'] = $value;
		}
	}

	public function validate() {
		if (!preg_match('/^[A-Za-z0-9_]+$/', $this->get_var)):
			$this->val_errors[] = __('Problem with variable %s "get %s". The part after get can only contain a-Z0-9 and the underscore.', $this->name, $this->get_var);
		endif;
		return parent::validate();
	}

	public function render() {
		return $this->render_input();
	}

	public function needsDynamicValue() {
		return false;
	}

}

class Item_choose_two_weekdays extends Item_mc_multiple {

	protected function setMoreOptions() {
		$this->optional = 0;
		$this->classes_input[] = 'choose2days';
		$this->input_attributes['name'] = $this->name . '[]';
	}

}

class Item_timezone extends Item_select_one {

	public $mysql_field = 'VARCHAR(255)';
	public $choice_list = '*';

	protected function chooseResultFieldBasedOnChoices() {
	}

	protected function setMoreOptions() {
		$this->classes_input[] = 'select2zone';

		parent::setMoreOptions();
		$this->setChoices(array());
	}
	public function setChoices($choices) {
		$zonenames = timezone_identifiers_list();
		asort($zonenames);
		$zones = array();
		$offsets = array();
		foreach ($zonenames AS $zonename):
			$zone = timezone_open($zonename);
			$offsets[] = timezone_offset_get($zone, date_create());
			$zones[] = str_replace("/", " - ", str_replace("_", " ", $zonename));
		endforeach;
		$this->choices = $zones;
		$this->offsets = $offsets;
	}

	protected function render_input() {
		$ret = '<select ' . self::_parseAttributes($this->input_attributes, array('type')) . '>';

		if (!isset($this->input_attributes['multiple'])) {
			$ret .= '<option value="">&nbsp;</option>';
		}

		foreach ($this->choices AS $value => $option):
			$selected = array('selected' => $this->isSelectedOptionValue($value, $this->value_validated));
			$ret .= '<option value="' . $option . '" ' . self::_parseAttributes($selected, array('type')) . '>' . $option . '</option>';
		endforeach;

		$ret .= '</select>';

		return $ret;
	}

}

class Item_mc_heading extends Item_mc {

	public $type = 'mc_heading';
	public $mysql_field = null;
	public $save_in_results_table = false;

	protected function setMoreOptions() {
		$this->input_attributes['disabled'] = 'disabled';
	}

	protected function render_label() {
		return '<div class="' . implode(" ", $this->classes_label) . '">' .
				($this->error ? '<span class="label label-danger hastooltip" title="' . $this->error . '"><i class="fa fa-exclamation-triangle"></i></span> ' : '') . $this->label_parsed .
				'<input type="hidden" name="' . $this->name . '" value="1"></div>
		';
	}

	protected function render_input() {
		$ret = '<div class="mc-table">';
		$this->input_attributes['type'] = 'radio';
		$opt_values = array_count_values($this->choices);
		if (isset($opt_values['']) && /* // if there are empty options $opt_values[''] > 0 && */ current($this->choices) != '') { // and the first option isn't empty
			$this->label_first = true;  // the first option label will be rendered before the radio button instead of after it.
		} else {
			$this->label_first = false;
		}

		if (mb_strpos(implode(" ", $this->classes_wrapper), 'mc_first_left') !== false) {
			$this->label_first = true;
		}
		$all_left = false;
		if (mb_strpos(implode(" ", $this->classes_wrapper), 'mc_all_left') !== false) {
			$all_left = true;
		}

		foreach ($this->choices AS $value => $option):
			$this->input_attributes['selected'] = $this->isSelectedOptionValue($value, $this->value_validated);
			$ret .= '<label for="item' . $this->id . '_' . $value . '">' . (($this->label_first || $all_left) ? $option . '&nbsp;' : '') .
					'<input ' . self::_parseAttributes($this->input_attributes, array('id','required')) . ' value="' . $value . '" id="item' . $this->id . '_' . $value . '">' . (($this->label_first || $all_left) ? "&nbsp;" : ' ' . $option) .
					'</label>';

			if ($this->label_first) {
				$this->label_first = false;
			}

		endforeach;

		$ret .= '</div>';

		return $ret;
	}

}

/*
 * todo: item - rank / sortable
 * todo: item - facebook connect?
 * todo: captcha items

 */

class Item_file extends Item {

	public $type = 'file';
	public $input_attributes = array('type' => 'file', 'accept' => "image/*,video/*,audio/*,text/*;capture=camera");
	public $mysql_field = 'VARCHAR(1000) DEFAULT NULL';
	protected $file_endings = array(
		'image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif', 'image/tiff' => '.tif',
		'video/mpeg' => '.mpg', 'video/quicktime' => '.mov', 'video/x-flv' => '.flv', 'video/x-f4v' => '.f4v', 'video/x-msvideo' => '.avi',
		'audio/mpeg' => '.mp3',
		'text/csv' => '.csv', 'text/css' => '.css', 'text/tab-separated-values' => '.tsv', 'text/plain' => '.txt'
	);
	protected $embed_html = '%s';
	protected $max_size = 16777219;

	protected function setMoreOptions() {
		if (is_array($this->type_options_array) && count($this->type_options_array) == 1) {
			$val = (int) trim(current($this->type_options_array));
			if (is_numeric($val)) {
				$bytes = $val * 1048576; # size is provided in MB
				$this->max_size = $bytes;
			}
		}
	}

	public function validateInput($reply) {
		if ($reply['error'] === 0) { // verify maximum length and no errors
			if (filesize($reply['tmp_name']) < $this->max_size) {
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime = $finfo->file($reply['tmp_name']);
				$new_file_name = crypto_token(66) . $this->file_endings[$mime];
				if (!in_array($mime, array_keys($this->file_endings))) {
					$this->error = 'Files of type' . $mime . ' are not allowed to be uploaded.';
				} elseif (move_uploaded_file($reply['tmp_name'], INCLUDE_ROOT . 'webroot/assets/tmp/' . $new_file_name)) {
					$public_path = WEBROOT . 'assets/tmp/' . $new_file_name;
					$reply = __($this->embed_html, $public_path);
				} else {
					$this->error = __("Unable to save uploaded file");
				}
			} else {
				$this->error = __("This file is too big the maximum is %d megabytes.", round($this->max_size / 1048576, 2));
				$reply = null;
			}
		} else {
			$this->error = "Error uploading file";
			$reply = null;
		}

		$this->reply = parent::validateInput($reply);
		return $this->reply;
	}

	public function getReply($reply) {
		return $this->reply;
	}
}

class Item_image extends Item_file {

	public $type = 'image';
	public $input_attributes = array('type' => 'file', 'accept' => "image/*;capture=camera");
	public $mysql_field = 'VARCHAR(1000) DEFAULT NULL';
	protected $file_endings = array('image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif', 'image/tiff' => '.tif');
	protected $embed_html = '<img src="%s">';
	protected $max_size = 16777219;

}

class Item_blank extends Item_text {

	public $type = 'blank';
	public $mysql_field = 'TEXT DEFAULT NULL';

	public function render() {
		if ($this->error) {
			$this->classes_wrapper[] = "has-error";
		}

		return '<div class="' . implode(" ", $this->classes_wrapper) . '"' . ($this->data_showif ? ' data-showif="' . h($this->js_showif) . '"' : '') . '>' . $this->label_parsed . '</div>';
	}
}

class Item_hidden extends Item {

	public $type = 'hidden';
	public $mysql_field = 'TEXT DEFAULT NULL';
	public $input_attributes = array('type' => 'hidden');
	public $optional = 1;
	public function setMoreOptions() {
		unset($this->input_attributes["required"]);
		$this->classes_wrapper[] = "hidden";
	}
	public function render_inner() {
		return $this->render_input();
	}
}

class Item_block extends Item_note {

	public $type = 'block';
	public $input_attributes = array('type' => 'checkbox');
	
	public function setMoreOptions() {
		$this->classes_wrapper[] = "alert alert-danger";
	}
}


class HTML_element {

	// from CakePHP
	/**
	 * Minimized attributes
	 *
	 * @var array
	 */
	protected $_minimizedAttributes = array(
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected',
		'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize',
		'autoplay', 'controls', 'loop', 'muted', 'required', 'novalidate', 'formnovalidate'
	);

	/**
	 * Format to attribute
	 *
	 * @var string
	 */
	protected $_attributeFormat = '%s="%s"';

	/**
	 * Format to attribute
	 *
	 * @var string
	 */
	protected $_minimizedAttributeFormat = '%s="%s"';

	/**
	 * Returns a space-delimited string with items of the $options array. If a key
	 * of $options array happens to be one of those listed in `Helper::$_minimizedAttributes`
	 *
	 * And its value is one of:
	 *
	 * - '1' (string)
	 * - 1 (integer)
	 * - true (boolean)
	 * - 'true' (string)
	 *
	 * Then the value will be reset to be identical with key's name.
	 * If the value is not one of these 3, the parameter is not output.
	 *
	 * 'escape' is a special option in that it controls the conversion of
	 *  attributes to their html-entity encoded equivalents. Set to false to disable html-encoding.
	 *
	 * If value for any option key is set to `null` or `false`, that option will be excluded from output.
	 *
	 * @param array $options Array of options.
	 * @param array $exclude Array of options to be excluded, the options here will not be part of the return.
	 * @param string $insertBefore String to be inserted before options.
	 * @param string $insertAfter String to be inserted after options.
	 * @return string Composed attributes.
	 * @deprecated This method will be moved to HtmlHelper in 3.0
	 */
	protected function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (!is_string($options)) {
			$options = (array) $options + array('escape' => true);

			if (!is_array($exclude)) {
				$exclude = array();
			}

			$exclude = array('escape' => true) + array_flip($exclude);
			$escape = $options['escape'];
			$attributes = array();

			foreach ($options as $key => $value) {
				if (!isset($exclude[$key]) && $value !== false && $value !== null) {
					$attributes[] = $this->_formatAttribute($key, $value, $escape);
				}
			}
			$out = implode(' ', $attributes);
		} else {
			$out = $options;
		}
		return $out ? $insertBefore . $out . $insertAfter : '';
	}

	/**
	 * Formats an individual attribute, and returns the string value of the composed attribute.
	 * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
	 *
	 * @param string $key The name of the attribute to create
	 * @param string $value The value of the attribute to create.
	 * @param boolean $escape Define if the value must be escaped
	 * @return string The composed attribute.
	 * @deprecated This method will be moved to HtmlHelper in 3.0
	 */
	protected function _formatAttribute($key, $value, $escape = true) {
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		if (is_numeric($key)) {
			return sprintf($this->_minimizedAttributeFormat, $value, $value);
		}
		$truthy = array(1, '1', true, 'true', $key);
		$isMinimized = in_array($key, $this->_minimizedAttributes);
		if ($isMinimized && in_array($value, $truthy, true)) {
			return sprintf($this->_minimizedAttributeFormat, $key, $key);
		}
		if ($isMinimized) {
			return '';
		}
		return sprintf($this->_attributeFormat, $key, ($escape ? h($value) : $value));
	}

	protected function isSelectedOptionValue($expected = null, $actual = null) {
		if ($expected !== null && $actual !== null && $expected == $actual) {
			return true;
		}
		return false;
	}

}
