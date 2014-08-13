<?php
$functions = array(
    'droodle_user_id' => array(         //web service function name
        'classname'   => 'droodle_helpers_external',  //class containing the external function
        'methodname'  => 'user_id',          //external function name
        'classpath'   => 'local/droodle/helpers/externallib.php',  //file containing the class/external function
        'description' => 'Get user id.',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
    ),
    'droodle_create_user' => array(        
        'classname'   => 'droodle_helpers_external',
        'methodname'  => 'create_user', 
        'classpath'   => 'local/droodle/helpers/externallib.php',
        'description' => 'Creates user account', 
        'type'        => 'read',                 
    ),
    'droodle_enrol_user' => array(        
        'classname'   => 'droodle_helpers_external',
        'methodname'  => 'enrol_user', 
        'classpath'   => 'local/droodle/helpers/externallib.php',
        'description' => 'Enrols user into course', 
        'type'        => 'read',                 
    ),
    'droodle_delete_user' => array(        
        'classname'   => 'droodle_helpers_external',
        'methodname'  => 'delete_user', 
        'classpath'   => 'local/droodle/helpers/externallib.php',
        'description' => 'Get user events', 
        'type'        => 'read',                 
    ),
    'droodle_course_id' => array(         //web service function name
        'classname'   => 'droodle_helpers_external',  //class containing the external function
        'methodname'  => 'course_id',          //external function name
        'classpath'   => 'local/droodle/helpers/externallib.php',  //file containing the class/external function
        'description' => 'Get course id.',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
    ),
    'droodle_test_connection' => array(         //web service function name
        'classname'   => 'droodle_helpers_external',  //class containing the external function
        'methodname'  => 'test_connection',          //external function name
        'classpath'   => 'local/droodle/helpers/externallib.php',  //file containing the class/external function
        'description' => 'Test connection.',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
    ),
    'droodle_add_group_member' => array(        
        'classname'   => 'droodle_helpers_external',
        'methodname'  => 'add_group_member', 
        'classpath'   => 'local/droodle/helpers/externallib.php',
        'description' => 'Add group member.', 
        'type'        => 'read',                 
    ),
   'droodle_unenrol_user' => array(
        'classname'   => 'droodle_helpers_external',
        'methodname'  => 'unenrol_user',
        'classpath'   => 'local/droodle/helpers/externallib.php',
        'description' => 'Unenrol user',
        'type'        => 'read',
    ),
);

?>