<?php

namespace Parth\LaravelRbacSuite\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a standardized JSON response.
     */
    protected function jsonResponse(bool $success, string $message, array $data = [], int $status = 200)
    {
        return response()->json(array_merge([
            'success' => $success,
            'message' => $message,
        ], $data), $status);
    }
}
