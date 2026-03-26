@report @report_completion
Feature: Teacher can search for users in the course completion report
  In order to find learners more easily in large course reports
  As a teacher
  I need to search for users by email in the course completion report

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                    |
      | teacher1 | Teacher   | One      | teacher1@example.com     |
      | student1 | Maria     | Dawson   | maria.dawson@example.com |
      | student2 | Michael   | Dunn     | michael.dunn@example.com |
      | student3 | John      | Smith    | john.smith@example.com   |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |

  @javascript
  Scenario: Teacher can search for a user by email address in the course completion report
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_criteria_self | 1 |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    And I should see "Maria Dawson" in the "completion-progress" "table"
    And I should see "Michael Dunn" in the "completion-progress" "table"
    And I should see "John Smith" in the "completion-progress" "table"
    When I set the field "Search" to "maria.dawson@example.com"
    And I press "Search"
    Then I should see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "Michael Dunn" in the "completion-progress" "table"
    And I should not see "John Smith" in the "completion-progress" "table"

  @javascript
  Scenario: Teacher can clear a user search in the course completion report
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_criteria_self | 1 |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    When I set the field "Search" to "michael.dunn@example.com"
    And I press "Search"
    Then I should see "Michael Dunn" in the "completion-progress" "table"
    And I should not see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "John Smith" in the "completion-progress" "table"
    When I click on "Clear" "link"
    Then I should see "Maria Dawson" in the "completion-progress" "table"
    And I should see "Michael Dunn" in the "completion-progress" "table"
    And I should see "John Smith" in the "completion-progress" "table"
