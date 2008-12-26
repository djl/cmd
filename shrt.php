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
    return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
}

function get_args($arg)
{
    $args = preg_replace('/\s\s+/', ' ', trim($arg));
    $args = split('[ ]+', $args, 2);
    $args = array('trigger' => $args[0],
                  'term' => urlencode($args[1]));
    return $args;
}

function file_get_contents_curl($url) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
    
    $data = curl_exec($ch);
    curl_close($ch);
    
    return $data;
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
    $ref = $_SERVER['HTTP_REFERER'];
    $parsed = parse_url($ref);
    $domain = $parsed['host'];
    if (is_array($args))
    {
        $url = preg_replace("/%s/", $args['term'], $url);
    }
    else
    {
        $url = preg_replace("/%s/", $args, $url);
    }
    $url = preg_replace("/%d/", $domain, $url);
    $url = preg_replace("/%r/", $ref, $url);
    return $url;
}

function go()
{
    $args_array = get_args(trim($_GET['c']));
    $shrts = get_shrts($_GET['s']);
    $shrt = $shrts[$args_array['trigger']];
    if ($shrt)
    {
        $url = parse_location($shrt['url'], $args_array);
    }
    else
    {
        $shrt = $shrts['*'];
        if ($shrt)
        {
            $url = parse_location($shrt['url'], $args);
        }
        else
        {
            $url = parse_location(DEFAULT_URL, $args);
        }
    }
    header('Location: ' . $url);
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

if (isset($_GET['c']) and isset($_GET['s']) and !show_help()) 
{
    go();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>shrt</title>
    <style type="text/css">
    *{margin:0;padding:0;}
    body{background:#fff;border-top:4px solid #c86f4d;color:black;font:62.5% Helvetica,sans-serif;margin:0;padding:0;text-align:center;}
    div{background:#fff;margin:40px auto;width:50em;}
    .help{margin:0 0 3em;text-align:center;}
    h1{font-size:2em;line-height:6em;text-align:center;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:#c86f4d;}
    h2{color:#bbb;font-size:2em;font-weight:normal;margin:0 0 3em;text-align:center;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label,.out{line-height:1.8em !important;}
    label{font-size:1.4em;}
    em{color:#bbb;font-style:normal;font-weight:normal;}
    p{font-size:1.4em;margin:0 0 2em;line-height:2em;text-align:center;}
    p.note{font-size:9px;margin-top:10em;}
    a{color:#c86f4d;}
    a:hover{color:black;}
    a#link{background:#c86f4d;color:#fff;padding:4px;text-shadow:#c86f4d 1px 1px 1px;text-decoration:none;}
    a#link:hover{background:black;text-shadow:black 1px 1px 1px;}
    dl{font-size:1.4em;}
    dt{display:inline;}
    .out{color:#aaa;float:left;font-weight:bold;line-height:1.4em;margin-left:-220px;width:200px;text-align:right;}
    .red{color:#c86f4d;}
    .left { text-align:left;}
    code {color:#c86f4d;font-family:"panic sans",consolas,"bitstream vera sans",monaco,"courier new",monospace;}
    </style>
    <script type="text/javascript">function $(id){return document.getElementById(id)};</script>
</head>
<body>
    <div>
        <h1><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">shrt</a> <em><?php echo title(); ?></em></h1>
        <?php if (show_help()): ?>
            <p class="center"><span class="red">red lines</span> denote trigger is capable of taking a search term e.g. <code class="red">i stanley kubrick</code></p>
            <dl>
            <?php $shrts = get_shrts($_GET['s']); ?>
            <?php foreach($shrts as $shrt): ?>
                <dt<?php if ($shrt['search']): ?> class="red"<?php endif; ?>><?php echo $shrt['trigger'] ?></dt>
                <dd<?php if ($shrt['search']): ?> class="red"<?php endif; ?>><?php echo $shrt['title'] ?></dd>
            <?php endforeach; ?>
            </dl>
        <?php else: ?>
            <form action="<?php echo $_SERVER['SELF'] ?>" method="get">
                <label for="custom" id="label" class="out">Shortwave file URL:</label><input type="text" name="custom" value="http://" id="custom" onkeyup="$('link').href=$('link').href.replace(/&s=(.*?)\;/,'&s='+this.value+'\';')">
            </form>
            <p class="left"><span class="out">bookmarklet: </span><a id="link" href="javascript:shrt();function%20shrt(){var%20nw=false;var%20c=window.prompt('Type%20`<?php echo HELP_TRIGGER ?>`%20for%20a%20list%20of%20commands:');if(c){if(c.substring(0,1)=='%20'){c=c.replace(/^\s+|\s+$/g, '');nw=true;}var%20u='<?php echo full_url(); ?>?c='+c+'&s=';if(nw){var%20w=window.open(u);w.focus();}else{window.location.href=u;};};};">shrt</a></p>
            <p class="note"><a href="<?php echo SHRT_URL ?>">shrt</a> is an implementation of <a href="http://shortwaveapp.com/">Shortwave</a> by <a href="http://shauninman.com">Shaun Inman</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>