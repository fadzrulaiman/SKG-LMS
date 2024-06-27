<?php 
/**
 * This partial view is loaded into a modal form and allows the connected user to change its password.
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since 0.2.0
 */
?>

<style>
#target {
    width: 100%;
}

input,
button {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
}

button {
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #0056b3;
}

.cancel-button {
    background-color: #dc3545;
}

.cancel-button:hover {
    background-color: #c82333;
}
</style>

<?php
$attributes = array('id' => 'target');
echo form_open('users/reset/' . $target_user_id, $attributes); ?>
<input type="hidden" name="CipheredValue" id="CipheredValue" />
<label for="password"><?php echo lang('users_reset_field_password'); ?></label>
<input type="password" name="password" id="password" required /><br />
<button id="send" class="btn btn-primary"><?php echo lang('users_reset_button_reset'); ?></button>
<button type="button" class="cancel-button" data-dismiss="modal"><?php echo lang('users_reset_button_cancel'); ?></button>
</form>

<script type="text/javascript">
$(function() {
    $('#send').click(function() {
        var encrypter = new CryptoTools();
        encrypter.encrypt($('#pubkey').val(), $('#password').val()).then((encrypted) => {
            $('#CipheredValue').val(encrypted);
            $('#target').submit();
        });
    });

    // Validate the form if the user presses the enter key in the password field
    $('#password').keypress(function(e) {
        if (e.keyCode == 13) {
            $('#send').click();
        }
    });
});
</script>

<textarea id="pubkey" style="visibility:hidden;"><?php echo $public_key; ?></textarea>
