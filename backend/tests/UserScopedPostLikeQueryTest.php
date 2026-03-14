<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Query\ListUserLikes\ListUserLikesQueryHandler;
use App\Application\Query\ListUserPosts\ListUserPostsQueryHandler;
use App\Domain\Post\Repository\PostRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class UserScopedPostLikeQueryTest extends TestCase
{
    public function testListUserPostsQueryHandlerUsesCurrentUserAndLimitClamp(): void
    {
        $repo = $this->createMock(PostRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findByUserIdWithImages')
            ->with('user-123', 100)
            ->willReturn([]);

        $handler = new ListUserPostsQueryHandler($repo);
        $result = $handler->handle('user-123', 999);

        self::assertSame([], $result);
    }

    public function testListUserLikesQueryHandlerUsesCurrentUserAndMinimumLimit(): void
    {
        $expected = [['id' => 'post-1', 'userId' => 'user-abc', 'likesCount' => 1, 'images' => []]];

        $repo = $this->createMock(PostRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findLikedByUserIdWithImages')
            ->with('user-abc', 1)
            ->willReturn($expected);

        $handler = new ListUserLikesQueryHandler($repo);
        $result = $handler->handle('user-abc', 0);

        self::assertSame($expected, $result);
    }
}
