<?php
class Page
{
	public
		$assigned,
		$input,
		$html;
	
	function __construct($module="accueil", $template="", $menu="", $header="", $footer="")
	{
		global $TPL;
		$template = ($template) ? $template : "index";
		if (is_array($menu)) {
			$t = $menu;
			$this->assigned['head']['file'] = "templates/".$t['header'].".html";
			$this->assigned['menu']['file'] = "templates/".$t['menu'].".html";
			$this->assigned['page']['file'] = "modules/".$module."/".$template.".html";
			$this->assigned['foot']['file'] = "templates/".$t['footer'].".html";
		}
		else {
			$this->assigned['head']['file'] = ($header) ? 
				"templates/".$header.".html" :
				"templates/".$TPL['default']['header'].".html";
			$this->assigned['menu']['file'] = ($menu) ? $menu :
				"templates/".$TPL['default']['menu'].".html";
			$this->assigned['page']['file'] = "modules/".$module."/".$template.".html";
			$this->assigned['foot']['file'] = ($footer) ?
				"templates/".$footer.".html" :
				"templates/".$TPL['default']['footer'].".html";
			}
		$this->assigned['head']['vars'] = array();
		$this->assigned['menu']['vars'] = array();
		$this->assigned['page']['vars'] = array();
		$this->assigned['foot']['vars'] = array();
		$this->html = "";
	}
	
	// Pour assigner une valeur de remplacement à la place d'une balise 
	// Soit par string	: assign("prenom", "Mourad");
	// Soit par array	: assign(array("prenom" => "Mourad");
	function assign($place, $name, $content="")
	{
		if (is_array($name))
		{
			foreach ($name as $tag => $tag_content)
			{
				$this->assigned[$place]['vars'][$tag] = $tag_content;
			}
		}
		else
		{
			$this->assigned[$place]['vars'][$name] = $content;
		}
	}

	function loop_assign($place, $name, $array)
	{
		$this->assigned[$place]['vars']['_loop'][$name] = $array;
	}

	function set_menu($filename, $assign="")
	{
		$this->assigned['menu']['file'] = $filename;
		$this->assigned['menu']['vars'] = ($assign) ? $assign : $this->assigned['menu']['vars'];
	}
	function set_header($filename, $assign="")
	{
		$this->assigned['head']['file'] = $filename;
		$this->assigned['head']['vars'] = ($assign) ? $assign : $this->assigned['head']['vars'];
	}
	function set_template($filename, $assign="")
	{
		$this->assigned['page']['file'] = $filename;
		$this->assigned['page']['vars'] = ($assign) ? $assign : $this->assigned['page']['vars'];
	}
	function set_footer($filename, $assign="")
	{
		$this->assigned['foot']['file'] = $filename;
		$this->assigned['foot']['vars'] = ($assign) ? $assign : $this->assigned['foot']['vars'];
	}
	
	function jsplug($name) {
		if (is_array($name)) {
			foreach($name as $line) {
				$this->jsplug($line);
			}
		}
		else {
			$addon = "<script type='text/javascript' src='$name'></script>";
			$this->assigned['head']['vars']['plugins'] = (isset($this->assigned['head']['vars']['plugins'])) ?
				$this->assigned['head']['vars']['plugins'] . "\n\t" .$addon :
				$addon;
		}
	}

	function jsadd($name) {
		if (is_array($name)) {
			foreach($name as $line) {
				$this->jsadd($line);
			}
		}
		else {
			$addon = "<script type='text/javascript' src='$name'></script>";
			$this->assigned['head']['vars']['addons'] = (isset($this->assigned['head']['vars']['addons'])) ?
				$this->assigned['head']['vars']['addons'] . "\n\t" .$addon : $addon;
		}
	}

	function cssplug($name) {
		if (is_array($name)) {
			foreach($name as $line) {
				$this->cssplug($line);
			}
		}
		else {
			$addon = "<link rel='stylesheet' type='text/css' href='$name' />";
			$this->assigned['head']['vars']['plugins'] = (isset($this->assigned['head']['vars']['plugins'])) ?
				$this->assigned['head']['vars']['plugins'] . "\n\t" .$addon :
				$addon;
		}
	}

	function cssadd($name) {
		if (is_array($name)) {
			foreach($name as $line) {
				$this->cssadd($line);
			}
		}
		else {
			$addon = "<link rel='stylesheet' type='text/css' href='$name' />";
			$this->assigned['head']['vars']['addons'] = (isset($this->assigned['head']['vars']['addons'])) ?
				$this->assigned['head']['vars']['addons'] . "\n\t" .$addon : $addon;
		}
	}
	//function if_assign($cond, $name, $array
	function clear()
	{
		$this->assigned = array();
		$this->html = "";
	}

	function output($debug=0)
	{
		$assigned = $this->assigned;
		foreach($assigned as $place => $value)
		{
			if (!empty($value['file']))
			{
				$output = file_get_contents($value['file']);
				$this->input .= $output;
				ksort($value['vars']); // Pour que '_loop' soit traité en premier
				foreach($value['vars'] as $key => $data)
				{
					if (is_array($data))
					{
						foreach($data as $name => $array)
						{
							$output = loop_output($output, $array, $name);
						}
					}
					else
					{
						$output = simple_output($key, $data, $output);
					}
				}
				$this->html .= $output;
			}
		}
		if ($debug)
		{
			echo "TOTAL TEMPLATE :\n";
			echo $this->input;
			echo "\n\nVAR ASSIGN :\n";
			print_r($this->assigned);
			return 0;
		}
		// Nettoyage des variables non-renseignées
		$this->html = preg_replace("`\{([A-Z_]+)\}`U", "", $this->html);
		$this->html = preg_replace("`<:(?:.+):>`U", "", $this->html);
		return $this->html;
	}
}

// on en aura besoin dans la classe
function loop_output($file_data, $array, $id="[a-z0-9]+")
{
/*
	Transforme une ligne HTML en plusieurs lignes de contenu issu d'un tableau multidimentionnel

Arguments :
	$file : le template
	$array : tableau multidimensionnel contenant les données sous cette forme : $array = array( array("variable" => "valeur") );
	$id : si il n'est pas définit, la fonction s'appliquera sur toutes les boucles qu'elle trouvera sinon seulement celle avec l'id demandé
	
Utilisation :
	$html = trloop("template", $donnees, 2);
	echo $html;
*/

	// Les deux canevas de REGEX qu'on utilisera dans cette fonction un pour le match et un pour le replace
	$pattern_m = "`<:(?:.+) tid=\"$id\"(?:.*)>(?:.+)<(?:.+):>`U"; // aucune parenthèse capturante, on veut toute la ligne !
	$pattern_r = "`<:(.+) tid=\"$id\"(.*)>(.+)<(?:.+):>`"; // capture du tag, des attributs et du innerHTML

	// on chope le code HTML de notre template et on en extrait la ligne à modifier
	preg_match_all($pattern_m, $file_data, $out, PREG_SET_ORDER);
	
	// au cas où il y ait plusieurs "id" identiques ou alors si l'id n'a pas été spécifié, on va répéter 
	// l'opération autant de fois qu'il n'y a de lignes à faire répéter
	
	foreach ($out as $result)
	{
		// la variable $replace va emmagaziner le code HTML de toutes les lignes à afficher
		// idée : utiliser le paramètre /e de preg_replace
		// idée+: utiliser les preg_replace array / array
		
		$replace = "";
		foreach($array as $element)
		{
			// on redonne au tag HTML son vrai nom (<:tr (...)> <tr:> deviendra <tr (...)> </tr> etcetera)
			// sachant que $result[0] c'est notre ligne à looper
			
			$line = preg_replace($pattern_r, "<$1$2>$3</$1>", $result[0]);
			foreach($element as $key => $word)
			{
				$thevar = strtoupper($key);
				$line = str_ireplace("{".$thevar."}", $word, $line);
			}
			$replace .= $line."\n";
		}
		// dans le code HTML de notre template, on remplace la ligne à looper par les lignes à afficher
		$file_data = str_replace($result[0], $replace."\n", $file_data);
	}
	return $file_data;
}

function simple_output($var, $remplacement, $src)
{
	$ret = str_replace("{".strtoupper($var)."}", $remplacement, $src);
	return $ret;
}

function autorefresh($sec, $page)
{
	return('<meta http-equiv="refresh" content="'.$sec.';URL='.$page.'">');
}
?>
