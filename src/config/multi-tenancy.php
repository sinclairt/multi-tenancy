<?php

return [
    'ignore-roles'          => [ 'super-admin' ],
    'relationship'          => [
        'name'             => 'tenant',
        'table'            => 'tenants',
        'class'            => \App\Models\Tenant::class,
        'polymorph-term'   => 'tenantable',
        'foreign_key'      => 'tenant_id',
        'slug_column_name' => 'slug'
    ],
    'should-apply-callback' => null,
    'should-apply-default'  => true,
    'role'                  => [
        'class' => \App\Models\Role::class
    ]
];