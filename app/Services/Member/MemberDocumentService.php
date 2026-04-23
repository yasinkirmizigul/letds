<?php

namespace App\Services\Member;

use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MemberDocumentService
{
    public function sync(Member $member, ?UploadedFile $file, bool $clearExisting = false): void
    {
        if ($file === null && !$clearExisting) {
            return;
        }

        if ($file === null) {
            $this->delete($member);

            return;
        }

        $oldPath = $member->filepath;
        $oldDisk = $member->documentDisk();

        $member->forceFill($this->store($file))->save();

        if ($oldPath) {
            Storage::disk($oldDisk)->delete($oldPath);
        }
    }

    public function delete(Member $member): void
    {
        if ($member->filepath) {
            Storage::disk($member->documentDisk())->delete($member->filepath);
        }

        $member->forceFill([
            'filepath' => null,
            'file_disk' => null,
            'file_original_name' => null,
            'file_mime_type' => null,
            'file_size' => null,
        ])->save();
    }

    protected function store(UploadedFile $file): array
    {
        return [
            'filepath' => $file->store('members/documents', 'local'),
            'file_disk' => 'local',
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }
}
