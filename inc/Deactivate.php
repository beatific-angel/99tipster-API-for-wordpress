<?php
/**
 * @package Super Side
 */

namespace Inc;

class Deactivate 
{
	public static function deactivate() {
		flush_rewrite_rules();
	}
}