<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractApiController extends AbstractController
{
    protected function getJsonData(Request $request): array|JsonResponse
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }

        return $data;
    }

    /**
     * Parses a "Y-m-d" date string strictly, rejecting anything that doesn't
     * match that exact format (unlike the DateTimeImmutable constructor,
     * which silently accepts many other formats).
     *
     * Returns:
     * - null if $value is null (field simply not provided)
     * - a DateTimeImmutable on success
     * - a 400 JsonResponse if $value is present but not a valid Y-m-d date
     */
    protected function parseDate(?string $value, string $fieldName): \DateTimeImmutable|JsonResponse|null
    {
        if ($value === null) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        $errors = \DateTimeImmutable::getLastErrors();
        $hasErrors = $errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if (!$date || $hasErrors) {
            return $this->json([
                'error' => "Invalid {$fieldName} format. Expected Y-m-d"
            ], 400);
        }

        return $date;
    }
}