<?php

declare(strict_types=1);

namespace App\Application\GraphQL\Logging;

use App\Infrastructure\Auth\JwtProvider;
use App\Infrastructure\ID\SnowflakeGenerator;

final class GraphQlRequestLogger
{
    public function __construct(
        private readonly GraphQlRequestLogRepositoryInterface $logs,
        private readonly SnowflakeGenerator $idGenerator,
        private readonly JwtProvider $jwtProvider
    ) {
    }

    /**
     * @param array<int, string> $rootFields
     * @param array<string, mixed>|null $variables
     */
    public function log(string $path, array $rootFields, ?array $variables, ?string $authorizationHeader): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $normalizedRootFields = array_values(array_unique(array_filter(array_map('trim', $rootFields), static fn (string $field): bool => $field !== '')));

        $this->logs->save(
            $this->idGenerator->nextId(),
            $path,
            implode(',', $normalizedRootFields),
            $this->summarizeVariables($variables),
            $this->extractUserId($authorizationHeader),
            $now
        );
    }

    /**
     * @param array<string, mixed>|null $variables
     * @return array<string, mixed>|null
     */
    private function summarizeVariables(?array $variables): ?array
    {
        if ($variables === null) {
            return null;
        }

        return $this->summarizeValue($variables, 0);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function summarizeValue(mixed $value, int $depth): mixed
    {
        if ($depth > 3) {
            return '[depth-limited]';
        }

        if (is_string($value)) {
            $length = strlen($value);
            if ($length > 120) {
                return [
                    'type' => 'string',
                    'length' => $length,
                    'preview' => substr($value, 0, 32),
                ];
            }

            return $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $summary = [];
        $count = 0;
        foreach ($value as $key => $item) {
            $count++;
            if ($count > 30) {
                $summary['__truncated__'] = true;
                break;
            }

            $summary[(string) $key] = $this->summarizeValue($item, $depth + 1);
        }

        return $summary;
    }

    private function extractUserId(?string $authorizationHeader): ?string
    {
        if ($authorizationHeader === null || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authorizationHeader, 7));
        if ($token === '') {
            return null;
        }

        try {
            $claims = $this->jwtProvider->verifyAccessToken($token);
            return $claims['sub'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
