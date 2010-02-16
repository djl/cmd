<?php
define('ARGUMENT_DELIMITER', ',');
define('DEFAULT_URL', 'http://www.google.com/search?q=%c');
define('HELP_TITLE', '...your commands');
define('HELP_TRIGGER', 'help');
define('SHRT_URL', 'http://github.com/xvzf/shrt/tree/master');
define('TITLE', '...because Saft is broken in the WebKit nightlies');
define('USERAGENT', 'Grabbing your shortcuts. (' . SHRT_URL . '); Allow like Gecko');
define('IS_LOCKED', FALSE);
define('FILE_MATCH', '');

ini_set('user_agent', USERAGENT);

function encode(&$val)
{
    $val = urlencode($val);
}

function url()
{
    $protocol = array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    return $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

function show_help()
{
    return isset($_GET['f']) and isset($_GET['c']) and trim($_GET['c']) == HELP_TRIGGER;
}

function title()
{
    if (show_help()) { return HELP_TITLE; }
    return TITLE;
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

function preg_match_named($pattern, $subject, &$matches, $flags=null, $offset=null)
{
    $c = preg_match($pattern, $subject, $matches, $flags, $offset);
    $matches = remove_numeric_keys($matches);
    return $c;
}

function preg_match_all_named($pattern, $subject, &$matches, $flags=null, $offset=null)
{
    $c = preg_match_all($pattern, $subject, $matches, $flags, $offset);
    $matches = remove_numeric_keys($matches);
    return $c;
}

function get_file($url)
{
    if (FILE_MATCH != '' && preg_match(FILE_MATCH, $url) == FALSE)
    {
        die("<p><strong class=\"error\">Warning:</strong> The URL <strong>$url</strong> did not match the required pattern.</p>");
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    if(curl_error($ch))
    {
        die(curl_error($ch));
    }
    curl_close($ch);
    return $data;
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

    $arguments = explode(ARGUMENT_DELIMITER, $matches['args']);
    $kwargs = array();

    $count = 0;
    foreach ($arguments as $argument)
    {
        preg_match_named('/^(?<key>(\w|\p{P})+)=(?<value>.*)$/', $argument, $named_args);
        if (array_key_exists('key', $named_args))
        {
            if ($named_args['key'])
            {
                $kwargs[$named_args['key']] = $named_args['value'];
                unset($arguments[$count]);
            }
        }
        $count++;
    }

    array_walk($arguments, 'encode');

    $matches['command'] = $command;
    $matches['args'] = $arguments;
    $matches['kwargs'] = $kwargs;
    return $matches;
}

function get_shortcut($file, $trigger)
{
    $file = get_file($file);
    $lines = explode("\n", $file);
    $shortcuts = array();
    foreach($lines as $line)
    {
        $line = preg_replace('/\s\s+/', ' ', trim($line));
        // Kill blank lines, comments and '#kill-defaults' lines
        if (!preg_match('/^>|#/', $line) && $line != "")
        {
            list($s_trigger, $s_url, $s_title) = preg_split('/[ ]+/', $line, 3);
            if ($trigger == $s_trigger)
            {
                return $s_url;
            }
            else
            {
                $shortcuts[$s_trigger] = $s_url;
            }
        }
    }
    if (array_key_exists('*', $shortcuts))
    {
        return $shortcuts['*'];
    }
    else
    {
        return DEFAULT_URL;
    }
}

function get_shortcuts($file)
{
    $file = get_file($file);
    $lines = explode("\n", $file);
    $shortcuts = array();
    foreach ($lines as $line)
    {
        $line = preg_replace('/\s\s+/', ' ', trim($line));
        // Kill blank lines, comments, '#kill-defaults' and @groups
        if (!preg_match('/^>|#|@/', $line) && $line != "")
        {
            $segments = preg_split('/[ ]+/', $line, 3);
            $takes_search = (strstr($segments[1], "%s") && $segments[0] != "*");
            $shortcuts[$segments[0]] = array('trigger' => $segments[0],
                                             'url' => $segments[1],
                                             'title' => $segments[2],
                                             'search' => $takes_search);
        }
    }
    return $shortcuts;
}

function get_url($shortcut_url, $args, $kwargs, $command)
{
    $filters = array('parse_kwargs',
                     'parse_simple',
                     'parse_optional',
                     'parse_default');
    
    foreach ($filters as $filter)
    {
        $shortcut_url = $filter($shortcut_url, $args, $kwargs, $command);
    }
    return $shortcut_url;
}

function parse_simple($url, $args, $kwargs, $command)
{
    $url = preg_replace("/%d/", urldecode($_GET['d']), $url);
    $url = preg_replace("/%r/", urlencode($_GET['r']), $url);
    $url = preg_replace("/%t/", urldecode($_GET['t']), $url);
    $url = preg_replace("/%c/", urldecode($_GET['c']), $url);
    return $url;
}

function parse_optional($url, $args, $kwargs, $command)
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

function parse_kwargs($url, $args, $kwargs, $command)
{
    if (preg_match('/%{[\w|\p{P}]+:(.*)}/', $url))
    {
        $parts = preg_split('/%{([\w|\p{P}]+:.*)}/', $url, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $furl = array_shift($parts);
        $count = 0;
        foreach ($parts as $part)
        {
            if (preg_match('/[\w|\p{P}]+:(.*)/', $part))
            {

                if (preg_match_named('/(?<wrap>(?<key>[\w|\p{P}]+):(?<value>.*))/', $part, $matches))
                {
                    if (array_key_exists($matches['key'], $kwargs))
                    {
                        $pattern = "/(%s)|(%{.*?})/";
                        $part = str_replace($matches['wrap'], $kwargs[$matches['key']], $matches['value']);
                        $part = preg_replace($pattern, $kwargs[$matches['key']], $part);
                        $furl .= $part;
                    }
                }
            }
            else
            {
                $url = str_replace('%s', $args[$count], $part);
                $count++;
            }
        }
        $url = $furl;
    }
    return $url;
}

function parse_default($url, $args, $kwargs, $command)
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
            $furl .= $part;
         }
         $url = $furl;
    }
    return $url;
}


function go($command, $file)
{
    $args = get_args_from_command($command);
    $shortcut = get_shortcut($file, $args['trigger']);
    header('Location: ' . get_url($shortcut, $args['args'], $args['kwargs'], $command));
}

// Go go gadget shortcut!
if (isset($_GET['c']) and isset($_GET['f']) and !show_help())
{
    go(urldecode($_GET['c']), urldecode($_GET['f']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>shrt</title>
    <style type="text/css">
    <?php $color = "#c86f4d"; ?>
    *{margin:0;padding:0;}
    html{background:#fff;border-top:4px solid <?php echo $color; ?>;color:black;font:62.5% Helvetica,sans-serif;text-align:center;}
    body{margin:4em auto;width:50em;}
    h1{font-size:2em;line-height:6em;text-shadow: 0 -1px 1px #FFF;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:<?php echo $color; ?>;}
    h2{color:#bbb;font-size:2em;font-weight:normal;margin:0 0 3em;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label,.out{line-height:1.8em !important;text-shadow: 0 -1px 1px #FFF;}
    label{font-size:1.4em;}
    em{color:#bbb;font-style:normal;font-weight:normal;}
    p{font-size:1.4em;margin:0 0 2em;line-height:2em;}
    p.note{font-size:1.1em;margin-top:10em;padding:1em;}
    a{color:<?php echo $color; ?>;}
    a:hover{color:black;}
    a#link{background:<?php echo $color; ?>;color:#fff;padding:4px;text-shadow: 1px 1px 1px <?php echo $color; ?>;text-decoration:none;}
    a#link:hover{background:black;text-shadow:1px 1px 1px black;}
    table{font-size:1.4em;margin:4em auto;width:100%;}
    td{padding:10px;}
    code {color:#777;font: 1.1em consolas,"panic sans","bitstream vera sans","courier new",monaco,monospace;}
    .out{color:#aaa;float:left;font-weight:bold;line-height:1.4em;margin-left:-220px;width:200px;text-align:right;}
    .red{color:<?php echo $color; ?> !important;}
    .left{text-align:left;}
    .alt{background:#eee;}
    .error{color:red;font-weight:bold;}
    </style>
    <script type="text/javascript">function $(id){return document.getElementById(id)};</script>
</head>
<body>
    <header><h1><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">shrt</a> <em><?php echo title(); ?></em></h1></header>
    <?php if (show_help()): ?>
        <?php $shrts = get_shortcuts($_GET['f']); ?>
        <p><span class="red">*</span> triggers may be followed by a search term. e.g. <code>i stanley kubrick</code></p>
        <table cellspacing="0">
            <thead>
                <tr>
                    <th>Trigger</th>
                    <th>Title</th>
                </tr>
            </thead>
        <?php $count=0;?>
        <?php foreach($shrts as $shrt): ?>
            <tr<?php if ($count % 2): ?> class="alt"<?php endif; ?>>
                <td><code><?php echo $shrt['trigger'] ?></code></td>
                <td><?php echo $shrt['title'] ?><?php if ($shrt['search']): ?> <span class="red">*</span><?php endif; ?></td>
            </tr>
            <?php $count++; ?>
        <?php endforeach; ?>
        </table>
    <?php else: ?>
        <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="get">
            <label for="custom" id="label" class="out">Shortcut file:</label><input<?php if (IS_LOCKED): ?> disabled="disabled" <?php endif; ?> type="text" name="custom" value="http://" id="custom" onkeyup="$('link').href=$('link').href.replace(/&f=(.*?)\'/,'&f='+this.value+'\'')">
        </form>
        <p class="left"><span class="out">bookmarklet: </span><a id="link" href="javascript:shrt();function%20shrt(){var%20nw=false;var%20c=window.prompt('Type%20`help`%20for%20a%20list%20of%20commands:');var%20h='';try{h=escape(window.location.hostname);}catch(e){h='about:blank'};var%20u=escape(window.location);var%20t=escape(document.title);if(c){if(c.substring(0,1)=='%20'){nw=true;}c=escape(c);var%20url='<?php echo url() ?>?c='+c+'&f='+'&d='+h+'&r='+u+'&t='+t;if(nw){var%20w=window.open(url);w.focus();}else{window.location.href=url;};};};">shrt</a></p>
    <?php endif; ?>
</body>
</html>