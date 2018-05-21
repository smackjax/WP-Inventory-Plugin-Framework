<?php
    $new_inventory_vals = [
        'singular_name' => /*item*/,
        'plural_name' =>  /*items*/,
        'db_table_name' => /*new_table_name*/,
        'menu_btn_text' => /*items*/,

        /* Optional
        'bulk_actions' => [
            'some_action_name' => [
                'label'=>'Add to Tap',
                'db_values' => [ 
                    'db_column'=> 'new value'
                ]
            ],
        ],
        */

        'inventory_columns' => [ // <-- don't change this key
            'text_input_col_name' => [
                'default_value' => 'New default value',
                // Editing input
                'input'=>[
                    'type' => 'text',
                    'label'=> 'Input label',
                    'required'=>'true',
                    'attributes' => [
                        'placeholder' => 'Input placeholder'
                    ]
                ],
                // Column behaviour in inventory table
                'table' => [
                    // If 'display_as' isn't set, nothing here matters
                    'display_as' => 'Text Input Label',
                    // All of these are optional
                    'sortable' => true,
                    'display_row_actions'=> true,
                ],
                'data' => [
                    'sql_type' =>      /*STRING Valid SQL data type(VARCHAR, TINYTEXT, BIT, etc...)*/,
                    'parse_type' =>    /*STRING %s, %d, %f*/
                ]
            ],

            'select_input_col_name' => [
                'default_value' => '1',
                'input'=>[
                    'type' => 'select',
                    'label'=> 'Input Label',
                    'required'=>'true',
                    'options' => [
                        '0' => 'No',
                        '1' => 'Yes'
                    ]
                ],
                // Column behaviour in inventory table
                'table' => [
                    'display_as' => 'A Select Column',
                    // All of these are optional
                    'default_sort' => true,
                    'sortable' => true,
                    'hidden'=> false,
                    'output_function' => function($item, $col_name){
                        // return '<span>Some html text</span>'
                    }
                ],
                
                'data' => [
                    'sql_type' =>      /*STRING Valid SQL data type(VARCHAR, TINYTEXT, BIT, etc...)*/,
                    'parse_type' =>    /*STRING %s, %d, %f*/
                ]
            ],
        ]
    ];
    
    // Queues everything
    global $inventory;
    $new_inventory_obj = new Inventory($new_inventory_vals);
    $inventory->add_inventory_object($new_inventory_obj);
?>