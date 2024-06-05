<?php 
/**
 * This view allows an HR admin to create a new location (occupied by an employee).
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.2.0
 */
?>

<h2><?php echo lang('locations_create_title');?></h2>

<?php echo validation_errors(); ?>

<?php
$attributes = array('id' => 'target');
echo form_open('locations/create', $attributes); ?>

    <label for="name"><?php echo lang('locations_create_field_name');?></label>
    <input type="text" name="name" id="name" autofocus required /><br />    
    <br /><br />
    <button id="send" class="btn btn-primary"><i class="mdi mdi-check"></i>&nbsp;<?php echo lang('locations_create_button_create');?></button>
    &nbsp;
    <a href="<?php echo base_url(); ?>locations" class="btn btn-danger"><i class="mdi mdi-close"></i>&nbsp;<?php echo lang('locations_create_button_cancel');?></a>
</form>
