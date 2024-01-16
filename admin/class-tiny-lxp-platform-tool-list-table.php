<?php
/*
 *  wordpress-tiny-lxp - Enable WordPress to act as an Tiny LXP Platform.

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

/**
 * The table of current Tiny LXP tools.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-tiny-lxp
 * @since      1.0.0
 * @package    Tiny_LXP_Platform
 * @subpackage Tiny_LXP_Platform/admin
 * @author     Waqar Muneer <waqarmuneer@gmail.com>
 */
class Tiny_LXP_Platform_Tool_List_Table extends WP_List_Table
{

    private $mu_items = array();
    private $is_trash;

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'tool',
            'plural' => 'tools',
            'ajax' => false
        ));
        add_filter('list_table_primary_column', array($this, 'set_primary_column'), 10, 2);
        if (!defined('WP_NETWORK_ADMIN') || !WP_NETWORK_ADMIN) {
            $args = array(
                'post_type' => Tiny_LXP_Platform_Tool::POST_TYPE_NETWORK,
                'post_status' => 'publish'
            );
            $this->mu_items = array_keys(Tiny_LXP_Platform_Tool::all($args));
        }
    }

    public static function set_primary_column()
    {
        return 'name';
    }

    public static function define_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox">',
            'name' => __('Name', Tiny_LXP_Platform::get_plugin_name()),
            'code' => __('Code', Tiny_LXP_Platform::get_plugin_name()),
            'enabled' => __('Enabled?', Tiny_LXP_Platform::get_plugin_name()),
            'debugMode' => __('Debug mode?', Tiny_LXP_Platform::get_plugin_name()),
            'lastAccess' => __('Last launch', Tiny_LXP_Platform::get_plugin_name()),
            'created' => __('Created', Tiny_LXP_Platform::get_plugin_name()),
            'modified' => __('Modified', Tiny_LXP_Platform::get_plugin_name()),
        );

        return $columns;
    }

    public static function tools_orderby($args, $wp_query)
    {
        global $wpdb;

        if (isset($wp_query->query['post_type']) && ($wp_query->query['post_type'] === Tiny_LXP_Platform::$postType)) {
            if ($wp_query->query['orderby'] === 'enabled') {
                $args = "{$wpdb->posts}.post_status {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            } elseif ($wp_query->query['orderby'] === 'debugMode') {
                $args = "LOCATE('\"_debug\":\"true\"', {$wpdb->posts}.post_content) > 0 {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            } elseif ($wp_query->query['orderby'] === 'url') {
                $args = "SUBSTR({$wpdb->posts}.post_content, LOCATE('\"__messageUrl\":\"', {$wpdb->posts}.post_content)+16, 100) {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            } elseif ($wp_query->query['orderby'] === 'lastAccess') {
                $args = "LOCATE('\"__lastAccess\":\"', {$wpdb->posts}.post_content), 27) {$wp_query->query['order']}, {$wpdb->posts}.post_name ASC";
            }
        }

        return $args;
    }

    public function trash_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) moved to the Bin.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function trash_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when moving tool(s) to the Bin.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function delete_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) deleted.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function delete_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when deleting tool(s).', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function restore_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) restored.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function restore_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when restoring tool(s).', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function enable_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) enabled.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function enable_notice_denied()
    {
        echo('    <div class="notice notice-warning is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tools cannot be enabled if they are not fully configured for either Tiny LXP 1.0/1.1/1.2 or Tiny LXP 1.3, or no private key has been defined.',
            Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function enable_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when enabling tool(s).', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function disable_notice_success()
    {
        echo('    <div class="notice notice-success is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('Tool(s) disabled.', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function disable_notice_error()
    {
        echo('    <div class="notice notice-error is-dismissible">' . "\n");
        echo('        <p>' . esc_html__('An error occurred when disablng tool(s).', Tiny_LXP_Platform::get_plugin_name()) . '</p>' . "\n");
        echo('    </div>' . "\n");
    }

    public function process_action()
    {
        if (!empty($_REQUEST['tool'])) {
            $ids = sanitize_text_field($_REQUEST['tool']);
            if (!is_array($ids)) {
                $ids = array($ids);
            }
            $ok = true;
            if ($this->current_action() === 'trash') {
                foreach ($ids as $id) {
                    $tool = Tiny_LXP_Platform_Tool::fromRecordId(intval($id), Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
                    $ok = $ok && $tool->trash();
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'trash_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'trash_notice_error'));
                }
            } elseif ($this->current_action() === 'untrash') {
                foreach ($ids as $id) {
                    $tool = Tiny_LXP_Platform_Tool::fromRecordId(intval($id), Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
                    $ok = $ok && $tool->restore();
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'restore_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'restore_notice_error'));
                }
            } elseif ($this->current_action() === 'delete') {
                foreach ($ids as $id) {
                    $tool = new Tiny_LXP_Platform_Tool(Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
                    $tool->setRecordId(intval($id));
                    $ok = $ok && $tool->delete();
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'delete_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'delete_notice_error'));
                }
            } else if ($this->current_action() === 'enable') {
                $denied = false;
                foreach ($ids as $id) {
                    $tool = Tiny_LXP_Platform_Tool::fromRecordId(intval($id), Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
                    if ($tool->canBeEnabled()) {
                        $tool->enabled = true;
                        $ok = $ok && $tool->save(true);
                    } elseif (!$denied) {
                        $ok = false;
                        $denied = true;
                        add_action('all_admin_notices', array($this, 'enable_notice_denied'));
                    }
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'enable_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'enable_notice_error'));
                }
            } else if ($this->current_action() === 'disable') {
                foreach ($ids as $id) {
                    $tool = Tiny_LXP_Platform_Tool::fromRecordId(intval($id), Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
                    $tool->enabled = false;
                    $ok = $ok && $tool->save(true);
                }
                if ($ok) {
                    add_action('all_admin_notices', array($this, 'disable_notice_success'));
                } else {
                    add_action('all_admin_notices', array($this, 'disable_notice_error'));
                }
            }
        }
    }

    public function prepare_items()
    {
        if (!isset($_REQUEST['_wpnonce']) || wp_verify_nonce($_REQUEST['_wpnonce'], Tiny_LXP_Platform::get_plugin_name() . '-nonce')) {
            $this->process_action();

            $per_page = $this->get_items_per_page(Tiny_LXP_Platform::get_plugin_name() . '-tool_per_page');

            $args = array(
                'posts_per_page' => $per_page,
                'orderby' => 'name',
                'order' => 'ASC',
                'offset' => ($this->get_pagenum() - 1) * $per_page,
                'suppress_filters' => false
            );

            if (isset($_REQUEST['post_status'])) {
                $args['post_status'] = sanitize_text_field($_REQUEST['post_status']);
            }
            if (!empty($_REQUEST['s'])) {
                $args['s'] = sanitize_text_field($_REQUEST['s']);
            }

            if (!empty($_REQUEST['orderby'])) {
                if ('name' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'name';
                } elseif ('enabled' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'enabled';
                } elseif ('debugMode' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'debugMode';
                } elseif ('lastAccess' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'lastAccess';
                } elseif ('created' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'created';
                } elseif ('modified' == sanitize_text_field($_REQUEST['orderby'])) {
                    $args['orderby'] = 'modified';
                }
            }

            if (!empty($_REQUEST['order'])) {
                if ('asc' == strtolower(sanitize_text_field($_REQUEST['order']))) {
                    $args['order'] = 'ASC';
                } elseif ('desc' == strtolower(sanitize_text_field($_REQUEST['order']))) {
                    $args['order'] = 'DESC';
                }
            }

            $this->items = array_values(Tiny_LXP_Platform_Tool::all($args));
            $tool_counts = (array) wp_count_posts(Tiny_LXP_Platform::$postType, 'readable');
            if (isset($_REQUEST['post_status'])) {
                $total_items = $tool_counts[sanitize_text_field($_REQUEST['post_status'])];
            } else {
                $total_items = array_sum($tool_counts);
            }
            $total_pages = ceil($total_items / $per_page);

            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'per_page' => $per_page,
            ));

            $this->is_trash = isset($_REQUEST['post_status']) && (sanitize_text_field($_REQUEST['post_status']) === 'trash');
        }
    }

    protected function get_views()
    {
        $views = array();
        $num_tools = wp_count_posts(Tiny_LXP_Platform::$postType, 'readable');
        $total_tools = array_sum((array) $num_tools) - $num_tools->trash;

        $class = (count($_GET) <= 1) ? $class = 'current' : '';
        $views['all'] = $this->get_edit_link(array(), "All <span class=\"count\">({$total_tools})</span>", $class);
        if ($num_tools->publish > 0) {
            $class = (isset($_GET['post_status']) && (sanitize_text_field($_GET['post_status']) === 'publish')) ? $class = 'current' : '';
            $views['publish'] = $this->get_edit_link(array('post_status' => 'publish'),
                "Enabled <span class=\"count\">({$num_tools->publish})</span>", $class);
        }
        if ($num_tools->draft) {
            $class = (isset($_GET['post_status']) && (sanitize_text_field($_GET['post_status']) === 'draft')) ? $class = 'current' : '';
            $views['draft'] = $this->get_edit_link(array('post_status' => 'draft'),
                "Disabled <span class=\"count\">({$num_tools->draft})</span>", $class);
        }
        if ($num_tools->trash) {
            $class = (isset($_GET['post_status']) && (sanitize_text_field($_GET['post_status']) === 'trash')) ? $class = 'current' : '';
            $views['trash'] = $this->get_edit_link(array('post_status' => 'trash'),
                "Bin <span class=\"count\">({$num_tools->trash})</span>", $class);
        }

        return $views;
    }

    public function get_columns()
    {
        return get_column_headers(get_current_screen());
    }

    protected function get_sortable_columns()
    {
        $columns = array(
            'name' => array('title', true),
            'code' => array('code', true),
            'enabled' => array('enabled', false),
            'debugMode' => array('debugMode', false),
            'lastAccess' => array('lastAccess', false),
            'created' => array('created', false),
            'modified' => array('modified', false)
        );

        return $columns;
    }

    protected function column_default($item, $column_name)
    {
        return '';
    }

    protected function get_bulk_actions()
    {
        if (!$this->is_trash) {
            $actions = array(
                'enable' => __('Enable', Tiny_LXP_Platform::get_plugin_name()),
                'disable' => __('Disable', Tiny_LXP_Platform::get_plugin_name()),
                'trash' => __('Move to Bin', Tiny_LXP_Platform::get_plugin_name())
            );
        } else {
            $actions = array(
                'untrash' => __('Restore', Tiny_LXP_Platform::get_plugin_name()),
                'delete' => __('Delete permanently', Tiny_LXP_Platform::get_plugin_name())
            );
        }

        return $actions;
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->getRecordId()
        );
    }

    public function column_name($item)
    {
        if (in_array($item->code, $this->mu_items)) {
            return sprintf('<span style="text-decoration: line-through;" title="Network Tiny LXP tool exists with same code">%1$s</span>',
                esc_html($item->name));
        } else {
            return sprintf('<strong>%1$s</strong>', esc_html($item->name));
        }
    }

    protected function handle_row_actions($item, $column_name, $primary)
    {
        if ($column_name !== $primary) {
            return '';
        }

        if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
            $page = 'settings.php';
            $url = network_admin_url(add_query_arg('page', Tiny_LXP_Platform::get_plugin_name(), 'settings.php'));
        } else {
            $page = 'options-general.php';
            $url = menu_page_url(Tiny_LXP_Platform::get_plugin_name(), false);
        }
        $actions = array();
        if (!$item->deleted) {
            $edit_link = add_query_arg(array('page' => Tiny_LXP_Platform::get_plugin_name() . '-edit', 'tool' => absint($item->getRecordId())),
                $page);
            $actions['edit'] = sprintf(
                '<a href = "%1$s" aria-label = "%2$s">%3$s</a>', esc_url($edit_link),
                esc_attr(sprintf(__('Edit &#8220;%s&#8221;', Tiny_LXP_Platform::get_plugin_name()), $item->name)),
                esc_html(__('Edit', Tiny_LXP_Platform::get_plugin_name()))
            );
            if (!$item->enabled) {
                $enable_link = add_query_arg(array('action' => 'enable', 'tool' => absint($item->getRecordId())), $url);
                $actions['enable'] = sprintf(
                    '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($enable_link),
                    esc_attr(sprintf(__('Enable &#8220;%s&#8221;', Tiny_LXP_Platform::get_plugin_name()), $item->name)),
                    esc_html__('Enable', Tiny_LXP_Platform::get_plugin_name())
                );
            } else {
                $disable_link = add_query_arg(array('action' => 'disable', 'tool' => absint($item->getRecordId())), $url);
                $actions['disable'] = sprintf(
                    '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($disable_link),
                    esc_attr(sprintf(__('Disable &#8220;%s&#8221;', Tiny_LXP_Platform::get_plugin_name()), $item->name)),
                    esc_html__('Disable', Tiny_LXP_Platform::get_plugin_name())
                );
            }
            $trash_link = add_query_arg(array('action' => 'trash', 'tool' => absint($item->getRecordId())), $url);
            $actions['trash'] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($trash_link),
                esc_attr(sprintf(__('Bin &#8220;%s&#8221;', Tiny_LXP_Platform::get_plugin_name()), $item->name)),
                esc_html__('Bin', Tiny_LXP_Platform::get_plugin_name())
            );
        } else {
            $untrash_link = add_query_arg(array('action' => 'untrash', 'tool' => absint($item->getRecordId())), $url);
            $actions['untrash'] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($untrash_link),
                esc_attr(sprintf(__('Disable &#8220;%s&#8221;', Tiny_LXP_Platform::get_plugin_name()), $item->name)),
                esc_html__('Restore', Tiny_LXP_Platform::get_plugin_name())
            );
            $delete_link = add_query_arg(array('action' => 'delete', 'tool' => absint($item->getRecordId())), $url);
            $actions['delete'] = sprintf(
                '<a href="%1$s" aria-label="%2$s">%3$s</a>', esc_url($delete_link),
                esc_attr(sprintf(__('Permanently delete &#8220;%s&#8221;', Tiny_LXP_Platform::get_plugin_name()), $item->name)),
                esc_html__('Delete permanently', Tiny_LXP_Platform::get_plugin_name())
            );
        }

        return $this->row_actions($actions);
    }

    public function column_enabled($item)
    {
        $post = get_post($item->getRecordId());

        if (!$post) {
            return;
        }

        return esc_html__($item->enabled ? 'Yes' : 'No', Tiny_LXP_Platform::get_plugin_name());
    }

    public function column_debugMode($item)
    {
        $post = get_post($item->getRecordId());

        if (!$post) {
            return;
        }

        return esc_html__($item->debugMode ? 'Yes' : 'No', Tiny_LXP_Platform::get_plugin_name());
    }

    public function column_code($item)
    {
        if (!$item->deleted) {
            return esc_html($item->code);
        } else {
            return esc_html(str_replace('__trashed', '', $item->code));
        }
    }

    public function column_lastAccess($item)
    {
        if ($item->lastAccess) {
            $last_access = date('Y/m/d', $item->lastAccess);
        } else {
            $last_access = esc_html__('None', Tiny_LXP_Platform::get_plugin_name());
        }

        return esc_html($last_access);
    }

    public function column_created($item)
    {
        if (empty($item->created)) {
            return '';
        } else {
            return date('Y/m/d H:i', $item->created);
        }
    }

    public function column_modified($item)
    {
        if (empty($item->updated)) {
            return '';
        } else {
            return date('Y/m/d H:i', $item->updated);
        }
    }

    public function no_items()
    {
        if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
            esc_html_e('No Network Tiny LXP tools found.', Tiny_LXP_Platform::get_plugin_name());
        } else {
            esc_html_e('No Tiny LXP tools found.', Tiny_LXP_Platform::get_plugin_name());
        }
    }

    private function get_edit_link($args, $label, $class = '')
    {
        if (defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) {
            $args['page'] = Tiny_LXP_Platform::get_plugin_name();
            $url = network_admin_url(add_query_arg($args, 'settings.php'));
        } else {
            $url = add_query_arg($args, menu_page_url(Tiny_LXP_Platform::get_plugin_name(), false));
        }
        $class_html = '';
        $aria_current = '';
        if (!empty($class)) {
            $class_html = ' class="' . esc_attr($class) . '"';
            if ($class === 'current') {
                $aria_current = ' aria-current="page"';
            }
        }

        return '<a href="' . esc_url($url) . "\"{$class_html}{$aria_current}>{$label}</a>";
    }

}
