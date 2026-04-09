<?php

namespace Klaviyo\Reclaim\KlaviyoV3Sdk\Exception;

/**
 * Thrown when the Klaviyo API returns a 409 Conflict response.
 *
 * Carries the decoded API response body so callers can inspect structured error
 * metadata, including meta.duplicate_profile_id on profile-creation conflicts.
 */
class KlaviyoResourceConflictException extends KlaviyoApiException
{
    /**
     * @var array
     */
    private $responseBody;

    public function __construct($message = '', $code = 0, $previous = null, array $responseBody = [])
    {
        parent::__construct($message, $code, $previous);
        $this->responseBody = $responseBody;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }

    /**
     * Returns the duplicate_profile_id from the API error response body.
     * Present when a POST to api/profiles/ conflicts with an already-existing profile.
     *
     * @return string|null
     */
    public function getDuplicateProfileId(): ?string
    {
        return $this->responseBody['errors'][0]['meta']['duplicate_profile_id'] ?? null;
    }
}
