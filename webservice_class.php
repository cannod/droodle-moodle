<?php

/**
 * @author Dave Cannon 
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle multiauth
 *
 * Authentication Plugin: droodle
 *
 * Checks against Joomla web services provided by droodle
 *
 * 2009-10-25  File created.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/auth/manual/auth.php');
require_once($CFG->dirroot.'/enrol/locallib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/lib/grouplib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * droodle
 */
class droodle_webservice {

    function test () {
        return "Moodle web services are working!";
    }

    function user_id ($username) {
        global $DB;

        $username = utf8_decode ($username);
        $username = strtolower ($username);
        $conditions = array("username" => $username);
        $user = $DB->get_record("user", $conditions);

        if (!$user) {
            return 0;
        }

        return $user->id;
    }

    function course_id ($idnumber) {
        global $DB;

        $idnumber = utf8_decode ($idnumber);
        $idnumber = strtolower ($idnumber);
        $conditions = array("idnumber" => $idnumber);
        $course = $DB->get_record("course", $conditions);

        if (!$course) {
            return 0;
        }

        return $course->id;
    }

    function enrol_user ($username, $courseid, $roleid = 5, $timestart, $timeend) {
        global $CFG, $DB, $PAGE;

        $username = utf8_decode ($username);
        $username = strtolower ($username);
        /* Create the user before if it is not created yet */
        $conditions = array ('username' => $username);
        $user = $DB->get_record('user', $conditions);
        if (!$user) {
            return 0;
        }

        $user = $DB->get_record('user', $conditions);
        $conditions = array ('id' => $courseid);
        $course = $DB->get_record('course', $conditions);

        if (!$course) {
            return 0;
        }

        // Get enrol start and end dates of manual enrolment plugin.
        if ($CFG->version >= 2011061700) {
            $manager = new course_enrolment_manager($PAGE, $course);
        } else {
            $manager = new course_enrolment_manager($course);
        }

        $instances = $manager->get_enrolment_instances();
        $plugins = $manager->get_enrolment_plugins();
        $enrolid = 1; // Manual.

        /*
        $today = time();
        $today = make_timestamp(date('Y', $today), date('m', $today),
            date('d', $today), date ('H', $today), date ('i', $today), date ('s', $today));
        $timestart = $today;
        $timeend = 0;
        */

        $found = false;
        foreach ($instances as $instance) {
            if ($instance->enrol == 'manual') {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return 0;
        }

        $plugin = $plugins['manual'];

        $timestart = isset($timestart) ? $timestart : time();

        $timeend = isset($timeend) ? $timeend : 0;

        // Only use default enrol period if no timeend set.
        if ($timeend == 0) {
            if ( $instance->enrolperiod) {
                $timeend   = $timestart + $instance->enrolperiod;
            }
        }

        // First, check if user is already enroled but suspended, so we just need to enable it.

        $conditions = array ('courseid' => $courseid, 'enrol' => 'manual');
        $enrol = $DB->get_record('enrol', $conditions);

        if (!$enrol) {
            return 0;
        }

        $conditions = array ('username' => $username);
        $user = $DB->get_record('user', $conditions);

        if (!$user) {
            return 0;
        }

        $conditions = array ('enrolid' => $enrol->id, 'userid' => $user->id);
        $ue = $DB->get_record('user_enrolments', $conditions);

        if ($ue) {

            // User already enroled
            // Can be suspended, or maybe enrol time passed
            // Just activate enrolment and set new dates
            $file = '/tmp/mdl.log';
            $current = print_r($ue, TRUE);
             // Write the contents back to the file
            file_put_contents($file, $current);

            $ue->status = 0; // Active.
            $ue->timestart = $timestart;
            $ue->timeend = $timeend;
            $ue->timemodified = time();
            $DB->update_record('user_enrolments', $ue);
            return 1;
        }

        $plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend);

        return 1;
    }

    // Unenrol user totally.
    function unenrol_user ($username, $courseid) {

        global $CFG, $DB;

        $username = utf8_decode ($username);
        $username = strtolower ($username);

        $conditions = array ('courseid' => $courseid, 'enrol' => 'manual');
        $enrol = $DB->get_record('enrol', $conditions);

        if (!$enrol) {
            return 0;
        }

        $conditions = array ('username' => $username);
        $user = $DB->get_record('user', $conditions);

        if (!$user) {
            return 0;
        }

        $conditions = array ('enrolid' => $enrol->id, 'userid' => $user->id);
        $ue = $DB->get_record('user_enrolments', $conditions);

        if (!$ue) {
            return 0;
        }

        $instance = $DB->get_record('enrol', array('id' => $ue->enrolid), '*', MUST_EXIST);

        $plugin = enrol_get_plugin($instance->enrol);

        $plugin->unenrol_user($instance, $ue->userid);

        return 1;
    }

    function create_user ($username, $firstname, $lastname, $email, $auth) {

        global $CFG, $DB;

        // Error if username, email or auth not set.
        if (empty($username) || empty($email) || empty($auth)) {
            return 0;
        }

        //$username = core_text::strtolower($username);
        $username = strtolower($username);

        $conditions = array ('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id);

        // Get a single database record as an object where all the given conditions met.
        $user = $DB->get_record('user', $conditions);

        if (!$user) {
  
            // At this stage we cannot use user_create_user() as it does not allow spaces in username.
            // So this is code taken from create_user_record() and user_create_user().
            $newuser = new stdClass();
            
            $newuser->username = $username;
            if (!empty($firstname)) {
                 $newuser->firstname = $firstname;
            }
            if (!empty($lastname)) {
                 $newuser->lastname = $lastname;
            }
            $newuser->email = $email;
            $newuser->auth = $auth;
            $newuser->timecreated = time();
            $newuser->timemodified = $newuser->timecreated;
            $newuser->mnethostid = $CFG->mnet_localhost_id;
            $newuser->lang = $CFG->lang;
            $newuser->city = '';
            $newuser->confirmed = 1;

            $newuser = (object) $newuser;

            // Insert the user into the database.
            $newuserid = $DB->insert_record('user', $newuser);

            if (empty($newuserid)) {
                return 0;
            }

        } else {

            $update = false;

            if ($firstname !== $user->firstname) {
                $user->firstname = $firstname;
                $update = true;
            }
            if ($lastname !== $user->lastname) {
                $user->lastname = $lastname;
                $update = true;
            }
            if ($email !== $user->email) {
                $user->email = $email;
                $update = true;
            }
            if ($auth !== $user->auth) {
                $user->auth = $auth;
                $update = true;
            }

            if ($update) { 
                $result = $DB->update_record('user', $user);
                if (empty($result)) {
                    return 0;
                }
            }
        }
        // All good return success.
        return 1;
        
    }

    function delete_user ($username) {
        global $DB;

        $username = utf8_decode ($username);
        $username = strtolower ($username);
        $conditions = array("username" => $username);
        $user = $DB->get_record("user", $conditions);

        if ($user) {
            delete_user ($user);
            return 1;
        }
        return 0;
    }

    function test_connection () {
        return 1;
    }

    function add_group_member ($username, $courseid, $groupname) {
        global $CFG, $DB;

        $username = utf8_decode ($username);
        $username = strtolower ($username);
        /* Check that user exists */
        $conditions = array ('username' => $username);
        $user = $DB->get_record('user', $conditions);
        if (!$user) {
            return 0;
        }

        /* Check that course exists */
        $user = $DB->get_record('user', $conditions);
        $conditions = array ('id' => $courseid);
        $course = $DB->get_record('course', $conditions);

        if (!$course) {
            return 0;
        }

        /* check if group exists */
        $conditions = array ('name' => $groupname, 'courseid' => $course->id);
        $group = $DB->get_record ('groups', $conditions);

        /* create group if not exist */
        if (!$group) {
            // Create group if it does not exist.

            $data->courseid = $course->id;
            $data->name = $groupname;

            groups_create_group ($data);
        }

        $conditions = array ('name' => $groupname, 'courseid' => $course->id);
        $group = $DB->get_record ('groups', $conditions);

        groups_add_member ($group->id, $user->id);

        return 1;

    }
    
    function get_cohorts ()
    {
        global $CFG, $DB;

        $query = "SELECT id, name, description
          FROM {$CFG->prefix}cohort";

        $cohorts = $DB->get_records_sql($query);

        $rdo = array ();
        foreach ($cohorts as $cohort)
        {
            $c['id'] = $cohort->id;
            $c['name'] = $cohort->name;

            $rdo[] = $c;
        }

        return $rdo;
    }

    function add_cohort ($name)
    {
        global $CFG, $DB;

        $context = context_system::instance();

        $cohort = new stdClass();
        $cohort->name = $name;
        $cohort->contextid = $context->id;
        $cohort->component = 'local_droodle';

        $cohort_id = cohort_add_cohort ($cohort);

        return $cohort_id;
    }

    function delete_cohort ($id)
    {
        global $CFG, $DB;

        $context = context_system::instance();

        $cohort = new stdClass();
        $cohort->id = $id;
        $cohort->contextid = $context->id;

        cohort_delete_cohort ($cohort);

        return 1;
    }


    function update_cohort ($id, $name)
    {
        global $CFG, $DB;

        $context = context_system::instance();

        $cohort = new stdClass();
        $cohort->id = $id;
        $cohort->name = $name;
        $cohort->contextid = $context->id;

        cohort_update_cohort ($cohort);

        return 1; 
    }



    function add_cohort_member ($username, $cohort_id)
    {
        global $CFG, $DB;

        $username = utf8_decode ($username);
        $username = strtolower ($username);
        $conditions = array ('username' => $username);
        $user = $DB->get_record('user',$conditions);

        if (!$user)
            return 0;

	$conditions = array ('userid' => $user->id, 'cohortid' => $cohort_id);
        $member = $DB->get_record('cohort_members',$conditions);

        if ($member)
            return 1;


        cohort_add_member ($cohort_id, $user->id);

        return 1;
    }

    function remove_cohort_member ($username, $cohort_id)
    {
        global $CFG, $DB;

        $username = utf8_decode ($username);
        $username = strtolower ($username);
        $conditions = array ('username' => $username);
        $user = $DB->get_record('user',$conditions);

        if (!$user)
            return 0;

        $conditions = array ('userid' => $user->id, 'cohortid' => $cohort_id);
        $member = $DB->get_record('cohort_members',$conditions);

        if (!$member)
            return 1;

        cohort_remove_member ($cohort_id, $user->id);

        return 1;
    }
} // End Class.
