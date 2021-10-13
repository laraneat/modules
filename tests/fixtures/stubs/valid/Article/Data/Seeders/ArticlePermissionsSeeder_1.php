<?php

namespace App\Modules\Article\Data\Seeders;

use App\Modules\Authorization\Actions\CreatePermissionAction;
use App\Ship\Abstracts\Seeders\Seeder;

class ArticlePermissionsSeeder_1 extends Seeder
{
    public function run(): void
    {
        $createPermissionAction = CreatePermissionAction::make();
        $createPermissionAction->handle(
            name: 'view-article',
            displayName: 'View any articles',
            group: 'articles'
        );
        $createPermissionAction->handle(
            name: 'create-article',
            displayName: 'Create articles',
            group: 'articles'
        );
        $createPermissionAction->handle(
            name: 'update-article',
            displayName: 'Update any articles',
            group: 'articles'
        );
        $createPermissionAction->handle(
            name: 'delete-article',
            displayName: 'Delete any articles',
            group: 'articles'
        );
        $createPermissionAction->handle(
            name: 'force-delete-article',
            displayName: 'Force delete any articles',
            group: 'articles'
        );
    }
}