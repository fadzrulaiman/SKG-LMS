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

<style>
body {
    background-image: url('<?php echo base_url();?>assets/images/login-bg.jpg');
    background-size: cover;
    font-family: Arial, sans-serif;
}

.vertical-center {
    min-height: 90vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.form-box {
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #e4e4e4;
    border-radius: 4px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    width: 500px;
    /* Remove max-width */
    display: inline-block;
    /* Set display to inline-block */
    text-align: center;
    /* Align contents center */
}

.form-box h2 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
    margin-top: 10px;
    color: #333;
}

.form-box label {
    font-size: 16px;
    margin-bottom: 10px;
    color: #666;
}

.form-box input[type="text"],
.form-box input[type="password"],
.form-box select {
    width: calc(100% - 20px);
    /* Adjusted width for inputs */
    padding: 15px;
    /* Increased padding */
    border: 1px solid #ccc;
    margin-bottom: 10px;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-box button {
    width: 100%;
    margin-top: 10px;
    padding: 15px;
    /* Increased padding */
    border: none;
    border-radius: 4px;
    background-color: #007bff;
    color: #fff;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
}

.form-box button:hover {
    background-color: #0056b3;
}

.form-box .btn-google {
    background-color: #db4437;
}

.form-box .btn-google:hover {
    background-color: #c13505;
}

.form-box .btn-forget {
    background-color: #dc3545;
}

.form-box .btn-forget:hover {
    background-color: #bd2130;
}

.logo {
    display: block;
    margin: 0 auto;
    width: 320px; /* Set the desired width */
    height: 256px; /* Set the desired height */
}


.logo-text {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    text-align: center;
    margin-top: 10px;
    margin-bottom: 20px;
    /* Added margin-bottom */
}

.language-select {
    margin-top: 20px;
    /* Added margin-top */
}
</style>

<div class="vertical-center">
    <div class="form-box">
        <img src="<?php echo base_url();?>assets/images/logo_simple.png" class="logo" alt="Logo">
        <div class="logo-text">
            <span><?php echo lang('Leave Management System');?></span>
        </div>
        <h2><?php echo lang('session_login_title');?><?php echo $help;?></h2>
        <?php echo $flash_partial_view;?>
        <?php echo validation_errors(); ?>
        <?php
        $attributes = array('id' => 'loginFrom');
        echo form_open('session/login', $attributes);
        $languages = $this->polyglot->nativelanguages($this->config->item('languages'));
        ?>
        <input type="hidden" name="last_page" value="session/login" />
        <label for="login"><?php echo lang('session_login_field_login');?></label>
        <input type="text" class="form-control" name="login" id="login"
            value="<?php echo (ENVIRONMENT=='demo')?'bbalet':set_value('login'); ?>" required />
        <input type="hidden" name="CipheredValue" id="CipheredValue" />
        <label for="password"><?php echo lang('session_login_field_password');?></label>
        <input class="form-control" type="password" name="password" id="password"
            value="<?php echo (ENVIRONMENT=='demo')?'bbalet':''; ?>" /><br />
        <button id="send" class="btn btn-primary btn-block"><i
                class="mdi mdi-login"></i>&nbsp;<?php echo lang('session_login_button_login');?></button>
        <?php if ($this->config->item('oauth2_enabled') == TRUE) { ?>
        <?php if ($this->config->item('oauth2_provider') == 'google') { ?>
        <button id="cmdGoogleSignIn" class="btn btn-primary btn-google btn-block"><i
                class="mdi mdi-google"></i>&nbsp;<?php echo lang('session_login_button_login');?></button>
        <?php } ?>
        <?php } ?>
        <?php if (($this->config->item('ldap_enabled') == FALSE) && (ENVIRONMENT!='demo')) { ?>
        <button id="cmdForgetPassword" class="btn btn-danger btn-forget btn-block"><i
                class="mdi mdi-email"></i>&nbsp;<?php echo lang('session_login_button_forget_password');?></button>
        <?php } ?>
        <div class="language-select">
            <?php if (!empty($languages) && is_array($languages)) { ?>
            <?php if (count($languages) > 1) { ?>
            <label for="language"><?php echo lang('session_login_field_language');?></label>
            <select class="form-control" name="language" id="language">
                <?php foreach ($languages as $lang_code => $lang_name) { ?>
                <option value="<?php echo $lang_code; ?>" <?php if ($language_code == $lang_code) echo 'selected'; ?>>
                    <?php echo $lang_name; ?></option>
                <?php }?>
            </select>
            <?php } else { ?>
            <input type="hidden" name="language" value="<?php echo $language_code; ?>" />
            <?php } ?>
            <?php } ?>
        </div>
        <textarea id="pubkey" style="visibility:hidden;"><?php echo $public_key; ?></textarea>
        <?php echo form_close(); ?>
    </div>
</div>

<div class="modal hide" id="frmModalAjaxWait" data-backdrop="static" data-keyboard="false">
    <div class="modal-header">
        <h1><?php echo lang('global_msg_wait');?></h1>
    </div>
    <div class="modal-body">
        <img src="<?php echo base_url();?>assets/images/loading.gif" align="middle">
    </div>
</div>

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