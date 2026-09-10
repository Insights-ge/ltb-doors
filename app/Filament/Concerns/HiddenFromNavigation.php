<?php

namespace App\Filament\Concerns;

trait HiddenFromNavigation
{
    public function mountCanAuthorizeAccess(): void
    {
        abort(404);
    }

    public function hydrateCanAuthorizeAccess(): void
    {
        abort(404);
    }
}
