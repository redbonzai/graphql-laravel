<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\PrimaryKeyTests;

use Rebing\GraphQL\Tests\Support\Models\Comment;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class PaginationTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                PrimaryKeyQuery::class,
                PrimaryKeyPaginationQuery::class,
                PrimaryKeyInterfacePaginationQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            ModelInterfaceType::class,
            CommentType::class,
            PostType::class,
        ]);
    }

    public function testPagination(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'post 1',
        ]);
        Comment::factory()->create([
            'title' => 'post 1 comment 1',
            'post_id' => $post->id,
        ]);
        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'post 2',
        ]);
        Comment::factory()->create([
            'title' => 'post 2 comment 1',
            'post_id' => $post->id,
        ]);

        $query = <<<'GRAQPHQL'
{
  primaryKeyPaginationQuery {
    current_page
    data {
      title
      comments {
        title
      }
    }
    from
    has_more_pages
    last_page
    per_page
    to
    total
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select count(*) as aggregate from "posts";
select "posts"."title", "posts"."id" from "posts" limit 1 offset 0;
select "comments"."title", "comments"."post_id", "comments"."id" from "comments" where "comments"."post_id" in (?) order by "comments"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'primaryKeyPaginationQuery' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'title' => 'post 1',
                            'comments' => [
                                [
                                    'title' => 'post 1 comment 1',
                                ],
                            ],
                        ],
                    ],
                    'from' => 1,
                    'has_more_pages' => true,
                    'last_page' => 2,
                    'per_page' => 1,
                    'to' => 1,
                    'total' => 2,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }

    public function testInterfacePagination(): void
    {
        Post::factory(2)->create();

        $query = <<<'GRAQPHQL'
{
  primaryKeyInterfacePaginationQuery {
    current_page
    data {
      id
    }
    from
    has_more_pages
    last_page
    per_page
    to
    total
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
            select count(*) as aggregate from "posts";
            select * from "posts" limit 1 offset 0;
            SQL
        );

        $expectedResult = [
            'data' => [
                'primaryKeyInterfacePaginationQuery' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'id' => '1',
                        ],
                    ],
                    'from' => 1,
                    'has_more_pages' => true,
                    'last_page' => 2,
                    'per_page' => 1,
                    'to' => 1,
                    'total' => 2,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
