<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\ModelRepositories;

use App\ModelRepositories\Base\ModelRepository;
use App\Models\HandledFile;
use App\Models\HandledFileStore;
use App\Utils\ConfigHelper;
use App\Utils\HandledFiles\Filer\Filer;
use App\Utils\HandledFiles\Filer\ImageFiler;
use App\Utils\HandledFiles\Storage\LocalStorage;
use App\Utils\HandledFiles\Storage\ScanStorage;
use App\Utils\HandledFiles\Storage\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

/**
 * Class HandledFileRepository
 * @package App\ModelRepositories
 * @property HandledFile $model
 */
class HandledFileRepository extends ModelRepository
{
    protected $scan = false;
    protected $public = false;
    protected $inline = false;

    public function modelClass()
    {
        return HandledFile::class;
    }

    public function useScan($scan = true)
    {
        $this->scan = $scan;
        return $this;
    }

    public function usePublic($public = true)
    {
        $this->public = $public;
        return $this;
    }

    public function useInline($inline = true)
    {
        $this->inline = $inline;
        return $this;
    }

    /**
     * @return HandledFile[]|Collection
     * @throws
     */
    public function getForScanning()
    {
        return $this->catch(function () {
            return $this->query()
                ->whereHas('handledFileStores', function ($query) {
                    $query->where('store', 'scan');
                })
                ->get();
        });
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param array $options
     * @param string $name
     * @return HandledFile
     * @throws
     */
    public function createWithUploadedFile(UploadedFile $uploadedFile, $options = [], $name = null)
    {
        return $this->createWithFiler(
            (new Filer())->fromExisted($uploadedFile, false, false),
            $options,
            $name
        );
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param array $options
     * @param int|null|bool $imageMaxWidth
     * @param int|null|bool $imageMaxHeight
     * @param string|null $name
     * @return HandledFile
     * @throws
     */
    public function createWithUploadedImageFile(UploadedFile $uploadedFile, $options = [], $imageMaxWidth = null, $imageMaxHeight = null, $name = null)
    {
        return $this->createWithImageFiler(
            (new ImageFiler())->fromExisted($uploadedFile, false, false),
            $options,
            $imageMaxWidth,
            $imageMaxHeight,
            $name
        );
    }

    /**
     * @param ImageFiler $imageFiler
     * @param array $options
     * @param int|null|bool $imageMaxWidth
     * @param int|null|bool $imageMaxHeight
     * @return HandledFile
     */
    public function createWithImageFiler(ImageFiler $imageFiler, $options = [], $imageMaxWidth = null, $imageMaxHeight = null, $name = null)
    {
        if ($imageMaxWidth !== false && empty($imageMaxWidth)) {
            $imageMaxWidth = ConfigHelper::get('handled_file.image.max_width');
        }
        if ($imageMaxHeight !== false && empty($imageMaxHeight)) {
            $imageMaxHeight = ConfigHelper::get('handled_file.image.max_height');
        }
        if ($imageMaxWidth || $imageMaxHeight) {
            $imageFiler->imageResize($imageMaxWidth ? $imageMaxWidth : null, $imageMaxHeight ? $imageMaxHeight : null)
                ->imageSave();
        }
        if (ConfigHelper::get('handled_file.image.inline') && !isset($options['inline'])) {
            $options['inline'] = true;
        }
        return $this->createWithFiler($imageFiler, $options, $name);
    }

    /**
     * @param Filer $filer
     * @param array $options
     * @return Filer
     */
    protected function handleFilerWithOptions(Filer $filer, $options = [])
    {
        if (isset($options['scan']) && $options['scan']) {
            $filer->moveToScan();
        } elseif (isset($options['inline']) && $options['inline']) {
            $filer->moveToInline();
        } elseif (isset($options['public']) && $options['public']) {
            $filer->moveToPublic();
            if (ConfigHelper::get('handled_file.cloud.enabled')) {
                $filer->moveToCloud(null, true, ConfigHelper::get('handled_file.cloud.only'));
            }
        }
        return $filer;
    }

    public function createWithFiler(Filer $filer, $options = [], $name = null)
    {
        if ($this->scan) {
            if (ConfigHelper::get('handled_file.scan.enabled')) {
                $options['scan'] = true;
            }
            $this->scan = false;
        }
        if ($this->public) {
            $options['public'] = true;
            $this->public = false;
        }
        if ($this->inline) {
            $options['inline'] = true;
            $this->inline = false;
        }

        $filer = $this->handleFilerWithOptions($filer, $options)->setName($name);

        $this->createWithAttributes([
            'title' => (function ($name) {
                $names = explode('.', $name);
                if (count($names) > 1) {
                    array_pop($names);
                    return implode('.', $names);
                }
                return $name;
            })($filer->getName()),
            'name' => $filer->getName(),
            'mime' => $filer->getMime(),
            'size' => $filer->getSize(),
            'options_array_value' => $options,
            'handling' => $options['scan'] ?
                HandledFile::HANDLING_SCAN
                : HandledFile::HANDLING_NO,
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

    public function updateWithAttributes(array $attributes = [])
    {
        if (empty($attributes['title'])) {
            unset($attributes['title']);
        }
        if (empty($attributes['name'])) {
            unset($attributes['name']);
        }
        return parent::updateWithAttributes($attributes);
    }

    public function updateStoresWithFiler(Filer $filer)
    {
        $this->model->handledFileStores()->delete();
        $filer->eachStorage(function ($name, Storage $storage, $origin) {
            $this->model->handledFileStores()->create([
                'origin' => $origin ? HandledFileStore::ORIGIN_YES : HandledFileStore::ORIGIN_NO,
                'store' => $name,
                'data' => $storage->getData(),
            ]);
        });
        return $this->model;
    }

    public function handledWithFiler(Filer $filer, $options = null)
    {
        if (!$this->model->ready) {
            $options = is_null($options) ? $this->model->options_array_value : $options;
            $this->updateStoresWithFiler(
                $this->handleFilerWithOptions($filer, $options)
            );
            $this->updateWithAttributes([
                'handling' => HandledFile::HANDLING_NO,
                'options_overridden_array_value' => $options,
            ]);
        }
        return $this->model;
    }

    public function scan()
    {
        if ($this->model->handling == HandledFile::HANDLING_SCAN) {
            if (($originStorage = $this->model->originStorage) && $originStorage instanceof ScanStorage) {
                if ($originStorage->scan()) {
                    $this->handledWithFiler(
                        (new Filer())->fromStorage($originStorage),
                        (function ($options) {
                            unset($options['scan']);
                            return $options;
                        })($this->model->options_array_value)
                    );
                }
            }
        }
        return $this->model;
    }
}
