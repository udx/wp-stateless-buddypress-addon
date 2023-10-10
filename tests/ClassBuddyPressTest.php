<?php

namespace WPSL\BuddyPress;

use PHPUnit\Framework\TestCase;
use wpCloud\StatelessMedia\WPStatelessStub;
use WPSL\BuddyPress\BuddyPress;

/**
 * Class ClassBuddyPressTest
 */

class ClassBuddyPressTest extends TestCase {

  public static $functions;

  public function setUp(): void {
    self::$functions = $this->createPartialMock(
      ClassBuddyPressTest::class,
      ['add_filter', 'apply_filters', 'add_action', 'do_action']
    );

    $this::$functions->method('apply_filters')->will($this->returnArgument(1));
  }

  public function testShouldInitModule() {
    self::$functions->expects($this->exactly(3))
      ->method('add_filter')
      ->withConsecutive(
        ['bp_core_fetch_avatar'], 
        ['bp_core_pre_delete_existing_avatar'], 
        ['bp_attachments_pre_get_attachment'],
      );

    self::$functions->expects($this->exactly(2))
      ->method('add_action')
      ->withConsecutive(
        ['xprofile_avatar_uploaded'], 
        ['groups_avatar_uploaded'],
      );

    $budypress = new BuddyPress();
    $budypress->module_init([]);
  }

/*   public function testShouldFetchAvatar() {
    $budypress = new BuddyPress();

    $this->assertEquals(
      'https://test.test/buddypress/test', 
      $budypress->bp_core_fetch_avatar(' <img src="https://test.test/uploads/avatars/avatar.png"/>')
    );
  }
 */
  public function add_filter() {
  }

  public function apply_filters() {
  }

  public function add_action() {
  } 

  public function do_action($a, ...$b) {
  }
}

function add_filter($a, $b, $c = 10, $d = 1) {
  return ClassBuddyPressTest::$functions->add_filter($a, $b, $c, $d);
}

function apply_filters($a, $b) {
  return ClassBuddyPressTest::$functions->apply_filters($a, $b);
}

function add_action($a, $b, $c = 10, $d = 1) {
  return ClassBuddyPressTest::$functions->add_action($a, $b, $c, $d);
}

function do_action($a, ...$b) {
  return ClassBuddyPressTest::$functions->do_action($a, ...$b);
}

function wp_get_upload_dir() {
  return [
    'baseurl' => 'https://test.test/uploads',
    'basedir' => '/var/www/uploads'
  ];
}

function ud_get_stateless_media() {
  return WPStatelessStub::instance();
}
