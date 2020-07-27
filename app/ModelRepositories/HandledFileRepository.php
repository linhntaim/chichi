<?php

namespace App\ModelRepositories;

use App\ModelRepositories\Base\ModelRepository;
use App\Models\HandledFile;
use App\Models\HandledFileStore;
use App\Utils\ConfigHelper;
use App\Utils\HandledFiles\Filer\Filer;
use App\Utils\HandledFiles\Filer\ImageFiler;
use App\Utils\HandledFiles\Storage\LocalStorage;
use App\Utils\HandledFiles\Storage\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Class HandledFileRepository
 * @package App\ModelRepositories
 * @property HandledFile $model
 */
class HandledFileRepository extends ModelRepository
{
    public function modelClass()
    {
        return HandledFile::class;
    }

    public function createWithUploadedFile(UploadedFile $uploadedFile)
    {
        return $this->createWithFiler((new Filer())->fromExisted($uploadedFile, null, false));
    }

    public function createWithUploadedImageCasually(UploadedFile $uploadedFile)
    {
        $imageFiler = (new ImageFiler())->fromExisted($uploadedFile, null, false);
        $imageMaxWidth = ConfigHelper::get('image.upload.max_width');
        $imageMaxHeight = ConfigHelper::get('image.upload.max_height');
        if ($imageMaxWidth || $imageMaxHeight) {
            $imageFiler->imageResize($imageMaxWidth ? $imageMaxWidth : null, $imageMaxHeight ? $imageMaxHeight : null)
                ->imageSave();
        }
        if (ConfigHelper::get('image.upload.inline')) {
            $imageFiler->moveToInline();
        }
        return $this->createWithFiler($imageFiler);
    }

    /**
     * @param Filer $filer
     * @param array $options
     * @return Filer
     */
    protected function handleFilerWithOptions(Filer $filer, $options = [])
    {
        if (isset($options['public']) && $options['public']) {
            $filer->moveToPublic();
            if (ConfigHelper::get('managed_file.cloud_enabled')) {
                $filer->moveToCloud(null, true, ConfigHelper::get('managed_file.cloud_only'));
            }
        }
        return $filer;
    }

    public function createWithFiler(Filer $filer, $options = [])
    {
        $hasPostProcessed = isset($options['has_post_processed']) && $options['has_post_processed'];
        if (!$hasPostProcessed) {
            $filer = $this->handleFilerWithOptions($filer, $options);
        }

        $this->createWithAttributes([
            'name' => $filer->getName(),
            'mime' => $filer->getMime(),
            'size' => $filer->getSize(),
            'options_array_value' => $options,
            'handling' => $hasPostProcessed ? HandledFile::HANDLING_YES : HandledFile::HANDLING_NO,
        ]);

        $filer->eachStorage(function ($name, Storage $storage, $origin) {
            $this->model->handledFileStores()->create([
                'origin' => $origin ? HandledFileStore::ORIGIN_YES : HandledFileStore::ORIGIN_NO,
                'store' => $name,
                'data' => $storage->getData(),
            ]);
        });

        return $this->model;
    }

    public function handledWithFiler(Filer $filer)
    {
        if (!$this->model->ready) {
            $filer = $this->handleFilerWithOptions($filer, $this->model->options_array_value);
            $this->updateWithAttributes([
                'handling' => HandledFile::HANDLING_NO,
            ]);
            $this->model->handledFileStores()->delete();
            $filer->eachStorage(function ($name, Storage $storage, $origin) {
                $this->model->handledFileStores()->create([
                    'origin' => $origin ? HandledFileStore::ORIGIN_YES : HandledFileStore::ORIGIN_NO,
                    'store' => $name,
                    'data' => $storage->getData(),
                ]);
            });
        }
        return $this->model;
    }

    public function handlePostProcessed(callable $postProcessedCallback)
    {
        if (($originStorage = $this->model->originStorage) && $originStorage instanceof LocalStorage) {
            $postProcessedCallback($this->model);
        } else {
            if (!$this->model->ready) {
                $this->updateWithAttributes([
                    'handling' => HandledFile::HANDLING_NO,
                ]);
            }
        }
        return $this->model;
    }
}
