<?php

namespace App\Support;

final class ApplicationPermissions
{
    public const ALL = [
        'dashboard.view',

        'documents.view',
        'documents.create',
        'documents.update',
        'documents.delete',
        'documents.download',
        'documents.annotate',
        'documents.stamp',
        'documents.manage-routing',
        'documents.start-routing',
        'documents.cancel-routing',
        'documents.sign',
        'documents.request-revision',

        'users.view',
        'users.create',
        'users.update',
        'users.delete',

        'categories.view',
        'categories.create',
        'categories.update',
        'categories.delete',

        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',

        'permissions.view',
        'permissions.update',
    ];

    private function __construct() {}
}
