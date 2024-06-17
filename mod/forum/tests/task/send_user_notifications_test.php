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

namespace mod_forum\task;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

/**
 * The module forums tests
 *
 * @package    mod_forum
 * @copyright   2024 Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class send_user_notifications_test extends \advanced_testcase {
    /**
     * Testcase to check send notification for post via email
     *
     * @covers \mod_forum\task\send_user_notifications
     * @dataProvider send_user_notifications_cases
     * @param array $userdata Test user for the case.
     * @param string $expectedstring Expected string during the test case.
     * @param int $expecteddebuggingcount Expected debugging count.
     * @param array $expecteddebuggingstrings Expected debugging strings array.
     * @param bool $expectedassertion Expected adhoc task to be re queued or not.
     * @param array $userpreferences (optional) User preferences for the test case.
     * @throws InvalidArgumentException If the user data is invalid.
     * @throws RuntimeException If the notification fails to send.
     * @throws Exception For any other general errors.
     */
    public function test_send_user_notifications_with_empty_email(
        array $userdata,
        string $expectedstring,
        int $expecteddebuggingcount,
        array $expecteddebuggingstrings,
        bool $expectedassertion,
        array $userpreferences = []
    ): void {
        global $DB, $CFG;
        $CFG->handlebounces = true;
        $this->resetAfterTest(true);
        $this->preventResetByRollback();
        $this->redirectEmails();

        // Creating a user.
        $user = $this->getDataGenerator()->create_user($userdata);
        // Set user preferences.
        foreach ($userpreferences as $name => $value) {
            set_user_preference($name, $value, $user);
        }

        // Create a course and a forum.
        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'forcesubscribe' => \FORUM_FORCESUBSCRIBE,
        ]);

        // Create a discussion in the forum.
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $user->id,
            'message' => 'Test discussion',
        ]);
        // Create a post in the discussion.
        $post = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post([
            'course' => $course->id,
            'discussion' => $discussion->id,
            'userid' => $user->id,
            'message' => 'Test post',
        ]);

        // Setting placeholders for user id and post id.
        $expectedstring = sprintf($expectedstring, $user->id, $post->id, $user->id);
        foreach ($expecteddebuggingstrings as $index => $value) {
            $expecteddebuggingstrings[$index] = sprintf($value, $user->id, $user->firstname . " " . $user->lastname);
        }

        // Enroll the user in the course.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Trigger the send_user_notifications task.
        $task = new send_user_notifications();
        $task->set_userid($user->id);
        $task->set_custom_data($post->id);
        $this->expectOutputString($expectedstring);

        // Testing if an exception is thrown because the task is re queued if an exception is thrown in the adhoc task.
        $requeued = false;
        try {
            $task->execute();
        } catch (\Exception $ex) {
            $requeued = true;
        }
        if ($expecteddebuggingcount) {
            $this->assertdebuggingcalledcount($expecteddebuggingcount, $expecteddebuggingstrings);
        }

        $this->assertEquals($expectedassertion, $requeued, $expectedstring);
    }
    /**
     * Data provider for test cases related to sending user notifications.
     *
     * This data provider generates various test cases for the `test_send_user_notifications_with_empty_email` function.
     * Each test case consists of a user configuration, expected output strings, debugging counts, and assertions.
     *
     * @return array[] Array of test cases.
     */
    public static function send_user_notifications_cases(): array {

        return [
            [
                // Create a user with an empty email address.
                [
                    'email' => '',
                    'username' => 'testuser',
                ],
                "Sending messages to testuser (%d)\n" .
                    "  Failed to send post %d\n" .
                    "Sent 0 messages with 1 failures\n" .
                    "Failed to send emails for the user with id %d" .
                    " because of empty email address, Skipping re queuing of the task\n",
                2,
                [
                    "Can not send email to user without email: %d",
                    "Error calling message processor email",
                ],
                false,
            ],
            [
                // Create a user with bounce threshold.
                [
                    'email' => 'bounce@example.com',
                    'username' => 'bounceuser',
                ],
                "Sending messages to bounceuser (%d)\n" .
                    "  Failed to send post %d\n" .
                    "Sent 0 messages with 1 failures\n",
                2,
                [
                    "email_to_user: User %d (%s) is over bounce threshold! Not sending.",
                    "Error calling message processor email",
                ],
                true,
                [
                    'email_bounce_count' => 20,
                    'email_send_count' => 20,
                ],
            ],
        ];
    }
}
