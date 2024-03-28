<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Database\SelectFields\MorphRelationshipTests;

use Rebing\GraphQL\Tests\Support\Models\Like;
use Rebing\GraphQL\Tests\Support\Models\Post;
use Rebing\GraphQL\Tests\Support\Models\User;
use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class MorphRelationshipTest extends TestCaseDatabase
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
            LikableInterfaceType::class,
            CommentType::class,
            LikeType::class,
            PostType::class,
            UserType::class,
        ]);
    }

    public function testMorphRelationship(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'User Name',
        ]);

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        /** @var Post $post */
        $post = Post::factory()->create([
            'user_id' => $user->id,
        ]);

        /** @var Like $like */
        $like = Like::factory()->make([
            'user_id' => $otherUser->id,
        ]);

        $post->likes()->save($like);

        self::assertNotNull($user->posts[0]->likes[0]->user);

        $query = <<<'GRAQPHQL'
{
  users {
    id
    posts {
      id
      title
      likes {
        id
        user {
            id
        }
      }
    }
  }
}
GRAQPHQL;

        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertSqlQueries(
            <<<'SQL'
select "users"."id" from "users";
select "posts"."id", "posts"."title", "posts"."user_id" from "posts" where "posts"."user_id" in (?, ?) order by "posts"."id" asc;
select "likes"."id", "likes"."user_id", "likes"."likable_id", "likes"."likable_type" from "likes" where "likes"."likable_id" in (?) and "likes"."likable_type" = ?;
select "users"."id" from "users" where "users"."id" in (?);
select * from "posts" where "posts"."id" = ? limit 1;
SQL
        );

        $expectedResult = [
            'data' => [
                'users' => [
                    [
                        'id' => $user->id,
                        'posts' => [
                            [
                                'id' => $post->id,
                                'title' => $post->title,
                                'likes' => [
                                    [
                                        'id' => $like->id,
                                        'user' => [
                                            'id' => $otherUser->id,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => $otherUser->id,
                        'posts' => [],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedResult, $result);
    }
}
