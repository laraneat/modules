<?php

namespace Modules\Author\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Actions\CreatePermissionAction;
use Modules\Authorization\DTO\CreatePermissionDTO;

class AuthorPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $createPermissionAction = CreatePermissionAction::make();

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'view-author',
            display_name: 'View any "authors"',
            group: 'authors'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'create-author',
            display_name: 'Create "authors"',
            group: 'authors'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'update-author',
            display_name: 'Update any "authors"',
            group: 'authors'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'delete-author',
            display_name: 'Delete any "authors"',
            group: 'authors'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'force-delete-author',
            display_name: 'Force delete any "authors"',
            group: 'authors'
        ));
    }
}
