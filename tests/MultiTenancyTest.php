<?php

require_once 'DbTestCase.php';

/**
 * Class MultiTenancyTest
 */
class MultiTenancyTest extends DbTestCase
{
    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * MultiTenancyTest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->faker = Faker\Factory::create();
    }

    public function setUp()
    {
        parent::setUp();

        $this->migrate(__DIR__ . '/resources/migrations');

        if ( !defined('TENANT_SLUG') )
            define('TENANT_SLUG', 'foo');

        $this->app[ 'config' ]->set('auth.guard', 'tenant');
        $this->app[ 'config' ]->set('multi-tenancy.role.class', \Testing\Role::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.class', \Testing\Tenant::class);
        $this->app[ 'config' ]->set('multi-tenancy.ignore-roles', []);
        $this->app[ 'config' ]->set('multi-tenancy.should-apply-default', true);
        $this->app[ 'config' ]->set('multi-tenancy.should-apply-callback', null);
    }

    public function test_that_i_can_only_see_users_that_belong_to_the_same_tenant_as_me()
    {
        $tenant1 = \Testing\Tenant::create([
            'name' => 'foo',
            'slug' => 'foo'
        ]);

        $tenant2 = \Testing\Tenant::create([
            'name' => 'bar',
            'slug' => 'bar'
        ]);

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        $expected = collect();
        $notExpected = collect();

        $expected->push($user);
        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($newUser = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->attach($tenant1->id);
        }

        for ( $i = 0; $i < 10; $i++ )
        {
            $notExpected->push($newUser = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->sync([ $tenant2->id ]);
        }

        auth()->login($user);

        $actual = \Testing\User::all();

        $this->assertEquals(11, $actual->count());

        foreach ( $actual as $key => $user )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $user->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $user->toArray())));
        }
    }

    public function test_i_can_only_see_drivers_on_the_same_tenant_as_me()
    {
        $tenant1 = \Testing\Tenant::create([
            'name' => 'foo',
            'slug' => 'foo'
        ]);

        $tenant2 = \Testing\Tenant::create([
            'name' => 'bar',
            'slug' => 'bar'
        ]);

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        for ( $i = 0; $i < 10; $i++ )
        {
            $user = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]);

            $user->tenants()
                 ->sync([ $tenant1->id ]);

            $expected->push(\Testing\Driver::create([
                'user_id'        => $user->id,
                'license_number' => $this->faker->ean13
            ]));
        }

        for ( $i = 0; $i < 10; $i++ )
        {
            $user = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]);

            $user->tenants()
                 ->sync([ $tenant2->id ]);

            $notExpected->push(\Testing\Driver::create([
                'user_id'        => $user->id,
                'license_number' => $this->faker->ean13
            ]));
        }

        $actual = \Testing\Driver::all();

        $this->assertEquals(10, $actual->count());

        foreach ( $actual as $key => $driver )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $driver->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $driver->toArray())));
        }
    }

    public function test_i_can_only_see_addresses_on_the_same_tenant_as_me()
    {
        $tenant1 = \Testing\Tenant::create([
            'name' => 'foo',
            'slug' => 'foo'
        ]);

        $tenant2 = \Testing\Tenant::create([
            'name' => 'bar',
            'slug' => 'bar'
        ]);

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        // addresses can belong to drivers, user, or locations

        // address attached to driver of this tenant
        // 1
        $expected->push($address = \Testing\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant1->id ]);

        $driver = \Testing\Driver::create([
            'user_id'        => $user->id,
            'license_number' => $this->faker->ean13
        ]);

        $driver->addresses()
               ->attach($address->id);

        // address attached to driver of another tenant
        // 2
        $notExpected->push($address = \Testing\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant2->id ]);

        $driver = \Testing\Driver::create([
            'user_id'        => $user->id,
            'license_number' => $this->faker->ean13
        ]);

        $driver->addresses()
               ->attach($address->id);

        // address attached to location of this tenant
        // 3
        $expected->push($address = \Testing\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $location = \Testing\Location::create([
            'tenant_id' => $tenant1->id,
            'name'      => $this->faker->city
        ]);

        $location->addresses()
                 ->attach($address->id);

        // address attached to location of another tenant
        // 4
        $notExpected->push($address = \Testing\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $location = \Testing\Location::create([
            'tenant_id' => $tenant2->id,
            'name'      => $this->faker->city
        ]);

        $location->addresses()
                 ->attach($address->id);

        // address attached to user of this tenant
        // 5
        $expected->push($address = \Testing\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant1->id ]);

        $user->addresses()
             ->attach($address->id);

        // address attached to driver of another tenant
        // 6
        $notExpected->push($address = \Testing\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant2->id ]);

        $user->addresses()
             ->attach($address->id);

        $actual = Testing\Address::all();

        $this->assertEquals(3, $actual->count());

        foreach ( $actual as $key => $driver )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $driver->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $driver->toArray())));
        }
    }

    public function test_i_can_get_only_locations_with_the_same_tenant_as_me()
    {
        $tenant1 = \Testing\Tenant::create([
            'name' => 'foo',
            'slug' => 'foo'
        ]);

        $tenant2 = \Testing\Tenant::create([
            'name' => 'bar',
            'slug' => 'bar'
        ]);

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push(\Testing\Location::create([
                'tenant_id' => $tenant1->id,
                'name'      => $this->faker->city
            ]));

            $notExpected->push(\Testing\Location::create([
                'tenant_id' => $tenant2->id,
                'name'      => $this->faker->city
            ]));
        }

        $actual = Testing\Location::all();

        $this->assertEquals(10, $actual->count());

        foreach ( $actual as $key => $location )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $location->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $location->toArray())));
        }
    }

    public function test_i_can_only_see_messages_that_belong_to_the_same_tenant_as_me()
    {
        $tenant1 = \Testing\Tenant::create([
            'name' => 'foo',
            'slug' => 'foo'
        ]);

        $tenant2 = \Testing\Tenant::create([
            'name' => 'bar',
            'slug' => 'bar'
        ]);

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($expectedMessage = \Testing\Message::create([
                'text' => implode(' ', $this->faker->words()),
            ]));

            $expectedMessage->tenants()
                            ->sync([ $tenant1->id ]);

            $notExpected->push($notExpectedMessage = \Testing\Message::create([
                'text' => implode(' ', $this->faker->words()),
            ]));

            $notExpectedMessage->tenants()
                               ->sync([ $tenant2->id ]);
        }

        $actual = Testing\Message::all();

        $this->assertEquals(10, $actual->count());

        foreach ( $actual as $key => $message )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $message->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $message->toArray())));
        }
    }

    public function test_i_can_turn_off_the_multi_tenancy_logic()
    {
        $this->app[ 'config' ]->set('multi-tenancy.should-apply-default', false);

        $tenant1 = \Testing\Tenant::create([
            'name' => 'foo',
            'slug' => 'foo'
        ]);

        $tenant2 = \Testing\Tenant::create([
            'name' => 'bar',
            'slug' => 'bar'
        ]);

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        $expected = collect();

        $expected->push($user);
        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($newUser = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->sync([ $tenant1->id ]);
        }

        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($newUser = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->sync([ $tenant2->id ]);
        }

        auth()->login($user);

        $actual = \Testing\User::all();

        $this->assertEquals(21, $actual->count());

        foreach ( $actual as $key => $user )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $user->toArray());
        }
    }

    public function test_i_can_use_a_custom_callback_to_determine_whether_the_multi_tenancy_logic_should_be_applied()
    {
        $this->app[ 'config' ]->set('multi-tenancy.should-apply-default', true);

        $this->app[ 'config' ]->set('multi-tenancy.should-apply-callback', [ $this, 'custom_callback' ]);

        $tenant1 = \Testing\Tenant::create([
            'name' => 'foo',
            'slug' => 'foo'
        ]);

        $tenant2 = \Testing\Tenant::create([
            'name' => 'bar',
            'slug' => 'bar'
        ]);

        $user = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        $expected = collect();
//        $notExpected = collect();

        $expected->push($user);
        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($newUser = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->attach($tenant1->id);
        }

        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($newUser = \Testing\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->sync([ $tenant2->id ]);
        }

        auth()->login($user);

        $actual = \Testing\User::all();

        $this->assertEquals(21, $actual->count());

        foreach ( $actual as $key => $user )
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $user->toArray());
    }

    function custom_callback( $user, $roles = [] )
    {
        return false;
    }

    public function test_i_can_apply_multi_tenancy_logic_depending_on_roles()
    {
        $this->app[ 'config' ]->set('multi-tenancy.ignore-roles', [ 'admin' ]);

        $admin = \Testing\Role::create([ 'name' => 'admin' ]);

        $client = \Testing\Role::create([ 'name' => 'client' ]);

        $tenant1 = \Testing\Tenant::create([ 'name' => 'foo', 'slug' => 'foo' ]);

        $tenant2 = \Testing\Tenant::create([ 'name' => 'bar', 'slug' => 'bar' ]);

        $userAdmin = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $userClient = \Testing\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $userAdmin->tenants()
                  ->sync([ $tenant1->id ]);

        $userClient->tenants()
                   ->sync([ $tenant1->id ]);

        $userAdmin->roles()
                  ->attach($admin->id);

        $userClient->roles()
                   ->attach($client->id);

        $expected = collect();

        $notExpected = collect();

        for ( $i = 0; $i < 10; $i++ )
            $expected->push(\Testing\Location::create([ 'tenant_id' => $tenant1->id, 'name' => $this->faker->city ]));

        for ( $i = 0; $i < 10; $i++ )
            $notExpected->push(\Testing\Location::create([ 'tenant_id' => $tenant2->id, 'name' => $this->faker->city ]));

        auth()->login($userAdmin);

        $actual = \Testing\Location::all();

        $this->assertEquals(20, $actual->count());

        $combined = collect($expected->merge($notExpected));

        foreach ( $actual as $key => $location )
            $this->assertArraySubset($combined->get($key)
                                              ->toArray(), $location->toArray());

        auth()->logout();

        auth()->login($userClient);

        $actual = \Testing\Location::all();

        $this->assertEquals(10, $actual->count());

        foreach ( $actual as $key => $location )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $location->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $location->toArray())));
        }
    }

    /**
     * CUSTOM TENANT SET UP
     *
     **/

    public function test_that_i_can_only_see_users_that_belong_to_the_same_tenant_as_me_with_custom_tenant_setup()
    {
        $this->app[ 'config' ]->set('multi-tenancy.role.class', \Testing\CustomTenant\Role::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.class', \Testing\CustomTenant\Company::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.name', 'company');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.table', 'companies');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.polymorph-term', 'companyable');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.foreign_key', 'company_id');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.slug_column_name', 'foo_slug');

        $this->migrate(__DIR__ . '/resources/CustomTenantSetUp/migrations');

        $tenant1 = \Testing\CustomTenant\Company::create([
            'name' => 'foo',
            'foo_slug' => 'foo'
        ]);

        $tenant2 = \Testing\CustomTenant\Company::create([
            'name' => 'bar',
            'foo_slug' => 'bar'
        ]);

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        $expected = collect();
        $notExpected = collect();

        $expected->push($user);
        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($newUser = \Testing\CustomTenant\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->attach($tenant1->id);
        }

        for ( $i = 0; $i < 10; $i++ )
        {
            $notExpected->push($newUser = \Testing\CustomTenant\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]));

            $newUser->tenants()
                    ->sync([ $tenant2->id ]);
        }

        auth()->login($user);

        $actual = \Testing\CustomTenant\User::all();

        $this->assertEquals(11, $actual->count());

        foreach ( $actual as $key => $user )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $user->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $user->toArray())));
        }
    }

    public function test_i_can_only_see_drivers_on_the_same_tenant_as_me_with_custom_tenant_setup()
    {
        $this->app[ 'config' ]->set('multi-tenancy.role.class', \Testing\CustomTenant\Role::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.class', \Testing\CustomTenant\Company::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.name', 'company');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.table', 'companies');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.polymorph-term', 'companyable');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.foreign_key', 'company_id');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.slug_column_name', 'foo_slug');

        $this->migrate(__DIR__ . '/resources/CustomTenantSetUp/migrations');

        $tenant1 = \Testing\CustomTenant\Company::create([
            'name' => 'foo',
            'foo_slug' => 'foo'
        ]);

        $tenant2 = \Testing\CustomTenant\Company::create([
            'name' => 'bar',
            'foo_slug' => 'bar'
        ]);

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        for ( $i = 0; $i < 10; $i++ )
        {
            $user = \Testing\CustomTenant\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]);

            $user->tenants()
                 ->sync([ $tenant1->id ]);

            $expected->push(\Testing\CustomTenant\Driver::create([
                'user_id'        => $user->id,
                'license_number' => $this->faker->ean13
            ]));
        }

        for ( $i = 0; $i < 10; $i++ )
        {
            $user = \Testing\CustomTenant\User::create([
                'first_name' => $this->faker->firstName,
                'last_name'  => $this->faker->lastName,
                'email'      => $this->faker->email,
                'password'   => $this->faker->password
            ]);

            $user->tenants()
                 ->sync([ $tenant2->id ]);

            $notExpected->push(\Testing\CustomTenant\Driver::create([
                'user_id'        => $user->id,
                'license_number' => $this->faker->ean13
            ]));
        }

        $actual = \Testing\CustomTenant\Driver::all();

        $this->assertEquals(10, $actual->count());

        foreach ( $actual as $key => $driver )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $driver->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $driver->toArray())));
        }
    }

    public function test_i_can_only_see_addresses_on_the_same_tenant_as_me_with_custom_tenant_setup()
    {
        $this->app[ 'config' ]->set('multi-tenancy.role.class', \Testing\CustomTenant\Role::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.class', \Testing\CustomTenant\Company::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.name', 'company');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.table', 'companies');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.polymorph-term', 'companyable');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.foreign_key', 'company_id');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.slug_column_name', 'foo_slug');

        $this->migrate(__DIR__ . '/resources/CustomTenantSetUp/migrations');

        $tenant1 = \Testing\CustomTenant\Company::create([
            'name' => 'foo',
            'foo_slug' => 'foo'
        ]);

        $tenant2 = \Testing\CustomTenant\Company::create([
            'name' => 'bar',
            'foo_slug' => 'bar'
        ]);

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        // addresses can belong to drivers, user, or locations

        // address attached to driver of this tenant
        // 1
        $expected->push($address = \Testing\CustomTenant\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant1->id ]);

        $driver = \Testing\CustomTenant\Driver::create([
            'user_id'        => $user->id,
            'license_number' => $this->faker->ean13
        ]);

        $driver->addresses()
               ->attach($address->id);

        // address attached to driver of another tenant
        // 2
        $notExpected->push($address = \Testing\CustomTenant\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant2->id ]);

        $driver = \Testing\CustomTenant\Driver::create([
            'user_id'        => $user->id,
            'license_number' => $this->faker->ean13
        ]);

        $driver->addresses()
               ->attach($address->id);

        // address attached to location of this tenant
        // 3
        $expected->push($address = \Testing\CustomTenant\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $location = \Testing\CustomTenant\Location::create([
            'company_id' => $tenant1->id,
            'name'      => $this->faker->city
        ]);

        $location->addresses()
                 ->attach($address->id);

        // address attached to location of another tenant
        // 4
        $notExpected->push($address = \Testing\CustomTenant\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $location = \Testing\CustomTenant\Location::create([
            'company_id' => $tenant2->id,
            'name'      => $this->faker->city
        ]);

        $location->addresses()
                 ->attach($address->id);

        // address attached to user of this tenant
        // 5
        $expected->push($address = \Testing\CustomTenant\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant1->id ]);

        $user->addresses()
             ->attach($address->id);

        // address attached to driver of another tenant
        // 6
        $notExpected->push($address = \Testing\CustomTenant\Address::create([
            'house_number' => $this->faker->numberBetween(0, 50),
            'street'       => $this->faker->streetName,
            'line_1'       => '',
            'line_2'       => '',
            'city'         => $this->faker->city,
            'county'       => $this->faker->citySuffix,
            'postcode'     => $this->faker->postcode,
            'country'      => $this->faker->country
        ]));

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->sync([ $tenant2->id ]);

        $user->addresses()
             ->attach($address->id);

        $actual = Testing\CustomTenant\Address::all();

        $this->assertEquals(3, $actual->count());

        foreach ( $actual as $key => $driver )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $driver->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $driver->toArray())));
        }
    }

    public function test_i_can_get_only_locations_with_the_same_tenant_as_me_with_custom_tenant_setup()
    {
        $this->app[ 'config' ]->set('multi-tenancy.role.class', \Testing\CustomTenant\Role::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.class', \Testing\CustomTenant\Company::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.name', 'company');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.table', 'companies');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.polymorph-term', 'companyable');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.foreign_key', 'company_id');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.slug_column_name', 'foo_slug');

        $this->migrate(__DIR__ . '/resources/CustomTenantSetUp/migrations');

        $tenant1 = \Testing\CustomTenant\Company::create([
            'name' => 'foo',
            'foo_slug' => 'foo'
        ]);

        $tenant2 = \Testing\CustomTenant\Company::create([
            'name' => 'bar',
            'foo_slug' => 'bar'
        ]);

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push(\Testing\CustomTenant\Location::create([
                'company_id' => $tenant1->id,
                'name'      => $this->faker->city
            ]));

            $notExpected->push(\Testing\CustomTenant\Location::create([
                'company_id' => $tenant2->id,
                'name'      => $this->faker->city
            ]));
        }

        $actual = Testing\CustomTenant\Location::all();

        $this->assertEquals(10, $actual->count());

        foreach ( $actual as $key => $location )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $location->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $location->toArray())));
        }
    }

    public function test_i_can_only_see_messages_that_belong_to_the_same_tenant_as_me_with_custom_tenant_setup()
    {
        $this->app[ 'config' ]->set('multi-tenancy.role.class', \Testing\CustomTenant\Role::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.class', \Testing\CustomTenant\Company::class);
        $this->app[ 'config' ]->set('multi-tenancy.relationship.name', 'company');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.table', 'companies');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.polymorph-term', 'companyable');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.foreign_key', 'company_id');
        $this->app[ 'config' ]->set('multi-tenancy.relationship.slug_column_name', 'foo_slug');

        $this->migrate(__DIR__ . '/resources/CustomTenantSetUp/migrations');

        $tenant1 = \Testing\CustomTenant\Company::create([
            'name' => 'foo',
            'foo_slug' => 'foo'
        ]);

        $tenant2 = \Testing\CustomTenant\Company::create([
            'name' => 'bar',
            'foo_slug' => 'bar'
        ]);

        $user = \Testing\CustomTenant\User::create([
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $this->faker->email,
            'password'   => $this->faker->password
        ]);

        $user->tenants()
             ->attach($tenant1->id);

        auth()->login($user);

        $expected = collect();

        $notExpected = collect();

        for ( $i = 0; $i < 10; $i++ )
        {
            $expected->push($expectedMessage = \Testing\CustomTenant\Message::create([
                'text' => implode(' ', $this->faker->words()),
            ]));

            $expectedMessage->tenants()
                            ->sync([ $tenant1->id ]);

            $notExpected->push($notExpectedMessage = \Testing\CustomTenant\Message::create([
                'text' => implode(' ', $this->faker->words()),
            ]));

            $notExpectedMessage->tenants()
                               ->sync([ $tenant2->id ]);
        }

        $actual = Testing\CustomTenant\Message::all();

        $this->assertEquals(10, $actual->count());

        foreach ( $actual as $key => $message )
        {
            $this->assertArraySubset($expected->get($key)
                                              ->toArray(), $message->toArray());

            foreach ( $notExpected as $i => $item )
                $this->assertNotEquals(0, sizeof(array_diff_assoc($item->toArray(), $message->toArray())));
        }
    }
}