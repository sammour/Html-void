<?php
// Paramètres de connexionn à la base de données
define ("DB_HOST", "localhost");
define ("DB_NAME", "site");
define ("DB_USER", "root");
define ("DB_PASS", "root");

// Paramètres par défaut (Nom du site, Page d'accueil, Template choisit, langue, page d'erreur, URL)
define ("SITE_NAME", "Site");
define ("SITE_INDEX_USER", "accueil");
define ("SITE_INDEX_GUEST", "accueil");
define ("DEFAULT_LANG", "fr");

$TPL = [
	"default"	=> [
		"header"	=> "header",
		"footer"	=> "footer",
		"menu"		=> "menu"
	]
];
?>
