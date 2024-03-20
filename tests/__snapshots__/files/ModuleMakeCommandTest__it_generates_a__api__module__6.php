<?php

namespace Modules\ArticleComment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Actions\CreatePermissionAction;
use Modules\Authorization\DTO\CreatePermissionDTO;

class ArticleCommentPermissionsSeeder_1 extends Seeder
{
    public function run(): void
    {
        $createPermissionAction = CreatePermissionAction::make();

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'view-article-comment',
            display_name: 'View any "article-comments"',
            group: 'article-comments'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'create-article-comment',
            display_name: 'Create "article-comments"',
            group: 'article-comments'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'update-article-comment',
            display_name: 'Update any "article-comments"',
            group: 'article-comments'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'delete-article-comment',
            display_name: 'Delete any "article-comments"',
            group: 'article-comments'
        ));

        $createPermissionAction->handle(new CreatePermissionDTO(
            name: 'force-delete-article-comment',
            display_name: 'Force delete any "article-comments"',
            group: 'article-comments'
        ));
    }
}
