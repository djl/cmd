<?php
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
    
    function get_file($url)
    {
        $file = fopen($url, "r");
        if (!$file) return False;
        $headers = get_headers($_GET['s'], 1);
        if ($headers['Content-Type'] != "text/plain")
        {
            die("<p>Remote file was not a text file!</p>");
        }
        return $file;
    }

    function get_shrts($file)
    {
        $file = get_file($file);
        while (!feof($file))
        {
            $line = fgets($file, 4096);
            $line = trim($line);
            $line = preg_replace('/\s\s+/', ' ', trim($line));
            if ($line != "")
            {
                 // Ignore comments
                if (substr($line, 0, 1) != ">")
                {
                    // Break out wave into trigger, URL, title
                    $shrt = explode(" ", $line);
                    $takes_search = False;
                    if (strstr($shrt[1], "%s")) 
                    { 
                        $takes_search = True;
                    }
                    $shrts[] = array('trigger' => $shrt[0],
                                     'url' => $shrt[1],
                                     'title' => $shrt[2],
                                     'takes_search' => $takes_search);
                }   
            }
        }
        return $shrts;
    }

    function get_shrt($file, $args)
    {
        $shrts = get_shrts($file);
        $trigger = $args['trigger'];
        $term = $args['term'];
        foreach ($shrts as $shrt) 
        {
            if ($shrt['trigger'] == $trigger)
            {
                return $shrt;
            }
        }
        return False;
    }

    function go($file, $args)
    {
        $args = get_args($args);
        $shrt = get_shrt($file, $args);
        $url = preg_replace("/%s/", $args['term'], $shrt['url']);
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
    body{background:#fff;border-top:4px solid #c86f4d;color:black;font:62.5% Helvetica,sans-serif;margin:0;padding:0;}
    div{background:#fff;margin:40px auto;width:400px;}
    .help{margin:0 0 3em;}
    pre{background:#eee;color:#000;font:10px/16px 'Monaco','Courier New',Courier,monospace;padding:1em;}
    code{font:10px/16px 'Monaco','Courier New',Courier,monospace;}
    h1{font-size:20px;line-height:6em;}
    h1 a:link,h1 a:visited{color:black;text-decoration:none;}
    h1 a:hover,h1 a:active,h1 a:focus{color:#c86f4d;}
    h2{font-size:1.4em;font-weight:normal;}
    input{font:1.4em Helvetica,sans-serif;margin:0 0 2em;padding:0.2em;width:100%;}
    label{font-size:1.4em;line-height:1.8em !important;}
    em{color:#bbb;font-style:normal;}
    p{font-size:1.4em;line-height:2em;}
    p.note{background:#f4f4f4;font-size:0.8em;margin-top:10em;padding:1em;text-align:center;}
    a{color:#c86f4d;}
    a:hover{color:black;}
    a#link{background:#c86f4d;color:#fff;padding:4px;text-shadow:#c86f4d 1px 1px 1px;text-decoration:none;}
    a#link:hover{background:black;text-shadow:black 1px 1px 1px;}
    .out{color:#aaa;float:left;font-weight:bold;line-height:1.4em;margin-left:-205px;width:200px;text-align:right;}
    .red{color:#c86f4d;}
    table{font-size:1.4em;}
    td{padding:0.2em;width:50%;}
    .right{text-align:right;}
    </style>
    <script type="text/javascript">function $(id){return document.getElementById(id)};</script>
</head>
<body>
    <div>
        <h1><a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">shrt</a> <em>Shortwave for the paranoid</em></h1>
        <?php if ($_GET['c'] and $_GET['c'] !== "help"):?>
            <?php go($_GET['s'], $_GET['c'])?>
        <?php else: ?>
            <?php if ($_GET['c'] == help): ?>
                <?php if (get_shrts($_GET['s'])): ?>
                    <p class="help">Triggers in red means that a trigger takes a search term.</p>
                    <h2 class="out">Available triggers: </h2>
                    <table>
                    <?php foreach(get_shrts($_GET['s']) as $shrt): ?>
                        <tr<?php if ($shrt['takes_search']): ?> class="red"<?php endif; ?>>
                            <td class="right"><strong><?php echo $shrt['trigger'] ?></strong></td>
                            <td><?php echo $shrt['title'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </table>
            <?php else: ?>
                <form action="." method="get">
                    <label for="custom" id="label" class="out">Shortwave file URL:</label><input type="text" name="custom" value="http://" id="custom" onkeyup="$('link').href=$('link').href.replace(/&s=(.*?)\;/,'&s='+this.value+'\';')">
                </form>
                <h2> <span class="out">bookmarklet:</span><a id="link" href="javascript:goGoGadgetTrigger();function%20goGoGadgetTrigger(){var%20c=window.prompt('Type `help` to see your commands');if(c){var%20u='<?php echo full_url(); ?>?c='+c+'&s=';if(c.substring(0,1)=='%20'){var%20w=window.open(u);w.focus();}else{window.location.href=u;};};};">shrt</a></h2>
            <?php endif; ?>
        <?php endif;?>
        <p class="note">Based on <a href="http://shortwaveapp.com/">Shortwave</a> by <a href="http://shauninman.com">Shaun Inman</a></p>
    </div>
</body>
</html>