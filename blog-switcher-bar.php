<?php /*

**************************************************************************

Plugin Name:  Blog Switcher Bar
Plugin URI:   http://github.com/rmccue/blog-switcher-bar
Description:  Based on Viper007Bond's WordPress Admin Bar.
Version:      0.1
Author:       Ryan McCue
Author URI:   http://ryanmccue.info/

**************************************************************************

Copyright (C) 2009 Ryan McCue
Copyright (C) 2008 Viper007Bond

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************/

class BlogSwitcherBar {
	var $version = '0.1';
	var $menu = array();
	var $folder;

	function BlogSwitcherBar() {
		$this->__construct();
	}
	// Plugin initialization
	function __construct() {
		// This version only supports WP 2.7+
		if ( !function_exists('wp_list_comments') ) return;

		$this->folder = WPMU_PLUGIN_URL . '/blog-switcher-bar';

		add_action( 'wp_head', array(&$this, 'OutputCSS') );
		add_action( 'wp_footer', array(&$this, 'OutputMenuBar') );

		// Load the little JS file for this plugin
		wp_enqueue_script( 'blog-switcher-bar', $this->folder . '/blog-switcher-bar.js', array(), $this->version );
	}

	// Setup the menu arrays
	function SetupMenu() {
		if ( !empty($this->menu) ) return;

		/*$this->menu = array(
			'url' => array(
				0 => array(
					'id' => 0, // >39 = right-hand side
					'title' => 'Title',
					'url' => 'http://.../',
				),
				//1 => array(...) - submenu
			),
			'eh' => array(
				0 => array(
					'id' => 40, // >39 = right-hand side
					'title' => 'Title',
					'url' => 'http://.../',
				),
				//1 => array(...) - submenu
			),
		);*/

		global $wpdb;

		$this->menu['switch'] = array(0 => array(
			'id' => 0,
			'title' => 'Switch Theme Preview',
			'url' => '#'
		));
		foreach(get_blog_list() as $blog) {
			$this->menu['switch'][] = array(
				'id' => $blog['blog_id'],
				'title' => get_blog_option( $blog['blog_id'], "blogname"),
				'url' => 'http://' . $blog['domain'] . $blog['path']
			);
		}
	}

	// Output the needed CSS for the plugin
	function OutputCSS() { ?>
	<link rel="stylesheet" href="<?php echo $this->folder . '/style.css?ver=' . $this->version; ?>" type="text/css" />
	<!--[if lt IE 7]><style type="text/css">#bsbar { position: absolute; } #bsbar .bsbar-menupop li a { width: 100%; }</style><![endif]-->
<?php
		if ( is_admin() ) {
			echo '	<style type="text/css">#bsbarlist ul { margin: 5px 0 0 25px; }</style>' . "\n";
		}
	}


	// Generate and output the HTML for the admin menu
	function OutputMenuBar() {
		$this->SetupMenu();
?>

<!-- Start WordPress Admin Bar -->
<div id="bsbar">
	<div id="bsbar-leftside">
		<ul>
<?php
			global $current_blog;
?>
			<li><a href="http://<?php echo $current_blog->domain . $current_blog->path ?>">Current Theme: <?php echo get_blog_option( $current_blog->blog_id, "blogname") ?></a></li>
<?php

			$switched = FALSE;

			foreach( $this->menu as $topstub => $menu ) {
				if ( FALSE === $switched && 'switch' === $topstub ) {
					echo "		</ul>\n	</div>\n	<div id=\"bsbar-rightside\">\n		<ul>\n";
					$switched = TRUE;
				}

				if ( isset($this->settings['hide'][$topstub][0]) ) continue;

				if ( 1 == count($menu) ) {
					echo '			<li class="bsbar-menu_';
					echo str_replace( '.', '-', $topstub );

					echo '"><a href="' . $menu[0]['url'] . '">' . $menu[0]['title'] . "</a></li>\n";
				} else {
					echo '			<li class="bsbar-menu_';
					echo str_replace( '.', '-', $topstub );

					if ( TRUE == $first ) {
						echo ' bsbar-menu-first';
						$first = FALSe;
					}

					echo ' bsbar-menupop" onmouseover="showNav(this)" onmouseout="hideNav(this)">' . "\n" . '				<a href="' . $menu[0]['url'] . '"><span class="bsbar-dropdown">' . $menu[0]['title'] . "</span></a>\n				<ul>\n";

					foreach( $menu as $submenustub => $submenu ) {
						if ( 0 === $submenustub || ( !empty($this->settings['hide'][$topstub]) && TRUE === $this->settings['hide'][$topstub][$submenustub] && ( 'wordpress-admin-bar' !== $submenustub || !is_admin() ) ) )
							continue;

						echo '					<li><a href="' . $submenu['url'] . '">' . $submenu['title'] . "</a></li>\n";
					}

					echo "				</ul>\n			</li>\n";
				}
			}

?>
			<li><a href="/">Return to WPSynergy.org</a></li>
		</ul>
	</div>
</div>

<?php
	}
}


// Start this plugin once all other files and plugins are fully loaded
add_action( 'plugins_loaded', create_function( '', 'global $BlogSwitcherBar; $BlogSwitcherBar = new BlogSwitcherBar();' ), 15 );
?>