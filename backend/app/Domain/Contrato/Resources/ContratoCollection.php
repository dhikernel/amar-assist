<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ContratoCollection extends ResourceCollection
{
    public $collects = ContratoResource::class;
}
