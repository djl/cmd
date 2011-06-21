<?php
define('ARGUMENT_DELIMITER', ',');
define('COLOR', 'c86f4d');
define('COMMENT', '>');
define('DEFAULT_URL', 'http://www.google.com/search?q=%c');
define('HELP_TITLE', 'your shortcuts');
define('HELP_TRIGGER', 'help');
define('NAME', 'kid');
define('PROTOCOLS', CURLPROTO_HTTP|CURLPROTO_HTTPS);
define('TITLE', 'bookmarklet shortcuts');
define('USERAGENT', 'kid (https://github.com/xvzf/kid)');

function e($output)
{
    return htmlspecialchars($output, ENT_NOQUOTES);
}

function encode(&$val)
{
    $val = urlencode($val);
}

function error($error)
{
    $message = sprintf("<p><strong>%s couldn't grab your shortcuts file because:</strong></p>", e(NAME));
    $message = sprintf("%s<p><code>%s</code></p>", $message, e($error));
    die($message);
}

function get_args_from_command($command)
{
    $args = preg_replace('/\s\s+/', ' ', trim($command));
    preg_match_named('/^(?<trigger>(\w|\p{P})+)(\s+(?<args>.*))?/', $args, $matches);
    if (!$matches){ return; }

    if (!array_key_exists('args', $matches))
    {
        $matches['args'] = "";
    }

    $matches['trigger'] = strtolower($matches['trigger']);
    $arguments = explode(ARGUMENT_DELIMITER, $matches['args']);

    array_walk($arguments, 'encode');

    $matches['command'] = $command;
    $matches['args'] = $arguments;
    return $matches;
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
    if(curl_error($ch))
    {
        error(curl_error($ch));
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

function get_url($shortcut_url, $args, $command)
{
    $filters = array('parse_optional',
                     'parse_default',
                     'parse_simple');

    foreach ($filters as $filter)
    {
        $shortcut_url = $filter($shortcut_url, $args, $command);
    }
    return $shortcut_url;
}

function parse_default($url, $args, $command)
{
    $pattern = '/(%{[\w|\p{P}]+})/';
    if (preg_match($pattern, $url))
    {
         $parts = preg_split($pattern, $url, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
         $furl = array_shift($parts);
         $count = 0;
         foreach ($parts as $part)
         {
            if (preg_match($pattern, $part))
            {
                if (preg_match_named('/(?<wrap>%{(?<value>[\w|\p{P}]+)})/', $part, $matches))
                {
                    $part = $matches['value'];
                }
            }
            if ($args[$count])
            {
                $furl .= $args[$count];
            }
            else
            {
                $furl .= $part;
            }
            $count++;
         }
         $url = $furl;
    }
    return $url;
}

function parse_optional($url, $args, $command)
{
    $parts = preg_split('/(%s)/', $url, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $count = 0;
    $url = "";
    foreach($parts as $part)
    {
        $url .= str_replace('%s', $args[$count], $part);
    }
    return $url;
}

function parse_shortcut_file($file)
{
    $file = get_file($file);
    $lines = explode("\n", $file);

    $shortcuts = array();
    $last_was_group = false;
    $previous = $group_name = $group_description = null;
    foreach ($lines as $line)
    {
        // get rid of useless whitespace
        $line = preg_replace('/(\s{2,}|\t)/', ' ', trim($line));

        // Ignore blank lines, comments and '#kill-defaults' lines
        $ignore = sprintf('/^%s|#kill-defaults/', COMMENT);
        if (!$line || preg_match($ignore, $line)) continue;

        // @group lines
        if (preg_match('/^@/', $line))
        {
            // parse out the name/description
            $splits = preg_split('/^@/', $line, 0, PREG_SPLIT_NO_EMPTY);
            if ($splits)
            {
                if (!$last_was_group)
                {
                    $group_name = $splits[0];
                    $last_was_group = true;
                }
                else
                {
                    $last_was_group = false;
                    $group_description = $splits[0];
                }
            }
        }
        else
        {
            $segments = preg_split('/\s+/', $line, 3);
            $takes_search = (strstr($segments[1], "%s") && $segments[0] != "*");
            $shortcuts[$segments[0]] = array('trigger' => strtolower($segments[0]),
                                             'url' => $segments[1],
                                             'title' => $segments[2],
                                             'search' => $takes_search,
                                             'group_name' => $group_name,
                                             'group_description' => $group_description);
            $group_description = "";
        }
    }
    return $shortcuts;
}

function parse_simple($url, $args, $command)
{
    $url = preg_replace("/%d/", $_GET['d'], $url);
    $url = preg_replace("/%r/", $_GET['r'], $url);
    $url = preg_replace("/%t/", $_GET['t'], $url);
    $url = preg_replace("/%c/", $_GET['c'], $url);
    $url = preg_replace("/%l/", $_GET['l'], $url);
    return $url;
}

function preg_match_named($pattern, $subject, &$matches, $flags=null, $offset=null)
{
    $c = preg_match($pattern, $subject, $matches, $flags, $offset);
    $matches = remove_numeric_keys($matches);
    return $c;
}

function remove_numeric_keys(&$array)
{
    foreach ($array as $key => $value)
    {
        if (is_int($key))
        {
            unset($array[$key]);
        }
    }
    return $array;

}

function show_help()
{
    if (isset($_GET['c']) && isset($_GET['f']))
    {
        $parts = explode(" ", trim($_GET['c']), 2);
        return $parts[0] == preg_match(sprintf("/%s/i", HELP_TRIGGER));
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
    if (isset($_REQUEST['c'], $_GET['f']))
    {
        // compensate for JavaScript's odd escaping
        // we need to use $_REQUEST here because $_GET is automatically urldecoded
        $command = stripslashes($_REQUEST['c']);
        $file = stripslashes($_GET['f']);

        $shortcuts = parse_shortcut_file($file);
        $args = get_args_from_command($command);
        $shortcut = get_shortcut($shortcuts, $args['trigger']);
        $url = get_url($shortcut['url'], $args['args'], $command);

        // go!
        if (!show_help())
        {
            header('Location: ' . $url, true, 301);
        }
    }

    ?>
    <?php if (show_help()): ?>
        <p><span class="red">*</span> triggers may be followed by a search term</p>
        <?php $count = 0; $previous = null; ?>
        <?php foreach($shortcuts as $shortcut): ?>
            <?php if ($shortcut['group_name'] != $previous || $count < 1): ?>
                <?php if ($shortcut['group_name'] != $previous): ?></table><?php endif; ?>
                <header>
                    <h2><?php echo e($shortcut['group_name']); ?></h2>
                    <?php if ($shortcut['group_description']): ?><p class="lite"><?php echo e($shortcut['group_description']); ?></p><?php endif; ?>
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
            <?php $previous = $shortcut['group_name']; ?>
        <?php endforeach; ?>
        </table>
    <?php else: ?>
        <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="get">
            <label for="custom" id="label" class="out">shortcuts file:</label><input type="text" name="custom" value="http://" id="custom">
        </form>
        <a id="link" href="javascript:kid();function%20kid(){var%20nw=false;var%20c=window.prompt('Type%20`<?php echo e(HELP_TRIGGER); ?>`%20for%20a%20list%20of%20commands:');var%20h='';try{h=encodeURIComponent(window.location.hostname);}catch(e){h='about:blank'};var%20u=encodeURIComponent(window.location);var%20t=encodeURIComponent(document.title);if(c){if(c.substring(0,1)=='%20'){nw=true;}c=encodeURIComponent(c);var%20url='<?php echo url() ?>?c='+c+'&f='+'&d='+h+'&r='+u+'&t='+t+'&l='+document.getSelection();if(nw){var%20w=window.open(url);w.focus();}else{window.location.href=url;};};};"><?php echo e(NAME); ?></a>
    <?php endif; ?>
</body>
</html>
