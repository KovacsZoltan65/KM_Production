<?php

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class BusinessCacheArchitectureTest extends TestCase
{
    public function test_application_code_does_not_flush_the_entire_cache(): void
    {
        foreach ($this->phpFiles(base_path('app')) as $file) {
            $this->assertStringNotContainsString(
                'Cache::flush(',
                (string) file_get_contents($file->getPathname()),
                "A teljes alkalmazáscache törlése tiltott: {$file->getPathname()}",
            );
        }
    }

    public function test_business_cache_owners_use_named_central_keys(): void
    {
        foreach ($this->phpFiles(base_path('app/Services')) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            if (! str_contains($contents, 'Cache::remember(')) {
                continue;
            }

            $this->assertStringContainsString(
                'BusinessCacheKey::',
                $contents,
                "A cache owner nem központi kulcsot használ: {$file->getPathname()}",
            );
            $this->assertStringNotContainsString(
                'BusinessCacheKey::make(',
                $contents,
                "A cache owner név szerinti kulcsmetódus helyett nyers kulcsot képez: {$file->getPathname()}",
            );
        }
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }
}
