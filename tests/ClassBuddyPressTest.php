<?php

namespace SLCA\BuddyPress;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use wpCloud\StatelessMedia\WPStatelessStub;

/**
 * Class ClassBuddyPressTest
 */

class ClassBuddyPressTest extends TestCase {
  const TEST_URL = 'https://test.test';
  const UPLOADS_URL = self::TEST_URL . '/uploads';
  const PLUGINS_URL = self::TEST_URL . '/plugins';
  const AVATAR_FILE = 'avatars/avatar.png';
  const AVATAR_SRC_URL = self::UPLOADS_URL . '/' . self::AVATAR_FILE;
  const AVATAR_DST_URL = WPStatelessStub::TEST_GS_HOST . '/' . self::AVATAR_FILE;
  const TEST_BP_DATA = [
    'item_id'   => 15,
    'object'    => 'user',
  ];
  const TEST_UPLOAD_DIR = [
    'baseurl' => ClassBuddyPressTest::UPLOADS_URL,
    'basedir' => '/var/www/uploads'
  ];

  // Adds Mockery expectations to the PHPUnit assertions count.
  use MockeryPHPUnitIntegration;

  public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

    // WP mocks
    Functions\when('plugins_url')->justReturn( self::PLUGINS_URL );
    Functions\when('wp_get_upload_dir')->justReturn( self::TEST_UPLOAD_DIR );
        
    // WP_Stateless mocks
    Filters\expectApplied('wp_stateless_file_name')
      ->andReturn( self::AVATAR_FILE );

    Filters\expectApplied('wp_stateless_handle_root_dir')
      ->andReturn( 'uploads' );

    Functions\when('ud_get_stateless_media')->justReturn( WPStatelessStub::instance() );

    // BuddyPress mocks
    Functions\when('bp_core_fetch_avatar')->justReturn( self::AVATAR_SRC_URL );
    Functions\when('bp_attachments_get_attachment')->justReturn( self::AVATAR_SRC_URL );

  }
	
  public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

  public function testShouldInitHooks() {
    $budypress = new BuddyPress();

    $budypress->module_init([]);

    self::assertNotFalse( has_action('xprofile_avatar_uploaded', [ $budypress, 'avatar_uploaded' ]) );
    self::assertNotFalse( has_action('groups_avatar_uploaded', [ $budypress, 'avatar_uploaded' ]) );

    self::assertNotFalse( has_filter('bp_core_avatar_folder_url', [ $budypress, 'bp_core_avatar_folder_url' ]) );
    self::assertNotFalse( has_filter('bp_core_avatar_folder_dir', [ $budypress, 'bp_core_avatar_folder_dir' ]) );
    self::assertNotFalse( has_filter('bp_core_pre_delete_existing_avatar', [ $budypress, 'delete_existing_avatar' ]) );
    self::assertNotFalse( has_filter('sm:sync::syncArgs', [ $budypress, 'sync_args' ]) );
    self::assertNotFalse( has_filter('bp_attachments_pre_get_attachment', [ $budypress, 'bp_attachments_pre_get_attachment' ]) );
    self::assertNotFalse( has_filter('stateless_skip_cache_busting', [ $budypress, 'skip_cache_busting' ]) );
  }

  public function testShouldCountHooks() {
    $budypress = new BuddyPress();

    Functions\expect('add_action')->times(2);
    Functions\expect('add_filter')->times(6);

    $budypress->module_init([]);
  }

  public function testShouldSyncAvatar() {
    $budypress = new BuddyPress();
 
    Actions\expectDone('sm:sync::syncFile')->times(2);

    $budypress->avatar_uploaded(15, 'crop', self::TEST_BP_DATA);
  }
  
  public function testShouldDeleteAvatar() {
    $budypress = new BuddyPress();

    Actions\expectDone('sm:sync::deleteFile')->times(2);

    $budypress->delete_existing_avatar(self::AVATAR_SRC_URL, self::TEST_BP_DATA);
  }

  public function testShouldPreGetAttachment() {
    $budypress = new BuddyPress();
    Actions\expectDone('sm:sync::syncFile')->once();
    $this->assertEquals(
      self::AVATAR_DST_URL,
      $budypress->bp_attachments_pre_get_attachment(self::AVATAR_DST_URL, self::TEST_BP_DATA),
    );
  }
  
  public function testShouldUpdateArgs() {
    $budypress = new BuddyPress();

    $args = $budypress->sync_args([], self::AVATAR_FILE, '', false);

    self::assertTrue( isset( $args['source'] ) );
    self::assertTrue( isset( $args['source_version'] ) );
    self::assertEquals( 'BuddyPress', $args['source'] );
    self::assertFalse( isset( $args['name_with_root'] ) );
  }

  public function testShouldUpdateArgsStateless() {
    $budypress = new BuddyPress();

    ud_get_stateless_media()->set('sm.mode', 'stateless');

    $args = $budypress->sync_args([], self::AVATAR_FILE, '', false);

    self::assertTrue( isset( $args['source'] ) );
    self::assertTrue( isset( $args['source_version'] ) );
    self::assertEquals( 'BuddyPress', $args['source'] );
    self::assertTrue( isset( $args['name_with_root'] ) );
  }

  public function testShouldNotUpdateArgs() {
    $budypress = new BuddyPress();

    self::assertEquals(
      0,
      count( $budypress->sync_args([], self::TEST_URL, '', false) )
    );
  }
}

function debug_backtrace($a) {
  return [
    '3' => [
      'args' => [ 'url' ],
    ],
  ];
}
