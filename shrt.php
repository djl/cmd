<?php
define('DEFAULT_URL', 'http://www.google.com/search?q=%s');
define('SHRT_URL', 'http://github.com/xvzf/shrt/tree/master');
define('USER_AGENT', 'shrt; Just grabbing your Shortwave shortcuts. (' . SHRT_URL . ')');
define('HELP_TRIGGER', 'help');
define('TITLE', '...because Saft is broken in the WebKit nightlies');
define('HELP_TITLE', '...your commands');

ini_set('user_agent', USER_AGENT);

function full_url()
{
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
    return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['PHP_SELF'];
}

function file_get_contents_curl($url) 
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function get_args($arg)
{
    $args = preg_replace('/\s\s+/', ' ', trim($arg));
    $args = split('[ ]+', $args, 2);
    $term = " ";
    if (count($args) > 1) $term = urlencode($args[1]);
    $args = array('trigger' => $args[0],
                  'term' => urldecode($term));
    return $args;
}

function get_file($url)
{
    $headers = get_headers($url, 1);
    if ($headers['Content-Type'] != "text/plain")
    {
        die("<p>Remote file <em>$url</em> was not a text file!</p>");
    }
    $file = file_get_contents_curl($url);
    return $file;
}

function get_shrts($file)
{
    $file = get_file($file);
    $lines = explode("\n", $file);
    $shrts = array();
    foreach ($lines as $line)
    {
        $line = preg_replace('/\s\s+/', ' ', trim($line));
        // Kill blank lines, comments
        if ($line != '' && (substr($line, 0, 1) != ">"))
        {
            $segments = split('[ ]+', $line, 3);
            $takes_search = False;
            if (strstr($segments[1], "%s") && $segments[0] != "*")
            {
                $takes_search = True;
            }
            $shrts[$segments[0]] = array('trigger' => $segments[0],
                                         'url' => $segments[1],
                                         'title' => $segments[2],
                                         'search' => $takes_search);
        }
    }
    return $shrts;
}

function parse_location($url, $args)
{
    $ref = (array_key_exists('HTTP_REFERER', $_SERVER)) ? $_SERVER['HTTP_REFERER'] : "";

    $parsed = parse_url($ref);
    $domain = array_key_exists('host', $parsed) ? $parsed['host'] : "";
    $term = is_array($args) ? $args['term'] : $args;
    $url = preg_replace("/%s/", $term, $url);
    $url = preg_replace("/%d/", $domain, $url);
    $url = preg_replace("/%r/", $ref, $url);
    return $url;
}

function show_help()
{
    return isset($_GET['s']) and isset($_GET['c']) and trim($_GET['c']) == HELP_TRIGGER;
}

function title()
{
    if (show_help()) { return HELP_TITLE; }
    return TITLE;
}

// Go go gadget shrt!
if (isset($_GET['c']) and isset($_GET['s']) and !show_help()) 
{
    $args_array = get_args(trim($_GET['c']));
    $shrts = get_shrts($_GET['s']);
    if (array_key_exists($args_array['trigger'], $shrts))
    {
        $shrt = $shrts[$args_array['trigger']];
        $url = parse_location($shrt['url'], $args_array);
    }
    else
    {
        if (array_key_exists('*', $shrts))
        {
            $shrt = $shrts['*'];
            $url = parse_location($shrt['url'], $_GET['c']);
        }
        else
        {
            $url = parse_location(DEFAULT_URL, $args_array);
        }
    }
    header('Location: ' . $url);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>shrt</title>
    <style type="text/css">
    *{margin:0;padding:0;}
    body{background:#fff;border-top:4px solid #c86f4d;color:black;font:62.5% Helvetica,sans-serif;text-align:center;}
    div{margin:4em auto;width:50em;}
    .help{margin:0 0 3em;}
    h1{font-size:2em;line-height:6em;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:#c86f4d;}
    h2{color:#bbb;font-size:2em;font-weight:normal;margin:0 0 3em;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label,.out{font-size:1.em;line-height:1.8em !important;}
    label{font-size:1.4em;}
    em{color:#bbb;font-style:normal;font-weight:normal;}
    p{font-size:1.4em;margin:0 0 2em;line-height:2em;}
    p.note{font-size:1.1em;margin-top:10em;padding:1em;}
    a{color:#c86f4d;}
    a:hover{color:black;}
    a#link{background:#c86f4d;color:#fff;padding:4px;text-shadow:#c86f4d 1px 1px 1px;text-decoration:none;}
    a#link:hover{background:black;text-shadow:black 1px 1px 1px;}
    table{font-size:1.4em;margin:3em auto;}
    tr{margin:0 0 22em;}
    td{padding:10px;text-align:right;}
    code {color:#aaa;font: 1.1em monaco,"panic sans",consolas,"bitstream vera sans","courier new",monospace;}
    .out{color:#aaa;float:left;font-weight:bold;line-height:1.2em;margin-left:-220px;width:200px;text-align:right;}
    .red{color:#c86f4d !important;}
    .left{text-align:left;}
    </style>
    <script type="text/javascript">function $(id){return document.getElementById(id)};</script>
</head>
<body>
    <div>
        <h1><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">shrt</a> <em><?php echo title(); ?></em></h1>
        <?php if (show_help()): ?>
            <p><span class="red">*</span> triggers may be followed by a search term. e.g. <code>i stanley kubrick</code></p>
            <table>
            <?php $shrts = get_shrts($_GET['s']); ?>
            <?php foreach($shrts as $shrt): ?>
                <tr>
                    <td><code><?php echo $shrt['trigger'] ?></code></td>
                    <td class="left"><?php echo $shrt['title'] ?><?php if ($shrt['search']): ?> <span class="red">*</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </table>
        <?php else: ?>
            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="get">
                <label for="custom" id="label" class="out">Shortwave file URL:</label><input type="text" name="custom" value="http://" id="custom" onkeyup="$('link').href=$('link').href.replace(/&s=(.*?)\;/,'&s='+this.value+'\';')">
            </form>
            <p class="left"><span class="out">bookmarklet: </span><a id="link" href="javascript:shrt();function%20shrt(){var%20nw=false;var%20c=window.prompt('Type%20`help`%20for%20a%20list%20of%20commands:');if(c){c=escape(c);if(c.substring(0,1)=='%20'){c=c.replace(/^\s+|\s+$/g,'%20');nw=true;}var%20u='http://shrt.dev/shrt.php?c='+c+'&s=';if(nw){var%20w=window.open(u);w.focus();}else{window.location.href=u;};};};">shrt</a></p>
        <?php endif; ?>
    </div>
</body>
</html>