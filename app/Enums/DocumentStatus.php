<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Uploaded = 'uploaded';
    case ReadyToSign = 'ready_to_sign';
    case Updated = 'updated';
    case Routing = 'routing';
    case WaitingForSignatures = 'waiting_for_signatures';
    case NeedsRevision = 'needs_revision';
    case Signed = 'signed';
    case Stamped = 'stamped';
    case Archived = 'archived';
    case Cancelled = 'cancelled';
}
