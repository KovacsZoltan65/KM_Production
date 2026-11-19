<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

final readonly class TrustedDigestVerifier
{
    public const MAX_RAW_BYTES = 16777216;

    public function __construct(private AnchorProvider $anchors) {}

    public function verify(mixed $context, mixed $candidateAnchor, string $rawBytes): DigestComparisonResult
    {
        return DigestComparisonResult::fromVerification($this, $context, $candidateAnchor, $rawBytes);
    }

    /**
     * @internal Runs every TD01 gate once. Returned facts are data, not a reusable proof.
     *
     * @return DigestComparisonResult|array{matched: bool, selected: TrustAnchor, mode: string, actualDigest: string}
     */
    public function evaluate(mixed $context, mixed $candidateAnchor, string $rawBytes): DigestComparisonResult|array
    {
        if (! $context instanceof TrustContext) {
            return DigestComparisonResult::failure('CONFIG_INVALID');
        }
        if (in_array($context->trustMode, ['DEPLOYMENT', 'PROTECTED_DEPLOYMENT', 'EXTERNAL_ANCHOR'], true)) {
            return DigestComparisonResult::failure('CAPABILITY_UNAVAILABLE');
        }
        if (! in_array($context->trustMode, ['FIXTURE', 'OPERATOR_ATTESTED'], true)
            || trim($context->selectionOrigin) === ''
            || trim($context->selectionRef) === '') {
            return DigestComparisonResult::failure('CONFIG_INVALID');
        }
        $validator = new AnchorValidator;
        $expected = $validator->validate($context->expectedAnchor());
        if (! $expected instanceof TrustAnchor) {
            return DigestComparisonResult::failure('CONFIG_INVALID');
        }
        if (PHP_INT_SIZE !== 8 || ! function_exists('hash') || ! function_exists('hash_equals')
            || ! in_array('sha256', hash_algos(), true)) {
            return DigestComparisonResult::failure('CAPABILITY_UNAVAILABLE');
        }

        $selected = $this->anchors->selectedAnchor();
        if ($selected === null) {
            return DigestComparisonResult::failure('ANCHOR_MISSING');
        }
        $candidate = $validator->validate($candidateAnchor);
        if (is_string($candidate)) {
            return DigestComparisonResult::failure($candidate);
        }
        $validatedSelection = $validator->validate((object) get_object_vars($selected));
        if (is_string($validatedSelection)) {
            return DigestComparisonResult::failure($validatedSelection);
        }
        foreach ([
            'ANCHOR_SELECTION_MISMATCH' => ['anchorVersion', 'anchorId', 'manifestId', 'manifestSha256', 'approvalRef', 'status'],
            'PROFILE_MISMATCH' => ['profileId', 'profileSha256'],
            'VERIFIER_RELEASE_MISMATCH' => ['verifierReleaseId'],
        ] as $code => $fields) {
            foreach ($fields as $field) {
                if (! $this->sameField($field, $expected->{$field}, $selected->{$field})
                    || ! $this->sameField($field, $selected->{$field}, $candidate->{$field})) {
                    return DigestComparisonResult::failure($code);
                }
            }
        }
        if (strlen($rawBytes) > self::MAX_RAW_BYTES) {
            return DigestComparisonResult::failure('MANIFEST_SIZE_EXCEEDED');
        }
        $actualDigest = hash('sha256', $rawBytes);
        $matched = hash_equals($selected->manifestSha256, $actualDigest);

        return ['matched' => $matched, 'selected' => $selected, 'mode' => $context->trustMode, 'actualDigest' => $actualDigest];
    }

    private function sameField(string $field, string $expected, string $actual): bool
    {
        return in_array($field, ['manifestSha256', 'profileSha256'], true)
            ? hash_equals($expected, $actual) : $expected === $actual;
    }
}
