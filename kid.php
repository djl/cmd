<?php
define('COLOR', 'c86f4d');
define('DEFAULT_URL', 'http://www.google.com/search?q=%c');
define('HELP_TITLE', 'your shortcuts');
define('HELP_TRIGGER', 'help');
define('NAME', 'kid');
define('PROTOCOLS', CURLPROTO_HTTP|CURLPROTO_HTTPS);
define('TITLE', 'bookmarklet shortcuts');
define('USERAGENT', 'kid (https://github.com/xvzf/kid)');


function build_url($url, $arg, $command)
{
    // replace %s with the argument
    $url = str_replace('%s', $arg, $url);

    // the $_GET arguments
    $gets = array('c', 'd', 'l', 'r', 't');
    foreach ($gets as $a) {
        $url = str_replace('%' . $a, $_GET[$a], $url);
    }

    // defaults arguments
    $replace = $arg != '' ? $arg : '$2';
    $url = preg_replace('/(%{)(.*)(})/', $replace, $url);

    return $url;
}

function clean($str)
{
    return preg_replace('/(\s{2,}|\t)/', ' ', trim($str));
}

function e($output)
{
    return htmlspecialchars($output, ENT_NOQUOTES);
}

function error($error)
{
    $message = sprintf("<p><strong>%s couldn't grab your shortcuts file because:</strong></p>", e(NAME));
    $message = sprintf("%s<p><code>%s</code></p>", $message, e($error));
    echo $message;
}

function get_file($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, array(CURLOPT_CONNECTTIMEOUT => 60,
                                 CURLOPT_FAILONERROR => true,
                                 CURLOPT_HEADER => false,
                                 CURLOPT_RETURNTRANSFER => 1,
                                 CURLOPT_TIMEOUT => 60,
                                 CURLOPT_URL => $url,
                                 CURLOPT_USERAGENT => USERAGENT,
                                 CURLOPT_PROTOCOLS => PROTOCOLS));
    $data = curl_exec($ch);
    if (curl_error($ch))
    {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    return $data;
}


function get_shortcut($shortcuts, $trigger)
{
    if (array_key_exists($trigger, $shortcuts))
    {
        return $shortcuts[$trigger];
    }
    else if (array_key_exists('*', $shortcuts))
    {
        return $shortcuts['*'];
    }
    else
    {
        return DEFAULT_URL;
    }
}

function parse_shortcut_file($file)
{
    $file = get_file($file);
    $lines = explode("\n", $file);

    $shortcuts = array();
    $group = null;
    foreach ($lines as $line)
    {
        $line = clean($line);
        $ignore = '/^>|#kill-defaults/';
        if (!$line || preg_match($ignore, $line)) continue;

        if (strpos($line, '@') === 0)
        {
            $group = preg_replace('/^@/', '', $line);
            continue;
        }

        $segments = explode(' ', $line, 3);
        $takes_search = (strstr($segments[1], '%s') && $segments[0] != '*');
        $segments[0] = strtolower($segments[0]);
        $shortcuts[$segments[0]] = array('trigger' => $segments[0],
                                         'url' => $segments[1],
                                         'title' => $segments[2],
                                         'search' => $takes_search,
                                         'group' => $group);
    }
    return $shortcuts;
}

function show_help()
{
    if (isset($_GET['c']) && isset($_GET['f']))
    {
        $parts = explode(' ', clean($_GET['c']), 2);
        return strtolower($parts[0]) == strtolower(HELP_TRIGGER);
    }
    return false;
}

function title()
{
    if (show_help()) { return HELP_TITLE; }
    return TITLE;
}

function url()
{
    $protocol = array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    return $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo e(NAME); ?></title>
    <style type="text/css">
    *{margin:0;padding:0;}
    html{background:#fff;border-top:4px solid #<?php echo e(COLOR); ?>;color:black;font:62.5% Helvetica,sans-serif;text-align:center;}
    body{margin:4em auto;width:50em;}
    h1{font-size:3em;line-height:3em;margin-bottom:1em;text-shadow: 0 -1px 1px #FFF;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:#<?php echo e(COLOR); ?>;}
    h2{font-size:2em;font-weight:bold;margin:3em 0 0.5em;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label,.out{line-height:1.8em !important;text-shadow: 0 -1px 1px #FFF;}
    label{font-size:1.6em;}
    em{color:#bbb;font-style:normal;font-weight:normal;}
    p{font-size:1.4em;margin:0 0 2em;line-height:2em;}
    a{color:#<?php echo e(COLOR); ?>;}
    a:hover{color:black;}
    a#link{background:#<?php echo e(COLOR); ?>;font-size:14px;color:#fff;padding:4px;text-shadow: 1px 1px 1px #<?php echo e(COLOR); ?>;text-decoration:none;}
    a#link:hover{background:black;text-shadow:1px 1px 1px black;}
    table{font-size:1.4em;margin:4em auto 6em;width:100%;}
    td{padding:10px;}
    code {color:#777;font: 1.1em "Bitstream Vera Sans Mono","Courier New",Monaco,monospace;}
    label{color:#bbb;float:left;font-weight:bold;line-height:1.4em;margin-left:-220px;width:200px;text-align:right;}
    .red{color:#<?php echo e(COLOR); ?> !important;font-size:1.5em;}
    .alt{background:#eee;}
    .lite{color:#777;margin: 0;}
    </style>
    <script type="text/javascript">
        function $(id){return document.getElementById(id)};
        window.onload = function () { $("custom").onkeyup = function () { $('link').href = $('link').href.replace(/&f=(.*?)'/,'&f='+this.value+"'")}; }
    </script>
</head>
<body>
    <h1><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>"><?php echo e(NAME); ?></a> <em><?php echo e(title()); ?></em></h1>
    <?php
    $error = false;
    if (isset($_REQUEST['c'], $_GET['f']))
    {
        // compensate for JavaScript's odd escaping
        // we need to use $_REQUEST here because $_GET is automatically urldecoded
        $command = stripslashes($_REQUEST['c']);
        $command = clean($command);
        $file = stripslashes($_GET['f']);

        $shortcuts = array();

        try {
            $shortcuts = parse_shortcut_file($file);
        } catch (Exception $e) {
            $error = true;
            error($e->getMessage());
        }

        if ($shortcuts)
        {
            @list($trigger, $argument) = explode(' ', $command, 2);
            $trigger = strtolower($trigger);
            $shortcut = get_shortcut($shortcuts, $trigger);
            $url = build_url($shortcut['url'], urlencode($argument), $command);

            // go!
            if (!show_help())
            {
                header('Location: ' . $url, true, 301);
            }
        }
    }
    ?>
    <?php if (!$error): ?>
        <?php if (show_help()): ?>
            <p><span class="red">*</span> triggers may be followed by a search term</p>
            <?php $count = 0; $previous = null; ?>
            <?php foreach($shortcuts as $shortcut): ?>
                <?php if ($shortcut['group'] != $previous || $count < 1): ?>
                    <?php if ($shortcut['group'] != $previous): ?></table><?php endif; ?>
                    <header>
                        <h2><?php echo e($shortcut['group']); ?></h2>
                    </header>
                    <table cellspacing="0">
                    <thead>
                        <tr>
                            <th>Trigger</th>
                            <th>Title</th>
                        </tr>
                    </thead>
                <?php endif; ?>
                <tr<?php if ($count % 2): ?> class="alt"<?php endif; ?>>
                    <td><code><?php echo e($shortcut['trigger']) ?></code></td>
                    <td><?php echo e($shortcut['title']) ?><?php if ($shortcut['search']): ?> <span class="red">*</span><?php endif; ?></td>
                </tr>
                <?php $count++; ?>
                <?php $previous = $shortcut['group']; ?>
            <?php endforeach; ?>
            </table>
        <?php else: ?>
            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="get">
                <label for="custom" id="label" class="out">shortcuts file:</label><input type="text" name="custom" value="http://" id="custom">
            </form>
            <a id="link" href="javascript:kid();function%20kid(){var%20nw=false;var%20c=window.prompt('Type%20`<?php echo e(HELP_TRIGGER); ?>`%20for%20a%20list%20of%20commands:');var%20h='';try{h=encodeURIComponent(window.location.hostname);}catch(e){h='about:blank'};var%20u=encodeURIComponent(window.location);var%20t=encodeURIComponent(document.title);if(c){if(c.substring(0,1)=='%20'){nw=true;}c=encodeURIComponent(c);var%20url='<?php echo url() ?>?c='+c+'&f='+'&d='+h+'&r='+u+'&t='+t+'&l='+document.getSelection();if(nw){var%20w=window.open(url);w.focus();}else{window.location.href=url;};};};"><?php echo e(NAME); ?></a>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
