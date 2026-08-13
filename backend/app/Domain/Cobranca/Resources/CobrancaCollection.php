<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CobrancaCollection extends ResourceCollection
{
    public $collects = CobrancaResource::class;
}
