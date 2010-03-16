<?
/*
 * This file tries to include the Wordpress header by searching UP the directory structure, and dies on failure
 */
$searchFile = 'wp-blog-header.php';
for($i = 0; $i < 10; $i++)
{
    if( file_exists($searchFile) )
    {
        require_once($searchFile);
        break;    
    }
    $searchFile = "../" . $searchFile;
}

if( !defined('WPINC') )
{
    if( function_exists('j_die') ) j_die("Failed to locate wp-blog-header.php.");
    else                           die(  "Failed to locate wp-blog-header.php.");
}

?>