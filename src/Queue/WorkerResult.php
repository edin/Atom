<?php

declare(strict_types=1);

namespace Atom\Queue;

enum WorkerResult: string
{
    case Empty = "empty";
    case Completed = "completed";
    case Released = "released";
    case Failed = "failed";
}
