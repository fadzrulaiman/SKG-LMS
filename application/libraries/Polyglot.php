<?php
if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

class Polyglot {
    
    public function __construct() {
    }

    public function languages($languages_list) {
        $languages = array();
        $lang_codes = explode(",", $languages_list);
        foreach($lang_codes as $lang_code) {
            $languages[$lang_code] =  $this->code2language($lang_code);
        }
        return $languages;
    }

    public function nativelanguages($languages_list) {
        $languages = array();
        $lang_codes = explode(",", $languages_list);
        foreach($lang_codes as $lang_code) {
            $languages[$lang_code] =  $this->code2nativelanguage($lang_code);
        }
        return $languages;
    }

    public function code2language($code) {
        switch (strtolower($code)) {
            case 'zh' : return 'chinese'; break;
            case 'en' : return 'english'; break;
            case 'en-gb' : return 'english_gb'; break;
            case 'ms' : return 'malay'; break;
            default: return 'english'; break;
        }
    }

    public function language2code($language) {
        switch (strtolower($language)) {
            case 'chinese' : return 'zh'; break;
            case 'english' : return 'en'; break;
            case 'english_gb' : return 'en-GB'; break;
            case 'malay' : return 'ms'; break;
            default: return 'en'; break;
        }
    }

    public function code2nativelanguage($code) {
        switch (strtolower($code)) {
            case 'zh' : return '中文'; break;
            case 'en' : return 'English'; break;
            case 'en-gb' : return 'English (UK)'; break;
            case 'ms' : return 'Bahasa Melayu'; break;
            default: return 'English'; break;
        }
    }

    public function nativelanguage2code($language) {
        switch (strtolower($language)) {
            case '中文' : return 'zh'; break;
            case 'english' : return 'en'; break;
            case 'english (uk)' : return 'en-GB'; break;
            case 'bahasa melayu' : return 'ms'; break;
            default: return 'en'; break;
        }
    }
}
?>
