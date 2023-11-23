<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'articles';

    public static function addContent(int $projectId, string $articleName, int $webSiteId, string $rubric, string $marking): Article
    {
        $newContent = new Article();
        $newContent->setAttribute('project_id', $projectId);
        $newContent->setAttribute('name', $articleName);
        $newContent->setAttribute('rubric', $rubric);
        $newContent->setAttribute('created_at', new \DateTimeImmutable());
        $newContent->setAttribute('updated_at', new \DateTimeImmutable());
        $newContent->setAttribute('website_id', $webSiteId);
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

    public static function findAllById(int $articleId)
    {
        return self::query()
            ->select(
                'articles.id AS articles_id',
                'articles.name',
                'articles.rubric',
                'articles.marking',
                'articles.text',
                'articles.status_id',
                'articles.project_id',
                'articles.website_id',
                'websites.domen',
                'websites.user_name',
                'websites.password_app',
            )
            ->leftJoin('websites', 'articles.', '=', 'websites.id')
            ->where('articles.id', '=', $articleId)
            ->get()->toArray()[0];
    }
}