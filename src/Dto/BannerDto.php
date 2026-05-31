<?php

declare(strict_types=1);

namespace PromoBanner\Dto;

/**
 * Immutable value object representing a single promo banner configuration.
 *
 * Constructed from a raw database row so the rest of the codebase never
 * touches raw array keys directly.
 */
final class BannerDto
{
    public readonly int    $id;
    public readonly string $title;
    public readonly string $body;
    public readonly string $ctaUrl;
    public readonly string $ctaLabel;
    public readonly string $targetSex;       // 'male' | 'female' | 'any'
    public readonly string $targetLanguage;  // ISO-639-1 code or 'any'
    public readonly bool   $isActive;
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(array $row)
    {
        $this->id             = (int)    $row['id'];
        $this->title          = (string) $row['title'];
        $this->body           = (string) $row['body'];
        $this->ctaUrl         = (string) $row['cta_url'];
        $this->ctaLabel       = (string) $row['cta_label'];
        $this->targetSex      = (string) $row['target_sex'];
        $this->targetLanguage = (string) $row['target_language'];
        $this->isActive       = (bool)   $row['is_active'];
        $this->createdAt      = new \DateTimeImmutable($row['created_at'] ?? 'now');
    }
}
