<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Registrar cacheable class.
 *
 * @package     local_ucla
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

/**
 * This class implements a caching layer for any stored procedure that is
 * derived from it. The caching layer depends on some naming conventions for
 * its database tables:
 *
 * <stored procedure name>_cache
 *
 * The  <stored procedure name>_cache table is outlined as:
 * id
 * param_<param name> Then as many columns as needed by get_query_params()
 * expires_on   UNIX timestamp for how long the cache entries related to this
 *              query should be valid
 * <return name>    Then as many columns as needed by get_result_columns()
 */
abstract class registrar_cacheable_stored_procedure extends registrar_stored_procedure {
    /**
     * If null, then will use the local_plugin setting. Here so that child
     * classes can override plugin settings.
     * @var int     Time in seconds.
     */
    protected static $registrarcachettl = null;

    public function __construct() {
        // See if ttl was manually set beforehand, else use plugin default.
        if (empty(self::$registrarcachettl)) {
            self::$registrarcachettl = get_config('local_ucla', 'registrar_cache_ttl');
        }
    }

    /**
     * Returns the array describing the columns that are returned by the
     * stored procedure.
     * @return array
     */
    abstract public function get_result_columns();

    /**
     * This function will first try to see if there is a valid cache copy of
     * the results for the given set of parameters.
     *
     * If a valid cache is found, then those results are returned instead. If
     * not, then the regular retrieve_registrar_info is called and a cache copy
     * is saved.
     *
     * Note, that parameters that return 0 results will always be calling the
     * registrar.
     *
     * @param aray $drivingdata The data to run a set of queries on.
     * @param boolean $cached Default true. If false, then will return uncached data
     *
     * @return array    False indicates bad input. Empty array indicates good input, but no results.
     */
    public function retrieve_registrar_info($drivingdata, $cached = true) {
        global $DB;
        $queryparams = array(); // Store values to pass into $DB object.
        $results = array();     // Results to return.

        // See if caching is turned off.
        if (!$cached) {
            // Just call parent.
            return parent::retrieve_registrar_info($drivingdata);
        }

        // Get information and parameters for this call.
        $storedproccache = $this->get_stored_procedure() . '_cache';
        $params = $this->unindexed_key_translate($drivingdata);
        if (empty($params)) {
            return false;
        }

        // Columns to be returned.
        $columnstoreturn = implode(',', $this->get_result_columns());

        // Try to see if there is a valid cache copy.
        // NOTE: id needs to be returned so that we don't get "Did you remember
        // to make the first column something unique in your call to
        // get_records?" errors.
        $sql = "SELECT  id, $columnstoreturn
                FROM    {{$storedproccache}}
                WHERE   expires_on >= UNIX_TIMESTAMP() AND ";

        $firstentry = true;
        foreach ($params as $name => $value) {
            // Remember that parameters columns with have "param_" prefix.
            $firstentry ? $firstentry = false : $sql .= ' AND ';
            $sql .= 'param_' . $name . '= :param_' . $name;
            $queryparams['param_' . $name] = $value;
        }

        try {
            $cacheresults = $DB->get_records_sql($sql, $queryparams);
        } catch (Exception $e) {
            // Query failed, maybe table is missing or columns mismatched.
            throw new registrar_stored_procedure_exception(
                    sprintf('Cache query failed for: %s (%s)', $storedproccache,
                            implode('|', $queryparams)));
        }

        if (empty($cacheresults)) {
            // No valid cache found, so first delete any cache copy;
            // don't want to have old data laying around.
            $DB->delete_records($storedproccache, $queryparams);

            // Call stored procedure regularly.
            $results = parent::retrieve_registrar_info($drivingdata);

            if (!empty($results)) {
                // Save cache copy.

                // Set cache timeout.
                $expireson = time() + static::$registrarcachettl;

                $queryparams['expires_on'] = $expireson;

                foreach ($results as $result) {
                    // Take advantage of the fact that result is indexed by column name.
                    $insertparams = array_merge($queryparams, $result);
                    try {
                        $DB->insert_record($storedproccache, (object) $insertparams, false, true);
                    } catch (Exception $e) {
                        // Insert failed, maybe table is missing or columns mismatched.
                        throw new registrar_stored_procedure_exception(
                                sprintf('Cache insert failed for: %s (%s)', $storedproccache,
                                        implode('|', $insertparams)));
                    }
                }
            }
        } else {
            // Format results so it is returned as an array.
            foreach ($cacheresults as $cacheresult) {
                $results[] = (array) $cacheresult;
            }
        }
        return $results;
    }
}
