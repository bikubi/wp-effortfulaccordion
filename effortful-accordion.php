<?php
/*
Plugin Name: Effortful Accordion
Description: Convert content with headings to accordions. Flexible and once set up and configured, simple to use for end-users.
Version: 0.0.1
Author: Jakob Wierzba
Author URI: http://jakobwierzba.de/
Text Domain: effortful-accordion
*/

namespace EffortfulAccordion;

$theme_dir = get_template_directory();
if (file_exists("$theme_dir/effortful-accordion-config.php")) {
	include "$theme_dir/effortful-accordion-config.php";
}
// sage 8
elseif (file_exists("$theme_dir/lib/effortful-accordion-config.php")) {
	include "$theme_dir/lib/effortful-accordion-config.php";
}
// compatibility with sage 9, which points to theme/resources... 
elseif (file_exists("$theme_dir/../app/effortful-accordion-config.php")) {
	include "$theme_dir/../app/effortful-accordion-config.php";
}

/* defaults */
if (!defined('bootstrap_version')) define('bootstrap_version', 3);
if (!defined('shortcode_default_mode')) define('shortcode_default_mode', 'accordion');
if (!defined('shortcode_default_h')) define('shortcode_default_h', 2);

function content_preg_split ($hlevel, $content) {
	return preg_split(
		"@<h$hlevel.*?>(.*?)</h$hlevel>@is",
		$content, -1, PREG_SPLIT_DELIM_CAPTURE
	);
}

function sanitize_str ($str, $fallback) {
	return strtr(
		sanitize_title($str, $fallback),
		'-', '_'
	);
}

function content_split ($mode = 'accordion', $h = 2, $content = null) {
	global $post;
	if ($content === null) {
		$content = $post->post_content;
	}
	$parent_id_suffix = $mode === 'accordion' ? '_'.md5($content) : '';
	$split = content_preg_split($h, $content);
	if (count($split) < 2) {
		return '<!--no split found-->'.$content;
	}
	$lowerhs = implode('', range(1, $h - 1));
	$pre = $split[0];
	$ret = ''; //TODO check if this works for all modes
	$didcolumn = false;
	for ($s = 1; $s < count($split); $s++) {
		$_ = $split[$s];
		if ($s % 2 === 1) {
			$idx = (int) floor($s / 2) + 1;
			$id = sanitize_str($_, 'h'.(($s - 2) / 2));
			switch ($mode) {
			case 'tabs':
				$ret .= sprintf('<h%d>%s</h%d>', $h, $_, $h);
			case 'wrap':
				//$ret .= sprintf('<div class="hwrap h%d">', $h);
				$ret .= sprintf('<h%d>%s</h%d>', $h, $_, $h);
				break;
			case 'outerwrap':
				$ret .= '<div class="hwrap-outer">';
				$ret .= sprintf('<h%d>%s</h%d>', $h, $_, $h);
				break;
			case 'accordion':
				switch (bootstrap_version) {
				case 4:
					$ret .= '<div class="card">';
					$ret .= sprintf('<div class="card-header" id="%s"><h%d><button class="btn btn-link %s" data-toggle="collapse" data-target="#c_%s" aria-expanded="%s" aria-controls="c_%s">%s</button></h%d></div>',
						$id, $h, ($s === 1 ? '' : 'collapsed'), $id, ($s === 1 ? 'true' : 'false'), $id, $_, $h);
					break;
				case 3:
				default:
					$ret .= '<div class="panel">';
					$ret .= sprintf('<div class="panel-heading" role="tab" id="%s"><h%d><a role="button" data-toggle="collapse" data-parent="#accordion%s" href="#c_%s" aria-expanded="%s" aria-controls="c_%s" class="%s">%s</a></h%d></div>',
						$id, $h, $parent_id_suffix, $id, ($s === 1 ? 'true' : 'false'), $id, ($s === 1 ? '' : 'collapsed'), $_, $h
					);
				}
				break;
			case 'multicol':
				$ret .= '<div class="col">';
				$ret .= "<h$h>$_</h$h>";
				break;
			case 'columnize':
				$ret .= sprintf('<h%d>%s</h%d>', $h, $_, $h);
				break;
			}
		}
		else {
			$id = sanitize_str($split[$s - 1], 'h'.(($s - 2) / 2));
			$lowerh_split = preg_split("@(<h[$lowerhs])@i", $_, 2, PREG_SPLIT_DELIM_CAPTURE);
			$lowerh_pre = $lowerh_split[0];
			$lowerh_post = (count($lowerh_split) > 1)
				? $lowerh_split[1].$lowerh_split[2]
				: '';
			switch ($mode) {
			case 'tabs':
				$ret .= sprintf('<div class="tab" id="%s">%s</div>%s', $id, $lowerh_pre, $lowerh_post);
				break;
			case 'wrap':
				$ret .= sprintf('<div class="hwrap-body">%s</div>%s', $lowerh_pre, $lowerh_post);
				//$ret .= '</div><!--end class="hwrap"-->';
				break;
			case 'outerwrap':
				$ret .= $lowerh_pre.'</div>'.$lowerh_post;
				break;
			case 'accordion':
				switch (bootstrap_version) {
				case 4:
					$ret .= sprintf('<div id="c_%s" class="collapse %s" aria-labelledby="%s" data-parent="#accordion%s"><div class="card-body">%s</div></div>',
						$id, ($s === 2 ? 'show' : ''), $id, $parent_id_suffix, $lowerh_pre
					);
					$ret .= '</div><!-- end class="card" -->';
					$ret .= $lowerh_post;
					break;
				case 3:
				default:
					$ret .= sprintf('<div id="c_%s" class="panel-collapse collapse %s" role="tabpanel" aria-labelledby="%s"><div class="panel-body">%s</div></div>',
						$id, ($s === 2 ? 'in' : ''), $id, $lowerh_pre 
					);
					$ret .= '</div><!--end class="panel-->';
					$ret .= $lowerh_post;
				}
				break;
			case 'multicol':
				$ret .= $lowerh_pre.'</div>'.$lowerh_post;
				break;
			case 'columnize':
				$ret .= $lowerh_pre;
				if (!$didcolumn && ($s >= floor(count($split) / 2))) {
					$ret .= '</div><div>';
					$didcolumn = true;
				}
				$ret .= $lowerh_post;
				break;
			}
		}
	}
	switch ($mode) {
	case 'tabs':
		return $pre.'<div class="content tabbed">'.$ret.'</div>';
	case 'accordion':
		return $pre.'<div class="panel-group" id="accordion'.$parent_id_suffix.'" role="tablist" aria-multiselectable="true">'.$ret.'</div>';
	case 'columnize':
		return $pre.'<div class="cols cols2"><div>'.$ret.'</div></div>';
	case 'multicol':
		return $pre.'<div class="cols cols3">'.$ret.'</div>';
	default:
		return $pre.$ret;
	}
}

add_shortcode('accordion', function ($atts, $content = null) {
	$a = shortcode_atts(array(
		'mode' => shortcode_default_mode,
		'h' => shortcode_default_h
	), $atts);
	return content_split($a['mode'], $a['h']);
});
