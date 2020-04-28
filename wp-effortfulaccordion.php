<?php
/*
Plugin Name: Effortful Accordion
Description: Convert content with headings to accordions. Flexible and once set up and configured, simple to use for end-users.
Version: 0.3.0
Author: Jakob Wierzba
Author URI: http://3c33.de/
Text Domain: wp-effortfulaccordion
*/

namespace WPEffortfulAccordion;

$theme_dir = get_template_directory();
if (file_exists("$theme_dir/wp-effortfulaccordion-config.php")) {
	include "$theme_dir/wp-effortfulaccordion-config.php";
}
// sage 8
elseif (file_exists("$theme_dir/lib/wp-effortfulaccordion-config.php")) {
	include "$theme_dir/lib/wp-effortfulaccordion-config.php";
}
// compatibility with sage 9, which points to theme/resources...
elseif (file_exists("$theme_dir/../app/wp-effortfulaccordion-config.php")) {
	include "$theme_dir/../app/wp-effortfulaccordion-config.php";
}

/* defaults */
if (!defined('WPEffortfulAccordion\bootstrap_version'))
    define('WPEffortfulAccordion\bootstrap_version', 3);
if (!defined('WPEffortfulAccordion\shortcode_default_mode'))
    define('WPEffortfulAccordion\shortcode_default_mode', 'accordion');
if (!defined('WPEffortfulAccordion\shortcode_default_h'))
    define('WPEffortfulAccordion\shortcode_default_h', 2);
if (!defined('WPEffortfulAccordion\shortcode_default_open'))
    define('WPEffortfulAccordion\shortcode_default_open', 'first');

if (!defined('WPEffortfulAccordion\wrap_tag'))
    define('WPEffortfulAccordion\wrap_tag', 'div');
if (!defined('WPEffortfulAccordion\wrap_baseclass'))
    define('WPEffortfulAccordion\wrap_baseclass', 'hwrap');
if (!defined('WPEffortfulAccordion\accordion_bullet'))
    define('WPEffortfulAccordion\accordion_bullet', '');

function content_preg_split ($hlevel, $content) {
	return preg_split(
		"@<h$hlevel.*?>(.*?)</h$hlevel>@is",
		$content, -1, PREG_SPLIT_DELIM_CAPTURE
	);
}

function sanitize_str ($str, $fallback) {
	return preg_replace('/\W/', '_',
		sanitize_title($str, $fallback)
	);
}

function content_split ($mode = 'accordion', $h = 2, $open = 'first', $wrap_preamble = false, $content = null) {
	$parent_id_suffix = $mode === 'accordion' ? '_'.md5($content) : '';
	$split = content_preg_split($h, $content);
	if (count($split) < 2) {
		return '<!--no split found-->'.$content;
	}
	$lowerhs = implode('', range(1, $h - 1));
	$pre = $split[0];
	$ret = ''; //TODO check if this works for all modes
	$didcolumn = false;
	switch ($open) {
	case 'last':
		$open_s = count($split) - 2;
		break;
	case 'none':
		$open_s = -1;
		break;
	case 'first':
	default:
		$open_s = 1;
		break;
	}
	for ($s = 1; $s < count($split); $s++) {
		$_ = $split[$s];
		if ($s % 2 === 1) {
			$idx = (int) floor($s / 2) + 1;
			$id = sanitize_str($_, 'h'.(($s - 2) / 2));
			$is_open = $s === $open_s;
			switch ($mode) {
			case 'tabs':
				$ret .= sprintf('<h%d>%s</h%d>', $h, $_, $h);
			case 'wrap':
				$ret .= sprintf('<h%d>%s</h%d>', $h, $_, $h);
				break;
			case 'outerwrap':
            case 'doublewrap':
				$ret .= sprintf('<%s class="%s">', wrap_tag, wrap_baseclass);
				$ret .= sprintf('<h%d class="%s-title">%s</h%d>', $h, wrap_baseclass, $_, $h);
				break;
			case 'accordion':
				switch (bootstrap_version) {
				case 4:
					$ret .= '<div class="card">';
					$ret .= sprintf('<div class="card-header" id="%s"><h%d><button class="btn btn-link %s" data-toggle="collapse" data-target="#c_%s" aria-expanded="%s" aria-controls="c_%s">%s%s</button></h%d></div>',
						$id, $h, ($is_open ? '' : 'collapsed'), $id, ($s === $is_open ? 'true' : 'false'), $id, accordion_bullet, $_, $h);
					break;
				case 3:
				default:
					$ret .= '<div class="panel">';
					$ret .= sprintf('<div class="panel-heading" role="tab" id="%s"><h%d class="panel-title"><a role="button" data-toggle="collapse" data-parent="#accordion%s" href="#c_%s" aria-expanded="%s" aria-controls="c_%s" class="%s">%s%s</a></h%d></div>',
						$id, $h, $parent_id_suffix, $id, ($is_open ? 'true' : 'false'), $id, ($is_open ? '' : 'collapsed'), accordion_bullet, $_, $h
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
			$is_open = $s === $open_s + 1;
			switch ($mode) {
			case 'tabs':
				$ret .= sprintf('<div class="tab" id="%s">%s</div>%s', $id, $lowerh_pre, $lowerh_post);
				break;
			case 'wrap':
				$ret .= sprintf('<%s class="%s-body">%s</%s>%s', wrap_tag, wrap_baseclass, $lowerh_pre, wrap_tag, $lowerh_post);
				break;
			case 'outerwrap':
				$ret .= sprintf('%s</%s>%s', $lowerh_pre, wrap_tag, $lowerh_post);
				break;
            case 'doublewrap':
				$ret .= sprintf('<div class="%s-body">%s</div></%s>%s', wrap_baseclass, $lowerh_pre, wrap_tag, $lowerh_post);
                break;
			case 'accordion':
				switch (bootstrap_version) {
				case 4:
					$ret .= sprintf('<div id="c_%s" class="collapse %s" aria-labelledby="%s" data-parent="#accordion%s"><div class="card-body">%s</div></div>',
						$id, ($is_open ? 'show' : ''), $id, $parent_id_suffix, $lowerh_pre
					);
					$ret .= '</div><!-- end class="card" -->';
					$ret .= $lowerh_post;
					break;
				case 3:
				default:
					$ret .= sprintf('<div id="c_%s" class="panel-collapse collapse %s" role="tabpanel" aria-labelledby="%s"><div class="panel-body">%s</div></div>',
						$id, ($is_open ? 'in' : ''), $id, $lowerh_pre
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
		return sprintf('<div class="%s" id="accordion%s" role="tablist" aria-multiselectable="true">%s</div>',
			bootstrap_version === 3 ? 'panel-group' : 'accordion',
			$parent_id_suffix,
			$ret
		);
	case 'columnize':
		return $pre.'<div class="cols cols2"><div>'.$ret.'</div></div>';
	case 'multicol':
		return $pre.'<div class="cols cols3">'.$ret.'</div>';
    case 'wrap':
    case 'outerwrap':
        if ($wrap_preamble) {
            return sprintf('<%s class="%s %s-preamble">%s</%s>%s', wrap_tag, wrap_baseclass, wrap_baseclass, $pre, wrap_tag, $ret);
        }
        else {
            return $pre.$ret;
        }
    case 'doublewrap':
        if ($wrap_preamble) {
            return sprintf('<%s class="%s %s-preamble"><div class="%s-body">%s</div></%s>%s', wrap_tag, wrap_baseclass, wrap_baseclass, wrap_baseclass, $pre, wrap_tag, $ret);
        }
        else {
            return $pre.$ret;
        }
	default:
		return $pre.$ret;
	}
}

/* strip excess <p>s from shortcodes that are wpautop'ed, like:
 * <p>[shortcode]</p> */
add_filter('the_content', function ($content) {
        $content = preg_replace('@(?:</?p>)?(\[/?accordion?[\s\d\w="]*\])(?:</?p>)?@', '$1', $content);
	return $content;
});

add_shortcode('accordion', function ($atts, $content = null) {
	$a = shortcode_atts(array(
		'mode' => shortcode_default_mode,
		'h' => shortcode_default_h,
		'open' => shortcode_default_open
	), $atts);
	return content_split($a['mode'], $a['h'], $a['open'], $content);
});
