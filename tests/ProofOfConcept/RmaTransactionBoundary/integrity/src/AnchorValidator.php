<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use stdClass;

final class AnchorValidator
{
    private const FIELDS = [
        'anchorVersion', 'anchorId', 'manifestId', 'manifestSha256', 'profileId',
        'profileSha256', 'approvalRef', 'verifierReleaseId', 'status',
    ];

    /** Pure decoded-object boundary; a string is not parsed, and duplicate-key proof is deferred. */
    public function validate(mixed $candidate): TrustAnchor|string
    {
        if ($candidate === null) {
            return 'ANCHOR_MISSING';
        }
        if (! $candidate instanceof stdClass || $candidate::class !== stdClass::class) {
            return 'ANCHOR_INVALID';
        }
        $fields = get_object_vars($candidate);
        if (count($fields) !== count(self::FIELDS)) {
            return 'ANCHOR_INVALID';
        }
        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $fields) || ! is_string($fields[$field])) {
                return 'ANCHOR_INVALID';
            }
        }
        foreach (self::FIELDS as $field) {
            $value = $fields[$field];
            if ($field === 'anchorVersion') {
                if ($value !== '1.0') {
                    return 'ANCHOR_VERSION_UNSUPPORTED';
                }
            } elseif ($field === 'status') {
                if ($value === 'REVOKED') {
                    return 'ANCHOR_REVOKED';
                }
                if ($value !== 'ACTIVE') {
                    return 'ANCHOR_INVALID';
                }
            } else {
                $pattern = in_array($field, ['manifestSha256', 'profileSha256'], true)
                    ? '/\A[0-9a-f]{64}\z/' : '/\A[A-Za-z0-9._-]{1,80}\z/';
                if (preg_match($pattern, $value) !== 1) {
                    return 'ANCHOR_INVALID';
                }
            }
        }

        return new TrustAnchor(...$fields);
    }
}
