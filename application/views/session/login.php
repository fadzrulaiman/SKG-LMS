<?php
/**
 * This view displays the login form. Its layout differs from other pages of the application.
 * @copyright  Copyright (c) Fadzrul Aiman
 * @license    http://opensource.org/licenses/AGPL-3.0 AGPL-3.0
 * @link       https://github.com/fadzrulaiman/SKG-LMS
 * @since      0.1.0
 */
?>

<?php if ($this->config->item('oauth2_enabled') == TRUE) { ?>
<script type="text/javascript" src="https://apis.google.com/js/client:platform.js?onload=start" async defer></script>
<script type="text/javascript">
function start() {
    gapi.load('auth2', function() {
        auth2 = gapi.auth2.init({
            client_id: '<?php echo $this->config->item('oauth2_client_id');?>',
        });
    });
}
</script>
<?php }?>
<div class="row vertical-center">
    <div class="span3">&nbsp;</div>
    <div class="span6 form-box">
        <div class="row-fluid">
            <div class="span6">
                <h2><?php echo lang('session_login_title');?></h2>
                <?php echo $flash_partial_view;?>
                <?php echo validation_errors(); ?>
                <?php $attributes = array('id' => 'loginFrom');echo form_open('session/login', $attributes);
                $languages = $this->polyglot->nativelanguages($this->config->item('languages'));?>
                <input type="hidden" name="last_page" value="session/login" />
                <?php if (count($languages) == 1) { ?>
                <input type="hidden" name="language" value="<?php echo $language_code; ?>" />
                <?php } else { ?>
                <label for="language"><?php echo lang('session_login_field_language');?></label>
                <select class="input-medium" name="language" id="language">
                    <?php foreach ($languages as $lang_code => $lang_name) { ?>
                    <option value="<?php echo $lang_code; ?>"
                        <?php if ($language_code == $lang_code) echo 'selected'; ?>><?php echo $lang_name; ?></option>
                    <?php }?>
                </select>
                <br />
                <?php } ?>
                <label for="login"><?php echo lang('session_login_field_login');?></label>
                <input type="text" class="input-medium" name="login" id="login"
                    value="<?php echo (ENVIRONMENT=='demo')?'bbalet':set_value('login'); ?>" required />
                <input type="hidden" name="CipheredValue" id="CipheredValue" />
                </form>
                <input type="hidden" name="salt" id="salt" value="<?php echo $salt; ?>" />
                <label for="password"><?php echo lang('session_login_field_password');?></label>
                <input class="input-medium" type="password" name="password" id="password"
                    value="<?php echo (ENVIRONMENT=='demo')?'bbalet':''; ?>" /><br />
                <br />
                <button id="send" class="btn btn-primary"><i
                        class="mdi mdi-login"></i>&nbsp;<?php echo lang('session_login_button_login');?></button>
                <!--
                <?php if ($this->config->item('oauth2_enabled') == TRUE) { ?>
                <?php if ($this->config->item('oauth2_provider') == 'google') { ?>
                <button id="cmdGoogleSignIn" class="btn btn-primary"><i
                        class="mdi mdi-google"></i>&nbsp;<?php echo lang('session_login_button_login');?></button>
                <?php } ?>
                -->
                <?php } ?>
                <br /><br />
                <?php if (($this->config->item('ldap_enabled') == FALSE) && (ENVIRONMENT!='demo')) { ?>
                <button id="cmdForgetPassword" class="btn btn-danger"><i
                        class="mdi mdi-email"></i>&nbsp;<?php echo lang('session_login_button_forget_password');?></button>
                <?php } ?>
                <textarea id="pubkey" style="visibility:hidden;"><?php echo $public_key; ?></textarea>
            </div>
            <div class="span6" style="height:100%;">
                <div class="row-fluid">
                    <div class="span12">
                        <img src="<?php echo base_url();?>assets/images/logo_simple.png"
                            style="width: 100%; height: auto; margin: 10px; display: block;">
                        <span style="font-size:250%; font-weight: bold; line-height: 100%; color: black;">
                            <center><?php echo lang('Leave Management System');?></center>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="span3">&nbsp;</div>
</div>

<div class="modal hide" id="frmModalAjaxWait" data-backdrop="static" data-keyboard="false">
    <div class="modal-header">
        <h1><?php echo lang('global_msg_wait');?></h1>
    </div>
    <div class="modal-body">
        <img src="<?php echo base_url();?>assets/images/loading.gif" align="middle">
    </div>
</div>
<style>
body {
    background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo base_url();?>assets/images/ppns_bg.png');
    background-size: cover;
    background-position: center;
    font-family: 'Arial', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
    padding: 0;
    color: rgba(0, 0, 0, 0.6);
}

.vertical-center {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
    max-width: 450px;
    /* Increased form box width */
    margin: auto;
}

.form-box {
    width: 150%;
    padding: 20px;
    /* Adjusted for more space */
    border: 10px;
    border-radius: 20px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
    background-color: rgba(255, 255, 255, 0.95);
    /* Increased opacity for better readability */
    transition: box-shadow 0.3s ease-in-out;
    text-align: left;
}

.form-box:hover {
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.6);
}

.form-box h2 {
    margin-bottom: 20px;
    font-size: 50px;
    /* Larger font size */
    color: #333;
    text-align: center;
    text-transform: uppercase;
}

label {
    font-size: 18px;
    /* Slightly larger for clarity */
    font-weight: bold;
    color: #555;
    margin-bottom: 2px;
    display: block;
}

.input-medium {
    width: 90%;
    padding: 10px 14px;
    margin-bottom: 2px;
    border: 2px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    /* Larger font size for input fields */
    transition: border-color 0.2s;
}

.input-medium:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 4px rgba(0, 123, 255, 0.25);
}

.btn {
    display: inline-block;
    width: 90%;
    padding: 12px 0;
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.btn-primary {
    background-color: #007bff;
    color: #ffffff;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-danger {
    background-color: #dc3545;
    color: #ffffff;
}

.btn-danger:hover {
    background-color: #c82333;
}

.centered-text {
    font-size: 18px;
    color: #000;
    text-align: center;
}

textarea {
    visibility: hidden;
}
</style>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/bootbox.min.js"></script>
<script type="text/javascript">
//Encrypt the password using RSA and send the ciphered value into the form
function submit_form() {
    var encrypter = new CryptoTools();
    var clearText = $('#password').val() + $('#salt').val();
    encrypter.encrypt($('#pubkey').val(), clearText).then((encrypted) => {
        $('#CipheredValue').val(encrypted);
        $('#loginFrom').submit();
    });
}

//Attempt to authenticate the user using OAuth2 protocol
function signInCallback(authResult) {
    if (authResult['code']) {
        $.ajax({
            url: '<?php echo base_url();?>session/oauth2',
            type: 'POST',
            data: {
                auth_code: authResult.code
            },
            success: function(result) {
                if (result == "OK") {
                    var target = '<?php echo $last_page;?>';
                    if (target == '') {
                        window.location = "<?php echo base_url();?>";
                    } else {
                        window.location = target;
                    }
                } else {
                    bootbox.alert(result);
                }
            }
        });
    } else {
        // There was an error.
        bootbox.alert("Unknown OAuth2 error");
    }
}

$(function() {
    <?php if ($this->config->item('csrf_protection') == TRUE) {?>
    $.ajaxSetup({
        data: {
            <?php echo $this->security->get_csrf_token_name();?>: "<?php echo $this->security->get_csrf_hash();?>",
        }
    });
    <?php }?>
    //Memorize the last selected language with a cookie
    if (Cookies.get('language') !== undefined) {
        var IsLangAvailable = 0 != $('#language option[value=' + Cookies.get('language') + ']').length;
        if (Cookies.get('language') != "<?php echo $language_code; ?>") {
            //Test if the former selected language is into the list of available languages
            if (IsLangAvailable) {
                $('#language option[value="' + Cookies.get('language') + '"]').attr('selected', 'selected');
                $('#loginFrom').prop('action', '<?php echo base_url();?>session/language');
                $('#loginFrom').submit();
            }
        }
    }

    //Refresh page language
    $('#language').select2({
        width: '165px'
    });

    $('#language').on('select2:select', function(e) {
        var value = e.params.data.id;
        Cookies.set('language', value, {
            expires: 90,
            path: '/'
        });
        $('#loginFrom').prop('action', '<?php echo base_url();?>session/language');
        $('#loginFrom').submit();
    });

    $('#login').focus();

    $('#send').click(function() {
        submit_form();
    });

    //If the user has forgotten his password, send an e-mail
    $('#cmdForgetPassword').click(function() {
        if ($('#login').val() == "") {
            bootbox.alert("<?php echo lang('session_login_msg_empty_login');?>");
        } else {
            bootbox.confirm("<?php echo lang('session_login_msg_forget_password');?>",
                "<?php echo lang('Cancel');?>",
                "<?php echo lang('OK');?>",
                function(result) {
                    if (result) {
                        $('#frmModalAjaxWait').modal('show');
                        $.ajax({
                                type: "POST",
                                url: "<?php echo base_url(); ?>session/forgetpassword",
                                data: {
                                    login: $('#login').val()
                                }
                            })
                            .done(function(msg) {
                                $('#frmModalAjaxWait').modal('hide');
                                switch (msg) {
                                    case "OK":
                                        bootbox.alert(
                                            "<?php echo lang('session_login_msg_password_sent');?>"
                                        );
                                        break;
                                    case "UNKNOWN":
                                        bootbox.alert(
                                            "<?php echo lang('session_login_flash_bad_credentials');?>"
                                        );
                                        break;
                                }
                            });
                    }
                });
        }
    });

    //Validate the form if the user press enter key in password field
    $('#password').keypress(function(e) {
        if (e.keyCode == 13)
            submit_form();
    });

    //Alternative authentication methods
    <?php if ($this->config->item('oauth2_enabled') == TRUE) { ?>
    <?php if ($this->config->item('oauth2_provider') == 'google') { ?>
    $('#cmdGoogleSignIn').click(function() {
        auth2.grantOfflineAccess({
            'redirect_uri': 'postmessage'
        }).then(signInCallback);
    });
    <?php } ?>
    <?php } ?>

});
</script>