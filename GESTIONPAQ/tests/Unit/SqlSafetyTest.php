<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SqlSafetyTest extends TestCase
{
    private const FORBIDDEN_DIRECT_SQL_PATTERNS = [
        '/\bDB::(?:select|statement|unprepared)\s*\(/',
        '/\bPDO::(?:query|exec)\s*\(/',
        '/\b(?:mysqli_query|mysql_query)\s*\(/',
    ];

    private const RAW_METHODS = [
        'raw',
        'selectRaw',
        'whereRaw',
        'orWhereRaw',
        'havingRaw',
        'orHavingRaw',
        'orderByRaw',
        'groupByRaw',
    ];

    public function test_application_code_avoids_unsafe_sql_patterns(): void
    {
        $violations = [];

        foreach ($this->applicationFiles() as $filePath) {
            $contents = file_get_contents($filePath);

            if ($contents === false) {
                $violations[] = $this->relativePath($filePath).': unable to read file';
                continue;
            }

            foreach (self::FORBIDDEN_DIRECT_SQL_PATTERNS as $pattern) {
                if (! preg_match($pattern, $contents, $match, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                $violations[] = sprintf(
                    '%s:%d forbidden direct SQL call `%s`',
                    $this->relativePath($filePath),
                    $this->lineNumberFromOffset($contents, $match[0][1]),
                    $match[0][0],
                );
            }

            array_push($violations, ...$this->rawSqlViolations($filePath, $contents));
        }

        $this->assertSame([], $violations, "Unsafe SQL patterns found:\n".implode("\n", $violations));
    }

    /**
     * @return list<string>
     */
    private function applicationFiles(): array
    {
        $root = dirname(__DIR__, 2);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.DIRECTORY_SEPARATOR.'app', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function rawSqlViolations(string $filePath, string $contents): array
    {
        $tokens = token_get_all($contents);
        $violations = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            if (! $this->isRawSqlCall($tokens, $index)) {
                continue;
            }

            $openParenthesisIndex = $this->nextMeaningfulTokenIndex($tokens, $index + 1);

            if ($openParenthesisIndex === null || $tokens[$openParenthesisIndex] !== '(') {
                continue;
            }

            $firstArgumentTokens = $this->firstArgumentTokens($tokens, $openParenthesisIndex);

            if ($this->isStaticStringLiteral($firstArgumentTokens)) {
                continue;
            }

            $line = is_array($tokens[$index]) ? $tokens[$index][2] : 1;
            $method = is_array($tokens[$index]) ? $tokens[$index][1] : 'raw';

            $violations[] = sprintf(
                '%s:%d raw SQL call `%s` must use a literal SQL string as the first argument',
                $this->relativePath($filePath),
                $line,
                $method,
            );
        }

        return $violations;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function isRawSqlCall(array $tokens, int $index): bool
    {
        $token = $tokens[$index] ?? null;

        if (! is_array($token) || $token[0] !== T_STRING || ! in_array($token[1], self::RAW_METHODS, true)) {
            return false;
        }

        $previousIndex = $this->previousMeaningfulTokenIndex($tokens, $index - 1);

        if ($previousIndex === null) {
            return false;
        }

        if ($token[1] === 'raw') {
            $separator = $tokens[$previousIndex] ?? null;
            $classIndex = $this->previousMeaningfulTokenIndex($tokens, $previousIndex - 1);
            $classToken = $classIndex !== null ? ($tokens[$classIndex] ?? null) : null;

            return $this->tokenText($separator) === '::' && $this->tokenText($classToken) === 'DB';
        }

        return $this->tokenText($tokens[$previousIndex] ?? null) === '->';
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     * @return list<array{int, string, int}|string>
     */
    private function firstArgumentTokens(array $tokens, int $openParenthesisIndex): array
    {
        $argumentTokens = [];
        $depth = 1;
        $tokenCount = count($tokens);

        for ($index = $openParenthesisIndex + 1; $index < $tokenCount; $index++) {
            $token = $tokens[$index];
            $text = $this->tokenText($token);

            if ($text === '(') {
                $depth++;
            } elseif ($text === ')') {
                $depth--;

                if ($depth === 0) {
                    break;
                }
            } elseif ($depth === 1 && $text === ',') {
                break;
            }

            $argumentTokens[] = $token;
        }

        return $argumentTokens;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function isStaticStringLiteral(array $tokens): bool
    {
        $meaningfulTokens = array_values(array_filter(
            $tokens,
            fn ($token): bool => ! $this->isIgnorableToken($token)
        ));

        return count($meaningfulTokens) === 1
            && is_array($meaningfulTokens[0])
            && $meaningfulTokens[0][0] === T_CONSTANT_ENCAPSED_STRING;
    }

    /**
     * @param array{int, string, int}|string|null $token
     */
    private function isIgnorableToken(array|string|null $token): bool
    {
        return is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function previousMeaningfulTokenIndex(array $tokens, int $start): ?int
    {
        for ($index = $start; $index >= 0; $index--) {
            if (! $this->isIgnorableToken($tokens[$index] ?? null)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     */
    private function nextMeaningfulTokenIndex(array $tokens, int $start): ?int
    {
        $tokenCount = count($tokens);

        for ($index = $start; $index < $tokenCount; $index++) {
            if (! $this->isIgnorableToken($tokens[$index] ?? null)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array{int, string, int}|string|null $token
     */
    private function tokenText(array|string|null $token): string
    {
        return is_array($token) ? $token[1] : (string) $token;
    }

    private function lineNumberFromOffset(string $contents, int $offset): int
    {
        return substr_count(substr($contents, 0, $offset), "\n") + 1;
    }

    private function relativePath(string $path): string
    {
        $root = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;

        return str_replace($root, '', $path);
    }
}