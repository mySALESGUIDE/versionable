<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mockery as m;
    use Mpociot\Couchbase\Eloquent\Model;
    use Mpociot\Versionable\Version;

    class VersionableTest extends VersionableTestCase
{

    public function setUp()
    {
        parent::setUp();

        TestVersionableUser::flushEventListeners();
        TestVersionableUser::boot();

        TestVersionableSoftDeleteUser::flushEventListeners();
        TestVersionableSoftDeleteUser::boot();

        TestPartialVersionableUser::flushEventListeners();
        TestPartialVersionableUser::boot();
    }

    public function tearDown()
    {
        m::close();
        Auth::clearResolvedInstances();
    }

    public function testVersionableRelation()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertInstanceOf( TestVersionableUser::class, $version->versionable );
    }

    public function testInitialSaveShouldCreateVersion()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount(1, $user->versions );
    }

    public function testRetrievePreviousVersionFails()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount(1, $user->versions );
        $this->assertNull( $user->previousVersion() );
    }

    public function testRetrievePreviousVersionExists()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        // Needed because otherwise timestamps are exactly the same
        sleep(1);

        $user->name = "John";
        $user->save();

        $this->assertCount(2, $user->versions );
        $this->assertNotNull( $user->previousVersion() );

        $this->assertEquals( "Marcel", $user->previousVersion()->getModel()->name );
    }

    public function testVersionAndModelAreEqual()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertEquals( $user->attributesToArray(), $version->getModel()->attributesToArray() );
    }


    public function testVersionsAreRelatedToUsers()
    {
        $user_id = rand(1,100);

        Auth::shouldReceive('check')
            ->andReturn( true );

        Auth::shouldReceive('id')
            ->andReturn( $user_id );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();

        $this->assertEquals( $user_id, $version->user_id );
    }

    public function testGetResponsibleUserAttribute()
    {
        $responsibleOrigUser = new TestVersionableUser();
        $responsibleOrigUser->name = "Marcel";
        $responsibleOrigUser->email = "m.pociot@test.php";
        $responsibleOrigUser->password = "12345";
        $responsibleOrigUser->last_login = $responsibleOrigUser->freshTimestamp();
        $responsibleOrigUser->save();


        Auth::login($responsibleOrigUser);

        // Needed because otherwise timestamps are exactly the same
        sleep(1);

        $user = new TestVersionableUser();
        $user->name = "John";
        $user->email = "j.tester@test.php";
        $user->password = "67890";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();

        $responsibleUser = $version->responsible_user;
        $this->assertEquals( $responsibleUser->getKey(), $responsibleOrigUser->getKey() );
        $this->assertEquals( $responsibleUser->name, $responsibleOrigUser->name );
        $this->assertEquals( $responsibleUser->email, $responsibleOrigUser->email );
    }


    public function testDontVersionEveryAttribute()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestPartialVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();


        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1, $user->versions );
    }

    public function testVersionEveryAttribute()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 2, $user->versions );
    }

    public function testCheckForVersioningEnabled()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->disableVersioning();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 0, $user->versions()->get() );

        $user->enableVersioning();
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1, $user->versions()->get() );
    }


    public function testCheckForVersioningEnabledLaterOn()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        $user->disableVersioning();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1, $user->versions );
    }

    public function testCanRevertVersion()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user_id = $user->getKey();

        $user->name = "John";
        $user->save();

        $newUser = TestVersionableUser::find( $user_id );
        $this->assertEquals( "John", $newUser->name );

        // Fetch first version and revert ist
        $newUser->versions()->first()->revert();

        $newUser = TestVersionableUser::find( $user_id );
        $this->assertEquals( "Marcel", $newUser->name );
    }

    public function testCanRevertSoftDeleteVersion()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableSoftDeleteUser();

        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user_id = $user->getKey();

        $user->name = "John";
        $user->save();

        $newUser = TestVersionableSoftDeleteUser::find( $user_id );
        $this->assertEquals( "John", $newUser->name );

        // Fetch first version and revert ist
        $reverted = $newUser->versions()->first()->revert();

        $newUser = TestVersionableSoftDeleteUser::find( $user_id );
        $this->assertEquals( "Marcel", $reverted->name );
        $this->assertEquals( "Marcel", $newUser->name );
    }

    /**
     * @group testGetVersionModel
     */
    public function testGetVersionModel()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        // Create 3 versions
        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        sleep(2);

        $user->name = "John";
        $user->save();

        sleep(1);

        $user->name = "Michael";
        $user->save();

        $versions = $user->versions()->orderBy(Version::CREATED_AT, 'ASC')->get();

        $this->assertCount( 3, $versions );

        $this->assertEquals( "Marcel", $user->getVersionModel( $versions[0]->getKey() )->name );
        $this->assertEquals( "John", $user->getVersionModel( $versions[1]->getKey() )->name );
        $this->assertEquals( "Michael", $user->getVersionModel( $versions[2]->getKey() )->name );
        $this->assertEquals( null, $user->getVersionModel( 4 ) );

    }

    public function testUseReasonAttribute()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        // Create 3 versions
        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->reason = "Doing tests";
        $user->save();

        $this->assertEquals( "Doing tests", $user->currentVersion()->reason );
    }

    public function testIgnoreDeleteTimestamp()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertCount( 1 , $user->versions );
        $user_id = $user->getKey();
        $this->assertNull( $user->deleted_at );

        $user->delete();

        $this->assertNotNull( $user->deleted_at );

        $this->assertCount( 1 , $user->versions );
    }

    public function testDiffTwoVersions()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep(1);

        $user->name = "John";
        $user->save();

        $diff = $user->previousVersion()->diff();
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }

    public function testDiffIgnoresTimestamps()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep(1);

        $user->name = "John";
        $user->created_at = Carbon::now();
        $user->updated_at = Carbon::now();
        $user->deleted_at = Carbon::now();
        $user->save();

        $diff = $user->previousVersion()->diff();
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }

    public function testDiffSpecificVersions()
    {
        Auth::shouldReceive('check')
            ->andReturn( false );

        // Create 3 versions
        $user = new TestVersionableSoftDeleteUser();
        $user->name = "Marcel";
        $user->email = "m.pociot@test.php";
        $user->password = "12345";
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep(1);

        $user->name = "John";
        $user->email = "john@snow.com";
        $user->save();
        sleep(1);

        $user->name = "Julia";
        $user->save();

        $diff = $user->currentVersion()->diff( $user->versions()->orderBy("version_id","ASC")->first() );
        $this->assertTrue( is_array($diff) );

        $this->assertCount(2, $diff);
        $this->assertEquals( "Marcel", $diff["name"] );
        $this->assertEquals( "m.pociot@test.php", $diff["email"] );


        $diff = $user->currentVersion()->diff( $user->versions()->orderBy("version_id","ASC")->offset(1)->first() );
        $this->assertTrue( is_array($diff) );

        $this->assertCount(1, $diff);
        $this->assertEquals( "John", $diff["name"] );
    }


}




class TestVersionableUser extends Model implements \Illuminate\Contracts\Auth\Authenticatable{
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return "_id";
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        // TODO: Implement getAuthPassword() method.
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        // TODO: Implement getRememberToken() method.
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     * @return void
     */
    public function setRememberToken( $value )
    {
        // TODO: Implement setRememberToken() method.
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        // TODO: Implement getRememberTokenName() method.
    }
}

class TestVersionableSoftDeleteUser extends Model {
    use \Mpociot\Versionable\VersionableTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = "users";
}


class TestPartialVersionableUser extends Model {
    use \Mpociot\Versionable\VersionableTrait;

    protected $table = "users";

    protected $dontVersionFields = ["last_login"];
}