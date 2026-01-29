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

namespace qtype_calculated;

use qtype_calculated;
use qtype_numerical;
use question_bank;
use question_possible_response;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/calculated/questiontype.php');
require_once($CFG->dirroot . '/question/type/calculated/tests/helper.php');

/**
 * Unit tests for question/type/calculated/questiontype.php.
 *
 * @package    qtype_calculated
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \question_type
 * @covers \qtype_calculated
 */
final class question_type_test extends \advanced_testcase {
    protected $tolerance = 0.00000001;
    protected $qtype;

    protected function setUp(): void {
        parent::setUp();
        $this->qtype = new qtype_calculated();
    }

    protected function tearDown(): void {
        $this->qtype = null;
        parent::tearDown();
    }
    /**
     * Creates one dataset definition, links it to the question, and inserts one dataset item.
     *
     * @param int $questionid The question to attach the dataset to.
     */
    private function add_one_dataset_item(int $questionid): void {
        global $DB;

        // Dataset definition.
        $datasetdef = (object)[
            'type' => 1,
            'name' => 'a',
            'category' => 0,
            'options' => 'uniform:1.0:10.0:1',
            'itemcount' => 1,
        ];
        $datasetdef->id = $DB->insert_record('question_dataset_definitions', $datasetdef);

        // Link to question.
        $DB->insert_record('question_datasets', (object)[
            'question' => $questionid,
            'datasetdefinition' => $datasetdef->id,
        ]);

        // One item.
        $DB->insert_record('question_dataset_items', (object)[
            'definition' => $datasetdef->id,
            'itemnumber' => 1,
            'value' => '3.14',
        ]);
    }

    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'calculated');
    }

    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    public function test_get_random_guess_score(): void {
        $q = \test_question_maker::get_question_data('calculated');
        $q->options->answers[17]->fraction = 0.1;
        $this->assertEquals(0.1, $this->qtype->get_random_guess_score($q));
    }

    public function test_load_question(): void {
        $this->resetAfterTest();

        $syscontext = \context_system::instance();
        /** @var core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('calculated');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = new \stdClass();
        $question->category = $category->id;
        $question->qtype = 'calculated';
        $question->createdby = 0;

        $this->qtype->save_question($question, $fromform);
        $questiondata = question_bank::load_question_data($question->id);

        $this->assertEquals(['id', 'category', 'parent', 'name', 'questiontext', 'questiontextformat',
                'generalfeedback', 'generalfeedbackformat', 'defaultmark', 'penalty', 'qtype',
                'length', 'stamp', 'timecreated', 'timemodified', 'createdby', 'modifiedby', 'idnumber', 'contextid',
                'status', 'versionid', 'version', 'questionbankentryid', 'categoryobject', 'options', 'hints'],
                array_keys(get_object_vars($questiondata)));
        $this->assertEquals($category->id, $questiondata->category);
        $this->assertEquals(0, $questiondata->parent);
        $this->assertEquals($fromform->name, $questiondata->name);
        $this->assertEquals($fromform->questiontext, $questiondata->questiontext);
        $this->assertEquals($fromform->questiontextformat, $questiondata->questiontextformat);
        $this->assertEquals('', $questiondata->generalfeedback);
        $this->assertEquals(0, $questiondata->generalfeedbackformat);
        $this->assertEquals($fromform->defaultmark, $questiondata->defaultmark);
        $this->assertEquals(0, $questiondata->penalty);
        $this->assertEquals('calculated', $questiondata->qtype);
        $this->assertEquals(1, $questiondata->length);
        $this->assertEquals(\core_question\local\bank\question_version_status::QUESTION_STATUS_READY, $questiondata->status);
        $this->assertEquals($question->createdby, $questiondata->createdby);
        $this->assertEquals($question->createdby, $questiondata->modifiedby);
        $this->assertEquals('', $questiondata->idnumber);
        $this->assertEquals($category->contextid, $questiondata->contextid);
        $this->assertCount(1, $questiondata->hints);
        $hint = array_pop($questiondata->hints);
        $this->assertEquals('Add', $hint->hint);
        $this->assertEquals(FORMAT_HTML, $hint->hintformat);

        // Options.
        $this->assertEquals($questiondata->id, $questiondata->options->question);
        $this->assertCount(1, $questiondata->options->units);
        $unit = array_pop($questiondata->options->units);
        $this->assertEquals($unit->unit, 'x');
        $this->assertEquals($unit->multiplier, '1.0');
        $this->assertEquals(qtype_numerical::UNITOPTIONAL, $questiondata->options->showunits);
        $this->assertEquals(0, $questiondata->options->unitgradingtype); // Unit role is none, so this is 0.
        $this->assertEquals($fromform->unitpenalty, $questiondata->options->unitpenalty);
        $this->assertEquals($fromform->unitsleft, $questiondata->options->unitsleft);

        // Build the expected answer base.
        $answerbase = [
            'question' => $questiondata->id,
            'answerformat' => 0,
        ];
        $expectedanswers = [];
        foreach ($fromform->answer as $key => $value) {
            $answer = $answerbase + [
                'answer' => $fromform->answer[$key],
                'fraction' => (float)$fromform->fraction[$key],
                'tolerance' => $fromform->tolerance[$key],
                'tolerancetype' => $fromform->tolerancetype[$key],
                'correctanswerlength' => $fromform->correctanswerlength[$key],
                'correctanswerformat' => $fromform->correctanswerformat[$key],
                'feedback' => $fromform->feedback[$key]['text'],
                'feedbackformat' => $fromform->feedback[$key]['format'],
            ];
            $expectedanswers[] = (object)$answer;
        }
        // Need to get rid of ids.
        $gotanswers = array_map(function($answer) {
                unset($answer->id);
                return $answer;
        }, $questiondata->options->answers);
        // Compare answers.
        $this->assertEquals($expectedanswers, array_values($gotanswers));
    }

    protected function get_possible_response($ans, $tolerance, $type) {
        $a = new \stdClass();
        $a->answer = $ans;
        $a->tolerance = $tolerance;
        $a->tolerancetype = get_string($type, 'qtype_numerical');
        return get_string('answerwithtolerance', 'qtype_calculated', $a);
    }

    public function test_get_possible_responses(): void {
        $q = \test_question_maker::get_question_data('calculated');

        $this->assertEquals(array(
            $q->id => array(
                13 => new question_possible_response(
                        $this->get_possible_response('{a} + {b}', 0.001, 'nominal'), 1.0),
                14 => new question_possible_response(
                        $this->get_possible_response('{a} - {b}', 0.001, 'nominal'), 0.0),
                17 => new question_possible_response('*', 0.0),
                null => question_possible_response::no_response()
            ),
        ), $this->qtype->get_possible_responses($q));
    }

    public function test_get_possible_responses_no_star(): void {
        $q = \test_question_maker::get_question_data('calculated');
        unset($q->options->answers[17]);

        $this->assertEquals(array(
            $q->id => array(
                13 => new question_possible_response(
                        $this->get_possible_response('{a} + {b}', 0.001, 'nominal'), 1),
                14 => new question_possible_response(
                        $this->get_possible_response('{a} - {b}', 0.001, 'nominal'), 0),
                0  => new question_possible_response(
                        get_string('didnotmatchanyanswer', 'question'), 0),
                null => question_possible_response::no_response()
            ),
        ), $this->qtype->get_possible_responses($q));
    }

    public function test_get_short_question_name(): void {
        $this->resetAfterTest();

        // Enable multilang filter to on content and heading.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', 1);
        $filtermanager = \filter_manager::instance();
        $filtermanager->reset_caches();

        $context = \context_system::instance();

        $longmultilangquestionname = "<span lang=\"en\" class=\"multilang\">Lorem ipsum dolor sit amet, consetetur sadipscing elitr</span><span lang=\"fr\" class=\"multilang\">Lorem ipsum dolor sit amet, consetetur sadipscing elitr</span>";
        $shortmultilangquestionname = "<span lang=\"en\" class=\"multilang\">Lorem ipsum</span><span lang=\"fr\" class=\"multilang\">Lorem ipsum</span>";
        $longquestionname = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr";
        $shortquestionname = "Lorem ipsum";
        $this->assertEquals("Lorem ipsum dolor...", $this->qtype->get_short_question_name($longmultilangquestionname, 20));
        $this->assertEquals("Lorem ipsum", $this->qtype->get_short_question_name($shortmultilangquestionname, 20));
        $this->assertEquals("Lorem ipsum dolor...", $this->qtype->get_short_question_name($longquestionname, 20));
        $this->assertEquals("Lorem ipsum", $this->qtype->get_short_question_name($shortquestionname, 20));
    }

    public function test_placehodler_regex(): void {
        preg_match_all(qtype_calculated::PLACEHODLER_REGEX, '= {={a} + {b}}', $matches);
        $this->assertEquals([['{a}', '{b}'], ['a', 'b']], $matches);
    }

    public function test_formulas_in_text_regex(): void {
        preg_match_all(qtype_calculated::FORMULAS_IN_TEXT_REGEX, '= {={a} + {b}}', $matches);
        $this->assertEquals([['{={a} + {b}}'], ['{a} + {b}']], $matches);
    }

    public function test_find_dataset_names(): void {
        $this->assertEquals([], $this->qtype->find_dataset_names('Frog.'));

        $this->assertEquals(['a' => 'a', 'b' => 'b'],
                $this->qtype->find_dataset_names('= {={a} + {b}}'));

        $this->assertEquals(['a' => 'a', 'b' => 'b'],
                $this->qtype->find_dataset_names('What is {a} plus {b}? (Hint, it is not {={a}*{b}}.)'));

        $this->assertEquals(['a' => 'a', 'b' => 'b', 'c' => 'c'],
                $this->qtype->find_dataset_names('
                        <p>If called with $a = {a} and $b = {b}, what does this PHP function return?</p>
                        <pre>
                        /**
                         * What does this do?
                         */
                        function mystery($a, $b) {
                            return {c}*$a + $b;
                        }
                        </pre>
                        '));
    }

    public function test_calculate_answer_nan_inf(): void {
        $answer = qtype_calculated_calculate_answer('acos(1.1)', [], 0.1, 1, 2, 2);
        $this->assertIsObject($answer);
        $this->assertNan($answer->answer);

        $answer = qtype_calculated_calculate_answer('log(0.0)', [], 0.1, 1, 2, 2);
        $this->assertIsObject($answer);
        $this->assertInfinite($answer->answer); // Actually -INF.

        // Dividing by zero is hard to test, so get +INF another way.
        $answer = qtype_calculated_calculate_answer('abs(log(0.0))', [], 0.1, 1, 2, 2);
        $this->assertIsObject($answer);
        $this->assertInfinite($answer->answer);
    }

    /**
     * When not editing, loading an incomplete calculated question throws a friendly exception.
     */
    public function test_missing_datasets_throws_friendly_exception_when_not_editing(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $syscontext = \context_system::instance();
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('calculated');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = (object)[
            'category' => $category->id,
            'qtype' => 'calculated',
            'createdby' => 0,
        ];
        $this->qtype->save_question($question, $fromform);

        $PAGE->set_pagetype('mod-quiz-startattempt');

        try {
            question_bank::load_question($question->id);
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('missingdatasetswithlink', $e->errorcode);
            $this->assertEquals('qtype_calculated', $e->module);
        }
    }

    /**
     * When editing, loading an incomplete calculated question does not throw.
     */
    public function test_missing_datasets_does_not_throw_when_editing_request(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $syscontext = \context_system::instance();
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('calculated');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = (object)[
            'category' => $category->id,
            'qtype' => 'calculated',
            'createdby' => 0,
        ];
        $this->qtype->save_question($question, $fromform);

        $cache = \cache::make('qtype_calculated', 'editingrequest');
        $cache->set('editing', true);

        $qdef = question_bank::load_question($question->id);
        $this->assertInstanceOf(\question_definition::class, $qdef);
        $cache->delete('editing');
    }

    /**
     * Saving the datasetitems step with status READY throws if setup is still incomplete.
     */
    public function test_save_question_datasetitems_ready_throws_when_setup_missing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $syscontext = \context_system::instance();
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('calculated');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = (object)[
            'category' => $category->id,
            'qtype' => 'calculated',
            'createdby' => 0,
        ];
        $this->qtype->save_question($question, $fromform);

        // Force wizardnow=datasetitems path (save_question reads it via optional_param).
        $_POST['wizardnow'] = 'datasetitems';

        $form = (object)[
            'id' => $question->id,
            'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
            // Minimal payload for datasetitems step; should not create dataset items.
            'definition' => [],
            'number' => [],
            'itemid' => [],
        ];

        $this->expectException(\moodle_exception::class);
        $this->qtype->save_question($question, $form);
    }

    /**
     * Saving the datasetitems step can set the status to READY once dataset items exist.
     */
    public function test_save_question_datasetitems_saves_ready_when_setup_complete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $syscontext = \context_system::instance();
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('calculated');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = (object)[
            'category' => $category->id,
            'qtype' => 'calculated',
            'createdby' => 0,
        ];
        $this->qtype->save_question($question, $fromform);

        // Make the question "set up" by inserting one dataset item.
        $this->add_one_dataset_item((int)$question->id);

        $_POST['wizardnow'] = 'datasetitems';

        $form = (object)[
            'id' => $question->id,
            'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
            // Minimal payload; dataset items already exist so setup is complete.
            'definition' => [],
            'number' => [],
            'itemid' => [],
        ];

        $this->qtype->save_question($question, $form);

        $version = $DB->get_record('question_versions', ['questionid' => $question->id], 'status', MUST_EXIST);
        $this->assertEquals(\core_question\local\bank\question_version_status::QUESTION_STATUS_READY, $version->status);
    }

    /**
     * Saving the datasetitems step can keep the status as DRAFT even when dataset items exist.
     */
    public function test_save_question_datasetitems_saves_draft_when_selected(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $syscontext = \context_system::instance();
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('calculated');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = (object)[
            'category' => $category->id,
            'qtype' => 'calculated',
            'createdby' => 0,
        ];
        $this->qtype->save_question($question, $fromform);

        $this->add_one_dataset_item((int)$question->id);

        $_POST['wizardnow'] = 'datasetitems';

        $form = (object)[
            'id' => $question->id,
            'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_DRAFT,
            'definition' => [],
            'number' => [],
            'itemid' => [],
        ];

        $this->qtype->save_question($question, $form);

        $version = $DB->get_record('question_versions', ['questionid' => $question->id], 'status', MUST_EXIST);
        $this->assertEquals(\core_question\local\bank\question_version_status::QUESTION_STATUS_DRAFT, $version->status);
    }

}
