<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentSafety
{
    public function ensureCleanText(array $fields): void
    {
        $errors = [];
        $blockedTerms = config('launch.moderation.blocked_terms', []);
        $blockContact = (bool) config('launch.moderation.block_contact_in_public_fields', true);

        foreach ($fields as $label => $value) {
            $text = Str::of((string) $value)->lower()->squish()->value();

            if ($text === '') {
                continue;
            }

            foreach ($blockedTerms as $term) {
                if ($term !== '' && str_contains($text, $term)) {
                    $errors[$label] = ucfirst($label) . ' contains language that is not allowed for public listings.';
                    continue 2;
                }
            }

            if ($blockContact && ($this->containsPhoneNumber($text) || $this->containsUrl($text))) {
                $errors[$label] = ucfirst($label) . ' should not include direct phone numbers, WhatsApp numbers, or external links.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function inspectImage(?UploadedFile $image): void
    {
        if (! $image) {
            return;
        }

        $allowedMimeTypes = config('launch.moderation.image.allowed_mime_types', []);
        $dimensions = @getimagesize($image->getRealPath());

        if (! in_array($image->getMimeType(), $allowedMimeTypes, true) || ! is_array($dimensions)) {
            throw ValidationException::withMessages([
                'image' => 'Only JPEG, PNG, or WebP images are allowed.',
            ]);
        }

        [$width, $height] = $dimensions;

        $minWidth = (int) config('launch.moderation.image.min_width', 400);
        $minHeight = (int) config('launch.moderation.image.min_height', 300);
        $maxWidth = (int) config('launch.moderation.image.max_width', 6000);
        $maxHeight = (int) config('launch.moderation.image.max_height', 6000);

        if ($width < $minWidth || $height < $minHeight) {
            throw ValidationException::withMessages([
                'image' => "Image must be at least {$minWidth}x{$minHeight} pixels.",
            ]);
        }

        if ($width > $maxWidth || $height > $maxHeight) {
            throw ValidationException::withMessages([
                'image' => "Image must not exceed {$maxWidth}x{$maxHeight} pixels.",
            ]);
        }

        $name = Str::of($image->getClientOriginalName())->lower()->value();
        foreach (config('launch.moderation.blocked_terms', []) as $term) {
            if ($term !== '' && str_contains($name, $term)) {
                throw ValidationException::withMessages([
                    'image' => 'Image filename contains blocked terms and was rejected.',
                ]);
            }
        }
    }

    private function containsPhoneNumber(string $text): bool
    {
        return (bool) preg_match('/(?:\+?\d[\d\-\s()]{7,}\d)/', $text);
    }

    private function containsUrl(string $text): bool
    {
        return str_contains($text, 'http://')
            || str_contains($text, 'https://')
            || str_contains($text, 'www.')
            || str_contains($text, 'wa.me/');
    }
}
