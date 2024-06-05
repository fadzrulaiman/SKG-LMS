<?php 
/**
 * This view allows an HR admin to modify a location (occupied by an employee).
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.2.0
 */
?>

<h2><?php echo lang('locations_edit_title');?><?php echo $location['id']; ?></h2>

<?php echo validation_errors(); ?>

<?php echo form_open('locations/edit/' . $location['id']) ?>

    <label for="name"><?php echo lang('locations_edit_field_name');?></label>
    <input type="text" name="name" id="name" value="<?php echo $location['name']; ?>" autofocus required /><br />

    <br /><br />
    <button type="submit" class="btn btn-primary"><i class="mdi mdi-check"></i>&nbsp;<?php echo lang('locations_edit_button_update');?></button>
    &nbsp;
    <a href="<?php echo base_url();?>locations" class="btn btn-danger"><i class="mdi mdi-close"></i>&nbsp;<?php echo lang('locations_edit_button_cancel');?></a>
</form>
