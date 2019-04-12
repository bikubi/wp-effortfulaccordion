<?php
define('WPEffortfulAccordion\bootstrap_version', 4);

add_filter('the_content', function ($content) {
	if (is_admin()) return $content;
	if (is_page()) {
		return \WPEffortfulAccordion\content_split('accordion', 3, $content);
	}
	elseif (is_page_template('template-example.php')) {
		return \WPEffortfulAccordion\content_split('accordion', 2, $content);
	}
	return $content;
});
