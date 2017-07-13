<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Redirect users to combined enrolled users and participants page.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/registrar_query.base.php');

/**
 * Base class.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class registrar_stored_procedure extends registrar_query {
    /**
     * Returns the array describing the parameters are needed for the
     * stored procedure.
     * 
     * @return array
     */
    abstract public function get_query_params();

    /**
     * Returns the stored procedure itself.
     * @return string
     */
    public function get_stored_procedure() {
        $classname = get_class($this);
        if ($classname == 'registrar_stored_procedure') {
            throw new registrar_stored_procedure_exception('bad-oo');
        }

        return str_replace('registrar_', '', get_class($this));
    }

    /**
     * Try not to use this public function. The caller should correctly index
     * the array that is passed in.
     *
     * @param array $args
     * @return array
     */
    public function unindexed_key_translate($args) {
        $spargs = array();
        foreach ($this->get_query_params() as $key => $strkey) {
            if (isset($args[$strkey])) {
                $newarg = $args[$strkey];
            } else if (isset($args[$key])) {
                $newarg = $args[$key];
            } else {
                debugging('badly indexed parameters');
                return false;
            }

            $spargs[$strkey] = $newarg;
        }

        return $spargs;
    }

    /**
     * Executes stored procedure call.
     *
     * @param array $args
     * @return boolean|string
     */
    public function remote_call_generate($args) {
        $storedproc = $this->get_stored_procedure();

        $spargs = $this->unindexed_key_translate($args);
        if (!$spargs) {
            return false;
        }

        foreach ($spargs as $strkey => $val) {
            try {
                if (!ucla_validator($strkey, $val)) {
                    return false;
                }
            } catch (moodle_exception $e) {
                // Not a registered validation.
                continue;
            }
        }

        $procsql = "EXECUTE $storedproc ";
        if (!empty($spargs)) {
            $procsql .= "'" . implode("', '", $spargs) . "'";
        }

        return $procsql;
    }

    /**
     * By default, most stored procedures don't need validation.
     * 
     * @param array $new
     * @param array $old
     * @return boolean
     */
    public function validate($new, $old) {
        return true;
    }
}

/**
 * Extends registrar_query_exception.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registrar_stored_procedure_exception extends registrar_query_exception {
    // Nothing.
}
