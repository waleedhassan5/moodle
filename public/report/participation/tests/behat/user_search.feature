@report @report_participation
Feature: Teacher can search for users in the course participation report
  In order to find learners more easily in large course reports
  As a teacher
  I need to search for users by email in the course participation report

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                    |
      | teacher1 | Teacher   | 1        | teacher1@example.com     |
      | student1 | Maria     | Dawson   | maria.dawson@example.com |
      | student2 | Michael   | Dunn     | michael.dunn@example.com |
      | student3 | John      | Smith    | john.smith@example.com   |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "activity" exists:
      | course   | C1             |
      | activity | book           |
      | name     | Test book name |
      | idnumber | book1          |
    And I am on the "Test book name" "book activity" page logged in as student1
    And I am on the "Test book name" "book activity" page logged in as student2
    And I am on the "Test book name" "book activity" page logged in as student3

  @javascript
  Scenario: Teacher can search for a user by email address in the course participation report
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "Reports" in current page administration
    And I click on "Course participation" "link"
    And I set the field "instanceid" to "Test book name"
    And I set the field "roleid" to "Student"
    And I press "Go"
    And I should see "Maria Dawson" in the "region-main" "region"
    And I should see "Michael Dunn" in the "region-main" "region"
    And I should see "John Smith" in the "region-main" "region"
    When I set the field "Search" to "maria.dawson@example.com"
    And I press "Search"
    Then I should see "Maria Dawson" in the "region-main" "region"
    And I should not see "Michael Dunn" in the "region-main" "region"
    And I should not see "John Smith" in the "region-main" "region"

  @javascript
  Scenario: Teacher can clear a user search in the course participation report
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "Reports" in current page administration
    And I click on "Course participation" "link"
    And I set the field "instanceid" to "Test book name"
    And I set the field "roleid" to "Student"
    And I press "Go"
    When I set the field "Search" to "michael.dunn@example.com"
    And I press "Search"
    Then I should see "Michael Dunn" in the "region-main" "region"
    And I should not see "Maria Dawson" in the "region-main" "region"
    And I should not see "John Smith" in the "region-main" "region"
    When I click on "Clear" "link"
    Then I should see "Maria Dawson" in the "region-main" "region"
    And I should see "Michael Dunn" in the "region-main" "region"
    And I should see "John Smith" in the "region-main" "region"
