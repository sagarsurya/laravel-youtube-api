<?php

namespace App\Common\Services;

class FileUploadServices
{
    protected $upload_file_path;

    public function __construct(){
        $this->upload_file_path = "/uploads";
    }

    public function uploadVideos($request, $module) {
		$field_names = array_keys($_FILES);
		$uploaded_files = [];

		foreach ($field_names as $field_name) {
			if ($request->hasFile($field_name)) {
				$file = $request->file($field_name);
				$extension = $file->getClientOriginalExtension();
				$path = $this->upload_file_path . "/" . $module;

				// Check if the file is a valid video extension
				$allowedVideoExtensions = ['mp4', 'mov', 'ogg', 'qt'];
				if (in_array($extension, $allowedVideoExtensions)) {
					$filename = $field_name . "_" . time() . uniqid() . '.' . $extension;
					$file->storeAs($path, $filename, ['disk' => 'public']);

					$uploaded_files[] = $path . "/" . $filename;
				} else {
					return false;
				}
			}
		}
		if (!empty($uploaded_files)) {
			return $uploaded_files;
		}
		return false;
	}
}
