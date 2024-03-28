<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\ValidateDiffNodeTests;

use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\Support\Types\MyCustomScalarString;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class ValidateDiffNodeTests extends TestCaseDatabase
{
    use SqlAssertionTrait;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'query' => [
                UsersQuery::class,
            ],
        ]);

        $app['config']->set('graphql.types', [
            UserType::class,
            FilterInput::class,
            EpisodeEnum::class,
            PostType::class,
            MyCustomScalarString::class,
        ]);
    }

    public function testDiffValueNodeAndNestedValueNodeArgs(): void
    {
        /** @var User[] $users */
        $users = User::factory()->count(2)
            ->create()
            ->each(function (User $user): void {
                Post::factory()
                    ->create([
                        'user_id' => $user->id,
                    ]);

                Post::factory()
                    ->create([
                        'user_id' => $user->id,
                    ]);
            });

        $graphql = <<<'GRAQPHQL'
{
  users(id: 1, name: "john", price: 1.2, status: true, flag: null, author: NEWHOPE, post: {id: 1, body: "body"}, keywords: ["key1", "key2", "key3"], customType: "hello world") {
    id
    name
    posts(id: 2, name: "tom", price: 1.3, status: false, flag: null, author: EMPIRE, post: {id: 2, body: "body2"}, keywords: ["key4", "key5", "key6"], customType: "custom string") {
      id
      body
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($graphql);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id", "users"."name" from "users";
select "posts"."id", "posts"."body", "posts"."user_id" from "posts" where "posts"."user_id" in (?, ?) order by "posts"."id" asc;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => (string) $users[0]->id,
                        'name' => $users[0]->name,
                        'posts' => [
                            [
                                'body' => $users[0]->posts[0]->body,
                                'id' => (string) $users[0]->posts[0]->id,
                            ],
                            [
                                'body' => $users[0]->posts[1]->body,
                                'id' => (string) $users[0]->posts[1]->id,
                            ],
                        ],
                    ],
                    [
                        'id' => (string) $users[1]->id,
                        'name' => $users[1]->name,
                        'posts' => [
                            [
                                'body' => $users[1]->posts[0]->body,
                                'id' => (string) $users[1]->posts[0]->id,
                            ],
                            [
                                'body' => $users[1]->posts[1]->body,
                                'id' => (string) $users[1]->posts[1]->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedResult, $result);
    }
}
