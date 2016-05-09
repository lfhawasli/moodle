<?php
require_once(dirname(__FILE__).'/registrar_stored_procedure.base.php');

class registrar_ccle_ta_sections extends registrar_stored_procedure {
    
    /**
     * Want to remove dummy users from the ucla_id field.
     *
     * @param array $fields
     * @return array
     */
    function clean_row($fields) {
        $new = parent::clean_row($fields);

        if (is_dummy_ucla_user($new['ucla_id'])) {
            $new['ucla_id'] = '';
        }

        return $new;
    }

    function get_query_params() {
        return array('term', 'srs');
    }

    function get_stored_procedure() {
        return 'ccle_ta_sections';
    }
}
