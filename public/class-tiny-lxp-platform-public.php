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

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.spvsoftwareproducts.com/php/wordpress-tiny-lxp-platform
 * @since      1.0.0
 * @package    Tiny_LXP_Platform
 * @subpackage Tiny_LXP_Platform/public
 * @author     Waqar Muneer <waqarmuneer@gmail.com>
 */
use ceLTIc\LTI;

class Tiny_LXP_Platform_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function parse_request()
    {
        if (isset($_GET[Tiny_LXP_Platform::get_plugin_name()])) {
            if (!is_user_logged_in() && !isset($_GET['content']) && !isset($_GET['keys'])) {                                              
                Activity::is_public();
            }
            if (isset($_GET['tools'])) {
                header('Content-type: text/html');
                $allowed = array('div' => array('class' => true), 'h2' => array(), 'table' => array('style' => true), 'span' => array('class' => true), 'td' => array(), 'tr' => array('class' => true),  'p' => array(), 'br' => array(), 'input' => array('type' => true, 'name' => true, 'class' => true, 'value' => true, 'toolname' => true), 'button' => array('class' => true, 'id' => true, 'disabled' => true));
                echo(wp_kses($this->get_tools_list(), $allowed));
            } else if (isset($_GET['usecontentitem'])) {
                header('Content-type: application/json');
                echo(json_encode($this->get_tool(sanitize_text_field($_GET['tool']))));
            } else if (isset($_GET['keys'])) {
                $jwt = LTI\Jwt\Jwt::getJwtClient();
                $options = Tiny_LXP_Platform_Tool::getOptions();
                $keys = $jwt::getJWKS($options['privatekey'], 'RS256', $options['kid']);
                header('Content-type: application/json');
                echo(json_encode($keys));
            } else if (isset($_GET['auth'])) {
                $this->handleRequest();
            } else if (isset($_GET['embed'])) {
                $this->renderTool();
            } else if (isset($_GET['content'])) {
                $this->content(sanitize_text_field($_GET['tool']));
            } else if (isset($_GET['deeplink'])) {
                $this->message(true);
            } else if (isset($_GET['post'])) {
                $this->message();
            }
            exit;
        }
    }

    private function handleRequest()
    {
        $ok = !empty($_REQUEST['client_id']);
        if ($ok) {
            $tool = Tiny_LXP_Platform_Tool::fromCode(sanitize_text_field($_REQUEST['client_id']), Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
            $ok = !empty($tool->created);
        }
        if ($ok) {
            LTI\Tool::$defaultTool = $tool;
            $requestState = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.',  $_REQUEST['state'])[1]))));
            Activity::validate(trim(LTI\Tool::$defaultTool->messageUrl), trim($requestState->target_link_uri));
            $platform = $this->get_platform();
            $platform->handleRequest();
        } else {
            $this->error_page(__('Tool not found.', Tiny_LXP_Platform::get_plugin_name()));
        }
    }

    private function renderTool()
    {
        $allowed = array('em' => array());
        $reason = null;
        get_header();
        echo('		<div id="primary" class="content-area">' . "\n");
        echo('          <div id="content" class="site-content" role="main">' . "\n");
        $post = $this->get_post(intval(sanitize_text_field($_GET['post'])));
        $ok = !empty($post);
        if (!$ok) {
            $reason = 'Missing or invalid post attribute in link';
        } else {
            $ok = !empty($_GET['id']);
            if (!$ok) {
                $reason = 'Missing id attribute in link';
            }
        }
        if ($ok) {
            $link_atts = $this->get_link_atts($post, sanitize_text_field($_GET['id']));
            $ok = !empty($link_atts['tool']);
            if (!$ok) {
                $reason = 'No tool specified';
            }
        }
        if ($ok) {
            $tool = Tiny_LXP_Platform_Tool::fromCode($link_atts['tool'], Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
            $ok = !empty($tool);
            if (!$ok) {
                $reason = 'Tool not found';
            }
        }
        if ($ok) {
            $url = esc_url(add_query_arg(array(Tiny_LXP_Platform::get_plugin_name() => '', 'post' => $post->ID, 'id' => $link_atts['id']),
                    get_site_url()));
            $width = $tool->getSetting('presentationWidth');
            if (!empty($atts['width'])) {
                $width = intval($atts['width']);
            }
            $height = $tool->getSetting('presentationHeight');
            if (!empty($atts['height'])) {
                $height = intval($atts['height']);
            }
            if (empty($width)) {
                $width = '100%';
            }
            if (empty($height)) {
                $height = '400px';
            }
            $size = '';
            if (!empty($width)) {
                $size = " width: {$width};";
            }
            if (!empty($height)) {
                $size .= " height: {$height};";
            }
            $size = esc_attr($size);
            echo('            <iframe style="border: none; overflow: scroll;' . esc_attr($size) . '" class="" src="' . esc_attr($url) . '" allowfullscreen></iframe>');
        } else {
            $message = __('Sorry, the Tiny LXP tool could not be launched.', Tiny_LXP_Platform::get_plugin_name());
            if (!empty($reason)) {
                $options = Tiny_LXP_Platform_Tool::getOptions();
                $debug = $tool->debugMode || (isset($options['debug']) && ($options['debug'] === 'true'));
                if ($debug) {
                    $message .= ' <em>[' . esc_html($reason) . ']</em>';
                }
            }
            echo('            <p><strong>' . wp_kses($message, $allowed) . '</strong></p>' . "\n");
        }
        echo('          </div>' . "\n");
        echo('        </div>' . "\n");
        get_sidebar();
        get_footer();
    }

    public function message($deeplink = false)
    {
        $debug = false;
        $reason = null;
        $post = $this->get_post(intval(sanitize_text_field($_GET['post'])));
        $ok = !empty($post);
        if (!$ok) {
           $post =new \stdClass();
           $post->ID = 9999;
           $post->post_title = "";
           $ok = true;
        } else if (!$deeplink) {
            $ok = !empty($_GET['id']);
            if (!$ok) {
                $reason = __('Missing id attribute in link', Tiny_LXP_Platform::get_plugin_name());
            }
        }
        if ($ok) {
            if (!$deeplink) {
                $link_atts = $this->get_link_atts($post, sanitize_text_field($_GET['id']));
                $ok = !empty($link_atts['tool']);
                if (!$ok) {
                    $reason = __('No tool specified', Tiny_LXP_Platform::get_plugin_name());
                }
            } elseif (empty($_GET['tool'])) {
                $ok = false;
                $reason = __('Missing tool attribute in link', Tiny_LXP_Platform::get_plugin_name());
            } else {
                $link_atts['tool'] = sanitize_text_field($_GET['tool']);
            }
        }
        if ($ok) {
            $tool = Tiny_LXP_Platform_Tool::fromCode($link_atts['tool'], Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
            $ok = !empty($tool);
            if (!$ok) {
                $reason = __('Tool not found', Tiny_LXP_Platform::get_plugin_name());
            }
        }
        if ($ok && !$deeplink) {
            $debug = $tool->debugMode;
            $ok = !empty($link_atts['id']);
            if (!$ok) {
                $reason = __('Duplicate id attribute in link', Tiny_LXP_Platform::get_plugin_name());
            }
        }
        if ($ok) {
            $target = (!empty($link_atts['target'])) ? $link_atts['target'] : $tool->getSetting('presentationTarget', 'window');
            $ok = in_array($target, array('window', 'popup', 'iframe', 'embed'));
            if (!$ok) {
                $reason = __('Invalid target specified', Tiny_LXP_Platform::get_plugin_name());
            }
        }
        if ($ok) {
            if (empty($link_atts['url'])) {
                $url = $tool->messageUrl;
            } elseif (strpos($link_atts['url'], '://') === false) {
                $url = "{$tool->messageUrl}{$link_atts['url']}";
            } elseif (strpos($link_atts['url'], $tool->messageUrl) === 0) {
                $url = $link_atts['url'];
            }  else {
                $ok = false;
                $reason = __('Invalid url attribute', Tiny_LXP_Platform::get_plugin_name());
            }
        }
        if(isset($post->post_type) && $post->post_type == "tl_lesson" && !$ok){
            $link_atts['tool'] = get_post_meta($post->ID, 'lti_tool_code', true); 
            $link_atts['title'] =  get_post_meta($post->ID, 'lti_content_title', true); 
            $link_atts['url'] = get_post_meta($post->ID, 'lti_tool_url', true); 
            $link_atts['custom'] = get_post_meta($post->ID, 'lti_custom_attr', true);
            $target = "embed";
            $link_atts['id'] = get_post_meta($post->ID, 'lti_post_attr_id', true);
            $toolObj = Tiny_LXP_Platform_Tool::fromCode($link_atts['tool'], Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
            $url = strpos($link_atts['url'], $toolObj->messageUrl) === false ? $toolObj->messageUrl.$link_atts['url'] : $link_atts['url'];
            $tool = Tiny_LXP_Platform_Tool::fromCode($link_atts['tool'], Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
            $ok = true;
        }
        if ($ok) {
            $options = Tiny_LXP_Platform_Tool::getOptions();
            $user = isset($_GET['student']) && intval($_GET['student']) > 0 ? get_user_by('ID', $_GET['student']) : wp_get_current_user();
            if (!empty($link_atts['title'])) {
                $title = $link_atts['title'];
            } else {
                $title = (!empty($link_text)) ? $link_text : $link_atts['tool'];
            }
            $params = array(
                'context_id' => strval($post->ID),
                'context_title' => $post->post_title,
                'context_type' => 'CourseSection',
                'launch_presentation_document_target' => ($target !== 'popup') ? $target : 'window',
                'tool_consumer_info_product_family_code' => 'moodle',
                'tool_consumer_info_version' => get_bloginfo('version'),
                'tool_consumer_instance_name' => get_bloginfo('name'),
                'tool_consumer_instance_description' => get_bloginfo('description'),
                'tool_consumer_instance_url' => get_site_url(),
                'tool_consumer_instance_contact_email' => get_bloginfo('admin_email'),
            );
            if (!empty($options['platformguid'])) {
                $params['tool_consumer_instance_guid'] = $options['platformguid'];
            }
            if (!$deeplink) {
                $msg = 'basic-lti-launch-request';
                $params['resource_link_id'] = "{$post->ID}-{$link_atts['id']}";
                $params['resource_link_title'] = $title;
            } else {
                $msg = 'ContentItemSelectionRequest';
                $params['accept_media_types'] = 'application/vnd.ims.lti.v1.ltilink,*/*';
                $params['accept_multiple'] = 'false';
                $params['accept_presentation_document_targets'] = 'embed,frame,iframe,window,popup';
                $params['content_item_return_url'] = get_option('siteurl') . '/?' . Tiny_LXP_Platform::get_plugin_name() . '&content&tool=' . urlencode($link_atts['tool']);
                $url = $tool->contentItemUrl;
            }
            if (($target === 'popup') || ($target === 'iframe') || ($target === 'embed')) {
                $width = $tool->getSetting('presentationWidth');
                if (!empty($link_atts['width'])) {
                    $width = intval($link_atts['width']);
                }
                if (!empty($width)) {
                    $params['launch_presentation_width'] = $width;
                }
                $height = $tool->getSetting('presentationHeight');
                if (!empty($link_atts['height'])) {
                    $height = intval($link_atts['height']);
                }
                if (!empty($height)) {
                    $params['launch_presentation_height'] = $height;
                }
            }
            if ($tool->getSetting('sendUserId', 'false') === 'true') {
                $params['user_id'] = strval($user->ID);
            }
            if ($tool->getSetting('sendUserName', 'false') === 'true') {
                if (!empty($user->display_name)) {
                    $params['lis_person_name_full'] = $user->display_name;
                }
                if (!empty($user->first_name)) {
                    $params['lis_person_name_given'] = $user->first_name;
                }
                if (!empty($user->last_name)) {
                    $params['lis_person_name_family'] = $user->last_name;
                }
            }
            if ($tool->getSetting('sendUserEmail', 'false') === 'true') {
                $params['lis_person_contact_email_primary'] = $user->user_email;
            }
            if ($tool->getSetting('sendUserRole', 'false') === 'true') {
                if (current_user_can('manage_options')) {
                    $params['roles'] = 'urn:lti:instrole:ims/lis/Instructor';
                } else {
                    $params['roles'] = 'urn:lti:instrole:ims/lis/Learner';
                }
            }
            if ($tool->getSetting('sendUserUsername', 'false') === 'true') {
                $params['ext_username'] = $user->user_login;
            }
            $custom = array();
            if (!empty($link_atts['custom'])) {
                parse_str(str_replace(';', '&', $link_atts['custom']), $custom);
                foreach ($custom as $name => $value) {
                    $name = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($name)));
                    if (!empty($name)) {
                        $params["custom_{$name}"] = $value;
                    }
                }
            }
            if(isset($_GET['is_summary'])){
                $params["custom_is_summary"] = 1;
                $params["custom_student_id"] = $_GET['student_id'];
            }
            if(isset($_GET['slideNumber'])){
                $params["custom_slideNumber"] = $_GET['slideNumber'];
            }
            if(isset($_GET['skipSave'])){
                $params["custom_skipSave"] = $_GET['skipSave'];
            }
            $params["custom_platform"] = 'wordpress';

            if (!empty($tool->getSetting('custom'))) {
                // parse_str(str_replace('&#13;&#10;', '&', $tool->getSetting('custom')), $custom);
                parse_str($tool->getSetting('custom'), $custom);
                foreach ($custom as $name => $value) {
                    //$name = preg_replace('/[^a-z0-9]/', '', strtolower(trim($name)));
                    $name = preg_replace('/[^a-z0-9]\d+\x3B/', '', strtolower(trim($name)));
                    $course = get_post(get_post_meta($post->ID, 'tl_course_id', true));
                    if (!empty($name)) {
                        if ($course) {
                            if ($value === '$Context.id') {
                                $value = $course->ID;
                            }
                            if ($value === '$CourseSection.title') {
                                $value = $course->post_title;
                            }
                            if ($value === '$CourseSection.label') {
                                $value = $course->post_name;
                            }
                            if ($value === '$Person.email.primary') {
                                $value = $user->user_email;
                            }
                            if ($value === '$Person.name.given') {
                                $value = $user->first_name;
                            }
                            if ($value === '$Person.name.family') {
                                $value = $user->last_name;
                            }
                            if ($value === '$User.id') {
                                $value = strval($user->ID);
                            }
                        }
                        $params["custom_{$name}"] = $value;
                    }
                }

                if (isset($_GET["assignment_id"])) {
                    $params["custom_assignment_id"] = $_GET["assignment_id"];
                }
            }
            LTI\Tool::$defaultTool = $tool;
            $platform = $this->get_platform();
            echo ($platform->sendMessage($url, $msg, $params, '_self', "{$user->ID}", "{$post->ID}"));
            $day = date('Y-m-d');
            if ($day !== date('Y-m-d', $tool->lastAccess)) {
                $tool->lastAccess = strtotime($day);  // Update last access
                $tool->save();
            }
        } else {
            $this->error_page($reason, $debug);
        }
    }

    private function get_post($post_id)
    {
        $post = null;
        if (current_user_can('read_post', $post_id)) {
            $post = get_post($post_id);
        }

        return $post;
    }

    public static function get_link_atts($post, $id)
    {
        $link_atts = array();
        $pattern = get_shortcode_regex(array(Tiny_LXP_Platform::get_plugin_name()));
        if (preg_match_all("/{$pattern}/", $post->post_content, $shortcodes, PREG_SET_ORDER) !== false) {
            $link_text = '';
            foreach ($shortcodes as $shortcode) {
                $atts = shortcode_parse_atts($shortcode[3]);
                if (!empty($atts['id']) && ($atts['id'] === $id)) {
                    if (empty($link_atts)) {
                        $link_atts = $atts;
                        $link_text = $shortcode[5];
                    } else {  // Duplicate link
                        unset($link_atts['id']);
                        break;
                    }
                }
            }
        }
        foreach ($link_atts as $key => $value) {
            $link_atts[$key] = str_replace('&amp;', '&', $value);
        }

        return $link_atts;
    }

    private function get_platform()
    {
        $options = Tiny_LXP_Platform_Tool::getOptions();
        $platform = new Tiny_LXP_Platform_Platform(Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        $platform->setKey(LTI\Tool::$defaultTool->getKey());
        $platform->secret = LTI\Tool::$defaultTool->secret;
        $platform->platformId = get_option('siteurl');
        $platform->clientId = LTI\Tool::$defaultTool->code;
        $platform->deploymentId = strval(get_current_blog_id());
        $platform->ltiVersion = LTI\Util::LTI_VERSION1P3;
        $platform->signatureMethod = 'RS256';
        $platform->kid = $options['kid'];
        $platform->rsaKey = $options['privatekey'];
        if (!LTI\Tool::$defaultTool->canUseTinyLXP13()) {
            $platform->ltiVersion = LTI\Util::LTI_VERSION1;
            $platform->signatureMethod = 'HMAC-SHA1';
        } else {
            $platform->ltiVersion = LTI\Util::LTI_VERSION1P3;
            $platform->signatureMethod = 'RS256';
        }

        return $platform;
    }

    private function get_tools_list()
    {
        $hereValue = function($text) {
            return esc_html($text);
        };
        $hereAttr = function($text) {
            return esc_attr($text);
        };

        $args = array(
            'post_status' => 'publish'
        );
        $tools = Tiny_LXP_Platform_Tool::all($args);
        if (is_multisite()) {
            switch_to_blog(1);
            $tools = array_merge($tools,
                Tiny_LXP_Platform_Tool::all(array_merge($args, array('post_type' => Tiny_LXP_Platform_Tool::POST_TYPE_NETWORK))));
            restore_current_blog();
        }
        ksort($tools, SORT_STRING);

        $list = '
        <div class="tiny-lxp-platform-modal">
        <div class="tiny-lxp-platform-modal-content">
            <h2>Tiny LXP Tool</h2>
            <div>

        ';
        if (!empty($tools)) {
            $list .= 'Select the Tiny LXP tool you want to add a link for:';
            $list .= ' <table style="width:100%">';
            foreach ($tools as $tool) {
                $list .= '<tr class="tool-input-tr">';
                $list .= '<td><input type="radio" name="tool" class="tiny-lxp-platform-tool" value="'.$tool->code.'" toolname="'.$tool->name.'">' . $tool->name .'</td>';
                $list .= '<td><span class="dashicons dashicons-search"></span></td>';
                $list .=  '</tr>';
            }
            $list .= ' </table>';
        } else {
            $list .= 'There are no enabled Tiny LXP tools defined.';
        }
        $list .= '
            </div>
            <p>
                <button class="button button-primary" id="tiny-lxp-platform-select" disabled>Select</button>
                <button class="button" id="tiny-lxp-platform-cancel">Cancel</button>
            </p>
        </div>
        </div>
        ';

        return $list;
    }

    private function get_tool($code)
    {
        $obj = new \stdClass();
        $tool = Tiny_LXP_Platform_Tool::fromCode($code, Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        $obj->useContentItem = $tool->useContentItem;

        return $obj;
    }

    private function content($code)
    {
        $tool = Tiny_LXP_Platform_Tool::fromCode($code, Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        $platform = new Tiny_LXP_Platform_Platform(Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        LTI\Tool::$defaultTool = $tool;
        $platform->handleRequest();

        $html = <<< EOD
        <html>
        <head>
            <title>Content</title>
            <script>
            var wdw = window.opener;

EOD;
        if ($platform->ok) {
            $linktext = $tool->name;
            $item = $platform->contentItem;
            $attr = "tool={$code}";
            $randomId = strtolower(LTI\Util::getRandomString());
            $attr .= ' id=' . $randomId;
            if (!empty($item->title)) {
                $attr .= static::setAttribute('title', $item->title);
                $linktext = $item->title;
            }
            if (!empty($item->url)) {
                $attr .= static::setAttribute('url', $item->url);
            }
            if (isset($item->placementAdvice)) {
                if (!empty($item->placementAdvice->presentationDocumentTarget)) {
                    $targets = explode(',', $item->placementAdvice->presentationDocumentTarget);
                    $attr .= static::setAttribute('target', $targets[0]);
                    if (!empty($item->placementAdvice->displayWidth)) {
                        $attr .= static::setAttribute('width', $item->placementAdvice->displayWidth);
                    }
                    if (!empty($item->placementAdvice->displayHeight)) {
                        $attr .= static::setAttribute('height', $item->placementAdvice->displayHeight);
                    }
                }
            }
            if (!empty($item->custom)) {
                $attr .= static::setAttribute('custom', $item->custom);
            }
            $activity = isset($item->custom['activity']) ? $item->custom['activity'] : "";
            $plugin_name = Tiny_LXP_Platform::get_plugin_name();
            $html .= <<< EOD
            if (!wdw.LtiPlatformText) {
                wdw.LtiPlatformText = '{$linktext}';
            }
            var id = Math.random().toString(16).substr(2, 8);
            var tinyLxpToolUrl =  wdw.document.getElementById("tiny_lxp_tool_url");
            var tinyLxpToolCode =  wdw.document.getElementById("tiny_lxp_tool_code");
            var tinyLxpContetntTitle =  wdw.document.getElementById("tiny_lxp_content_title");
            var tinyLxpCustomAttr =  wdw.document.getElementById("tiny_lxp_custom_attr");
            var tinyLxpPostAttrId =  wdw.document.getElementById("tiny_lxp_post_attr_id");
            if(tinyLxpToolUrl){
                tinyLxpToolUrl.value= "{$item->url}";
                tinyLxpToolCode.value= "{$code}";
                tinyLxpContetntTitle.value= "{$item->title}";
                tinyLxpCustomAttr.value= "custom=activity={$activity}";
                tinyLxpPostAttrId.value= "{$randomId}";
               var title =  wdw.document.getElementById("title");
               if(title){
                title.value = "{$item->title}";
                wdw.document.getElementById("title-prompt-text").classList.add("screen-reader-text");
               }else{
                wdw.document.getElementsByClassName("wp-block wp-block-post-title")[0].innerHTML="{$item->title}";
               }     
            }else{
                wdw.LtiPlatformProps.onChange(wdw.wp.richText.insert(wdw.LtiPlatformProps.value, '[{$plugin_name} {$attr}]' + wdw.LtiPlatformText + '[/{$plugin_name}]'));
                wdw.LtiPlatformProps.onFocus();
            }
            window.close();
            EOD;
        } else {
            $html .= <<< EOD
            window.close();
            wdw.alert('Sorry, unable to verify the selected content');

        EOD;
        }
        $html .= <<< EOD
        </script>
        </head>
        <body>
        </body>
        </html>
    EOD;
        $allowed = array('html' => array(), 'head' => array(), 'title' => array(), 'script' => array(), 'body' => array());
        echo wp_kses($html, $allowed);
    }

    private static function setAttribute($name, $value)
    {
        $attr = '';
        if (!empty($value)) {
            if (!is_array($value)) {
                $attr = $value;
            } else {
                foreach ($value as $key => $val) {
                    $attr .= "{$key}={$val};";
                }
                $attr = substr($attr, 0, -1);
            }
            if (strpos($attr, ' ') !== false) {
                $attr = '"' . $attr . '"';
            }
            $attr = " {$name}={$attr}";
        }

        return $attr;
    }

    private function error_page($reason, $debug = false)
    {
        $allowed = array('em' => array());
        $message = __('Sorry, the Tiny LXP tool could not be launched.', Tiny_LXP_Platform::get_plugin_name());
        if (!empty($reason)) {
            $options = Tiny_LXP_Platform_Tool::getOptions();
            $debug = $debug || (isset($options['debug']) && ($options['debug'] === 'true'));
            if ($debug) {
                $message .= ' <em>[' . $reason . ']</em>';
            }
        }
        echo('<html>' . "\n");
        echo('  <head>' . "\n");
        echo('    <title>' . esc_html__('Tiny LXP Tool launch error', Tiny_LXP_Platform::get_plugin_name()) . '</title>' . "\n");
        echo('  </head>' . "\n");
        echo('  <body>' . "\n");
        echo('    <p><strong>' . wp_kses($message, $allowed) . '</strong></p>' . "\n");
        echo('  </body>' . "\n");
        echo('</html>' . "\n");
    }

}
