<?php
define('DEFAULT_DELIMITER', ',');
define('DEFAULT_URL', 'http://www.google.com/search?q=%s');
define('HELP_TITLE', '...your commands');
define('HELP_TRIGGER', 'help');
define('SHRT_URL', 'http://github.com/xvzf/shrt/tree/master');
define('TITLE', '...because Saft is broken in the WebKit nightlies');
define('USERAGENT', 'Grabbing your Shortwave shortcuts. (' . SHRT_URL . '); Allow like Gecko');
define('IS_LOCKED', false);

ini_set('user_agent', USERAGENT);

function encode(&$val, $key)
{
    return $val = urlencode($val);
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

function get_file($url) 
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
    $data = curl_exec($ch);
    if(curl_error($ch))
    {
        echo curl_error($ch);
    }
    curl_close($ch);
    return $data;
}

function get_args($arg)
{
    $args = preg_replace('/\s\s+/', ' ', trim($arg));
    preg_match('/^(?<trigger>\w+)(\s+(?<terms>.*))?/', $args, $matches);
    if (!$matches){ return; }
    
    if (!array_key_exists('terms', $matches))
    {
        $matches['terms'] = "";
    }
    $terms = explode(DEFAULT_DELIMITER, $matches['terms']);
    array_walk($terms, 'encode');

	$matches['command'] = $arg;
    $matches['terms'] = $terms;
    return $matches;
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
        if (!preg_match('/^>|\n+/i', $line) && $line != "")
        {
            $segments = preg_split('/[ ]+/', $line, 3);
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

function get_url($args, $shrts)
{
	// Referrer
    $ref = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "";
    $parsed = parse_url($ref);
    $domain = !empty($parsed['host']) ? $parsed['host'] : "";

	$pattern = "/(%s)|(%{.*})/";
	// Check if shrt exists
	if (array_key_exists($args['trigger'], $shrts))
	{
	    $shrt = $shrts[$args['trigger']];
		$url = $shrt['url'];
		foreach ($args['terms'] as $term)
		{
			$url = preg_replace($pattern, $term, $url, 1);
		}
	}
	// Doesn't exist, check for untriggered command
	else if (array_key_exists('*', $shrts))
	{
	    $url = preg_replace($pattern, $args['command'], $shrts['*']);
        $url = $url['url'];
	}
	else
	{
	    $url = preg_replace($pattern, $args['command'], DEFAULT_URL);
	}
	// Any left over arguments?
	$url = str_replace('%s', '', $url);
    preg_match_all("/(?<wrap>%{(?<arg>.*)}$)/", $url, $defaults);
    $url = str_replace($defaults['wrap'], $defaults['arg'], $url);
    $url = preg_replace("/%d/", $domain, $url);
    $url = preg_replace("/%r/", $ref, $url);
    return $url;

}

// Go go gadget shrt!
if (isset($_GET['c']) and isset($_GET['f']) and !show_help()) 
{
    $args = get_args(urldecode($_GET['c']));
    $shrts = get_shrts(urldecode($_GET['f']));
	if ($shrts)
	{
        header('Location: ' . get_url($args, $shrts));
	}
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
    h1{font-size:2em;line-height:6em;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:<?php echo $color; ?>;}
    h2{color:#bbb;font-size:2em;font-weight:normal;margin:0 0 3em;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label,.out{line-height:1.8em !important;}
    label{font-size:1.4em;}
    em{color:#bbb;font-style:normal;font-weight:normal;}
    p{font-size:1.4em;margin:0 0 2em;line-height:2em;}
    p.note{font-size:1.1em;margin-top:10em;padding:1em;}
    a{color:<?php echo $color; ?>;}
    a:hover{color:black;}
    a#link{background:<?php echo $color; ?>;color:#fff;padding:4px;text-shadow:<?php echo $color; ?> 1px 1px 1px;text-decoration:none;}
    a#link:hover{background:black;text-shadow:black 1px 1px 1px;}
    table{font-size:1.4em;margin:4em auto;width:100%;}
    td{padding:10px;}
    code {color:#777;font: 1.1em consolas,"panic sans","bitstream vera sans","courier new",monaco,monospace;}
    .out{color:#aaa;float:left;font-weight:bold;line-height:1.4em;margin-left:-220px;width:200px;text-align:right;}
    .red{color:<?php echo $color; ?> !important;}
    .left{text-align:left;}
    .alt{background:#eee;}
    </style>
    <script type="text/javascript">function $(id){return document.getElementById(id)};</script>
</head>
<body>
    <header><h1><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">shrt</a> <em><?php echo title(); ?></em></h1></header>
    <?php if (show_help()): ?>
        <?php $shrts = get_shrts($_GET['f']); ?>
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
            <label for="custom" id="label" class="out">Shortwave file URL:</label><input<?php if (IS_LOCKED): ?> disabled="disabled" <?php endif; ?> type="text" name="custom" value="http://" id="custom" onkeyup="$('link').href=$('link').href.replace(/&f=(.*?)\;/,'&f='+this.value+'\';')">
        </form>
        <p class="left"><span class="out">bookmarklet: </span><a id="link" href="javascript:shrt();function%20shrt(){var%20nw=false;var%20c=window.prompt('Type%20`help`%20for%20a%20list%20of%20commands:');if(c){if(c.substring(0,1)=='%20'){c=c.replace(/^\s+|\s+$/g,'%20');nw=true;}c=escape(c);var%20u='<?php echo url() ?>?c='+c+'&f=';if(nw){var%20w=window.open(u);w.focus();}else{window.location.href=u;};};};">shrt</a></p>
    <?php endif; ?>
</body>
</html>