<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * UCLA support tools plugin.
 *
 * @package    local_ucla_support_tools
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

require_sesskey(); // Gotta have the sesskey.
require_login(); // Gotta be logged in (of course).

global $PAGE;
$PAGE->set_context(context_system::instance());

// Shared params
$action = required_param('action', PARAM_ALPHA);
$json = optional_param('json', '', PARAM_RAW);

// Send header
echo $OUTPUT->header();

// Create response.
$response = array('status' => 0);

switch ($action) {
    case 'createcategory':

        if (!empty($json)) {

            $data = json_decode($json);

            $name = clean_param($data->name, PARAM_TEXT);
            $color = clean_param($data->color, PARAM_ALPHANUM);

            try {
                $cat = \local_ucla_support_tools_category::create(array(
                    'name' => $name,
                    'color' => $color
                ));

                $output = $PAGE->get_renderer('local_ucla_support_tools');

                $response['id'] = $cat->get_id();
                $response['category_html'] = $output->render($cat);
                $response['category_label'] = $output->category_label($cat);
                $response['status'] = 1;

            } catch (Exception $ex) {
                $response['error'] = array(
                    'msg' => $ex->getMessage()
                );
            }
        }

        break;

    case 'createtool':

        if (!empty($json)) {
            $data = json_decode($json);
            
            $name = clean_param($data->name, PARAM_TEXT);
            $url = clean_param($data->url, PARAM_URL);
            $desc = clean_param($data->desc, PARAM_TEXT);
            
            try {
                $response['name'] = $name;
                $response['url'] = $url;
                $response['desc'] = $desc;

                $tool = \local_ucla_support_tools_tool::create(array(
                            'name' => urldecode($name),
                            'url' => $url,
                            'description' => urldecode($desc)
                ));

                $output = $PAGE->get_renderer('local_ucla_support_tools');

                $response['id'] = $name;
                $response['html'] = $output->render($tool);
                $response['status'] = 1;
            } catch (Exception $ex) {
                $response['error'] = array(
                    'msg' => $ex->getMessage()
                );
            }
        }

        break;
    case 'deletecategory':

        if (!empty($json)) {
            $data = json_decode($json);

            $id = clean_param($data->id, PARAM_TEXT);

            $cat = \local_ucla_support_tools_category::fetch($id);
            $cat->delete();

            $response['status'] = 1;
            $response['id'] = $id;
        }

        break;

    case 'deletetool':
        if (!empty($json)) {
            
            $data = json_decode($json);
            $id = clean_param($data->id, PARAM_INT);
            
            $tool = \local_ucla_support_tools_tool::fetch($id);
            $tool->delete();
            
            $response['status'] = 1;
            $response['id'] = $id;
        }

        break;
        
    case 'addtooltocategory':

        if (!empty($json)) {

            $data = json_decode($json);

            $catid = clean_param($data->catid, PARAM_INT);
            $toolid = clean_param($data->toolid, PARAM_INT);

            $cat = \local_ucla_support_tools_category::fetch($catid);
            $tool = \local_ucla_support_tools_tool::fetch($toolid);

            $status = $cat->add_tool($tool);

            if ($status) {
                $response['status'] = 1;
            }
        }

        break;
        
    case 'removetoolfromcategory':
        
        if (!empty($json)) {
            $data = json_decode($json);

            $catid = clean_param($data->catid, PARAM_INT);
            $toolid = clean_param($data->toolid, PARAM_INT);

            $cat = \local_ucla_support_tools_category::fetch($catid);
            $tool = \local_ucla_support_tools_tool::fetch($toolid);
            
            $status = $cat->remove_tool($tool);

            if ($status) {
                $response['toolid'] = $toolid;
                $response['catid'] = $catid;
                $response['status'] = 1;
            }
        }
        
        break;

    case 'gettags':
        $tags = \local_ucla_support_tools_tag::fetch_all();
        
        $out = array();
        foreach ($tags as $tag) {
            $out[$tag->name] = $tag->get_id();
        }
        
        $response['status'] = 1;
        $response['tags'] = $out;
        
        break;
}

echo json_encode($response);
echo $OUTPUT->footer();
exit();