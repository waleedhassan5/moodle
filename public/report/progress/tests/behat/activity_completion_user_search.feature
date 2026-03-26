@report @report_progress
Feature: Teacher can search for users in the activity completion report
  In order to find learners more easily in large course reports
  As a teacher
  I need to search for users by email and name in the activity completion report

  Background:
    Given the following "course" exists:
      | fullname         | Course 1 |
      | shortname        | C1       |
      | format           | topics   |
      | enablecompletion | 1        |
      | groupmode        | 1        |
      | initsections     | 1        |
    And the following "activities" exist:
      | activity | name          | intro   | course | idnumber | section | completion | completionview |
      | quiz     | My quiz B     | A3 desc | C1     | quizb    | 0       | 2          | 1              |
      | quiz     | My quiz A     | A3 desc | C1     | quiza    | 1       | 2          | 1              |
      | page     | My page       | A4 desc | C1     | page1    | 2       | 2          | 1              |
      | assign   | My assignment | A1 desc | C1     | assign1  | 2       | 2          | 1              |
    And the following "users" exist:
      | username | firstname | lastname | email                      |
      | teacher1 | Teacher   | One      | teacher1@example.com       |
      | student1 | Maria     | Dawson   | maria.dawson@example.com   |
      | student2 | Michael   | Dunn     | michael.dunn@example.com   |
      | student3 | John      | Smith    | john.smith@example.com     |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
      | student3 | G2    |
      | teacher1 | G1    |
      | teacher1 | G2    |

  @javascript
  Scenario: Teacher can search for a user by email address
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    And I should see "Maria Dawson" in the "completion-progress" "table"
    And I should see "Michael Dunn" in the "completion-progress" "table"
    And I should see "John Smith" in the "completion-progress" "table"
    When I set the field "Search" to "maria.dawson@example.com"
    And I press "Search"
    Then I should see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "Michael Dunn" in the "completion-progress" "table"
    And I should not see "John Smith" in the "completion-progress" "table"

  @javascript
  Scenario: Teacher can search for a user by first name
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    When I set the field "Search" to "Michael"
    And I press "Search"
    Then I should see "Michael Dunn" in the "completion-progress" "table"
    And I should not see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "John Smith" in the "completion-progress" "table"

  @javascript
  Scenario: Teacher can search for a user by last name
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    When I set the field "Search" to "Smith"
    And I press "Search"
    Then I should see "John Smith" in the "completion-progress" "table"
    And I should not see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "Michael Dunn" in the "completion-progress" "table"

  @javascript
  Scenario: Teacher can clear a user search in the activity completion report
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    When I set the field "Search" to "Dawson"
    And I press "Search"
    Then I should see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "Michael Dunn" in the "completion-progress" "table"
    And I should not see "John Smith" in the "completion-progress" "table"
    And I click on "Clear" "link"
    And I should see "Maria Dawson" in the "completion-progress" "table"
    And I should see "Michael Dunn" in the "completion-progress" "table"
    And I should see "John Smith" in the "completion-progress" "table"

  @javascript
  Scenario: Teacher can search for a user while group filtering is applied
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    And I set the field "Separate groups" to "Group 1"
    And I should see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "Michael Dunn" in the "completion-progress" "table"
    And I should not see "John Smith" in the "completion-progress" "table"
    When I set the field "Search" to "maria.dawson@example.com"
    And I press "Search"
    Then I should see "Maria Dawson" in the "completion-progress" "table"
    And I should not see "Michael Dunn" in the "completion-progress" "table"
    And I should not see "John Smith" in the "completion-progress" "table"
