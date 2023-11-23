<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'articles';

    public static function addContent(int $projectId, string $articleName, int $webSiteId, string $rubric, string $marking): ContentVideo
    {
        $newContent = new ContentVideo();
        $newContent->setAttribute('project_id', $projectId);
        $newContent->setAttribute('name', $articleName);
        $newContent->setAttribute('rubric', $rubric);
        $newContent->setAttribute('created_at', new \DateTimeImmutable());
        $newContent->setAttribute('updated_at', new \DateTimeImmutable());
        $newContent->setAttribute('rubric', $webSiteId);
        $newContent->setAttribute('marking', $marking);
        $newContent->setAttribute('status_id', 1);
        $newContent->save();
        return $newContent;
    }

    public static function changeStatus(int $article_id, int $statusId): void
    {
        self::query()
            ->where([['id', '=', $article_id]])
            ->update(['status_id' => $statusId]);
    }
}