<?php
/*
 *  wordpress-tiny-lxp-platform - Enable WordPress to act as an Tiny LXP Platform.

 *  Copyright (C) 2022  Waqar Muneer
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: Waqar Muneer <waqarmuneer@gmail.com>
 */

/*
  Plugin Name: Tiny Lxp
  Plugin URI: https://github.com/i-do-dev/wordpress-lti-platform
  Description: This plugin allows WordPress to act as a Platform using the IMS Learning Tools Interoperability (Tiny LXP) specification.
  Version: 2.0.3
  Author: Waqar Muneer
  Author URI: https://github.com/i-do-dev/wordpress-lti-platform
  License: GPL3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin name.
 */
define('Tiny_LXP_PLATFORM_NAME', 'lti-platform');

/**
 * Current plugin version.
 */
define('Tiny_LXP_PLATFORM_VERSION', '2.0.3');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-tiny-lxp-platform.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_tiny_lxp_platform()
{
    $plugin = new Tiny_LXP_Platform();
    if ($plugin->isOK()) {
        $plugin->run();
    }
}

// Function to run on plugin activation
function my_plugin_activation_function() {
    $theme = wp_get_theme('tiny-lxp');

    if ($theme->exists()) {
        switch_theme('tiny-lxp');
    }
}

// Function to run on plugin deactivation
function my_plugin_deactivation_function() {
    $theme = wp_get_theme('twentytwentytwo');

    if ($theme->exists()) {
        switch_theme('twentytwentytwo');
    }
}

// Register activation hook
register_activation_hook(__FILE__, 'my_plugin_activation_function');

// Register deactivation hook
register_deactivation_hook(__FILE__, 'my_plugin_deactivation_function');

run_tiny_lxp_platform();