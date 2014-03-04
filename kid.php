<?php
define('COLOR', 'c86f4d');
define('HELP_TITLE', 'your shortcuts');
define('HELP_TRIGGER', 'help');
define('MAX_FILESIZE', 100 * 1024);
define('NAME', 'kid');
define('PROTOCOLS', CURLPROTO_HTTP|CURLPROTO_HTTPS);
define('TITLE', 'bookmarklet shortcuts');
define('USERAGENT', 'kid (https://github.com/djl/kid)');

function build_url($url, $arg) {
    $url = str_replace('%s', $arg, $url);
    foreach (array('d', 'r', 't') as $a) {
        if (isset($_GET[$a])) {
            $b = urldecode(base64_decode($_GET[$a]));
            $url = str_replace('%' . $a, $b, $url);
        }
    }
    $replace = $arg != '' ? $arg : '$2';
    $url = preg_replace('/(%{)(.*)(})/', $replace, $url);
    return $url;
}

function clean($str) {
    return preg_replace('/(\s{2,}|\t)/', ' ', trim($str));
}

function e($output) {
    return htmlspecialchars($output, ENT_NOQUOTES);
}

function get_file($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 128);
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
    curl_setopt($ch, CURLOPT_PROTOCOLS, PROTOCOLS);
    curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, PROTOCOLS);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($dls, $dld, $uls, $uld){
        return ($dld > MAX_FILESIZE) ? 1 : 0;
    });
    $data = curl_exec($ch);
    if (curl_error($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    return $data;
}

function get_shortcut($shortcuts, $trigger) {
    if (array_key_exists($trigger, $shortcuts)) {
        return array($shortcuts[$trigger], false);
    } else if (array_key_exists('*', $shortcuts)) {
        return array($shortcuts['*'], true);
    } else {
        return null;
    }
}

function parse_shortcut_file($file) {
    $file = get_file($file);
    $lines = explode("\n", $file);
    $shortcuts = array();
    foreach ($lines as $line) {
        $line = clean($line);
        if (!$line || strpos($line, '>') === 0) continue;
        $segments = explode(' ', $line, 3);
        if (count($segments) != 3) continue;
        $takes_search = (preg_match('/(%{.*})|%s/', $segments[1]) && $segments[0] != '*');
        $segments[0] = strtolower($segments[0]);
        $shortcuts[$segments[0]] = array('trigger' => $segments[0],
                                         'url' => $segments[1],
                                         'title' => $segments[2],
                                         'search' => $takes_search);
    }
    return $shortcuts;
}

function show_help() {
    if (isset($_GET['c'], $_GET['f'])) {
        $parts = explode(' ', clean(urldecode(base64_decode($_GET['c']))), 2);
        return strtolower($parts[0]) == strtolower(HELP_TRIGGER);
    }
    return false;
}

function url() {
    $protocol = 'http';
    $ssl_headers = array('HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'https');
    foreach ($ssl_headers as $key => $value) {
        if (array_key_exists($key, $_SERVER) && $_SERVER[$key] == $value) {
            $protocol = 'https';
            break;
        }
    }
    return $protocol.'://'.$_SERVER['HTTP_HOST'].parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}

// GO!
$error = null;
if (isset($_GET['c'], $_GET['f'])) {
    $command = clean(urldecode(base64_decode($_GET['c'])));
    $shortcuts = array();
    try {
        $shortcuts = parse_shortcut_file($_GET['f']);
        if (count($shortcuts) === 0) {
            throw new Exception("No shortcuts found. Blank file or incorrect format?");
        }
        @list($trigger, $argument) = explode(' ', $command, 2);
        $trigger = strtolower($trigger);

        // get the shortcut URL and whether or not it's the
        // untriggered shortcut
        @list($shortcut, $untriggered) = get_shortcut($shortcuts, $trigger);

        // we didn't find a shortcut
        if ($shortcut == null) {
            throw new Exception(sprintf("Unknown trigger '%s'", e($trigger)));
        }

        // if we got the "untriggered" search, make the search
        // term the full command
        if ($untriggered) {
            $argument = $command;
        }

        // only redirect if we're not showing the help page
        if (!show_help()) {
            $url = build_url($shortcut['url'], urlencode($argument));
            header('Location: ' . $url, true, 301);
        }
    } catch (Exception $e) {
        $error = $e;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo e(NAME); ?></title>
    <style type="text/css">
    html{background:#fff;border-top:4px solid #<?php echo e(COLOR); ?>;color:black;font:62.5% Helvetica,sans-serif;text-align:center;}
    body{margin:4em auto;width:50em;}
    h1{font-size:3em;line-height:3em;margin-bottom:1em;text-shadow: 0 -1px 1px #FFF;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:#<?php echo e(COLOR); ?>;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label{color:#bbb;float:left;font-size:1.6em;font-weight:bold;line-height:1.8em !important;margin-left:-220px;text-align:right;text-shadow: 0 -1px 1px #FFF;width:200px;}
    em{color:#bbb;font-style:normal;font-weight:normal;}
    p{font-size:1.4em;margin:0 0 2em;line-height:2em;}
    a{color:#<?php echo e(COLOR); ?>;}
    a:hover{color:black;}
    a#link{background:#<?php echo e(COLOR); ?>;font-size:14px;color:#fff;padding:4px;text-shadow: 1px 1px 1px #<?php echo e(COLOR); ?>;text-decoration:none;}
    a#link:hover{background:black;text-shadow:1px 1px 1px black;}
    table{border-spacing:0;font-size:1.4em;margin:4em auto 6em;width:100%;}
    td{padding:10px;}
    code{color:#777;font:bold 1.1em "Bitstream Vera Sans Mono","Courier New",monospace;}
    .highlight{color:#<?php echo e(COLOR); ?> !important;font-size:1.5em;}
    tr:nth-child(even){background:#eee;}
    </style>
    <?php if (!show_help()): ?><script type="text/javascript">window.onload = function() { document.getElementById("custom").onkeyup = function () { document.getElementById('link').href = document.getElementById('link').href.replace(/&f=(.*?)'/,'&f='+this.value+"'")}; }</script><?php endif; ?>
</head>
<body>
    <h1><a href="<?php echo url() ?>"><?php echo e(NAME); ?></a> <em><?php echo e(show_help() ? HELP_TITLE : TITLE); ?></em></h1>
    <?php if ($error): ?>
        <p><strong><?php echo $error->getMessage(); ?></strong></p>
    <?php else: ?>
        <?php if (show_help()): ?>
            <p><span class="highlight">*</span> triggers may be followed by a search term</p>
            <table>
            <thead>
                <tr>
                    <th>Trigger</th>
                    <th>Title</th>
                </tr>
            </thead>
            <?php foreach($shortcuts as $shortcut): ?>
                <tr>
                    <td><code><?php echo e($shortcut['trigger']) ?></code></td>
                    <td><?php echo e($shortcut['title']) ?><?php if ($shortcut['search']): ?> <span class="highlight">*</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </table>
        <?php else: ?>
            <form action="." onsubmit="javascript:document.getElementById('link').click();return false;">
                <label for="custom" id="label" class="out">shortcuts file:</label><input type="text" name="custom" value="http://" id="custom">
            </form>
            <a id="link" href="javascript:(function(){var%20nw=false;var%20c=window.prompt('Type%20`<?php echo e(HELP_TRIGGER); ?>`%20for%20a%20list%20of%20commands:');var%20d='';try{d=window.btoa(encodeURIComponent(window.location.hostname));}catch(e){d=window.btoa(encodeURIComponent('about:blank'))};var%20u=window.btoa(encodeURIComponent(window.location));var%20t=window.btoa(encodeURIComponent(document.title));if(c){if(c.substring(0,1)=='%20'){nw=true;}c=window.btoa(encodeURIComponent(c));var%20url='<?php echo url() ?>?c='+c+'&d='+d+'&r='+u+'&t='+t+'&f=';if(nw){var%20w=window.open(url);w.focus();}else{window.location.href=url;};};};)()"><?php echo e(NAME); ?></a>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
