# WP Inventory Plugin Framework

## Disclaimer
**Use at your own risk**. 
I want to be clear that I love collaboration, and anyone is welcome to use this, but it's meant as a tool in an arsenal. Not a quickfix for someone who doesn't know code.  
In short, I'm not responsible if you decide to push this up on a live site with zero testing or code experience and break the install.

## First things first
#### What this is not
* This is not a plugin. You have to know at least some code to use it.
* This is not exhaustive right now. For instance, there isn't pagination on the items table page(yet).

## What it is
A way to quickly add custom SQL tables for any item, the ability to see and edit those items, and then easily query them in your templates.  
All of that is from one array and 3 lines of code. 

## What you are allowed to do with this
...pretty much anything. My goal is to make this as simple, but useful and powerful as possible. Again, I'm not trying to empower the general user. This is very dangerous if used wrong. It's for developers.  
Just don't charge money for a plugin built on this framework; that would be being a crappy person, taking credit for someone else's work.  
**Don't be a crappy person.**


## How to use
1. Rename plugin folder and entry file(if you want)
2. Change the plugin meta info in the comment at the top of your main plugin entry file
3. Duplicate and rename the inventory template file from `/custom-inventory/_template.php`
4. Customize array values in new inventory file
5. Require the new inventory file in `/custom-inventory/_index.php`
    * (Repeat 3-5 as needed for different inventory types)
6. Activate plugin from admin dash

## Getting the inventory
* The `$inventory` global stores all the inventory objects under their `'singular_name'` key.  
* All query functions with multiple items return as an array.  
For instance, if I wanted to interact with the 'beer' inventory:
```php
<?php 
// Let's pretend I'm in a theme file right now
global $inventory;
$beer_inventory = $inventory['beer'];
// OR $beer_inventory = $inventory->get_obj('beer');
// All beers
$all_beers = $beer_inventory->get_all_items();
// One beer by it's id
$a_beer = $beer_inventory->get_item_by_id(3);
// All 'light' beers
$light_beers = $beer_inventory->get_items_by_column_value('beer_color', 'light');
// All beers that match a custom 'WHERE' SQL condition
$expensive_beers = $beer_inventory->get_items_by_where("beer_price>30");
?>
```

## Example
This is the use case that started it all. If you need to add 'beer' items to your site, feel free to use as-is. :)
```php
<?php
    $beer_inventory_item_vals = [
        // Provides some identifiers shown in the table and edit pages,
        // but this how you will retrieve the obj with $inventory['your_singular_item_name']
        'singular_name' => 'beer',
        // Auto generated by sticking an 's' on the end of singular, if not provided
        'plural_name' => 'beers', 
        // This is the table name for new inventory, will be created only on plugin 'activation'
        'db_table_name' => 'inventory_beers',
        // Text for admin menu btn
        'menu_btn_text' => 'New Beers',
        

        // Bulk item actions for WP_List_Table
        // 'db_values' should reference an $inventory_columns key(which will be in the database)
        //  a 'Delete Items' bulk action is automagically added, no need to do it here
        // NOTE: I used BIT(1) to keep track of a bool in SQL. So these values had to match that.
        'bulk_actions' => [
            'add_to_tap' => [
                'label'=>'Add to Tap',
                'db_values' => [ 'on_tap'=> 1 ]
            ],
            'remove_from_tap' => [
                'label'=>'Remove from Tap',
                'db_values' => [ 'on_tap'=> 0 ]
            ]
        ],

        // Everything comes from here,
            // each 'key' is a column name in the new table
        'inventory_columns' => [

            // Beer title/name
            'beer_title' => [
                // Used in editing page
                
                'default_value' => 'New Beer Name',
                // 'input' handles the item edit page options(defaults to text input)
                'input'=>[
                    'type' => 'text',
                    // Label is for the literal <label></label> tag
                    'label'=> 'Beer Name',
                    'required'=>'true',
                    // Put inside the main input tag
                    'attributes' => [
                        'placeholder' => 'Awesome beer title'
                    ]
                    // input 'name' will be set to column name, in this case 'beer_title'
                ],
                // WP_List_Table options for this column,
                // note that this doesn't need to be included at all,
                // if you don't want it in the table.
                'table' => [
                    // Column header label,
                    // If 'display_as' isn't set, nothing here matters because it won't be put in the table
                    'display_as' => 'Beer Title',
                    'sortable' => true,
                    // This means this items will sort by this column by default.
                    // If multiple columns have default_sort, the last one will overwrite the others

                    'default_sort' => true,
                    // Might be useful sometimes. I don't know, I added it because it's in WP_List_table
                    'hidden'=> false,
                    // Displays 'Edit | Delete' in this column, multiple columns can have it
                    'display_row_actions'=> true,
                ],
                
                'data' => [
                    // SQL column data type, defaults to VARCHAR(255)
                    'sql_type' =>      'TINYTEXT',
                    // New values are parsed from this type, 
                        // for instance %d removes every character but 0-9
                    // Defaults to %s
                    'parse_type' =>    '%s',

                ]
            ],

            // Whether beer is currently on tap
            'on_tap' => [
                // Even though '1' is a string, the %d parse type will try to convert it into an integer,
                // note that on a 'select' element, this will determine which <option> is selected by default
                'default_value' => '1',
                'input'=>[
                    // 'select' types are special(see 'options' key)
                    'type' => 'select',
                    'label'=> 'On Tap',
                    'required'=>'true',
                    // Only applies to type 'select', provides <option> tags 
                    // The signature is (db value) => (option text)
                    'options' => [
                        '0' => 'No',
                        '1' => 'Yes'
                    ],
                ],
                // WP_List_Table behaviour
                'table' => [
                    'display_as' => 'On Tap', 
                    'sortable' => true,
                    // This acts like a custom column function in WP_List_Table,
                        // has to return the html you want in the table cell.
                    // I.E. I didn't want it to read '0' or '1' in the table, so I gave it custom html:
                        // a green 'Yes'(on_tap === 1) or a red 'No'(on_tap === 0)
                    'output_function' => function($item, $col_name){
                        if($item[$col_name] === '1'){
                            return '<span style="color:green">Yes</span>';
                        } else {
                            return '<span style="color:red">No</span>';
                        }
                    }
                ],
                
                'data' => [
                    'sql_type' =>      'BIT(1)',
                    'parse_type' =>    '%d',
                ]
            ],

            // Beer Color
            'beer_color' => [
                // Remember, this is a 'select' input so it should really have a 'default_value'
                'default_value' => 'light',
                'input'=>[
                    'type' => 'select',
                    'label'=> 'Beer Color',
                    'required'=>'true',
                    'options' => [
                        'light' => 'Light',
                        'medium' => 'Medium',
                        'dark' => 'Dark'
                    ],
                ],
                // No 'table' key, I don't want this column displayed on the table page
                'data' => [
                    'sql_type' =>      'VARCHAR(200)',
                    'parse_type' =>    '%s',
                ]
            ],

            // Beer Description
            'beer_description' => [
                'input'=>[
                    'type' => 'textarea',
                    'label'=> 'Beer Description',
                    'required'=>'true',
                    'attributes' => [
                        'placeholder' => 'Awesome beer description'
                    ]
                ],
                'data' => [
                    'sql_type' =>      'TINYTEXT',
                    'parse_type' =>    '%s',
                ]
            ],
            
            // Beer ABV
            'abv' => [
                'input'=>[
                    'type' => 'text',
                    'label'=> 'ABV',
                    'required'=>'true',
                    'attributes' => [
                        'placeholder' => '6.2'
                    ]
                ],
                'data' => [
                    'sql_type' =>      'VARCHAR(20)',
                    // Note the parse_type. %f strips everything but ints and periods
                    'parse_type' =>    '%f',
                ]
            ],

        ]
    ];
    
    // Create new Inventory object from values
    $new_inventory_obj = new Inventory($beer_inventory_item_vals);
    // New object in global variable.
    // Note that the $inventory GLOBAL is actually an instance of InventoryController,
    // but accessing $inventory[(your singular item name)] is so much nicer
    global $inventory;
    $inventory->add_inventory_object($new_inventory_obj);
?>
```

## TODOS
* 'menu_icon_src' => 'some_src' for the admin menu button
* For every string that has to be SQL compliant(like the 'keys' in inventory_columns being SQL column names)
    * Verify string is formatted right (no spaces for instance)
    * Throw error if not valid SQL
* Items 'table' page
    * **Table Pagination** Looked straight-forward, but I don't have experience with WP_List_Table. I'll probably make it a default of '10', but add a 'screen options' tab.
    * At least one column needs to have a 'default_sort' if there are sortable columns. I need to throw an error or something in the code to catch for this. 
* I don't have nearly enough verification of values that are required in general    
* In the 'data' key there should also be regex parse types 'keys' for selecting chars and removing chars from saved strings
* Might be useful to have 'default_value' as a fallback for saving items to SQL, if no values are given
