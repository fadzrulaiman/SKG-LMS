<?php defined('BASEPATH') OR exit('No direct script access allowed.');

$config['useragent']        = 'PHPMailer';              // Mail engine switcher: 'CodeIgniter' or 'PHPMailer'
$config['protocol']         = 'smtp';                   // 'mail', 'sendmail', or 'smtp'
$config['mailpath']         = '/usr/sbin/sendmail';
$config['smtp_host']        = 'smtp.gmail.com';
$config['smtp_auth']        = true;                     // Whether to use SMTP authentication, boolean TRUE/FALSE.
$config['smtp_user']        = 'daniel.fadzrul@gmail.com';
$config['smtp_pass']        = 'pdwpfpkvdsanfdup';       // Your SMTP password or App Password
$config['smtp_port']        = 587;
$config['smtp_timeout']     = 30;                       // (in seconds)
$config['smtp_crypto']      = 'tls';                    // '' or 'tls' or 'ssl'
$config['smtp_debug']       = 0;                        // PHPMailer's SMTP debug info level: 0 = off
$config['smtp_auto_tls']    = false;                    // Disable automatic TLS encryption
$config['smtp_conn_options'] = array();                 // SMTP connection options
$config['wordwrap']         = TRUE;
$config['wrapchars']        = 76;
$config['mailtype']         = 'html';                   // 'text' or 'html'
$config['charset']          = 'UTF-8';                  // Character set
$config['validate']         = TRUE;
$config['priority']         = 3;                        // Email priority
$config['crlf']             = "\r\n";                   // Line break format
$config['newline']          = "\r\n";                   // Line break format
$config['bcc_batch_mode']   = FALSE;
$config['bcc_batch_size']   = 200;
$config['encoding']         = '8bit';                   // The body encoding

// DKIM Signing (if needed)
$config['dkim_domain']      = '';                       // DKIM signing domain name
$config['dkim_private']     = '';                       // DKIM private key, set as a file path
$config['dkim_private_string'] = '';                    // DKIM private key, set directly from a string
$config['dkim_selector']    = '';                       // DKIM selector
$config['dkim_passphrase']  = '';                       // DKIM passphrase
$config['dkim_identity']    = '';                       // DKIM Identity, usually the email address
