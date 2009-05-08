<?php /*

**************************************************************************

Plugin Name:  WordPress Admin Bar
Plugin URI:   http://github.com/rmccue/blog-switcher-bar
Description:  Based on Viper007Bond's WordPress Admin Bar.
Version:      3.1.5
Author:       Viper007Bond
Author URI:   http://www.viper007bond.com/

**************************************************************************

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
	var $version = '3.1.5';
	var $menu = array();
	var $submenu = array();
	var $folder;

	function BlogSwitcherBar() {
		$this->__construct();
	}
	// Plugin initialization
	function __construct() {
		// This version only supports WP 2.7+
		if ( !function_exists('wp_list_comments') ) return;

		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's folder and name it "wordpress-admin-bar-[value in wp-config].mo"
		load_plugin_textdomain( 'wordpress-admin-bar', FALSE, '/wordpress-admin-bar' );

		//add_filter( 'plugin_action_links', array(&$this, 'AddPluginActionLink'), 10, 2 );
		add_action( 'load-settings_page_wordpress-admin-bar', array(&$this, 'SettingsPageInit') );
		add_action( 'admin_head-settings_page_wordpress-admin-bar', array(&$this, 'AdminCSS') );
		add_action( 'admin_post_wordpress-admin-bar', array(&$this, 'HandleFormPOST') );

		// Modify the menu array a little to make it better
		add_filter( 'wpabar_menuitems', array(&$this, 'AddSingleEditLink') );
		add_filter( 'wpabar_menuitems', array(&$this, 'CommentsAwaitingModCount') );
		add_filter( 'wpabar_menuitems', array(&$this, 'PluginsUpdateCount') );

		$this->folder = plugins_url('blog-switcher-bar');

		add_action( 'wp_head', array(&$this, 'OutputCSS') );
		add_action( 'wp_footer', array(&$this, 'OutputMenuBar') );

		// Load the little JS file for this plugin
		wp_enqueue_script( 'blog-switcher-bar', $this->folder . '/blog-switcher-bar.js', array(), $this->version );
	}

	// Setup the menu arrays
	function SetupMenu() {
		if ( !empty($this->menu) ) return;

		global $wpdb, $menu, $submenu, $_wp_submenu_nopriv;

		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
		require_once( ABSPATH . 'wp-admin/menu.php' );

		// The top-level menus
		foreach( $menu as $id => $menuitem ) {
			if ( empty($menuitem[2]) ) continue;
			$custom = ( !empty($menuitem[3]) ) ? TRUE : FALSE;
			$this->menu[$menuitem[2]] = array( 0 => array( 'id' => $id, 'title' => $menuitem[0], 'custom' => $custom ) );
		}

		// All children menus
		foreach( $submenu as $parent => $submenuitem ) {
			foreach( $submenuitem as $id => $item ) {
				$custom = ( isset($item[3]) ) ? TRUE : FALSE;
				if ( $parent == $item[2] )
					$custom = FALSE;
				$this->menu[$parent][$item[2]] = array( 'id' => $id, 'title' => $item[0], 'custom' => $custom );
			}
		}

		$this->menu = apply_filters( 'wpabar_menuitems', $this->menu );
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

			$first = TRUE;
			$switched = FALSE;

			foreach( $this->menu as $topstub => $menu ) {
				if ( FALSE === $switched && 39 < $menu[0]['id'] ) {
					echo "		</ul>\n	</div>\n	<div id=\"bsbar-rightside\">\n		<ul>\n";
					$switched = TRUE;
				}

				if ( isset($this->settings['hide'][$topstub][0]) && ( 'options-general.php' !== $topstub || !is_admin() ) ) continue;

				if ( 1 == count($menu) ) {
					echo '			<li class="bsbar-menu_';
					if ( TRUE === $menu[0]['custom'] ) echo 'admin-php_';
					echo str_replace( '.', '-', $topstub );

					if ( TRUE == $first ) {
						echo ' bsbar-menu-first';
						$first = FALSe;
					}

					echo '"><a href="' . admin_url( $topstub ) . '">' . $menu[0]['title'] . "</a></li>\n";
				} else {
					echo '			<li class="bsbar-menu_';
					if ( TRUE === $menu[0]['custom'] ) echo 'admin-php_';
					echo str_replace( '.', '-', $topstub );

					if ( TRUE == $first ) {
						echo ' bsbar-menu-first';
						$first = FALSe;
					}

					$url = ( TRUE === $menu[0]['custom'] ) ? 'admin.php?page=' . $topstub : $topstub;

					echo ' bsbar-menupop" onmouseover="showNav(this)" onmouseout="hideNav(this)">' . "\n" . '				<a href="' . admin_url( $url ) . '"><span class="bsbar-dropdown">' . $menu[0]['title'] . "</span></a>\n				<ul>\n";

					foreach( $menu as $submenustub => $submenu ) {
						if ( 0 === $submenustub || ( !empty($this->settings['hide'][$topstub]) && TRUE === $this->settings['hide'][$topstub][$submenustub] && ( 'wordpress-admin-bar' !== $submenustub || !is_admin() ) ) )
							continue;

						$parent = ( TRUE === $menu[0]['custom'] ) ? 'admin.php' : $topstub;
						$url = ( TRUE === $submenu['custom'] ) ? $parent . '?page=' . $submenustub : $submenustub;

						echo '					<li><a href="' . admin_url( $url ) . '">' . $submenu['title'] . "</a></li>\n";
					}

					echo "				</ul>\n			</li>\n";
				}
			}

?>
			<li><a href="/">Return to site</a></li>
		</ul>
	</div>
</div>

<?php
	}
}


// Start this plugin once all other files and plugins are fully loaded
add_action( 'plugins_loaded', create_function( '', 'global $BlogSwitcherBar; $BlogSwitcherBar = new BlogSwitcherBar();' ), 15 );

?>