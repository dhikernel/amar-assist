<?php

declare(strict_types=1);

namespace App\Domain\Cliente\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClienteCollection extends ResourceCollection
{
    public $collects = ClienteResource::class;
}
