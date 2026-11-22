<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

/** G01-G09 only. The received binary string is never replaced or acquired here. */
final readonly class ManifestDigestVerifier
{
    private TrustedDigestVerifier $digests;

    public function __construct(AnchorProvider $anchors)
    {
        $this->digests = new TrustedDigestVerifier($anchors);
    }

    public function verify(mixed $context, mixed $candidateAnchor, string $rawBytes): ManifestVerificationResult
    {
        return ManifestVerificationResult::fromVerification($this, $context, $candidateAnchor, $rawBytes);
    }

    /** @internal Fresh evaluation facts, never accepted as caller-supplied verification evidence. */
    public function evaluate(mixed $context, mixed $candidateAnchor, string $rawBytes): array
    {
        // TD01 owns context/anchor/size/digest decisions; this preflight adds the
        // shared TD02/TD03 runtime floor before any anchor or manifest processing.
        $digest = PHP_VERSION_ID < 80400 || PHP_INT_SIZE !== 8
            || ! function_exists('hash') || ! function_exists('hash_equals')
            || ! function_exists('hash_algos')
            || ! in_array('sha256', hash_algos(), true)
            ? DigestComparisonResult::failure('CAPABILITY_UNAVAILABLE')
            : $this->digests->verify($context, $candidateAnchor, $rawBytes);
        $facts = [
            'manifestVerdict' => $digest->digestStatus === 'MATCH' ? 'VERIFIED' : ($digest->phaseVerdict === 'INCONCLUSIVE' ? 'INCONCLUSIVE' : 'DENIED'),
            'digestStatus' => $digest->digestStatus,
            'schemaStatus' => 'NOT_EVALUATED', 'canonicalStatus' => 'NOT_EVALUATED', 'bindingStatus' => 'NOT_EVALUATED',
            'trustMode' => $digest->trustMode, 'authenticity' => $digest->authenticity,
            'anchorId' => $digest->anchorId, 'approvalRef' => $digest->approvalRef, 'manifestId' => $digest->manifestId,
            'expectedDigest' => $digest->expectedDigest, 'actualDigest' => $digest->actualDigest,
            'primaryFailureCode' => $digest->primaryFailureCode, 'errors' => $digest->errors,
            'diagnosticsTruncated' => false, 'manifest' => null,
        ];
        if ($digest->digestStatus !== 'MATCH') {
            return $facts;
        }
        $parsed = (new StrictJsonTokenizer)->parse($rawBytes);
        if (! $parsed->valid) {
            return self::failure($facts, $parsed->error['code'], 'G05', $parsed->error['message']);
        }
        $validation = (new ManifestValidator)->validate($parsed->value);
        unset($parsed);
        if (! $validation->valid) {
            $facts['manifestVerdict'] = 'DENIED';
            $facts['schemaStatus'] = 'INVALID';
            $facts['primaryFailureCode'] = 'MANIFEST_SCHEMA_INVALID';
            $facts['diagnosticsTruncated'] = $validation->diagnosticsTruncated;
            $facts['errors'] = array_map(static fn (array $error): array => ['stage' => 'V06_V07'] + $error, $validation->errors);

            return $facts;
        }
        $manifest = $validation->manifest;
        $facts['schemaStatus'] = 'VALID';
        if (! CanonicalJson::isCanonical($manifest, $rawBytes)) {
            $facts['canonicalStatus'] = 'NONCANONICAL';

            return self::failure($facts, 'MANIFEST_NONCANONICAL', 'G07', 'Original raw manifest is not the exact canonical representation.');
        }
        $facts['canonicalStatus'] = 'CANONICAL';
        if ($manifest->manifestId !== $digest->manifestId) {
            $facts['bindingStatus'] = 'MISMATCH';

            return self::failure($facts, 'ANCHOR_MANIFEST_MISMATCH', 'G08', 'Decoded manifest identity does not match the verified anchor.');
        }
        // TD01 MATCH has established the immutable context's complete selected
        // tuple, profile and release binding. No provider lookup or reselection.
        if ($manifest->verificationMetadata['profileId'] !== $context->profileId
            || ! hash_equals($context->profileSha256, $manifest->verificationMetadata['profileSha256'])) {
            $facts['bindingStatus'] = 'MISMATCH';

            return self::failure($facts, 'PROFILE_MISMATCH', 'G08', 'Decoded profile does not match the verified selection.');
        }
        $facts['bindingStatus'] = 'MATCH';
        $facts['manifest'] = $manifest;

        return $facts;
    }

    private static function failure(array $facts, string $code, string $stage, string $message): array
    {
        $facts['manifestVerdict'] = 'DENIED';
        $facts['primaryFailureCode'] = $code;
        $facts['errors'] = [['stage' => $stage, 'path' => '$', 'code' => $code, 'message' => $message]];

        return $facts;
    }
}
