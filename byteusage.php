<?php
/**
* NOTICE OF LICENSE
*
*  @author    Kjeld Borch Egevang
*  @copyright 2018 Kjeld Borch Egevang
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*  $Date: 2018/06/22 18:34:56 $
*  E-mail: kjeld@mail4us.dk
*/
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Directory Size Usage</title>
        <style>
            table { border-collapse: collapse }
            th, td { border: 1px solid black }
            .right { text-align: right }
            .btn { border: none; padding: 0px }
            .btn:hover { background: lightblue }
        </style>
        <script type="text/javascript">
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
    </head>
    <body>
        <pre>

<?php

// Here you can list the relevant directories
$homeDirs = array(
    'Home' => '.',
    'Base' => '..',
);


function AddTag($str, $tag = '', $class = '')
{
    if (!$tag) {
        return sprintf('<%s>', $str);
    }
    if (is_array($str)) {
        $str = implode('', $str);
    }
    if ($class) {
        $class = sprintf(' class="%s"', $class);
    }
    return sprintf('<%s%s>%s</%s>', $tag, $class, $str, $tag);
}


function AddAttr($str, $attr, $val)
{
    $pos = strpos($str, '>');
    if ($pos !== false) {
        $replace = sprintf(' %s="%s">', $attr, $val);
        $str = substr_replace($str, $replace, $pos, 1);
    }
    return $str;
}


function CheckDir($dir)
{
    global $homeDirs;
    foreach ($homeDirs as $name => $homeDir) {
        if (substr($dir, 0, strlen($homeDir)) == $homeDir) {
            return true;
        }
    }
    return false;
}


function GetDirectorySize($dir)
{
    $sizes = new stdClass();
    $sizes->bytes = 0;
    $sizes->directories = 0;
    $sizes->files = 0;
    $entries = scandir($dir);
    if ($entries === false) {
        printf("Can't get size of %s\n", $dir);
        return;
    }
    foreach ($entries as $entry) {
        $fentry = $dir.'/'.$entry;
        if (is_file($fentry)) {
            $sizes->files++;
            $sizes->bytes += filesize($fentry);
        } elseif ($entry != '..' && $entry != '.' && is_dir($fentry)) {
            $sizes->directories++;
            $rsizes = GetDirectorySize($fentry);
            $sizes->bytes += $rsizes->bytes;
            $sizes->directories += $rsizes->directories;
            $sizes->files += $rsizes->files;
        }
    }
    return $sizes;
}


function RemoveDir($dir)
{
    if (CheckDir($dir) == false) {
        global $homeDirs;
        printf("Directory %s not within %s\n", $dir, implode(', ',$homeDirs));
        return;
    }

    if (is_file($dir)) {
        if (!unlink($dir)) {
            printf("Can't unlink %s\n", $dir);
        }
        return;
    }

    $entries = scandir($dir);
    if ($entries === false) {
        printf("Can't remove %s\n", $dir);
        return;
    }
    foreach ($entries as $entry) {
        $fentry = $dir.'/'.$entry;
        if (is_file($fentry)) {
            if (!unlink($fentry)) {
                printf("Can't unlink %s\n", $fentry);
            }
        } elseif ($entry != '..' && $entry != '.' && is_dir($fentry)) {
            RemoveDir($fentry);
        }
    }
    if (!rmdir($dir)) {
        printf("Can't rmdir %s\n", $dir);
    }
}


function ListDir($dir)
{
    global $homeDirs;
    $dir = realpath($dir);
    if (CheckDir($dir) == false) {
        global $homeDirs;
        printf("Directory %s not within %s\n", $dir, implode(', ',$homeDirs));
        return;
    }
    $entries = scandir($dir);
    if ($entries === false) {
        printf("Can't list %s\n", $dir);
        $entries = array();
    }
    $out = AddTag($dir, 'h2');
    $dirs = array();
    $files = array();
    $rows = array(
        AddTag('Directory', 'th'),
        AddTag('Bytes', 'th'),
        AddTag('Files', 'th'),
        AddTag('Directories', 'th'),
        AddTag('Action', 'th')
    );
    if ($dir == '/') {
        $dir = '';
    }
    foreach ($entries as $entry) {
        $fentry = $dir.'/'.$entry;
        if ($entry == '.' ||  $entry == '..') {
            continue;
        }
        if (is_dir($fentry)) {
            $sizes = GetDirectorySize($fentry);
            $dirs[$entry] = $sizes;
        } else {
            $sizes = new stdClass();
            $sizes->bytes = filesize($fentry);
            $sizes->files = '';
            $sizes->directories = '';
            $files[$entry] = $sizes;
        }
    }
    arsort($dirs);
    arsort($files);
    $allfiles = array('..' => '');
    foreach ($dirs as $entry => $sizes) {
        $allfiles[$entry] = $sizes;
    }
    foreach ($files as $entry => $sizes) {
        $allfiles[$entry] = $sizes;
    }
    foreach ($allfiles as $entry => $sizes) {
        if ($sizes->bytes) {
            $sizes->bytes = number_format($sizes->bytes, 0, '.', ',');
        }
        if ($sizes->files) {
            $sizes->files = number_format($sizes->files, 0, '.', ',');
        }
        if ($sizes->directories) {
            $sizes->directories = number_format($sizes->directories, 0, '.', ',');
        }
        $fentry = $dir.'/'.$entry;
        $cells = array();
        if (substr($fentry, 0, 2) == './') {
            $fentry = substr($fentry, 2);
        }
        if ($entry == '..') {
            $value = explode('/', $dir);
            array_pop($value);
            $value = implode('/', $value);
            if (!$value) {
                $value = '/';
            }
            if (CheckDir($value) == false) {
                continue;
            }
            $button = AddTag($entry, 'button', 'btn');
            $button = AddAttr($button, 'name', 'dir');
            $button = AddAttr($button, 'value', $value);
            $cells[] = AddTag($button, 'td');
        }
        elseif (is_dir($fentry)) {
            $button = AddTag($entry, 'button', 'btn');
            $button = AddAttr($button, 'name', 'dir');
            $button = AddAttr($button, 'value', $fentry);
            $cells[] = AddTag($button, 'td');
        } else {
            $cells[] = AddTag($entry, 'td');
        }
        $cellStr = AddTag($sizes->bytes, 'td');
        $cells[] = AddAttr($cellStr, 'class', 'right');
        $cellStr = AddTag($sizes->files, 'td');
        $cells[] = AddAttr($cellStr, 'class', 'right');
        $cellStr = AddTag($sizes->directories, 'td');
        $cells[] = AddAttr($cellStr, 'class', 'right');
        if ($entry == '..') {
            $button = '';
            $input = '';
        } else {
            $button = AddTag('Delete', 'button', 'btn');
            $button = AddAttr($button, 'value', $fentry);
            $button = AddAttr($button, 'name', 'delete');
            $input = AddTag('input');
            $input = AddAttr($input, 'type', 'hidden');
            $input = AddAttr($input, 'value', $dir);
            $input = AddAttr($input, 'name', 'cdir');
        }
        $cells[] = AddTag($button.$input, 'td');
        $rows[] = AddTag($cells, 'tr');
    }
    $table = AddTag($rows, 'table');

    $button = AddTag('Refresh', 'button'); 
    $table .= AddTag('br').$button;

    foreach ($homeDirs as $name => $homeDir) {
        $button = AddTag($name, 'button'); 
        $button = AddAttr($button, 'value', $homeDir);
        $button = AddAttr($button, 'name', 'hdir');
        $table .= ' '.$button;
    }

    $form = AddTag($table, 'form');
    $out .= AddAttr($form, 'method', 'post');
    return $out;
}


foreach ($homeDirs as $name => &$homeDir) {
    $homeDir = realpath($homeDir);
}


if (isset($_POST['delete'])) {
    $delete = realpath($_POST['delete']);
    RemoveDir($delete);
}

$dir = reset($homeDirs);
if (isset($_POST['cdir'])) {
    $dir = $_POST['cdir'];
}
if (isset($_POST['dir'])) {
    $dir = $_POST['dir'];
}
if (isset($_POST['hdir'])) {
    $dir = $_POST['hdir'];
}
$out = ListDir($dir);
print $out;


?>
        </pre>
    </body>
</html>
