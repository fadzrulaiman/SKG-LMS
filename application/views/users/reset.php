<?php 
/**
 * This partial view is loaded into a modal form and allows the connected user to change its password.
 * 
 * @since 0.2.0
 */
?>

<div class="modal-body p-4">
    <?php
    $attributes = array('id' => 'target');
    echo form_open('users/reset/' . $target_user_id, $attributes); ?>
        <input type="hidden" name="CipheredValue" id="CipheredValue" />
    </form>
    <div class="form-group">
        <label for="password"><?php echo lang('users_reset_field_password'); ?></label>
        <input type="password" class="form-control" name="password" id="password" required />
        <div id="password-strength" class="mt-2"></div>
    </div>
</div>

<div class="modal-footer d-flex justify-content-end p-3">
    <button id="send" class="btn btn-primary mr-2"><?php echo lang('users_reset_button_reset'); ?></button>
    <button class="btn btn-secondary" data-dismiss="modal"><?php echo lang('users_reset_button_cancel'); ?></button>
</div>

<script type="text/javascript">
    $(function () {
        function updatePasswordStrength() {
            var strength = 0;
            var password = $('#password').val();
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[\W]+/)) strength += 1;

            var strengthText = "";
            switch (strength) {
                case 0:
                case 1:
                    strengthText = "<div class='text-danger'><?php echo lang('password_weak'); ?></div>";
                    break;
                case 2:
                    strengthText = "<div class='text-warning'><?php echo lang('password_fair'); ?></div>";
                    break;
                case 3:
                case 4:
                    strengthText = "<div class='text-success'><?php echo lang('password_strong'); ?></div>";
                    break;
                case 5:
                    strengthText = "<div class='text-success'><?php echo lang('password_very_strong'); ?></div>";
                    break;
            }
            $('#password-strength').html(strengthText);
        }

        $('#password').on('input', updatePasswordStrength);

        $('#send').click(function() {
            var encrypter = new CryptoTools();
            encrypter.encrypt($('#pubkey').val(), $('#password').val()).then((encrypted) => {
                $('#CipheredValue').val(encrypted);
                $('#target').submit();
            });
        });

        // Validate the form if the user presses the enter key in the password field
        $('#password').keypress(function(e){
            if (e.keyCode == 13) $('#send').click();
        });
    });
</script>

<textarea id="pubkey" style="display:none;"><?php echo $public_key; ?></textarea>
