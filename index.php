<?php
session_start();
date_default_timezone_set('Europe/London');
require_once "config.php";
require_once "./incl/class.page.php";
$_SESSION['auth'] = (isset($_SESSION['auth'])) ? $_SESSION['auth'] : false;
$_SESSION['lang'] = (isset($_SESSION['lang'])) ? $_SESSION['lang'] : DEFAULT_LANG;
if (!isset($_GET['mod']))
{
	if ($_SESSION['auth'])
	{
		include ("./modules/".SITE_INDEX_LOGGED."/index.php");
	} else
	{
		include ("./modules/".SITE_INDEX_GUEST."/index.php");
	}
}
else
{
	// $_GET['mod'] correspond au nom du sous dossier du module qu'on veut
	// La page index du sous-dossier contiendra les includes dynamiques correspondant aux différentes pages du module
	$file = "./modules/".$_GET['mod']."/index.php";
	if (!file_exists($file))
	{
		// La page d'erreur par défaut définie dans config.php
		include ("./modules/404/index.php");
	}
	else
	{
		include ($file);
	}
}

if (isset($page))
{
	if (!isset($_GET['mod'])) {
		$url = "index.php";
	} else {
		$url = $_GET['mod'];
		$url .= (isset($_GET['var'])) ? "/".$_GET['var'] : "";
		$url .= (isset($_GET['svar'])) ? "/".$_GET['svar'] : "";
		$url .= (isset($_GET['tvar'])) ? "/".$_GET['tvar'] : "";
		$url .= ".html";
	}
	$title = SITE_NAME;
	$title .= (isset($module['name'])) ? ' - '.$module['name'] : '';
	$title .= (isset($module['page'])) ? ' : '.$module['page'] : '';
	$lang = $_SESSION['lang'];
	$page->assign("head", array(
		"dir" => $dir[$lang],
		"lang" => $lang,
		"title" => $title));
	if (isset($head_addon))
	{
		$page->assign("head", "head_addon", $head_addon);
	}
	echo $page->output();
}	

?>