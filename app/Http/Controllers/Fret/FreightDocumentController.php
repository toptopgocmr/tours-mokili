<?php

namespace App\Http\Controllers\Fret;

use App\Http\Controllers\Controller;
use App\Models\Fret\FreightDocument;
use App\Models\Fret\FreightOrder;
use App\Models\Fret\FreightPartnerProfile;
use App\Models\Fret\QuoteRequest;
use App\Models\User;
use App\Services\Fret\FreightAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pieces (candidature, facture commerciale, colisage, preuve de livraison...).
 * Stockage PRIVE : aucun lien public, telechargement uniquement ici,
 * avec controle d'acces et journal des consultations sensibles.
 */
class FreightDocumentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target' => ['required', 'in:partner_profile,quote_request,order'],
            'reference' => ['nullable', 'string', 'max:40'],
            'type' => ['required', 'string', 'max:40'],
            'expires_at' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        $user = $request->user();
        $owner = $this->resolveTarget($user, $data['target'], $data['reference'] ?? null);

        $file = $request->file('file');
        $path = $file->store('freight/'.$data['target'], 'local');

        $document = FreightDocument::create([
            'documentable_type' => $owner::class,
            'documentable_id' => $owner->getKey(),
            'user_id' => $user->id,
            'type' => $data['type'],
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'path' => $path,
            'visibility' => $data['target'] === 'partner_profile' ? 'staff' : 'parties',
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        FreightAudit::log('document.uploaded', $document, 'user:'.$user->id, null, ['type' => $data['type'], 'target' => $data['target']]);

        return $request->expectsJson() ? response()->json($document, 201) : back()->with('success', 'Document ajoute.');
    }

    public function download(Request $request, FreightDocument $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->canAccess($user, $document), 403);

        if ($document->documentable_type === FreightPartnerProfile::class || $user->isStaff()) {
            FreightAudit::log('document.viewed', $document, 'user:'.$user->id);
        }

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function canAccess(User $user, FreightDocument $document): bool
    {
        if ($user->isStaff() || $document->user_id === $user->id) {
            return true;
        }
        if ($document->visibility === 'staff') {
            return false;
        }

        $owner = $document->documentable;

        return match (true) {
            $owner instanceof FreightOrder => in_array($user->id, [$owner->user_id, $owner->partner_user_id], true),
            $owner instanceof QuoteRequest => $owner->user_id === $user->id || $owner->partner_user_id === $user->id
                || $owner->quotes()->where('partner_user_id', $user->id)->exists(),
            default => false,
        };
    }

    private function resolveTarget(User $user, string $target, ?string $reference)
    {
        if ($target === 'partner_profile') {
            return FreightPartnerProfile::where('user_id', $user->id)->firstOrFail();
        }

        if ($target === 'quote_request') {
            $r = QuoteRequest::where('reference', $reference)->firstOrFail();
            abort_unless($r->user_id === $user->id || $r->partner_user_id === $user->id, 403);

            return $r;
        }

        $o = FreightOrder::where('reference', $reference)->firstOrFail();
        abort_unless(in_array($user->id, [$o->user_id, $o->partner_user_id], true), 403);

        return $o;
    }
}
