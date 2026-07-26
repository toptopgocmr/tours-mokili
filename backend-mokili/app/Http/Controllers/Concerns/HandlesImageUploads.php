<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Shared logic for the listing forms (Voyage, Logement, Voiture,
 * Divertissement, Marketplace, Fret) that let a partner/admin attach a
 * cover photo. Stores on the "public" disk (storage/app/public, served via
 * the public/storage symlink) under a per-module folder.
 *
 * Note: on Railway (and most PaaS hosts) the filesystem is ephemeral unless
 * a persistent volume is attached to the service - uploaded photos will be
 * lost on the next deploy/restart without one. Fine for local development;
 * flagged separately for production.
 */
trait HandlesImageUploads
{
    /**
     * Returns the path to store on the model's `image` column: the newly
     * uploaded file's path if one was sent, the existing path unchanged if
     * no new file was sent, or null if the caller asked to remove it.
     */
    protected function resolveImagePath(Request $request, ?string $existingPath, string $folder): ?string
    {
        if ($request->hasFile('image')) {
            if ($existingPath) {
                Storage::disk('public')->delete($existingPath);
            }

            return $request->file('image')->store($folder, 'public');
        }

        if ($request->boolean('remove_image')) {
            if ($existingPath) {
                Storage::disk('public')->delete($existingPath);
            }

            return null;
        }

        return $existingPath;
    }
}
