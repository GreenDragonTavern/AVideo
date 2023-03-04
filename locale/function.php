<?php

if (empty($config)) {
    return true;
}

// filter some security here
if (!empty($_GET['lang'])) {
    $_GET['lang'] = str_replace(["'", '"', "&quot;", "&#039;"], ['', '', '', ''], xss_esc($_GET['lang']));
}

if (!empty($_GET['lang'])) {
    $_GET['lang'] = strip_tags($_GET['lang']);
    $_SESSION['language'] = $_GET['lang'];
}
@include_once "{$global['systemRootPath']}locale/{$_SESSION['language']}.php";

function __($str, $allowHTML = false)
{
    global $t, $t_insensitive;
    if (!isset($t_insensitive)) {
        if (is_array($t) && function_exists('array_change_key_case') && !isCommandLineInterface()) {
            $t_insensitive = array_change_key_case($t, CASE_LOWER);
        } else {
            $t_insensitive = [];
        }
    }
    $return = $str;

    if (!empty($t[$str])) {
        $return = $t[$str];
    } elseif (!empty($t_insensitive) && !empty($t_insensitive[strtolower($str)])) {
        $return = $t_insensitive[strtolower($str)];
    }

    if ($allowHTML) {
        return $return;
    }
    return str_replace(["'", '"', "<", '>'], ['&apos;', '&quot;', '&lt;', '&gt;'], $return);
}

function printJSString($str, $return = false)
{
    $text = json_encode(__($str), JSON_UNESCAPED_UNICODE);
    if ($return) {
        return $text;
    } else {
        echo $text;
    }
}

function isRTL()
{
    global $t_isRTL;
    return !empty($t_isRTL) && $t_isRTL;
}

function getAllFlags()
{
    global $global;
    $dir = "{$global['systemRootPath']}view/css/flag-icon-css-master/flags/4x3";
    $flags = [];
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $flags[] = str_replace(".svg", "", $entry);
            }
        }
        closedir($handle);
    }
    sort($flags);
    return $flags;
}

/**
 * Deprecated replaced by Layout::getAvilableFlags()
 * @global array $global
 * @return array
 */
function getEnabledLangs()
{
    global $global;
    $dir = "{$global['systemRootPath']}locale";
    $flags = [];
    if (empty($global['dont_show_us_flag'])) {
        $flags[] = 'us';
    }
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..' && $entry != 'index.php' && $entry != 'function.php' && $entry != 'save.php') {
                $flags[] = str_replace('.php', '', $entry);
            }
        }
        closedir($handle);
    }
    sort($flags);
    return $flags;
}

function textToLink($string, $targetBlank = false)
{
    $target = "";
    if ($targetBlank) {
        $target = "target=\"_blank\"";
    }
    return preg_replace('$(\s|^)(https?://[a-z0-9_./?=&-]+)(?![^<>]*>)$i', ' <a href="$2" ' . $target . '>$2</a> ', $string);
}

function br2nl($html)
{
    $nl = preg_replace(['#<br\s*/?>#i', '#<p\s*/?>#i', '#</p\s*>#i'], ["\n", "\n", ''], $html);
    return $nl;
}

function flag2Lang($flagCode)
{
    global $global;
    $index = strtolower($flagCode);
    if (!empty($global['flag2Lang'][$index])) {
        return $global['flag2Lang'][$index];
    }
    return $flagCode;
}

function setSiteLang()
{
    global $config;

    $userLocation = false;
    $obj = AVideoPlugin::getDataObjectIfEnabled('User_Location');
    $userLocation = !empty($obj) && !empty($obj->autoChangeLanguage);

    if (!empty($_GET['lang'])) {
        _session_start();
        $_SESSION['language'] = $_GET['lang'];
    } elseif (empty($_SESSION['language']) && !$userLocation) {
        _session_start();
        $_SESSION['language'] = $config->getLanguage();
    }
    if (empty($_SESSION['language'])) {
        $_SESSION['language'] = 'en_us';
    }
    return setLanguage($_SESSION['language']);
}

function setLanguage($lang)
{
    if (empty($lang)) {
        return false;
    }
    global $global;
    $lang = flag2Lang($lang);
    if (empty($lang) || $lang === '-') {
        return false;
    }

    $file = "{$global['systemRootPath']}locale/{$lang}.php";
    _session_start();
    if (file_exists($file)) {
        $_SESSION['language'] = $lang;
        include_once $file;
        return true;
    } else {
        //_error_log('setLanguage: File does not exists 1 ' . $file);
        $lang = strtolower($lang);
        $file = "{$global['systemRootPath']}locale/{$lang}.php";
        if (file_exists($file)) {
            $_SESSION['language'] = $lang;
            include_once $file;
            return true;
        } else {
            //_error_log('setLanguage: File does not exists 2 ' . $file);
        }
    }
    return false;
}

function getLanguage()
{
    if (empty($_SESSION['language'])) {
        global $global;
        require_once $global['systemRootPath'] . 'objects/configuration.php';
        require_once $global['systemRootPath'] . 'objects/functions.php';
        $config = new Configuration();
        $_SESSION['language'] = $config->getLanguage();
    }
    if(empty($_SESSION['language'])){
        $_SESSION['language'] = 'us_EN';
    }
    return fixLangString($_SESSION['language']);
}

function fixLangString($lang){
    return strtolower(str_replace('_', '-', $lang));
}

function revertLangString($lang){
    $parts = explode('-', $lang);

    $lang = strtolower($parts[0]);
    if(!empty($parts[1])){
        $lang .= '_'.strtoupper($parts[1]);
    }
    return $lang;
}
