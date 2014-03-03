<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
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

require_once(dirname(__FILE__) . '/wiki.php');

// Require a title.
$title = required_param('title', PARAM_TEXT);

$parser = new sidebar_wiki_parser();
$data = $parser->parse_doc_page($title);

$json = new stdClass();
$json->status = 1;
$json->content = array_reduce($data, function($out, $in) { 
    $out .= $in['parsed_text'];
    return $out;
}, '');


header('Content-Type: application/json');
echo json_encode($json);

