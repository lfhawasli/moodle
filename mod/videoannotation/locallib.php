<?php
    function videoannotation_user_candoanything() {
        $context = get_context_instance(CONTEXT_SYSTEM);

        return (has_capability('moodle/site:doanything', $context));
    }
?>