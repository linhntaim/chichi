<?php

namespace App\Models;

use App\Models\Base\Model;
use App\Utils\ConfigHelper;
use App\Utils\HandledFiles\Storage\CloudStorage;
use App\Utils\HandledFiles\Storage\ExternalStorage;
use App\Utils\HandledFiles\Storage\HandledStorage;
use App\Utils\HandledFiles\Storage\InlineStorage;
use App\Utils\HandledFiles\Storage\IResponseStorage;
use App\Utils\HandledFiles\Storage\IUrlStorage;
use App\Utils\HandledFiles\Storage\PrivateStorage;
use App\Utils\HandledFiles\Storage\PublicStorage;
use App\Utils\HandledFiles\Storage\Storage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class HandledFile
 * @package App\Models
 * @property int $id
 * @property string $name
 * @property string $mime
 * @property Collection $handledFileStores
 */
class HandledFile extends Model
{
    protected $table = 'handled_files';

    protected $fillable = [
        'name',
        'mime',
        'size',
    ];

    protected $visible = [
        'id',
        'name',
        'url',
    ];

    protected $appends = [
        'url',
    ];

    public function getUrlAttribute()
    {
        return $this->tryStorage(
            function (Storage $storage, HandledFileStore $store) {
                if ($storage instanceof InlineStorage) {
                    return route('handled_file.show', ['id' => $this->id]) . '?_inline=1';
                }
                return $storage->setData($store->data)->getUrl();
            },
            function (Storage $storage) {
                return $storage instanceof IUrlStorage;
            }
        );
    }

    public function handledFileStores()
    {
        return $this->hasMany(HandledFileStore::class, 'handled_file_id', 'id');
    }

    public function delete()
    {
        parent::delete();
        $this->tryStorage(
            function (Storage $storage, HandledFileStore $store) {
                return $storage->setData($store->data)->delete();
            },
            function (Storage $storage) {
                return $storage instanceof HandledStorage;
            }
        );
        return true;
    }

    public function responseDownload($name = null, $headers = [])
    {
        if (empty($name)) $name = $this->name;

        return $this->tryStorage(
            function (Storage $storage, HandledFileStore $store) use ($name, $headers) {
                return $storage->setData($store->data)->responseDownload($name, $this->mime, $headers);
            },
            function (Storage $storage) {
                return $storage instanceof IResponseStorage;
            }
        );
    }

    public function responseFile($headers = [])
    {
        return $this->tryStorage(
            function (Storage $storage, HandledFileStore $store) use ($headers) {
                return $storage->setData($store->data)->responseFile($this->mime, $headers);
            },
            function (Storage $storage) {
                return $storage instanceof IResponseStorage;
            }
        );
    }

    public function tryStorage(callable $tryCallback, callable $filterCallback = null)
    {
        $handledFileStores = $this->handledFileStores;
        $try = function (Storage $storage) use ($handledFileStores, $tryCallback) {
            if ($store = $handledFileStores->firstWhere('store', $storage->getName())) {
                return $tryCallback($storage, $store);
            }
            return false;
        };
        $storagePriorities = [
            new ExternalStorage(),
            new InlineStorage(),
            ConfigHelper::get('managed_file.cloud_enabled') ?
                new CloudStorage() : null,
            new PublicStorage(),
            new PrivateStorage(),
        ];
        foreach ($storagePriorities as $storage) {
            if (!$storage || ($filterCallback && !$filterCallback($storage))) continue;
            if (($result = $try($storage)) !== false) {
                return $result;
            }
        }
        return null;
    }
}