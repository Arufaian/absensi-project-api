<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;


class Handler extends ExceptionHandler
{
  public function register(): void
  {

  }

  protected function unauthenticated($request, AuthenticationException $exeption)
  {
    if ($request -> expectsJson()) {
      return response() -> json([
        'message' => 'Unauthenticated'
      ], 401);
    }

    return redirect() -> guest(route('login'));
  }
}