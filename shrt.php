<?php
define(DEFAULT_URL, 'http://www.google.com/search?q=%s');
define(USER_AGENT, 'shrt; Just grabbing your Shortwave shortcuts. (http://github.com/xvzf/shrt/tree/master).');

@ini_set('user_agent', USER_AGENT);

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
                  'term' => $args[1]);
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
            if (strstr($segments[1], "%s"))
            {
                $takes_search = True;
            }
            $shrts[$segments[0]] = array('trigger' => $segments[0],
                                         'url' => $segments[1],
                                         'title' => $segments[2],
                                         'takes_search' => $takes_search);
        }
    }
    return $shrts;
}

function parse_location($url, $args)
{
    $ref = $_SERVER['HTTP_REFERER'];
    $parsed = parse_url($ref);
    $domain = $parsed['host'];
    echo $args;
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

function go($file, $args)
{
    $args_array = get_args($args);
    $shrts = get_shrts($file);
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

if ($_GET['c'] and $_GET['c'] !== "help") 
{
    go($_GET['s'], $_GET['c']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>shrt</title>
    <style type="text/css">
    *{margin:0;padding:0;}
    body{background:#fff;border-top:4px solid #c86f4d;color:black;font:62.5% Helvetica,sans-serif;margin:0;padding:0;}
    div{background:#fff;margin:40px auto;width:500px;}
    .help{margin:0 0 3em;text-align:center;}
    h1{font-size:20px;line-height:6em;text-align:center;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:#c86f4d;}
    h2{font-size:1.4em;font-weight:normal;line-height:1.6em !important;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label{font-size:1.4em;line-height:1.8em !important;}
    em{color:#bbb;font-style:normal;font-weight:normal;}
    p{font-size:1.4em;line-height:2em;}
    p.note{background:#f4f4f4;font-size:0.8em;margin-top:10em;padding:1em;text-align:center;}
    a{color:#c86f4d;}
    a:hover{color:black;}
    a#link{background:#c86f4d;color:#fff;padding:4px;text-shadow:#c86f4d 1px 1px 1px;text-decoration:none;}
    a#link:hover{background:black;text-shadow:black 1px 1px 1px;}
    table{font-size:1.4em;margin:0 auto;}
    td{padding:0.2em;width:50%;}
    .out{color:#aaa;float:left;font-weight:bold;line-height:1.4em;margin-left:-220px;width:200px;text-align:right;}
    .red{color:#c86f4d;}
    </style>
    <script type="text/javascript">function $(id){return document.getElementById(id)};</script>
</head>
<body>
    <div>
        <h1><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">shrt</a> <em>...because Saft is broken in the WebKit nightlies</em></h1>
        <?php if ($_GET['c'] == help): ?>
            <?php if ($_GET['s']): ?>
                <p class="help">Lines in red indicate that a trigger is capable of taking a search&nbsp;term.</p>
                <h2 class="out">Available triggers: </h2>
                <table>
                <?php $shrts = get_shrts($_GET['s']); ?>
                <?php foreach($shrts as $shrt): ?>
                    <tr<?php if ($shrt['takes_search']): ?> class="red"<?php endif; ?>>
                        <td><?php echo $shrt['trigger'] ?></td>
                        <td><?php echo $shrt['title'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </table>
        <?php else: ?>
            <form action="." method="get">
                <label for="custom" id="label" class="out">Shortwave file URL:</label><input type="text" name="custom" value="http://" id="custom" onkeyup="$('link').href=$('link').href.replace(/&s=(.*?)\;/,'&s='+this.value+'\';')">
            </form>
            <h2> <span class="out">bookmarklet:</span><a id="link" href="javascript:shrt();function%20shrt(){var%20c=window.prompt('Type%20`help`%20for%20a%20list%20of%20commands:');if(c){var%20u='<?php echo full_url(); ?>?c='+c+'&s=';if(c.substring(0,1)=='%20'){var%20w=window.open(u);w.focus();}else{window.location.href=u;};};};">shrt</a></h2>
        <?php endif; ?>
        <p class="note">Based on <a href="http://shortwaveapp.com/">Shortwave</a> by <a href="http://shauninman.com">Shaun Inman</a></p>
    </div>
</body>
</html>