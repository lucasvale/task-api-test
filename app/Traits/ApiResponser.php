<?php

namespace App\Traits;

use App\Enum\HttpStatus;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * JSON API Response format based from JSend pattern
 * URL:https://github.com/omniti-labs/jsend
 */
trait ApiResponser
{
    /**
     * Success Response Format
     *
     * @param array $data
     * @param HttpStatus $httpStatus
     * @param string|null $message
     * @return JsonResponse
     */
    protected function successResponse(
        array $data,
        HttpStatus $httpStatus = HttpStatus::HTTP_OK,
        ?string $message = null
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'data' => $data,
        ];
        if (!empty($message)) {
            $response = array_merge($response, ['message' => $message]);
        }

        return response()->json($response, $httpStatus->value);
    }

    /**
     * Failed Response Format
     *
     * @param array $data
     * @param HttpStatus $httpStatus
     * @return JsonResponse
     */
    protected function failResponse(array $data, HttpStatus $httpStatus = HttpStatus::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json(
            [
                'status' => 'fail',
                'data' => $data,
            ],
            $httpStatus->value
        );
    }

    /**
     * Error Response Format
     *
     * @param string $message
     * @param array $data
     * @param HttpStatus $httpStatus
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        array $data = [],
        HttpStatus $httpStatus = HttpStatus::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];
        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $httpStatus->value);
    }

    /**
     * Success Response With Meta Information Format
     *
     * @param array $data
     * @param array $meta
     * @param HttpStatus $httpStatus
     * @return JsonResponse
     */
    protected function successResponseWithMeta(
        array $data,
        array $meta,
        HttpStatus $httpStatus = HttpStatus::HTTP_OK
    ): JsonResponse {
        return response()->json(
            [
                'status' => 'success',
                'meta' => $meta,
                'data' => $data,
            ],
            $httpStatus->value
        );
    }

    /**
     * Download PDF File
     *
     * @param string $file
     * @param HttpStatus $httpStatus
     * @return StreamedResponse
     */
    protected function downloadPDFResponse(string $file, HttpStatus $httpStatus = HttpStatus::HTTP_OK): StreamedResponse
    {
        $headers = [
            'Content-type'                  => 'application/pdf',
            'Content-Disposition'           => 'attachment; filename=the-file.pdf',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
            'Pragma'                        => 'no-cache',
            'Cache-Control'                 => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'                       => 0,
        ];

        return response()->stream(
            function () use ($file) {
                $fileStream = fopen('php://output', 'w');
                fwrite($fileStream, $file);
                fclose($fileStream);
            },
            $httpStatus->value,
            $headers
        );
    }
}
