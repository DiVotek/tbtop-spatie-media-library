<?php

namespace Tbtop\SpatieMediaLibrary\Tests\Fixtures;

use Closure;
use Illuminate\Http\Request;

final class RejectAll
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort(403, 'page-level middleware ran');
    }
}
