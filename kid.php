<?php
define('COLOR', 'c86f4d');
define('DEFAULT_URL', 'https://www.google.com/search?q=%c');
define('HELP_TITLE', 'your shortcuts');
define('HELP_TRIGGER', 'help');
define('NAME', 'kid');
define('PROTOCOLS', CURLPROTO_HTTP|CURLPROTO_HTTPS);
define('TITLE', 'bookmarklet shortcuts');
define('USERAGENT', 'kid (https://github.com/djl/kid)');

function build_url($url, $arg) {
    $url = str_replace('%s', $arg, $url);
    foreach (array('c', 'd', 'r', 't') as $a) {
        if (isset($_POST[$a])) {
            $url = str_replace('%' . $a, $_POST[$a], $url);
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
    curl_setopt_array($ch, array(CURLOPT_CONNECTTIMEOUT => 60,
                                 CURLOPT_FAILONERROR => true,
                                 CURLOPT_HEADER => false,
                                 CURLOPT_RETURNTRANSFER => 1,
                                 CURLOPT_TIMEOUT => 60,
                                 CURLOPT_URL => $url,
                                 CURLOPT_USERAGENT => USERAGENT,
                                 CURLOPT_PROTOCOLS => PROTOCOLS,
                                 CURLOPT_REDIR_PROTOCOLS => PROTOCOLS));
    $data = curl_exec($ch);
    if (curl_error($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    return $data;
}

function get_shortcut($shortcuts, $trigger) {
    if (array_key_exists($trigger, $shortcuts)) {
        return $shortcuts[$trigger];
    } else if (array_key_exists('*', $shortcuts)) {
        return $shortcuts['*'];
    } else {
        return DEFAULT_URL;
    }
}

function parse_shortcut_file($file) {
    $file = get_file($file);
    $lines = explode("\n", $file);
    $shortcuts = array();
    $group = null;
    foreach ($lines as $line) {
        $line = clean($line);
        if (!$line || strpos($line, '>') === 0) continue;
        if (strpos($line, '@') === 0)  {
            $group = str_replace('@', '', $line);
            continue;
        }
        $segments = explode(' ', $line, 3);
        if (count($segments) != 3) continue;
        $takes_search = (preg_match('/(%{.*})|%s/', $segments[1]) && $segments[0] != '*');
        $segments[0] = strtolower($segments[0]);
        $shortcuts[$segments[0]] = array('trigger' => $segments[0],
                                         'url' => $segments[1],
                                         'title' => $segments[2],
                                         'search' => $takes_search,
                                         'group' => $group);
    }
    return $shortcuts;
}

function show_help() {
    if (isset($_POST['c'], $_POST['f'])) {
        $parts = explode(' ', clean(urldecode($_POST['c'])), 2);
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
if (isset($_POST['c'], $_POST['f'])) {
    $command = clean(urldecode($_POST['c']));
    $shortcuts = array();
    try {
        $shortcuts = parse_shortcut_file($_POST['f']);
        if ($shortcuts) {
            @list($trigger, $argument) = explode(' ', $command, 2);
            $trigger = strtolower($trigger);
            $shortcut = get_shortcut($shortcuts, $trigger);
            $url = build_url($shortcut['url'], urlencode($argument));
            if (!show_help()) {
                header('Location: ' . $url, true, 301);
            }
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
    h2{font-size:3em;font-weight:bold;margin:3em 0 0.5em;}
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
    <?php if (!show_help()): ?><script type="text/javascript">window.onload = function() { document.getElementById("custom").onkeyup = function () { document.getElementById('link').href = document.getElementById('link').href.replace(/'f':'(.*?)'/,"'f':'"+this.value+"'")}; }</script><?php endif; ?>
</head>
<body>
    <h1><a href="<?php echo url() ?>"><?php echo e(NAME); ?></a> <em><?php echo e(show_help() ? HELP_TITLE : TITLE); ?></em></h1>
    <?php if ($error): ?>
        <p><strong>Couldn't grab your shortcuts file because:</strong></p>
        <p><code><?php echo $error->getMessage(); ?></code></p>
    <?php else: ?>
        <?php if (show_help()): ?>
            <p><span class="highlight">*</span> triggers may be followed by a search term</p>
            <?php $first = true; $previous = null; ?>
            <?php foreach($shortcuts as $shortcut): ?>
                <?php if ($first || $shortcut['group'] != $previous): ?>
                    <?php if ($shortcut['group'] != $previous): ?></table><?php endif; ?>
                    <?php if ($shortcut['group'] != "" ): ?><h2><?php echo e($shortcut['group']); ?></h2><?php endif; ?>
                    <table>
                    <thead>
                        <tr>
                            <th>Trigger</th>
                            <th>Title</th>
                        </tr>
                    </thead>
                <?php endif; ?>
                <tr>
                    <td><code><?php echo e($shortcut['trigger']) ?></code></td>
                    <td><?php echo e($shortcut['title']) ?><?php if ($shortcut['search']): ?> <span class="highlight">*</span><?php endif; ?></td>
                </tr>
                <?php $first = false; $previous = $shortcut['group']; ?>
            <?php endforeach; ?>
            </table>
        <?php else: ?>
            <form action="." onsubmit="javascript:document.getElementById('link').click();return false;">
                <label for="custom" id="label" class="out">shortcuts file:</label><input type="text" name="custom" value="http://" id="custom">
            </form>
            <a id="link" href="javascript:kid();function%20kid(){var%20nw=false;var%20url='<?php echo url() ?>';var params={'f':'','c':window.prompt('Type%20`<?php echo e(HELP_TRIGGER); ?>`%20for%20a%20list%20of%20commands:'),'u':encodeURIComponent(window.location),'t':encodeURIComponent(document.title),};try{params['d']=encodeURIComponent(window.location.hostname);}catch(e){params['d']='about:blank';}if(params['c']){if(params['c'].substring(0,1)=='%20'){nw=true;}params['c']=encodeURIComponent(params['c']);var%20form=document.createElement('form');form.setAttribute('method','post');form.setAttribute('action',url);if(nw){form.setAttribute('target','_blank');}for(var%20key%20in%20params){if(params.hasOwnProperty(key)){var%20hiddenField=document.createElement('input');hiddenField.setAttribute('type','hidden');hiddenField.setAttribute('name',key);hiddenField.setAttribute('value',params[key]);form.appendChild(hiddenField);}}}document.body.appendChild(form);form.submit();};"><?php echo e(NAME); ?></a>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
