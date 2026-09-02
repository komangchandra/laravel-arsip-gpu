<?php

namespace App\Enums;

enum SignRouteStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Signed = 'signed';
    case RevisionRequested = 'revision_requested';
    case Cancelled = 'cancelled';
}
