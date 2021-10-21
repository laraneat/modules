<?php

namespace App\Modules\Article\Data\Seeders;

use App\Modules\Authorization\Actions\CreatePermissionAction;
use App\Modules\Authorization\DTO\CreatePermissionDTO;
use App\Ship\Abstracts\Seeders\Seeder;

class ArticlePermissionsSeeder_1 extends Seeder
{
    public function run(): void
    {
        $createPermissionAction = CreatePermissionAction::make();

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'view-article',
            display_name: 'View any articles',
            group: 'articles'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'create-article',
            display_name: 'Create articles',
            group: 'articles'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'update-article',
            display_name: 'Update any articles',
            group: 'articles'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'delete-article',
            display_name: 'Delete any articles',
            group: 'articles'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'force-delete-article',
            display_name: 'Force delete any articles',
            group: 'articles'
        ));
    }
}
