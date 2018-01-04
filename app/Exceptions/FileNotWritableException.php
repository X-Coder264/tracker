<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class FileNotWritableException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function render($request): RedirectResponse
    {
        return back()->withInput()->with('error', $this->getMessage());
    }
}
