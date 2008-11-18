<?php

function full_url()
{
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
}

if (isset($_GET['c']))
{
    $c = preg_replace('/\s\s+/', ' ', trim($_GET['c']));
    $c = split('[ ]+', $c, 2);
    $t = $c[1];
    $c = $c[0];
    if (isset($_GET['s']))
    {
        $file = fopen($_GET['s'], "r") or die('file not found');
        while (!feof($file))
        {
            $line = fgets($file, 4096);
            $line = trim($line);
            $line = preg_replace('/\s\s+/', ' ', trim($line));
            // Ignore comments
            if (substr($line, 0, 1) != ">")
            {
                if (preg_match("/^".$c."\b/", $line))
                {
                    // Break out wave into trigger, URL, title
                    $waves = explode(" ", $line);
                    $url = preg_replace('/%s/', $t, $waves[1]);
                    header("Location: $url");
                }
            }
        }   
    }
}
else
{
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>shrt</title>
    <style type="text/css">
    *{margin:0;padding:0;}
    body{background:#fff;color:black;font:14px Helvetica,sans-serif;margin:0;padding:0;}
    div{background:#fff;margin:40px auto;width:400px;}
    h1{font-size:20px; line-height: 4em; }
    h1 em{color:#bbb; font-style:normal;}
    h2{font-size:1em;font-weight:normal;}
    input{color:#aaa;font:1em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label{font-size:1em;line-height:2em !important;}
    p{margin-top:10em;font-size:0.8em;}
    a{color:#c86f4d;}
    a:hover { color: black;}
    a#link{background:#c86f4d;color:#fff;padding:4px;text-shadow:#c86f4d 1px 1px 1px;text-decoration:none;}
    a#link:hover{background:black;text-shadow:black 1px 1px 1px;}
    .out{float:left;font-weight:bold;line-height:1em;margin-left:-205px;width:200px;text-align:right;}
    </style>
    <script type="text/javascript">function $(id){return document.getElementById(id)};</script>
</head>
<body>
    <div>
    <h1>shrt <em>shortwave for the paranoid</em></h1>
        <form action="." method="get">
        <label for="custom" id="label" class="out">Shortwave file URL:</label><input type="text" name="custom" value="http://" id="custom" onkeyup="$('link').href=$('link').href.replace(/&s=(.*?)\;/,'&s='+this.value+'\';')">
    </form>
    <h2> <span class="out">bookmarklet:</span><a id="link" href="javascript:goGoGadgetTrigger();function%20goGoGadgetTrigger(){var%20c=window.prompt('`help` lists commands');if(c){var%20u='<?php echo full_url(); ?>?c='+c+'&s=';if(c.substring(0,1)=='%20'){var%20w=window.open(u);w.focus();}else{window.location.href=u;};};};">shrt</a></h2>
    <p>Based on <a href="http://shortwaveapp.com/">Shortwave</a> by <a href="http://shauninman.com">Shaun Inman</a></p>
    </div>
</body>
</html>
<?php 
} 
?>