<?php 
class EditInventoryItemPage {
    private $singular;
    private $inputs;
    private $inventory_crud;

    private function extract_inputs(){}
    private function load_inventory_item(){}
    private function save_item_vals(){
        // Save input vals
            // Parse input through type(getInputType())
            // Default to text input with %s parse
            // Use 'order' of input option
            // Add any 'class' on input options to default input class
            // Look for 'default_value'
            // sort inputs by order
            // save to $this->inputs
    }


    public function __construct($str_values, $inventory_columns, $inventory_crud){
        
        // Holds any inputs with 'order' set
        $input_order = [];
        // Holds all inputs without 'order'
        $other_inputs = [];
        // Pull inputs with order set
        foreach($inventory_columns as $col_name => $col_vals){
            $col_input = $col_vals['input'];
            $col_input['name'] = $col_name;
            if(!empty($col_vals['default_value'])){
                $col_input['default_value'] = $col_vals['default_value'];
            }

            if(!empty($col_input['order'])){ 
                $position = $col_input['order'];
                $input_order[$position] = $col_input;
                return;
            }
            $other_inputs[] = $col_input;
        }
        
        $inputs = [];
        for($i = 0; $i < count($inventory_columns); $i++){
            if(!empty($input_order[$i])){
                $inputs[] = $input_order[i];
            } else {
                $inputs[] = array_shift($other_inputs);
            }
        }

        // TODO this way of doing things is prone to error if 'order' is assigned to a greater number of inputs.
        // Definitely bad since arrays are 0 based

        $this->inputs = $inputs;
        $this->inventory_crud = $inventory_crud;
        $this->singular_name = $str_values['singular_name'];
    }

    // Returns string of html attributes
    private function get_attributes($attributes){
        $attributes_str = '';
        if(!empty($attributes)){
            foreach($attributes as $attr => $attr_value){
                $attributes_str .= "$attr='$attr_value' ";
            }
        }
        return $attributes_str;
    }

    private function render_input_text($input_options, $current_val, $attributes){
        if(empty($attributes['class'])) $attributes['class'] = 'regular-text';
        $attributes['value'] = $current_val;

        $input = "
            <input
            type='text'
            id='" . $input_options['name'] ."'
            name='" . $input_options['name'] . "'
            ". $this->get_attributes($attributes) ."
            />
        ";
        return $input;
    }

    private function render_input_textarea($input_options, $current_val, $attributes){
        // Initialize(but don't overwrite) attributes
        if(empty($attributes['class'])) $attributes['class'] = 'widefat';
        if(empty($attributes['rows'])) $attributes['rows'] = '10';
        if(empty($attributes['cols'])) $attributes['cols'] = '30';

        $input = "
            <textarea
            id='" . $input_options['name'] ."'
            name='" . $input_options['name'] . "'
            " . $this->get_attributes($attributes) . "
            >$current_val</textarea>
        ";
        return $input;
    }

    private function render_input_select($input_options, $current_val, $attributes){
        // Prep for if no value passed in
        reset($input_options['options']);
        // If no default or current value from db, select first option
        $selected_val = isset($current_val) ? $current_val : key($input_options['options']);

        $input = "
                    <select
                    name='" . $input_options['name'] . "'
                    id='" . $input_options['name'] . "'
                    " . $this->get_attributes($attributes) . "
                    > 
                ";
                // Renders 'select' option elements
                foreach($input_options['options'] as $db_value => $option_label){
                    $selected = ($selected_val === "$db_value") ? "selected='selected'" : '';
                    // Add option to html
                    $input .= "
                        <option
                        value='$db_value'
                        $selected
                        >
                            $option_label
                        </option>
                    ";
                }
        $input .= '</select>';

        return $input;
    }

    private function render_input_by_type($input_options, $db_value){
        $type = $input_options['type'];

        $attributes = !empty($input_options['attributes']) ? $input_options['attributes'] : [];
        if(!empty($input_options['required']) && $input_options['required'] == true) $attributes['required'] = 'required';

        // Can't add to 'value' attributes because textarea(at least) uses value differently
        $current_val = '';
        if(isset($input_options['default_value'])) $current_val = $input_options['default_value'];
        if($db_value !== false) $current_val = $db_value;
        $escaped_val = esc_html($current_val);
        
        if($type === 'select'): 
            return $this->render_input_select($input_options, $escaped_val, $attributes);
        elseif($type === 'textarea'): 
            return $this->render_input_textarea($input_options, $escaped_val, $attributes);
        else: 
            // Default to text input
            return $this->render_input_text($input_options, $escaped_val, $attributes);
        endif;
    }

    // Render inputs by building each input type
    private function render_item_inputs( $item_db_vals){
        $inputs = $this->inputs;

        // Loop through inputs and display them
        foreach($inputs as $input_position => $input_options){
            // Get current value for this input,
            // default to empty string
            $col_name = $input_options['name'];
            $current_val =  $item_db_vals[$col_name] ?? false;

            echo "
            <tr class='" . $input_options['name'] . "'>
                <th>
                    <label for='" . $input_options['name'] . "'> " . $input_options['label'] . "</label>
                </th>
                <td>
            ";
                echo $this->render_input_by_type($input_options, $current_val);
            echo "    
                </td>
            </tr>
            ";
        }
    }



    private function render_header($is_edit){
        $singular_name = $this->singular_name;

        $handler_url = admin_url(
            sprintf("admin.php?page=%s&%s=%s&%s=%s",
            $_GET['page'], 
            Inventory::$item_action_key, 
            Inventory::$save_item_action, 
            Inventory::$ids_key,
            htmlspecialchars($_GET[Inventory::$ids_key])
        ));
        $is_edit_text = $is_edit ? 'Edit' : 'New';
        $lowercase_singular = strtolower($singular_name);
        

        echo "<div class='wrap'>
            <h1 class='wp-heading-inline'>
                $is_edit_text $lowercase_singular
            </h1> 
	
            <hr class='wp-header-end' />
            <form action='$handler_url' method='POST'>";

        $this->render_control_btns('header');

        echo "  <hr />
                <table class='form-table'>
                    <tbody>
        ";
    }
    private function render_footer(){
        echo "
                    </tbody>
                </table>";
            $this->render_control_btns('footer');
        echo "
            </form>
        </div>
        ";
    }
    
    private function render_control_btns($location){
        $base_page_url = admin_url('admin.php?page=' . $_GET['page']);
        echo "
            <input type='submit' class='button button-primary button-large' id='save-item-$location' value='Save' />
            <a href='" . $base_page_url . "' class='button button-secondary button-large' id='cancel-item-$location' >Cancel</a>
        ";
    }

    public function render(){
        $item_id = $_GET[Inventory::$ids_key];
        $is_edit = !($item_id === Inventory::$new_item_id);
        // Get's item values from database or initializes with empty array
        $item_vals = 
            $is_edit ? $this->inventory_crud->get_item_by_id($item_id) : [];

        $this->render_header($is_edit);
            $this->render_item_inputs($item_vals);
        //  $this->render_control_btns();
        $this->render_footer();
    }
}
?>